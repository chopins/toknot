<?php
class user extends XDataModel {
    protected function auto_conf() {
        $ins = $this->use_conf('txtdb','a');
        $this->connect_database($ins);
    }
    public function get_user_info($username) {
        $user_info = $this->DB->admin_table->get($username);
        return $user_info;
    }
    public function add_user($user_info) {
        return $ret = $this->DB->admin_user->add($user_info['username'], $user_info);
    }
}
