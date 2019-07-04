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

use RazeSoldier\MWUpKit\MediaWiki\EnvChecker;
use RazeSoldier\MWUpKit\MediaWiki\MWVersion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{
    InputArgument,
    InputInterface
};
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Implement 'prepare:envCheck' command
 * @package RazeSoldier\MWUpKit\Command
 */
class PrepareEnvCheck extends Command
{
    const DESC = 'Check the environment required by the specified version';

    protected static $defaultName = 'prepare:envCheck';

    protected function configure()
    {
        $this->setDescription(self::DESC)
            ->addArgument('version', InputArgument::REQUIRED, 'Check if the system can run the specified version of MW');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = new MWVersion($input->getArgument('version'));
        $checker = new EnvChecker($version);
        $report = $checker->phpCheck();
        if ($report->isGood()) {
            $output->writeln("<info>Your system can run MediaWiki {$input->getArgument('version')}</info>");
            return 0;
        }
        foreach ($report->getFailCheck() as $value) {
            $output->writeln("<comment>{$value['message']}</comment>");
        }
        return 0;
    }
}
