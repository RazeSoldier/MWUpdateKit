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
    ExtensionList,
    MediaWikiInstance,
    MWVersion
};
use RazeSoldier\MWUpKit\Exception\ProcessExecException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\{
    PhpExecutableFinder,
    Process
};

/**
 * This class contains the common code for the two implementations.
 * @package RazeSoldier\MWUpKit\MediaWiki\Preparer
 */
abstract class ExtensionPreparerBase implements IExtensionPreparer
{
    /**
     * @var MediaWikiInstance
     */
    protected $mwInstance;

    /**
     * @var MWVersion
     */
    protected $targetVersion;

    /**
     * @var string
     */
    protected $dst;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(MediaWikiInstance $mwInstance, MWVersion $version, string $dst, OutputInterface $output)
    {
        $this->mwInstance = $mwInstance;
        $this->targetVersion = $version;
        $this->dst = $dst;
        $this->output = $output;
    }

    /**
     * Prepare directories for storing extensions
     * @throws \RuntimeException Exception thrown if the extension/skin directory already exist
     */
    protected function prepareDir()
    {
        if (file_exists($this->dst)) {
            if (file_exists("{$this->dst}/extensions")) {
                throw new \RuntimeException("{$this->dst}/extensions already exist");
            }
            mkdir("{$this->dst}/extensions", 0775);
            if (file_exists("{$this->dst}/skins")) {
                throw new \RuntimeException("{$this->dst}/skins already exist");
            }
            mkdir("{$this->dst}/skins", 0775);
        } else {
            mkdir($this->dst, 0775, true);
            mkdir("{$this->dst}/extensions", 0775);
            mkdir("{$this->dst}/skins", 0775);
        }
    }

    /**
     * Check if the extension code is hosted on WMF-gerrit and filter
     * @param PrepareResult $result
     * @param ExtensionList $list
     */
    protected function preCheck(PrepareResult $result, ExtensionList $list)
    {
        foreach ($this->getExtList() as $ext) {
            if ($ext->isHostWMF()) {
                $list[] = $ext;
            } else {
                $text = $ext->getTypeText();
                $this->output->writeln("<comment>'{$ext->getName()}' $text not hosting WMF-gerrit, ignore</comment>");
                $result->addFailItem("{$ext->getTypeText()}-{$ext->getName()}",
                    "'{$ext->getName()}' $text not hosting WMF-gerrit, ignore");
            }
        }
    }

    /**
     * @return ExtensionList
     */
    private function getExtList() : ExtensionList
    {
        return new ExtensionList($this->mwInstance->getExtensionList()->getList() + $this->mwInstance->getSkinList()->getList());
    }

    /**
     * Install dependence for the extension via Composer
     * @param string $path Path to the extension
     * @throws ProcessExecException Exception thrown if the process exit code is not 0
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    protected function installDepend(string $path)
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
            throw new ProcessExecException($process);
        }
    }
}
