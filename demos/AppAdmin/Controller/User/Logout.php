<?php
namespace AppAdmin\Controller\User;
use Toknot\Admin\Login;
class Logout extends Login {
	public function GET() {
		parent::logout();	
		self::$FMAI->redirectController('\User\Login');
	}
}