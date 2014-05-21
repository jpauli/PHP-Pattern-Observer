<?php
/**
* Observer-SPL-PHP-Pattern
*
* Copyright (c) 2010, Julien Pauli <jpauli@php.net>.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions
* are met:
*
* * Redistributions of source code must retain the above copyright
* notice, this list of conditions and the following disclaimer.
*
* * Redistributions in binary form must reproduce the above copyright
* notice, this list of conditions and the following disclaimer in
* the documentation and/or other materials provided with the
* distribution.
*
* * Neither the name of Julien Pauli nor the names of his
* contributors may be used to endorse or promote products derived
* from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
* "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
* LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
* FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
* COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
* CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
* LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
* ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* @package Observer
* @author Julien Pauli <jpauli@php.net>
* @copyright 2010 Julien Pauli <jpauli@php.net>
* @license http://www.opensource.org/licenses/bsd-license.php BSD License
*/
namespace Observer;

/**
* Base class for error handling.
 * This class may not be perfect but is a very good implementation
* of the Subject/Observer pattern in PHP.
*
* @package Observer
* @author Julien Pauli <jpauli@php.net>
* @copyright 2010 Julien Pauli <jpauli@php.net>
* @license http://www.opensource.org/licenses/bsd-license.php BSD License
* @version Release: @package_version@
*/
final class ErrorHandler implements Pattern\Subject, \IteratorAggregate, \Countable
{
    /**
     * Singleton instance
     */
    private static ?self $instance;

    private array $error = [ ];

    /**
     * Either or not fallback to PHP internal
     * error handler
     */
    private bool $fallBackToPHPErrorHandler = false;

    /**
     * Either or not rethrow the PHP Error
     */
    private bool $rethrowException = true;

    /**
     * Either or not clear the last error after
     * sending it to Listeners
     */
    private bool $clearErrorAfterSending = true;

    /**
     * Should ErrorHandler catch its listeners exception
     * while dispatching them ?
     */
    private bool $catchListenersException = true;

    /**
     * Listeners classes namespace
     * @var string
     */
    public const LISTENERS_NS = "Listeners";

    private \SplObjectStorage $observers;

    /**
     * Retrieves singleton instance
     */
    public static function getInstance(bool $andStart = false) : self
    {
        if (self::$instance == null) {
            self::$instance = new self;
            if ($andStart) {
                self::$instance->start();
            }
        }
        return self::$instance;
    }

    /**
     * Singleton : can't be cloned
     */
    private function __clone() { }

    /**
     * Singleton constructor
     */
    private function __construct()
    {
        $this->observers = new \SplObjectStorage();
    }

    /**
     * Factory to build some Listeners
     *
     * @return object|false
     */
    public static function factory(string $listener, array $args = [ ]) : object|false
    {
        $class = sprintf('%s\%s\%s',  __NAMESPACE__, self::LISTENERS_NS, $listener);
        try {
            $reflect = new \ReflectionClass($class);
            return $reflect->newInstanceArgs($args);
        } catch (\ReflectionException) {
            return false;
        }
    }

    /**
     * Method run by PHP's error handler
     */
    public function __invoke(int $errno, string $errstr, string $errfile = '', int $errline = 0) : bool
    {
        if(error_reporting() == 0) { // @ errors ignored
            return false;
        }
        $this->error = [$errno, $errstr, $errfile, $errline];
        $this->notify();

        return !$this->fallBackToPHPErrorHandler;
    }

    /**
     * Method run by PHP's exception handler
     */
    public function exceptionHandler(\Throwable $e)
    {
        if ($e instanceof \Error) {
            $this($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
            if (!$this->rethrowException) {
                return;
            }
        }
        throw $e;
    }

    /**
     * Either or not fallback to PHP internal
     * error handler
     */
    public function setFallBackToPHPErrorHandler( bool $bool) : self
    {
        $this->fallBackToPHPErrorHandler = $bool;
        return $this;
    }

    /**
     * Either or not fallback to PHP internal
     * error handler
     */
    public function getRethrowException() : bool
    {
        return $this->rethrowException;
    }

    /**
     * Either or not rethrow PHP error
     */
    public function setRethrowException(bool $bool) : self
    {
        $this->rethrowException = $bool;
        return $this;
    }

    /**
     * Either or not rethrow PHP Error
     */
    public function getFallBackToPHPErrorHandler() : bool
    {
        return $this->fallBackToPHPErrorHandler;
    }

    /**
     * Either or not clear the last error after
     * sending it to Listeners
     */
    public function setClearErrorAfterSending(bool $bool) : self
    {
        $this->clearErrorAfterSending = $bool;
        return $this;
    }

    /**
     * Either or not clear the last error after
     * sending it to Listeners
     */
    public function getClearErrorAfterSending() : bool
    {
        return $this->clearErrorAfterSending;
    }

    /**
     * Either or not the ErrorHandler should catch its
     * listeners' exceptions while notifying() them
     */
    public function setCatchListenersException(bool $bool) : self
    {
        $this->catchListenersException = $bool;
        return $this;
    }

    /**
     * Either or not the ErrorHandler should catch its
     * listeners' exceptions while notifying() them
     */
    public function getCatchListenersException() : bool
    {
        return $this->catchListenersException;
    }

    /**
     * Starts the ErrorHandler
     */
    public function start() : self
    {
        set_error_handler($this);
        set_exception_handler($this->exceptionHandler(...));

        return $this;
    }

    /**
     * Stops the ErrorHandler
     */
    public function stop() : self
    {
        restore_error_handler();
        set_exception_handler(null);

        return $this;
    }

    /**
     * Observer pattern : shared method
     * to all observers
     *
     * @return string|bool
     */
    public function getError() : bool|string
    {
        if (!$this->error) {
            return false;
        }
        return vsprintf("Error %d: %s, in file %s at line %d", $this->error);
    }

    /**
     * Resets the singleton instance
     */
    public static function resetInstance(bool $andRecreate = false) : ?self
    {
        self::$instance = null;
        return $andRecreate ? self::getInstance() : null;
    }

    /**
     * Observer pattern : attaches observers
     */
    public function attach(Pattern\Observer ...$obs) : self
    {
        foreach ($obs as $o) {
            $this->observers->attach($o);
        }
        return $this;
    }

    /**
     * Observer pattern : detaches observers
     */
    public function detach(Pattern\Observer ...$obs) : self
    {
        foreach ($obs as $o) {
            $this->observers->detach($o);
        }
        return $this;
    }

    /**
     * Observer pattern : notify observers
     */
    public function notify() : int
    {
        $i = 0;
        foreach ($this as $observer) {
            try {
                $observer->update($this);
                $i++;
            } catch(\Exception $e) {
                if (!$this->catchListenersException) {
                    throw new ErrorHandlerException("Exception while notifying observer", previous:$e);
                }
            } finally {
                if ($this->clearErrorAfterSending) {
                    $this->error = array();
                }
            }
        }

        if ($this->clearErrorAfterSending) {
            $this->error = array();
        }

        return $i;
    }

    /**
     * IteratorAggregate
     */
    public function getIterator() : \Iterator
    {
        return $this->observers;
    }

    /**
     * Countable
     */
    public function count() : int
    {
        return count($this->observers);
    }

    /**
     * Hack for 1.attach('Listener')
     *          2.detach('Listener')
     *
     * @param string $funct
     * @param array $args
     * @return ErrorHandler
     * @throws \BadMethodCallException
     */
    public function __call($funct, $args)
    {
        if (preg_match('#(?P<prefix>at|de)tach(?P<listener>\w+)#', $funct, $matches)) {
            $meth     = $matches['prefix'] . 'tach';
            $listener = ucfirst(strtolower($matches['listener']));
            return $this->$meth(self::factory($listener, $args));
        }
        throw new \BadMethodCallException("unknown method $funct");
    }
}
