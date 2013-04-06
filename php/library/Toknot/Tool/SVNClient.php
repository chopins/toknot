<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Toknot\Tool;

class SVNClient {
    /**
     * socket 
     * 
     * @var resource
     * @access private
     */
    private $socket;
    /**
     * errno 
     * 
     * @var int
     * @access private
     */
    private $errno;
    /**
     * errstr 
     * 
     * @var string
     * @access private
     */
    private $errstr;
    /**
     * server_data_dir 
     * 
     * @var string
     * @access private
     */
    private $server_data_dir;
    /**
     * server_url 
     * 
     * @var mixed
     * @access private
     */
    private $server_url;
    /**
     * local_dir 
     * 
     * @var mixed
     * @access private
     */
    private $local_dir;
    /**
     * repos_name 
     * 
     * @var mixed
     * @access private
     */
    private $repos_name = null;
    /**
     * config_list 
     * 
     * @var array
     * @access private
     */
    private $config_list = array();
    /**
     * __construct 
     * 
     * @param mixed $config_file 
     * @access public
     * @return void
     */
    public function __construct($config_file) {
        $this->load_cfg($config_file);
        dl_extension('svn', 'svn_checkout');
   //     $this->deamon();
    }

    /**
     * set_repos_name 
     * 
     * @param mixed $name 
     * @access public
     * @return void
     */
    public function set_repos_name($name) {
        $this->repos_name = $name;
    }

    /**
     * repos_list 
     * 
     * @access public
     * @return array
     */
    public function repos_list() {
        $repos_list = array();
        $arr = scandir($this->server_data_dir);
        $k = 0;
        foreach($arr as $repos_name) {
            if($repos_name == '.' || $repos_name == '..') continue;
            $repos_info = svn_info($this->local_dir.'/'.$repos_name, false);
            $repos_list[$k] = $repos_info[0];
        }
        return $repos_list;
    }
    /**
     * ls 
     * 
     * @param string $dir 
     * @access public
     * @return void
     */
    public function ls($dir = '/') {
        return svn_ls($this->server_url.'/'.$this->repos_name.$dir);
    }
    /**
     * checkout 
     * 
     * @access public
     * @return boolean
     */
    public function checkout() {
        return svn_checkout($this->server_url.'/'.$this->repos_name, 
                        $this->local_dir.'/'.$this->repos_name);
    }
    /**
     * worker_revision 
     * 
     * @access public
     * @return int
     */
    public function worker_revision() {
        $info = svn_info($this->local_dir.'/'.$this->repos_name, false);
        return $info[0]['revision'];
    }
    /**
     * update 
     * 
     * @param mixed $filepath 
     * @access public
     * @return boolean
     */
    public function update($filepath) {
        return svn_update($this->local_dir.'/'.$this->repos_name.$filepath);
    }
    /**
     * change_list 
     * get server repository lastest to local woker revision change log list
     * 
     * @access public
     * @return array
     */
    public function change_list() {
        $local_revision = $this->worker_revision();
        $log_list = svn_log($this->server_url.'/'.$this->repos_name,SVN_REVISION_HEAD,
                            $local_revision);
        return $log_list;
    }
    /**
     * update_all 
     * 
     * @access public
     * @return boolean
     */
    public function update_all() {
        return svn_update($this->local_dir.'/'.$this->repos_name);
    }
    /**
     * status 
     * 
     * @access public
     * @return array
     */
    public function status() {
        return svn_status($this->local_dir.'/'.$this->repos_name);
    }
    /**
     * logs 
     * 
     * @param string $path 
     * @access public
     * @return array
     */
    public function logs($path = '/') {
        return svn_log($this->server_url.'/'.$this->repos_name.$path);
    }
    /**
     * use_confg 
     * 
     * @param mixed $idx 
     * @access public
     * @return void
     */
    public function use_confg($idx) {
        $this->server_url = $this->config_list[$idx]['server_url'];
        $this->server_data_dir = $this->config_list[$idx]['repos_path'];
        $this->local_dir = $this->config_list[$idx]['worker_path'];
    }
    /**
     * load_cfg 
     * 
     * @param mixed $config_file 
     * @access private
     * @return void
     */
    private function load_cfg($config_file) {
        $config_file = __X_APP_DATA_DIR__."/conf/{$config_file}";
        if(!file_exists($config_file)) {
            throw new XException("{$config_file} not exists");
        }
        $this->config_list = XConfig::parse_ini($config_file);
        return;
    }
}
