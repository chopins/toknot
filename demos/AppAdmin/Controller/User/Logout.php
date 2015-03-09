<?php
namespace AppAdmin\Controller\User;
use Toknot\Lib\Admin\Login;
class Logout extends Login {
	public function GET() {
		parent::logout();	
		$this->redirectController('\User\Login');
	}
}