<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\AdminhtmlSalesOrderCreditmemoRegisterBefore;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Mollie\Payment\Service\Mollie\Order\ParseAdditionalData;

class DoNotRefundGiftcard implements ObserverInterface
{
    /**
     * @var ParseAdditionalData
     */
    private $parseAdditionalData;

    public function __construct(
        ParseAdditionalData $parseAdditionalData
    ) {
        $this->parseAdditionalData = $parseAdditionalData;
    }

    /**
     * When a giftcard is used with a remainderamount paid by a different payment method (eg ideal, creditcard),
     * we can only refund the part where the different payment method is used.
     */
    public function execute(Observer $observer): void
    {
        /** @var CreditmemoInterface $creditmemo */
        $creditmemo = $observer->getEvent()->getData('creditmemo');
        $order = $creditmemo->getOrder();

        $additionalData = $this->parseAdditionalData->fromPayment($order->getPayment());
        if ($additionalData->getDetails() === null) {
            return;
        }

        $remainderAmount = $additionalData->getDetails()->getRemainderAmount();
        if ($remainderAmount === null) {
            return;
        }

        $creditmemo->setSubtotal(min($remainderAmount->getValue(), $creditmemo->getSubtotal()));
        $creditmemo->setBaseSubtotal(min($remainderAmount->getValue(), $creditmemo->getBaseSubtotal()));
        $creditmemo->setGrandTotal(min($remainderAmount->getValue(), $creditmemo->getGrandTotal()));
        $creditmemo->setBaseGrandTotal(min($remainderAmount->getValue(), $creditmemo->getBaseGrandTotal()));
    }
}
