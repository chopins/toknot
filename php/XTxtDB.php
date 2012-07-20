<?php
/**
 * Toknot
 *
 * XTxtDB class
 *
 * PHP version 5.3
 * @category phpframework
 * @package XPHPFramework
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release 2.2
 *
 */
exists_frame();
/**
 * key/value database classes with text, The data stroage type is accordance with the linear
 * and get key or set key is seek text file form file start to file end
 * 
 * @package XPHPFramework
 * @author chopins xiao <chopins.xiao@gmail.com>
 */

class XTxtDB {
    private $db = null;
    private $line_size = 1024;
    private $line_data_size = 0x400;
    private $lf = "\n";
    private $space_flag = ' ';
    private $db_charset = 'utf8';
    const KEY_SIZE = 0x10;
    const FLAG_SIZE = 1;
    const DB_TYPE = 'XFrameworkTxtDB';
    public function __construct($cfg = null) {
        if($cfg == null) $cfg = $GLOBALS['_CFG'];
        $this->db_dir = __X_APP_DATA_DIR__."/{$cfg->db_data}/{$cfg->txtdb_dir}";
        $this->space_flag = pack('C',32);
        $this->lf = pack('C',10);
    }
    public function set_line_size($size) {
        if(!is_int($size)) return false;
        $this->line_size = $size;
    }
    public function get_line_size() {
        return $this->line_size;
    }
    public function get_db_charset() {
        return $this->db_charset;
    }
    public function __destruct() {
        if(is_resource($this->db)) {
            fclose($this->db);
            $this->db = null;
        }
    }
    public function create($file) {
        $this->set_line_data_size();
        $file_path = "{$this->db_dir}/{$file}.db";
        if(file_exists($file_path)) return false;
        $this->db = fopen($file_path,'x+b');
        $this->write_db_info();
        return;
    }
    private function db_type() {
        return pack('H*',dechex(crc32(self::DB_TYPE)));
    }
    private function write_db_info() {
        $db_type = $this->db_type();
        $line = "{$db_type}LF={$this->lf}&SP={$this->space_flag}
                &LS={$this->line_size}&LDS={$this->line_data_size}&CS={$this->db_charset}\r\n\r\n";
        dump($this->line_size);
        $line = pack("a{$this->line_size}",$line);
        fwrite($this->db, $line);
    }
    private function get_db_info() {
        fseek($this->db,0);
        $db_type = $this->db_type();
        $type_len = strlen($db_type);
        $file_type = fread($this->db,$type_len);
        if($db_type != $file_type) throw new XException('unknown db file format');
        $info = trim(fread($this->db, $this->line_size - $type_len));
        $field = strtok($info, '=');
        while($field) {
            switch($field) {
                case 'LF':
                    $this->lf = strtok('&');
                break;
                case 'SP':
                    $this->space_flag = strtok('&');
                break;
                case 'LS':
                    $this->line_size = strtok('&');
                break;
                case 'LDS':
                    $this->line_data_size = strtok('&');
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
    private function set_line_data_size() {
        $this->line_data_size = $this->line_size - self::KEY_SIZE 
            - self::FLAG_SIZE - strlen($this->lf);
    }
    public function open($file) {
        $file_path = "{$this->db_dir}/{$file}.db";
        if(!file_exists($file_path)) return $this->create($file);
        $this->db = fopen($file_path,'r+b');
        $this->get_db_info();
    }
    public function add($key, array $array) {
        if(!is_resource($this->db)) return false;
        fseek($this->db, $this->line_size, SEEK_SET);
        $data = serialize($array);
        $len = strlen($data);
        $key = md5($key, true);
        if($len > $this->line_data_size) {
            $r = $this->multi_line_add($key, $data);
        } else {
            $r = $this->single_line_add($key, $data);
        }
        return $r ? $len : $r;
    }
    public function key_exists($key) {
        fseek($this->db,$this->line_size, SEEK_SET);
        $key = md5($key, true);
        $this->line_start();
        while(!feof($this->db)) {
            $fkey = fread($this->db,self::KEY_SIZE);
            fseek($this->db, $this->line_size - self::KEY_SIZE, SEEK_CUR);
            if($key == $fkey) {
                return true;
            }
            if(false ===$this->next_line()) break;
        }
        return false;
    }
    public function del($key) {
        if(!is_resource($this->db)) return false;
        fseek($this->db,$this->line_size, SEEK_SET);
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
    private function multi_line_add($key , $data) {
        $chunk_arr = chunk_split($data, $this->line_data_size);
        $flag = 0;
        $empty_line = array();
        while(!feof($this->db)) {
            $fkey = fread($this->db,self::KEY_SIZE);
            if($key == $fkey) {
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
                $chunk = pack("a{$this->line_data_size}", array_shift($chunk_arr));
                fwrite($this->db,"{$key}{$flag}{$chunk}{$this->lf}", $this->line_size);
                $flag ++;
            }
        }
        fseek($this->db,1,SEEK_END);
        foreach($chunk_arr as $chunk) {
            $chunk = pack("a{$this->line_data_size}", $chunk);
            fwrite($this->db,"{$key}{$flag}{$chunk}{$this->lf}", $this->line_size);
            $flag ++;
        }
        return true;
    }
    private function single_line_add($key , $data) {
        $empty_line = null;
        while(!feof($this->db)) {
            $fkey = fread($this->db,self::KEY_SIZE);
            if($key == $fkey) {
                throw new XException("key {$key} is exists");
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
        $data = pack("a{$this->line_data_size}",$data);
        fwrite($this->db,"{$key}{$this->space_flag}{$data}{$this->lf}", $this->line_size);
        return true;
    }
    public function next_line() {
        if(feof($this->db)) return false;
        $next_offset = $this->line_size - (ftell($this->db) % $this->line_size);
        fseek($this->db, $next_offset, SEEK_CUR);
        return true;
    }
    public function line_start() {
        $current = ftell($this->db);
        $seek_line_start = $current % $this->line_size;
        if($current % $this->line_size != 0) {
            fseek($this->db, $current - $seek_line_start, SEEK_SET);
        }
    }
    public function pack_empty_line() {
        $len = $this->line_size - strlen($this->lf);
        return pack("a{$len}",''). $this->lf;
    }
    private function multi_line_set($key , $data) {
        $chunk_arr = chunk_split($data, $this->line_data_size);
        $pack_empty = $this->pack_empty_line();
        $flag = 0;
        $empty_line = array();
        while(!feof($this->db)) {
            $fkey = fread($this->db,self::KEY_SIZE);
            if($fkey == $key) {
                if(!empty($chunk_arr)) {
                    $data = pack("a{$this->line_data_size}",array_shift($chunk_arr));
                    fwrite($this->db,"{$flag}{$data}", $this->line_data_size+1);
                    $flag ++;
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
                    $chunk = pack("a{$this->line_data_size}", array_shift($chunk_arr));
                    fwrite($this->db,"{$key}{$flag}{$chunk}{$this->lf}", $this->line_size);
                    $flag++;
                }
            }
            if(!empty($chunk_arr)) {
                fseek($this->db,1,SEEK_END);
                foreach($chunk_arr as $chunk) {
                    $chunk = pack("a{$this->line_data_size}", $chunk);
                    fwrite($this->db,"{$key}{$flag}{$chunk}{$this->lf}", $this->line_size);
                    $flag ++;
                }
            }
        }
        return true;
    }
    private function single_line_set($key, $data) {
        $pack_empty = $this->pack_empty_line();
        $write_complete = false;
        while(!feof($this->db)) {
            $fkey = fread($this->db,self::KEY_SIZE);
            if($key == $fkey) {
                $flag = fread($this->db, self::FLAG_SIZE);
                if($flag === $this->space_flag || $flag == 0) {
                    $data = pack("a{$this->line_data_size}",$data);
                    fseek($this->db , -1 , SEEK_CUR);
                    fwrite($this->db,"{$this->space_flag}{$data}", $this->line_data_size+1);
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
            $data = pack("a{$this->line_data_size}",$data);
            $this->line_start();
            fwrite($this->db,"{$key}{$this->space_flag}{$data}", $this->line_data_size+1);
        }
        return true;
    }
    public function set($key, array $array) {
        if(!is_resource($this->db)) return false;
        fseek($this->db,$this->line_size, SEEK_SET);
        $key = md5($key, true);
        $data = serialize($array);
        $len = strlen($data);
        if($len > $this->line_data_size) {
            $this->multi_line_set($key, $data);
        } else {
            $this->single_line_set($key, $data);
        }
        return true;
    }
    public function get($key) {
        if(!is_resource($this->db)) return false;
        fseek($this->db,$this->line_size, SEEK_SET);
        $key = md5($key, true);
        $data = '';
        while(!feof($this->db)) {
            $fkey = fread($this->db, self::KEY_SIZE);
            if($fkey == $key) {
                $flag = fread($this->db, self::FLAG_SIZE);
                if($flag === $this->space_flag) {
                    $data = trim(fread($this->db, $this->line_data_size));
                    $data = unserialize($data);
                    fseek($this->db,1,SEEK_CUR);
                    return $data;
                } else if($flag == 0){
                    $data = trim(fread($this->db, $this->line_data_size));
                } else {
                    $data .= trim(fread($this->db, $this->line_data_size));
                }
                fseek($this->db,1,SEEK_CUR);
            }
        }
        if(empty($data)) return false;
        return unserialize($data);
    }
}
