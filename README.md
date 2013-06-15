##Toknot 2.0-dev

####About
ToKnot is php MVC framework

__The project is still under heavy development__

####License
The PHP framework under New BSD License (http://toknot.com/LICENSE.txt)
The demos under GNU GPL version 3 or later <http://opensource.org/licenses/gpl-3.0.html>

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
          Admin/        Admin model
     demos/

####Usage and Configure

1. Simply download the framework, extract it to the folder you would like to keep it in, then create application

2. In command line, use `php -f /yourpath/Toknot/Tool/CreateApp.php` create your application default directory structure flow to the guide  

3. edit /your-app-path/Config/config.ini

4. if be created general application, your should change /rootpath/appName/{APP-NAME}Base.php for your application

5. into /your-app-path/Controller change Index.php or create other controller file of php

6. use HTML template, create template file under `/your-app-path/View`

7. change `/your-app-path/Config/config.ini`

8. more usage see demo https://github.com/chopins/toknot/tree/master/demos

####Previous Versions
The ToKnot Freamwork v1 visit https://github.com/chopins/toknot/tree/V1
