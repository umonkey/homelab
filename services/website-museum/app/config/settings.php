<?php
/**
 * Main website settings file.
 *
 * This file can go to the repository, so please don't add any sensitive data to it.
 * Instead, create files named settings_hostname_port.php, e.g.: settings_localhost_8080.php.
 *
 * This way you can have staging and local developement server etc.  You can include
 * other files to reuse common settings.
 **/

$config = array();

$config["db_dsn"] = "mysql:host=homelab_mysql;dbname=sebmus";
$config["db_user"] = "sebmus";
$config["db_password"] = "ecbuPyfV";

$config["mail_from"] = "info@example.com";
$config["mail_from_name"] = "My Website";
$config["mail_bcc"] = null;  // "archive@example.com, debug@example.com"

// This is used by locking and other parts of the code.
$config["tmpdir"] = getcwd() . "/tmp";

$config["template_class"] = "App_Core_Template";

$config["template_defaults"] = array(
    "site_name" => "Себежский краеведческий музей",
    );

$config["email_from"] = "hex+museum@umonkey.net";
$config["email_to"] = "seb-museum@yandex.ru";
$config["email_bcc"] = "hex+museum@umonkey.net";

$config['vk_oauth'] = [
    'id' => '6330063 ',
    'secret' => 'yxwVwXKaZHsXCjcnmvCX',
];

// The application is usually packaged in a PHAR file, which can't be edited quickly,
// so it's a good idea to patch settings with a separate file.  In that file you can
// directly modify the $config array.

if (is_readable($fn = __DIR__ . "/../settings.php"))
    @include $fn;

return $config;
