<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Model;

use Hyva\Theme\Service\HyvaThemes;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File as Filesystem;
use Magento\Framework\Filesystem\DriverPool as FilesystemDriverPool;
use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;
use Magento\Framework\Filesystem\Glob;
use Magento\Framework\View\Design\Theme\ThemeList;
use Magento\Framework\View\Design\ThemeInterface;
use function array_filter as filter;
use function array_map as map;
use function array_values as values;

// phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged

class HyvaThemeResetInfo
{
    /**
     * @var ThemeList
     */
    private $themeList;

    /**
     * @var HyvaThemes
     */
    private $hyvaThemes;

    /**
     * @var ComponentRegistrarInterface
     */
    private $componentRegistrar;

    /**
     * @var FileReadFactory
     */
    private $fileReadFactory;

    /**
     * @var Filesystem|null
     */
    private $filesystem;

    public function __construct(
        FileReadFactory $fileReadFactory,
        ThemeList $themeList,
        HyvaThemes $hyvaThemes,
        ComponentRegistrarInterface $componentRegistrar,
        ?Filesystem $filesystem = null,
    ) {
        $this->fileReadFactory = $fileReadFactory;
        $this->themeList = $themeList;
        $this->hyvaThemes = $hyvaThemes;
        $this->componentRegistrar = $componentRegistrar;
        $this->filesystem = $filesystem ?? ObjectManager::getInstance()->get(Filesystem::class);
    }

    public function getHyvaThemesInfo(?string $themeCode = null): array
    {
        $hyvaThemes = filter(values($this->themeList->getItems()), [$this, 'isHyvaTheme']);

        $info = values(map([$this, 'buildInfo'], $hyvaThemes));
        if ($themeCode === null) {
            return $info;
        }

        foreach ($info as $theme) {
            if ($theme['code'] === $themeCode) {
                return $theme;
            }
        }

        throw new \OutOfBoundsException(sprintf('Hyvä theme "%s" not found.', $themeCode));
    }

    private function isHyvaTheme(ThemeInterface $theme): bool
    {
        return $theme->getCode() !== 'Hyva/reset' && $this->hyvaThemes->isHyvaTheme($theme);
    }

    private function buildInfo(ThemeInterface $theme): array
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::THEME, $theme->getArea() . '/' . $theme->getCode());

        $rootTemplateFile = $path . '/Magento_Theme/templates/root.phtml';
        $themeXmlFile = $path . '/' . 'theme.xml';

        $info = [
            'title'                        => $theme->getThemeTitle(),
            'code'                         => $theme->getCode(),
            'path'                         => $path,
            'parent-in-db'                 => $theme->getParentThemePath(),
            'parent-in-xml'                => $this->getParentFromXml($this->loadThemeXml($themeXmlFile)),
            'root-template'                => file_exists($rootTemplateFile) ? $rootTemplateFile : null,
            'includes-default-hyva-layout' => $this->hasDefaultHyvaLayoutUpdateDirective($path),
        ];

        return $info;
    }

    private function loadThemeXml(string $themeXmlFile): \SimpleXMLElement
    {
        if (!file_exists($themeXmlFile)) {
            throw new \RuntimeException(sprintf('No theme.xml file found in "%s".', $this->filesystem->getParentDirectory($themeXmlFile)));
        }
        return $this->loadXml($themeXmlFile, 'theme.xml');
    }

    private function getParentFromXml(\SimpleXMLElement $xmlElement): ?string
    {
        $nodes = $xmlElement->xpath('/theme/parent');
        foreach ($nodes as $node) {
            $dom = dom_import_simplexml($node);
            return $dom->textContent;
        }
        return null;
    }

    private function hasDefaultHyvaLayoutUpdateDirective(string $themePath): bool
    {
        $defaultLayoutFiles = Glob::glob($themePath . '/*/layout/default.xml');
        foreach ($defaultLayoutFiles as $defaultLayoutFile) {
            $xml = $this->loadXml($defaultLayoutFile, 'Layout XML');
            $nodes = $xml->xpath('//update');
            foreach ($nodes as $node) {
                if (dom_import_simplexml($node)->getAttribute('handle') === 'default_hyva') {
                    return true;
                }
            }
        }
        return false;
    }

    private function loadXml(string $xmlFilePath, string $fileType): \SimpleXMLElement
    {
        $xmlString = $this->fileReadFactory->create($xmlFilePath, FilesystemDriverPool::FILE)->readAll();
        $xml = simplexml_load_string($xmlString);
        if (!$xml instanceof \SimpleXMLElement) {
            libxml_clear_errors();
            throw new \RuntimeException(sprintf('Invalid %s file "%s"', $fileType, $xmlFilePath));
        }
        return $xml;
    }
}
