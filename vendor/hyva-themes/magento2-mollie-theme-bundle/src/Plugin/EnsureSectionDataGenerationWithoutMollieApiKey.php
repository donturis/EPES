<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\MollieThemeBundle\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Mollie\Payment\Model\MollieConfigProvider;

class EnsureSectionDataGenerationWithoutMollieApiKey
{
    /**
     * @var ScopeConfigInterface
     */
    private $systemConfig;

    public function __construct(ScopeConfigInterface $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    public function aroundGetConfig(MollieConfigProvider $subject, callable $proceed): array
    {
        return $this->systemConfig->getValue('payment/mollie_general/enabled', ScopeInterface::SCOPE_STORES)
            ? $proceed()
            : [];
    }
}
