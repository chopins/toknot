###建立第一个ToKnot应用
1. 下载Toknot框架文件，例如保存在下面的地址 
    `/home/Toknot`

该文件夹下目录结构如下: 
        Config              默认配置文件和配置文件夹加载类 
        Control             框架控制中心 
        Db                  数据库相关 
        Di                  框架内部数据类型 
        Exception           异常类 
        Http                HTTP协议相关 
        Process             进程管理相关 
        Tool                工具类 
        User                用户组件 
        View                页面渲染相关 
 
2. 创建一个应用程序的文件夹，如下所示  
    `mkdir /home/MyApp`

3. 然后在`/home/MyApp`目录下面分别创建如下文件夹 
    Config          配置文件,建议 
    Controller      应用控制器文件夹，必须 
    Data            应用程序数据存放文件夹，建议 
    View            页面HTML模板文件存放位置，建议 
    WebRoot         web站点入口文件存放位置，建议 
    Model           模块文件夹,建议 

4. 进入 /home/MyApp/WebRoot 文件夹创建 index.php 文件(建议)， 在该文件中创建如下代码
```php
use Toknot\Control\Application;
require_once dirname(dirname(dirname(__DIR__))).'/Toknot/Control/Application.php';
$app = new Application;
$app->run('\MyApp',dirname(__DIR__));
```  
从上面的例子，可以看到，我们使用了 MyApp 这个命名空间，这个名字与`/home/MyApp`相同。  
因为这是框架约定的命名空间的规则：即命名空间的名字与类所在文件夹的名字相同 
 
5. 进入`/home/MyApp/Controller`文件夹，创建`Index.php`文件, 输入如下代码:
```php
namespace MyApp\Controller;

class Index {
    public $FMAI = null;
    public function __construct($FMAI) {
        $this->FMAI = $FMAI;
    }

    public function GET() {
        print 'hello world';
    }

    public function POST() {
        print 'This POST method request';
    }
```
    上面例子可以看到，Index.php 文件中的类名是与文件名相同的，这是框架默认路由器规定的 
    而用户控制器的构造函数将会接收到了一个FMAI类的实例，这个类提供框架各个组件的标准访问方法，该类所拥有的方法可以建框架类参考`http://toknot.com/toknot/class-Toknot.Control.FMAI.html` 
    如果web服务器已经配置，你可以通过访问`http://localhost/`看到打印了 'hello world' 的页面 
    如果我们构造一个HTML form表单, 并且以 POST 方式提交到`http://localhost/`， 将会看到打印了 'This POST method request' 的页面, 这因为框架路由器将会根据不同请求HTTP方法映射到不同的控制器方法上。 
    框架对控制器的规定如下： 
        1. 类名首字目大写
        2. 类必须是在一个命名空间类，且命名空间名必须与类文件所在文件夹相同
        3. 类提供用户HTTP访问的方法名必须大写，且只能为GET,POST,PUT,HEAD等HTTP协议中定义的请求方法的名字，他们分别会在用户以同名方法请求时被调用
        4. 非第3条定义的方法，路由器不会调用
        5. 命名空间下的Index控制器类将会作为该空间下默认调用的控制器，这类似于web服务器配置index.html等文件
    

6. 配置Web服务器
  Web服务器普通配置情况下，可以通过类似下面的方式访问控制器:
   `http://localhost/index.php?c=Index`
   `http://localhost/index.php?c=User.Login`
   `http://localhost/index.php?c=User`

  在 nginx 下可以通过如下配置来实现 PATH 模式
```conf
server {
    listen 80;
    server_name shop;

    #set applcation site path
    set $appPath /home/MyApp;

    #if the server have statice file and add static file location
    location / {
        root $appPath/WebRoot;
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        #set applcation index.php file(a single entry point file) for nginx 
        #SCRIPT_FILENAME support PATH access mode
        #otherwise only use GET query mode
        fastcgi_param  SCRIPT_FILENAME $appPath/WebRoot/index.php;
        include        fastcgi_params;
    }
}
```   
   通过上面的配置后，就可以使用如下的方法访问上面的几个地址
        `http://localhost/Index`
        `http://localhost/User/Login`
        `http://localhost/User`

   对于其他服务器，可以使用rewrite方式，将访问重写到`index.php`上

7. 对于PHP应用通常是需要链接数据库和使用模板的，下面改造了`/home/MyApp/Controller/Index.php`文件，使其支持：
```php 
class Index {
      public $FMAI = null;
      public $AR = null;
      public $AppPath;
      public function __construct($FMAI) {
          $this->FMAI = $FMAI;
          $this->AppPath = dirname(__DIR__);

          //加载应用的配置文件，默认会加载框架配置文件，如果想覆盖框架的配置项，可以创建同名的进行覆盖
          $this->CFG = $this->FMAI->loadConfigure($this->AppPath . '/Config/config.ini');

          //创建一个数据库映射类
          $this->AR = $this->FMAI->getActiveRecord();

          //使用数据库配置
          $this->AR->config($this->CFG->Database);

          //激活HTML缓存
          $this->FMAI->enableHTMLCache();

          //实例化一个模板渲染类，类文档见http://toknot.com/toknot/class-Toknot.View.Renderer.html
          $view = $this->FMAI->newTemplateView();
          //设置模板文件所在文件夹
          $view->scanPath = $this->AppPath . '/View';
          //设置模板编译后的文件存放文件夹
          $view->cachePath = $this->AppPath . '/Data/View';
          //设置模板文件后缀
          $view->fileExtension = 'html';
      }

      public function GET() {
          //连接数据库，将返回一个数据库对象实例, 类文档见http://toknot.com/toknot/class-Toknot.Db.DatabaseObject.html
          $shopDatabase = $this->AR->connect();

          //查询tableName 表主键等于 1 的记录
          //$shopDatabase->tableName  是一个数据库表对象实例类文档见http://toknot.com/toknot/class-Toknot.Db.DbTableObject.html
          $record = $shopDatabase->tableName->findByPK(1);

          print 'hello world';

          //渲染模板index.html, 模板实例与构造函数的中的实例是同一个
          $this->FMAI->display('index');
      }
}
```
8. 配置文件
   Toknot配置文件类型与PHP的INI配置文件一样, 使用框架加载类加载配置文件会区分配置块，例如下面的数据库配置
```ini
[Database]
dsn = 'mysql:dbname=test;host=localhost;port=3306'
username = root
password  = 112211
dirverOptions[p] = 1
dirverOptions[c] = 0

[Localization]
timezone = Asia/Chongqing
language = zh
encoding = utf-8

[Site]
domain = MyApp
```
上面配置假设存放在 `$ini` 表示的文件名中，可以通过下面的方式加载:
```php
$confg = ConfigLoader::loadCFG($ini);

//下面是访问配置我文件中的`Database`块下面的`dsn`项
$dsn = $confg->Database->dsn;

//下面是访问配置我文件中的`Database`块下面的`dirverOptions`项的 p
$dirverOptions = $confg->Database->dirverOptions->p;
//或者使用下面的
$dirverOptions = $confg->Database->dirverOptions['p'];

//下面是访问配置我文件中的`Localization`块下面的`encoding`项
$encoding = $config->Localization->encoding;
```
注意：目前框架的数据库链接所需要的配置项与上面Database块的一样，

9. 创建模板文件
    在`/home/MyApp/View`下面创建 index.html, 模板文件语法见模板相关文档
    对于`/home/MyApp/View`的子文件夹下的文件使用类似下面的方法来调用：
```php
$this->FMAI->display('User/index'); //使用/home/MyApp/View/User/index.html
```
    框架不会自动根据当前控制器命名空间进行访问