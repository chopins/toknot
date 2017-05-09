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
        $nodes = [];
        $nodes[] = $this->button()->pushText('发起讨论');
        $nodes[] = $this->button()->pushText('我关注的项目');
        $nodes[] = $this->button()->pushText('讨论列表');
        $this->rbox->batchPush($nodes);
    }

}
?>


