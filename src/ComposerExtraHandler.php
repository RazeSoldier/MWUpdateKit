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

namespace RazeSoldier\MWUpKit;

use Symfony\Component\Process\{
    PhpExecutableFinder,
    Process
};

/**
 * Used to store code of composer's script
 * @package RazeSoldier\MWUpKit
 */
class ComposerExtraHandler
{
    /**
     * This method run after executed `composer install/update`
     * @throws \RuntimeException
     */
    public static function afterDumpAutoload()
    {
        $composerPath = __DIR__ . '/../vendor/bin/composer.phar';
        if (!is_dir(__DIR__ . '/../vendor/bin/')) {
            mkdir(__DIR__ . '/../vendor/bin/');
        }

        if (is_readable($composerPath)) {
            $path = (new PhpExecutableFinder)->find();
            if ($path === false) {
                throw new \RuntimeException('Can\'t find PHP executable');
            }
            $process = new Process("\"$path\" $composerPath --version");
            $process->run();
            if (!preg_match('/[0-9]*?\.[0-9]*?\.[0-9]*/', $process->getOutput(), $matches)) {
                throw new \RuntimeException("Failed to catch Composer version");
            }
            $currentVersion = $matches[0];
            $client = Services::getInstance()->getHttpClient();
            $json = $client->GET('https://getcomposer.org/versions')->getBody();
            $stableInfo = json_decode($json, true)['stable'][0];
            if (version_compare($stableInfo['version'], $currentVersion, '=')) {
                return;
            }
            echo ">> Downloading Composer executable\n";
            $client->download("https://getcomposer.org{$stableInfo['path']}", $composerPath);
        } else {
            $client = Services::getInstance()->getHttpClient();
            $json = $client->GET('https://getcomposer.org/versions')->getBody();
            $stableInfo = json_decode($json, true)['stable'][0];
            echo ">> Downloading Composer executable\n";
            $client->download("https://getcomposer.org{$stableInfo['path']}", $composerPath);
        }
    }
}
