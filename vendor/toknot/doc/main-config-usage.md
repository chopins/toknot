#The main config document

### App config in section name of ini file:`[app]`
* `disable_install = true`
* app time zone:`timezone = UTC`
* app charset:`charet = utf8`
* app namespace:`app_ns=Event`
* app namespace of controller:`ctl_ns=Controller`
* app namespace of model:`model_ns=Model`
* app namespace of middleware:`middleware_ns=Middleware`
* app namespace of service:`service_ns=`
* app namespce of view:`view_ns=View`
* app class name of router:`router = Toknot\Share\Router`
* app default key of database config:`default_db_config_key = db1`
* wheter truncation path in exception trace:`short_except_path = true`
* table of database map to model cache directory:`model_dir = runtime/model`
* default layout for view:`default_layout = Event\View\Layout\DefaultLayout`
* session store table(option of app):`session.table = session`
* session name(option of app):`session.name = sid`
* session httponly config(option of app):`session.cookie_httponly = 1`

### section of App vendor lib config:`[vendor]`
* `dbal = doctrine/Doctrine`
* `routing = symfony/Symfony`
* `phpdoc = zend/Zend`

### section of App databae config:`[database]`
* Add type of database data:`ext_type = tinyint`
* host of db1, the key db1 equal above key `default_db_config_key`:`db1.host = localhost`
* port of db1:`db1.port = 3369`
* `db1.user = root`
* `db1.password = `
* `db1.dbname = process`
* connect charset of database:`db1.charset = utf8`
* `db1.type = mysql`
* the value is name of table list config file:`db1.table_config = database`
* when create table, set default engine of table:`db1.table_default.engine = innodb`
* when create table, set default charset of table:`db1.table_default.collate = utf8_general_ci`
* when create table, set default unsigned of integer column:`db1.column_default.unsigned = true`
* when create table, set default charset of string column:`db1.column_default.collate = utf8_general_ci`
