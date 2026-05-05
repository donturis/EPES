<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Model;

use Hyva\BaseLayoutReset\Model\Layout\MutateXml;
use Hyva\Theme\Service\HyvaThemes;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem\Directory\WriteFactory as DirectoryWriteFactory;
use Magento\Framework\Filesystem\Driver\File as Filesystem;
use Magento\Framework\Filesystem\DriverPool as FilesystemDriverPool;
use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;
use Magento\Framework\Module\Dir as ModuleDir;

// phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged

class MigrateThemeToGeneratedBaseLayout
{
    /**
     * @var HyvaThemeResetInfo
     */
    private $hyvaThemeResetInfo;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var FileReadFactory
     */
    private $fileReadFactory;

    /**
     * @var MutateXml
     */
    private $mutateXml;

    /**
     * @var DirectoryWriteFactory
     */
    private $directoryWriteFactory;

    /**
     * @var ModuleDir
     */
    private $moduleDir;

    /**
     * @var HyvaThemes
     */
    private $hyvaThemes;

    /**
     * @var string[]
     */
    private $performedActions = [];

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        HyvaThemeResetInfo $hyvaThemeResetInfo,
        ResourceConnection $resourceConnection,
        FileReadFactory $fileReadFactory,
        DirectoryWriteFactory $directoryWriteFactory,
        ModuleDir $moduleDir,
        MutateXml $mutateXml,
        HyvaThemes $hyvaThemes,
        ?Filesystem $filesystem = null,
    ) {
        $this->hyvaThemeResetInfo = $hyvaThemeResetInfo;
        $this->resourceConnection = $resourceConnection;
        $this->fileReadFactory = $fileReadFactory;
        $this->directoryWriteFactory = $directoryWriteFactory;
        $this->moduleDir = $moduleDir;
        $this->mutateXml = $mutateXml;
        $this->hyvaThemes = $hyvaThemes;
        $this->filesystem = $filesystem ?? ObjectManager::getInstance()->get(Filesystem::class);
    }

    public function process(string $code): void
    {
        $themeInfo = $this->hyvaThemeResetInfo->getHyvaThemesInfo($code);
        if ($themeInfo['parent-in-db']) {
            $this->removeParentInThemeTable($code);
        }
        if ($themeInfo['parent-in-xml']) {
            $this->removeParentInThemeXml($themeInfo['path']);
        }
        if (!$themeInfo['root-template']) {
            $this->createRootTemplate($themeInfo['path']);
        }
        if (!$themeInfo['includes-default-hyva-layout']) {
            $this->includeDefaultHyvaLayoutHandle($themeInfo['path']);
        }
        if (! in_array($code, $this->hyvaThemes->getHyvaBaseThemes(), true)) {
            $this->addHyvaBaseThemeDiConfig($code);
        }
    }

    private function removeParentInThemeTable(string $code): void
    {
        $db = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('theme');
        $where = sprintf('code = %s', $db->quote($code));
        $db->update($table, ['parent_id' => null], $where);

        $this->performedActions[] = sprintf('Run SQL: UPDATE theme.parent_id = NULL WHERE code = "%s"', $code);
    }

    private function removeParentInThemeXml(string $path): void
    {
        $themeXmlFile = $path . '/theme.xml';
        $xml = $this->loadThemeXml($themeXmlFile);
        $this->mutateXml->removeXpath($xml, '/theme/parent');
        $xml->saveXML($themeXmlFile);

        $this->performedActions[] = sprintf('Removed <parent> node from %s', $themeXmlFile);
    }

    private function loadThemeXml(string $themeXmlFile): \SimpleXMLElement
    {
        if (!file_exists($themeXmlFile)) {
            throw new \RuntimeException(sprintf('No theme.xml file found in "%s".', $this->filesystem->getParentDirectory($themeXmlFile)));
        }
        return $this->loadXml($themeXmlFile, 'theme.xml');
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

    private function createRootTemplate(string $themePath): void
    {
        if (file_exists($themePath . '/Magento_Theme/templates/root.phtml')) {
            return;
        }

        $sourceDirPath = $this->moduleDir->getDir('Hyva_BaseLayoutReset') . '/Resources';
        $sourceDir = $this->directoryWriteFactory->create($sourceDirPath);
        $destinationDir = $this->directoryWriteFactory->create($themePath . '/Magento_Theme/templates');
        $sourceDir->copyFile('root-template.phtml', 'root.phtml', $destinationDir);

        $this->performedActions[] = sprintf('Created %s/Magento_Theme/templates/root.phtml', $themePath);
    }

    private function includeDefaultHyvaLayoutHandle(string $themePath): void
    {
        $targetLayoutFile = $themePath . '/Magento_Theme/layout/default.xml';

        if (!file_exists($targetLayoutFile)) {
            file_put_contents($targetLayoutFile, <<<EOXML
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
</page>
EOXML
            );
        }

        $xml = $this->loadXml($targetLayoutFile, 'Layout XML');
        if ($xml->xpath('/page/update[@handle="default_hyva"]')) {
            return;
        }

        $this->mutateXml->addChild($xml, '/page', '    <update handle="default_hyva"/>');
        $xml->saveXML($targetLayoutFile);

        $this->performedActions[] = sprintf('Added <update handle="default_hyva"/> to %s', $targetLayoutFile);
    }

    private function addHyvaBaseThemeDiConfig(string $code): void
    {
        $globalDiXmlFilePath = BP . '/app/etc/di.xml';
        $hyvaBaseThemesXpath = '/config/type[@name="Hyva\Theme\Service\HyvaThemes"]/arguments/argument[@name="hyvaBaseThemes"]';

        $xml = $this->loadXml($globalDiXmlFilePath, 'di.xml');

        foreach ($xml->xpath($hyvaBaseThemesXpath . '/item') as $baseTheme) {
            if ((string) $baseTheme === $code) {
                return;
            }
        }

        if (!($xml->xpath('/config/type[@name="Hyva\Theme\Service\HyvaThemes"]'))) {
            $this->mutateXml->addChild(
                $xml,
                '/config',
                '<type name="Hyva\Theme\Service\HyvaThemes"></type>'
            );
        }
        if (!($xml->xpath('/config/type[@name="Hyva\Theme\Service\HyvaThemes"]/arguments'))) {
            $this->mutateXml->addChild(
                $xml,
                '/config/type[@name="Hyva\Theme\Service\HyvaThemes"]',
                '<arguments></arguments>'
            );
        }
        if (!($xml->xpath($hyvaBaseThemesXpath))) {
            $this->mutateXml->addChild(
                $xml,
                '/config/type[@name="Hyva\Theme\Service\HyvaThemes"]/arguments',
                '<argument name="hyvaBaseThemes" xsi:type="array"/>'
            );
        }
        $this->mutateXml->addChild(
            $xml,
            $hyvaBaseThemesXpath,
            sprintf('<item name="%1$s" xsi:type="string">%1$s</item>', $code)
        );

        $xml->saveXML($globalDiXmlFilePath);

        $this->performedActions[] = sprintf('Added %s as Hyva\Theme\Service\HyvaThemes $hyvaBaseThemes constructor argument in app/etc/di.xml', $code);
    }

    public function getPerformedActions(): array
    {
        $actions = $this->performedActions;
        $this->performedActions = [];
        return $actions;
    }
}
