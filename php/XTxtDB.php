<?php
/**
 * Toknot
 *
 * XTxtDB class
 *
 * PHP version 5.3
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release 2.2
 *
 */

exists_frame();

/**
 * XTxtDB 
 * key/value database classes with text, The data stroage type is accordance with the linear
 * and get key or set key is seek text file form file start to file end
 * 
 * @author chopins xiao <chopins.xiao@gmail.com>
 */
class XTxtDB {

    /**
     * db 
     * the database file connect resource
     * 
     * @var mixed
     * @access private
     */
    private $db = null;

    /**
     * block_size 
     * the one key/value use stroage size
     * 
     * @var float
     * @access private
     */
    private $block_size = 1024;

    /**
     * block_data_size 
     * one key of data part use stroage size
     * 
     * @var float
     * @access private
     */
    private $block_data_size = 0x400;

    /**
     * last_key 
     * lastest use key of name
     * 
     * @var string
     * @access private
     */
    private $last_key = null;
    /**
     * db_name 
     * 
     * @var string
     * @access private
     */
    private $db_name = null;
    /**
     * db_dir 
     * 
     * @var string
     * @access private
     */
    private $db_dir = null;
    

    /**
     * space_flag 
     * key and value separates of bit if value use single block is space otherwise is number
     *
     * @var string
     * @access private
     */
    private $space_flag = ' ';
    /**
     * db_charset 
     * 
     * @var string
     * @access private
     */
    private $db_charset = 'utf8';

    const KEY_SIZE = 0x10;
    const BLOCK_MIN_SIZE = 48;
    const FLAG_SIZE = 1;
    const DB_TYPE = 'XToknotTxtDB';
    const FIND_START = 1;
    const FIND_END = 2;
    const ORDER_ASC = 1;
    const ORDER_DESC = 2;
    const BLOCK_SEP = "\0x1E";
    const BLOCK_INSIDE_SEP = "\0x1F";
    /**
     * __construct 
     * 
     * @access public
     * @return void
     */
    public function __construct() {}

    /**
     * set_db_dir 
     * set the data file in directory 
     *
     * @param mixed $path 
     * @access public
     * @return void
     */
    public function set_db_dir($path) {
        $this->db_dir = $path;
        xmkdir($this->db_dir);
    }

    /**
     * set_block_size 
     * set block size
     *
     * @param mixed $size 
     * @access public
     * @return void
     */
    public function set_block_size($size) {
        $size = (int)$size;
        if($size < self::BLOCK_MIN_SIZE) return false;
        $this->block_size = $size;
    }

    /**
     * get_block_size 
     * 
     * @access public
     * @return void
     */
    public function get_block_size() {
        return $this->block_size;
    }
    /**
     * get_db_charset 
     * 
     * @access public
     * @return void
     */
    public function get_db_charset() {
        return $this->db_charset;
    }
    /**
     * get_db_path 
     * 
     * @access public
     * @return void
     */
    public function get_db_path() {
        return "{$this->db_dir}/{$this->db_name}.db";
    }
    /**
     * __destruct 
     * 
     * @access public
     * @return void
     */
    public function __destruct() {
        if(is_resource($this->db)) {
            fclose($this->db);
            $this->db = null;
        }
    }
    /**
     * create 
     * 
     * @param mixed $file 
     * @access public
     * @return void
     */
    public function create($file) {
        $this->set_block_data_size();
        $file_path = "{$this->db_dir}/{$file}.db";
        if(file_exists($file_path)) return false;
        $this->db = fopen($file_path,'x+b');
        $this->write_db_info();
        $this->db_name = $file;
        return;
    }
    /**
     * check_key_type 
     * 
     * @param mixed $key 
     * @access private
     * @return void
     */
    private function check_key_type($key) {
        $this->last_key = $key;
        if(!is_int($key) && !is_string($key)) {
            throw new XException($key .' is not string or integer for key');
            return false;
        }
        return true;
    }
    /**
     * db_type 
     * 
     * @access private
     * @return void
     */
    private function db_type() {
        return pack('H*',dechex(crc32(self::DB_TYPE)));
    }
    /**
     * write_db_info 
     * 
     * @access private
     * @return void
     */
    private function write_db_info() {
        $db_type = $this->db_type();
        $line = "{$db_type}BT=".self::BLOCK_SEP."&SP=0&BS={$this->block_size}&BDS={$this->block_data_size}&CS={$this->db_charset}\r\n\r\n";
        $line = pack("a{$this->block_size}",$line);
        fwrite($this->db, $line);
    }
    /**
     * get_db_info 
     * 
     * @access private
     * @return void
     */
    private function get_db_info() {
        fseek($this->db,0);
        $db_type = $this->db_type();
        $type_len = strlen($db_type);
        $file_type = fread($this->db,$type_len);
        if($db_type != $file_type) throw new XException('unknown db file format');
        $info = trim(fread($this->db, $this->block_size - $type_len));
        $field = strtok($info, '=');
        while($field) {
            switch($field) {
                case 'BS':
                    $this->block_size = strtok('&');
                break;
                case 'BDS':
                    $this->block_data_size = strtok('&');
                break;
                case 'CS':
                    $this->db_charset = strtok('&');
                break;
                default:
                break;
            }
            $field = strtok('=');
        }
    }
    /**
     * set_block_data_size 
     * 
     * @access private
     * @return void
     */
    private function set_block_data_size() {
        $this->block_data_size = $this->block_size - self::KEY_SIZE 
            - self::FLAG_SIZE - 1;
    }
    /**
     * open 
     * 
     * @param mixed $file 
     * @access public
     * @return void
     */
    public function open($file) {
        $file_path = "{$this->db_dir}/{$file}.db";
        if(!file_exists($file_path)) return $this->create($file);
        $this->db = fopen($file_path,'r+b');
        $this->get_db_info();
        $this->db_name = $file;
    }

    /**
     * add 
     * add new key
     * 
     * @param string $key 
     * @param mixed $value
     * @param int $expire
     * @access public
     * @return int
     */
    public function add($key, $value, $expire =0) {
        $this->check_key_type($key);
        if(!is_resource($this->db)) return false;
        fseek($this->db, $this->block_size, SEEK_SET);
        $value = serialize(array('k'=>$key,'v'=>$value));
        $len = strlen($value);
        $key = md5($key, true);
        flock($this->db, LOCK_SH);
        if($len > $this->block_data_size) {
            $r = $this->multi_line_add($key, $value, $expire);
        } else {
            $r = $this->single_line_add($key, $value, $expire);
        }
        flock($this->db, LOCK_UN);
        return $r ? $len : $r;
    }

    /**
     * key_exists 
     * check key whether is exists 
     *
     * @param mixed $key 
     * @access public
     * @return void
     */
    public function key_exists($key) {
        $this->check_key_type($key);
        fseek($this->db,$this->block_size, SEEK_SET);
        $key = md5($key, true);
        $this->line_start();
        while(!feof($this->db)) {
            $fkey = fread($this->db,self::KEY_SIZE);
            fseek($this->db, $this->block_size - self::KEY_SIZE, SEEK_CUR);
            if($key == $fkey) {
                return true;
            }
            if(false ===$this->next_line()) break;
        }
        return false;
    }

    /**
     * del 
     * 
     * @param string $key 
     * @access public
     * @return void
     */
    public function del($key) { 
        $this->check_key_type($key);
        if(!is_resource($this->db)) return false;
        fseek($this->db,$this->block_size, SEEK_SET);
        $pack_empty = $this->pack_empty_line();
        while(!feof($this->db)) {
            $fkey = fread($this->db , self::KEY_SIZE);
            if($key == $fkey) {
                $this->line_start();
                fwrite($this->db,$pack_empty);
                continue;
            }
            if(false ===$this->next_line()) break;
        }
        return true;
    }
    /**
     * multi_line_add 
     * 
     * @param string $key 
     * @param string $data 
     * @param int $expire 
     * @access private
     * @return void
     */
    private function multi_line_add($key , $data, $expire) {
        $chunk_arr = chunk_split($data, $this->block_data_size);
        $flag = count($chunk_arr);
        $empty_line = array();
        while(!feof($this->db)) {
            $fkey = fread($this->db,self::KEY_SIZE);
            if($key == $fkey) {
                throw new XException("key {$this->last_key} is exists");
                return false;
            }
            if(empty($key)) {
                $empty_line[] = ftell($this->db);
                continue;
            }
            if(false ===$this->next_line()) break;
        }
        if(!empty($empty_line)) {
            foreach($empty_line as $s) {
                fseek($this->db, $s - self::KEY_SIZE, SEEK_SET);
                $chunk = pack("a{$this->block_data_size}", array_shift($chunk_arr));
                fwrite($this->db,"{$key}{$flag}{$chunk}".self::BLOCK_SEP, $this->block_size);
            }
        }
        fseek($this->db,1,SEEK_END);
        foreach($chunk_arr as $chunk) {
            $chunk = pack("a{$this->block_data_size}", $chunk);
            fwrite($this->db,"{$key}{$flag}{$chunk}".self::BLOCK_SEP, $this->block_size);
        }
        return true;
    }
    /**
     * single_line_add 
     * 
     * @param string $key 
     * @param string $data 
     * @access private
     * @return boolean
     */
    private function single_line_add($key , $data) {
        $empty_line = null;
        while(!feof($this->db)) {
            $fkey = fread($this->db,self::KEY_SIZE);
            if($key == $fkey) {
                throw new XException("key {$this->last_key} is exists");
                return false;
            }
            if(empty($key) && empty($empty_line)) {
                $empty_line = ftell($this->db);
            }
            if(false ===$this->next_line()) break;
        }
        if(!empty($empty_line)) {
            fseek($this->db, $empty_line - self::KEY_SIZE, SEEK_SET);
        }
        $this->line_start();
        $data = pack("a{$this->block_data_size}",$data);
        fwrite($this->db,"{$key}1{$data}".self::BLOCK_SEP, $this->block_size);
        return true;
    }
    /**
     * next_line 
     * 
     * @access private
     * @return boolean
     */
    private function next_line() {
        if(feof($this->db)) return false;
        $next_offset = $this->block_size - (ftell($this->db) % $this->block_size);
        fseek($this->db, $next_offset, SEEK_CUR);
        return true;
    }

    /**
     * line_start 
     * to current key block start
     * 
     * @access private
     * @return void
     */
    private function line_start() {
        $current = ftell($this->db);
        $seek_line_start = $current % $this->block_size;
        if($current % $this->block_size != 0) {
            fseek($this->db, $current - $seek_line_start, SEEK_SET);
        }
    }
    /**
     * pack_empty_line 
     * 
     * @access private
     * @return void
     */
    private function pack_empty_line() {
        $len = $this->block_size - strlen(self::BLOCK_SEP);
        return pack("a{$len}",''). self::BLOCK_SEP;
    }
    /**
     * multi_line_set 
     * 
     * @param string $key 
     * @param string $data 
     * @access private
     * @return boolean
     */
    private function multi_line_set($key , $data) {
        $chunk_arr = chunk_split($data, $this->block_data_size);
        $pack_empty = $this->pack_empty_line();
        $flag = count($chunk_arr);
        $empty_line = array();
        while(!feof($this->db)) {
            $fkey = fread($this->db,self::KEY_SIZE);
            if($fkey == $key) {
                if(!empty($chunk_arr)) {
                    $data = pack("a{$this->block_data_size}",array_shift($chunk_arr));
                    fwrite($this->db,"{$flag}{$data}", $this->block_data_size+1);
                    continue;
                } else {
                    $this->line_start();
                    fwrite($this->db,$pack_empty);
                    continue;
                }
            }
            if(empty($fkey)) {
                $empty_line[] = ftell($this->db);
            }
            if(false ===$this->next_line()) break;
        }
        if(!empty($chunk_arr)) {
            if(!empty($empty_line)) {
                foreach($empty_line as $seek) {
                    fseek($this->db, $seek- self::KEY_SIZE, SEEK_SET);
                    $chunk = pack("a{$this->block_data_size}", array_shift($chunk_arr));
                    fwrite($this->db,"{$key}{$flag}{$chunk}".self::BLOCK_SEP, $this->block_size);
                }
            }
            if(!empty($chunk_arr)) {
                fseek($this->db,1,SEEK_END);
                foreach($chunk_arr as $chunk) {
                    $chunk = pack("a{$this->block_data_size}", $chunk);
                    fwrite($this->db,"{$key}{$flag}{$chunk}".self::BLOCK_SEP, $this->block_size);
                }
            }
        }
        return true;
    }
    /**
     * single_line_set 
     * 
     * @param string $key 
     * @param string $data 
     * @access private
     * @return boolean
     */
    private function single_line_set($key, $data) {
        $pack_empty = $this->pack_empty_line();
        $write_complete = false;
        while(!feof($this->db)) {
            $fkey = fread($this->db,self::KEY_SIZE);
            if($key == $fkey) {
                $flag = fread($this->db, self::FLAG_SIZE);
                if($flag === $this->space_flag || $flag == 0) {
                    $data = pack("a{$this->block_data_size}",$data);
                    fseek($this->db , -1 , SEEK_CUR);
                    fwrite($this->db,"1{$data}", $this->block_data_size+1);
                    $write_complete = false;
                    continue;
                } else {
                    $this->line_start();
                    fwrite($this->db,$pack_empty);
                    continue;
                }
            }
            if(false === $this->next_line()) break;
        }
        if($write_complete === false) {
            $data = pack("a{$this->block_data_size}",$data);
            $this->line_start();
            fwrite($this->db,"{$key}1{$data}", $this->block_data_size+1);
        }
        return true;
    }
    /**
     * set 
     * add new key if key not exists , update key if key is exists;
     * 
     * @param string $key 
     * @param string $value 
     * @access public
     * @return boolean
     */
    public function set($key, $value) {
        $this->check_key_type($key);
        if(!is_resource($this->db)) return false;
        fseek($this->db,$this->block_size, SEEK_SET);
        $key = md5($key, true);
        $value = serialize(array('k'=>$key,'v'=>$value));
        $len = strlen($value);
        flock($this->db, LOCK_SH);
        if($len > $this->block_data_size) {
            $this->multi_line_set($key, $value);
        } else {
            $this->single_line_set($key, $value);
        }
        flock($this->db, LOCK_UN);
        return true;
    }
    /**
     * get 
     * get a key
     * 
     * @param string $key 
     * @access public
     * @return mixed
     */
    public function get($key) {
        $this->check_key_type($key);
        if(!is_resource($this->db)) return false;
        fseek($this->db,$this->block_size, SEEK_SET);
        $key = md5($key, true);
        $data = '';
        $count = 0;
        while(!feof($this->db)) {
            $fkey = fread($this->db, self::KEY_SIZE);
            if($fkey == $key) {
                $flag = fread($this->db, self::FLAG_SIZE);
                if($flag == 1) {
                    $data = trim(fread($this->db, $this->block_data_size));
                    fseek($this->db,1,SEEK_CUR);
                    break;
                } else if($flag > 1){
                    $data .= trim(fread($this->db, $this->block_data_size));
                    $count++;
                    fseek($this->db,1,SEEK_CUR);
                    if($count >= $flag) {
                        break;
                    }
                }
            } else {
                $this->next_line();
            }
        }
        if(empty($data)) return false;
        $data = unserialize($data);
        return $data['v'];
    }
    /**
     * compare_key 
     * 
     * @param array $return_data 
     * @param int $int 
     * @param string $comparison 
     * @access private
     * @return void
     */
    private function compare_key(&$return_data, $int, $comparison) {
        $data = trim(fread($this->db, $this->block_data_size));
        $data = unserialize($data);
        $k = $data['k'];
        if(is_numeric($k)) {
            $k = (int)$k;
            if($comparison == '>' && $k > $int) {
                $return_data[$data['k']] = $data['v'];
            } elseif($comparison == '<' && $k < $int) {
                $return_data[$data['k']] = $data['v'];
            }
        }
    }
    /**
     * key_greater_than 
     * 
     * @param int $int 
     * @param int $find 
     * @param int $order 
     * @access public
     * @return mixed
     */
    public function key_greater_than($int, $find = self::FIND_START, $order = self::ORDER_ASC) {
        return $this->key_compare_than($int, $find,$order,'>');
    }
    /**
     * key_less_than 
     * 
     * @param int $int 
     * @param int $find 
     * @param int $order 
     * @access public
     * @return mixed
     */
    public function key_less_than($int , $find = self::FIND_START, $order = self::ORDER_ASC) {
        return $this->key_compare_than($int, $find,$order,'<');
    }
    /**
     * key_compare_than 
     * 
     * @param int $int 
     * @param int $find 
     * @param int $order 
     * @param string $comparison 
     * @access private
     * @return mixed
     */
    private function key_compare_than($int, $find, $order, $comparison) {
        $int = (int)$int;
        $return_data = array();
        if(!is_resource($this->db)) return false;
        if($find == self::FIND_START) {
            fseek($this->db, $this->block_size, SEEK_SET);
            while(!feof($this->db)) {
                fseek($this->db, self::KEY_SIZE);
                $flag = fread($this->db, self::FLAG_SIZE);
                if($flag === $this->space_flag) {
                    $this->compare_key($return_data, $int, $comparison);
                }
                fseek($this->db, 1, SEEK_CUR);
            }
        } else if($find == self::FIND_END) {
            fseek($this->db, 0, SEEK_END);
            while(ftell($this->db) > 0) {
                $backward_pre_start = ($this->block_data_size + self::FLAG_SIZE + 1)  * -1;
                fseek($this->db, $backward);
                $flag = fread($this->db, self::FLAG_SIZE);
                if($flag === $this->space_flag) {
                    $this->compare_key($return_data, $int, $comparison);
                }
                $this->line_start();
            }
        }
        if($order == self::ORDER_ASC) {
            ksort($return_data, SORT_NUMERIC);
        } elseif($order == self::ORDER_DESC) {
            krsort($return_data, SORT_NUMERIC);
        }
        return $return_data;
    }
}
