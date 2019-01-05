<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\View;

use Toknot\Boot\Kernel;

class Element {

    public static function table($dataName, $title, $attr = '', $local = false) {
        if (is_array($attr)) {
            $attr = self::batchSetTagAttr($attr);
        }
        $html = "<table $attr><thead><tr>";

        $itemName = 'tValue';
        if ($local) {
            $itemName = Control::scopeVarName($itemName);
        }

        $dataTr = '';
        foreach ($title as $k => $v) {
            $html .= "<th>$v</th>";
            $dataTr .= '<td>' . Control::arrVarExp($itemName, $k) . '</td>';
        }
        $html .= '</thead><tbody>';

        $html .= Control::iterator($dataName, $dataTr, $itemName);
        $html .= '</tbody></table>';
        return $html;
    }

    public static function tag($tagName, $attr = '') {
        return "<{$tagName}{$attr}>";
    }

    public static function endTag($tag) {
        return "</$tag>";
    }

    public static function liList($dataName, $attr = '') {
        if (is_array($attr)) {
            $attr = self::batchSetTagAttr($attr);
        }

        $html = self::tag('ul', $attr);
        $inVar = Control::scopeVarName('lValue');

        $atag = '<a' . self::setTagAttr('href', self::echoArrValue($inVar, 'href')) . '>';
        $ifa = Control::ifexp(Control::hasArrVar($inVar, 'href'), $atag);

        $ifa2 = Control::ifexp(Control::hasArrVar($inVar, 'href'), '</a>');

        $li = self::tag('li') . $ifa . self::echoArrValue($inVar, 'title') . $ifa2 . self::endTag('li');

        $html .= Control::iterator($dataName, $li, $inVar);
        $html .= self::endTag('ul');
        return $html;
    }

    /**
     *  type, div-attr,input-attr,option, label
     * 
     * @param string $dataName
     * @param array $attr
     */
    public static function form($dataName, $attr = '') {
        if (is_array($attr)) {
            $attr = self::batchSetTagAttr($attr);
        }

        $html = self::tag('form', $attr);
        $inVar = Control::scopeVarName('formInputItem');

        $attrVar = Control::scopeVarName('formInputItemAttr');
        $attrVarKey = Control::scopeVarName('formInputItemAttrKey');
        $inputOptionVar = Control::scopeVarName('inputOptionVar');
        $inputOptionVarKey = Control::scopeVarName('inputOptionVarKey');
        $merge = Control::scopeVarName('merge');

        $expList = [];

        $typeExp = Control::arrVarExp($inVar, 'type');

        $labelForExp = Control::ifexp(' for="' . Control::echoArrValue($inVar, 'id') . '"', Control::hasArrVar($inVar, 'id'));

        $labelExp = Control::ifexp("<label{$labelForExp}>" . Control::echoArrValue($inVar, 'label') . '</label>', Control::hasArrVar($inVar, 'label'));

        $singleAttr = self::echoTagAttr($attrVarKey, $attrVar);
        $pushAttr = '['. Control::arrayElementExp('type', Control::arrVarExp($inVar, 'type')) . Control::arrayElementExp('id', Control::arrVarExp($inVar, 'id')) . ']';
        $checkAttrArr = Control::ifexp('<?php ' . Control::arrVarExp($inVar, 'input-attr') . '=[];?>', Control::notHasArrVar($inVar, 'input-attr'));

        $mergeTpl = $checkAttrArr . Control::setVar($merge, Control::callFunc('array_merge', Control::arrVarExp($inVar, 'input-attr'), $pushAttr));

        $inputAttr = $mergeTpl . Control::iterator($merge, $singleAttr, $attrVar, $attrVarKey);
        //$inputAttr = Control::ifexp($inputAttr, Control::hasArrVar($inVar, 'input-attr'));
        $selected = Control::ifexp(' selected', Control::varExp($inputOptionVar) . '==' . Control::arrVarExp($inVar, 'input-attr', 'value'));
        $optionTpl = '<option value="' . Control::echoVar($inputOptionVar) . '"' . $selected . '>' . Control::echoVar($inputOptionVarKey) . '</option>';
        $options = Control::iterator(Control::arrVarExp($inVar, 'option'), $optionTpl, $inputOptionVar, $inputOptionVarKey);

        $selectType = "$typeExp=='select'";
        $selectTpl = "<div>$labelExp<select{$inputAttr}>$options</select></div>";
        $expList[$selectType] = $selectTpl;
        $textareaType = "$typeExp=='textarea'";
        $textareaTpl = "<div>$labelExp<textarea{$inputAttr}></textarea></div>";
        $expList[$textareaType] = $textareaTpl;

        $buttonType = "$typeExp=='button'";
        $buttonTpl = "<button{$inputAttr}>" . Control::echoArrValue($inVar, 'input-attr', 'value') . "</button>";
        $expList[$buttonType] = $buttonTpl;


        $inputTpl = "<div>$labelExp<input{$inputAttr}></div>";

        $input = Control::ifelse($expList, $inputTpl);

        $html .= Control::iterator($dataName, $input, $inVar);
        $html .= '</form>';
        return $html;
    }

    public static function echoTagAttrArr($arrName, $keyName) {
        $arr = Control::hasArrVar($arrName, $keyName);
        $tpl = self::setTagAttr($keyName, Control::echoArrValue($arrName, $keyName));
        return Control::ifexp($tpl, $arr);
    }

    public static function echoTagAttr($varName, $valueName) {
        return self::setTagAttr(Control::echoVar($varName), Control::echoVar($valueName));
    }

    public static function setTagAttr($attrName, $value) {
        return Kernel::SP . $attrName . '="' . $value . '"';
    }

    public static function batchSetTagAttr($arr) {
        $attr = Kernel::NOP;
        foreach ($arr as $k => $v) {
            $attr .= self::setTagAttr($k, $v);
        }
        return $attr;
    }

}
