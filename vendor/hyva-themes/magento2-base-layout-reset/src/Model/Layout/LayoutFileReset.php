<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Model\Layout;

use Hyva\BaseLayoutReset\Model\Layout\SpecialCaseLayoutFileReset\SpecialCaseBaseLayoutResetInterface;
use Hyva\BaseLayoutReset\Model\Layout\SpecialCaseLayoutFileReset\SpecialCaseLayoutResetPool;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Config\Dom\ValidationException;
use Magento\Framework\Filesystem\Directory\Write as DirectoryWrite;
use Magento\Framework\Filesystem\Directory\WriteFactory as DirectoryWriteFactory;
use Magento\Framework\Filesystem\DriverPool as FilesystemDriverPool;
use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;
use Magento\Framework\Phrase;
use Magento\Framework\View\File;
use Magento\Framework\View\Layout\Element as LayoutElement;
use Psr\Log\LoggerInterface;

// phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
// phpcs:disable Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative

/**
 * Generate a version of the given layout file without block declarations in the same way the Hyvä reset theme used to be.
 *
 * This class has to be usable without a DB so it can run during the Adobe Commerce Cloud deployment build step,
 * so it must not use classes like App\State.
 */
class LayoutFileReset
{
    /**
     * Specify a directory path using this key at the top level of app/etc/env.php, for the layout resets to be generated in that directory.
     * If not configured, the layout resets are generated at var/hyva-layout-resets/.
     */
    public const CONFIG_PATH_HYVA_RESETS_DIR = 'hyva_layout_resets_generation_directory';

    /**
     * @var LayoutResetFileWrapperFactory
     */
    private $layoutResetFileWrapperFactory;

    /**
     * @var AppDirectoryList
     */
    private $appDirectoryList;

    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * @var FileReadFactory
     */
    private $fileReadFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DirectoryWriteFactory
     */
    private $directoryWriteFactory;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var GenericBaseLayoutFileReset
     */
    private $genericBaseLayoutFileReset;

    /**
     * @var SpecialCaseLayoutResetPool
     */
    private $specialCaseLayoutResetPool;

    /**
     * @var array[]
     */
    private array $excludeFromResets;

    /**
     * @var bool[]
     */
    private $modulesToReset;

    /**
     * @var bool
     */
    private $isDeveloperMode = false;

    /**
     * @var bool
     */
    private $isRegenerateMode = false;

    public function __construct(
        LayoutResetFileWrapperFactory $layoutResetFileWrapperFactory,
        AppDirectoryList $appDirectoryList,
        ComponentRegistrar $componentRegistrar,
        FileReadFactory $fileReadFactory,
        DirectoryWriteFactory $directoryWriteFactory,
        LoggerInterface $logger,
        DeploymentConfig $deploymentConfig,
        GenericBaseLayoutFileReset $genericBaseLayoutFileReset,
        SpecialCaseLayoutResetPool $specialCaseLayoutResetPool,
        array $excludeFromResets,
        array $modulesToReset
    ) {
        $this->layoutResetFileWrapperFactory = $layoutResetFileWrapperFactory;
        $this->appDirectoryList = $appDirectoryList;
        $this->componentRegistrar = $componentRegistrar;
        $this->fileReadFactory = $fileReadFactory;
        $this->directoryWriteFactory = $directoryWriteFactory;
        $this->logger = $logger;
        $this->deploymentConfig = $deploymentConfig;
        $this->genericBaseLayoutFileReset = $genericBaseLayoutFileReset;
        $this->excludeFromResets = $excludeFromResets;
        $this->modulesToReset = $modulesToReset;
        $this->specialCaseLayoutResetPool = $specialCaseLayoutResetPool;
    }

    public function setIsDeveloperMode(bool $isDeveloperMode): void
    {
        $this->isDeveloperMode = $isDeveloperMode;
    }

    public function setRegenerateMode(bool $isRegenerateMode): void
    {
        $this->isRegenerateMode = $isRegenerateMode;
    }

    public function getLayoutResetFile(File $inputLayoutFile): File
    {
        if (!$this->shouldBeReset($inputLayoutFile)) {
            return $inputLayoutFile;
        }

        $resetFileName = $this->getLayoutResetFileName($inputLayoutFile);

        if ($this->isRegenerateMode || !file_exists($resetFileName)) {
            $this->createLayoutResetFile($inputLayoutFile->getFilename(), $resetFileName, $inputLayoutFile->getModule());

            if (!file_exists($resetFileName)) {
                // Something went wrong generating the reset layout. Check the logs or enable developer mode for details.
                return $inputLayoutFile;
            }
        }

        return $this->layoutResetFileWrapperFactory->create(['filename' => $resetFileName, 'originalFile' => $inputLayoutFile]);
    }

    private function getLayoutResetFileName(File $inputLayoutFile): string
    {
        $moduleDir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $inputLayoutFile->getModule());

        $layoutFilePath = dirname(substr($inputLayoutFile->getFilename(), strlen($moduleDir . '/view') + 1));
        $outputDir = sprintf('%s/%s/%s', $this->getLayoutResetDirPath(), $inputLayoutFile->getModule(), $layoutFilePath);

        return $outputDir . '/' . $inputLayoutFile->getName();
    }

    /**
     * Default to a path in var/ that is writable even on Adobe Commerce Cloud.
     */
    public function getLayoutResetDirPath(): string
    {
        return $this->deploymentConfig->getConfigData(self::CONFIG_PATH_HYVA_RESETS_DIR)
            ?: sprintf('%s/hyva-layout-resets', $this->appDirectoryList->getPath(AppDirectoryList::VAR_DIR));
    }

    private function createLayoutResetFile(string $inputFile, string $outputFile, ?string $module): void
    {
        $layoutXml = $this->loadLayoutXml($inputFile);
        if (!$layoutXml) {
            return;
        }

        $isSpecialCase = false;
        /** @var $specialCaseLayoutReset SpecialCaseBaseLayoutResetInterface */
        foreach ($this->specialCaseLayoutResetPool as $specialCaseLayoutReset) {
            if ($specialCaseLayoutReset->matches($module, basename($inputFile))) {
                $specialCaseLayoutReset->resetLayout($layoutXml);
                $isSpecialCase = true;
                break;
            }
        }
        if (!$isSpecialCase) {
            $this->genericBaseLayoutFileReset->resetLayout($layoutXml);
        }

        $this->createLayoutResetDirWriter()->create(dirname($outputFile));
        $layoutXml->saveXML($outputFile);
    }

    /**
     * @param string $filename
     * @return false|LayoutElement|\SimpleXMLElement
     */
    private function loadLayoutXml(string $filename)
    {
        $xmlString = $this->fileReadFactory->create($filename, FilesystemDriverPool::FILE)->readAll();
        $fileXml = simplexml_load_string($xmlString, LayoutElement::class);

        if (!$fileXml instanceof LayoutElement) {
            $xmlErrors = $this->getXmlErrors(libxml_get_errors());
            libxml_clear_errors();
            $this->logger->info(sprintf("Theme layout update file '%s' is not valid.\n%s", $filename, implode("\n", $xmlErrors)));
            if ($this->isDeveloperMode) {
                $this->throwLayoutFileInvalidException($filename, $xmlErrors);
            }
        }

        return $fileXml;
    }

    /**
     * @param \LibXMLError[] $libXmlErrors
     * @return string[]
     */
    private function getXmlErrors(array $libXmlErrors): array
    {
        $errors = [];
        if (count($libXmlErrors)) {
            foreach ($libXmlErrors as $error) {
                $errors[] = "{$error->message} Line: {$error->line}";
            }
        }
        return $errors;
    }

    /**
     * @param string $filename
     * @param string[] $xmlErrors
     * @return void
     */
    private function throwLayoutFileInvalidException(string $filename, array $xmlErrors): void
    {
        throw new ValidationException(
            (string) new Phrase("Theme layout update file '%1' is not valid.\n%2", [$filename, implode("\n", $xmlErrors)])
        );
    }

    private function createLayoutResetDirWriter(): DirectoryWrite
    {
        return $this->directoryWriteFactory->create($this->getLayoutResetDirPath());
    }

    private function shouldBeReset(File $layoutFile): bool
    {
        if (!$layoutFile->isBase()) {
            return false;
        }
        $module = $layoutFile->getModule();
        if ($this->excludeFromResets[$module][$layoutFile->getName()]
            ?? $this->excludeFromResets['*'][$layoutFile->getName()]
            ?? false) {
            return false;
        }

        if (isset($this->modulesToReset[$module])) {
            return (bool) $this->modulesToReset[$module];
        }

        [$vendor] = explode('_', $module);
        return $this->modulesToReset[$vendor] ?? false;
    }
}
