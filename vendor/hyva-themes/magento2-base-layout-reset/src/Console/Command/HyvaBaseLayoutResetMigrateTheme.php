<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\BaseLayoutReset\Console\Command;

use Hyva\BaseLayoutReset\Model\MigrateThemeToGeneratedBaseLayout;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HyvaBaseLayoutResetMigrateTheme extends Command
{
    /**
     * @var MigrateThemeToGeneratedBaseLayout
     */
    private $migrateThemeToGeneratedBaseLayout;

    public function __construct(
        MigrateThemeToGeneratedBaseLayout $migrateThemeToGeneratedBaseLayout
    ) {
        parent::__construct();
        $this->migrateThemeToGeneratedBaseLayout = $migrateThemeToGeneratedBaseLayout;
    }

    protected function configure()
    {
        $this->setName('hyva:base-layout-resets:migrate');
        $this->setDescription('Migrate a Hyvä theme from the reset-theme to using the generated base-layout');
        $this->addArgument('theme', InputArgument::REQUIRED, 'Theme code to be migrated');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('theme');
        $this->migrateThemeToGeneratedBaseLayout->process($code);

        $performedActions = $this->migrateThemeToGeneratedBaseLayout->getPerformedActions();
        if ($performedActions) {
            $output->writeln("<info>Performed actions:</info>");
            foreach ($performedActions as $action) {
                $output->writeln(" - " . $action);
            }
        } else {
            $output->writeln("<info>$code</info> is already using the Generated Base Layout - nothing needs to be done.");
        }

        return Command::SUCCESS;
    }
}
