<?php
/**
 * Toknot
 *
 * XHTTPResponse class
 *
 * PHP version 5.3
 * 
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release 0.1
 */

exists_frame();


/**
 * XHTTPResponse 
 * 
 * @abstract
 * @version $id$
 * @copyright 2012 The Author
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
abstract class XHTTPResponse {
    /**
     * response_status 
     * 
     * @var string
     * @access private
     */
    public $response_status = '';
    /**
     * response_status_code 
     * 
     * @var float
     * @access private
     */
    public $response_status_code = 200;
    /**
     * request_static_file 
     * 
     * @var string
     * @access public
     */
    public $request_static_file = null;
    /**
     * index 
     * 
     * @var array
     * @access public
     */
    public $index = array('index.php','index.html');
    /**
     * request_static_file_type 
     * 
     * @var string
     * @access public
     */
    public $request_static_file_type = null;
    /**
     * request_static_file_state 
     * 
     * @var mixed
     * @access public
     */
    public $request_static_file_state = null;
    /**
     * request_body_length 
     * 
     * @var float
     * @access public
     */
    public $request_body_length = 512000;
    /**
     * content_type 
     * 
     * @var mixed
     * @access public
     */
    public $content_type = null;
    /**
     * content_charset 
     * 
     * @var mixed
     * @access public
     */
    public $content_charset = null;
    /**
     * content_length 
     * 
     * @var float
     * @access public
     */
    public $content_length = 0;
    /**
     * document_root 
     * 
     * @var mixed
     * @access public
     */
    public $document_root;
    /**
     * upfile_tmp_dir 
     * 
     * @var mixed
     * @access public
     */
    public $upfile_tmp_dir;
    /**
     * upfile_tmp_list 
     * 
     * @var mixed
     * @access public
     */
    public $upfile_tmp_list;
    /**
     * boundary 
     * 
     * @var string
     * @access public
     */
    public $boundary = null;
    /**
     * user_headers 
     * 
     * @var array
     * @access public
     */
    public $user_headers = array();
    /**
     * cookie_header 
     * 
     * @var array
     * @access public
     */
    public $cookie_header = null;
    /**
     * get_request_body_by_form_urlencode 
     * 
     * @param resource $connect 
     * @access protected
     * @return void
     */
    protected function get_request_body_by_form_urlencode($connect) {
        $receive_length = 0;
        $body = '';
        while($read = fread($connect,1024)) {
            $receive_length+=strlen($read);
            if($receive_length > $this->request_body_length) 
                return $this->return_server_status(413);
            $body .= $read;
        }
        parse_str($body,$_POST);
    }
    /**
     * get_request_body_by_multipart 
     * 
     * @param resource $connect 
     * @access protected
     * @return void
     */
    protected function get_request_body_by_multipart($connect) {
        $receive_length = 0;
        $boundary_count = 0;
        $cl_count = 0;
        $form_input = array();
        $body_end = false;
        while($read = fgets($connect)) {
            $receive_length+=strlen($read);
            if($receive_length > $this->request_body_length) {
                return $this->return_server_status(413);
            }
            if($read == "{$this->boundary}\r\n") {
                $boundary_count++;
            } else if($read == "\r\n" && $cl_count == $boundary_count-1) {
                $cl_count++;
            } else if($read == "{$this->boundary}--\r\n") {
                $body_end = true;
                break;
            } else {
                if($boundary_count == $cl_count +1) {
                    $field_str = strtolower(trim(strtok($read,':')));
                    if($field_str == 'content-disposition') {
                        $field_sub = strtok(';');
                        if($field_sub === false) continue;
                        $form_data = $form_file_name = $form_input_name = $field_is_array = false;
                        $field_sub = trim($field_sub);
                        while($field_sub !== false) {
                            $field_sub = trim($field_sub);;
                            switch($field_sub) {
                            case 'form-data':
                                $form_data = true;
                                $field_sub = strtok('=');
                            break;
                            case 'name':
                                $field_sub = strtok(';');
                                $form_input_name = trim(trim($field_sub),'"');
                                $field_sub = strtok('=');
                            break;
                            case 'filename':
                                $field_sub = strtok('"');
                                $form_file_name = trim($field_sub);
                                $field_sub = strtok('=');
                            break;
                            default:
                                $field_sub = strtok('=');
                            }
                        }
                        if($form_data && $form_input_name) {
                            if(substr($form_input_name,-1,2) == '[]') {
                                $form_input[$boundary_count]['name_is_array'] = true;
                                $form_input_name = substr($form_inpt_name,0,strlen($form_input_name)-2);
                            } else {
                                $form_input[$boundary_count]['name_is_array'] = false;
                            }
                            $form_input[$boundary_count]['name'] =  $form_input_name;
                            if($form_file_name) {
                                $form_input[$boundary_count]['file'] = $form_file_name;
                            }
                        }
                    } else if('content-type' == $field_str) {
                        $field_value = strtok(';');
                        if($field_value === false) {
                            list(,$field_value) = explode(':',$read,2);
                            $form_input[$boundary_count]['type'] = trim($field_value);
                        } else {
                            $form_input[$boundary_count]['type'] = trim($field_value);
                        }
                    }
                } else {
                    if(isset($form_input[$boundary_count]['fp'])) {
                        $form_input[$boundary_count]['size'] += strlen($read);
                        $re = fwrite($form_input[$boundary_count]['fp'],$read);
                    } elseif(isset($form_input[$boundary_count['data']])) {
                        $form_input[$boundary_count]['data'] .= $read;
                    } else {
                        if(isset($form_input[$boundary_count]['file'])) {
                            $form_input[$boundary_count]['tmp'] = tempnam($this->upfile_tmp_dir,'tmp_XPF_');
                            $this->upfile_tmp_list[] = $form_input[$boundary_count]['tmp'];
                            $form_input[$boundary_count]['fp'] = @fopen($form_input[$boundary_count]['tmp'], 'w');
                            if($form_input[$boundary_count]['fp'] == false) {
                                $errno = UPLOAD_ERR_CANT_WRITE;
                            } else if(strlen($read) > 0) {
                                $re = fwrite($form_input[$boundary_count]['fp'],$read);
                            } else {
                                unset($form_input[$boundary_count]['fp']);
                            }
                        } else {
                            $form_input[$boundary_count]['data'] = $read;
                        }
                        $form_input[$boundary_count]['size'] = strlen($read);
                    }
                }
            }
        }
        foreach($form_input as $field) {
            $input =  array();
            if(isset($field['file']) && $field['file']) {
                if(isset($errno) && $errno == UPLOAD_ERR_CANT_WRITE) {
                    $errno = UPLOAD_ERR_CANT_WRITE;
                } elseif($body_end == false ) {
                    $errno = UPLOAD_ERR_PARTIAL;
                } else if(!isset($field['fp'])) {
                    $errno = UPLOAD_ERR_NO_FILE;
                } else {
                    $errno = UPLOAD_ERR_OK;
                }
                fclose($field['fp']);
                if($field['name_is_array']) {
                    $_FILES[$field['name']]['name'][] = $field['file'];
                    $_FILES[$field['name']]['type'][] = $field['type'];
                    $_FILES[$field['name']]['size'][] = $field['size'];
                    $_FILES[$field['name']]['tmp_name'][] = $field['tmp'];
                    $_FILES[$name]['error'][] = $errno;
                } else {
                    $_FILES[$field['name']]['name'] = $field['file'];
                    $_FILES[$field['name']]['type'] = $field['type'];
                    $_FILES[$field['name']]['size'] = $field['size'];
                    $_FILES[$field['name']]['tmp_name'] = $field['tmp'];
                    $_FILES[$name]['error'] = $errno;
                }
            } else {
                if($field['name_is_array']) {
                    $_POST[$field['name']][] = rtrim($field['data']);
                } else {
                    $_POST[$field['name']] = rtrim($field['data']);
                }
            }
        }
    }
    /**
     * get_request_header 
     * 
     * @param resource $connect 
     * @access protected
     * @return void
     */
    protected function get_request_header($connect) {
        $this->request_static_file = null;
        $this->request_static_file_state = false;
        $this->get_request_start_line($connect);
        while($read = fgets($connect,1024)) {
            switch($read) {
                case "\r\n":
                return;
                default:
                $field = explode(':',$read,2);
                $field_name = strtoupper(trim($field[0]));
                $field_value = empty($field[1]) ? '' : trim($field[1]);
                switch($field_name) {
                    case 'HOST':
                    $_SERVER['HTTP_HOST'] = $field_value;
                    break;
                    case 'USER-AGENT':
                    $_SERVER['HTTP_USER_AGENT'] = $field_value;
                    break;
                    case 'REFERER':
                    $_SERVER['HTTP_REFERER'] = $field_value;
                    break;
                    case 'ACCEPT':
                    $_SERVER['HTTP_ACCEPT'] = $field_value;
                    break;
                    case 'ACCEPT-CHARSET':
                    $_SERVER['HTTP_ACCEPT_CHARSET'] = $field_value;
                    break;
                    case 'ACCEPT-ENCODING':
                    $_SERVER['HTTP_ACCEPT_ENCODING'] = $field_value;
                    break;
                    case 'ACCEPT-LANGUAGE':
                    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $field_value;
                    break;
                    case 'CONNECTION':
                    $_SERVER['HTTP_CONNECTION'] = $field_value;
                    break;
                    case 'COOKIE':
                    $_SERVER['HTTP_COOKIE'] = $field_value;
                    get_cookie();
                    break;
                    case 'CONTENT-TYPE':
                    $field_value = strtolower($field_value);
                    $sub_field = strtok($field_value,';');
                    $this->boundary = null;
                    while($sub_field !== false) {
                        $sub_field = trim($sub_field);
                        switch($sub_field) {
                            case 'multipart/form-data':
                            $this->content_type = $sub_field;
                            $sub_field = strtok('=');
                            break;
                            case 'boundary':
                            $sub_field = strtok(';');
                            if($sub_field === false) {
                                $lt = explode('=',$field_value,2);
                                $this->boundary = '--'. trim(array_pop($lt));
                            } else {
                                $this->boundary = '--'.trim($sub_field);
                            }
                            break;
                            case 'application/x-www-form-urlencoded':
                            $this->content_type = $sub_field;
                            $sub_field = strtok('=');
                            break;
                            case 'charset':
                            $sub_field = strtok(';');
                            if($sub_field === false) {
                                $lt = explode('=',$field_value,2);
                                $this->content_charset = trim(array_pop($lt));
                            } else {
                                $this->content_charset = trim($sub_field);
                            }
                            break;
                            default:
                            $sub_field = strtok('=');
                            break;
                        }
                    }
                    break;
                    case 'CONTENT-LENGTH':
                    $this->content_length = $field_value;
                        if($field_value > $this->request_body_length) {
                            return $this->return_server_status('413');
                        }
                    break;
                    default:
                    $field_name = 'HTTP_' . str_replace('-','_',strtoupper($field_name));
                    $_SERVER[$field_name] = $field_value;
                    break;
                }
            }
        }
    }
    /**
     * get_request_start_line 
     * RFC 3986 , RFC1738
     * 
     * @param resource $connect 
     * @access protected
     * @return void
     */
    protected function get_request_start_line($connect) {
        $start_line = trim(fgets($connect));
        if(empty($start_line)) {
            return $this->return_server_status(400);
        }
        $uri_list = explode(' ', $start_line);
        if($uri_list[0] != 'POST' && $uri_list[0] != 'GET') {
            $this->return_server_status('405');
            return false;
        }
        if(empty($uri_list[2]) || $uri_list[2] != 'HTTP/1.1') {
            $this->return_server_status('505');
            return false;
        }
        $_SERVER['REQUEST_METHOD'] = $uri_list[0];
        if(empty($uri_list[1])) {
            $uri_list[1] = '/';
            $_SERVER['DOCUMENT_URI'] = $this->index[0];
        }
        $this->get_access_file_info($uri_list[1]);
        $_SERVER['REQUEST_URI'] = urldecode($uri_list[1]);
        if(($qtag_idx = strpos($_SERVER['REQUEST_URI'],'?')) !== false) {
            $_SERVER['DOCUMENT_URI'] = substr($_SERVER['REQUEST_URI'],0,$qtag_idx);
            $_SERVER['QUERY_STRING'] = substr($_SERVER['REQUEST_URI'],$qtag_idx+1);
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        } else {
            $_SERVER['DOCUMENT_URI'] = $uri_list[1] == '/' ? $this->index[0] : $_SERVER['REQUEST_URI'];
            $_SERVER['QUERY_STRING'] = '';
        }
    }
    /**
     * return_server_status 
     * 
     * @param int $code 
     * @access protected
     * @return void
     */
    protected function return_server_status($code) {
        $response_status_array = array(
            100=>'Continue',101=>'Switching Protocols',
            200=>'OK',201=>'Created',202=>'Accepted',
            203=>'Non-Authoritative Information',
            204=>'No Content',205=>'Reset Content',
            206=>'Partial Content',300=>'Multiple Choices',
            301=>'Moved Permanently',302=>'Moved Temporarily',
            303=>'See Other',304=>'Not Modified',305=>'Use Proxy',
            400=>'Bad Request',401=>'Unauthorized',
            402=>'Payment Required',403=>'Forbidden',
            404=>'Not Found',405=>'Method Not Allowed',
            406=>'Not Acceptable',407=>'Proxy Authentication Required',
            408=>'Request Time-out',409=>'Conflict',410=>'Gone',
            411=>'Length Required',412=>'Precondition Failed',
            413=>'Request Entity Too Large',414=>'Request-URI Too Large',
            415=>'Unsupported Media Type',500=>'Internal Server Error',
            501=>'Not Implemented',502=>'Bad Gateway',503=>'Service Unavailable',
            504=>'Gateway Time-out',505=>'HTTP Version not supported',
            0=>null);
        $this->response_status_code = $code;
        $this->response_status = "HTTP/1.1 $code {$response_status_array[$code]}\r\n";
    }
    /**
     * get_access_file_info 
     * 
     * @param string $uri 
     * @access protected
     * @return void
     */
    protected function get_access_file_info($uri) {
        $uri_info = pathinfo($uri);
        if(isset($uri_info['extension']) && $uri_info['extension'] != $this->php_file_ext) {
            $this->request_static_file = true;
            if(is_dir($this->document_root)) {
                if(file_exists("{$this->document_root}{$uri}")) {
                    $this->request_static_file = "{$this->document_root}{$uri}";
                    $this->request_static_file_state = true;
                    $this->request_static_file_type = get_file_mime($this->request_static_file);
                    if($uri_info['extension'] == 'js') {
                        list(,$charset) = explode(';',$this->request_static_file_type);
                        $this->request_static_file_type = "application/x-javascript;$charset";
                    } elseif($uri_info['extension'] == 'css') {
                        list(,$charset) = explode(';',$this->request_static_file_type);
                        $this->request_static_file_type = "text/css;$charset";
                    }
                } else {
                    return $this->return_server_status(404);
                }
                if(is_readable($this->request_static_file) === false) {
                    return $this->return_server_status(403);
                }
            } else {
                $this->return_server_status(404);
            }
        }
    }
    /**
     * get_request_body 
     * RFC 1867
     * 
     * @param resource $connect 
     * @access private
     * @return void
     */
    private function get_request_body($connect) {
        if(!empty($this->content_type)) {
            switch($this->content_type) {
                case 'application/x-www-form-urlencoded':
                return $this->get_request_body_by_form_urlencode($connect);
                case 'multipart/form-data':
                return $this->get_request_body_by_multipart($connect);
            }
        }
        return;
    }

    /**
     * set_length 
     * 
     * @param int $len 
     * @access private
     * @return void
     */
    private function set_length($len) {
        return "Content-Length:$len\r\n";
    }
  
    /**
     * get_response_header 
     * RFC2616 set HTTP/1.1 response header
     * 
     * @access private
     * @return void
     */
    private function get_response_header() {
        if(!empty($this->user_headers)) {
            foreach($this->user_headers as $header) {
                $field = explode(':',$header);
                $fieldname = strtolower(trim($field[0]));
                switch($fieldname) {
                    case 'location':
                    $this->return_server_status(301);
                    $u_location = $field[1];
                    break;
                    case 'cache-control':
                    $u_cache_control = $field[1];
                    break;
                    case 'connection':
                    $u_connect = $field[1];
                    break;
                    case 'content-language':
                    $u_content_language = $field[1];
                    break;
                    default:
                    break;
                }
            }
        }
        $header = $this->response_status;
        if($this->request_static_file_state === true) {
            $header .= "Cache-Control:max-age={$this->cache_control_time}\r\n";
        } else if(!empty($u_cache_control)) {
            $header .= "Cache-Control:{$u_cache_control}\r\n";
        } else {
            $header .= "Cache-Control:no-cache\r\n";
            $header .= "Pragma:no-cache\r\n";
        }
        if(!empty($u_location)) {
            $header .= "Location:{$u_location}\r\n";
        }
        if(isset($_SERVER['HTTP_CONNECTION'])) {
            $header .= "Connection:{$_SERVER['HTTP_CONNECTION']}\r\n";
        } else if(!empty($u_connect)) {
            $header .= "Connection:{$u_connect}\r\n";
        } else {
            $header .= "Connection:Keep-Alive\r\n";
        }
        $gdate = $this->set_server_date(gtime());
        $header .= "Date:{$gdate} GMT\r\n";
        if(!empty($u_content_language)) {
            $header .= "Content-Language:{$u_content_language}\r\n";
        } else {
            $header .= "Content-Language:zh\r\n";
            }
        if($this->request_static_file_type) {
            $header .= "Content-Type:{$this->request_static_file_type}\r\n";
            $last_modif = $this->set_server_date(filemtime($this->request_static_file));
            $header .= "Last-Modified:{$last_modif} GMT\r\n";
        } else {
            $header .= "Content-Type:text/html;charset=utf-8\r\n";
        }
        $header .= "Server:XPHPFramework\r\n";
        return $header;
    }
    /**
     * set_server_date 
     * 
     * @param int $time 
     * @access private
     * @return void
     */
    private function set_server_date($time) {
        return gmdate('D, d M Y H:i:s', $time);
    }
    /**
     * get_setcookie_header 
     * RFC6265 set cookie
     * 
     * @access private
     * @return void
     */
    private function get_setcookie_header() {
        $header = '';
        $cookie_arr = $this->scheduler->app_instance->R->C->get_cookie_array();
        if(empty($cookie_arr)) {
            return '';
        }
        foreach($cookie_arr as $cs) {
            $header .= $cs;
        }
        if(!empty($header)) {
            $header = "Set-Cookie:{$header}\r\n";
        }
        return $header;
    }
  
}
