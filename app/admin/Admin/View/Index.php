<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View;

use Admin\View\Lib\BaseView;

class Index extends BaseView {

    public $content;

    public function contanier() {
        $rightTable = $this->table(['class' => 'pure-table']);

        $rightTableThead = $this->thead();
        $rightTable->push($rightTableThead);
        $tr = $this->tr();
        $rightTableThead->push($tr);
        for ($i = 0; $i < 5; $i++) {
            $td = $this->td()->pushText("#$i Title");
            $tr->push($td);
        }


        $rightTableBody = $this->tbody();
        $rightTable->push($rightTableBody);
        for ($i = 0; $i < 5; $i++) {
            $bodyTr = $this->tr();
            $rightTableBody->push($bodyTr);
            for ($d = 0; $d < 5; $d++) {
                $td = $this->td()->pushText("#$i-$d Title");
                $bodyTr->push($td);
            }
        }
        $this->rbox->push($rightTable);
    }

}
