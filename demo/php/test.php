<?php
class test extends X {
    public function gindex() {
        $db = new XFirebirdLocal();
        $db->set_db_path('/home/chopin/Code/toknot/demo/var/db/firebird');
        $db->create_database('dopush');
    }
    public function pindex() {
    }
    public function gtest() {
    }
}
