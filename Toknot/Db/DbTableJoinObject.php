<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;
use Toknot\Db\DbCRUD;
use Toknot\Db\DbTableJoinObject;
use \InvalidArgumentException;

class DbTableJoinObject extends DbCRUD{
    public function __construct(DbTableJoinObject $table1, DbTableObject $table2) {
        $tableList = func_get_args();
        foreach($tableList as $tableObject) {
            if(!$tableObject instanceof DbTableJoinObject) {
                throw new InvalidArgumentException();
            }
            $this->interatorArray[$tableObject->tableName] = $tableObject;
        }
    }
    public function __get($name) {
        if(isset($this->interatorArray[$name])) {
            return $this->interatorArray[$name];
        }
    }
    public function tableON($key1, $key2) {
        $tableList = func_get_args();
    }
}

?>
