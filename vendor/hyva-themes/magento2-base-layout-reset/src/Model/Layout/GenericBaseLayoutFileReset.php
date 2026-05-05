<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Model\Layout;

class GenericBaseLayoutFileReset
{
    /**
     * @var MutateXml
     */
    private $mutateXml;

    public function __construct(MutateXml $mutateXml)
    {
        $this->mutateXml = $mutateXml;
    }

    public function resetLayout(\SimpleXMLElement $layoutXml, array $skip = []): void
    {
        foreach ([
                     '//css',
                     '//link',
                     '//script',
                     '//meta',
                     '//block',
                     '//move',
                     '//action',
                     '//remove',
                     '//uiComponent',
                     '//referenceBlock/arguments',
                     '//referenceBlock[@template]',
                     '//referenceBlock[@remove="true"]',
                     '//referenceContainer[@remove="true"]',
                 ] as $xpath) {
            if (!in_array($xpath, $skip, true)) {
                $this->mutateXml->removeXpath($layoutXml, $xpath);
            }
        }
    }
}
