<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2022-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Model\Layout\SpecialCaseLayoutFileReset;

use Hyva\BaseLayoutReset\Model\Layout\GenericBaseLayoutFileReset;
use Hyva\BaseLayoutReset\Model\Layout\MutateXml;

class LiveSearchProductListingBaseLayoutReset implements SpecialCaseBaseLayoutResetInterface
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
        return $module === 'Magento_LiveSearchProductListing' && in_array($filename, ['catalog_category_view.xml', 'catalogsearch_result_inded.xml'], true);
    }

    public function resetLayout(\SimpleXMLElement $layoutXml): void
    {
        $this->genericBaseLayoutFileReset->resetLayout($layoutXml);
        $this->mutateXml->removeXpath($layoutXml, '//body/attribute');
        $this->mutateXml->removeXpath($layoutXml, '//body/referenceContainer', function (\DOMElement $dom): bool {
            return $dom->getAttribute('name') !== 'columns';
        });
        $this->mutateXml->removeXpath($layoutXml, '//body/referenceBlock');
    }
}
