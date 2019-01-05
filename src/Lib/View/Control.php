<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\View;

class Control {

    public static function iterator($dataName, $tpl, $itemName, $keyName = '') {
        $key = $keyName ? (self::varExp($keyName) . ' =>') : '';
        $dataName = ltrim($dataName, '$');
        $str = '<?php foreach (' . self::varExp($dataName) . " as $key " . self::varExp($itemName) . ") {?>$tpl<?php } ?>";
        return $str;
    }

    public static function ifexp($tpl, $exp) {
        $str = "<?php if({$exp}) { ?>$tpl<?php } ?>";
        return $str;
    }

    public static function ifelse($explist, $elseTpl = '') {
        $str = '';
        $i = 0;
        foreach ($explist as $exp => $tpl) {
            $elseif = $i > 0 ? '} else' : '';
            $str .= "<?php {$elseif}if({$exp}) { ?>$tpl";
            $i++;
        }
        if ($elseTpl) {
            $str .= "<?php } else { ?>$elseTpl<?php } ?>";
        }
        return $str;
    }

    public static function hasArrVar($arrName, $keyName) {
        return 'isset(' . self::arrVarExp($arrName, $keyName) . ')';
    }

    public static function notHasArrVar($arrName, $keyName) {
        return '!' . self::hasArrVar($arrName, $keyName);
    }

    public static function echoVar($varName) {
        return '<?=' . self::varExp($varName) . '?>';
    }

    public static function echoArrValue($arrName, ...$keyName) {
        array_unshift($keyName, $arrName);
        return '<?=' . call_user_func_array(array(__CLASS__, 'arrVarExp'), $keyName) . '?>';
    }

    public static function arrVarExp($arrName, ...$keyName) {
        return "\${$arrName}['" . join('\'][\'', $keyName) . '\']';
    }

    public static function arrKeyVarExp($arrName, $keyVarName) {
        return "\${$arrName}[\${$keyVarName}]";
    }

    public static function varExp($varName) {
        return '$' . $varName;
    }

    public static function scopeVarName($varName) {
        return $varName . '_' . md5($varName . microtime() . mt_rand(10000, 99999));
    }

    public static function setVar($varNameTpl, $valueTpl) {
        return "<?php \$$varNameTpl = $valueTpl ?>";
    }

    public static function callFunc($function, ...$args) {
        return "$function(" . join(',', $args) . ")";
    }

    public static function arrayElementExp($key, $var) {
        return "$key => $var,";
    }

}
