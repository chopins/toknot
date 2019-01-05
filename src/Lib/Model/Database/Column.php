<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Database;

use Toknot\Boot\Kernel;
use Toknot\Lib\Model\Database\Expression;
use Toknot\Lib\Model\Database\QueryBuild;
use Toknot\Lib\Model\Database\TableModel;
use Toknot\Lib\Model\Database\QueryExpression;

class Column extends QueryExpression {

    /**
     *
     * @var \Toknot\Lib\Model\Database\QueryBuild
     */
    protected $query = null;
    protected $name = '';
    protected $type = '';
    protected $length = 0;
    protected $exp = null;
    protected $enumList = [];
    protected $default = null;
    protected $table = null;
    protected $bind = true;
    protected $hasDefault = false;
    protected $scale = 0;
    public static $guessString = Kernel::NOP;
    public static $guessNumber = 0;
    public static $guessJSON = '[]';
    public static $guessTime = 'now';
    public static $guessEnum = 0;

    public function __construct($name, TableModel $table, QueryBuild $query, $info) {
        $this->name = $name;
        $this->query = $query;
        $this->table = $table;
        list($this->type, $this->length, $this->scale, $this->default, $this->enumList) = $info;
        $this->defaultValue();
    }

    protected function defaultValue() {
        if ($this->default !== null) {
            $this->hasDefault = true;
        } else {
            $this->hasDefault = false;
        }
    }

    public function isAutoIncrement() {
        if ($this->isKey() && $this->table->isAutoIncrement()) {
            return true;
        }
    }

    public function getType() {
        return $this->type;
    }

    public function getName() {
        return $this->name;
    }

    public function getLength() {
        return $this->length;
    }

    public function getScaleLenght() {
        return $this->scale;
    }

    public function noBind() {
        $this->bind = false;
        return $this;
    }

    public function isKey() {
        return $this->table->getKey() == $this->name;
    }

    public function isUnique() {
        return in_array($this->name, $this->table->getUnique());
    }

    public function isIndex() {
        $indexs = $this->table->getIndex();
        foreach ($indexs as $keyName => $index) {
            if (count($index) === 0 && in_array($this->name, $index)) {
                return $keyName;
            }
        }
        return false;
    }

    public function inMulIndex() {
        $indexs = $this->table->getIndex();
        foreach ($indexs as $keyName => $index) {
            if (count($index) > 1 && in_array($this->name, $index)) {
                return $keyName;
            }
        }
        return false;
    }

    public function inMulUnique() {
        $uniques = $this->table->getMulUnique();
        foreach ($uniques as $index) {
            if (in_array($this->name, $index)) {
                return true;
            }
        }
        return false;
    }

    public function hasDefault() {
        return $this->hasDefault;
    }

    public function getDefault() {
        return $this->default;
    }

    public function guessInsertDefaultValue() {
        if ($this->isKey() || $this->isUnique()) {
            Kernel::runtimeException("column $this->name can not guess defalut value", E_USER_WARNING);
        }
        if ($this->isString()) {
            return self::$guessString;
        } elseif ($this->isNumber()) {
            return self::$guessNumber;
        } elseif ($this->isJSON()) {
            return self::$guessJSON;
        } elseif ($this->isTime()) {
            return self::$guessTime === 'now' ? date('Y-m-d H:i:s') : self::$guessTime;
        } elseif ($this->isEnum()) {
            return $this->enumList[self::$guessEnum];
        }
        Kernel::runtimeException("column $this->name can not guess defalut value", E_USER_NOTICE);
    }

    public function isString() {
        $typeList = ['CHAR', 'TEXT', 'BINARY', 'BLOB'];
        return $this->checkType($typeList);
    }

    public function isNumber() {
        $typeList = ['INT', 'FLOAT', 'DOUBLE', 'REAL', 'BIT', 'BOOLEAN', 'SERIAL', 'DECIMAL'];
        return $this->checkType($typeList);
    }

    public function isJSON() {
        return $this->caseType('JSON');
    }

    public function isEnum() {
        return $this->caseType('ENUM') || $this->caseType('SET');
    }

    public function isTime() {
        $typeList = ['DATE', 'TIME', 'YEAR'];
        return $this->checkType($typeList);
    }

    /**
     * 
     * @return \Toknot\Lib\Model\Database\TableModel
     */
    public function getTable() {
        return $this->table;
    }

    public function func($name, ...$args) {
        $exp = new FunctionExpression($name);
        $exp->arg($this);
        $exp->args($args);
        return $exp;
    }

    public function set($value) {
        return $this->expression(Kernel::EQ, $value, Expression::TYPE_SET_VALUE);
    }

    public function eq($value) {
        if (is_array($value)) {
            return $this->in($value);
        }
        return $this->expression(Kernel::EQ, $value);
    }

    public function lt($value) {
        return $this->expression(Kernel::LT, $value);
    }

    public function gt($value) {
        return $this->expression(Kernel::GT, $value);
    }

    public function le($value) {
        return $this->expression(Kernel::LE, $value);
    }

    public function ge($value) {
        return $this->expression(Kernel::GE, $value);
    }

    public function neq($value) {
        return $this->expression(Kernel::NEQ, $value);
    }

    public function lg($value) {
        return $this->expression(Kernel::LG, $value);
    }

    public function add($value) {
        return $this->expression(Kernel::M_ADD, $value);
    }

    public function sub($value) {
        return $this->expression(Kernel::M_SUB, $value);
    }

    public function mul($value) {
        return $this->expression(Kernel::M_MUL, $value);
    }

    public function div($value) {
        return $this->expression(Kernel::M_DIV, $value);
    }

    public function in($value) {
        return $this->expression(QueryBuild::IN, $value, Expression::TYPE_LIKE_FUNC);
    }

    public function notIn($value) {
        return $this->expression(QueryBuild::NOT_IN, $value, Expression::TYPE_LIKE_FUNC);
    }

    public function notlike($value) {
        return $this->expression(Kernel::SP . Kernel::L_NOT . QueryBuild::LKIE, $value);
    }

    public function like($value) {
        return $this->likeExpression($value, Kernel::PERCENT, Kernel::PAD_BOTH);
    }

    public function like1($value) {
        return $this->likeExpression($value, Kernel::UDL, Kernel::PAD_BOTH);
    }

    public function leftLike($value) {
        return $this->likeExpression($value, Kernel::PERCENT, Kernel::PAD_LEFT);
    }

    public function leftLike1($value) {
        return $this->likeExpression($value, Kernel::UDL, Kernel::PAD_LEFT);
    }

    public function rightLike($value) {
        return $this->likeExpression($value, Kernel::PERCENT, Kernel::PAD_RIGHT);
    }

    public function rightLike1($value) {
        return $this->likeExpression($value, Kernel::UDL, Kernel::PAD_RIGHT);
    }

    public function strictLike($value) {
        return $this->likeExpression($value);
    }

    public function getExpression() {
        $alias = $this->table->getAlias();
        if ($alias) {
            $alias .= Kernel::DOT;
        }
        return $alias . Kernel::BACKTICK . $this->name . Kernel::BACKTICK;
    }

    protected function filterData($value) {
        if ($this->isNumber() && !is_numeric($value)) {
            throw new Exception("Column '$this->name' need a numeric value, other given");
        } elseif ($this->isJSON() && is_array($value)) {
            return $this->table->quote(json_encode($value));
        } elseif ($this->isTime() && is_numeric($value)) {
            return $this->filterTime($value);
        } elseif ($this->isEnum() && !in_array($value, $this->enumlist)) {
            throw new Exception("given enum value '$value' invaild");
        } else {
            return $value;
        }
    }

    protected function filterTime($value) {
        if ($this->caseType('DATE')) {
            return date('Y-m-d', $value);
        } elseif ($this->caseType('DATETIME')) {
            return date('Y-m-d H:i:s', $value);
        } elseif ($this->caseType('TIMESTAMP')) {
            return date('Y-m-d H:i:s', $value);
        } elseif ($this->caseType('YEAR')) {
            $f = $this->length == 2 ? 'y' : 'Y';
            return date($f, $value);
        } elseif ($this->caseType('TIME')) {
            return date('H:i:s', $value);
        }
    }

    protected function checkType($typeList) {
        foreach ($typeList as $t) {
            if (stripos($this->type, $t) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function caseType($type) {
        return Kernel::caseEq($this->type, $type);
    }

    protected function likeExpression($value, $pad = Kernel::NOP, $num = Kernel::PAD_NO) {
        if ($num === Kernel::PAD_LEFT) {
            $value = $pad . $value;
        } elseif ($num === Kernel::PAD_RIGHT) {
            $value = $value . $pad;
        } elseif ($num === Kernel::PAD_BOTH) {
            $value = $pad . $value . $pad;
        }
        return $this->expression(QueryBuild::LKIE, $value);
    }

    public function expression($expOp, $value, $expType = 0) {
        $this->exp = new Expression($expOp, $expType);
        $this->expressionColFlag($this, $this->exp);
        $this->exp->left($this);
        $value = $this->filterData($value);
        if (is_scalar($value) && $this->bind) {
            $setValue = $this->query->bindColParameterValue($this, $value);
            $this->exp->bindRight();
        } else {
            $setValue = $value;
        }
        $this->bind = true;
        $this->exp->right($setValue);
        return $this->exp;
    }

    protected function expressionColFlag($col, $exp) {
        if ($col->isKey()) {
            return $exp->hasKey(true);
        } elseif ($col->isUnique()) {
            return $exp->hasUnique(true);
        } elseif ($col->isIndex()) {
            return $exp->hasIndex(true);
        }
        return false;
    }

}
