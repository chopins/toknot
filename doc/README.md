#####[框架配置文件说明](https://github.com/chopins/toknot/blob/master/doc/%E6%A1%86%E6%9E%B6%E9%85%8D%E7%BD%AE%E6%96%87%E4%BB%B6%E8%AF%B4%E6%98%8E%28%E9%92%88%E5%AF%B93.0%29.md)

#####[快速开始](https://github.com/chopins/toknot/blob/master/doc/quickstart.first-app.mdown)

#####[使用数据库](https://github.com/chopins/toknot/blob/master/doc/use.database.mdown)

#####[自带模板语法](https://github.com/chopins/toknot/blob/master/doc/toknot-view-template-doc.mdown)

###自动加载规则
   框架提供自动加载功能，加载规则是按命名空间在指定目录中搜索与类同名文件，并加载  
   指定目录是指通过[Toknot\Boot\Autoloader::addPath()](http://toknot.com/toknot/class-Toknot.Boot.Autoloader.html)方法添加的路径，应用在被引导时会将框架所在路径与应用所在路径添加到搜索路径中去。因此对于应用命名空间下的类不需要手动包含
   命名空间转换成目录路径的规则是一层命名空间名字等于一个文件夹名字，例如：

        \MyApp\SubNS\TheClassName    这个类将转换成文件路径就是 /your-path/MyApp/SubNS/TheClassName.php
        \Toknot\Share\FMAI           这个类的转换路径为       /your-path/Toknot/Share/FMAI.php
        \TestApp\Lib\User     这个类转换路径为         /home/user/project/TestApp/Lib/User.php

   **由于这个规则，所以在创建应用时，应用所在文件夹的名字必须符合命名空间规范，并且与应用的顶级命名空间名字相同**  
   自动加载功能是大小写敏感的(与操作系统相关)  
   注意添加的搜索路径必在末尾包含一个顶级命名空间名字的文件夹，上面例子添加的路径应该如下:

```php
Toknot\Boot\Autoloader::addPath('/your-path/MyApp');
Toknot\Boot\Autoloader::addPath('/your-path/Toknot');
Toknot\Boot\Autoloader::addPath('/home/user/project/TestApp');
```
    
###ToKnot Type Object
ToKnot 增加了三种类型， 字符串对象，数组对象和文件对象

#####[Object对象说明](https://github.com/chopins/toknot/blob/master/doc/Object%E5%AF%B9%E8%B1%A1%E8%AF%B4%E6%98%8E.mdown)

#####字符串对象：[Toknot\Boot\StringObject](http://toknot.com/toknot/class-Toknot.Boot.StringObject.html)

    该对象支持大部分PHP标准字符串函数同名得静态方法，功能也一样
    该对象支持echo, 大部分时候可以当成字符串类型使用
    支持迭代器
    支持数组访问
    支持count函数

#####数组对象：[Toknot\Boot\ArrayObject](http://toknot.com/toknot/class-Toknot.Boot.ArrayObject.html)
    
    支持迭代器
    支持数组访问
    支持count函数
    
#####文件对象:[Toknot\Boot\FileObject](http://toknot.com/toknot/class-Toknot.Boot.FileObject.html)
    支持迭代器
    支持数组访问
    支持count函数

###路由器
#####[URL映射规则](https://github.com/chopins/toknot/blob/master/doc/toknot-url-mapping-doc.mdown)
在使用ROUTER_PATH模式时，路由器提供了两个比较有用的方法用来获取URI包含资源参数和指向资源类型，使用方法如下:

首先获取路由器当前实例:

```php
$router = $router = \Toknot\Boot\Router::getClassInstance();
```
下面是获取资源类型的用法:

```php
$router->getResourceType(); 
```
比如`http://domain/Yourpath/resourcename.json`,下面的方法将返回`json`  
由于使用多个后缀对于路由匹配没有意义，所以对于`http://domain/Yourpath/resourcename.ext.json` 将会返回`ext.json`    

下面是获取URI path中的参数用法，所谓参数是指路径字符串匹配控制器后剩余的部分，下面是用法:

```
$router->getParams(); //返回全部参数
$router->getParams(0); //返回第一个参数
```
比如`http://domain/user/info/1221/update` 匹配`YourApp\User\Info`时，全部参数为 array('1221','update')，而获取其中的参数时，传入的索引从0开始，注意本方法会返回原始数据而步进行过滤处理

##[ToKnot中文教程](http://toknot.com/category/tutorials/)
