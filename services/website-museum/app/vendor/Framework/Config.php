<?php

class Framework_Config
{
    private static $config = null;

    public static function get($key, $default = null)
    {
        if (self::$config === null) {
            self::$config = array();

            if (defined("APP_ROOT") and is_readable($fn = APP_ROOT . "/config/settings.php")) {
                $tmp = include $fn;
                if (!is_array($tmp))
                    throw new RuntimeException("config/settings.php did not return an array");
                self::$config = array_merge(self::$config, $tmp);
            }
        }

        if (array_key_exists($key, self::$config))
            return self::$config[$key];

        return $default;
    }
}
