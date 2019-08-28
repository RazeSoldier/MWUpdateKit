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
use Symfony\Component\Console\Output\OutputInterface;

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
     * @throws \Exception
     */
    protected function prepareDir()
    {
        if (file_exists($this->dst)) {
            if (file_exists("{$this->dst}/extensions")) {
                throw new \Exception("{$this->dst}/extensions already exist");
            }
            mkdir("{$this->dst}/extensions", 0775);
            if (file_exists("{$this->dst}/skins")) {
                throw new \Exception("{$this->dst}/skins already exist");
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
}
