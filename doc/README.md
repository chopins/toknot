###ToKnot Type Object
ToKnot 增加了三种类型， 字符串对象，数组对象和文件对象

#####[Object对象说明](https://github.com/chopins/toknot/blob/master/doc/Object%E5%AF%B9%E8%B1%A1%E8%AF%B4%E6%98%8E.mdown)

#####字符串对象：[Toknot\Di\StringObject](http://toknot.com/toknot/class-Toknot.Di.StringObject.html)

    可以使用 _s('string')来将字符串转换为字符串对象 
    该对象支持大部分PHP标准字符串函数同名得静态方法，功能也一样
    该对象支持echo, 大部分时候可以当成字符串类型使用
    支持迭代器
    支持数组访问
    支持count函数

#####数组对象：[Toknot\Di\ArrayObject](http://toknot.com/toknot/class-Toknot.Di.ArrayObject.html)
    
    可以使用_l(array())来将数组转换为数组对象
    支持迭代器
    支持数组访问
    支持count函数
    
#####文件对象:[Toknot\Di\FileObject](http://toknot.com/toknot/class-Toknot.Di.FileObject.html)
    支持迭代器
    支持数组访问
    支持count函数

####路由器接口
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

###[ToKnot中文教程](http://toknot.com/category/tutorials/)


###connact at weibo
http://www.weibo.com/colors