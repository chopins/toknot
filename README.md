### About
ToKnot is a php framework

[http://toknot.com](http://toknot.com)

[![Latest Stable Version](https://poser.pugx.org/toknot/toknot/v/stable)](https://packagist.org/packages/toknot/toknot)
[![Latest Unstable Version](https://poser.pugx.org/toknot/toknot/v/unstable)](https://packagist.org/packages/toknot/toknot)
[![License](https://poser.pugx.org/toknot/toknot/license)](https://packagist.org/packages/toknot/toknot)
### License
The PHP framework is under New BSD License (http://toknot.com/LICENSE.txt)

The demos is under GNU GPL version 3 or later <http://opensource.org/licenses/gpl-3.0.html>

### Usage and Configure
On command line exec: `php vendor/toknot/initapp.php` App Init Guide build your app

On command line exec: `php app/tool/index.php` show tool app help message

在命令行中执行：`php vendor/toknot/initapp.php` 运行应用初始化向导，向导程序会创建应用基本目录及文件

在命令行中执行：`php app/tool/index.php` 显示tool应用帮助信息

* [Controller And Model usage](https://github.com/chopins/toknot/blob/master/vendor/toknot/doc/Controller-Model-Usage.md)
* [Main config](https://github.com/chopins/toknot/blob/master/vendor/toknot/doc/main-config-usage.md)  
* [router config](https://github.com/chopins/toknot/blob/master/vendor/toknot/doc/route-config.md)  
* [table config](https://github.com/chopins/toknot/blob/master/vendor/toknot/doc/table-config.md)  
* [view document](https://github.com/chopins/toknot/blob/master/vendor/toknot/doc/view.md) 

### Server Config
将所有请求都定向到index.php入口文件，以下是nginx与apache服务器配置方法
* nginx:
    ```conf
    location  / {
        root $dir/index.php;
    }
    ```

* apache:
    ```conf
    <Directory "/your-app-path/webroot">
        RewriteBase /
        RewriteRule .*  index.php
        RewriteEngine On
    </Directory>
    ```
* PHP CLI Web Server:
  ```
  php -S 127.0.0.1:8000 index.php
  ```
