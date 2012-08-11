<?php
/*
$ssh_ins = new XSSH2('192.168.1.251','22', 'root','251testroot');
$ssh_ins->connect();
$ssh_ins->create_sftp();
$ssh_ins->sendfile('/home/chopin/Code/toknot/demo/daemon/php/test/14','/home/chopin/test/14',0644);
die;*/
new XInotifySync(__DIR__.'/watch_list.ini',__DIR__.'/run',__DIR__.'/log');
