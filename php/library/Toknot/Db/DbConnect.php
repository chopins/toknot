<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Object;

final class DbConnect extends Object {
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

