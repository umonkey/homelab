<?php

class Framework_Errors_NotFound extends RuntimeException
{
    public function __construct($message, $code = 404)
    {
        return parent::__construct($message, $code);
    }
}
