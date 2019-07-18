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

/**
 * List that store ExtensionInstance
 * @package RazeSoldier\MWUpKit\MediaWiki
 */
class ExtensionList implements \ArrayAccess, \Countable, \Iterator
{
    /**
     * @var ExtensionInstance[] (name => ExtensionInstance) Map
     */
    private $list = [];

    /**
     * @var string[] (int => name)
     */
    private $listIndex = [];

    /**
     * @var int Pointer for $this->list
     */
    private $internalPointer = 0;

    public function __construct(array $initValue = null)
    {
        if ($initValue !== null) {
            $this->setList($initValue);
        }
    }

    /**
     * @return ExtensionInstance[]
     */
    public function getList() : array
    {
        return $this->list;
    }

    public function setList(array $list)
    {
        $arr = [];
        foreach ($list as $item) {
            if (!$item instanceof ExtensionInstance) {
                throw new \InvalidArgumentException('$list contains non-ExtensionInstance value');
            }
            $arr[$item->getName()] = $item;
        }
        $this->list = $arr;
        $this->listIndex = array_keys($arr);
    }

    /**
     * Whether the list is empty?
     * @return bool
     */
    public function isEmpty() : bool
    {
        return count($this->list) === 0;
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for
     * @return bool true on success or false on failure.
     */
    public function offsetExists($offset) : bool
    {
        return isset($this->list[$offset]);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve
     * @return ExtensionInstance Can return all value types.
     */
    public function offsetGet($offset) : ExtensionInstance
    {
        return $this->list[$offset];
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param string|null $offset The offset to assign the value to
     * @param ExtensionInstance $value The value to set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (!$value instanceof ExtensionInstance) {
            throw new \InvalidArgumentException('$value is not ExtensionInstance');
        }
        if ($offset === null || is_int($offset)) {
            $offset = $value->getName();
        } elseif (!is_string($offset)) {
            throw new \InvalidArgumentException('$offset is not a string');
        }
        $this->list[$offset] = $value;
        $this->listIndex[] = $offset;
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->list[$offset]);
        unset($this->listIndex[array_search($offset, $this->listIndex)]);
    }

    /**
     * Count elements of an object
     * @link https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     */
    public function count() : int
    {
        return count($this->list);
    }

    /**
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return ExtensionInstance
     */
    public function current() : ExtensionInstance
    {
        return $this->list[$this->listIndex[$this->internalPointer]];
    }

    /**
     * Move forward to next element
     * @link https://php.net/manual/en/iterator.next.php
     * @return void
     */
    public function next()
    {
        $this->internalPointer++;
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return string scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->listIndex[$this->internalPointer];
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return isset($this->listIndex[$this->internalPointer]);
    }

    /**
     * Rewind the Iterator to the first element
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void
     */
    public function rewind()
    {
        $this->internalPointer = 0;
    }
}
