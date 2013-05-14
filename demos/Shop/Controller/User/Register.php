<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Shop\View\User;
use Shop\ShopContext;

class Register extends ShopContext {
    public function GET($appContext) {
        $appContext->D->hashkey = '11222';
        $appContext->display('register');
    }
    public function POST($appContext) {
        $postData = $_POST;
        $db =  new ActiveRecord;
        $db->user->importPropertie($postData);
        $db->user->save();
    }
}