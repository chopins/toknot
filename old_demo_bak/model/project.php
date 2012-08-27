<?php
class projectModel extends XDataModel {
    public function save_project_list($data) {
        return $this->write_text_data('project_list',$data);
    }
    public function get_project_list() {
        return $this->get_text_data('project_list');
    }
}
