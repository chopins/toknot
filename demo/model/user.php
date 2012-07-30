<?php
class user extends XDataModel {
    protected function conf() {
        $this->dbtype = 'txtdb';
        $this->dbname = 'admin_table';
    }
    public function get_user_info($username) {
        $admin_user = $this->connect_database('conf');
        $user_info = $admin_user->get($username);
        return $user_info;
    }
    public function add_user($user_info) {
        $admin_user = $this->connect_database('conf');
        return $ret = $admin_user->add($user_info['username'], $user_info);
    }
}
