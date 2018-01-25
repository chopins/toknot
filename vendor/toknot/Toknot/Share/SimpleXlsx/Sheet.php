<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\SimpleXlsx;

use Toknot\Share\File;

/**
 * Sheet
 *
 */
class Sheet {

    protected $xlsxObj = null;
    protected $sheetName = '';
    protected $sheetIndex = 1;
    protected $rowNum = 1;
    protected $columMaxNum;
    protected $workSpace;
    protected $sheetFile = '';
    protected $worksheetsFile = '/xl/worksheets/sheet%d.xml';
    protected $shared = null;
    protected $sheetXlObj = null;
    protected $dimension = [];
    protected $sharedObj = null;

    public function __construct(Xlsx $xlsx, $sheetName = '') {
        $this->xlsxObj = $xlsx;
        $this->sheetName = $sheetName;
        $this->workSpace = $xlsx->getWorkspace();
    }

    public function setShared($shared) {
        $this->shared = $shared;
    }

    public function getSheetName() {
        return $this->sheetName;
    }

    public function getDimension() {
        return $this->dimension;
    }

    public function setSheetIndex($index) {
        $this->sheetIndex = $index;
    }

    public function getSheetIndex() {
        return $this->sheetIndex;
    }

    public function convertDimension($dimension) {
        list(, $endPos) = explode(':', $dimension);
        $columns = $row = 0;
        if (preg_match('/^([A-Z]+)([0-9]+)$/i', $endPos, $matches)) {
            unset($matches[0]);
            $columns = $this->xlsxObj->coverOrder2Alphabet($matches[1]);
            $rows = $matches[2];
        }

        $this->dimension = ['col' => $columns, 'row' => $rows];
    }

    public function loadSheet() {
        $xl = sprintf($this->workSpace . $this->worksheetsFile, $this->sheetIndex);
        $this->sheetXlObj = new File($xl, 'rb');
        $search = '<dimension';
        $this->sheetXlObj->strpos($search);

        $dimension = $this->sheetXlObj->findRange('ref="', '"');
        $this->convertDimension($dimension);

        $this->sheetXlObj->strpos('<sheetData>');
    }

    public function readRow() {
        $data = $this->sheetXlObj->findRange('<row ', '</row>');
        if (!$data) {
            return false;
        }
        $doc = new \DOMDocument;

        $doc->loadXML("<row $data</row>");
        $nodes = $doc->getElementsByTagName('c');
        $res = [];
        foreach ($nodes as $node) {
            $type = $node->getAttribute('t');
            $v = $node->getElementsByTagName('v');
            $k = $node->getAttribute('r');
            $value = $v->item(0)->nodeValue;
            if ($type == 's') {
                $res[$k] = $this->shared->getShared($value);
            } else {
                $res[$k] = $value;
            }
        }
        return $res;
    }

    public function addRow($row) {
        $xml = '<row r="%d" customFormat="false" ht="12.8" hidden="false" customHeight="false" outlineLevel="0" collapsed="false">';

        $xml = sprintf($xml, $this->rowNum);
        $columPos = 0;

        foreach ($row as $v) {
            $alp = $this->xlsxObj->covertAlphabetOrder($columPos);
            if (is_numeric($v)) {
                $t = '';
            } else {
                $t = 's="0" t="s"';
                $this->shared->addShared($v);
            }

            $xml .= "<c r=\"{$alp}{$this->rowNum}\" $t><v>$v</v></c>";
            $columPos++;
        }
        if ($columPos > $this->columMaxNum) {
            $this->columMaxNum = $columPos;
        }
        $xml .= '</row>' . PHP_EOL;
        $this->rowNum++;
        $this->xlsxObj->saveXml("_sheet_tmpfile.{$this->sheetIndex}.xml", $xml, FILE_APPEND);
        return $this;
    }

    public function getSheetFile() {
        return $this->sheetFile;
    }

    public function saveSheet() {
        $column = $this->columMaxNum - 1;
        $endPos = $this->xlsxObj->covertAlphabetOrder($column) . ($this->rowNum - 1);
        
        $this->dimension = ['col' => $column, 'row' => $this->rowNum - 1];
        $this->sheetFile = sprintf($this->worksheetsFile, $this->sheetIndex);
        $xml = $this->xlsxObj->readTemplateXml('sheet.head.xml');
        $xml = str_replace('%$$endPos$$%', $endPos, $xml);

        $this->xlsxObj->saveXml($this->sheetFile, $xml);
        $dataFile = "{$this->workSpace}/_sheet_tmpfile.$this->sheetIndex.xml";
        $data = new File($dataFile, 'r');
        $sfp = new File("{$this->workSpace}/{$this->sheetFile}", 'a+');
        foreach ($data as $line) {
            $sfp->fwrite(trim($line));
        }
        unlink($dataFile);
        $end = $this->xlsxObj->readTemplateXml('sheet.end.xml');
        $this->xlsxObj->saveXml($this->sheetFile, $end, FILE_APPEND);
        return $this->sheetFile;
    }

}
