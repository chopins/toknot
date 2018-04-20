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
        $nodes = [];
        $nodes[] = $this->button()->pushText('我的项目');
        $nodes[] = $this->button()->pushText('我关注的项目');
        $nodes[] = $this->button()->pushText('创建项目');
        $nodes[] = $p = $this->div();
        $this->rbox->batchPush($nodes);
        $a = [];
        $a[] = $this->a(['href' => $this->route('project')])->pushText('项目1');
        $a[] = $this->a()->pushText('项目2');
        $a[] = $this->a()->pushText('项目3');
        $p->batchPush($a);
    }

}
