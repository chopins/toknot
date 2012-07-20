<?php
class call extends X {
    public function Pindex() {
        $act = $this->R->P->act;
        $this->exit_json(1,'OK',$act);
    }
}
