<?php

class Framework_Redirect extends Framework_Response
{
    public function __construct($location, $status = "303 See Other")
    {
        parent::__construct();

        $this->setStatus($status);
        $this->setHeader("Location", $location);
    }
}
