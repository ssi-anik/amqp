<?php

namespace Anik\Amqp\Exceptions;

use Exception;

class AmqpException extends Exception
{
    public function __construct($message = "", $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
