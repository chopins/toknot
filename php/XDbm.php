<?php
/**
 * Toknot
 * XDbm
 *
 * PHP version 5.3
 * 
 * @package XDataStruct
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release $id$
 */

exists_frame();
/**
 * XDbm 
 * This is data model class base class
 * 
 * @abstract
 * @package DataBase
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
abstract class XDbm {
    public $db = null;
    protected $cfg;
	public $cache_file = '/data/cache/lib_exec_cache.dat';
    static $db_instance = array();
    public $idx = 0;
    public $dbtype;
    public function __construct() {
    }
    public function init_database() {
        $xconfig = XConfig::singleton();
        $this->cfg = $xconfig->get_cfg();
        $dba = new XDba($this->dbtype,$this->cfg, $idx = 0);
        $this->db = $dba->get_instance();
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
