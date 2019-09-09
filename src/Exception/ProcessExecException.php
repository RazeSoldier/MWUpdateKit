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

namespace RazeSoldier\MWUpKit\Exception;

use Symfony\Component\Process\Process;
use Throwable;

/**
 * Exception thrown if the process exit code is not 0
 * @package RazeSoldier\MWUpKit\Exception
 */
class ProcessExecException extends \Exception
{
    private $process;

    public function __construct(Process $process, string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->process = $process;
        if ($message === '') {
            $this->message = $this->__toString();
        } else {
            $this->message = $message;
        }
    }

    public function getProcess() : Process
    {
        return $this->process;
    }

    public function getProcessExitCode() : int
    {
        return $this->process->getExitCode();
    }

    public function getProcessErrorOutput() : string
    {
        return $this->process->getErrorOutput();
    }

    public function __toString() : string
    {
        return "Exception: {$this->process->getErrorOutput()}";
    }
}
