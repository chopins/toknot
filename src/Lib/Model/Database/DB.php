<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Database;

use PDO;
use Toknot\Boot\Kernel;

class DB extends PDO {

    private static $ins = [];
    private static $last = '';
    private $dbname = '';
    private $tablePrefix = '';
    private $tableModelCacheFile = '';
    private $modelCacheLoad = false;
    private $sessionQueryAutocommitChanged = false;
    public $tableLimit = 100;
    protected $config = null;
    public static $forceFlushDatabaseCache = false;

    public function __construct($dsn, $username = null, $pass = null, $option = []) {
        parent::__construct($dsn, $username, $pass, $option);
        $this->config = self::databaseConfig();
        $this->queryDBName();
        $this->flushDatabaseCache();
    }

    public function last() {
        return self::$last;
    }

    public static function databaseConfig() {
        return Kernel::instance()->config()->database;
    }

    /**
     * 
     * @param string $key
     * @return $this
     */
    public static function instance($key = '') {
        $lastKey = $key ? $key : self::$last;
        if (isset(self::$ins[$lastKey]) && is_a(self::$ins[$lastKey], __CLASS__)) {
            return self::$ins[$lastKey];
        }

        $config = self::databaseConfig();
        $defaultKey = $config->default;
        $key = $key ? $key : $defaultKey;

        $dataArg = ['dsn' => 'localhost', 'username' => '', 'password' => null, 'option' => [], 'prefix' => ''];
        foreach ($dataArg as $arg => $v) {
            $$arg = isset($config->$key->$arg) ? $config->$key->$arg : $v;
        }
        $attr = [];
        foreach ($option as $key => $v) {
            $keyValue = constant('\PDO::' . strtoupper($key));
            $attr[$keyValue] = $v;
        }

        self::$last = $key;
        self::$ins[$key] = new static($dsn, $username, $password, $attr);

        self::$ins[$key]->tablePrefix = $prefix;
        return self::$ins[$key];
    }

    public function setConnectAutocommit() {
        return $this->setAttribute(self::ATTR_AUTOCOMMIT, 1);
    }

    public function isConnectAutocommit() {
        return $this->getAttribute(self::ATTR_AUTOCOMMIT);
    }

    public function flushDatabaseCache() {
        $key = self::$last ? self::$last : $this->dbname;
        $this->tableModelCacheFile = Kernel::instance()->databasePath . DIRECTORY_SEPARATOR . $key . Kernel::PHP_EXT;

        if (self::$forceFlushDatabaseCache || !file_exists($this->tableModelCacheFile)) {
            $this->generateTableModel();
        }
    }

    public function tablePrefix() {
        return $this->tablePrefix;
    }

    public function getDBName() {
        return $this->dbname;
    }

    public function loadTableCacheFile() {
        if (!$this->modelCacheLoad) {
            $this->modelCacheLoad = true;
            include $this->tableModelCacheFile;
        }
    }

    /**
     * 
     * @param string $table
     * @return \Toknot\Lib\Model\Database\TableModel
     */
    public function table($table) {
        $this->loadTableCacheFile();
        if ($this->tablePrefix && strpos($table, $this->tablePrefix) === 0) {
            $table = substr($table, strlen($this->tablePrefix));
        }
        $tableClass = $this->tableModelNamespace() . Kernel::NS . Kernel::toUpper($table);
        if (class_exists($tableClass, false)) {
            return new $tableClass();
        }
        Kernel::runtimeException("table '$table' not exists at database '$this->dbname'", E_USER_ERROR);
    }

    public function tableNumber() {
        return $this->query("SELECT COUNT(*) AS cnt FROM `information_schema`.`TABLES` "
                        . "WHERE `TABLES`.`TABLE_SCHEMA`='{$this->dbname}'")->fetchColumn();
    }

    protected function getDBTablesInfo($offset = 0) {
        return $this->query("SELECT * FROM `information_schema`.`COLUMNS` "
                                . "WHERE `COLUMNS`.`TABLE_SCHEMA`='{$this->dbname}' LIMIT {$this->tableLimit} OFFSET $offset")
                        ->fetchAll(self::FETCH_ASSOC);
    }

    protected function getTableIndex($table) {
        $indexList = $this->query("SHOW INDEX FROM `{$this->dbname}`.`{$table}`");
        $mulList = ['mulIndex' => [], 'mulUni' => []];
        $keys = ['mulIndex' => [], 'mulUni' => []];
        foreach ($indexList as $index) {
            $keyName = $index['Key_name'];
            $column = $index['Column_name'];
            if ($keyName == 'PRIMARY') {
                continue;
            }
            $vName = $index['Non_unique'] ? 'mulIndex' : 'mulUni';

            if (isset($mulList[$vName][$keyName])) {
                $mulList[$vName][$keyName][] = $column;
                $keys[$vName][$keyName] = $mulList[$vName][$keyName];
            } else {
                $mulList[$vName][$keyName] = [$column];
                if ($vName == 'mulIndex') {
                    $keys[$vName][$keyName] = $mulList[$vName][$keyName];
                }
            }
        }

        return $keys;
    }

    public function tableModelNamespace() {
        $defNs = Kernel::toUpper($this->dbname, Kernel::UDL . Kernel::HZL);
        return Kernel::TOKNOT_NS . Kernel::NS . 'TableModel' . Kernel::NS . $defNs;
    }

    protected function tableModelString() {
        $offset = 0;
        $tableColumns = [];
        do {
            $tableCols = $this->getDBTablesInfo($offset);
            $tableColumns = array_merge($tableColumns, $tableCols);
            $offset += 100;
        } while ($tableCols);
        $tableList = [];
        foreach ($tableColumns as $col) {
            $tableName = $col['TABLE_NAME'];
            $column = $col['COLUMN_NAME'];
            if (empty($tableList[$tableName])) {
                $tableList[$tableName] = [];
                $tableList[$tableName]['column'] = [];
                $tableList[$tableName]['columnInfo'] = [];
                $tableList[$tableName]['key'] = '';
                $tableList[$tableName]['uni'] = [];
                $tableList[$tableName]['index'] = [];
                $tableList[$tableName]['ai'] = false;
                $tableList[$tableName]['mul'] = false;
            }

            $tableList[$tableName]['column'][] = $column;

            $values = [];
            if ($col['DATA_TYPE'] == 'set' || $col['DATA_TYPE'] == 'enum') {
                $len = strlen($col['DATA_TYPE']);
                $values = explode(Kernel::COMMA, str_replace(Kernel::QUOTE, Kernel::NOP, substr($col['COLUMN_TYPE'], $len + 1, -1)));
            }
            if ($col['CHARACTER_MAXIMUM_LENGTH'] !== null) {
                $len = $col['CHARACTER_MAXIMUM_LENGTH'];
            } elseif ($col['NUMERIC_PRECISION'] !== null) {
                $len = $col['NUMERIC_PRECISION'];
            } elseif ($col['DATETIME_PRECISION'] !== null) {
                $len = $col['DATETIME_PRECISION'];
            }
            $tableList[$tableName]['columnInfo'][$column] = [strtoupper($col['DATA_TYPE']), $len,
                $col['NUMERIC_SCALE'],
                $col['COLUMN_DEFAULT'], $values];

            if ($col['COLUMN_KEY'] == 'PRI') {
                $tableList[$tableName]['key'] = $column;
            } elseif ($col['COLUMN_KEY'] == 'UNI') {
                $tableList[$tableName]['uni'][] = $column;
            } elseif ($col['COLUMN_KEY'] == 'MUL') {
                $tableList[$tableName]['mul'] = true;
            }

            if ($col['EXTRA'] == 'auto_increment') {
                $tableList[$tableName]['ai'] = true;
            }
        }

        $class = Kernel::PHP . '/* Auto generate by toknot at Date:' . date('Y-m-d H:i:s') . ' */' . Kernel::EOL;
        $class .= Kernel::DEF_NS . $this->tableModelNamespace() . Kernel::SEMI;
        $class .= Kernel::DEF_USE . TableModel::class . Kernel::SEMI . Kernel::EOL;
        foreach ($tableList as $tableName => $cols) {
            if ($cols['mul']) {
                $keys = $this->getTableIndex($tableName);
            } else {
                $keys = [];
            }

            if ($this->tablePrefix) {
                $tableName = substr($tableName, strlen($this->tablePrefix));
            }
            $class .= Kernel::DEF_CLASS . Kernel::toUpper($tableName, Kernel::UDL) . Kernel::DEF_EXTENDS . 'TableModel' . Kernel::LB . Kernel::EOL;
            $sep = Kernel::BACKTICK . Kernel::COMMA . Kernel::SP . Kernel::BACKTICK;
            $class .= $this->generateTableModelProperty('selectFeild', Kernel::BACKTICK . join($sep, $cols['column']) . Kernel::BACKTICK);
            $class .= $this->generateTableModelProperty('tableName', $tableName);
            $class .= $this->generateTableModelProperty('columnList', $cols['column'], true);
            $class .= $this->generateTableModelProperty('keyName', $cols['key']);
            $class .= $this->generateTableModelProperty('ai', $cols['ai']);
            $class .= $this->generateTableModelProperty('unique', $cols['uni'], true);
            if ($keys) {
                $class .= $this->generateTableModelProperty('index', array_merge($keys['mulIndex']), true);
                $class .= $this->generateTableModelProperty('mulUnique', $keys['mulUni'], true);
            }
            $class .= $this->generateTableModelProperty('cols', $cols['columnInfo'], true);
            $class .= $this->generateTableConst('NAME', $tableName);
            $class .= $this->generateTableConst('KEY', $cols['key']);
            $class .= Kernel::RB . Kernel::EOL;
        }
        return $class;
    }

    public function generateTableModel() {
        $class = $this->tableModelString();
        file_put_contents($this->tableModelCacheFile, $class);
    }

    protected function queryDBName() {
        $this->dbname = $this->query('SELECT database()')->fetchColumn();
    }

    protected function generateTableConst($const, $expression) {
        return 'CONST ' . strtoupper($const) . Kernel::EQ . var_export($expression, true) . Kernel::SEMI . Kernel::EOL;
    }

    protected function generateTableModelProperty($varName, $expression, $trimeol = false) {
        $expressionStr = var_export($expression, true);
        if ($trimeol) {
            $expressionStr = str_replace(Kernel::EOL, Kernel::NOP, $expressionStr);
        }
        return Kernel::DEF_PROTECTED . Kernel::DOLAR . $varName . Kernel::EQ . $expressionStr . Kernel::SEMI . Kernel::EOL;
    }

    public function transaction(callable $queryCallabe, callable $afterRollback) {
        $this->beginTransaction();
        try {
            $res = $queryCallabe();
            $this->commit();
            return $res;
        } catch (\Exception $e) {
            $this->rollBack();
            return $afterRollback($e);
        } catch (\Error $e) {
            $this->rollBack();
            return $afterRollback($e);
        }
    }

    public function beginWork() {
        return $this->query('EGIN WORK');
    }

    public function commitWork() {
        return $this->query('COMMIT WORK');
    }

    public function setAutocommit() {
        if (!$this->isConnectAutocommit()) {
            $this->sessionQueryAutocommitChanged = true;
            return $this->query("SET autocommit = 1");
        }
        return true;
    }

    public function unAutocommit() {
        if ($this->isConnectAutocommit()) {
            $this->sessionQueryCommit = true;
            return $this->query("SET autocommit = 0");
        }
        return true;
    }

    public function commit() {
        parent::commit();
        if ($this->isConnectAutocommit()) {
            $status = $this->query('select @@session.autocommit')->fetchColumn(0);
            $newStatus = intval(!$status);
            $this->sessionQueryAutocommitChanged = false;
            if ($newStatus == $this->isConnectAutocommit()) {
                return $this->query("SET autocommit = $newStatus");
            }
        }
    }

}
