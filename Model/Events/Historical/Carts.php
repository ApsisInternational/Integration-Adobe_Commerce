<?php

namespace Apsis\One\Model\Events\Historical;

use Apsis\One\Model\Events\Historical\Event as HistoricalEvent;
use Apsis\One\Model\ResourceModel\Event as EventResource;
use Apsis\One\Model\Service\Config as ApsisConfigHelper;
use Apsis\One\Model\Service\Core as ApsisCoreHelper;
use Exception;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Apsis\One\Model\Events\Historical\Carts\Data as CartData;
use Apsis\One\Model\Event;

class Carts extends HistoricalEvent implements EventHistoryInterface
{
    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var EventResource
     */
    private $eventResource;

    /**
     * @var CartData
     */
    private $cartData;

    /**
     * Carts constructor.
     *
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param EventResource $eventResource
     * @param CartData $cartData
     */
    public function __construct(
        QuoteCollectionFactory $quoteCollectionFactory,
        EventResource $eventResource,
        CartData $cartData
    ) {
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->eventResource = $eventResource;
        $this->cartData = $cartData;
    }

    /**
     * @param StoreInterface $store
     * @param ApsisCoreHelper $apsisCoreHelper
     * @param array $profileCollectionArray
     * @param array $duration
     */
    public function fetchForStore(
        StoreInterface $store,
        ApsisCoreHelper $apsisCoreHelper,
        array $profileCollectionArray,
        array $duration
    ) {
        if ((boolean) $apsisCoreHelper->getStoreConfig(
            $store,
            ApsisConfigHelper::CONFIG_APSIS_ONE_EVENTS_PRODUCT_CARTED
        )) {
            try {
                if (! empty($quoteCollection = $this->getCartCollection(
                    $apsisCoreHelper,
                    $store,
                    array_keys($profileCollectionArray),
                    $duration
                ))) {
                    $eventsToRegister = $this->getEventsToRegister(
                        $apsisCoreHelper,
                        $quoteCollection,
                        $profileCollectionArray
                    );
                    if (! empty($eventsToRegister)) {
                        $this->eventResource->insertEvents($eventsToRegister, $apsisCoreHelper);
                    }
                }
            } catch (Exception $e) {
                $apsisCoreHelper->logMessage(__METHOD__, $e->getMessage(), $e->getTraceAsString());
            }
        }
    }

    /**
     * @param ApsisCoreHelper $apsisCoreHelper
     * @param QuoteCollection $quoteCollection
     * @param array $profileCollectionArray
     *
     * @return array
     */
    private function getEventsToRegister(
        ApsisCoreHelper $apsisCoreHelper,
        QuoteCollection $quoteCollection,
        array $profileCollectionArray
    ) {
        $eventsToRegister = [];
        /** @var Quote $quote */
        foreach ($quoteCollection as $quote) {
            try {
                $items = $quote->getAllVisibleItems();
                /** @var Item $item */
                foreach ($items as $item) {
                    try {
                        if (isset($profileCollectionArray[$quote->getCustomerId()])) {
                            $eventsToRegister[] = $this->getEventData(
                                $profileCollectionArray[$quote->getCustomerId()],
                                Event::EVENT_TYPE_CUSTOMER_ADDED_PRODUCT_TO_CART,
                                $item->getCreatedAt(),
                                $apsisCoreHelper->serialize(
                                    $this->cartData->getDataArr(
                                        $quote,
                                        $item,
                                        $apsisCoreHelper
                                    )
                                )
                            );
                        }
                    } catch (Exception $e) {
                        $apsisCoreHelper->logMessage(__METHOD__, $e->getMessage(), $e->getTraceAsString());
                        continue;
                    }
                }
            } catch (Exception $e) {
                $apsisCoreHelper->logMessage(__METHOD__, $e->getMessage(), $e->getTraceAsString());
                continue;
            }
        }
        return $eventsToRegister;
    }

    /**
     * @param ApsisCoreHelper $apsisCoreHelper
     * @param StoreInterface $store
     * @param array $customerIds
     * @param array $duration
     *
     * @return array|QuoteCollection
     */
    private function getCartCollection(
        ApsisCoreHelper $apsisCoreHelper,
        StoreInterface $store,
        array $customerIds,
        array $duration
    ) {
        try {
            return $this->quoteCollectionFactory->create()
                ->addFieldToFilter('main_table.store_id', $store->getId())
                ->addFieldToFilter('main_table.customer_id', ['in' => $customerIds])
                ->addFieldToFilter('main_table.created_at', $duration);
        } catch (Exception $e) {
            $apsisCoreHelper->logMessage(__METHOD__, $e->getMessage(), $e->getTraceAsString());
            return [];
        }
    }
}
