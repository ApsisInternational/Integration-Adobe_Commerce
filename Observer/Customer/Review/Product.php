<?php

namespace Apsis\One\Observer\Customer\Review;

use Apsis\One\Model\Profile;
use Apsis\One\Model\ResourceModel\Profile as ProfileResource;
use Apsis\One\Model\ResourceModel\Profile\CollectionFactory as ProfileCollectionFactory;
use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Review\Model\Review;
use Apsis\One\Model\Service\Core as ApsisCoreHelper;
use Apsis\One\Model\EventFactory;
use Apsis\One\Model\ResourceModel\Event as EventResource;
use Apsis\One\Model\Event;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Customer\Model\Customer;
use Apsis\One\Model\Service\Config as ApsisConfigHelper;
use Magento\Store\Model\ScopeInterface;
use Apsis\One\Model\Events\Historical\Reviews\Data;

class Product implements ObserverInterface
{
    /**
     * @var ProfileCollectionFactory
     */
    private $profileCollectionFactory;

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
     * @var ProfileResource
     */
    private $profileResource;

    /**
     * @var Data
     */
    private $reviewData;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Product constructor.
     *
     * @param ApsisCoreHelper $apsisCoreHelper
     * @param EventFactory $eventFactory
     * @param EventResource $eventResource
     * @param ProfileResource $profileResource
     * @param Data $reviewData
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProfileCollectionFactory $profileCollectionFactory
     */
    public function __construct(
        ApsisCoreHelper $apsisCoreHelper,
        EventFactory $eventFactory,
        EventResource $eventResource,
        ProfileResource $profileResource,
        Data $reviewData,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository,
        ProfileCollectionFactory $profileCollectionFactory
    ) {
        $this->profileCollectionFactory = $profileCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->reviewData = $reviewData;
        $this->profileResource = $profileResource;
        $this->eventFactory = $eventFactory;
        $this->apsisCoreHelper = $apsisCoreHelper;
        $this->eventResource = $eventResource;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Review $reviewObject */
            $reviewObject = $observer->getEvent()->getDataObject();

            if (empty($reviewObject->getCustomerId())) {
                return $this;
            }

            /** @var MagentoProduct $product */
            $product = $this->getProductById($reviewObject->getEntityPkValue());
            /** @var Customer $customer */
            $customer = $this->customerRepository->getById($reviewObject->getCustomerId());
            $profile = $this->profileCollectionFactory->create()
                ->loadByEmailAndStoreId($customer->getEmail(), $this->apsisCoreHelper->getStore()->getId());

            if ($customer && $product && $this->isOkToProceed() && $profile && $reviewObject->isApproved()) {
                $eventModel = $this->eventFactory->create()
                    ->setEventType(Event::EVENT_TYPE_CUSTOMER_LEFT_PRODUCT_REVIEW)
                    ->setEventData(
                        $this->apsisCoreHelper->serialize(
                            $this->reviewData->getDataArr($reviewObject, $product, $this->apsisCoreHelper)
                        )
                    )
                    ->setProfileId($profile->getId())
                    ->setCustomerId($reviewObject->getCustomerId())
                    ->setStoreId($this->apsisCoreHelper->getStore()->getId())
                    ->setEmail($customer->getEmail())
                    ->setStatus(Profile::SYNC_STATUS_PENDING);
                $profile->setCustomerSyncStatus(Profile::SYNC_STATUS_PENDING);
                $this->eventResource->save($eventModel);
                $this->profileResource->save($profile);
            }
        } catch (Exception $e) {
            $this->apsisCoreHelper->logMessage(__METHOD__, $e->getMessage(), $e->getTraceAsString());
        }
        return $this;
    }

    /**
     * @return bool
     */
    private function isOkToProceed()
    {
        $store = $this->apsisCoreHelper->getStore();
        $account = $this->apsisCoreHelper->isEnabled(ScopeInterface::SCOPE_STORES, $store->getStoreId());

        $event = (boolean) $this->apsisCoreHelper->getStoreConfig(
            $store,
            ApsisConfigHelper::CONFIG_APSIS_ONE_EVENTS_CUSTOMER_REVIEW
        );

        return ($account && $event);
    }

    /**
     * @param int $productId
     * @return bool|ProductInterface
     */
    private function getProductById(int $productId)
    {
        try {
            return $this->productRepository->getById($productId);
        } catch (Exception $e) {
            $this->apsisCoreHelper->logMessage(__METHOD__, $e->getMessage(), $e->getTraceAsString());
            return false;
        }
    }
}
