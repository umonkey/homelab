<?php

class App_Util extends Framework_Util
{
    public static function writeFile($path, $data)
    {
        $folder = dirname($path);
        if (!is_dir($folder)) {
            @mkdir($folder, 0755, true);
        }

        file_put_contents($path, $data);
        log_debug("wrote %u bytes to %s", strlen($data), $path);
    }

    public static function hook($name, array $args = array())
    {
        $hooks = Framework_Config::get("hooks", array());
        if (empty($hooks[$name]))
            return null;

        foreach ($hooks[$name] as $fn) {
            call_user_func_array($fn, $args);
        }
    }
}
