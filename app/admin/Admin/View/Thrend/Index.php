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
        $this->button($this->rbox)->pushText('事件动态');
        $this->button($this->rbox)->pushText('短信动态');
        $this->button($this->rbox)->pushText('项目动态');
        $this->p($this->rbox)->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $this->p($this->rbox)->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $this->p($this->rbox)->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $this->p($this->rbox)->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $this->p($this->rbox)->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $this->p($this->rbox)->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $this->p($this->rbox)->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $this->p($this->rbox)->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $this->p($this->rbox)->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $this->p($this->rbox)->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $this->p($this->rbox)->pushText('xxxx-xx-xx xx:xx:xx XXXXXXXXXX');
        $this->p($this->rbox)->pushText('分页 1 2 3 4 5 6 7 ... 100');
    }

}
