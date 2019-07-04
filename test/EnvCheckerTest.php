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

use RazeSoldier\MWUpKit\MediaWiki\{
    MWVersion,
    EnvChecker
};
use PHPUnit\Framework\TestCase;

class EnvCheckerTest extends TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Failed to GET https://raw.githubusercontent.com/wikimedia/mediawiki/1.33.99/composer.json
     */
    public function testPhpCheckFail()
    {
        $version = new MWVersion('1.33.99');
        $checker = new EnvChecker($version);
        $checker->phpCheck();
    }

    public function testPhpCheck()
    {
        $version = new MWVersion('1.33.0');
        $checker = new EnvChecker($version);
        $checker->phpCheck();
    }
}
