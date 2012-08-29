#Usage
##Documentation  
http://chopins.github.com/toknot/  

##Initialization Your Application  
  Please run like : `php XCreateApp.php /directory-path/application`  in CLI  
  Will create below directory or file inside provided path:
  * model       is database access relevant file of project
  * php         is service logic
  * ui          is css, js, HTML template file inside
  * var         is application data ,config.ini, temp directory
  * run.php     is application entry file

##Server Support  
  * nginx       add below code to your nginx config file:
                
                fastcgi_param  SCRIPT_FILENAME    /directory-path/application/run.php;
  
  * apache      use rewrite

#Repository File Explain
Toknot is php framework code root directory  
  * Toknot/php      is php framework code 
  * Toknot/js       is javascript framework  
  * Toknot/epoll    is epoll of php extensions, do not complete
  * Toknot/demo     is framework demo    


#Feature
  * service logic and data access separated
  * Multipe HTTP request method support
  * HTML template support
  * HTML output string compression
  * Key/Value text data base linear select 
  * Multipe process web server, experiment
  * support 5 URL access mode
  * cookie check, and if supperglobal is disable, there variable still use
  * SSH2 support
  * Multipe server file sync
  * At time same allow connect mulitpe database that is different database type
  * data of view page cache
  * view page HTML cache
  * javascript code output
  * support interface of remote data access that is no data 
  * `dump()`, `printn()`, `print_rn()` function will output call in line when print
  * run daemon when CLI mode
  * support ini file that is configure file type and support dot symbol is object access tag

