<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Model\Layout;

use function array_keys as keys;
use function array_map as map;

class MutateXml
{
    public function removeXpath(\SimpleXMLElement $xmlElement, string $xpath, ?callable $predicate = null): void
    {
        $directives = $xmlElement->xpath($xpath);
        foreach ($directives as $directive) {
            $dom = dom_import_simplexml($directive);
            if (!$predicate || $predicate($dom)) {
                $dom->parentNode->removeChild($dom);
            }
        }
    }

    public function addChild(\SimpleXMLElement $rootElement, string $parentXpath, string $xmlToAdd): void
    {
        $directives = $rootElement->xpath($parentXpath);
        foreach ($directives as $directive) {
            $dom = dom_import_simplexml($directive);
            $rootTag = $rootElement->xpath('/*')[0]->getName();
            $namespaces = implode(' ', map(function (string $key, string $value) {
                return sprintf('xmlns:%s="%s"', $key, $value);
            }, keys($rootElement->getNamespaces()), $rootElement->getNamespaces()));
            $xmlToImport = sprintf('<%1$s %2$s>%3$s</%1$s>', $rootTag, $namespaces, $xmlToAdd);
            $xml = simplexml_load_string($xmlToImport);
            $instanceToImport = $xml->xpath("/$rootTag/*");
            $externalChild = dom_import_simplexml($instanceToImport[0]);
            $dom->append($dom->ownerDocument->importNode($externalChild, true));
            return;
        }
    }
}
