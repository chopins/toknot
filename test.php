<?php
class A {
    private static function who() {
        echo __CLASS__;
    }

    private static function test() {
        self::who();
    }
}
class B extends A {
    public static function who() : int {
        echo __CLASS__;
    }
}

B::who();
