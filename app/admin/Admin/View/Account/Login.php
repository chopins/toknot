<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Account;

use Toknot\Share\View\View;

class Login extends View {

    public function page() {
        $this->contanier();
    }
    public function contanier() {
        $bodyContaier = $this->div($this->body,
                ['class' => 'box-center', 'style' => 'width:366px;text-align: center;']);
        $form = $this->form($bodyContaier,
                ['class' => 'pure-form pure-form-stacked', 'method' => 'post', 'action' => $this->param['login'],
            'input' => ['login-name' => ['value' => '', 'id' => 'login_name', 'type' => 'text', 'placeholder' => '用户名/邮件/手机号', 'label' => ''],
                'password' => ['value' => '', 'type' => 'password', 'id' => 'password', 'placeholder' => '登陆密码', 'label' => ''],
                    ['type' => 'submit', 'value' => '登陆', 'class' => 'pure-button pure-button-primary'],
        ]]);
        $this->enableCsrf($form);
        $this->a($form,
                ['class' => 'pure-button', 'href' => $this->param['signup']])->pushText('注册');
    }

}
