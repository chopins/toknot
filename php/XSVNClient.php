<?php
class XSVNClient {
    private $socket = '';
    private $errno;
    private $errstr;
    public function __construct() {
        $this->load_cfg();
        dl_extension('svn', 'svn_checkout');
        $this->deamon();
    }
    private function load_cfg($_CFG=null) {
        if($_CFG === null) {
            global $_CFG;
        }
        if(strtolower($_CFG->svn->protocol) != 'unix') throw new XException('XSVNClient deamon only support unix:// transports');
        $this->run_dir = empty($_CFG->run_dir) ?
            __X_APP_ROOT__."/{$_CFG->data_dir_name}/run": $_CFG->run_dir;
        $sock = empty($_CFG->svn->socket) ? 'xsvn.sock' : $_CFG->svn->socket;
        if(!is_dir(dirname($sock))) throw new XException(dirname($sock).' not exists');
        $this->socket = "{$_CFG->svn->protocol}://{$sock}";
    
    }
    public function deamon() {
        $local_socket = $this->get_loacl_socket();
        $this->server = stream_socket_server($this->socket,$this->errno, $this->errstr);
        stream_set_blocking($this->server,1);
            while($connect = stream_socket_accept($this->server,-1,$peername)) {
            }
        }
    }
}
