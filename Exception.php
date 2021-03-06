<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2013, Ivan Enderlin. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Hoa\Core\Exception {

/**
 * Class \Hoa\Core\Exception\Idle.
 *
 * \Hoa\Core\Exception\Idle is the mother exception class of libraries. The only
 * difference between \Hoa\Core\Exception\Idle and its directly child
 * \Hoa\Core\Exception is that the later fires event after beeing constructed.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Ivan Enderlin.
 * @license    New BSD License
 */

class Idle extends \Exception {

    /**
     * Delay processing on arguments.
     *
     * @var \Hoa\Core\Exception array
     */
    protected $_tmpArguments = null;

    /**
     * Arguments to format message.
     *
     * @var \Hoa\Core\Exception array
     */
    protected $_arguments    = null;

    /**
     * Backtrace.
     *
     * @var \Hoa\Core\Exception\Idle array
     */
    protected $_trace        = null;

    /**
     * Previous.
     *
     * @var \Exception object
     */
    protected $_previous     = null;



    /**
     * Create an exception.
     * An exception is built with a formatted message, a code (an ID), and an
     * array that contains the list of formatted string for the message. If
     * chaining, we can add a previous exception.
     *
     * @access  public
     * @param   string      $message      Formatted message.
     * @param   int         $code         Code (the ID).
     * @param   array       $arguments    Arguments to format message.
     * @param   \Exception  $previous     Previous exception in chaining.
     * @return  void
     */
    public function __construct ( $message, $code = 0, $arguments = array(),
                                  \Exception $previous = null ) {

        $this->_tmpArguments = $arguments;
        parent::__construct($message, $code, $previous);

        return;
    }

    /**
     * Get the backtrace.
     * Do not use \Exception::getTrace() any more.
     *
     * @access  public
     * @return  array
     */
    public function getBacktrace ( ) {

        if(null === $this->_trace)
            $this->_trace = $this->getTrace();

        return $this->_trace;
    }

    /**
     * Get previous.
     * Do not use \Exception::getPrevious() any more.
     *
     * @access  public
     * @return  \Exception
     */
    public function getPreviousThrow ( ) {

        if(null === $this->_previous)
            $this->_previous = $this->getPrevious();

        return $this->_previous;
    }

    /**
     * Get arguments for the message.
     *
     * @access  public
     * @return  array
     */
    public function getArguments ( ) {

        if(null === $this->_arguments) {

            $arguments = $this->_tmpArguments;

            if(!is_array($arguments))
                $arguments = array($arguments);

            foreach($arguments as $key => &$value)
                if(null === $value)
                    $value = '(null)';

            $this->_arguments = $arguments;
            unset($this->_tmpArguments);
        }

        return $this->_arguments;
    }

    /**
     * Get the message already formatted.
     *
     * @access  public
     * @return  string
     */
    public function getFormattedMessage ( ) {

        return @vsprintf($this->getMessage(), $this->getArguments());
    }

    /**
     * Get the source of the exception (class, method, function, main etc.).
     *
     * @access  public
     * @return  string
     */
    public function getFrom ( ) {

        $trace = $this->getBacktrace();
        $from  = '{main}';

        if(!empty($trace)) {

            $t    = $trace[0];
            $from = '';

            if(isset($t['class']))
                $from .= $t['class'] . '::';

            if(isset($t['function']))
                $from .= $t['function'] . '()';
        }

        return $from;
    }

    /**
     * Raise an exception as a string.
     *
     * @access  public
     * @param   bool    $previous    Whether raise previous exception if exists.
     * @return  string
     */
    public function raise ( $previous = false ) {

        $message = $this->getFormattedMessage();
        $trace   = $this->getBacktrace();
        $file    = '/dev/null';
        $line    = -1;
        $pre     = $this->getFrom();

        if(!empty($trace)) {

            $file = isset($trace['file']) ? $trace['file'] : null;
            $line = isset($trace['line']) ? $trace['line'] : null;
        }

        $pre  .= ': ';

        try {

            $out = $pre . '(' . $this->getCode() . ') ' . $message . "\n" .
                   'in ' . $this->getFile() . ' at line ' .
                   $this->getLine() . '.';
        }
        catch ( \Exception $e ) {

            $out = $pre . '(' . $this->getCode() . ') ' . $message . "\n" .
                   'in ' . $file . ' around line ' . $line . '.';
        }

        if(   true === $previous
           && null !== $previous = $this->getPreviousThrow())
            $out .= "\n\n" . '    ⬇' . "\n\n" .
                    'Nested exception (' . get_class($previous) . '):' . "\n" .
                    $previous->raise(true);

        return $out;
    }

    /**
     * Catch uncaught exception (only \Hoa\Core\Exception\Idle and children).
     *
     * @access  public
     * @param   \Exception  $exception    The exception.
     * @return  void
     * @throw   \Exception
     */
    public static function uncaught ( \Exception $exception ) {

        if(!($exception instanceof Idle))
            throw $exception;

        while(0 < ob_get_level())
            ob_end_flush();

        echo 'Uncaught exception (' . get_class($exception) . '):' . "\n" .
             $exception->raise(true);

        return;
    }

    /**
     * Catch PHP (and PHP_USER) errors and transform them into
     * \Hoa\Core\Exception\Error.
     * Obviously, if code that caused the error is preceeded by @, then we do
     * not thrown any exception.
     *
     * @access  public
     * @param   int     $errno      Level.
     * @param   string  $errstr     Message.
     * @param   string  $errfile    File.
     * @param   int     $errline    Line.
     * @return  void
     * @throw   \Hoa\Core\Exception\Error
     */
    public static function error ( $errno, $errstr, $errfile, $errline ) {

        if(0 === ($errno & error_reporting()))
            return;

        $trace = debug_backtrace();
        array_shift($trace);
        array_shift($trace);

        throw new Error($errstr, -1, $errfile, $errline, $trace);
    }

    /**
     * String representation of object.
     *
     * @access  public
     * @return  string
     */
    public function __toString ( ) {

        return $this->raise();
    }
}

/**
 * Class \Hoa\Core\Exception.
 *
 * Each exception must extend \Hoa\Core\Exception.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Ivan Enderlin.
 * @license    New BSD License
 */

class Exception extends Idle implements \Hoa\Core\Event\Source {

    /**
     * Create an exception.
     * An exception is built with a formatted message, a code (an ID), and an
     * array that contains the list of formatted string for the message. If
     * chaining, we can add a previous exception.
     *
     * @access  public
     * @param   string      $message      Formatted message.
     * @param   int         $code         Code (the ID).
     * @param   array       $arguments    Arguments to format message.
     * @param   \Exception  $previous     Previous exception in chaining.
     * @return  void
     */
    public function __construct ( $message, $code = 0, $arguments = array(),
                                  \Exception $previous = null ) {

        parent::__construct($message, $code, $arguments, $previous);

        if(false === \Hoa\Core\Event::eventExists('hoa://Event/Exception'))
            \Hoa\Core\Event::register('hoa://Event/Exception', __CLASS__);

        $this->send();

        return;
    }

    /**
     * Send the exception on hoa://Event/Exception.
     *
     * @access  public
     * @return  void
     */
    public function send ( ) {

        \Hoa\Core\Event::notify(
            'hoa://Event/Exception',
            $this,
            new \Hoa\Core\Event\Bucket($this)
        );

        return;
    }
}

/**
 * Class \Hoa\Core\Exception\Error.
 *
 * This exception is the equivalent representation of PHP errors.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Ivan Enderlin.
 * @license    New BSD License
 */

class Error extends Exception {

    /**
     * Constructor.
     *
     * @access  public
     * @param   string  $message    Message.
     * @param   int     $code       Code (the ID).
     * @param   string  $file       File.
     * @param   int     $line       Line.
     * @param   array   $trace      Trace.
     */
    public function __construct ( $message, $code, $file, $line,
                                  Array $trace = array() ) {

        $this->file   = $file;
        $this->line   = $line;
        $this->_trace = $trace;

        parent::__construct($message, $code);

        return;
    }
}

/**
 * Class \Hoa\Core\Exception\Group.
 *
 * This is an exception that contains a group of exceptions.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Ivan Enderlin.
 * @license    New BSD License
 */

class          Group
    extends    Exception
    implements \ArrayAccess, \IteratorAggregate, \Countable {

    /**
     * All exceptions (stored in a stack for transactions).
     *
     * @var \SplStack object
     */
    protected $_group = null;



    /**
     * Create an exception.
     *
     * @access  public
     * @param   string      $message      Formatted message.
     * @param   int         $code         Code (the ID).
     * @param   array       $arguments    Arguments to format message.
     * @param   \Exception  $previous     Previous exception in chaining.
     * @return  void
     */
    public function __construct ( $message, $code = 0, $arguments = array(),
                                  \Exception $previous = null ) {

        parent::__construct($message, $code, $arguments, $previous);
        $this->_group = new \SplStack();
        $this->beginTransaction();

        return;
    }

    /**
     * Raise an exception as a string.
     *
     * @access  public
     * @param   bool    $previous    Whether raise previous exception if exists.
     * @return  string
     */
    public function raise ( $previous = false ) {

        $out = parent::raise($previous);

        if(0 >= count($this))
            return $out;

        $out .= "\n\n" . 'Contains the following exceptions:';

        foreach($this as $exception)
            $out .= "\n\n" . '  • ' . str_replace(
                "\n",
                "\n" . '    ',
                $exception->raise($previous)
            );

        return $out;
    }

    /**
     * Begin a transaction.
     *
     * @access  public
     * @return  \Hoa\Core\Exception\Group
     */
    public function beginTransaction ( ) {

        $this->_group->push(new \ArrayObject());

        return $this;
    }

    /**
     * Rollback a transaction.
     *
     * @access  public
     * @return  \Hoa\Core\Exception\Group
     */
    public function rollbackTransaction ( ) {

        if(1 >= count($this->_group))
            return $this;

        $this->_group->pop();

        return $this;
    }

    /**
     * Commit a transaction.
     *
     * @access  public
     * @return  \Hoa\Core\Exception\Group
     */
    public function commitTransaction ( ) {

        if(false === $this->hasUncommittedExceptions())
            return $this;

        foreach($this->_group->pop() as $index => $exception)
            $this->offsetSet($index, $exception);

        return $this;
    }

    /**
     * Check if there is uncommitted exceptions.
     *
     * @access  public
     * @return  bool
     */
    public function hasUncommittedExceptions ( ) {

        return    1 < count($this->_group)
               && 0 < count($this->_group->top());
    }

    /**
     * Check if an index in the group exists.
     *
     * @access  public
     * @param   mixed  $index    Index.
     * @return  bool
     */
    public function offsetExists ( $index ) {

        foreach($this->_group as $group)
            if(isset($group[$index]))
                return true;

        return false;
    }

    /**
     * Get an exception from the group.
     *
     * @access  public
     * @param   mixed  $index    Index.
     * @return  \Exception
     */
    public function offsetGet ( $index ) {

        foreach($this->_group as $group)
            if(isset($group[$index]))
                return $group[$index];

        return null;
    }

    /**
     * Set an exception in the group.
     *
     * @access  public
     * @param   mixed       $index        Index.
     * @param   \Exception  $exception    Exception.
     * @return  void
     */
    public function offsetSet ( $index, $exception ) {

        if(!($exception instanceof \Exception))
            return null;

        $group = $this->_group->top();

        if(   null === $index
           || true === is_int($index))
            $group[]       = $exception;
        else
            $group[$index] = $exception;

        return;
    }

    /**
     * Remove an exception in the group.
     *
     * @access  public
     * @param   mixed  $index    Index.
     * @return  void
     */
    public function offsetUnset ( $index ) {

        foreach($this->_group as $group)
            if(isset($group[$index]))
                unset($group[$index]);

        return;
    }

    /**
     * Get committed exceptions in the group.
     *
     * @access  public
     * @return  \ArrayObject
     */
    public function getExceptions ( ) {

        return $this->_group->bottom();
    }

    /**
     * Get an iterator over all exceptions (committed or not).
     *
     * @access  public
     * @return  \ArrayIterator
     */
    public function getIterator ( ) {

        return $this->getExceptions()->getIterator();
    }

    /**
     * Count the number of committed exceptions.
     *
     * @access  public
     * @return  int
     */
    public function count ( ) {

        return count($this->_group->bottom());
    }
}

}

namespace {

/**
 * Alias.
 */
class_alias('Hoa\Core\Exception\Exception', 'Hoa\Core\Exception');

}
