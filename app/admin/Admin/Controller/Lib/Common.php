<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\Controller\Lib;

use Toknot\Share\Controller;
use Toknot\Boot\Tookit;

/**
 * Description of Common
 *
 * @author chopin
 */
class Common extends Controller {

    public function __construct() {
        $this->setMenu();
    }

    public function setMenu() {
        $left = ['account' => ['个人', 'fa-user', ['id' => 111]], 'trend' => ['动态', 'fa-rss'], 'event' => ['事件', 'fa-tasks'], 'project' => ['项目', 'fa-book'],
            'org' => ['组织', 'fa-group'], 'discussion' => ['讨论', 'fa-comments-o'], 'doc' => ['文档', 'fa-file-text']];
        $leftUrl = $this->addMenuItem($left);

        $header = ['user-profile' => '昵称', 'message' => ['消息', 'fa-bell'],
            'user-setting' => ['设置', 'fa-gear'], 'day' => ['日历', 'fa-calendar'], 'logout' => ['退出', 'fa-sign-out']];
        $headerUrl = $this->addMenuItem($header);
        $this->v->leftMenu = $leftUrl;
        $this->v->headerMenu = $headerUrl;
    }

    public function addMenuItem($menuConfig) {
        $menuList = [];
        foreach ($menuConfig as $route => $title) {
            if (is_array($title)) {
                $params = Controller::coalesce($title, 2, []);
                $url = $this->url($route, $params);
                $menuList[$route] = [$title[0], $url, $title[1]];
            } else {
                $url = $this->url($route);
                $menuList[$route] = [$title, $url];
            }
        }
        return $menuList;
    }

}
