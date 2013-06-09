##Toknot V2-dev

####About
ToKnot is php MVC framework

__The project is still under heavy development__

####License
see [LICENSE](https://github.com/chopins/toknot/blob/master/LICENSE)

####API and Class Reference
see (http://toknot.com/toknot/)

####Directory Structure
    Toknot/             framework sources code
          Config/       default ini file and load config of class
          Control/      Router
          Db/           Database opreate
          Di/           framework of object
          Exception/    Framework Exception class
          Http/         Http opreate
          Process/      Process manage
          Tool/
          View/         view layer
          User/         User Control model that is like unix file access permissions
     demos/

####Usage and Configure

Simply download the framework, extract it to the folder you would like to keep it in，and include
`Toknot/Control/Application.php` on your application index.php file, like below code:

your application of `index.php`:
```php
use Toknot\Control\Application;

require_once '/your_install_path/Toknot/Control/Application.php';

$app = new Application;
$app->run('\MyApp',dirname(__DIR__));
```
then, configure your web-server set webroot to the path be index.php in directory, and set rewrite
become all http request access the index.php

####Create Application
1. In command line, use `php -f /yourpath/Toknot/Tool/CreateApp.php applicationPath` create your application
    default directory structure, example below:
    `php -f /yourpath/Toknot/Tool/CreateApp.php /rootpath/your_application`     
     up example also like below be create:      
     `cd /rootpath/`        
    then:   `php -f /yourpath/Toknot/Tool/CreateApp.php  your_application`      

2. change /rootpath/appName/Controller/Index.php, write code like below:
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

3. use HTML template, create index.html template under `/your_application/View`
    ```html
    <html>
    <head></head>
    <body>{$hello}</body>
    </html>
    ```
4. change `/your_application/Config/config.ini`
5. more usage see demo https://github.com/chopins/toknot/tree/master/demos

####Previous Versions
The ToKnot Freamwork v1 visit https://github.com/chopins/toknot/tree/V1

####Hello World Benchmark
Run AppAdmin of under demos, PHP 5.4.14, with APC extension(Version 2.2.2,default configure)
Using the “ab” benchmark tool we sent 1000 requests using 5 concurrent connections to each framework
```
ab -n 1000 -c 5 http://admin/
This is ApacheBench, Version 2.3 <$Revision: 1430300 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Server Software:        nginx/1.2.9
Server Hostname:        admin
Server Port:            80

Document Path:          /
Document Length:        136 bytes

Concurrency Level:      5
Time taken for tests:   0.939 seconds
Complete requests:      1000
Failed requests:        165
   (Connect: 0, Receive: 0, Length: 165, Exceptions: 0)
Write errors:           0
Total transferred:      282833 bytes
HTML transferred:       135833 bytes
Requests per second:    1064.49 [#/sec] (mean)
Time per request:       4.697 [ms] (mean)
Time per request:       0.939 [ms] (mean, across all concurrent requests)
Transfer rate:          294.02 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.0      0       0
Processing:     3    5   0.9      5      12
Waiting:        3    5   0.9      5      12
Total:          3    5   0.9      5      12

Percentage of the requests served within a certain time (ms)
  50%      5
  66%      5
  75%      5
  80%      5
  90%      6
  95%      6
  98%      6
  99%      7
 100%     12 (longest request)
```