<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

class KVDB {
    public $db_dir = '';
    private $db = '';
    private $db_idx = '';
    private $idx_max_size = 1048576; //1MB
    private $line_size = 1024;
    private $idx_block_size = 8;
    public $area = '';
    private $max_line = 65536;
    public function __construct($cfg) {
        $this->db_dir = $cfg->kvdb_dir;
    }
    public function create_db($name) {
        $dbfile = "{$this->db_dir}/{$name}.kvdb";
        $db_idx = "{$this->db_dir}/{$name}.kvdbi";
        $this->db = new SplFileObject($dbfile,'r+b');
        $this->db_idx = new SplFileObject($db_idx, 'r+b');
        $this->init_idx_db();
    }
    private function hkey($str_key) {
        $key = $this->idx_offset($str_key);
        return pack('H*',$key);
    }
    private function init_idx_db() {
        $file = pack("a{$this->idx_max_size}",'');
        $this->db_idx->fwrite($file);
    }
    private function idx_offset($str_key) {
        $int_hash = 5831;
        for($i=0;$i<32;$i++) {
            $int_hash = (int)((($int_hash <<5) + $int_hash) + ord($str_key[$i])) & 0x7fffffff;
        }
        $max_int = floor($this->idx_max_size / 8);
        $key = $int_hash % $max_int;
        return $key;
    }
    private function hline($line) {
        if($line >= self::$max_line) return false;
        $high = floor($line/ 256);
        $low = $line % 256;
        return pack('C2',$high,$low);
    }
    private function create_line($name,$value) {
        $size = $this->db->getSize();
        $this->db->fseek($size,SEEK_END);
        $name = $this->hkey($name);
        $apped_size = $this->line_size - 1;
        $area = pack("a{$apped_size}",$name)."$value\n";
        $this->db->fwrite($area);
    }
    private function line_seek($line) {
        if($line >= self::$max_line) return false;
        $seek = $this->line_size * $line;
        $this->db->fseek($seek, SEEK_SET);
    }
    public function add($key, $value) {
        if(strlen($value) > 1008) return false;
        $this->create_area($key, $value);
    }
    public function get($key) {
        $key = $this->hkey($key);
        while(!$this->db->eof()) {
            
        }
    }
}
