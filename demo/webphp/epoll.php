<?php
class epoll extends X {
    public function Gindex() {
        header('HTTP/1.1 101 Switching Protocols');
        header('Upgrade: websocket');
        header('Connection: Upgrade');
        $this->setKey();
        header('Sec-WebSocket-Prototcol: epoll');
        header('WebSocket-Origin: http://phpframe');
        header('WebSocket-Location: http://phpframe:80/epoll');

    }
    public function setKey() {
    }
}
