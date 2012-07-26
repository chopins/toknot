<?php
class XFirebirdLocal {
    public $dbpath = '';
    public $con = null;
    public $cfg = null;
    public function __construct($cfg_idx = 'firebird') {
        if(!file_exists('ibase_connect')) {
            throw new XException('Firebird extension is not load, Please install Firebird/interbase extension');
        }
        $this->cfg = $GLOBALS['_CFG']->db[$cfg_idx];
    }
    public function set_db_path($path) {
        $this->dbpath = $path;
    }
    public function connect($dbname,$username,$pass, $charset) {
        $dbname = $this->dbpath.'/'.$dbname;
        $this->con = ibase_connect($dbname, $username, $pass,$charset);
    }
    public function create_database($dbname) {
        $dbname = $this->dbpath.'/'.$dbname;
        $re = ibase_query(null,"CREATE DATABASE '$dbname'");
        return $re;
    }
    public function push($array_sql) {
        $trans = ibase_trans(IBASE_DEFAULT, $this->con);
        foreach($array_sql as $idx =>$sql) {
            $pre_sql = ibase_query($trans, $sql);
            if(!$pre_sql) {
                ibase_rollback($trans);
                return $idx;
            }
        }
        ibase_commit($trans);
        return true;
    }
}
