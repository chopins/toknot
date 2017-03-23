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
        $this->title('index');
        $rightTable = $this->table($this->rbox, ['class' => 'pure-table']);
        $rightTableThead = $this->thead($rightTable);
        $tr = $this->tr($rightTableThead);
        for ($i = 0; $i < 5; $i++) {
            $this->td($tr)->pushText("#$i Title");
        }

        
        $rightTableBody = $this->tbody($rightTable);
        for ($i = 0; $i < 5; $i++) {
            $bodyTr = $this->tr($rightTableBody);
            for ($d = 0; $d < 5; $d++) {
                $this->td($bodyTr)->pushText("#$i-$d Title");
            }
        }
    }
    
   

}
