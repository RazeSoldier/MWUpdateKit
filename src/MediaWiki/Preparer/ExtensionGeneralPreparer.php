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
    Services,
    StatusValue
};

/**
 * This preparer downloads the tarball directly from the Internet to prepare for the extension
 * @package RazeSoldier\MWUpKit\MediaWiki\Preparer
 */
class ExtensionGeneralPreparer extends ExtensionPreparerBase
{
    public function prepare() : StatusValue
    {
        $status = new StatusValue;
        $extList = new ExtensionList;
        $this->preCheck($status, $extList);
        if ($extList === []) {
            return $status;
        }
        $this->prepareDir();

        foreach ($extList as $instance) {
            $this->output->writeln("> Downloading {$instance->getName()} from extdist.wmflabs.org");
            $link = $this->getDownloadLink($instance->getName(), $instance->getType(), $this->targetVersion->toBranch());
            $dst = sys_get_temp_dir() . '/' . uniqid('MediaWikiUp', true) . '.tar.gz';
            try {
                $this->downloadTarball($link, $dst);
            } catch (\RuntimeException $e) {
                $this->output->writeln("<error>Exception: {$e->getMessage()}</error>");
                continue;
            }
            $typeText = $instance->getTypeText();
            $this->extractTarball($dst, "{$this->dst}/$typeText");
            $status->addSuccess("$typeText-{$instance->getName()}");
            unlink($dst);
        }

        return $status;
    }

    /**
     * Get the download link from the API of mediawiki.org
     * @param string $extName
     * @param int $type
     * @param string $branchName
     * @return string
     */
    private function getDownloadLink(string $extName, int $type, string $branchName) : string
    {
        $client = Services::getInstance()->getHttpClient();
        $url = 'https://www.mediawiki.org/w/api.php?action=query&list=extdistbranches&format=json&formatversion=2&';
        $url .= $type === ExtensionInstance::TYPE_EXTENSION ? "edbexts=$extName" : "edbskins=$extName";
        $json = $client->GET($url)->getBody();
        $json = json_decode($json, true);
        $typeText = ExtensionInstance::TYPE_TEXT[$type];
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
