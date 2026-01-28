<?php

class App_Admin_Common
{
    public static function getTypeConfig($typeName)
    {
        $config = self::getConfig();

        if (empty($config["types"][$typeName])) {
            log_error("type %s not configured", $typeName);
            throw new Framework_Errors_NotFound("unknown page type");
        }

        $type = $config["types"][$typeName];

        if (empty($type["model"])) {
            log_error("type %s has no model set", $typeName);
            throw new Framework_Errors_ServiceUnavailable("type not configured");
        }

        if (!class_exists($type["model"])) {
            log_error("type %s has bad model: %s", $typeName, $type["model"]);
            throw new Framework_Errors_ServiceUnavailable("type not configured");
        }

        return $type;
    }

    public static function getConfig()
    {
        $fn = get_app_path("config/admin.php");
        if (!is_readable($fn))
            throw new Framework_Errors_ServiceUnavailable("config/admin.php not found");

        $config = include $fn;
        if (!is_array($config))
            throw new RuntimeException("config/admin.php must return an array");

        $config = array_merge(array(
            "recent" => 10,
            "types" => array(),
            ), $config);

        // some validation
        if (!is_array($config["types"]))
            throw new RuntimeException("types must be an array");

        foreach ($config["types"] as $name => $type) {
            $config["types"][$name] = array_merge(array(
                "title" => "Objects of type {$name}",
                "model" => null,
                "notfound" => "Object not found.",
                "boolean" => array(),
                "template_edit" => "{$name}-edit.twig",
                "template_item" => "{$name}.twig",
                "template_list" => "{$name}s.twig",
                "admin_list_template" => "admin-docs.twig",
                "view_link" => null,
                "can_add" => true,
                "dashboard" => true,
                "list_where" => "1",
                "admin_list_where" => "1 ORDER BY `id` DESC",
                ), $type);
        }

        return $config;
    }

    public static function formatLink($template, array $data)
    {
        $link = $template;

        if (preg_match_all('@{([^}]+)}@', $template, $m)) {
            foreach ($m[1] as $idx => $key) {
                if (!array_key_exists($key, $data))
                    throw new RuntimeException("field {$key} not found");

                $src = $m[0][$idx];
                $link = str_replace($src, $data[$key], $link);
            }
        }

        return $link;
    }
}
