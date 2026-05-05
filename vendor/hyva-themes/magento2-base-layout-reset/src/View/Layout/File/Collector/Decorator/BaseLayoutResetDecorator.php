<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\View\Layout\File\Collector\Decorator;

use Hyva\BaseLayoutReset\Model\Layout\LayoutFileReset;
use Hyva\Theme\Service\HyvaThemes;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\AreaList as AppAreaList;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\File;
use Magento\Framework\View\File\CollectorInterface;

/**
 * Generate the base layout XML without any block directives.
 * This used to be done by the Hyvä reset theme.
 */
class BaseLayoutResetDecorator implements CollectorInterface
{
    /**
     * @var CollectorInterface
     */
    private $subject;

    /**
     * @var LayoutFileReset
     */
    private $layoutFileReset;

    /**
     * @var AppAreaList
     */
    private $appAreaList;

    /**
     * @var HttpRequest
     */
    private $httpRequest;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var HyvaThemes
     */
    private $hyvaThemes;

    public function __construct(
        CollectorInterface $subject,
        LayoutFileReset $layoutFileReset,
        AppAreaList $appAreaList,
        AppState $appState,
        HttpRequest $httpRequest,
        HyvaThemes $hyvaThemes
    ) {
        $this->subject = $subject;
        $this->layoutFileReset = $layoutFileReset;
        $this->appAreaList = $appAreaList;
        $this->appState = $appState;
        $this->httpRequest = $httpRequest;
        $this->hyvaThemes = $hyvaThemes;
    }

    /**
     * @param ThemeInterface $theme
     * @param string $filePath
     * @return File[]
     */
    public function getFiles(ThemeInterface $theme, $filePath)
    {
        if (!$this->isFrontendHyvaTheme($theme)) {
            return $this->subject->getFiles($theme, $filePath);
        }

        $this->layoutFileReset->setIsDeveloperMode($this->appState->getMode() === AppState::MODE_DEVELOPER);

        $result = [];
        $files = $this->subject->getFiles($theme, $filePath);
        foreach ($files as $layoutFile) {
            $result[] = $this->layoutFileReset->getLayoutResetFile($layoutFile);
        }

        return $result;
    }

    private function isFrontendHyvaTheme(ThemeInterface $theme): bool
    {
        $area = $this->appAreaList->getCodeByFrontName($this->httpRequest->getFrontName());
        return $area === AppArea::AREA_FRONTEND && $this->hyvaThemes->isHyvaTheme($theme);
    }
}
