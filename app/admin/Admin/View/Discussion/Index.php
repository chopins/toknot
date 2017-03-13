<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Discussion;

use Admin\View\Lib\BaseView;

class Index extends BaseView {

    public function contanier() {
        $this->button($this->rbox)->pushText('发起讨论');
        $this->button($this->rbox)->pushText('我关注的项目');
        $this->button($this->rbox)->pushText('讨论列表');
    }

}
