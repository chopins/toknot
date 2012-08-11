<?php
/**
 * Toknot
 *
 * XDbConnect
 *
 * PHP version 5.3
 * 
 * @package DataBase
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release $id$
 */

exists_frame();
/**
 * XDba 
 * 
 * @uses XMySQLDba
 * @final
 * @package DataBase
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
final class XDbConnect extends XObject {
    private $db_instance = null;
    private $commit_data = null;
    protected function __construct() {}
    public static function singleton() {
        return parent::__singleton();
    }
    /**
     * create_instance
     * 
     * @param mixed $dbtype 
     * @param mixed $cfg   this is database configuration that not application config
     * @param mixed $idx 
     * @access public
     * @return void
     */
    public function create_instance($dbtype) {
        $dbtype = strtolower($dbtype);
        switch($dbtype) {
            case 'mysql':
                $this->db_instance = XMySQLConnect::singleton();
            break;
            case 'firebird':
                $local = 'db_firebird_dirname';
                $this->db_instance = new XFirebirdLocal($local);
            break;
            case 'txtdb':
                $this->db_instance = new XTxtDB();
            break;
        }
    }
    public function get_instance() {
        return $this->db_instance;
    }
    public function table($name) {
        $this->commit_data = array();
        $this->commit_data['table'] = $name;
    }
    public function convert_query() {
        $this->db_instance->fetch('sql');
    }
    public function execute() {
    }
    public function commit() {
        $this->convert_query();
        $this->execute();
        $this->commit_data = null;
    }
}

