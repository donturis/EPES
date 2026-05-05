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

class SwaggerIndexBaseLayoutReset implements SpecialCaseBaseLayoutResetInterface
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
        return $filename === 'swagger_index_index.xml';
    }

    public function resetLayout(\SimpleXMLElement $layoutXml): void
    {
        $skip = ['//css', '//link', '//script', '//block', '//referenceBlock/arguments', '//referenceContainer[@remove="true"]'];
        $this->genericBaseLayoutFileReset->resetLayout($layoutXml, $skip);

        // Keep swagger-ui assets
        foreach (['//css', '//link', '//script'] as $xpath) {
            $this->mutateXml->removeXpath($layoutXml, $xpath, function (\DOMElement $dom): bool {
                return strpos($dom->getAttribute('src'), 'Magento_Swagger::swagger-ui/') !== 0;
            });
        }

        // Keep swaggerUiContent block
        $this->mutateXml->removeXpath($layoutXml, '//block', function (\DOMElement $dom): bool {
            return $dom->getAttribute('name') !== 'swaggerUiContent';
        });

        // Keep swaggerUiContent block arguments
        $this->mutateXml->removeXpath($layoutXml, '//referenceBlock/arguments', function (\DOMElement $dom): bool {
            return $dom->parentNode->getAttribute('name') !== 'swaggerUiContent';
        });

        // Keep removal of page.wrapper container
        $this->mutateXml->removeXpath($layoutXml, '//referenceContainer[@remove="true"]', function (\DOMElement $dom): bool {
            return $dom->getAttribute('name') !== 'page.wrapper';
        });
    }
}
