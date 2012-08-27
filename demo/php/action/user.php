<?php
class user extends X {
    public function logout() {
        session_start();
        session_destroy();
        return $this->exit_json(1,'成功退出',array('act'=>'refresh','part'=>'page'));
    }

}
