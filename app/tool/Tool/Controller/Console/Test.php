<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;


/**
 * Test
 *
 */
class Test {

    public $cmd;


    /**
     * @console test
     */
    public function __construct() {
      $s = new \Toknot\Share\RobotSpot;
      //echo $s->number2zh(4.4) . PHP_EOL;
      echo $s->findOrder($result) . PHP_EOL;
      var_dump($result);
      echo $s->calculation($result) . PHP_EOL;
      var_dump($result);
    }

    public function song($songlist) {

        foreach ($songlist as $s) {
            $songName = $s['songName'];
            $allaudios = $s['allAudios'];
            
            foreach ($allaudios as $audio) {
                if($audio['format'] == 'mp3') {
                    $url = $audio['filePath'];
                    $this->cmd->message($url);
                    $this->mpg123($url);
                    break;
                }
            }
            
            
            
            //$this->mpg123($url);
        }
    }

    public function mpg123($url) {
        exec("/usr/bin/mpg123 $url", $output, $ret);
    }

    public function findPlaylist($id) {
        $ts = $this->rand();
        $url = "http://www.xiami.com/song/playlist-default/cat/json?_ksTS=$ts";

        $http = new \Toknot\Share\HttpTool($url, 'GET');
        $v = $this->playUrl($id);
        $http->pushCookie('XMPLAYER_url', $v);
        $http->pushCookie('_unsign_token', '314f6e5dec26cc1a3ecb40ea189813f8');
        $http->pushCookie('_xiamitoken', 'a60e4f52d51045dda3abb735c34ff217');
        $http->pushCookie('gid', '148972252083612');
        $cookie = $http->buildCookie();
        $http->addCookie($cookie);

        $res = json_decode($http->getPage(), true);
        if ($res['status']) {
            return $res['data']['trackList'];
        } else {
            throw new \Toknot\Exception\BaseException($res['message']);
        }
    }

    public function playUrl($id) {
        //1792702528,1774490672,1795263120,1792567930,1792541433,1774321215,1792568090,1774078768,1774917404,1792621117,1775713590,1774191999,1775751240,1775713592,1795540323,1774946504,1774998780,1792701804,1795428369,1776204257,1792724108,1792568097,1776429386,1792568093,1776080300
        return "/song/playlist/id/$id/object_name/default/object_id/0";
    }

    public function rand() {
        list($mec, $sec) = explode(' ', microtime());
        $mec = ceil($mec * 100);
        return "{$sec}_{$mec}";
    }

}
