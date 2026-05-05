<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Model\Layout\SpecialCaseLayoutFileReset;

use Hyva\BaseLayoutReset\Model\Layout\GenericBaseLayoutFileReset;
use Hyva\BaseLayoutReset\Model\Layout\MutateXml;

class NegotiableQuotePrintBaseLayoutReset implements SpecialCaseBaseLayoutResetInterface
{
    /**
     * @var GenericBaseLayoutFileReset
     */
    private $genericBaseLayoutFileReset;

    /**
     * @var MutateXml
     */
    private $mutateXml;

    public function __construct(
        GenericBaseLayoutFileReset $genericBaseLayoutFileReset,
        MutateXml $mutateXml
    ) {
        $this->genericBaseLayoutFileReset = $genericBaseLayoutFileReset;
        $this->mutateXml = $mutateXml;
    }

    public function matches(string $module, string $filename): bool
    {
        return $module === 'Magento_NegotiableQuote' && $filename === 'negotiable_quote_quote_print.xml';
    }

    public function resetLayout(\SimpleXMLElement $layoutXml): void
    {
        $this->genericBaseLayoutFileReset->resetLayout($layoutXml, /* Skip xpaths */ ['//referenceContainer[@remove="true"]']);

        // Keep page.messages container removal
        $this->mutateXml->removeXpath($layoutXml, '//referenceContainer[@remove="true"]', function (\DOMElement $dom): bool {
            return $dom->getAttribute('name') !== 'page.messages';
        });
    }
}
