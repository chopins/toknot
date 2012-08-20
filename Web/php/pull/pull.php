<?php
class pull extends X {
    //签名KEY
    private $cert = '-----BEGIN PUBLIC KEY----- 
        MIIBtjCCASsGByqGSM44BAEwggEeAoGBAK6/KXtmuShgMjG7bc7YKuA96sSgfG2g                
        OqWnt1g9UG6pr6i8phXbCel8xzce/eHcIPriVPXi5oobPMXNmWA8Tmvygx9ca89G                
        C8bMNV3xYYf8jaNY5jsWGQIk4UrOI4QelUCpSStcy5lAh5XGM/aYUQrkU6RCUNf7                
        C10kUEaHLEyzAhUA29d2GdW+IaEPiAmWMCmtpoMc850CgYBq63WZkL+sqw7F5wTG                
        tN/crd016j7m8WOE+gU08v8swTkhYkLZM/otNaV4142e98amO3iG7F1exp2oETS9                
        f9Wvlnp6BAKH+59oBNS7SDN/DAyVDjVT0uwI3rYmKwaVQRrSzELIWeiFa71EuNto                
        2sWRCvhizMHeOGXHo5MyxARWJQOBhAACgYAHgCfB2i2gyZ7uTxjNVREzEwSVz0T9                
        9rW+LvCylX4qBUHsJJxgtpuOLIgHIuHCFwn3acjPW+3yyvTJ2TdvmmLHUPewQgQh                
        7hTtjrvGrNOPO5T55172Rya4BBeOS9If5zgqthiZgkQM9rszex5GJ9fFElAinEBz                
        U5cHoK0iDFcO9g==                                                                
        -----END PUBLIC KEY-----
        ';
    private function getData() {
        $data = file_get_contents("php://input", 'r');
        if(empty($data)) return 1; //没有数据
        if(empty($_SERVER['PHP_AUTH_DIGEST'])) return 2;  //没有签名
        $sigin = $_SERVER['PHP_AUTH_DIGEST'];
        $server = $_SERVER['PHP_AUTH_USER'];
        $time = $_SERVER['PHP_AUTH_PW'];
        if(time() - $time > 120) return 4; //数据过期
        if($sigin != md5($data.$server.$date)) {
            return 3;  //数据不完整
        }
        $data = base64_decode($data);
        $res = openssl_pkey_get_public($this->cert);
        $data = openssl_public_decrypt($data, $dedata, $res);
        if($data === true) return unserialize($data);
        return 5;  //数据错误
    }
    //代码更新推送
    public function Pupate() {
    }
    //服务器环境创建
    public function Pcreate() {
    }
    //服务器配置文件修改
    public function Pconf() {
    }
    public fun 

}
