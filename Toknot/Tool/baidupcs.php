#!/bin/env php
<?php

class BaiduPCS {

    private $transprot = 'https://';
    private $accessToken = '';
    private $url = '';
    private $ch = null;
    private $oauth_file = '';

    const CLIENT_ID = 'mvC5MuV9kduAabHYXGku5VSF';
    const SECRET_KEY = 'cDiWiGkuLfB3siK4dyQZiWWlWOGPLrp5';

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
        upload [option] localpath remote
            option:
                  -o 
                  -n
                  -p
                  -l
        download [option] remote localpath
             option:
                  -o
                  -n
        quota   
        mkdir
        mv
        rm
        cp
        ls
            option:
                -l
                -r
EOF;
        echo "\r\n";
    }

    public function httpInit() {
        $this->ch = curl_init();
        $set = array(CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_DNS_USE_GLOBAL_CACHE => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_LOW_SPEED_LIMIT => 256,
            CURLOPT_LOW_SPEED_TIME => 5
        );
        curl_setopt_array($this->ch, $set);
        if (file_exists('~/.baidupcs')) {
            $oauth = file_get_contents($this->oauth_file);
            $ret = json_decode($oauth, true);
            $mtime = filemtime($this->oauth_file) + $ret['expires_in'];
            if ($mtime < time()) {
                return $this->oauth();
            }
            $this->accessToken = $ret['access_token'];
        } else {
            $this->oauth();
        }
    }

    public function quota() {
        $this->httpInit();
        $url = "{$this->transprot}pcs.baidu.com";
        $query = http_build_query(array('method' => 'info', 'access_token' => $this->accessToken));
        $this->url = "{$url}/rest/2.0/pcs/quota?{$query}";
        $ret = $this->httpRequest();
        print($ret);
    }

    public function doUpload($localfile, $remotepath, $option) {
        $this->httpInit();
        $localfile = realpath($localfile);
        if (!$localfile) {
            return $this->errMessage("{$localfile}文件不存在");
        }

        if (isset($option['-o'])) {
            $ondup = 'overwrite';
        } else {
            $ondup = 'newcopy';
        }

        $url = "{$this->transprot}c.pcs.baidu.com";

        $queryParamString = http_build_query(array('method' => 'upload',
            'access_token' => $this->accessToken,
            'path' => $remotepath,
            'ondup' => $ondup
        ));
        $this->url = "{$url}/rest/2.0/pcs/file?{$queryParamString}";
        curl_setopt($this->ch, CURLOPT_UPLOAD, 1);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        $filesize = filesize($localfile);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Content-length: {$filesize}"));
        $formdata = array('name' => 'file', 'file' => "@{$localfile}");
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $formdata);
    }

    public function oauth() {
        $queryParamString = http_build_query(array('client_id' => self::CLIENT_ID,
            'response_type' => 'device_code',
            'scope' => 'basic,netdisk'
        ));
        $url = "{$this->transprot}openapi.baidu.com";
        $this->url = "{$url}/oauth/2.0/device/code?{$queryParamString}";
        $return = $this->httpRequest();

        if (!empty($return)) {
            $this->errMessage($return);
            $retData = json_decode($return, true);
            if ($retData) {
                if (isset($retData['error'])) {
                    die($this->oauthError($retData));
                }
                echo "验证码:{$retData['user_code']}\r\n验证地址:{$retData['verification_url']}\r\n二维码地址:{$retData['qrcode_url']}\r\n";
            } else {
                return $this->errMessage('Error');
            }
        } else {
            return $this->errMessage('Error');
        }
        $this->errMessage('请根据上面输出的验证码，前往验证地址或二维码地址进行授权');
        $tokenQuery = http_build_query(array('grant_type' => 'device_token',
            'code' => $retData['device_code'],
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::SECRET_KEY));
        $this->url = "{$url}/oauth/2.0/token?{$tokenQuery}";
        $expires = time() + $retData['expires_in'];
        $inter = $retData['interval'] + 1;
        while (true) {
            if (time() > $expires) {
                return $this->errMessage('用户验证码过期');
            }
            $this->errMessage('等待授权');
            sleep($inter);
            $ret = $this->httpRequest();
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

    public function httpRequest() {
        curl_setopt($this->ch, CURLOPT_URL, $this->url);
        return curl_exec($this->ch);
    }

    public function __destory() {
        curl_close($this->ch);
    }

}

return new BaiduPCS($argv, $argc);