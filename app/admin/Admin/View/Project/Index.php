<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Project;

use Admin\View\Lib\BaseView;

class Index extends BaseView {

    public function contanier() {
        $this->button($this->rbox)->pushText('我的项目');
        $this->button($this->rbox)->pushText('我关注的项目');
        $this->button($this->rbox)->pushText('创建项目');
        $p = $this->div($this->rbox);

        $this->a($p, ['href' => $this->route('project')])->pushText('项目1');
        $this->a($p)->pushText('项目2');
        $this->a($p)->pushText('项目3');
    }

}
