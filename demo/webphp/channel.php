<?php
class channel extends X {
    public function Tindex() {
        header('HTTP/1.1 101 Switching Protocols');
        header('Upgrade: websocket');
        header('Connection: Upgrade');
        $this->setKey();
        header('Sec-WebSocket-Prototcol: epoll');
        header('WebSocket-Origin: http://phpframe');
        header('WebSocket-Location: http://phpframe:80/epoll');

    }
    public function Pcall() {
        $act = $this->R->P->act->value;
        $func = basename($act);
        if(empty($func)) $func = 'index';
        $view_class = dirname($act);
        if(empty($view_class) || $view_class == '/') $view_class = 'index';
        $cv_ins = $this->CV($view_class);
        if(method_exists($cv_ins,$func)) {
            $cv_ins->$func();
        } else {
            $this->exit_json(0,'404','Not Found');
        }

    }
    public function Ppoll() {
        $this->Hpoll();
    }
    public function Hpoll() {
        if($this->CV('index')->checklogin()) {
            $timeout = 10;
            $data_file = __X_APP_DATA_DIR__.'/cache/DATA_CHANGE_FLAG';
            $client_modified_time = $_SERVER['HTTP_CLIENT_MODIFIED'];
            $sleep_time = 0;
            $last_time = 0;
            while(true) {
                sleep(1);
                $sleep_time++;
                if($sleep_time >= $timeout) break;
                if(!file_exists($data_file)) {
                    continue;
                }
                $last_time = filemtime($data_file);
                if($last_time > $client_modified_time) {
                    break;
                }
            }
            header("Data-Modified:{$last_time}");
        } else {
            header("Authorization:nologin");
        }
        header("EPOLL:true");
    }

}
