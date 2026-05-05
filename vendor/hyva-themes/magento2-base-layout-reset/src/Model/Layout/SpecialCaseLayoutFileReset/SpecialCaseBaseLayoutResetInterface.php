<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Model\Layout\SpecialCaseLayoutFileReset;

interface SpecialCaseBaseLayoutResetInterface
{
    public function matches(string $module, string $filename): bool;

    public function resetLayout(\SimpleXMLElement $layoutXml): void;
}
