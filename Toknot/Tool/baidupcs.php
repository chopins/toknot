#!/bin/env php
<?php

class BaiduPCS {

    private $transprot = 'ssl://';
    private $accessToken = '';
    private $url = '';
    private $ch = null;
    private $oauth_file = '';
    private $uploadSize = 0;
    private $splitUpload = true;
    private $splitSize = 0;
    private $filesize = 0;
    private $ffp = null;
    private $writeStatus = 0;

    const CLIENT_ID = '';
    const SECRET_KEY = '';
    const APP_ROOT = '/apps/app5';
    const DEFAULT_SPLIT_SIZE = '1000M';

    public function __construct($argv, $argc) {
        if ($argc <= 1) {
            return $this->help();
        }
        $this->getAuthFile();
        foreach ($argv as $command) {
            switch ($command) {
                case 'upload':
                    if (empty($argv[2]) || empty($argv[3])) {
                        $this->argsError();
                    }
                    $option = array();
                    $optionList = array('-o', '-n', '-p', '-l');
                    $localfile = $remotepath = null;
                    for ($i = 1; $i < $argc; $i++) {
                        if (in_array($argv[$i], $optionList)) {
                            $option[$argv[$i]] = 1;
                            if ($argv[$i] == '-p') {
                                $split = $argv[$i];
                                $i++;
                                $option[$split] = $argv[$i];
                            }
                        } else if ($argv[$i] == 'upload') {
                            continue;
                        } else {
                            if ($localfile === null) {
                                $localfile = $argv[$i];
                            } else {
                                $remotepath = $argv[$i];
                            }
                        }
                    }
                    if (empty($remotepath) || empty($localfile)) {
                        $this->argsError();
                    }
                    $this->doUpload($localfile, $remotepath, $option);
                    return;
                case 'quota':
                    $this->quota();
                    return;
                case 'ls':
                    $this->ls($argv[2]);
                    return;
            }
        }
        return $this->help();
    }

    public function getAuthFile() {
        $this->oauth_file = $_SERVER['HOME'] . '/.baidpcs';
    }

    public function argsError() {
        $this->errMessage('参数错误');
        $this->help();
        exit;
    }

    public function help() {
        echo <<<EOF
Usage：baidupcs command [option]
     command：
        upload [option] localpath remote 上传文件
            option:
                  -o 覆盖
                  -n 不覆盖，并新命名
                  -p 分片大小
                  -l 跟踪软链接
        download [option] remote localpath 下载文件
             option:
                  -o 覆盖本地同名文件
                  -n 不覆盖
        quota   查新配额
        mkdir   新建文件夹
        mv      移动文件
        rm      删除文件
        cp      复制文件
        ls      文件列表
            option:
                -l
                -r
EOF;
        echo "\r\n";
    }

    public function ls($remotepath) {
        $this->oauth();
        $port = $this->transprot == 'ssl://' ? 443 : 80;
        $host = "pcs.baidu.com";
        $query = http_build_query(array('method' => 'list',
            'access_token' => $this->accessToken,
            'path' => $remotepath));
        $this->url = "/rest/2.0/pcs/quota?{$query}";
        $ret = $this->httpRequest($host, $port);
        var_dump($ret);
    }

    public function quota() {
        $this->oauth();

        $host = "pcs.baidu.com";
        $port = $this->transprot == 'ssl://' ? 443 : 80;
        $query = http_build_query(array('method' => 'info', 'access_token' => $this->accessToken));
        $this->url = "/rest/2.0/pcs/quota?{$query}";
        $ret = $this->httpRequest($host, $port);
        if (!empty($ret)) {
            $retData = json_decode($ret,true);
            if (isset($retData['error'])) {
                $this->oauthError($retData);
            } else {
                $quota = $this->sizeFormat($retData['quota']);
                $used = $this->sizeFormat($retData['used']);
                $this->errMessage("总量: {$quota}\r\n已使用: {$used}\r\n");
            }
        }
    }

    public function doUpload($localfile, $remotepath, $option) {
        $this->oauth();

        $localfile = realpath($localfile);
        if (!$localfile) {
            return $this->errMessage("{$localfile}文件不存在");
        }

        if (isset($option['-o'])) {
            $ondup = 'overwrite';
        } else {
            $ondup = 'newcopy';
        }

        $host = "c.pcs.baidu.com";
        $this->filesize = $fileSize = filesize($localfile);
        $this->splitSize = $this->sizeCovert(isset($option['-p']) ? $option['-p'] : self::DEFAULT_SPLIT_SIZE);
        $splitNum = 1;
        if ($this->splitSize < $fileSize) {
            $queryParamString = http_build_query(array('method' => 'upload',
                'access_token' => $this->accessToken,
                'type' => 'tmpfile'
            ));
            $this->splitUpload = true;
            $blockList = array();
            $splitNum = ceil($fileSize / $this->splitSize);
        } else {
            $this->splitSize = $fileSize;
            $queryParamString = http_build_query(array('method' => 'upload',
                'access_token' => $this->accessToken,
                'path' => self::APP_ROOT . $remotepath,
                'ondup' => $ondup
            ));
            $this->splitUpload = false;
        }
        $this->url = "/rest/2.0/pcs/file?{$queryParamString}";

        $port = $this->transprot == 'ssl://' ? 443 : 80;
        $this->errMessage($this->splitSize);
        for ($i = 0; $i < $splitNum; $i++) {
            $this->errMessage("第{$i}片;共{$splitNum}片");
            $ret = $this->httpPost($host, $port, $localfile);
            if (!$ret) {
                if ($this->writeStatus == 'reset') {
                    $i--;
                    continue;
                }
            }
            $retData = json_decode($ret, true);
            if (isset($retData['error'])) {
                return $this->oauthError($retData);
            } else {
                if ($this->splitUpload) {
                    $blockList[] = $retData['md5'];
                }
            }
        }
        if ($this->splitUpload) {
            $queryParamString = http_build_query(array('method' => 'createsuperfile',
                'access_token' => $this->accessToken,
                'path' => self::APP_ROOT . $remotepath,
                'ondup' => $ondup
            ));
            print_r($blockList);
            $postData = '{ "block_list":' . json_encode($blockList) . '}';
            $this->errMessage('合并文件');
            $ret = $this->httpPost($host, $port, $postData, false);
            $retData = json_decode($ret, true);
            if (isset($retData['error'])) {
                return $this->oauthError($retData);
            }
            print_r($retData);
        }
        $this->errMessage('Upload Success');
    }

    public function sizeFormat($size) {
        if ($size > 1099511627776) {
            return round($size / 1099511627776, 2) . 'T';
        } elseif ($size > 1073741824) {
            return round($size / 1073741824, 2) . 'G';
        } elseif ($size > 1048576) {
            return round($size / 1048576, 2) . 'M';
        } elseif ($size > 1024) {
            return round($size / 1024, 2) . 'K';
        }
    }

    public function sizeCovert($size) {
        $min = 5120;
        if (!is_numeric($size)) {
            $uint = strtolower(substr($size, -1));
            $num = substr($size, 0, -1);
            if (!is_numeric($num)) {
                $this->errMessage('分割尺寸错误,使用默认');
                return $this->sizeCovert(self::DEFAULT_SPLIT_SIZE);
            }
            switch ($uint) {
                case 'k':
                    $size = $num * 1024;
                    break;
                case 'm':
                    return $num * 1048576;
                case 'g':
                    return $num * 1073741824;
                default :
                    $this->errMessage('分割尺寸错误,使用默认');
                    return $this->sizeCovert(self::DEFAULT_SPLIT_SIZE);
            }
        }
        return $size < $min ? $min : $size;
    }

    public function oauth() {
        if (file_exists($this->oauth_file)) {
            $oauth = file_get_contents($this->oauth_file);
            $ret = json_decode($oauth, true);
            $mtime = filemtime($this->oauth_file) + $ret['expires_in'];
            if ($mtime < time()) {
                return $this->oauth();
            }
            $this->accessToken = $ret['access_token'];
            return;
        }
        $queryParamString = http_build_query(array('client_id' => self::CLIENT_ID,
            'response_type' => 'device_code',
            'scope' => 'basic,netdisk'
        ));
        $host = "openapi.baidu.com";
        $this->url = "/oauth/2.0/device/code?{$queryParamString}";
        $port = $this->transprot == 'ssl://' ? 443 : 80;
        $return = $this->httpRequest($host, $port);
        if (!empty($return)) {
            //$this->errMessage($return);
            $retData = json_decode($return, true);
            if ($retData) {
                if (isset($retData['error'])) {
                    die($this->oauthError($retData));
                }
				print_r($retData);
                echo "验证码:{$retData['user_code']}\r\n验证地址:{$retData['verification_url']}\r\n二维码地址:{$retData['qrcode_url']}\r\n";
            } else {
                return $this->errMessage($return);
            }
        } else {
            return $this->errMessage('Network Error');
        }
        $this->errMessage('请根据上面输出的验证码，前往验证地址或二维码地址进行授权');
        $tokenQuery = http_build_query(array('grant_type' => 'device_token',
            'code' => $retData['device_code'],
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::SECRET_KEY));

        $this->url = "/oauth/2.0/token?{$tokenQuery}";
        $expires = time() + $retData['expires_in'];
        $inter = $retData['interval'] + 1;
        while (true) {
            if (time() > $expires) {
                return $this->errMessage('用户验证码过期');
            }
            $this->errMessage('等待授权');
            sleep($inter);
            $ret = $this->httpRequest($host, $port);
            if (!empty($ret)) {
                $this->errMessage($ret);
                $retData = json_decode($ret, true);
                if (isset($retData['error'])) {
                    if ($retData['error'] == 'authorization_pending') {
                        continue;
                    }
                    if ($retData['error'] == 'slow_down') {
                        sleep($inter);
                        continue;
                    }
                    return $this->oauthError($retData);
                }
                $this->accessToken = $retData['access_token'];
                file_put_contents($this->oauth_file, $ret);
                $this->errMessage('授权成功');
                return;
            } else {
                return $this->errMessage('Error');
            }
        }
    }

    public function oauthError($retData) {
        return $this->errMessage("{$retData['error']}:{$retData['error_description']}");
    }

    public function errMessage($str) {
        echo "$str\n";
    }

    public function httpRequest($hostname, $port) {
        $errno = $errstr = 0;
        $sock = fsockopen($this->transprot . $hostname, $port, $errno, $errstr, 5);
        if ($sock) {
            $this->errMessage("Request:$hostname");
            $header = "GET {$this->url} HTTP/1.1\r\n";
            $header .= "Host: {$hostname}\r\n";
            $header .= "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:1.0) MyBaiduClient\r\n";
            $header .= "Accept:text/plain，text/html；q=0.8\r\n";
            $header .= "\r\n";
            fwrite($sock, $header, strlen($header));
            $response = stream_get_contents($sock);
            fclose($sock);
			$chunked = FALSE;
            if (!empty($response)) {
                list($rheader, $rbody) = explode("\r\n\r\n", $response);
                $rheaderList = explode("\r\n", $rheader);
                $resStatus = explode(' ', $rheaderList[0]);
				foreach($rheaderList as $headerItem) {
					if(strtolower(trim($headerItem)) == 'transfer-encoding: chunked') {
						$chunked = true;
					}
				}
                if ($resStatus[1] != 200) {
                    $this->errMessage($rheader);
                    return false;
                } else {
					if($chunked) {
						$chunkList = explode("\r\n", $rbody);
						$rbody = '';
						foreach($chunkList as $k=>$block) {
							if($k%2 == 0 && $block == '0') {
								break;
							} elseif($k%2 == 1) {
								$rbody .= $block;
							}
						}
					}
                    $this->errMessage($rheaderList[0]);
                    return $rbody;
                }
            }
        } else {
            $this->errMessage("$errstr($errno)");
        }
    }

    public function httpPost($hostname, $port, $filepath, $upfile = true) {
        $errno = $errstr = 0;
        $sock = fsockopen($this->transprot . $hostname, $port, $errno, $errstr, 5);
        if ($sock) {
            $this->errMessage('SSL connect OK');
            $boundaryStr = '------------' . md5(time() . $filepath . $hostname);
            $header = "POST {$this->url} HTTP/1.1\r\n";
            $header .= "Host: {$hostname}\r\n";
            $header .= "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:1.0) MyBaiduClient\r\n";
            $header .= "Accept: */*\r\n";
            $header .= "Content-Type: multipart/form-data; boundary={$boundaryStr}\r\n";


            $endheader = "\r\n{$boundaryStr}--";
            $endHeaderLength = strlen($endheader);

            if ($upfile === false) {
                $bodyheader = "{$boundaryStr}\r\n";
                $bodyheader .= "Content-Disposition: form-data; name=param\r\n";
                $bodyheader .= "\r\n";
                $bodyheader .= "$filepath";

                $bodyLength = strlen($bodyheader) + $endHeaderLength;
                $header .= "Content-length: {$bodyLength}\r\n";
                $header .= "\r\n";
                fwrite($sock, $header, strlen($header));

                fwrite($sock, $bodyheader, strlen($bodyheader));
                $writeOk = true;
            } else {
                $bodyheader = "{$boundaryStr}\r\n";
                $bodyheader .= "Content-Type: application/octet-stream\r\n";
                $bodyheader .= "Content-Transfer-Encoding: binary\r\n";
                $bodyheader .= "Content-Disposition: form-data; name=file; filename={$filepath}\r\n";
                $bodyheader .= "\r\n";

                $currentSize = $this->filesize - $this->uploadSize;
                if ($currentSize < $this->splitSize) {
                    $fileDataLength = $currentSize;
                } else {
                    $fileDataLength = $this->splitSize;
                }
                $bodyLength = strlen($bodyheader) + $endHeaderLength + $fileDataLength;
                $header .= "Content-length: {$bodyLength}\r\n";
                $header .= "\r\n";
                fwrite($sock, $header, strlen($header));

                $this->errMessage('start send file');
                fwrite($sock, $bodyheader, strlen($bodyheader));
                if (!$this->ffp) {
                    $this->ffp = fopen($filepath, 'rb');
                }
                $readSize = 1024;
                $sendSize = $writeOk = 0;
                $c = 1;
                while (!feof($this->ffp)) {
                    $fileData = '';
                    $i = 0;
                    while (!feof($this->ffp) && $i <= 5) {
                        $fileData .= fread($this->ffp, $readSize);
                        $i++;
                    }
                    if ($sock) {
                        $rReadSize = strlen($fileData);
                        if (!fwrite($sock, $fileData, $rReadSize)) {
                            $this->writeStatus = 'reset';
                            fseek($this->ffp, $currentSize);
                            return false;
                        }
                        $sendSize += $rReadSize;
                        $per = round($sendSize / $fileDataLength, 2) * 100;
                        if ($per >= 10 * $c) {
                            $this->errMessage("$per%");
                            $c++;
                        }

                        $writeOk = true;
                        if ($sendSize + $readSize > $this->splitSize) {
                            break;
                        }
                    }
                }
                $this->uploadSize += $sendSize;
                $this->errMessage('File Send Ok');
            }
            if ($writeOk) {
                fwrite($sock, $endheader, strlen($endheader));
                $this->errMessage('End line');
            }
            $response = stream_get_contents($sock);
            fclose($sock);

            if (!empty($response)) {
                list($rheader, $rbody) = explode("\r\n\r\n", $response);
                $rheaderList = explode("\r\n", $rheader);
                $resStatus = explode(' ', $rheaderList[0]);
                if ($resStatus[1] != 200) {
                    $this->errMessage($rheader);
                    return false;
                } else {
                    $this->errMessage($rheaderList[0]);
                    return $rbody;
                }
            }
        } else {
            $this->errMessage("$errstr($errno)");
        }
    }

    public function __destory() {
        curl_close($this->ch);
    }

}

return new BaiduPCS($argv, $argc);
