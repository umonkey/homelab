<?php $_phar = '/home/files/app/dist/files-v3.phar';
if (!is_readable($_phar)) die('Phar file not found: ' . $_phar);
require $_phar;
