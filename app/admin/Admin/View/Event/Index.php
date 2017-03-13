<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Event;

use Admin\View\Lib\BaseView;

class Index extends BaseView {

    public function contanier() {
        $this->button($this->rbox)->pushText('发起事件');
        $this->button($this->rbox)->pushText('项目事件');
        $this->button($this->rbox)->pushText('组织事件');
        $this->button($this->rbox)->pushText('我关注的事件');
        $this->button($this->rbox)->pushText('需我审批的事件');
        $this->button($this->rbox)->pushText('我发起的事件');
        $this->button($this->rbox)->pushText('事件统计');
        $this->button($this->rbox)->pushText('创建类型事件');

        $pbox = $this->div($this->rbox, ['style' => 'border:1px solid red;'])->pushText('经常性事件');
        $this->h3($pbox, ['style' => 'background-color:yellow;'])->pushText('签到/考勤');
        $this->h3($pbox, ['style' => 'background-color:yellow;'])->pushText('定期提醒');

        $this->p($this->rbox)->pushText('最近事件进度');
        $this->p($this->rbox)->pushText('事件1：进度50%，由 XXXX 于xxxx-xx-xx xx:xx:xx更新');
        $this->p($this->rbox)->pushText('事件2：进度60%，由 XXXX 于xxxx-xx-xx xx:xx:xx更新');
        $this->p($this->rbox)->pushText('事件3：进度30%，由 XXXX 于xxxx-xx-xx xx:xx:xx更新');

    }

}
