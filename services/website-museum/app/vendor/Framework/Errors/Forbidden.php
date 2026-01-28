<?php

class Framework_Errors_Forbidden extends RuntimeException
{
    public function __construct($message = null)
    {
        if (null === $message)
            $message = "No access.";
        parent::__construct($message, 403);
    }
}
