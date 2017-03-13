<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Org;

use Admin\View\Lib\BaseView;

class Index extends BaseView {

    public function contanier() {
        $this->button($this->rbox)->pushText('我的组织');
        $this->button($this->rbox)->pushText('组织架构');
        $this->button($this->rbox)->pushText('创建组织');
    }

}
