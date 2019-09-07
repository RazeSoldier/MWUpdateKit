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

namespace RazeSoldier\MWUpKit\MediaWiki\Preparer;

use RazeSoldier\MWUpKit\MediaWiki\{
    ExtensionInstance,
    ExtensionList
};
use Symfony\Component\Process\{
    Process,
    PhpExecutableFinder
};

/**
 * This preparer uses cloning feature of Git to prepare for the extension
 * @package RazeSoldier\MWUpKit\MediaWiki\Preparer
 */
class ExtensionGitPreparer extends ExtensionPreparerBase
{
    public function prepare() : PrepareResult
    {
        $result = new PrepareResult;
        $extList = new ExtensionList;
        $this->preCheck($result, $extList);
        if ($extList === []) {
            return $result;
        }
        $this->prepareDir();

        foreach ($extList as $instance) {
            $this->output->writeln("> Cloning {$instance->getName()} from Github");
            if ($instance->getType() === ExtensionInstance::TYPE_EXTENSION) {
                $pathPrefix = "{$this->dst}/extensions";
                $res = $this->doGitClone($instance->getName(), $this->targetVersion->toBranch(), $pathPrefix, 'extensions');
            } else {
                $pathPrefix = "{$this->dst}/skins";
                $res = $this->doGitClone($instance->getName(), $this->targetVersion->toBranch(), $pathPrefix, 'skins');
            }
            if ($res->getExitCode() !== 0) {
                $this->output->writeln("<error>{$res->getErrorOutput()}</error>");
                $result->addFailItem("{$instance->getTypeText()}-{$instance->getName()}");
                continue;
            }

            try {
                $this->installDepend("$pathPrefix/{$instance->getName()}");
            } catch (\RuntimeException $e) {
                $this->output->writeln("<error>{$e->getMessage()}</error>");
                $result->addFailItem("{$instance->getTypeText()}-{$instance->getName()}");
                continue;
            }
            $result->addOkItem("{$instance->getTypeText()}-{$instance->getName()}");
        }
        return $result;
    }

    private function doGitClone(string $repoName, string $branch, string $cwd, string $type) : Process
    {
        $process = new Process(['git', 'clone', "https://github.com/wikimedia/mediawiki-$type-$repoName.git",
            '--branch', $branch, $repoName], $cwd, null, null, null);
        $process->run();
        return $process;
    }

    /**
     * Install dependence for the extension via Composer
     * @param string $path Path to the extension
     * @throws \RuntimeException
     */
    private function installDepend(string $path)
    {
        // If composer.json doesn't exist or the composer.json doesn't contain "require" key,
        // exit this method directly.
        if (!is_readable("$path/composer.json")) {
            return;
        }
        $json = file_get_contents("$path/composer.json");
        $json = json_decode($json, true);
        if (!isset($json['require'])) {
            return;
        }

        // Check if Composer binary exists in the $PATH and use it if it exists,
        // or use the build-in binary if it doesn't exists.
        if ((new Process('composer'))->run() === 0) {
            $process = new Process(['composer', 'install', '--no-dev'], $path, null, null, null);
        } else {
            $phpPath = (new PhpExecutableFinder)->find();
            $composerPath = ROOT_PATH . '/vendor/bin/composer.phar';
            $process = new Process([$phpPath, $composerPath, 'install', '--no-dev'], $path, null, null, null);
        }
        if ($process->run() !== 0) {
            throw new \RuntimeException("Exception: {$process->getErrorOutput()}");
        }
    }
}
