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

use Curl\Curl;
use RazeSoldier\MWUpKit\Exception\HttpTimeoutException;

/**
 * Used to access internet
 * @package RazeSoldier\MWUpKit
 */
class HttpClient
{
    /**
     * @var Curl
     */
    private $curlClient;

    public function __construct()
    {
        $this->curlClient = new Curl;
        $this->curlClient->setJsonDecoder(false); // Do not use the JSON decoder that comes with Curl\Curl
    }

    /**
     * Set proxy
     * @param string $proxy
     */
    public function setProxy(string $proxy)
    {
        $this->curlClient->setProxy($proxy);
    }

    /**
     * Send a GET Http request
     * @param string $url
     * @param array|null $headers
     * @return HttpResponse
     */
    public function GET(string $url, array $headers = []) : HttpResponse
    {
        $this->curlClient->setHeaders($headers);
        $this->curlClient->get($url);
        $this->clearCurlClientStatus();
        return new HttpResponse($this->curlClient->getHttpStatusCode(), $this->curlClient->getResponseHeaders(),
            $this->curlClient->getResponse());
    }

    /**
     * @param string $url
     * @param string $filename
     * @throws HttpTimeoutException Exception thrown if the timeout reached while HTTP connection
     * @throws \RuntimeException Exception thrown if any error occurred while HTTP connection
     */
    public function download(string $url, string $filename)
    {
        $this->curlClient->setTimeout(300);
        $res = $this->curlClient->download($url, $filename);
        $this->curlClient->setDefaultTimeout();
        if (!$res) {
            // Handle timeout error
            if ($this->curlClient->curlErrorCode === 28) {
                $msg = $this->curlClient->curlErrorMessage;
                $this->clearCurlClientStatus();
                throw new HttpTimeoutException($url, $msg);
            } else {
                $msg = $this->curlClient->getErrorMessage();
                $this->clearCurlClientStatus();
                throw new \RuntimeException("Failed to download $url, $msg");
            }
        }
    }

    /**
     * After send request, must call this method to clear the curl client status
     */
    private function clearCurlClientStatus()
    {
        $this->curlClient->error = false;
        $this->curlClient->errorCode = 0;
        $this->curlClient->errorMessage = null;
        $this->curlClient->curlError = false;
        $this->curlClient->curlErrorCode = 0;
        $this->curlClient->curlErrorMessage = null;
        $this->curlClient->httpError = false;
        $this->curlClient->httpStatusCode = 0;
        $this->curlClient->httpErrorMessage = null;
    }
}
