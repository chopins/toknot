<?php
class XSVNClient {
    private $socket = '';
    private $errno;
    private $errstr;
    private $server_data_dir;
    private $server_url;
    private $local_dir;
    private $repos_name = null;
    public function __construct() {
        $this->load_cfg();
        dl_extension('svn', 'svn_checkout');
   //     $this->deamon();
    }
    public function set_repos_name($name) {
        $this->repos_name = $name;
    }
    public function repos_list() {
        return scandir($this->server_data_dir);
    }
    public function ls($dir = '/') {
        return svn_ls($this->server_url.'/'.$this->repos_name.$dir);
    }
    public function checkout() {
        return svn_checkout($this->server_url.$this->repos_name, 
                        $this->local_dir.'/'.$this->repos_name);
    }
    public function worker_revision() {
        $info = svn_info($this->local_dir.'/'.$this->repos_name, false);
        return $info[0];
    }
    public function update($filepath) {
        return = svn_update($this->local_dir.'/'.$this->repos_name.$filepath);
    }
    public function update_all() {
        return svn_update($this->local_dir.'/'.$this->repos_name);
    }
    public function status() {
        return svn_status($this->local_dir.'/'.$this->repos_name);
    }
    public function logs($path = '/') {
        return svn_log($this->server_url.'/'.$this->repos_name.$path);
    }
    private function load_cfg($_CFG=null) {
        if($_CFG === null) {
            global $_CFG;
        }
        $this->server_url = $_CFG->svn->server_url;
        $this->server_data_dir = $_CFG->svn->server_data_dir;
        $this->local_dir = $_CFG->svn->local_dir;
        return;
    }
}
