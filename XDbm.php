<?php
exists_frame();
abstract class XDbm {
    public $db = null;
    public $main_table = null;
    public $limit = 20;
    public $start = 0;
    public $current_page = 1;
    public $page_num = 1;
    public $record_num = 0;
    protected $cfg;
	public $cache_file = '/data/cache/lib_exec_cache.dat';
    public function __construct() {
        $this->cfg = $GLOBALS['_CFG'];
        $this->db = new XDba();
    }
    public function page_count() {
        $this->page_num = ceil($this->record_num/$this->limit);
    }
    public function get_page() {
        if(isset($_GET['r'])) {
            $r = (int) $_GET['r'];
            if($r>0) $this->limit = $r;
        }
        if(isset($_GET['p'])) {
            $page = (int) $_GET['p'] >=1 ? (int)$_GET['p']:1;
            $this->current_page = $page;
            $this->start = ($page -1) * $this->limit;
        }
    }
	public function get_data_cache($n) {
        if(file_exists(__X_APP_ROOT__ . $this->cache_file)) {
            $data = unserialize(file_get_contents(__X_APP_ROOT__ . $this->cache_file));
            if($n == 'data_cache_update_flag') {
                return empty($data['data_cache_update_flag']) ? false : $data['data_cache_update_flag'];
            }
            if(isset($data[$n])) {
                if($data['data_cache_update_flag'][$n] == true) {
                    return false;
                }
                return $data[$n];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    public function update_data_cache($n) {
        $expire = $this->get_data_cache('data_cache_update_flag');
        if($expire == false) $expire = array();
        $expire[$n] = true;
        $this->set_data_cache('data_cache_update_flag',$expire);
    }
    public function set_data_cache($n,$data) {
        if(file_exists(__X_APP_ROOT__ . $this->cache_file)) {
            $cd = unserialize(file_get_contents(__X_APP_ROOT__ . $this->cache_file));
            $cd[$n] = $data;
            $cd['data_cache_update_flag'][$n] = false;
            file_put_contents(__X_APP_ROOT__ . $this->cache_file, serialize($cd));
        } else {
            file_put_contents(__X_APP_ROOT__ . $this->cache_file, serialize(array($n=>$data,'data_cache_update_flag'=>array($n=>false))));
        }
    }
}
