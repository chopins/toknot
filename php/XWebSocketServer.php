<?PHP
/**
 * Toknot
 *
 * XWebSocketServer class
 *
 * PHP version 5.3
 * 
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release 0.1
 */

exists_frame();

/**
 * XWebSocketServer 
 * 
 * @uses XHTTPResponse
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
class XWebSocketServer extends XHTTPResponse {
    private $sock = null;
    public $ip = '127.0.0.1';
    public $port = '8181';
    public $errno;
    public $errstr;
    public function __construct() {
        $local_socket = $this->get_loacl_socket();
        $this->sock = stream_socket_server($local_socket
                                            $this->errno,$this->errstr);
        stream_set_blocking($this->server,1);
        $this->accept_loop();
    }
    private function get_loacl_socket() {
        return "tcp://{$this->ip}:{$this->port}";
    }
    public function accept_loop() {
        while(true) {
            $connect = stream_socket_accept($this->server, 0, $client_info);
            $this->client_server_process($connect);
        }
    }
    public function client_server_process($connect) {
        $pid = pcntl_fork();
        if($pid < 0) return;
        if($pid > 0) return;
        $this->get_request_header($connect);
    }
    public function set_header() { 
        header('HTTP/1.1 101 Switching Protocols');
        header('Upgrade: websocket');
        header('Connection: Upgrade');
        $key = sha1($_SERVER['HTTP_SEC_WEBSOCKET_KEY'] . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11");
        $key = base64_encode($key);
        header("Sec-WebSocket-Accept: $key");
        header('Sec-WebSocket-Prototcol: epoll');
        header('WebSocket-Origin: http://phpframe');
        header('WebSocket-Location: http://phpframe:80/epoll');

}
