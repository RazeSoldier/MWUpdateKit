<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace RazeSoldier\MWUpKit\Command;

use RazeSoldier\MWUpKit\MediaWiki\{
    MediaWikiInstance,
    MWVersion,
    Preparer\ExtensionPreparerFactory
};
use RazeSoldier\MWUpKit\Services;
use Symfony\Component\Console\{
    Command\Command,
    Output\OutputInterface
};
use Symfony\Component\Console\Input\{
    InputArgument,
    InputInterface,
    InputOption
};
use Symfony\Component\Process\Process;

/**
 * Implement 'prepare:ext' command
 * @package RazeSoldier\MWUpKit\Command
 */
class PrepareExtension extends Command
{
    const DESC = 'Prepare new extensions by exist extensions';

    protected static $defaultName = 'prepare:ext';

    protected function configure()
    {
        $this->setDescription(self::DESC)
            ->addArgument('basePath', InputArgument::REQUIRED, 'The MediaWiki installation directory')
            ->addArgument('dstPath', InputArgument::REQUIRED, 'New extension storage location directory (Will create if not exist)')
            ->addArgument('version', InputArgument::REQUIRED, 'Which version of the extension to prepare?')
            ->addOption('git', null, null, 'Prepare via Git')
            ->addOption('proxy', null, InputOption::VALUE_REQUIRED, 'Set HTTP proxy');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('proxy') !== null) {
            Services::getInstance()->getHttpClient()->setProxy($input->getOption('proxy'));
        }
        $isGit = $input->getOption('git');
        if ($isGit) {
            // Check "git bin
            $process = new Process('git');
            if ($process->run() !== 1) {
                $output->writeln('<error>Can\'t find `git` executable</error>');
                return 1;
            }
        }

        $mwInstance = MediaWikiInstance::newByPath($input->getArgument('basePath'));
        $status = ExtensionPreparerFactory::make($isGit, $mwInstance, new MWVersion($input->getArgument('version')),
            $input->getArgument('dstPath'), $output)->prepare();

        if ($status->getSuccessCount() === 0) {
            foreach ($status->getErrorsByType('warning') as $value) {
                $output->writeln("<comment>{$value['message']}</comment>");
            }
            return 1;
        }
        $ops = $status->getSuccess();
        $okOps = [];
        $failOps = [];
        foreach ($ops as $name => $ok) {
            if ($ok) {
                $okOps[] = $name;
            } else {
                $failOps[] = $name;
            }
        }

        if ($okOps !== []) {
            $output->writeln('<info>Successfully prepared extension:</info>');
            foreach ($okOps as $op) {
                $output->writeln("<info>$op</info>");
            }
        }
        if ($failOps !== []) {
            $output->writeln('<comment>Unprepared extension:</comment>');
            foreach ($failOps as $op) {
                $output->writeln("<comment>$op</comment>");
            }
        }
        return 0;
    }
}
