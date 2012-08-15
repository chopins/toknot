<?php
/*
$ssh_ins = new XSSH2('192.168.1.251','22', 'root','251testroot');
$ssh_ins->connect();
$ssh_ins->create_sftp();
$ssh_ins->sendfile('/home/chopin/Downloads/100wine.tar.gz','/home/chopin/test/100wine.tar.gz',0644);
die;
*/
new XInotifySync(__DIR__.'/watch_list.ini',__DIR__.'/run',__DIR__.'/log');
