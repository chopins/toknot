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
use Toknot\Boot\ObjectHelper;
use Toknot\Boot\Tookit;
use Toknot\Share\DB\DBSchema as Schema;
use Toknot\Boot\Configuration as TKConfig;
use Toknot\Exception\BaseException;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Query\QueryBuilder;

class DBA extends Object {

    use ObjectHelper;

    /**
     * config.ini database db config, inculude table struct
     *
     * @var array
     */
    private $cfg = [];
    private $modelDir;

    /**
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;
    private $nodbconn;
    private static $tableClassNs;
    private $usedb;
    private $tableConfig;
    private $extType = [];
    public $confType = 'ini';
    public static $fechStyle = \PDO::FETCH_ASSOC;
    public static $cursorOri = \PDO::FETCH_ORI_NEXT;
    private $transactionActive = false;
    public $appDir = '';
    private $columnDefaultOption = [];
    private $tableDefaultOption = [];
    private $dbns = '';
    private $appns = '';

    const T_OR = '||';
    const T_AND = '&&';
    const TS_OR = 'OR';
    const TS_AND = 'AND';
    const TEXT_LEN = 65535;
    const MTEXT_LEN = 16777215;
    const BTEXT_LEN = 4294967295;
    const SELECT = QueryBuilder::SELECT;
    const DELETE = QueryBuilder::DELETE;
    const UPDATE = QueryBuilder::UPDATE;
    const INSERT = QueryBuilder::INSERT;

    /**
     * 
     * @param array $dbkey The database config key
     */
    protected function __construct($dbkey = '', $mainConfig = []) {
        $this->appDir = Kernel::single()->appDir();
        $mainConfig = $mainConfig ? $mainConfig : Kernel::single()->cfg;

        $this->autoConfigProperty($this->propertySetList(), $mainConfig);

        if ($dbkey) {
            $this->usedb = $dbkey;
        }

        $this->extType = explode(',', $this->extType);
        $this->autoConfigProperty($this->dbProperty($this->usedb), $mainConfig);

        self::$tableClassNs = Tookit::nsJoin($this->appns, $this->dbns);
    }

    public function propertySetList() {
        return ['usedb' => 'database.default',
            'extType' => 'database.ext_type',
            'appns' => 'app.app_ns',
            'dbns' => 'app.db_table_ns',
            'modelDir' => 'app.model_dir',
            'cfg' => 'database'];
    }

    public function dbProperty($db) {
        return ['cfg' => "database.$db",
            'confType' => "database.$db.config_type",
            'tableConfig' => "database.$db.table_config",
            'columnDefaultOption' => "database.$db.column_default",
            'tableDefaultOption' => "database.$db.table_default",
            'dbType' => "database.$db.type"
        ];
    }

    public function setConfType($type) {
        $this->confType = $type;
    }

    public function setAppDir($dir) {
        $this->appDir = $dir;
    }

    public function getUseDB() {
        return $this->usedb;
    }

    public function getDBConfig() {
        return $this->cfg;
    }

    public function initModelDir() {
        $this->modelDir = Tookit::realpath($this->modelDir, $this->appDir);
    }

    public function getQuotedName($name) {
        $platform = $this->conn->getDatabasePlatform();
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
        $cnf = $this->appDir . "/config/$name." . $this->confType;
        $cfg = new TKConfig(Kernel::single(), []);
        $cfg->setAppDir($this->appDir);
        return $cfg->load($cnf);
    }

    public function connect($newConn = false) {
        if ($this->conn instanceof Connection && !$newConn) {
            return $this->conn;
        }
        $config = new Configuration;
        $connectionParams = $this->dbconfig()->toArray();

        $this->conn = DriverManager::getConnection($connectionParams, $config);

        return $this->conn;
    }

    public function unSelectDBConnect() {
        if ($this->nodbconn instanceof Connection) {
            return $this->nodbconn;
        }
        $config = new Configuration;
        $connectionParams = $this->dbconfig()->toArray();
        unset($connectionParams['dbname']);
        $this->nodbconn = DriverManager::getConnection($connectionParams, $config);

        return $this->nodbconn;
    }

    public function close($conn = null) {
        if ($conn) {
            return $conn->close();
        }
        return $this->conn->close();
    }

    public function query($sql) {
        return $this->conn->executeQuery($sql);
    }

    public function getDatabase() {
        return $this->cfg->dbname;
    }

    public function dbconfig() {
        $params = $this->cfg;

        if (class_exists('PDO', false) && strpos($this->dbType, 'pdo_') === false) {
            $params->driver = 'pdo_' . $this->dbType;
        } elseif (class_exists('mysqli', false) && $this->dbType == 'mysql') {
            $params->driver = 'mysqli';
        } else {
            $params->driver = $this->dbType;
        }
        Tookit::arrayRemove($params, 'type', 'tables');
        return $params;
    }

    public function loadModel() {
        $this->initModelDir();
        $modleFile = $this->modelDir . '/model.' . $this->usedb . '.php';
        if (!file_exists($modleFile)) {
            $tables = $this->loadConfig($this->tableConfig);
            $this->initModel($tables, $this->usedb);
        }
        include_once $this->modelDir . '/model.' . $this->usedb . '.php';
    }

    public static function table2Class($table) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
    }

    public function initModel($tables, $db) {
        $this->initModelDir();
        if (!is_dir($this->modelDir)) {
            mkdir($this->modelDir);
        }

        $code = '<?php' . PHP_EOL;
        $code .= 'namespace ' . Tookit::nsJoin(self::$tableClassNs, ucfirst($db)) . ';' . PHP_EOL;
        $code .= 'use Toknot\Share\DB\Table;' . PHP_EOL;

        foreach ($tables as $table => $v) {
            if (!isset($v['column'])) {
                throw new BaseException("$table miss column list");
            }
            $columnSQL = implode(',', array_keys($v['column']));
            $class = Tookit::underline2Camel($table);
            $code .= "class $class extends Table {";
            $code .= "protected \$tableName = '$table';";
            $code .= 'protected $tableStructure=' . var_export($v, true) . ';';
            if (isset($v['indexes']) && isset($v['indexes']['primary'])) {
                $keys = explode(',', $v['indexes']['primary']);
                $key = count($keys) > 1 ? var_export($keys, true) : '\'' . $v['indexes']['primary'] . '\'';
                $code .= "protected \$primaryKeyName=$key;";
            }

            $code .= "protected \$columnSql='$columnSQL';}" . PHP_EOL;
        }

        $modelFile = $this->modelDir . '/model.' . $db . '.php';
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
                $this->conn->executeQuery($dropSql[$i]);
            }
            $execResult[] = $t;
            $this->conn->executeQuery($t);
        }
        return $execResult;
    }

    /**
     * 
     * @param string $table
     * @param string $dbkey
     * @return \Toknot\Share\DB\Table
     */
    public static function table($table, $dbkey = '', $newConn = false) {
        $db = self::decideIns($dbkey);
        $conn = $db->connect($newConn);
        $tableClass = Tookit::nsJoin(self::$tableClassNs, ucfirst($db->getUseDB()), self::table2Class($table));
        $tableClass = Tookit::dotNS($tableClass);

        $db->loadModel();
        if (!class_exists($tableClass, false)) {
            throw new BaseException("class '$tableClass of table '$table' of database does not exists, check the table '$table' whether it is exists");
        }
        $m = new $tableClass($db);
        return $m;
    }

    public static function composite($type, $where) {
        return new CompositeExpression($type, $where);
    }

    /**
     * 
     * @param string $db
     * @return $this
     */
    public static function decideIns($db = '') {
        return $db ? self::single($db) : self::single();
    }

    public static function transaction($callable, $db = '') {
        $conn = self::decideIns($db)->connect();
        $conn->beginTransaction();
        try {
            $callable();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * start transaction update
     */
    public function beginTransaction() {
        if ($this->transactionActive) {
            return;
        }
        $this->transactionActive = true;
        $this->conn->beginTransaction();
    }

    /**
     * submit query
     * 
     * @throws \Exception
     */
    public function commit() {
        if ($this->transactionActive === false) {
            throw new BaseException('transaction not start');
        }
        try {
            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function rollBack() {
        if ($this->transactionActive === false) {
            throw new BaseException('transaction not start');
        }

        $this->conn->rollBack();
        $this->transactionActive = false;
    }

    public function autoCommit($mark = true) {
        $this->conn->setAutoCommit($mark);
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

    public function createDatabase($dbname) {
        $this->unSelectDBConnect();
        return $this->nodbconn->getSchemaManager()->createDatabase($dbname);
    }

    public function getTableStructure($table) {
        return $this->conn->getSchemaManager()->listTableColumns($table);
    }

    public function getTableList() {
        return $this->conn->getSchemaManager()->listTables();
    }

    public function getDBList() {
        $this->unSelectDBConnect();
        return $this->nodbconn->getSchemaManager()->listDatabases();
    }

    public function getTableIndexs($table) {
        return $this->conn->getSchemaManager()->listTableIndexes($table);
    }

    public function getAllTableStructureCacheArray() {
        $tables = $this->getTableList();
        $cacheArray = [];
        foreach ($tables as $t) {
            $talename = $t->getName();
            $cacheArray[$talename] = [];
            $cacheArray[$talename]['column'] = [];
            $columns = $t->getColumns();
            foreach ($columns as $col) {
                $columnsArray = [];
                $name = $col->getName();
                $columnsArray['type'] = $col->getType()->getName();
                $columnsArray['length'] = $col->getLength();
                $columnsArray['unsigned'] = $col->getUnsigned();
                $columnsArray['fixed'] = $col->getFixed();
                $columnsArray['default'] = $col->getDefault();
                $columnsArray['autoincrement'] = $col->getAutoincrement();
                $columnsArray['comment'] = $col->getComment();
                $cacheArray[$talename]['column'][$name] = $columnsArray;
            }
            $cacheArray[$talename]['option'] = $t->getOptions();

            $indexes = $t->getIndexes();
            $idxArr = [];
            foreach ($indexes as $key => $idx) {
                $this->getIndexStructrue($idxArr, $idx, $key);
            }
            $cacheArray[$talename]['indexes'] = $idxArr;
        }
        return $cacheArray;
    }

    private function getIndexStructrue(&$idxArr, $idx, $key) {
        if ($idx->isPrimary()) {
            $primary = $idx->getColumns();
            $idxArr[$key] = $primary[0];
            return;
        } elseif ($idx->isUnique()) {
            $idxArr[$key] = ['type' => 'unique'];
        } else {
            $idxArr[$key] = ['type' => 'index'];
        }
        $idxes = $idx->getColumns();

        foreach ($idxes as $k) {
            $idxArr[$key][$k] = 'default';
        }
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
        foreach ($tables as $table => $info) {
            $nt = $schema->createTable($table);
            if (empty($info['column'])) {
                throw new BaseException('column of table not exists');
            }
            $this->tableOptoin($nt, $this->tableDefaultOption);
            if (isset($info['option'])) {
                $this->tableOptoin($nt, $info['option']);
            }
            foreach ($info['column'] as $column => $cinfo) {
                if (empty($cinfo['type'])) {
                    throw new BaseException("table '$table' of column '$column' missed type");
                }
                $this->columnTextLength($cinfo);
                $option = array_merge(iterator_to_array($this->columnDefaultOption), Tookit::arrayRemove($cinfo, 'type'));
                if ($cinfo['type'] == 'char') {
                    $option['fixed'] = true;
                    $cinfo['type'] = 'string';
                }

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
            throw new BaseException("index '$key'  of  table '$tablename' missed type");
        }
        if (empty($v['feilds'])) {
            $tablename = $nt->getName();
            throw new BaseException("index '$key' missed feilds");
        }
        if ($v['type'] == 'unique') {
            $func = 'addUniqueIndex';
        }
        if (isset($v['comment'])) {
            $option['comment'] = $v['comment'];
        }
        $ic = explode(',', $v['feild']);

        $nt->$func($ic, $key, $option);
    }

    /**
     * parse index from config
     * 
     * primary key value like: id1,id2
     * 
     * @param Table $nt
     * @param array $index
     */
    public function setIndexes(&$nt, $index) {
        foreach ($index as $key => $v) {
            if ($key == 'primary') {
                $nt->setPrimaryKey(explode(',', $v));
            } else {
                $this->addIndex($nt, $key, $v);
            }
        }
    }

    public function initType() {
        foreach ($this->extType as $type) {
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
        $platform = $this->conn->getDatabasePlatform();
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
        $platform = $this->conn->getDatabasePlatform();
        $schema = $this->newSchema($tables);
        $query = $schema->toSql($platform);
        //$dropSql = $schema->toDropSql($platform);
        $dropSql = $schema->toDropIfExistsSql($platform);
        return [$query, $dropSql];
    }

    public function getColumnTypeDefaultLength($type) {
        $cls = Type::getType($type);
        return $cls->getDefaultLength($this->conn->getDatabasePlatform());
    }

}
