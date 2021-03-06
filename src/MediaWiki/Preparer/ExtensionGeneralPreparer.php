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
    Exception\ProcessExecException,
    MediaWiki\ExtensionInstance,
    MediaWiki\ExtensionList,
    Services
};
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * This preparer downloads the tarball directly from the Internet to prepare for the extension
 * @package RazeSoldier\MWUpKit\MediaWiki\Preparer
 */
class ExtensionGeneralPreparer extends ExtensionPreparerBase
{
    /**
     * @return PrepareResult
     * @throws \RuntimeException Exception thrown if the extension/skin directory already exist
     */
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
            try {
                $link = $this->getDownloadLink($instance, $this->targetVersion->toBranch());
            } catch (\ErrorException $e) {
                // Quick exit because curl extension not loaded
                $this->output->writeln("<error>{$e->getMessage()}</error>");
                exit(1);
            } catch (\RuntimeException $e) {
                $this->output->writeln("<error>{$e->getMessage()}</error>");
                $result->addFailItem("{$instance->getTypeText()}-{$instance->getName()}");
                continue;
            }
            if ($link === null) {
                $this->output->writeln("<error>{$this->targetVersion->toBranch()->getBranchText()} for {$instance->getName()} " .
                    "{$instance->getTypeText()} does not exists</error>");
                $result->addFailItem("{$instance->getTypeText()}-{$instance->getName()}");
                continue;
            }
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
            } catch (ProcessExecException $e) {
                $this->output->writeln("<error>{$e->getMessage()}</error>");
                $result->addFailItem("{$instance->getTypeText()}-{$instance->getName()}");
                continue;
            } catch (RuntimeException $e) {
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
     * @return string|null Returns the download link for the extension, return NULL if without link
     * @throws \RuntimeException Exception thrown if receive non-200 response and retry stills fails
     * @throws \ErrorException Exception thrown if unload curl extension
     */
    private function getDownloadLink(ExtensionInstance $ext, string $branchName)
    {
        $extName = $ext->getName();
        $client = Services::getInstance()->getHttpClient();
        $url = 'https://www.mediawiki.org/w/api.php?action=query&list=extdistbranches&format=json&formatversion=2&';
        $url .= $ext->getType() === ExtensionInstance::TYPE_EXTENSION ? "edbexts=$extName" : "edbskins=$extName";

        $retryTime = -1;
        do {
            ++$retryTime;
            if ($retryTime === 3) {
                throw new \RuntimeException("Failed to GET $url, we already retry 3 times and stills fails");
            }
            $resp = $client->GET($url);
        } while ($resp !== 200);
        $json = json_decode($resp->getBody(), true);
        $typeText = $ext->getTypeTextWithS();
        if (isset($json['query']['extdistbranches'][$typeText][$extName][$branchName])) {
            return $json['query']['extdistbranches'][$typeText][$extName][$branchName];
        } else {
            return null;
        }
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
