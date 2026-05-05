<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Adminhtml\Sales\Creditmemo;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Mollie\Payment\Service\Mollie\Order\ParseAdditionalData;

class RemainderAmountWarning extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;
    /**
     * @var ParseAdditionalData
     */
    private $parseAdditionalData;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        PriceCurrencyInterface $priceCurrency,
        ParseAdditionalData $parseAdditionalData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->priceCurrency = $priceCurrency;
        $this->parseAdditionalData = $parseAdditionalData;
    }

    public function toHtml()
    {
        /** @var CreditmemoInterface $creditmemo */
        $creditmemo = $this->registry->registry('current_creditmemo');
        if (!$creditmemo->getOrder() || !$creditmemo->getOrder()->getPayment()) {
            return '';
        }

        $additionalData = $this->parseAdditionalData->fromPayment($creditmemo->getOrder()->getPayment());
        if ($additionalData->getDetails() === null) {
            return '';
        }

        $remainderAmount = $additionalData->getDetails()->getRemainderAmount();
        if ($remainderAmount === null) {
            return '';
        }

        $message = __(
            'Warning: This order is (partially) paid using a voucher or giftcard. You can refund a maximum of %1.',
            $this->priceCurrency->format(
                $remainderAmount->getValue(),
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $creditmemo->getStoreId()
            )
        );

        return '<br><div class="message message-warning warning">' . $message . '</div>';
    }
}
