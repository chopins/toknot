#!/opt/php/bin/php -f
<?php

include_once(__DIR__.'/XSSH2.php');
include_once(__DIR__.'/XInotyfySync.php');
new XInotifySync(0, __DIR__.'/watch_list.conf');
