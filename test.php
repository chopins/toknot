<?php 
namespace k;
trait a {
    protected function b() {
        echo 'a:';
        var_dump($this);
    }
}

class c {
    use a;
    public function b() {
        echo 'c:';
        var_dump($this);
    }
    public function e() {
        $this->b();
    }
}

$d = new c;
$d->e();