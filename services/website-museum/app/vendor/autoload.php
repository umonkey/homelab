<?php

function class_loader($className)
{
    $fileName = str_replace("_", "/", $className) . ".php";
    $filePath = sprintf("%s/vendor/%s", APP_ROOT, $fileName);

    if (is_readable($filePath)) {
        require $fileName;
    } else {
        error_log("WRN: bootstrap: unable to load class {$className} from {$fileName}.");
    }
}

spl_autoload_register("class_loader");
