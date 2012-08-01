<?php
class index extends X {
    public $loginStat = false;
    public function init() {
        $this->checklogin();
    }
    public function checklogin() {
        return $this->loginStat = !empty($this->R->S->username);
    }
    public function gindex() {
        if($this->R->AS) {
            return $this->exit_json(1,'ok');
        } else {
            $this->T->name = 'home';
            $this->T->type = 'htm';
            $this->T->static_cache = true;
            $this->T->check_cache();
            if($this->T->be_cache) return;
            $this->D->top_nav = array();
            $this->D->footer_nav = array();
        }
    }
    public function glogout($te= 1) {
        session_destroy();
        return $this->exit_json(1,'成功退出',array('act'=>'refresh','part'=>'page'));
    }
    public function plogin() {
        if(empty($this->R->A->username)) {
            return $this->exit_json(0,'用户名不能为空');
        }
        if(empty($this->R->A->password)) {
            return $this->exit_json(0,'密码不能为空');
        }
        $password = md5($this->R->A->password, true);
        $user_info = $this->LM('user')->get_user_info($this->R->A->username);
        if(empty($user_info) || $user_info['password'] != $password) {
            return $this->exit_json(0,'用户名或密码错误');
        }
        $this->R->S->username = $this->R->A->username;
        $this->R->C->username = $this->R->A->username;
        $this->R->C->username->set();
        return $this->exit_json(1,'登录成功','/index/mynav');
    }
    public function gmynav() {
        if($this->loginStat == false) return $this->exit_json(0,'', $this->login_ui());
        $nav_list = array("{$this->R->S->username}|/user/info|true",
                          '后台首页|/index',
                          '个人信息|/user/info',
                          '项目|/project/all',
                          '服务器|/server/all',
                          'Push|/push/all',
                          '短信|/message/all',
                          '用户列表|/user/all',
                          'Dopush|/dopush/info',
                          '退出|/index/logout');
        return $this->exit_json(1,'', $nav_list);
    }
    public function login_ui() {
        $login_form_input = array();
        $login_form_input[] = array('label'=>'用户名','type'=>'text','name'=>'username','value'=>'','cls'=>'b-text-input');
        $login_form_input[] = array('label'=>'密码','type'=>'password','name'=>'password','value'=>'','cls'=>'b-text-input');
        $login_form_button = array();
        $login_form_button[] = array('label'=>'登录','value'=>'','cls'=>'b-button-input',
                'call'=>'dop.userLoginAct','url'=>'/index/login');
        $login_form = array('input'=>$login_form_input,
                            'button'=>$login_form_button,
                            'cls'=>'b-input-box',
                            'title'=>'登录');
        return $login_form;
    }
    public function gchecklogin() {
        if($this->loginStat == false) {
            $login_form = $this->login_ui(); 
            $this->exit_json(0,'未登录', $login_form);
        } else {
            $this->R->C->username = $this->R->S->username;
            $this->R->C->username->set();
            $this->exit_json(1,'已登录','/index/mynav');
        }
    }
}
