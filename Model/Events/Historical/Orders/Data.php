<?php

namespace Apsis\One\Model\Events\Historical\Orders;

use Apsis\One\Model\Events\Historical\EventData;
use Apsis\One\Model\Events\Historical\EventDataInterface;
use Apsis\One\Model\Service\Core as ApsisCoreHelper;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Exception;

class Data extends EventData implements EventDataInterface
{
    /**
     * @var int
     */
    private $subscriberId;

    /**
     * @param Order $order
     * @param ApsisCoreHelper $apsisCoreHelper
     * @param int $subscriberId
     *
     * @return array
     */
    public function getDataArr(Order $order, ApsisCoreHelper $apsisCoreHelper, int $subscriberId = 0)
    {
        $this->subscriberId = $subscriberId;
        return $this->getProcessedDataArr($order, $apsisCoreHelper);
    }

    /**
     * @param AbstractModel $order
     * @param ApsisCoreHelper $apsisCoreHelper
     *
     * @return array
     */
    public function getProcessedDataArr(AbstractModel $order, ApsisCoreHelper $apsisCoreHelper)
    {
        try {
            $items = [];
            /** @var Item $item */
            foreach ($order->getAllVisibleItems() as $item) {
                try {
                    $product = $item->getProduct();
                    $items [] = [
                        'orderId' => (int) $order->getEntityId(),
                        'productId' => (int) $item->getProductId(),
                        'sku' => (string) $item->getSku(),
                        'name' => (string) $item->getName(),
                        'productUrl' => (string) $product->getProductUrl(),
                        'productImageUrl' => (string) $this->productServiceProvider->getProductImageUrl($product),
                        'qtyOrdered' => $apsisCoreHelper->round($item->getQtyOrdered()),
                        'priceAmount' => $apsisCoreHelper->round($item->getPrice()),
                        'rowTotalAmount' => $apsisCoreHelper->round($item->getRowTotal()),
                    ];
                } catch (Exception $e) {
                    $apsisCoreHelper->logError(__METHOD__, $e->getMessage(), $e->getTraceAsString());
                    continue;
                }
            }

            return [
                'orderId' => (int) $order->getEntityId(),
                'incrementId' => (string) $order->getIncrementId(),
                'customerId' => (int) $order->getCustomerId(),
                'subscriberId' => (int) $this->subscriberId,
                'isGuest' => (boolean) $order->getCustomerIsGuest(),
                'websiteName' => (string) $order->getStore()->getWebsite()->getName(),
                'storeName' => (string) $order->getStore()->getName(),
                'grandTotalAmount' => $apsisCoreHelper->round($order->getGrandTotal()),
                'shippingAmount' => $apsisCoreHelper->round($order->getShippingAmount()),
                'discountAmount' => $apsisCoreHelper->round($order->getDiscountAmount()),
                'shippingMethodName' => (string) $order->getShippingDescription(),
                'paymentMethodName' => (string) $order->getPayment()->getMethod(),
                'itemsCount' => (int) $order->getTotalItemCount(),
                'currencyCode' => (string) $order->getOrderCurrencyCode(),
                'items' => $items
            ];
        } catch (Exception $e) {
            $apsisCoreHelper->logError(__METHOD__, $e->getMessage(), $e->getTraceAsString());
            return [];
        }
    }
}
