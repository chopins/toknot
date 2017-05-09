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
        $ts = [];
        $ts[] = $this->button()->pushText('个人信息');
        $ts[] = $this->button()->pushText('修改信息');
        $ts[] = $this->button()->pushText('修改安全信息');
        $ts[] = $this->button()->pushText('个人简历');
        $ts[] = $this->p()->pushText('最后登录信息');
        $ts[] = $this->p()->pushText('头像');
        $ts[] = $this->p()->pushText('账户名');
        $ts[] = $this->p()->pushText('邮件*隐藏部分');
        $ts[] = $this->p()->pushText('手机号*隐藏部分');
        $ts[] = $this->p()->pushText('最近三条动态');
        $ts[] = $this->p()->pushText('最近三条事件进度');
        $ts[] = $this->p()->pushText('所属项目');
        $ts[] = $this->p()->pushText('所属组织');
        $ts[] = $this->p()->pushText('快捷事件操作');

        $this->rbox->batchPush($ts);
    }

}
