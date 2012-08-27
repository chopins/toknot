<?php
class opreateRecordModel extends XDataModel {
    public $log_file = '';
    private static $opreate_info_table = 'opreate_info_table';
    private static $opreate_table = 'opreate_table';
    private $last_pull_id = 0;
    public function auto_conf() {
        $opreate_table = $this->use_conf('txtdb','b');
        self::$opreate_table = $opreate_table->dbname;
        $this->connect_database($opreate_table);
        $this->opreate_info_table = $this->use_conf('txtdb','c');
        self::$opreate_info_table = $opreate_info_table->dbname;
        $this->connect_database($opreate_info_table);
    }
    public function get_db_file() {
        return $this->DB(self::$opreate_table)->get_db_path();
    }
    public function last_id() {
        $id = $this->DB(self::$opreate_info_table)->get('opreate_table_last_key');
        $this->DB(self::$opreate_info_table)->set('opreate_table_last_update', time());
        return $id;
    }
    public function update_last_id($id) {
        $this->DB(self::$opreate_info_table)->set('opreate_table_last_key',$id++);
    }
    public function last_exec_id() {
        return $this->DB(self::$opreate_info_table)->get('last_exec_id');
    }
    public function set_last_exec_id($id) {
        return $this->DB(self::$opreate_info_table)->set('last_exec_id',$id);
    }
    public function append_record($action) {
        $id = $this->last_id();
        $this->update_last_id($id);
        $this->DB(self::$opreate_table)->add($id,$action);
    }
    public function last_pull_id() {
        return $this->last_pull_id;
    }
    public function pull_modified($id) {
        $list = $this->DB(self::$opreate_table)->key_greater_than($id, XTxtDB::FIND_END);
        $this->last_pull_id = $this->last_id();
        return $list;
    }
}
