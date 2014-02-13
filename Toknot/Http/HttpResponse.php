<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Http;

class HttpResponse {

    /**
     * response_status 
     * 
     * @var string
     * @access private
     */
    public $responseStatus = '';

    /**
     * response_status_code 
     * 
     * @var float
     * @access private
     */
    public $responseStatusCode = 200;

    /**
     * request_static_file 
     * 
     * @var string
     * @access public
     */
    public $requestStaticFile = null;

    /**
     * index 
     * 
     * @var array
     * @access public
     */
    public $index = array('index.php', 'index.html');

    /**
     * request_static_file_type 
     * 
     * @var string
     * @access public
     */
    public $requestStaticFileType = null;

    /**
     * request_static_file_state 
     * 
     * @var mixed
     * @access public
     */
    public $requestStaticFileState = null;

    /**
     * request_body_length 
     * 
     * @var float
     * @access public
     */
    public $requestBodyLength = 512000;

    /**
     * content_type 
     * 
     * @var mixed
     * @access public
     */
    public $contentType = null;

    /**
     * content_charset 
     * 
     * @var mixed
     * @access public
     */
    public $contentCharset = null;

    /**
     * content_length 
     * 
     * @var float
     * @access public
     */
    public $contentLength = 0;

    /**
     * document_root 
     * 
     * @var mixed
     * @access public
     */
    public $documentRoot;

    /**
     * upfile_tmp_dir 
     * 
     * @var mixed
     * @access public
     */
    public $upfileTmpDir;

    /**
     * upfile_tmp_list 
     * 
     * @var mixed
     * @access public
     */
    public $upfileTmpList;

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
    public $userHeaders = array();

    /**
     * cookie_header 
     * 
     * @var array
     * @access public
     */
    public $cookieHeader = null;
    
    public $responseBodyLen = 0;

    /**
     * get_request_body_by_form_urlencode 
     * 
     * @param resource $connect 
     * @access protected
     * @return void
     */
    protected function getRequestBodyByFormUrlEncode($connect) {
        $receive_length = 0;
        $body = '';
        while ($read = fread($connect, 1024)) {
            $receive_length+=strlen($read);
            if ($receive_length > $this->requestBodyLength)
                return $this->returnServerStatus(413);
            $body .= $read;
        }
        parse_str($body, $_POST);
    }

    /**
     * get_request_body_by_multipart 
     * 
     * @param resource $connect 
     * @access protected
     * @return void
     */
    protected function getRequestBodyByMultipart($connect) {
        $receive_length = 0;
        $boundary_count = 0;
        $cl_count = 0;
        $form_input = array();
        $body_end = false;
        while ($read = fgets($connect)) {
            $receive_length+=strlen($read);
            if ($receive_length > $this->requestBodyLength) {
                return $this->returnServerStatus(413);
            }
            if ($read == "{$this->boundary}\r\n") {
                $boundary_count++;
            } else if ($read == "\r\n" && $cl_count == $boundary_count - 1) {
                $cl_count++;
            } else if ($read == "{$this->boundary}--\r\n") {
                $body_end = true;
                break;
            } else {
                if ($boundary_count == $cl_count + 1) {
                    $field_str = strtolower(trim(strtok($read, ':')));
                    if ($field_str == 'content-disposition') {
                        $field_sub = strtok(';');
                        if ($field_sub === false)
                            continue;
                        $form_data = $form_file_name = $form_input_name = $field_is_array = false;
                        $field_sub = trim($field_sub);
                        while ($field_sub !== false) {
                            $field_sub = trim($field_sub);
                            ;
                            switch ($field_sub) {
                                case 'form-data':
                                    $form_data = true;
                                    $field_sub = strtok('=');
                                    break;
                                case 'name':
                                    $field_sub = strtok(';');
                                    $form_input_name = trim(trim($field_sub), '"');
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
                        if ($form_data && $form_input_name) {
                            if (substr($form_input_name, -1, 2) == '[]') {
                                $form_input[$boundary_count]['name_is_array'] = true;
                                $form_input_name = substr($form_inpt_name, 0, strlen($form_input_name) - 2);
                            } else {
                                $form_input[$boundary_count]['name_is_array'] = false;
                            }
                            $form_input[$boundary_count]['name'] = $form_input_name;
                            if ($form_file_name) {
                                $form_input[$boundary_count]['file'] = $form_file_name;
                            }
                        }
                    } else if ('content-type' == $field_str) {
                        $field_value = strtok(';');
                        if ($field_value === false) {
                            list(, $field_value) = explode(':', $read, 2);
                            $form_input[$boundary_count]['type'] = trim($field_value);
                        } else {
                            $form_input[$boundary_count]['type'] = trim($field_value);
                        }
                    }
                } else {
                    if (isset($form_input[$boundary_count]['fp'])) {
                        $form_input[$boundary_count]['size'] += strlen($read);
                        $re = fwrite($form_input[$boundary_count]['fp'], $read);
                    } elseif (isset($form_input[$boundary_count['data']])) {
                        $form_input[$boundary_count]['data'] .= $read;
                    } else {
                        if (isset($form_input[$boundary_count]['file'])) {
                            $form_input[$boundary_count]['tmp'] = tempnam($this->upfileTmpDir, 'tmp_XPF_');
                            $this->upfileTmpList[] = $form_input[$boundary_count]['tmp'];
                            $form_input[$boundary_count]['fp'] = @fopen($form_input[$boundary_count]['tmp'], 'w');
                            if ($form_input[$boundary_count]['fp'] == false) {
                                $errno = UPLOAD_ERR_CANT_WRITE;
                            } else if (strlen($read) > 0) {
                                $re = fwrite($form_input[$boundary_count]['fp'], $read);
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
        foreach ($form_input as $field) {
            $input = array();
            if (isset($field['file']) && $field['file']) {
                if (isset($errno) && $errno == UPLOAD_ERR_CANT_WRITE) {
                    $errno = UPLOAD_ERR_CANT_WRITE;
                } elseif ($body_end == false) {
                    $errno = UPLOAD_ERR_PARTIAL;
                } else if (!isset($field['fp'])) {
                    $errno = UPLOAD_ERR_NO_FILE;
                } else {
                    $errno = UPLOAD_ERR_OK;
                }
                fclose($field['fp']);
                if ($field['name_is_array']) {
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
                if ($field['name_is_array']) {
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
    protected function getRequestHeader($connect) {
        $this->requestStaticFile = null;
        $this->requestStaticFileState = false;
        $this->getRequestStartLine($connect);
        while ($read = fgets($connect, 1024)) {
            switch ($read) {
                case "\r\n":
                    return;
                default:
                    $field = explode(':', $read, 2);
                    $field_name = strtoupper(trim($field[0]));
                    $field_value = empty($field[1]) ? '' : trim($field[1]);
                    switch ($field_name) {
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
                            putenv("HTTP_COOKIE={$field_value}");
                            self::parseRequestCookieFromEnvVariable();
                            break;
                        case 'CONTENT-TYPE':
                            $field_value = strtolower($field_value);
                            $sub_field = strtok($field_value, ';');
                            $this->boundary = null;
                            while ($sub_field !== false) {
                                $sub_field = trim($sub_field);
                                switch ($sub_field) {
                                    case 'multipart/form-data':
                                        $this->contentType = $sub_field;
                                        $sub_field = strtok('=');
                                        break;
                                    case 'boundary':
                                        $sub_field = strtok(';');
                                        if ($sub_field === false) {
                                            $lt = explode('=', $field_value, 2);
                                            $this->boundary = '--' . trim(array_pop($lt));
                                        } else {
                                            $this->boundary = '--' . trim($sub_field);
                                        }
                                        break;
                                    case 'application/x-www-form-urlencoded':
                                        $this->contentType = $sub_field;
                                        $sub_field = strtok('=');
                                        break;
                                    case 'charset':
                                        $sub_field = strtok(';');
                                        if ($sub_field === false) {
                                            $lt = explode('=', $field_value, 2);
                                            $this->contentCharset = trim(array_pop($lt));
                                        } else {
                                            $this->contentCharset = trim($sub_field);
                                        }
                                        break;
                                    default:
                                        $sub_field = strtok('=');
                                        break;
                                }
                            }
                            break;
                        case 'CONTENT-LENGTH':
                            $this->contentLength = $field_value;
                            if ($field_value > $this->requestBodyLength) {
                                return $this->returnServerStatus('413');
                            }
                            break;
                        default:
                            $field_name = 'HTTP_' . strtr(strtoupper($field_name), '-', '_');
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
    protected function getRequestStartLine($connect) {
        $start_line = trim(fgets($connect));
        if (empty($start_line)) {
            return $this->returnServerStatus(400);
        }
        $uri_list = explode(' ', $start_line);
        if ($uri_list[0] != 'POST' && $uri_list[0] != 'GET') {
            $this->returnServerStatus('405');
            return false;
        }
        if (empty($uri_list[2]) || $uri_list[2] != 'HTTP/1.1') {
            $this->returnServerStatus('505');
            return false;
        }
        $_SERVER['REQUEST_METHOD'] = $uri_list[0];
        putenv("REQUEST_METHOD={$uri_list[0]}");
        if (empty($uri_list[1])) {
            $uri_list[1] = '/';
            $_SERVER['DOCUMENT_URI'] = $this->index[0];
        }
        $this->getAccessFileInfo($uri_list[1]);
        $_SERVER['REQUEST_URI'] = urldecode($uri_list[1]);
        if (($qtag_idx = strpos($_SERVER['REQUEST_URI'], '?')) !== false) {
            $_SERVER['DOCUMENT_URI'] = substr($_SERVER['REQUEST_URI'], 0, $qtag_idx);
            $_SERVER['QUERY_STRING'] = substr($_SERVER['REQUEST_URI'], $qtag_idx + 1);
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
    protected function returnServerStatus($code) {
        $responseStatusArray = array(
            100 => 'Continue', 101 => 'Switching Protocols',
            200 => 'OK', 201 => 'Created', 202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content', 205 => 'Reset Content',
            206 => 'Partial Content', 300 => 'Multiple Choices',
            301 => 'Moved Permanently', 302 => 'Moved Temporarily',
            303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy',
            400 => 'Bad Request', 401 => 'Unauthorized',
            402 => 'Payment Required', 403 => 'Forbidden',
            404 => 'Not Found', 405 => 'Method Not Allowed',
            406 => 'Not Acceptable', 407 => 'Proxy Authentication Required',
            408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone',
            411 => 'Length Required', 412 => 'Precondition Failed',
            413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large',
            415 => 'Unsupported Media Type', 500 => 'Internal Server Error',
            501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable',
            504 => 'Gateway Time-out', 505 => 'HTTP Version not supported',
            0 => null);
        $this->responseStatusCode = $code;
        $this->responseStatus = "HTTP/1.1 $code {$responseStatusArray[$code]}\r\n";
    }

    /**
     * get_access_file_info 
     * 
     * @param string $uri 
     * @access protected
     * @return void
     */
    protected function getAccessFileInfo($uri) {
        $uri_info = pathinfo($uri);
        if (isset($uri_info['extension']) && $uri_info['extension'] != $this->php_file_ext) {
            $this->requestStaticFile = true;
            if (is_dir($this->documentRoot)) {
                if (file_exists("{$this->documentRoot}{$uri}")) {
                    $this->requestStaticFile = "{$this->documentRoot}{$uri}";
                    $this->requestStaticFileState = true;
                    $this->requestStaticFileType = get_file_mime($this->requestStaticFile);
                    if ($uri_info['extension'] == 'js') {
                        list(, $charset) = explode(';', $this->requestStaticFileType);
                        $this->requestStaticFileType = "application/x-javascript;$charset";
                    } elseif ($uri_info['extension'] == 'css') {
                        list(, $charset) = explode(';', $this->requestStaticFileType);
                        $this->requestStaticFileType = "text/css;$charset";
                    }
                } else {
                    return $this->returnServerStatus(404);
                }
                if (is_readable($this->requestStaticFile) === false) {
                    return $this->returnServerStatus(403);
                }
            } else {
                $this->returnServerStatus(404);
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
    protected  function getRequestBody($connect) {
        if (!empty($this->contentType)) {
            switch ($this->contentType) {
                case 'application/x-www-form-urlencoded':
                    return $this->getRequestBodyByFormUrlEncode($connect);
                case 'multipart/form-data':
                    return $this->getRequestBodyByMultipart($connect);
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
    protected  function setLength($len) {
        return "Content-Length:$len\r\n";
    }

    /**
     * get_response_header 
     * RFC2616 set HTTP/1.1 response header
     * 
     * @access private
     * @return void
     */
    protected function getResponseHeader() {
        $userHeaders = '';
        if (!empty($this->userHeaders)) {
            foreach ($this->userHeaders as $header) {
                $field = explode(':', $header);
                $fieldname = strtolower(trim($field[0]));
                switch ($fieldname) {
                    case 'location':
                        $this->returnServerStatus(301);
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
                        $userHeaders .= "$header\r\n";
                        break;
                }
            }
        }
        $header = $this->responseStatus;
        if ($this->requestStaticFileState === true) {
            $header .= "Cache-Control:max-age={$this->cache_control_time}\r\n";
        } else if (!empty($u_cache_control)) {
            $header .= "Cache-Control:{$u_cache_control}\r\n";
        } else {
            $header .= "Cache-Control:no-cache\r\n";
            $header .= "Pragma:no-cache\r\n";
        }
        if (!empty($u_location)) {
            $header .= "Location:{$u_location}\r\n";
        }
        if (isset($_SERVER['HTTP_CONNECTION'])) {
            $header .= "Connection:{$_SERVER['HTTP_CONNECTION']}\r\n";
        } else if (!empty($u_connect)) {
            $header .= "Connection:{$u_connect}\r\n";
        } else {
            $header .= "Connection:Keep-Alive\r\n";
        }
        $gdate = $this->setServerDate();
        $header .= "Date:{$gdate} GMT\r\n";
        if (!empty($u_content_language)) {
            $header .= "Content-Language:{$u_content_language}\r\n";
        } else {
            $header .= "Content-Language:zh\r\n";
        }
        if ($this->requestStaticFileType) {
            $header .= "Content-Type:{$this->requestStaticFileType}\r\n";
            $last_modif = $this->setServerDate(filemtime($this->requestStaticFile));
            $header .= "Last-Modified:{$last_modif} GMT\r\n";
        } else {
            $header .= "Content-Type:text/html;charset=utf-8\r\n";
        }
        if($this->responseBodyLen > 0) {
            $header .= $this->setLength($this->responseBodyLen);
        }
        $header .= "Server:XPHPFramework\r\n";
        self::getSetCookieHeader();
        $header .= $userHeaders;
        $header .= "\r\n";
        return $header;
    }

    /**
     * set_server_date 
     * 
     * @access private
     * @return void
     */
    private function setServerDate() {
        return gmdate('D, d M Y H:i:s',time());
    }

    /**
     * get_setcookie_header 
     * RFC6265 set cookie
     * 
     * @access private
     * @return void
     */
    public static  function getSetCookieHeader() {
        $header = '';
        foreach($_SERVER['COOKIES_LIST'] as $cookie) {
            $cookieValue = urlencode($cookie[1]);
            $cookieName = urlencode($cookie[0]);
            $header .= "{$cookieName}={$cookieValue};";
            if($cookie[2] > 0 && is_numeric($cookie[2])) {
                $header .= 'Expires='. gmdate('D, d-M-Y H:i:s GMT', $cookie[2]).';';
            }
            if(!empty($cookie[3])) {
                $cookiePath = str_replace(array(';','='), array('%3B','%3D'), $cookie[3]);
                $header .= "Path={$cookiePath};";
            } 
            if(!empty($cookie[4])) {
                $cookieDomain = str_replace(array(';','='), array('%3B','%3D'), $cookie[4]);
                $header .= "Domain={$cookieDomain};";
            }
            if(!empty($cookie[5])) {
                $header .= 'Secure;';
            }
            if(!empty($cookie[6])) {
                $header .= 'HttpOnly;';
            }
            $header = rtrim($header, ';')."\r\n";
        }
        if (!empty($header)) {
            $header = "Set-Cookie: {$header}\r\n";
        }
        return $header;
    }

    static public function importPostData() {
        $http_body = file_get_contents('php://input','r');
        if(!empty($http_body)) {
            $content_type = getenv('HTTP_CONTENT_TYPE');
            if($content_type == 'application/x-www-form-urlencoded') {
                if($this->encoding != $this->utf8) 
                    $http_body = mb_convert_encoding($http_body, $this->utf8,$this->encoding);
                parse_str($http_body,$_POST);
            } else {
                $content_len = getenv('HTTP_CONTENT_LENGTH');
                $c_field = trim(strtok($content_type,';'));
                $upload_max_filesize = conv_human_byte(ini_get('upload_max_filesize'));
                while($c_field !== false) {
                    switch($c_field) {
                    case 'multipart/form-data':
                        $c_field = trim(strtok('='));
                    break;
                    case 'boundary':
                        if(($c_field = strtok(';')) === false) {
                            $boundary = '--'.trim($c_field);
                        } else {
                            $lt = explode('=',$content_type);
                            $boundary = '--'. trim(array_pop($lt));
                        }
                        $c_field = strtok(';');
                    break;
                    default:
                        $c_field = strtok('=');
                    break;
                    }
                }
                if(empty($boundary)) return;
                $part_arr = explode($boundary,$http_body);
                $body_end = false;
                foreach($part_arr as $part) {
                    if(empty($part)) continue;
                    if(trim($part) == '--') {
                        $body_end = true;
                        break;
                    }
                    $content_arr = explode("\r\n\r\n",$part,2);
                    $content_data = rtrim($content_arr[1]);
                    $content_field = trim(strtolower(strtok($content_arr[0],':')));
                    while(false !== $content_field) {
                        switch($content_field) {
                            case 'content-disposition':
                                $content_field = strtok(';');
                                $content_field = trim(strtok('='));
                            break;
                            case 'name':
                                $name = strtok('"');
                                if($name == 'MAX_FILE_SIZE') $form_max_size = $content_data;
                                $content_field = trim(ltrim(strtok('='),';'));
                                if($content_field === false) $content_field = trim(strtok(':'));
                                else $content_field = trim($content_field);
                            break;
                            case 'filename':
                                $filename = strtok('"');
                                $content_field = strtok(':');
                                if($content_field === false) $content_field = trim(strtok(':'));
                                else $content_field = strtolower(trim($content_field));
                            break;
                            case 'content-type':
                                $file_type = trim(strtok("\r\n"));
                                $content_field = strtok(':');
                            break;
                            default:
                                $content_field = strtok(';');
                                $content_field = strtok('=');
                            break;
                        }
                    }
                    if(isset($name) && isset($filename) && $filename !== false) {
                        $upfile_tmp_dir = isset($this->cfg->server->upfileTmpDir) ? 
                                            $this->cfg->server->upfileTmpDir:sys_get_temp_dir();
                        $tmp = tempnam($upfile_tmp_dir,'tmp_XPF_');
                        $file_len = strlen($content_data);
                        if($file_len > $upload_max_filesize) {
                            $errno = UPLOAD_ERR_INI_SIZE;
                        } else if($file_len == 0) {
                            $errno = UPLOAD_ERR_NO_FILE;
                        } else if(isset($form_max_size) && $form_max_size < $file_len) {
                            $errno = UPLOAD_ERR_FORM_SIZE;
                        } else if(empty($upfile_tmp_dir) || !is_dir($upfile_tmp_dir)) {
                            $errno = UPLOAD_ERR_NO_TMP_DIR;
                        } else if($body_end == false) {
                            $errno = UPLOAD_ERR_PARTIAL;
                        } else {
                            $errno = UPLOAD_ERR_OK;
                        }
                        if($errno == UPLOAD_ERR_OK) {
                            $fp = file_put_contents($tmp,$content_data);
                            if($fp === false) $errno = UPLOAD_ERR_CANT_WRITE;
                        }
                        if(substr($name,-1,2) == '[]') {
                            $_FILES[$name]['name'][] = $filename;
                            $_FILES[$name]['type'][] = $file_type;
                            $_FILES[$name]['size'][] = $file_len;
                            $_FILES[$name]['tmp_name'][] = $tmp;
                            $_FILES[$name]['error'][] = $errno;
                        } else {
                            $_FILES[$name]['name'] = $filename;
                            $_FILES[$name]['type'] = $file_type;
                            $_FILES[$name]['size'] = $file_len;
                            $_FILES[$name]['tmp_name'] = $tmp;
                            $_FILES[$name]['error'] = $errno;
                        }
                    } elseif(isset($name)) {
                        if($this->encoding != $this->utf8) {
                            $name = mb_convert_encoding($name, $this->utf8,$this->encoding);
                            $content_data = mb_convert_encoding($content_data, $this->utf8,$this->encoding);
                        }
                        if(substr($name,-1,2) == '[]') {
                            $_POST[$name][] = $content_data;
                        } else {
                            $_POST[$name] = $content_data;
                        } 
                    }
                }
            }
        }
    }
    
    /**
     * set $_COOKIE from $_SERVER or environment variable
     */
    static public function parseRequestCookieFromEnvVariable() {
        $cookieString = empty($_SERVER['HTTP_COOKIE']) ? getenv('HTTP_COOKIE') : $_SERVER['HTTP_COOKIE'];
        if ($cookieString) {
            $cookieName = trim(strtok($cookieString, '='));
            while($cookieName) {
                $_COOKIE[$cookieName] = trim(strtok(';'));
                $cookieName = strtok('=');
            }
        }
    }

}

?>
