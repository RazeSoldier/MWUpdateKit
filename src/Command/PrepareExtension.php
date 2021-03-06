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

use RazeSoldier\MWUpKit\MediaWiki\{MediaWikiInstance,
    MWVersion,
    Preparer\ExtensionPreparerFactory,
    Preparer\PrepareResult};
use RazeSoldier\MWUpKit\Exception\FileAccessException;
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \RuntimeException Exception thrown if the extension/skin directory already exist
     * @throws \UnexpectedValueException Exception thrown if the given version is invalid
     * @throws FileAccessException
     */
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
        $result = ExtensionPreparerFactory::make($isGit, $mwInstance, new MWVersion($input->getArgument('version')),
            $input->getArgument('dstPath'), $output)->prepare();

        if ($result->isAllFail()) {
            $output->writeln('<comment>Unprepared extension:</comment>');
            $this->outputFailMessage($output, $result);
            $output->writeln("<comment>Nothing needs to be prepared</comment>");
            return 0;
        }

        $okItems = $result->getOkItem();

        if ($okItems !== []) {
            $output->writeln('<info>Successfully prepared extension:</info>');
            foreach ($okItems as $item) {
                $output->writeln("<info>$item</info>");
            }
        }
        if ($result->hasFail()) {
            $output->writeln('<comment>Unprepared extension:</comment>');
            $this->outputFailMessage($output, $result);
        }
        return 0;
    }

    private function outputFailMessage(OutputInterface $output, PrepareResult $result)
    {
        $failItems = $result->getFailItem();
        foreach ($failItems as $failItem) {
            $msg = $failItem['name'];
            if ($failItem['reason'] !== null) {
                $msg .= " - {$failItem['reason']}";
            }
            $output->writeln("<comment>$msg</comment>");
        }
    }
}
