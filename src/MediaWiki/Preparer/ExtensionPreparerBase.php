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
    ExtensionList,
    MediaWikiInstance,
    MWVersion
};
use RazeSoldier\MWUpKit\StatusValue;
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
            mkdir("{$this->dst}/extensions", 0644);
            if (file_exists("{$this->dst}/skins")) {
                throw new \Exception("{$this->dst}/skins already exist");
            }
            mkdir("{$this->dst}/skins", 0644);
        } else {
            mkdir($this->dst, 0644, true);
            mkdir("{$this->dst}/extensions", 0644);
            mkdir("{$this->dst}/skins", 0644);
        }
    }

    protected function preCheck(StatusValue $status, ExtensionList &$list)
    {
        foreach ($this->getExtList() as $ext) {
            if ($ext->isHostWMF()) {
                $list[] = $ext;
            } else {
                $text = $ext->getType() === ExtensionInstance::TYPE_EXTENSION ? 'extension' : 'skin';
                $this->output->writeln("<comment>'{$ext->getName()}' $text not hosting WMF-gerrit, ignore</comment>");
                $status->addFail($ext->getName());
            }
        }
        if ($list === []) {
            $status->warning('Nothing needs to be prepared');
        }
    }

    /**
     * @return ExtensionList
     */
    protected function getExtList() : ExtensionList
    {
        return new ExtensionList($this->mwInstance->getExtensionList()->getList() + $this->mwInstance->getSkinList()->getList());
    }
}
