<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\LumaCheckout\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoresConfig;

class MigrateConfigSettings implements DataPatchInterface
{

    /**
     * @var StoresConfig
     */
    private $storesConfig;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(StoresConfig $storesConfig, ResourceConnection $resourceConnection)
    {
        $this->storesConfig       = $storesConfig;
        $this->resourceConnection = $resourceConnection;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $db = $this->resourceConnection->getConnection();
        $db->beginTransaction();

        $pathMigrationMap = [
            'hyva_luma_checkout/general/enable'          => 'hyva_theme_fallback/general/enable',
            'hyva_luma_checkout/general/theme_full_path' => 'hyva_theme_fallback/general/theme_full_path',
        ];

        foreach ($pathMigrationMap as $from => $to) {
            $this->migrateSetting($from, $to);
        }

        $db->commit();
    }

    private function migrateSetting(string $from, string $to): void
    {
        $db = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('core_config_data');
        $db->update($table, ['path' => $to], sprintf('path=%s', $db->quote($from)));
    }
}
