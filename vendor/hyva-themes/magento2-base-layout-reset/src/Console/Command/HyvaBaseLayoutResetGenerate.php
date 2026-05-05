<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Console\Command;

use Hyva\BaseLayoutReset\Model\Layout\LayoutFileReset;
use Magento\Framework\App\Area;
use Magento\Framework\View\File\CollectorInterface as ViewFileCollector;
use Magento\Theme\Model\Theme;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to generate all base layout XML resets.
 *
 * This command needs to be functional without a DB, so it can be run during the Adobe Commerce Cloud deployment build step.
 * Do not use classes like:
 *   Magento\Framework\App\State
 *   Magento\Framework\App\ResourceConnection
 *   Magento\Store\Model\StoreManagerInterface
 *   Magento\Framework\App\Config\ScopeConfigInterface
 */
class HyvaBaseLayoutResetGenerate extends Command
{
    /**
     * @var ViewFileCollector
     */
    private $baseLayoutFilesCollector;

    /**
     * @var ViewFileCollector
     */
    private $basePageLayoutFilesCollector;

    /**
     * @var Theme\DataFactory
     */
    private $themeFactory;

    /**
     * @var LayoutFileReset
     */
    private $layoutFileReset;

    public function __construct(
        ViewFileCollector $baseLayoutFilesCollector,
        ViewFileCollector $basePageLayoutFilesCollector,
        Theme\DataFactory $themeFactory,
        LayoutFileReset $layoutFileReset
    ) {
        $this->baseLayoutFilesCollector = $baseLayoutFilesCollector;
        $this->basePageLayoutFilesCollector = $basePageLayoutFilesCollector;
        $this->themeFactory = $themeFactory;
        $this->layoutFileReset = $layoutFileReset;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hyva:base-layout-resets:generate');
        $this->setDescription(sprintf('Generate Base Layout XML Resets in %s/', $this->getLayoutResetDirDisplayPath()));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->layoutFileReset->setRegenerateMode(true);

        foreach ([$this->basePageLayoutFilesCollector, $this->baseLayoutFilesCollector] as $layoutFileCollector) {
            $this->generateLayoutResetsForCollector($layoutFileCollector);
        }
        return Command::SUCCESS;
    }

    private function generateLayoutResetsForCollector(ViewFileCollector $layoutFileCollector): void
    {
        // The theme instance is only needed to supply the app area to the layout file collector.
        $dummyTheme = $this->themeFactory->create(['data' => ['area' => Area::AREA_FRONTEND]]);

        foreach ($layoutFileCollector->getFiles($dummyTheme, '*.xml') as $layoutFile) {
            $this->layoutFileReset->getLayoutResetFile($layoutFile);
        }
    }

    private function getLayoutResetDirDisplayPath(): string
    {
        $path = $this->layoutFileReset->getLayoutResetDirPath();
        return strpos($path, BP) === 0
            ? substr($path, strlen(BP) + 1)
            : $path;
    }
}
