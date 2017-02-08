<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\DB;

use Toknot\Boot\Kernel;
use Toknot\Boot\Object;
use Toknot\Boot\Tookit;
use Toknot\Share\DB\DBSchema as Schema;
use Toknot\Exception\BaseException;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Types\Type;

class DB extends Object {

    /**
     * config.ini database db config, inculude table struct
     *
     * @var array
     */
    private static $cfg = [];
    private static $modelDir;

    /**
     *
     * @var \Doctrine\DBAL\Connection
     */
    private static $conn;
    private static $modelNs;
    private static $usedb;
    private $tableConfig;

    const T_OR = '||';
    const T_AND = '&&';
    const TEXT_LEN = 65535;
    const MTEXT_LEN = 16777215;
    const BTEXT_LEN = 4294967295;

    /**
     * 
     * @param array $db The database config key
     */
    protected function __construct($db = '') {
        $allcfg = Kernel::single()->cfg;

        if (empty($db) && empty(self::$usedb)) {
            self::$usedb = $allcfg->app->default_db_config_key;
        } else {
            self::$usedb = $db;
        }

        $config = $allcfg->database[self::$usedb];

        $this->tableConfig = $config->table_config;

        self::$cfg = $config;

        Tookit::coalesce(self::$cfg, 'table_default', []);
        Tookit::coalesce(self::$cfg, 'column_default', []);

        $appCfg = $allcfg->app;
        self::$modelNs = Tookit::nsJoin($appCfg->app_ns, $appCfg->model_ns);
        self::$modelDir = Tookit::realpath($allcfg->app->model_dir, APPDIR);
    }

    public static function getUseDBConfig() {
        return self::$usedb;
    }

    public function getQuotedName($name) {
        $platform = self::$conn->getDatabasePlatform();
        $keywords = $platform->getReservedKeywordsList();
        $parts = explode(".", $name);

        foreach ($parts as $k => $v) {
            $parts[$k] = $keywords->isKeyword($v) ? $platform->quoteIdentifier($v) : $v;
        }

        return implode(".", $parts);
    }

    /**
     * get column type of database from config
     * 
     * @param string $t
     * @return string
     */
    public static function getDBType($t) {
        $type = Type::getType($t);
        return $type->getBindingType();
    }

    /**
     * 
     * @param string $name filename of The database struct *.ini
     *                      The name is config.ini of database.db1.tables
     * @return array
     */
    public function loadConfig($name) {
        $cnf = APPDIR . "/config/$name.ini";
        return Kernel::single()->loadini($cnf);
    }

    public function connect() {
        if (self::$conn instanceof DriverManager) {
            return self::$conn;
        }
        $config = new Configuration;
        $connectionParams = $this->dbconfig()->toArray();

        self::$conn = DriverManager::getConnection($connectionParams, $config);

        return self::$conn;
    }

    public function getDatabase() {
        return self::$cfg->dbname;
    }

    public function dbconfig() {
        $params = self::$cfg;

        if (class_exists('PDO', false) && strpos(self::$cfg->type, 'pdo_') === false) {
            $params->driver = 'pdo_' . self::$cfg->type;
        } elseif (class_exists('mysqli', false) && self::$cfg->type == 'mysql') {
            $params->driver = 'mysqli';
        } else {
            $params->driver = self::$cfg->type;
        }
        Tookit::arrayRemove($params, 'type', 'tables');
        return $params;
    }

    public function loadModel() {
        $modleFile = self::$modelDir . '/model.' . self::$usedb . '.php';
        if (!file_exists($modleFile)) {
            throw new BaseException('default model list uninitialized');
        }
        include_once self::$modelDir . '/model.' . self::$usedb . '.php';
    }

    public static function table2Class($table) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
    }

    public function initModel($tables, $db) {
        if (!is_dir(self::$modelDir)) {
            mkdir(self::$modelDir);
        }

        $code = '<?php' . PHP_EOL;
        $code .= 'namespace ' . self::$modelNs . ';' . PHP_EOL;
        $code .= 'use Toknot\Share\Model;' . PHP_EOL;

        foreach ($tables as $table => $v) {
            $columnSQL = implode(',', array_keys($v['column']));
            $class = self::table2Class($table);
            $code .= "class $class extends Model {";
            $code .= "protected \$table = '$table';";

            if (isset($v['indexes']) && isset($v['indexes']['primary'])) {
                $code .= "protected \$key='{$v['indexes']['primary']}';";
            }

            $code .= "protected \$columnSql='$columnSQL';}" . PHP_EOL;
        }

        $modelFile = self::$modelDir . '/model.' . $db . '.php';
        file_put_contents($modelFile, $code);
        //include_once $modelFile;
    }

    /**
     * 
     * @param string $usedb         will use db config in database section of sub key in config.ini
     * @param type $tableFeildKey   the table struct config file name in db section
     *                              it value is db1.table_config of value
     * @param type $force
     * @return type
     */
    public function initDatabaseTables($usedb, $tableFeildKey, $force = false) {
        $tables = $this->loadConfig($tableFeildKey);
        $this->initModel($tables, $usedb);

        list($query, $dropSql) = $this->createSchema($tables);
      
        $execResult = [];
        foreach ($query as $i => $t) {
            if ($force) {
                Logs::colorMessage('Exec: ', 'purple', false);
                Logs::colorMessage($dropSql[$i]);
                self::$conn->executeQuery($dropSql[$i]);
            }
            $execResult[] = $t;
            self::$conn->executeQuery($t);
        }
        return $execResult;
    }

    /**
     * 
     * @param string $table
     * @param string $dbconfig
     * @return \Toknot\Share\Model
     */
    public static function table($table, $dbconfig = '') {
        $db = self::single($dbconfig);

        $db->connect();
        $tableClass = Tookit::nsJoin(self::$modelNs, self::table2Class($table));
        $tableClass = Tookit::dotNS($tableClass);

        if (empty(self::$cfg->tables)) {
            self::$cfg->tables = $db->loadConfig(self::$cfg->table_config);
            $db->iteratorArray = self::$cfg->tables;
        }
        $db->loadModel();
        $m = new $tableClass(self::$cfg->tables[$table]);
        $m->connect(self::$conn);
        return $m;
    }

    public static function composite($type, $where) {
        return new CompositeExpression($type, $where);
    }

    /**
     * 
     * @param string $v
     * @return string
     */
    public static function getCompType($v) {
        return $v == self::T_OR ? CompositeExpression::TYPE_OR : CompositeExpression::TYPE_AND;
    }

    public function addType($string) {
        $className = Tookit::nsJoin(__NAMESPACE__, ucwords($string) . 'Type');
        Type::addType($string, $className);
    }

    /**
     * 
     * @param array $tables The database table struct array
     * @return Schema
     */
    public function newSchema($tables) {
        $schema = new Schema();
        $this->createTable($schema, $tables);
        return $schema;
    }

    /**
     * @access protected
     * @param Doctrine\DBAL\Schema\Schema $schema
     * @param array $tables
     */
    protected function createTable(Schema &$schema, $tables) {
        $tableDefault = self::$cfg->table_default;
        $columnDefault = self::$cfg->column_default;
        
        foreach ($tables as $table => $info) {
            $nt = $schema->createTable($table);
            if (empty($info['column'])) {
                throw new BaseException('column of table not exists');
            }
            $this->tableOptoin($nt, $tableDefault);
            if (isset($info['option'])) {
                $this->tableOptoin($nt, $info['option']);
            }
            foreach ($info['column'] as $column => $cinfo) {
                if (empty($cinfo['type'])) {
                    throw new BaseException("table '$table' of column '$column' missed type");
                }
                $this->columnTextLength($cinfo);
                $option = array_merge(iterator_to_array($columnDefault), Tookit::arrayRemove($cinfo, 'type'));

                $nt->addColumn($column, $cinfo['type'], $option);
            }
            if (isset($info['indexes'])) {
                $this->setIndexes($nt, $info['indexes']);
            }
        }
    }

    public function tableOptoin(&$table, $option) {
        foreach ($option as $k => $v) {
            $table->addOption($k, $v);
        }
    }

    public function columnTextLength(&$column) {
        if ($column['type'] != 'text') {
            return;
        }

        if (empty($column['length'])) {
            $column['length'] = self::TEXT_LEN;
        } elseif (strtolower($column['length']) == 'm') {
            $column['length'] = self::MTEXT_LEN;
        } elseif (strtolower($column['length']) == 'b') {
            $column['length'] = self::BTEXT_LEN;
        }
    }

    public function addIndex(&$nt, $key, $v) {
        $func = 'addIndex';
        $option = [];
        if (empty($v['type'])) {
            $tablename = $nt->getName();
            throw new BaseException("table '$tablename' of index '$key' missed type");
        }
        if ($v['type'] == 'unique') {
            $func = 'addUniqueIndex';
        }
        if (isset($v['comment'])) {
            $option['comment'] = $v['comment'];
        }
        $ic = [];
        foreach ($v as $n => $v) {
            if ($n == 'type' || $n == 'comment') {
                continue;
            }
            $ic[] = $n;
        }
        $nt->$func($ic, $key, $option);
    }

    public function setIndexes(&$nt, $index) {
        foreach ($index as $key => $v) {
            if ($key == 'primary') {
                $nt->setPrimaryKey(array($v));
            } else {
                $this->addIndex($nt, $key, $v);
            }
        }
    }

    public function initType() {
        $extType = ['tinyint'];
        foreach ($extType as $type) {
            $this->addType($type);
        }
    }

    /**
     * get differences of tables, and use the diff update table
     * 
     * @param array $from  current schema table info
     * @param array $to    new schema table info
     * @return array
     */
    public function updateSchema($from, $to) {
        $this->initType();
        $platform = self::$conn->getDatabasePlatform();
        $fromSchema = $this->newSchema($from);
        $toSchema = $this->newSchema($to);
        $comparator = new Comparator;
        $diff = $comparator->compare($fromSchema, $toSchema);
        $sql = $diff->toSaveSql($platform);
        return $sql;
    }

    /**
     * create new schema
     * 
     * @param array $tables
     * @return array
     */
    public function createSchema($tables) {
        $this->initType();
        $platform = self::$conn->getDatabasePlatform();
        $schema = $this->newSchema($tables);
        $query = $schema->toSql($platform);
        //$dropSql = $schema->toDropSql($platform);
        $dropSql = $schema->toDropIfExistsSql($platform);
        return [$query, $dropSql];
    }

}