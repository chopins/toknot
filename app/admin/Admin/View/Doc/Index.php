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
        $this->button($this->rbox)->pushText('我的文档');
        $this->button($this->rbox)->pushText('组织文档');
        $this->button($this->rbox)->pushText('项目文档');
        $this->button($this->rbox)->pushText('开放文档');
        $this->button($this->rbox)->pushText('事件文档');
    }

}
