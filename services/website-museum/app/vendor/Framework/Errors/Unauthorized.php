<?php

class Framework_Errors_Unauthorized extends RuntimeException
{
    public function __construct($message = null)
    {
        if (null === $message)
            $message = "Unauthorized.";
        parent::__construct($message, 401);
    }
}
