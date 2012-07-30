<?php
exists_frame();
function XAutoload($class_name) {
    require_once("$class_name.php");
}
function exists_frame() {
    if(!defined('__X_IN_FRAME__')) throw new XException('Constants IN_FRAME undefined',1,__FILE__,__LINE__);
}
function error2debug($errno, $errstr, $errfile, $errline,$errcontext) {
    if(isset($_ENV['__X_EXCEPTION_THROW_DISABEL__']) 
            && $_ENV['__X_EXCEPTION_THROW_DISABEL__']) return;
    if(__X_EXCEPTION_LEVEL__>0) {
        switch($errno) {
        case E_USER_NOTICE:
        case E_NOTICE:
        case @E_STRICT:
            echo new XException($errstr,$errno,$errfile,$errline,true);
        return;
        }
    }
    if(__X_EXCEPTION_LEVEL__ >1) {
        switch($errno) {
            case E_USER_WARNING:
            case E_WARNING:
            echo new XException($errstr,$errno,$errfile,$errline,true);
            return;
        }
    }
    throw new XException($errstr,$errno,$errfile,$errline,true);
}
function XExitAlert($str = '') {
    $error_arr = error_get_last();
    $_ENV['__X_OUT_BROWSER__'] = false;
    $ob_stat = ob_get_status();
    if(PHP_SAPI == 'cli' && !empty($ob_stat)) {
        ob_end_clean();
    }
    try {
        if(!empty($error_arr)) {
            throw new XException($error_arr['message'],$error_arr['type'],$error_arr['file'],$error_arr['line'],true);
        } elseif(PHP_SAPI == 'cli') {
            throw new XException('Please use $this->xexit() method instend exit of php language construct in your php script',E_USER_ERROR);
        }
    } catch(XException $e) {
        if(PHP_SAPI == 'cli') {
            if(isset($_ENV['__X_RUN_APP_COMPLETE__']) && $_ENV['__X_RUN_APP_COMPLETE__'] == false &&
                    isset($_ENV['__X_SERVER_INSTANCE__']) && isset($_ENV['__X_REQUEST_CONNECT__'])) {
                $_ENV['__X_SERVER_INSTANCE__']->exit_alert($e); 
            }
        } else {
            echo $e;
        }
    }
}
function get_file_mime($file) {
    if(function_exists('finfo_open')) {
        $fo = new finfo(FILEINFO_MIME);
        $type = $fo->file($file);
        return $type;
    } else if(file_exists('mime_content_type')){
        return mime_content_type($file);
    } else {
        $file = escapeshellcmd($file);
        $re = popen("file -ib $file", 'r');
        return fread($re, 1024);
    }
}
function x_notice($str) {
    echo "<b>$str</b>";
}
function conv_human_time($ts) {
    $suffix = substr($ts,-1,1);
    if(is_numeric($suffix)) return $ts;
    $suffix = strtolower($suffix);
    $number = (int)substr($ts,0,strlen($ts)-1);
    switch($suffix) {
        case 's':return $number;
        case 'm':return $number * 60;
        case 'h':return $number * 3600;
        case 'd':return $number * 86400;
        default: return false;
    }
}
function conv_human_byte($bs) {
    $suffix = substr($bs, -1,1);
    if(is_numeric($suffix)) return $suffix;
    $suffix = strtolower($suffix);
    $number = (int)substr($bs,0,strlen($bs)-1);
    switch($suffix) {
        case 'b':return $number;
        case 'k':return $number* 1024;
        case 'm':return $number * 1048576;
        case 'g':return $number * 1073741824;
        case 't':return (float)$number * 1099511627776;
        case 'p':return (float)$number * 1125899906842624;
    }
}
function time33($str) {
    $int_hash = 5831;
    for($i=0;$i<32;$i++) {
        $int_hash = ((($int_hash <<5) + $int_hash) + ord($str_key[$i])) & 0x7fffffff;
    }
    return $int_hash;
}
function xfrombin($str) {
    list($str) = unpack('H*',$str);
    
}
function xtobin($str) {
    $len = strlen($str);
    $hex_str = '';
    for($i=0;$i<$len;$i++) {
        $hex_str .= dechex(ord($str[$i]));
    }
    return pack('H*',$hex_str);
}

/*convert word to upper or lower by rand*/
function rand_strtoupper($str) {
    $len = strlen($str);
    $re = '';
    for($i=0;$i<$len;$i++) {
        $re .=  mt_rand(1,1000000)%2==0 ? strtoupper($str[$i]) : $str[$i];
    }
    return $re;
}

/*cutting str to param lenght and add suffix*/
function str_cutting($str,$len, $suffix='...') {
    $strlen = mb_strlen($str,'utf-8');
    if($strlen <= $len) {
        return $str;
    } else {
        return mb_substr($str,0, $len-3,'utf-8') . $suffix;
    }
}
/*get english char of sort number*/
function word_number($w) {
    return ord(strtolower($w)) -97;
}
/*get rand string*/
function randstr($min, $max=null, $num = false) {
    $word = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
    if(isset($_ENV['filter_confuse']) && $_ENV['filter_confuse'] == true) {
        $word = str_replace(array('0','o','O','i','I','1'),'',$word);
    }
    $filter_confuse = false;
    if($num) {
        $md5str = sha1(time(). randstr(5,6));
        $word = '';
        for($j=0;$j<40;$j++) {
            $word .=  is_numeric($md5str[$j]) ? $md5str[$j] : word_number($md5str[$j]);
        }
    }
    if($max == null) {
        $len = $min;
    } else {
       $len = mt_rand($min,$max);
    }
    $re = '';
    $strlen = strlen($word);
    for($i=0; $i<$len; $i++) {
        $idx = mt_rand(0, $strlen-1);
        $re .= $word[$idx];
    }
    return $re;
}
/*check word is not specify char*/
function is_word($word, $min=4,$max=10) {
    return preg_match("/^[A_Za-z0-9_\x7f-\xff]{{$min},{$max}}$/i",$word);
}
/*check number is china of moblie number*/
function is_moblie($tel) {
    return preg_match('/^1[358]{1}[0-9]{9}$/i',$tel);
}

/*check string is YYYY-mm-dd or YYY/mm/dd of farmat date*/
function is_day_str($day) {
    return preg_match('/^([12]{1}[0-9]{3})(\-|\/)(0[1-9]{1}|1[12]{1})(\-|\/)([12]{1}[0-9]|3[01]{1})/',$day);
}
/*check email address is vaild*/
function is_email($user_email) { 
    $chars = '/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}$/i';
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false)
        return preg_match($chars, $user_email) ? true : false;
    else
        return false;
}
/*convert " ' < > \ to html char*/
function conv_quotation($str) {
    $str = str_replace('"','&#39;',$str);
    $str = str_replace("'",'&#34;',$str);
    $str = str_replace("<",'&lt;', $str);
    $str = str_replace('>','&gt;',$str);
    return str_replace('\\','&#92;',$str);
}
/*check file mime is image*/
function is_image($file_mime) {
    list($type, $ext) = explode('/',$file_mime);
    return $type == 'image';
}
function ext($file_mime) {
    list($type, $ext)= explode('/',$file_mime);
    return $ext;
}
/*convert number to chinese number char*/
function num2chs($num) {
    $decimal = array('十','百','千','万','亿');
    $len = strlen($num);
    $chs_list = array('O','一','二','三','四','五','六','七','八','九');
    $chsnum = '';
    for($i=0;$i<$len;$i++) {
        $tail = $substr($num,-1,1);
        if($i<=4) {
            $dec = $decimal[$i-1];
            $str = $chs_list[$tail].$dec;
        }elseif(($i>4&&$i<8) || ($i>9||$i<14)) {
            $dec = $decimal[$i-5];
            $str = $chs_list[$tail].$dec;
        } else {
            $dec = $decimal[4];
            $str = $chs_list[$tail].$dec;
        }
        $chsnum = $str.$chsnum;
        $num = floor($num/10);
    }
    return $chs_list[$num];
}
/*convert array " ' < > \ to html char*/
function arr_conv(&$arr) {
    foreach($arr as &$v) {
        if(is_array($v)) {
            arr_conv($v);
            continue;
        }
        $v = conv_quotation($v);
    }
    return $arr;
}

/**
 * arr_in_arr 
 * check needle in arr
 * 
 * @param array $needle 
 * @param array $arr 
 * @access public
 * @return void
 */
function arr_in_arr(array $needle, array $arr) {
    $inter = array_intersect($needle,$arr);
    if(count($inter) != count($needle)) {
        return false;
    }
    $diff = array_diff($needle,$inter);
    return empty($diff);
}

/*check two array have same value*/
function arr_same($arr1,$arr2) {
    if(count($arr1) != count($arr2)) {
        return false;
    }
    $diff1 = array_diff($arr1, $arr2);
    $diff2 = array_diff($arr2,$arr1);
    if(empty($diff1) && empty($diff2)) {
        return true;
    } else {
        return false;
    }
}
/*get microtime*/
function getmtime() {
    list($m,$s) = explode(' ', microtime());
    return (float)$s+(float)$m;
}
/*get YYYY-mm-dd HH:ii:ss time*/
function get_timestmap($day) {
    $time_arr = explode(' ',$day);
    $date_arr = explode('-',$time_arr[0]);
    if(count($date_arr) != 3) {
        return false;
    }
    $hms = array();
    if(isset($time_arr[1])) {
        $hms = explode(':',$time_arr[1]);
    }
    $hms[0] = empty($hms[0]) ? 0 : $hms[0];
    $hms[1] = empty($hms[1]) ? 0 : $hms[1];
    $hms[2] = empty($hms[2]) ? 0 : $hms[2];
    return mktime($hms[0],$hms[1],$hms[2],$date_arr[1],$date_arr[2],$date_arr[0]);
}
/*add zero to number to length*/
function add_zero($num, $len) {
    $num_len = strlen($num);
    if($num_len == $len) return $num;
    $add_num = $len - $num_len;
    $zero_str = '';
    for($i=0;$i<$add_num;$i++) {
        $zero_str .='0';
    }
    return $zero_str . $num;
}
/*check string is real ip*/
function isip($ip) {
    return preg_match("/[\d\.]{7,15}/", $ip) === 0 ? false : true;
}
/*get user agent*/
function get_user_agent() {
    return isset($_SERVER['HTTP_USER_AGENT']) ?  $_SERVER['HTTP_USER_AGENT'] : null;
}
/*get current access user real ip*/
function get_uip() {
    $ip = 'unknown';
    if(!empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }else{
        $array = array('HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR');
        for($i=0;$i<3;$i++) {
            if(!empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown'))
            $ip = $re[$i];
            break;
        }
    }
    preg_match("/[\d\.]{7,15}/", $ip, $ipm);
    return isset($ipm[0]) ? $ipm[0] : 'unknown';
}
function class_redirect($class,$method='',$params='',$uri_mode) {
        $url = support_url_mode($class,$method,$params,$uri_mode);
        header("Location:$url");
        exit;
}
function byte_format($number) {
    if($number <1024) return $number;
    if($number >= 1024 && $number <1048576) return ceil($number/1024) .'K';
    if($number >= 1048576 && $number <1073741824) return round($number/1048576,2).'M';
    if($number >= 1073741824) return round($number/1073741824,2).'G';
    if($number >= 1073741824*1024) return round($number/1073741824/1024,2).'T';
    if($number >= 1073741824*1048576) return round($number/1073741824/1048576,2).'P';
}
function support_url_mode($path_uri, $params='') {
    $_CFG = XConfig::CFG();
    $domain = empty($_SERVER['HTTP_HOST']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
    switch($_CFG->uri_mode) {
        case 1:
            $index_file = basename($_SERVER['SCRIPT_FILENAME']);
            $url = "{$domain}/{$index_file}?a={$path_uri}&{$params}";
        break;
        case 2:
            $index_file = basename($_SERVER['SCRIPT_FILENAME']);
            $params = empty($params) ? '' : "?$params";
            $url = "{$domain}/{$index_file}{$path_uri}{$params}";
        break;
        case 3:
            $params = empty($params) ? '' : "?$params";
            $url= "{$domain}{$path_uri}{$params}";
        break;
        default:
            $params = empty($params) ? '' : '?'.$params;
            $url= "{$domain}{$path_uri}{$_CFG->url_file_suffix}$params";    
        break;
    }
    return "http://$url";
}
function gtime() {
    return gmmktime(date('H'),date('i'),date('s'),date('m'),date('d'),date('Y'));
}
function not_found() {
    xheader('Status:404 Not Found');
    echo '<h1>404 Not Found</h1>';
    die;
}
function xprint($buff) {
    if((isset($_ENV['__X_AJAX_REQUEST__']) && $_ENV['__X_AJAX_REQUEST__']) || 
           (PHP_SAPI == 'cli' && $_ENV['__X_OUT_BROWSER__'] == false)) {
        $html = strip_tags($buff);
    } else {
        $html = $buff;
    }
    return $html;
}
function printn($str) {
    $trace = debug_backtrace();
    ob_start('xprint');
    echo "<pre><span style=\"font-size:10px;\">------Print in {$trace[0]['file']} line {$trace[0]['line']}-------</span>
        \n$str<br /></pre>\n";
    ob_end_flush();
}
function dump($str) {
    $trace = debug_backtrace();
    ob_start('xprint');
    echo '<pre>';
    echo "<span style=\"font-size:10px;\">------Dump in {$trace[0]['file']} line {$trace[0]['line']}-------</span>\n";
    var_dump($str);
    echo '</pre>';
    ob_end_flush();
}
function print_rn($mix) {
    $trace = debug_backtrace();
    $k = ob_start('xprint');
    echo '<pre>';
    echo "<span style=\"font-size:10px;\">------Print_r in {$trace[0]['file']} line {$trace[0]['line']}-------</span>\n";
    print_r($mix);
    echo '</pre>';
    echo "<br />\n";
    ob_end_flush();
}

/*check object has property*/
function in_object($needle,$obj) {
    foreach($obj as $value) {
        if($value == $needle) return true;
    }
    return false;
}
function file_line($filename, $startLine, $endLine = null) {
    $file = new SplFileObject($filename);
    $file->seek($startLine - 1);
    if($endLine === null) return $file->current();
    $re = '';
    while(!$file->eof()) {
        $buff = $file->fgets();
        if($this->ftell() <= $endLine) {
            $re .= $buff;
        }
        $line++;
    }
    return $re;
}
function file_str_line($filename, $line_str) {
    $f = fopen($filename,'r');
    $re = '';
    $line = 1;
    $line_str = trim($line_str);
    while(!feof($f)) {
        $buff = trim(fgets($f));
        if($line_str == $buff) return $line;
        $line++;
    }
    return false;
}
/**
 * convert array to object
 *
 * @param array $array   needle convert array
 * @return object  converted object
 */
function array2object(array $array) {
    foreach($array as $key =>$value) {
        if(is_array($value)) {
            $array[$key] = array2object($value);
        }
    }
    return (object)$array;
}
function erase_path_tail_slash(&$path) {
    $path = rtrim($path,'/');
}
function check_syntax($file) {
    $error = php_file_syntax_check($file);
    if($error === true) return;
    throw new XException("{$error}");
}
function get_cookie() {
    $cookie_string = empty($_SERVER['HTTP_COOKIE']) ? getenv('HTTP_COOKIE') : $_SERVER['HTTP_COOKIE'];
    if($cookie_string) {
        $cookie_arr = explode(';',$cookie_string);
        foreach($cookie_arr as $c) {
            list($c_name,$c_value) = explode("=", $c);
            $c_name = trim($c_name);
            $_COOKIE[$c_name] = $c_value;
        }
    }
}
function php_file_syntax_check($file) {
    if(PHP_SAPI == 'cli') {
        $php_exc = getenv('_');
        if($php_exc == $_SERVER['argv'][0]) {
            $php_exc = PHP_BINDIR.'/php';
        }
        $str = exec("{$php_exc} -l {$file} 2>&1",$error, $rest);
        if($rest == 0) return true;
        if($rest == 255) return $error[0];
        return 'unable check syntax';
    }
    return true;
}
function dl_extension($ext, $func) {
    $cmd_help = ', you can use "php -d enable_dl" in command line that enable dl load function';
    if(!extension_loaded($ext)) {
        $extension_suffix = strtoupper(substr(PHP_OS,0,3)) === 'WIN' ? '.dll':'.so';
        if(ini_get('enable_dl') == '' || !dl("{$ext}{$extension_suffix}")) 
            throw new XException("can not load $ext extension $cmd_help");
    }
    if(!function_exists($func)) throw new XException("{$func} extension function can not used");
}
function daemon() {
    $fock_pid = pcntl_fork();
    if($fock_pid == -1) throw new XException('fork #1 Error');
    if($fock_pid >0) die;
    $fock_pid = pcntl_fork();
    if($fock_pid == -1) throw new XException('fork #2 ERROR');
    if($fock_pid>0) die;
    chdir('/');
    umask('0');
    posix_setsid();
    fclose(STDIN);
    fclose(STDOUT);
    fclose(STDERR);
    $sub_pid = pcntl_fork();
    if($sub_pid == -1) throw new XException('fork #3 ERROR');
    if($sub_pid >0) die;
}
define('UPFILE_NOT_EXISTS',957);
define('UPFILE_FILE_TYPE_ERROR', 9550);
define('UNAUTH_ACCESS',102);
define('UPFILE_SIZE_LARGER',4032);
define('UPFILE_FAILURE',5000);
