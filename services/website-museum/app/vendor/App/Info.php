<?php
/**
 * Вывод информации о PHP.
 **/

class App_Info extends App_Core_View
{
    public function onGet()
    {
        die(phpinfo());
    }
}
