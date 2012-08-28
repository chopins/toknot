<?php
class project extends X{
    public function all() {
        $data['opreate_nav'] = array('所有项目|/project/all','添加新项目|/project/add');
        $data['table_data']['type'] = 'table';
        $prj_list = $this->LM('project')->get_project_list();
        if($prj_list) {
            $data['table_data']['status'] = 'AVAILABLE';
            $data['table_data']['data'] = $prj_list;
        } else {
            $data['table_data']['status'] = 'WAIT';
            $this->LM('opreateRecord')->append_record( array('cls'=>'project',
                                                     'func' => 'update_project_list',
                                                     'user'  => $this->R->S->username->value,
                                                     'type' => 'select'));
        }
        $this->exitJSON(1,'项目管理',$data);
        return true;
    }
    public function update_project_list() {
        $svn = new XSVNClient('svn.ini');
        $svn->use_confg(1);
        $prj_list = $svn->repos_list();
        $pl = $this->LM('project')->save_project_list($prj_list);    
        if($pm) {
            return true;
        } else {
            return false;
        }
    }
}
