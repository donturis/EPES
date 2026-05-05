<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Model\Layout;

use Magento\Framework\View\File;

/**
 * The File::isBase() method does not return the protected isBase property value. Instead, it checks if the theme property is set.
 * We need to pass the isBase property value to the constructor, since it does not match the isBase() method value.
 * It's possible to access the protected properties because the class extends \Magento\Framework\View\File.
 */
class LayoutResetFileWrapper extends File
{
    public function __construct(string $filename, File $originalFile)
    {
        parent::__construct($filename, $originalFile->module, $originalFile->theme, $originalFile->isBase);
    }
}
