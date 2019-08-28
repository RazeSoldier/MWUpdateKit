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

/**
 * Used to store the result of preparation
 * @package RazeSoldier\MWUpKit\MediaWiki\Preparer
 */
class PrepareResult
{
    /**
     * @var string[]
     */
    private $okItem = [];

    /**
     * @var array[]
     *   "name" => Item name
     *   "reason" => Reason of failure
     */
    private $failItem = [];

    /**
     * @note Only call by preparer
     * @param string $item
     * @return PrepareResult
     */
    public function addOkItem(string $item) : self
    {
        $this->okItem[] = $item;
        return $this;
    }

    /**
     * @note Only call by preparer
     * @param string $item
     * @param string|null $reason Reason of failure
     * @return PrepareResult
     */
    public function addFailItem(string $item, string $reason = null) : self
    {
        $this->failItem[] = [
            'name' => $item,
            'reason' => $reason,
        ];
        return $this;
    }

    /**
     * All ok?
     * @return bool
     */
    public function isAllOK() : bool
    {
        return $this->failItem === [];
    }

    /**
     * All fail?
     * @return bool
     */
    public function isAllFail() : bool
    {
        return $this->okItem === [] && $this->failItem !== [];
    }

    public function hasOK() : bool
    {
        return $this->okItem !== [];
    }

    public function hasFail() : bool
    {
        return $this->failItem !== [];
    }

    /**
     * @return string[]
     */
    public function getOkItem() : array
    {
        return $this->okItem;
    }

    /**
     * @return array[]
     */
    public function getFailItem() : array
    {
        return $this->failItem;
    }
}
