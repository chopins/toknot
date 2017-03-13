<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Account;

use Admin\View\Lib\BaseView;

class Signup extends BaseView {

    public function buildFrame() {
        
    }
    public function contanier() {
        $bodyContaier = $this->div($this->body,
                ['class' => 'box-center', 'style' => 'width:366px;text-align: center;']);
        $form = $this->form($bodyContaier,
                ['class' => 'pure-form pure-form-stacked', 'method' => 'post', 'action' => $this->param['signup'],
            'input' => ['login-name' => ['value' => '', 'id' => 'login_name', 'type' => 'text', 'placeholder' => '用户名', 'label' => ''],
                'password' => ['value' => '', 'type' => 'password', 'id' => 'password', 'placeholder' => '登陆密码', 'label' => ''],
                'password-verify' => ['value' => '', 'type' => 'password', 'id' => 'password-verify', 'placeholder' => '确认登陆密码', 'label' => ''],
                'email' => ['value' => '', 'id' => 'email', 'type' => 'email', 'placeholder' => '邮件', 'label' => ''],
                'mobile' => ['value' => '', 'id' => 'mobile', 'type' => 'text', 'placeholder' => '手机号', 'label' => ''],
                    ['type' => 'submit', 'value' => '注册', 'class' => 'pure-button pure-button-primary'],
            ]
        ]);
        $this->enableCsrf($form);
        $this->a($form,
                ['class' => 'pure-button', 'href' => $this->param['login']])->pushText('已有帐号登陆');
    }

}
