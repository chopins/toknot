<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
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
                            get_cookie();
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
                            $field_name = 'HTTP_' . str_replace('-', '_', strtoupper($field_name));
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
    private function getRequestBody($connect) {
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
    private function setLength($len) {
        return "Content-Length:$len\r\n";
    }

    /**
     * get_response_header 
     * RFC2616 set HTTP/1.1 response header
     * 
     * @access private
     * @return void
     */
    private function getResponseHeader() {
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
        $gdate = $this->setServerDate(gtime());
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
    private function setServerDate($time) {
        return gmdate('D, d M Y H:i:s', $time);
    }

    /**
     * get_setcookie_header 
     * RFC6265 set cookie
     * 
     * @access private
     * @return void
     */
    private function getSetcookieHeader() {
        $header = '';
        $cookie_arr = $this->scheduler->app_instance->R->C->get_cookie_array();
        if (empty($cookie_arr)) {
            return '';
        }
        foreach ($cookie_arr as $cs) {
            $header .= $cs;
        }
        if (!empty($header)) {
            $header = "Set-Cookie:{$header}\r\n";
        }
        return $header;
    }

}

?>
