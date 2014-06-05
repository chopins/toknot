<?php
class t {
    const B =1;
    public function __construct() {
        var_dump(defined('self::B'));
    }
}

new t;

