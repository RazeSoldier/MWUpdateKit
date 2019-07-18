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

use RazeSoldier\MWUpKit\Services;

/**
 * Modeling for MediaWiki
 * @package RazeSoldier\MWUpKit\MediaWiki
 */
class ExtensionInstance
{
    const TYPE_EXTENSION = 5;
    const TYPE_SKIN = 6;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $type;

    private function __construct(string $path, string $name, int $type)
    {
        $this->basePath = $path;
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Instantiate extension type based on file path
     * @param string $path
     * @return ExtensionInstance
     */
    public static function newExtensionByPath(string $path) : self
    {
        return new self($path, basename($path), self::TYPE_EXTENSION);
    }

    /**
     * Instantiate skin type based on file path
     * @param string $path
     * @return ExtensionInstance
     */
    public static function newSkinByPath(string $path) : self
    {
        return new self($path, basename($path), self::TYPE_SKIN);
    }

    /**
     * Whether hosting in WMF-gerrit?
     * @return bool
     */
    public function isHostWMF() : bool
    {
        $client = Services::getInstance()->getHttpClient();
        $url = 'https://www.mediawiki.org/w/api.php?action=query&list=extdistbranches&format=json&formatversion=2&';
        $url .= $this->type === self::TYPE_EXTENSION ? "edbexts={$this->name}" : "edbskins={$this->name}";
        $json = $client->GET($url)->getBody();
        $json = json_decode($json, true);
        return $json['query']['extdistbranches'] !== [];
    }

    public function getType() : int
    {
        return $this->type;
    }

    public function getName() : string
    {
        return $this->name;
    }
}
