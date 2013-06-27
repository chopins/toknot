<?php

class B {

	const D = 'test';

	private $a = 'my test';
	static $s = 'my static';

	public function p() {
		var_dump(self::D);
	}

}

class A {
	const B = 3;
	var $s = null;
	public function __construct() {
		session_set_save_handler(array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc'));
	}
	public function open() {
		$this->s = new B;
	}
	public function start() {
		session_start();
	}
	public function close() {
	}

	public function read() {
	}
	public function destroy() {
	}
	public function gc() {
	}
	public function write() {
		$this->s->p();
	}
}

$obj = new A;
$obj->start();