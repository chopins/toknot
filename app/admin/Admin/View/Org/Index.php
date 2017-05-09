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
        $nodes = [];
        $nodes[] = $this->button()->pushText('我的组织');
        $nodes[] = $this->button()->pushText('组织架构');
        $nodes[] = $this->button()->pushText('创建组织');
        $this->rbox->batchPush($nodes);
    }

}
