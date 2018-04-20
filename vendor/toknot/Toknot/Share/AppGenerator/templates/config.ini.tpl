;
; Toknot (http://toknot.com)
;
; @copyright  Copyright (c) 2011 - 2017 Toknot.com
; @license    http://toknot.com/LICENSE.txt New BSD License
; @link       https://github.com/chopins/toknot
;
[app]
trace = true
timezone = UTC
charet = utf8
app_ns={{appNS}}
ctl_ns=Controller
model_ns=Model
middleware_ns=Middleware
service_ns=
view_ns= View
route_conf_type = ini
router = Toknot\Share\Router
default_db_config_key = db1 
short_except_path = true
model_dir = runtime/model
default_layout = 
default_call = rt
session.enable = true
;session config see http://php.net/session.configuration
session.table = session
session.name = sid
session.cookie_httponly = 1
log.enable = false
log.logger = runtime/logs/trace
;log.logger = APP\Logger  
    
[database]
default =db1
ext_type = tinyint
;primary database
db1.host = 127.0.0.1
db1.config_type = ini
db1.port = 3306
db1.user = root
db1.password = 
db1.dbname = process
db1.charset = utf8
db1.type = mysql
db1.table_config = database   ;tables info config file
db1.table_default.engine = innodb
db1.table_default.collate = utf8_general_ci
db1.column_default.unsigned = true
db1.column_default.collate = utf8_general_ci
db1.config_type = ini

[vendor]
dbal = doctrine/Doctrine
routing = symfony/Symfony
phpdoc = zend/Zend

[wrapper]
rt = Toknot\Share\Route\Router
ts = Toknot\Share\Service\Wrapper
