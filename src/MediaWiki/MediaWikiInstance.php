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

use RazeSoldier\MWUpKit\Exception\FileAccessException;

/**
 * Modeling for MediaWiki
 * @package RazeSoldier\MWUpKit\MediaWiki
 */
class MediaWikiInstance
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var MWVersion
     */
    private $version;

    /**
     * @var ExtensionList
     */
    private $extList;

    /**
     * @var ExtensionList
     */
    private $skinList;

    /**
     * @param string $path
     * @throws FileAccessException Exception thrown if failed to read the file
     * @throws \UnexpectedValueException Exception thrown if failed to catch the MediaWiki version
     * @throws \UnexpectedValueException Exception thrown if the MediaWiki version is invalid
     * @throws \UnexpectedValueException Exception thrown if failed to open the extension directory
     */
    private function __construct(string $path)
    {
        $this->basePath = $path;
        $this->version = self::catchVersionFromFile("$path/includes/DefaultSettings.php");
        $this->parseExtension();
    }

    /**
     * Instantiate based on file path
     * @param string $path
     * @return MediaWikiInstance
     * @throws FileAccessException Exception thrown if failed to read the file
     * @throws \UnexpectedValueException Exception thrown if failed to catch the MediaWiki version
     * @throws \UnexpectedValueException Exception thrown if the MediaWiki version is invalid
     * @throws \UnexpectedValueException Exception thrown if failed to open the extension directory
     * @throws \UnexpectedValueException Exception thrown if $path does not exist
     * @throws \UnexpectedValueException Exception thrown if $path is not a valid MediaWiki installation directory
     */
    public static function newByPath(string $path) : self
    {
        $realPath = realpath($path);
        if ($realPath === false) {
            throw new \UnexpectedValueException("$path does not exist");
        }
        if (!self::isValid($realPath)) {
            throw new \UnexpectedValueException("$path is not a valid MediaWiki installation directory");
        }
        return new self($realPath);
    }

    /**
     * @throws \UnexpectedValueException Exception thrown if failed to open the extension directory
     */
    private function parseExtension()
    {
        $extDir = new \DirectoryIterator("{$this->basePath}/extensions");
        $extList = new ExtensionList;
        foreach ($extDir as $iterator) {
            if ($iterator->isDot() || $iterator->isFile()) {
                continue;
            }
            $extList[$iterator->getBasename()] = ExtensionInstance::newExtensionByPath($iterator->getRealPath());
        }

        $skinDir = new \DirectoryIterator("{$this->basePath}/skins");
        $skinList = new ExtensionList;
        foreach ($skinDir as $iterator) {
            if ($iterator->isDot() || $iterator->isFile()) {
                continue;
            }
            $skinList[$iterator->getBasename()] = ExtensionInstance::newSkinByPath($iterator->getRealPath());
        }

        $this->extList = $extList;
        $this->skinList = $skinList;
    }

    public function getExtensionList() : ExtensionList
    {
        return $this->extList;
    }

    public function getSkinList() : ExtensionList
    {
        return $this->skinList;
    }

    /**
     * Checks if $path is a valid MediaWiki installation directory
     * @param string $path
     * @return bool
     */
    public static function isValid(string $path) : bool
    {
        $features = [
            '/includes/Setup.php',
            '/includes/MediaWiki.php',
            '/includes/MediaWikiServices.php',
        ];
        foreach ($features as $feature) {
            if (!file_exists("${path}${feature}")) {
                return false;
            }
        }
        return true;
    }

    /**
     * Catch the MediaWiki version from file
     * @param string $filePath
     * @return MWVersion
     * @throws FileAccessException Exception thrown if failed to read the file
     * @throws \UnexpectedValueException Exception thrown if failed to catch the MediaWiki version
     * @throws \UnexpectedValueException Exception thrown if the MediaWiki version is invalid
     */
    public static function catchVersionFromFile(string $filePath) : MWVersion
    {
        if (!is_readable($filePath)) {
            throw new FileAccessException($filePath, "Failed to read $filePath");
        }
        return self::catchVersionFromText(file_get_contents($filePath));
    }

    /**
     * Catch the MediaWiki version from text
     * @param string $text
     * @return MWVersion
     * @throws \UnexpectedValueException Exception thrown if failed to catch the MediaWiki version
     * @throws \UnexpectedValueException Exception thrown if the MediaWiki version is invalid
     */
    public static function catchVersionFromText(string $text) : MWVersion
    {
        if (!preg_match('/\$wgVersion\s=\s\'(?<version>.*?)\';/', $text, $matches)) {
            throw new \UnexpectedValueException('Failed to catch the MediaWiki version');
        }
        return new MWVersion($matches['version']);
    }
}
