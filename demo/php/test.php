<?php
class test extends X {
    public function gindex() {
        $this->T->name ='test';
        $this->T->type = 'html';
        $this->D->hello = 'hello world the index';
        $this->LM('job_user_data')->get_all_row();
    }
    public function pindex() {
    }
    public function gtest() {
        dump(dirname('./'));;
        $this->T->name ='test';
        $this->T->type = 'html';
        $this->D->hello = 'hello world, the test';
        #$this->LM('job_user_data')->get_all_row();
    }
}
