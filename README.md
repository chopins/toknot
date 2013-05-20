#Toknot V2dev

##About 
The project is php of web fast development of MVC framework

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
* creare application controller

##Previous versions 
The ToKnot Freamwork v1 visit https://github.com/chopins/toknot/tree/V1