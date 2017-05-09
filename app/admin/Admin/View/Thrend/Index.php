<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Thrend;

use Admin\View\Lib\BaseView;

class Index extends BaseView {

    public function contanier() {
        $nodes = [];
        $nodes[] = $this->button()->pushText('事件动态');
        $nodes[] =$this->button()->pushText('短信动态');
        $nodes[] =$this->button()->pushText('项目动态');
        $nodes[] =$this->p()->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $nodes[] =$this->p()->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $nodes[] =$this->p()->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $nodes[] =$this->p()->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $nodes[] =$this->p()->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $nodes[] =$this->p()->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $nodes[] =$this->p()->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $nodes[] =$this->p()->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $nodes[] =$this->p()->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $nodes[] =$this->p()->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $nodes[] =$this->p()->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $nodes[] =$this->p()->pushText('分页 1 2 3 4 5 6 7 ... 100');
        $this->rbox->batchPush($nodes);
    }

}
