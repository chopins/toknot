<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Account;

use Admin\View\Lib\BaseView;

class Index extends BaseView {

    public function contanier() {
        $this->button($this->rbox)->pushText('个人信息');
        $this->button($this->rbox)->pushText('修改信息');
        $this->button($this->rbox)->pushText('修改安全信息');
        $this->button($this->rbox)->pushText('个人简历');
        $this->p($this->rbox)->pushText('最后登录信息');
        $this->p($this->rbox)->pushText('头像');
        $this->p($this->rbox)->pushText('账户名');
        $this->p($this->rbox)->pushText('邮件*隐藏部分');
        $this->p($this->rbox)->pushText('手机号*隐藏部分');
        $this->p($this->rbox)->pushText('最近三条动态');
        $this->p($this->rbox)->pushText('最近三条事件进度');
        $this->p($this->rbox)->pushText('所属项目');
        $this->p($this->rbox)->pushText('所属组织');
        $this->p($this->rbox)->pushText('快捷事件操作');
    }

}
