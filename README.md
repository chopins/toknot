#Toknot V2dev

##About 
The project is php of web fast development of MVC framework

##LICENSE
    see [LICENSE](https://github.com/chopins/toknot/blob/master/LICENSE)

##DIRECTORY STRUCTURE
    Toknot/             framework sources code
          Config/       default ini file and load config of class
          Control/      Router
          Db/           Database opreate
          Di/           framework of object
          Exception/    
          Http/         Http opreate
          Image/        Image opreate
          Process/      Process manage
          Tool/         
          View/         view layer
     demos/

##Install and Configure Usage

Simply download the framework, extract it to the folder you would like to keep it in，and include 
`Toknot/Control/Application.php` on your application index.php file, like below code:

your application of `index.php`:
```php
use Toknot\Control\Application;

require_once '/your_install_path/Toknot/Control/Application.php';

$app = new Application;
$app->run('\Shop',dirname(__DIR__));
```
then, configure your nginx conf file of server section like below:
```conf  
server {
    listen 80;
    server_name localhost;

    #set applcation site path
    set $appPath /your_application_path;

    #if the server have statice file and add static file location
    location / {
        root $appPath/WebRoot;
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;

        #set applcation index.php file(a single entry point file) for nginx SCRIPT_FILENAME support PATH access mode
        #otherwise only use GET query mode
        fastcgi_param  SCRIPT_FILENAME $appPath/WebRoot/index.php;
        include        fastcgi_params;
    }
}
```   
##Create Application
1. creare application of one simply controller provide `http://your_domain/Index` visit, code like below:
    ```php

    //The class provide url is http://your_domain/Index visit
    class Index {
        public $FMAI = null;
        
        //the method recived FMAI object instance(only on controller construct method recived)
        public function __construct($FMAI) {
            $this->FMAI = $FMAI;
            $view = $this->FMAI->newTemplateView();
            $view->scanPath = __DIR__ . '/View';
            $view->cachePath = __DIR__ . '/Data/View';
            $view->fileExtension = 'html';
        }

        //The method provide HTTP GET method request
        public function GET() {
            $this->FMAI->D['hello'] = 'Hello world';
            $this->FMAI->display('index');  //output index template
        }

        //The method provide HTTP POST method request
        public function POST() {
        }
    }

    ```
    then save the file is Index.php under `/your_application_path/Controller`

2. use HTML template, create index.html template under `/your_application/View`
    ```html
    <html>
    <head></head>
    <body>{$hello}</body>
    </html>
    ```
3. create your configure file in `/your_application/Config` if your have config
4. more usage see demo https://github.com/chopins/toknot/tree/master/demos

##Previous versions 
The ToKnot Freamwork v1 visit https://github.com/chopins/toknot/tree/V1