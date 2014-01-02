<?php

function daemon($logFile = null) {
    if (!function_exists('pcntl_fork')) {
        if (!dl('pcntl.' . PHP_SHLIB_SUFFIX)) {
            echo("PCNTL extension not exists\n");
            return;
        }
    }
//    $oneForkPid = pcntl_fork();
//    if ($oneForkPid == -1)
//        die('fork #1 Error');
//    if ($oneForkPid > 0)
//        exit(0);
//    $secForkPid = pcntl_fork();
//    if ($secForkPid == -1)
//        die('fork #2 ERROR');
//    if ($secForkPid > 0)
//        die;
//    chdir('/');
//    umask('0');
//    posix_setsid();
    fclose(STDIN);
    fclose(STDOUT);
    fclose(STDERR);
    ob_start();
    var_dump(STDOUT);
    var_dump(is_resource(STDOUT));
    file_put_contents($logFile,ob_get_contents());
    ob_clean();
    die;
    if ($logFile) {
        $STDIN = fopen('/dev/null', 'r');
        $STDOUT = fopen($logFile, 'wb');
        $STDERR = fopen($logFile, 'wb');
    }
//    $subPid = pcntl_fork();
//    if ($subPid == -1)
//        die('fork #3 ERROR');
//    if ($subPid > 0)
//        exit(0);
}
daemon('/tmp/test.log');
while(true) {
echo 'test';
sleep(3);
}
