<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\LumaCheckout\Setup\Patch\Data;

use Magento\Framework\App\Cache\Type\Config as ConfigCacheType;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoresConfig;

class AddCustomerAjaxLoginPathFallback implements DataPatchInterface
{
    /**
     * @var StoresConfig
     */
    private $storesConfig;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ThemeProviderInterface
     */
    private $themeProvider;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var array[]
     */
    private $updatedScopes = [];

    public function __construct(
        StoresConfig $storesConfig,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection,
        ThemeProviderInterface $themeProvider,
        CacheInterface $cache
    ) {
        $this->storesConfig = $storesConfig;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->themeProvider = $themeProvider;
        $this->cache = $cache;
    }

    public function apply()
    {
        $fallbackThemeByStore = $this->storesConfig->getStoresConfigByPath('hyva_theme_fallback/general/theme_full_path');
        $isConfigUpdated = false;

        foreach ($this->storeManager->getStores() as $store) {
            if (!($fallbackThemeFullPath = $fallbackThemeByStore[$store->getId()])) {
                continue;
            }

            $theme = $this->themeProvider->getThemeByFullPath($fallbackThemeFullPath);
            if ($this->isLumaTheme($theme)) {
                $isConfigUpdated = $this->injectAjaxLoginRouteIfMissing($store) || $isConfigUpdated;
            }
        }

        if ($isConfigUpdated) {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, [ConfigCacheType::CACHE_TAG]);
        }
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }

    private function isLumaTheme(?ThemeInterface $theme): bool
    {
        if (!$theme) {
            return true;
        }
        if (strpos($theme->getCode(), 'Hyva/') !== false) {
            return false;
        }
        return $this->isLumaTheme($theme->getParentTheme());
    }

    private function injectAjaxLoginRouteIfMissing(StoreInterface $store): bool
    {
        $configScopeFallback = [
            ['scope' => 'stores', 'scope_id' => $store->getId()],
            ['scope' => 'websites', 'scope_id' => $store->getWebsiteId()],
            ['scope' => 'default', 'scope_id' => 0],
        ];
        $path = 'hyva_theme_fallback/general/list_part_of_url';

        $isUpdated = false;

        foreach ($configScopeFallback as $tuple) {
            if ($value = $this->getSettingForScope($tuple['scope'], (int) $tuple['scope_id'], $path)) {

                $fallbackPaths = json_decode($value, true);
                if ($this->containsCheckoutPath($fallbackPaths) && !$this->containsCustomerAjaxLogin($fallbackPaths)) {
                    $this->updateSettingForScope(json_encode($this->injectAjaxLoginpath($fallbackPaths)), $tuple['scope'], (int) $tuple['scope_id'], $path);
                    $isUpdated = true;
                }

            }
        }

        return $isUpdated;
    }

    private function getSettingForScope(string $scope, int $scopeId, string $path): ?string
    {
        $db = $this->resourceConnection->getConnection();
        $select = $db->select()
                     ->from($this->resourceConnection->getTableName('core_config_data'), 'value')
                     ->where('scope=:scope AND scope_id=:scope_id AND path=:path');
        return $db->fetchOne($select, ['scope' => $scope, 'scope_id' => $scopeId, 'path' => $path]) ?: null;
    }

    private function updateSettingForScope(string $value, string $scope, int $scopeId, string $path): void
    {
        $scopeIsUpdatedKey = $scope .'_' . $scopeId . '_' . $path;
        if (isset($this->updatedScopes[$scopeIsUpdatedKey])) {
            return;
        }

        $db = $this->resourceConnection->getConnection();
        $where = sprintf("scope=%s AND scope_id=%d AND path=%s", $db->quote($scope), $scopeId, $db->quote($path));
        $db->update($this->resourceConnection->getTableName('core_config_data'), ['value' => $value], $where);

        $this->updatedScopes[$scopeIsUpdatedKey] = true;
    }

    private function containsCheckoutPath(array $fallbackPaths): bool
    {
        foreach ($fallbackPaths as $row) {
            $path = $row['path'];
            if (preg_match('#checkout/#', $path) && !preg_match('#/cart/#', $path)) {
                return true;
            }
        }
        return false;
    }

    private function containsCustomerAjaxLogin(array $fallbackPaths): bool
    {
        foreach ($fallbackPaths as $row) {
            $path = $row['path'];
            if ($path === 'customer/ajax/login' || $path === '/customer/ajax/login') {
                return true;
            }
        }
        return false;
    }

    private function injectAjaxLoginpath(array $fallbackPaths): array
    {
        do {
            // See adminhtml template Magento_Config::system/config/form/field/array.phtml (line 111)
            $key = '_' . ((int) floor(microtime(true) * 1000)) . '_123';
        } while (isset($fallbackPaths[$key]));
        $fallbackPaths[$key] = ['path' => 'customer/ajax/login'];
        return $fallbackPaths;
    }
}
