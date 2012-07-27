<?php
class user extends XDataModel {
    protected function conf() {
        $this->dbtype = 'txtdb';
        $this->dbname = 'admin_table.db';
    }
    public function get_user_info($username) {
        $admin_user = $this->connect_database('conf');
        $user_info = $admin_user->get($username);
        dump($user_info);
    }
}
