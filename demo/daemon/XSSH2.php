<?php

/**
 * Toknot
 *
 * XSSH2
 *
 * PHP version 5.3
 * 
 * @package XDataStruct
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release $id$
 */
/**
 * XSSH2 
 * 
 * @package 
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
class XSSH2 {
    private $host = 0;
    private $port = 22;
    private $ssh_con = null;
    private $ssh_server_fp = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    private $keyfile_dir = null;
    private $ssh_user = 'sysnc';
    private $ssh_auth_pass = null;
    private $sftp = null;
    private $last_error = null;
    private $auth_type = 1; // 1 is username and password, 0 is auth_pub_file
    public function __construct($_CFG = null) {
        $this->host = $_CFG->host;
        $this->port = $_CFG->port;
        $this->keyfile_dir - $_CFG->auth_pub_dir;
        $this->ssh_user = $_CFG->ssh_user;
    }
    public function connect() {
        $methods = array('hostkey'=>'ssh-rsa');
        $this->ssh_con = ssh2_connect($this->host,$this->port);
        if(!$this->ssh_con) {
            throw new XException('cannot connect to ssh server');
        }
        $fingerprint = ssh2_fingerprint($this->ssh_con, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
        if (strcmp($this->ssh_server_fp, $fingerprint) !== 0) {
            throw new XException('Unable to verify server identity!');
        }
        if($this->auth_type) {
            ssh2_auth_password($this->ssh_con,$this->ssh_user,$this->ssh_auth_pass);
        } else {
            $this->auth_pub_file();
        }
    }
    public function cmd($cmd) {
        if(!($cmd_stream = ssh2_exec($this->ssh_con,$cmd))) {
            throw new XException('SSH exec command '.$cmd.' failure');
        }
        $stderr = ssh2_fetch_stream($cmd_stream, SSH2_STREAM_STDERR);
        stream_set_blocking($cmd_stream, true);
        $data = stream_get_contents($cmd_stream);
        fclose($stream);
        $this->last_error = stream_get_contents($stderr);
        fclose($stderr);
        return $data;
    }
    public function get_last_error() {
        return $this->last_error;
    }
    public function mkdir($path) {
        if(!$this->check_file_exists($path)) {
            $result = ssh2_sftp_mkdir($this->sftp, $path,0755,true);
        }
        return true;
    }
    public function create_sftp() {
        $this->sftp = ssh2_sftp($this->ssh_con);
    }
    public function sftp_file_exists($path) {
        $result = file_exists("ssh2.sftp://{$this->sftp}/{$path}");
       return $result;
    }
    public function sftp_ls($path) {
        return scandir("ssh2.sftp://{$this->sftp}/{$path}");
    }
    public function  sftp_is_dir($path) {
        return is_dir("ssh2.sftp://{$this->sftp}/{$path}");
    }
    public function rm($path) {
        if($this->sftp_is_dir($path)) {
            $file_list = $this->sftp_ls($path);
            foreach($file_list as $file) {
                if($file == '.' || $file == '..') continue;
                $this->delete_file($file);
            }
            return ssh2_sftp_rmdir($this->sftp,$path);
        } else {
            return ssh2_sftp_unlink($this->sftp, $path);
        }
    }
    public function sendfile($local_file, $remote_file,$mode) {
        $dir = dirname($remote_file);
        $this->mkdir($dir);
        return ssh2_scp_send($this->ssh_con,$local_file,$remote_file,$mode);
    }
    public function disconnect() {
        $stream = ssh2_exec($this->ssh_con,'exit;');
        $this->ssh_con = null;
    }
    public function auth_pub_file() {
        $ssh_auth_pub = $this->keyfile_dir.'/'.$this->host.'.id_rsa.pub';
        $ssh_auth_priv = $this->keyfile_dir . '/'. $this->host.'.id_rsa';
        if (!ssh2_auth_pubkey_file($this->ssh_con, $this->ssh_user,
                $ssh_auth_pub, $ssh_auth_priv, $this->ssh_auth_pass)) {
                    throw new XException('Autentication rejected by server');
            }
    }
    public function __destruct() {
        $this->disconnect();
    }
}
