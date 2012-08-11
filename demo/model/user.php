<?php
class userModel extends XDataModel {
    protected function auto_conf() {
        $admin_table = $this->use_conf('txtdb','a');
        $this->connect_database($admin_table);
    }
    public function get_user_info($username) {
        $user_info = $this->DB->admin_table->get($username);
        return $user_info;
    }
    public function add_user($user_info) {
        $user_info['password'] = md5($user_info['password'],true);
        return $ret = $this->DB->admin_table->add($user_info['username'], $user_info);
    }
}
