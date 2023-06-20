<?php

namespace Apsis\One\Observer\Sales\Cart;

use Apsis\One\Observer\AbstractObserver;
use Apsis\One\Service\ProfileService;
use Apsis\One\Service\Sub\SubEventService;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\Item;
use Throwable;

class AddProductObserver extends AbstractObserver
{
    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @param ProfileService $profileService
     * @param Registry $registry
     * @param CustomerRepositoryInterface $customerRepository
     * @param SubEventService $subEventService
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        ProfileService $profileService,
        Registry $registry,
        CustomerRepositoryInterface $customerRepository,
        SubEventService $subEventService,
        CheckoutSession $checkoutSession
    ) {
        parent::__construct($profileService, $registry, $customerRepository, $subEventService);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        try {
            $cart = $this->checkoutSession->getQuote();
            if (empty($cart) || $cart->getCustomerIsGuest() || ! $cart->getCustomerId()) {
                return $this;
            }

            /** @var Product $product */
            $product = $observer->getEvent()->getData('product');
            if (empty($product) || ! $product->getId()) {
                return $this;
            }

            /** @var Item $item */
            $item = $cart->getItemByProduct($product);
            if (empty($item) || ! $item->getId()) {
                return $this;
            }

            $profile = $this->profileService
                ->getProfile(
                    (int) $cart->getStoreId(),
                    (string) $cart->getCustomerEmail(),
                    (int) $cart->getCustomerId()
                );
            if ($profile) {
                $this->subEventService->registerProductCartedEvent($cart, $item, $profile, $this->profileService);
            }
        } catch (Throwable $e) {
            $this->profileService->logError(__METHOD__, $e);
        }

        return $this;
    }
}
