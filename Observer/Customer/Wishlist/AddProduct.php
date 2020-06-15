<?php

namespace Apsis\One\Observer\Customer\Wishlist;

use Apsis\One\Model\Profile;
use Apsis\One\Model\Service\Profile as ProfileServiceProvider;
use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Apsis\One\Model\Service\Core as ApsisCoreHelper;
use Apsis\One\Model\EventFactory;
use Apsis\One\Model\ResourceModel\Event as EventResource;
use Apsis\One\Model\Event;
use Magento\Store\Model\ScopeInterface;
use Magento\Wishlist\Model\Wishlist;
use Magento\Catalog\Model\Product;
use Magento\Wishlist\Model\Item as WishlistItem;
use Apsis\One\Model\Service\Config as ApsisConfigHelper;
use Magento\Store\Model\Store;
use Apsis\One\Model\Service\Product as ProductServiceProvider;

class AddProduct implements ObserverInterface
{
    /**
     * @var ProfileServiceProvider
     */
    private $profileServiceProvider;

    /**
     * @var ProductServiceProvider
     */
    private $productServiceProvider;

    /**
     * @var ApsisCoreHelper
     */
    private $apsisCoreHelper;

    /**
     * @var EventFactory
     */
    private $eventFactory;

    /**
     * @var EventResource
     */
    private $eventResource;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * AddProduct constructor.
     *
     * @param ApsisCoreHelper $apsisCoreHelper
     * @param EventFactory $eventFactory
     * @param EventResource $eventResource
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductServiceProvider $productServiceProvider
     * @param ProfileServiceProvider $profileServiceProvider
     */
    public function __construct(
        ApsisCoreHelper $apsisCoreHelper,
        EventFactory $eventFactory,
        EventResource $eventResource,
        CustomerRepositoryInterface $customerRepository,
        ProductServiceProvider $productServiceProvider,
        ProfileServiceProvider $profileServiceProvider
    ) {
        $this->profileServiceProvider = $profileServiceProvider;
        $this->productServiceProvider = $productServiceProvider;
        $this->customerRepository = $customerRepository;
        $this->eventFactory = $eventFactory;
        $this->apsisCoreHelper = $apsisCoreHelper;
        $this->eventResource = $eventResource;
    }

    public function execute(Observer $observer)
    {
        try {
            /** @var Wishlist $wishlist */
            $wishlist = $observer->getEvent()->getWishlist();
            $store = $wishlist->getStore();
            /** @var Customer $customer */
            $customer = $this->customerRepository->getById($wishlist->getCustomerId());
            $profile = $this->profileServiceProvider
                ->getProfileByEmailAndStoreId($customer->getEmail(), $store->getId());

            if ($customer && $this->isOkToProceed($store) && $profile) {
                /** @var Product $product */
                $product = $observer->getEvent()->getProduct();
                /** @var WishlistItem $item */
                $item = $observer->getEvent()->getItem();

                $eventModel = $this->eventFactory
                    ->create()
                    ->setEventType(Event::EVENT_TYPE_CUSTOMER_ADDED_PRODUCT_TO_WISHLIST)
                    ->setEventData(
                        $this->apsisCoreHelper->serialize($this->getDataArr($wishlist, $store, $item, $product))
                    )
                    ->setProfileId($profile->getId())
                    ->setCustomerId($wishlist->getCustomerId())
                    ->setStoreId($store->getId())
                    ->setEmail($customer->getEmail())
                    ->setStatus(Profile::SYNC_STATUS_PENDING);
                $this->eventResource->save($eventModel);
            }
        } catch (Exception $e) {
            $this->apsisCoreHelper->logMessage(__METHOD__, $e->getMessage());
        }
        return $this;
    }

    /**
     * @param Store $store
     *
     * @return bool
     */
    private function isOkToProceed(Store $store)
    {
        $account = $this->apsisCoreHelper->isEnabled(ScopeInterface::SCOPE_STORES, $store->getStoreId());

        $event = (boolean) $this->apsisCoreHelper->getStoreConfig(
            $store,
            ApsisConfigHelper::CONFIG_APSIS_ONE_EVENTS_CUSTOMER_WISHLIST
        );

        return ($account && $event);
    }

    /**
     * @param Wishlist $wishlist
     * @param Store $store
     * @param WishlistItem $item
     * @param Product $product
     *
     * @return array
     */
    private function getDataArr(Wishlist $wishlist, Store $store, WishlistItem $item, Product $product)
    {
        $data = [
            'wishlistId' => (int) $wishlist->getId(),
            'wishlistItemId' => (int) $item->getId(),
            'wishlistName' => (string) $wishlist->getName(),
            'customerId' => (int) $wishlist->getCustomerId(),
            'websiteName' => (string) $this->apsisCoreHelper->getWebsiteNameFromStoreId($store->getId()),
            'storeName' => (string) $this->apsisCoreHelper->getStoreNameFromId($store->getId()),
            'productId' => (int) $product->getId(),
            'sku' => (string) $product->getSku(),
            'name' => (string) $product->getName(),
            'productUrl' => (string) $product->getProductUrl(),
            'productImageUrl' => (string) $this->productServiceProvider->getProductImageUrl($product),
            'catalogPriceAmount' => (float) $this->apsisCoreHelper->round($product->getPrice()),
            'qty' => (float) $item->getQty(),
            'currencyCode' => (string) $store->getCurrentCurrencyCode(),
        ];
        return $data;
    }
}
