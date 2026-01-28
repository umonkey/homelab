<?php

class Framework_Errors_TemplateNotFound extends RuntimeException
{
    public function getTemplateName()
    {
        return $this->getMessage();
    }
}
