<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Console\Command;

use Hyva\BaseLayoutReset\Model\HyvaThemeResetInfo;
use Hyva\Theme\Service\HyvaThemes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table as CliTable;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HyvaBaseLayoutResetInfo extends Command
{
    /**
     * @var HyvaThemeResetInfo
     */
    private $hyvaThemeResetInfo;

    /**
     * @var HyvaThemes
     */
    private $hyvaThemes;

    public function __construct(
        HyvaThemeResetInfo $hyvaThemeResetInfo,
        HyvaThemes $hyvaThemes
    ) {
        parent::__construct();
        $this->hyvaThemeResetInfo = $hyvaThemeResetInfo;
        $this->hyvaThemes = $hyvaThemes;
    }

    protected function configure()
    {
        $this->setName('hyva:base-layout-resets:info');
        $this->setDescription('List Hyvä Themes and the layout reset mechanism they use');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $info = $this->hyvaThemeResetInfo->getHyvaThemesInfo();
        $table = new CliTable($output);
        $table->setHeaders([
            'Theme',
            'Code',
            'Actions to migrate to Generated Base Layout',
        ]);

        foreach ($info as $i => $theme) {
            if ($i > 0) {
                $table->addRow(new TableSeparator());
            }

            if ($this->isBaseHyvaTheme($theme)) {
                $steps = $this->getRequiredMigrationSteps($theme);
                if (!$steps) {
                    $steps = 'Already using the generated base layout';
                }
            } else {
                $steps = 'Not a Hyvä base theme';
            }

            $table->addRow([
                'title' => $theme['title'],
                'code'  => $theme['code'],
                'steps' => $steps,
            ]);
        }
        $table->render();

        return Command::SUCCESS;
    }

    private function isBaseHyvaTheme($theme): bool
    {
        return $theme['parent-in-db'] === 'Hyva/reset' ||
            $theme['parent-in-xml'] === 'Hyva/reset' ||
            (null === $theme['parent-in-db'] && null === $theme['parent-in-xml']);
    }

    private function getRequiredMigrationSteps(array $theme): string
    {
        $steps = [];

        if ($theme['parent-in-db'] === 'Hyva/reset') {
            $steps[] = '- Unset <info>parent_id</info> in themes DB table';
        }
        if ($theme['parent-in-xml'] === 'Hyva/reset') {
            $steps[] = '- Remove <info><parent></info> node from themes.xml';
        }

        if (! in_array($theme['code'], $this->hyvaThemes->getHyvaBaseThemes(), true)) {
            $steps[] = "- Add <info>{$theme['code']}</info> as Hyva\Theme\Service\HyvaThemes constructor arg in di.xml";
        }

        if (! $theme['root-template']) {
            $steps[] = '- Add <info>Magento_Theme::root.phtml</info> file';
        }

        if (! $theme['includes-default-hyva-layout']) {
            $steps[] = '- Add <info><update handle="default_hyva"/></info> to Magento_Theme/layout/default.xml';
        }

        if ($steps && in_array($theme['code'], ['Hyva/default', 'Hyva/default-csp'], true)) {
            array_unshift($steps, "You can patch generated layout support with these actions:\n");
            array_unshift($steps, "Upgrade <info>{$theme['code']}</info> to version 1.4.0 or newer.");

            $steps[] = "\nRun the following command to apply the above changes:";
            $steps[] = "\n<info>bin/magento hyva:base-layout-resets:migrate {$theme['code']}</info>";
        }

        return implode("\n", $steps);
    }
}
