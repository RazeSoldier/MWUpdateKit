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

namespace RazeSoldier\MWUpKit\MediaWiki;

use RazeSoldier\MWUpKit\Exception\HttpException;
use RazeSoldier\MWUpKit\Services;

/**
 * Used to check environment to meet the requirements
 * @package RazeSoldier\MWUpKit\MediaWiki
 */
class EnvChecker
{
    /**
     * @var MWVersion
     */
    private $version;

    public function __construct(MWVersion $version)
    {
        $this->version = $version;
    }

    /**
     * @return EnvCheckReport
     * @throws HttpException
     */
    public function phpCheck() : EnvCheckReport
    {
        $rawVersion = $this->version->getFormatVersion();
        if ($rawVersion['small'] === null) {
            $rawVersion['small'] = '0';
        }
        $version = "${rawVersion['major']}.${rawVersion['minor']}.${rawVersion['small']}";
        $url = "https://raw.githubusercontent.com/wikimedia/mediawiki/$version/composer.json";
        $resp = Services::getInstance()->getHttpClient()->GET($url);
        if (!$resp->isOk()) {
            throw new HttpException($resp, "Failed to GET $url");
        }

        $json = json_decode($resp->getBody() ,true);
        foreach ($json['require'] as $key => $item) {
            if ($key === 'php') {
                $phpRequire = $item;
                continue;
            }
            if (strpos($key, 'ext-') === 0) {
                $extRequire[] = substr($key, 4);
                continue;
            }
        }

        $report = new EnvCheckReport;

        // Check PHP version require @{
        if (!isset($phpRequire)) {
            throw new \LogicException("Failed to catch PHP require value");
        }
        if (!preg_match('/[0-9]/', $phpRequire, $matches, PREG_OFFSET_CAPTURE)) {
            throw new \InvalidArgumentException("Invalid PHP require: $phpRequire");
        }
        $operator = substr($phpRequire, 0, $matches[0][1]);
        if (version_compare(PHP_VERSION, $phpRequire, $operator)) {
            $report->addOK('PHP Version Check');
        } else {
            $report->addBad('PHP Version Check', 'Your PHP version '. PHP_VERSION . " lower than $phpRequire");
        }
        // @}
        // Check PHP Extension @{
        if (!isset($extRequire)) {
            return $report;
        }
        foreach ($extRequire as $ext) {
            if (extension_loaded($ext)) {
                $report->addOK("PHP Extension Check: $ext");
            } else {
                $report->addBad("PHP Extension Check: $ext", "PHP Extension \"$ext\" unavailable");
            }
        }
        // @}
        return $report;
    }
}
