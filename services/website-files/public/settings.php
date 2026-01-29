<?php

$config["users"] = array(
    "umonkey" => "jCuo7FJJ",
    "estel" => "f7wuNsJv",
    );

$config["storage"] = "/home/files/app/storage";
$config["error_log"] = "/home/files/app/tmp/php.log";

$config["video_transcode"] = array(
    // "240p.webm" => "nice -n 15 avconv -loglevel panic -threads 0 -y -i '%s' -vcodec libvpx -vb 256k -preset slow -acodec libvorbis -ab 64k -ar 24000 -ac 1 -vf scale=424:240 -f webm '%s'",
    // "240p.mp4" => "nice -n 15 avconv -loglevel panic -threads 0 -y -i '%s' -vcodec libx264 -vb 256k -vprofile baseline -preset slow -acodec aac -strict -2 -ab 64k -ar 24000 -ac 1 -vf scale=424:240 -f mp4 '%s'",
    // "360p.webm" => "nice -n 15 avconv -loglevel panic -threads 0 -y -i '%s' -vcodec libvpx -vb 1000k -preset slow -acodec libvorbis -ab 128k -ar 48000 -ac 2 -vf scale=640:360 -f webm '%s'",
    "360p.webm" => "/h/files.umonkey.net/bin/encode-webm-360p '%s' '%s'",
    // "360p.mp4" => "nice -n 15 avconv -loglevel panic -threads 0 -y -i '%s' -vcodec libx264 -vb 1000k -vprofile baseline -preset slow -acodec aac -strict -2 -ab 128k -ar 48000 -ac 2 -vf scale=640:360 -f mp4 '%s'",
    "360p.mp4" => "/h/files.umonkey.net/bin/encode-mp4-360p '%s' '%s'",
    );

$config["thumbnail_rules"] = array(
    "lg" => array(
        "max" => 1000,
        "priority" => 5,
        ),
    "md" => array(
        "max" => 500,
        "source" => "lg",
        "priority" => 3,
        ),
    "sm" => array(
        "max" => 250,
        "source" => "lg",
        "priority" => 3,
        ),
    "hg" => array(
        "max" => 2000,
        "priority" => 5,
        ),
    );
