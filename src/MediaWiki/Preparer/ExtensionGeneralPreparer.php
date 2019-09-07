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

use RazeSoldier\MWUpKit\{
    Exception\HttpTimeoutException,
    MediaWiki\ExtensionInstance,
    MediaWiki\ExtensionList,
    Services
};

/**
 * This preparer downloads the tarball directly from the Internet to prepare for the extension
 * @package RazeSoldier\MWUpKit\MediaWiki\Preparer
 */
class ExtensionGeneralPreparer extends ExtensionPreparerBase
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
            $this->output->writeln("> Downloading {$instance->getName()} from extdist.wmflabs.org");
            $link = $this->getDownloadLink($instance, $this->targetVersion->toBranch());
            $tmpFilePath = sys_get_temp_dir() . '/' . uniqid('MediaWikiUp', true) . '.tar.gz';
            try {
                $this->downloadTarball($link, $tmpFilePath);
            } catch (\RuntimeException $e) {
                $this->output->writeln("<error>Exception: {$e->getMessage()}</error>");
                continue;
            }
            $this->extractTarball($tmpFilePath, "{$this->dst}/{$instance->getTypeTextWithS()}");
            try {
                $this->installDepend("{$this->dst}/{$instance->getTypeTextWithS()}/{$instance->getName()}");
            } catch (\RuntimeException $e) {
                $this->output->writeln("<error>{$e->getMessage()}</error>");
                $result->addFailItem("{$instance->getTypeText()}-{$instance->getName()}");
                continue;
            }
            $result->addOkItem("{$instance->getTypeText()}-{$instance->getName()}");
            unlink($tmpFilePath);
        }

        return $result;
    }

    /**
     * Get the download link from the API of mediawiki.org
     * @param ExtensionInstance $ext
     * @param string $branchName
     * @return string
     */
    private function getDownloadLink(ExtensionInstance $ext, string $branchName) : string
    {
        $extName = $ext->getName();
        $client = Services::getInstance()->getHttpClient();
        $url = 'https://www.mediawiki.org/w/api.php?action=query&list=extdistbranches&format=json&formatversion=2&';
        $url .= $ext->getType() === ExtensionInstance::TYPE_EXTENSION ? "edbexts=$extName" : "edbskins=$extName";
        $json = $client->GET($url)->getBody();
        $json = json_decode($json, true);
        $typeText = $ext->getTypeTextWithS();
        return $json['query']['extdistbranches'][$typeText][$extName][$branchName];
    }

    /**
     * Download the tarball from extdist.wmflabs.org
     * @param string $url
     * @param string $dst
     * @throws HttpTimeoutException
     * @throws \RuntimeException
     */
    private function downloadTarball(string $url, string $dst)
    {
        $client = Services::getInstance()->getHttpClient();
        $client->download($url, $dst);
    }

    private function extractTarball(string $src, string $dst)
    {
        $pharData = new \PharData($src);
        $pharData->extractTo($dst);
    }
}
