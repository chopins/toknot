<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

class MySQLDataCopy {
    public $sys_tmp;
    public $s_link;
    public $t_link;
    public $charset;
    public function __construct() {
        $this->sys_tmp = sys_get_temp_dir();
        if(!is_writable($this->sys_tmp)) {
            die("Temp directory({$this->sys_tmp}) can not be writeable\r\n");
        }
        $this->sys_tmp = "{$this->sys_tmp}/XMySQLDataCopyTmpDir_".md5(time());
        mkdir($this->sys_tmp);

        $option_list = $this->get_options();
        $this->s_link = mysql_connect($option_list['source_host'].':'.$option_list['source_host_port'],
                      $option_list['source_user'],$option_list['source_pass']);
        if(!$this->s_link) {
            die("Can not connect source database host\r\n");
        }

        $this->t_link = mysql_connect($option_list['target_host'].':'.$option_list['target_host_port'],
                             $option_list['target_user'],$option_list['target_pass']);
        if(!$this->t_link) {
            die("Can not connect target database host\r\n");
        }

        $this->dump($option_list);
    }
    public function dump($option_list) {
        $re = mysql_query("USE `{$option_list['source_db_name']}`",$this->s_link);
        if(!$re) die("can not use database\r\n");

        $re = mysql_query("SHOW CREATE DATABASE `{$option_list['source_db_name']}`",$this->s_link);
        list(,$c) = mysql_fetch_row($re);
        $c = rtrim($c,'*/');
        list(,$charset) = explode('CHARACTER SET',trim($c));
        $charset = trim($charset);
        mysql_query("SET NAMES '$charset'",$this->s_link);
        $this->charset = $charset;

        $table_list = $this->get_all("show tables", $this->s_link);
        foreach($table_list as $table_name) {
            mysql_query("SELECT * INTO OUTFILE '{$this->sys_tmp}/{$table_name}' FROM `$table_name`", $this->s_link);
        }
    }
    public function load($option_list) {
        $dir = opendir($this->sys_tmp);
        while(false === ($f = readdir($dir))) {
            if($f == '.' || $f == '..') continue;
            $path = "{$this->sys_tmp}/$f";
            mysql_query("LOAD DATA INFILE '$path' INTO TABLE `$f`", $this->t_link);
        }
    }
    public function get_all($sql, $link) {
        $re = mysql_query($sql, $link);
        $all_list = array();
        while($row = mysql_fetch_row($re)) {
            $all_list[] = $row[0];
        }
        return $all_list;
    }
    public function get_options() {
        $option_list = array();
        $option_list_lang = array();
        $option_list_lang['source_host'] = 'Enter source database host ip (default:localhost) :';
        $option_list_lang['source_host_port'] = 'Enter source database host port (default:3306) :';
        $option_list_lang['source_db_name'] = 'Enter Source Database name,can not be empty:';
        $option_list_lang['source_user'] = 'Enter source database username (default:'.get_current_user().') :';
        $option_list_lang['source_pass'] = 'Enter Source Database password of user (default is empty):';
        $option_list_lang['target_host'] = 'Enter target Database host ip (default:localhost):';
        $option_list_lang['target_host_port'] = 'Enter target database host port (default:3306):';
        $option_list_lang['target_db_name'] = 'Enter target Database name, can not be empty:';
        $option_list_lang['target_user'] = 'Enter target Database username (default:'.get_current_user().'):';
        $option_list_lang['target_pass'] = 'Enter target Database password of user (default is empty):';
        $stty_g = exec('stty -g', $output);
        foreach($option_list_lang as $key => $lang) {
            echo "$lang";
            $line = '';
            if($key == 'source_pass' || $key == 'target_pass') {
                system('stty -echo');
                $line = trim(fgets(STDIN));
                system("stty $stty_g");
            } else {
                $line = trim(fgets(STDIN));
            }
            $option_list[$key] = $line;
            switch($key) {
                case 'source_host':
                case 'target_host':
                    if(empty($line)) $option_list[$key] = 'localhost';
                break;
                case 'source_host_port':
                case 'target_host_port':
                    if(empty($line)) $option_list[$key] = '3306';
                case 'source_user':
                case 'target_user':
                    if(empty($line)) $option_list[$key] = get_current_user();
                break;
                case 'source_db_name':
                case 'target_db_name':
                    if(empty($line)) {
                        $option_list[$key] = $this->required_fields($lang);        
                    }
                break;
                default:
                echo "\n";
                break;
            }
        }
        fclose(STDIN);
        return $option_list;
    }
    private function required_fields($lang) {
        while(true) {
            echo "Need $lang";
            $line = trim(fgets(STDIN));
            if(!empty($line)) {
                return $line;
            }
        }
    }
}


return new XMySQLDataCopy();
