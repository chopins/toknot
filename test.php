<?php

class A {
    public function __construct() {
        echo 'a';
    }
}
$a = new A;
$b = function() {
    var_dump($this);
    echo 'b';
};
var_dump($b);
var_dump(is_object($b));
$bf = $b->bindTo($a);
$b->call($a);
