<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Doc;

use Admin\View\Lib\BaseView;

class Index extends BaseView {

    public function contanier() {
        $nodes = [];
        $nodes[] = $this->button()->pushText('我的文档');
        $nodes[] = $this->button()->pushText('组织文档');
        $nodes[] = $this->button()->pushText('项目文档');
        $nodes[] = $this->button()->pushText('开放文档');
        $nodes[] = $this->button()->pushText('事件文档');
        $this->rbox->batchPush($nodes);
    }

}
