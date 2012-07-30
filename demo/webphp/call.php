<?php
class call extends X {
    public function Pindex() {
        $act = $this->R->P->act->value;
        $func = basename($act);
        if(empty($func)) $func = 'index';
        $func = 'G'.$func;
        $view_class = dirname($act);
        if(empty($view_class) || $view_class == '/') $view_class = 'index';
        $this->CV($view_class)->$func();
        //$this->exit_json(1,'OK',$act);
    }
}
