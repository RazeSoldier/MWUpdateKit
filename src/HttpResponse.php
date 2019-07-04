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

use Curl\CaseInsensitiveArray;

/**
 * This store the response from a remote
 * @package RazeSoldier\MWUpKit
 */
class HttpResponse
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var CaseInsensitiveArray
     */
    private $headers = [];

    /**
     * @var string
     */
    private $body;

    public function __construct(int $statusCode, CaseInsensitiveArray $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * @return int
     */
    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    public function getHeader(string $key) : string
    {
        if (!isset($this->headers[$key])) {
            throw new \OutOfRangeException("No $key header");
        }
        return $this->headers[$key];
    }

    /**
     * @return CaseInsensitiveArray
     */
    public function getHeaders() : CaseInsensitiveArray
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getBody() : string
    {
        return $this->body;
    }

    /**
     * Whether the request was successful
     * @return bool
     */
    public function isOk() : bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
