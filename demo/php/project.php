<?php
class project extends X {
    public $loginStat = false;
    public function init() {
        $loginStat = $this->CV('index','checklogin');
        if(empty($this->R->S->username)) {
            $this->stop_run = true;
            $loginUi = $this->CV('index','login_ui');
            return $this->exit_json(0,'未登录',$loginUi);
        }
    }
    public function glist() {
        $table = array('opreate_nav'=> array('添加项目|/project/add','项目列表|/project/list','我的项目|/user/project'),
            'table_title'=> '项目名|更新时间|代码推送|操作',
            'table'=>array('test','2001','全部|Merlot','项目信息|最后更新日志|关闭'),
        );
        $this->exit_json(1, '项目列表', $table);
    }
}
