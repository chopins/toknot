####About
ToKnot is a php web framework that is suitable for RESTful style develop

[http://toknot.com](http://toknot.com)

####License
The PHP framework is under New BSD License (http://toknot.com/LICENSE.txt)

The demos is under GNU GPL version 3 or later <http://opensource.org/licenses/gpl-3.0.html>

####[简略文档](https://github.com/chopins/toknot/blob/master/doc)

####[API and Class Reference](http://toknot.com/toknot/)

####Directory Structure
    Toknot/             framework sources code
          Config/       default ini file and load config of class [Availabled]
          Db/           Database opreate, [Availabled]
          Boot/         boot app [Availabled]
          Command/      Command line tool [Availabled]
          Renderer/     view layer renderer [Availabled]
          Exception/    Framework Exception class  [Availabled]

          Share/          The share lib is options [Develop]
          Share/Http         Http opreate
          Share/Process/      Process manage
          Share/User/         User Control model that is like unix file access permissions
          Share/Admin/        Admin model
          
          Toknot.php     the main function
     demos/

####Usage and Configure

1. Simply download the framework, extract it to the folder you would like to keep it in, then create application

2. In command line, use `php -f /yourpath/Toknot/Toknot.php CreateApp` create your application default directory structure flow to the guide  

3. edit /your-app-path/Config/config.ini

4. into /your-app-path/Controller change Index.php or create other controller file of php

5. setup your web server all request rewrite to /your-app-path/webroot/index.php
