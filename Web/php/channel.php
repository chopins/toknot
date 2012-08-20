<?php
class channel extends X {
    public function tIndex() {
        header('HTTP/1.1 101 Switching Protocols');
        header('Upgrade: websocket');
        header('Connection: Upgrade');
        $this->setKey();
        header('Sec-WebSocket-Prototcol: epoll');
        header('WebSocket-Origin: http://phpframe');
        header('WebSocket-Location: http://phpframe:80/epoll');

    }
    public function pCall() {
        $act = $this->R->P->act->value;
        $func = basename($act);
        if(empty($func)) $func = 'index';
        $view_class = dirname($act);
        if(empty($view_class) || $view_class == '/') $view_class = 'home';
        $view_class = ltrim($view_class,'/');
        $cv_ins = $this->CV("/action/$view_class");
        if(method_exists($cv_ins,$func)) {
            $cv_ins->$func();
        } else {
            $this->exit_json(0,'404','Not Found');
        }

    }
    public function pGet() {
        $op = $this->LM('opreateRecord');
        $client_modified_time = (int)$this->R->P->client_modified_time;
        $data = $op->pull_modified($client_modified_time);
        $this->exit_json(1,$client_modified_time,$data);
    }
    public function pPoll() {
        $this->hPoll();
    }
    public function hPoll() {
        if($this->CV('index')->checklogin()) {
            $op = $this->LM('opreateRecord');
            $timeout = 20;
            $client_modified_time = $_SERVER['HTTP_CLIENT_MODIFIED'];
            $sleep_time = 0;
            $last_time = 0;
            while(true) {
                sleep(1);
                $sleep_time++;
                if($sleep_time >= $timeout) break;
                $last_time = $op->check_modified();
                session_write_close();
                if($last_time > $client_modified_time) {
                    break;
                }
            }
            header("Data-Modified:{$last_time}");
        } else {
            header("Authorization:nologin");
        }
        header("Update-Channel:Call");
    }

}
