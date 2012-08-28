<?php
/**
 * Toknot
 * XDbm
 *
 * PHP version 5.3
 * 
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
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
abstract class XDataModel extends XObject {
    /**
     * cache_file 
     * 
     * @var mixed
     * @access public
     */
	public $cache_file = null;
    /**
     * text_data_dir 
     * 
     * @var mixed
     * @access public
     */
    public $text_data_dir = null;
    /**
     * connect_pool 
     * 
     * @var array
     * @access private
     */
    private $connect_pool = array();
    /**
     * _CFG 
     * application of all configuration object 
     *
     * @var stdClass
     * @access protected
     */
    protected $_CFG = null;

    /**
     * singleton 
     * 
     * @static
     * @final
     * @access public
     * @return XDataModel
     */
    final public static function singleton() {
        return parent::__singleton();
    }
    /**
     * __construct 
     * 
     * @final
     * @access protected
     * @return void
     */
    final protected function __construct() {
        $this->_CFG = XConfig::CFG();
        $this->cache_file = __X_APP_DATA_DIR__."/{$this->_CFG->app->data_cache}/{$this->_CFG->app->cache_file}";
        $this->db_data_path = __X_APP_DATA_DIR__."/{$this->_CFG->app->db_data}";
        $this->text_data_dir = "{$this->db_data_path}/{$this->_CFG->text->data_dirname}";
        $this->DB = new stdClass;
        if(method_exists($this,'auto_conf')) {
            $this->auto_conf();
        }
    }
    /**
     * DB 
     * 
     * @param mixed $table_name 
     * @final
     * @access protected
     * @return void
     */
    final protected function DB($table_name) {
        if($table_name instanceof XDBConf) {
            return $this->connect_database($table_name);
        }
        if(isset($this->connect_pool[$table_name])) {
            return $this->connect_pool[$table_name];
        }
        throw new XException("database connect instance of $table_name is not exists");
    }

    /**
     * use_conf 
     * use one config of item form application config file
     * 
     * @param string $key   the key is use database name 
     * @param mixed $id     one kind of database has many group config and it one index 
     * @final
     * @access public
     * @return XDBConf
     */
    final public function use_conf($key, $id) {
        $conf_ins = new XDBConf();
        switch(strtolower($key)) {
            case 'mysql':
                $conf_ins->dbtype = 'mysql';
                $conf_ins->dbhost = $this->_CFG->$key->$id->host;
                $conf_ins->dbuser = $this->_CFG->$key->$id->user;
                $conf_ins->dbname = $this->_CFG->$key->$id->dbname;
                $conf_ins->dbpass = $this->_CFG->$key->$id->password;
                $conf_ins->dbport = $this->_CFG->$key->$id->port;
                $conf_ins->pconnect = $this->_CFG->$key->$id->pconnect;
            return $conf_ins;
            case 'firebird':
                $conf_ins->dbtype = 'firebird';
                $path = $this->set_db_path($this->_CFG->db_firebird_dirname);
                $conf_ins->dbhost = $path;
                $conf_ins->dbname = $this->_CFG->$key->name;
                return $conf_ins;
            case 'txtdb':
                $conf_ins->dbtype = 'txtdb';
                $conf_ins->dbhost = $this->set_db_path($this->_CFG->$key->data_dirname);
                $conf_ins->dbname = $this->_CFG->$key->$id->name;
                if(isset($this->_CFG->$key->$id->block_size)) {
                    $conf_ins->block_size = $this->_CFG->$key->$id->block_size;
                }
            return $conf_ins;
            case 'txtkvdb':
            break;
        }
    }

    /**
     * connect_database 
     * use one XBDConf config instance connect one database
     * 
     * @param XDBConf $conf_ins 
     * @final
     * @access public
     * @return void
     */
    final public function connect_database(XDBConf $conf_ins) {
        $dbc = XDbConnect :: singleton();
        $dbc->create_instance($conf_ins->dbtype);
        $dbname = $conf_ins->dbname;
        $this->connect_pool[$dbname] = $dbc->get_instance();
        $this->select_db($conf_ins);
    }

    /**
     * select_db 
     * select one database
     * 
     * @param XDBConf $conf_ins 
     * @final
     * @access private
     * @return void
     */
    final private function select_db(XDBConf $conf_ins) {
        $dbname = $conf_ins->dbname;
        switch(strtolower($conf_ins->dbtype)) {
            case 'mysql':
                $this->connect_pool[$dbname]->connect($conf_ins->host,$conf_ins->user,$conf_ins->pass);
                $this->connect_pool[$dbname]->select_db($conf_ins->dbname);
            case 'firebird':
                $this->connect_pool[$dbname]->set_db_path($conf_ins->dbhost);
                $this->connect_pool[$dbname]->connect($conf_ins->dbname);
            case 'txtdb':
                $this->connect_pool[$dbname]->set_db_dir($conf_ins->dbhost);
                if(isset($conf_ins->block_size)) {
                    $this->connect_pool[$dbname]->set_block_size($conf_ins->block_size);
                }
                $this->connect_pool[$dbname]->open($conf_ins->dbname);
            break;
        }
    }

    /**
     * set_db_path 
     * set the database data file save directory if it is local database
     * 
     * @param string $dbtype_path 
     * @final
     * @access public
     * @return string
     */
    final public function set_db_path($dbtype_path) {
        return $this->db_data_path.'/'.$dbtype_path;
    }
    /**
     * write_text_data 
     * 
     * @param mixed $filename 
     * @param mixed $data 
     * @final
     * @access public
     * @return void
     */
    final public function write_text_data($filename, $data) {
        if(!is_dir($this->text_data_dir) || !is_writable($this->text_data_dir)) {
            return false;
        }
        $data = serialize($data);
        return file_put_contents("{$this->text_data_dir}/{$filename}.dat",$data);
    }
    /**
     * get_text_data 
     * 
     * @param mixed $filename 
     * @final
     * @access public
     * @return void
     */
    final public function get_text_data($filename) {
        $data_file = "{$this->text_data_dir}/{$filename}.dat";
        if(!file_exists($data_file) || !is_readable($data_file)) {
            return false;
        }
        $data = file_get_contents($data_file);
        return unserialize($data);
    }
    /**
     * rm_text_data 
     * 
     * @param mixed $filename 
     * @final
     * @access public
     * @return void
     */
    final public function rm_text_data($filename) {
        $data_file = "{$this->text_data_dir}/{$filename}.dat";
        if(file_exists($data_file)) {
            if(!is_writable($data_file)) return false;
            @unlink($data_file);
        }
        return true;
    }
    /**
     * page_count 
     * 
     * @access public
     * @return void
     */
    public function page_count() {
        $this->page_num = ceil($this->record_num/$this->limit);
    }
    /**
     * get_page 
     * 
     * @access public
     * @return void
     */
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
    /**
     * get_data_cache 
     * 
     * @param mixed $n 
     * @access public
     * @return mixed
     */
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
    /**
     * update_data_cache 
     * 
     * @param mixed $n 
     * @access public
     * @return void
     */
    public function update_data_cache($n) {
        $expire = $this->get_data_cache('data_cache_update_flag');
        if($expire == false) $expire = array();
        $expire[$n] = true;
        $this->set_data_cache('data_cache_update_flag',$expire);
    }
    /**
     * set_data_cache 
     * 
     * @param mixed $n 
     * @param mixed $data 
     * @access public
     * @return void
     */
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
