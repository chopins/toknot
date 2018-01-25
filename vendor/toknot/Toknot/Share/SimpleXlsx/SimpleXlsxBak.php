<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\SimpleXlsx;

use Toknot\Boot\Kernel;
use Toknot\Exception\BaseException;
use Toknot\Share\File;

/**
 * SimpleXlsx
 *
 * @author chopin
 */
class SimpleXlsxBak {
    protected $rootPath = '/tmp';
    protected $relsFile = '/_rels/.rels';
    protected $appFile = '/docProps/app.xml';
    protected $coreFile = '/docProps/core.xml';
    protected $workbookRelsFile = '/xl/_rels/workbook.xml.rels';
    protected $workbookFile = '/xl/workbook.xml';
    protected $stylesFile = '/xl/styles.xml';
    protected $sharedStringsFile = '/xl/sharedStrings.xml';
    protected $worksheetsFile = '/xl/worksheets/sheet%d.xml';
    protected $typeFile = '[Content_Types].xml';
    protected $themeFile = '/xl/theme/theme1.xml';
    protected $createTime = '';
    protected $workspacedir = '';
    protected $alphabet = [];
    protected $rowNum = [];
    protected $columMaxNum = 1;
    protected $sheetnum = 0;
    protected $sheetNames = [];
    protected $xmlfile = '';
    protected $shareStringCnt = 0;
    protected $shareAllCnt = 0;
    protected $zip = null;
    protected $hasShared = 0;
    private $xlsxInit = false;
    private $extractDir = null;
    private $sheetList = [];
    private $xlFileObj = [];
    private $sharedFileObj = null;
    private $xlsxLoad = false;

    /**
     * Create a xlsx file
     * 
     * <code>
     *  $xlsx = new SimpleXlsx();
     *  $xlsx->createXlsx('/your_file_save_path/test.xlsx');
     *  $index = $xlsx->newSheet('test');
     *  for ($i = 0; $i < 1000; $i++) {
     *       $row = range(1, 100);
     *       $xlsx->addRow($row, $index);
     *  }
     *  $xlsx->save();
     * 
     *  $xls->loadXlsx('your_file_save_path/test.xlsx');
     *  $xls->readSheet(1, $pos);
     *  while($r = $xls->row()) {
     *      
     *  }
     * </code>
     * 
     * @param string $xmlfile
     */
    public function __construct() {
        if (!class_exists('ZipArchive', false)) {
            throw new BaseException('xlsx need php zip extension');
        }
        if(!class_exists('DOMDocument', false)) {
            throw new BaseException('xlsx need php zip extension');
        }
        $this->alphabetOrder();
        $this->getTmpDir();
    }

    /**
     * load a xlsx file
     * 
     * @param string $xlsx
     * @throws BaseException
     */
    public function loadXlsx($xlsx) {
        if (!file_exists($xlsx)) {
            throw new BaseException("$xlsx not exists");
        }
        if ($this->xlsxLoad) {
            throw new BaseException('must close previous xlsx file');
        }
        $xlsx = realpath($xlsx);
        $xlsxName = basename($xlsx, '.xlsx');
        
        $this->workspacedir = "$this->rootPath/$xlsxName";
        $this->extractDir = "$this->workspacedir/$xlsxName";
        mkdir($this->workspacedir);
        mkdir($this->extractDir);

        $zipfile = "$this->workspacedir/$xlsxName.zip";
        copy($xlsx, $zipfile);
        $this->zip = new \ZipArchive();
        $this->zip->open($zipfile, \ZipArchive::CREATE);
        $this->zip->extractTo($this->extractDir);
        $this->zip->close();

        $doc = new \DOMDocument();
        $workBook = $this->extractDir . $this->workbookFile;
        $doc->load($workBook);
        $sheets = $doc->getElementsByTagName('sheets')->item(0)->getElementsByTagName('sheet');
        $this->sheetList = [];
        foreach ($sheets as $sheet) {
            if ($sheet->tagName == 'sheet') {
                $fid = $sheet->getAttribute('sheetId');
                $sheetName = $sheet->getAttribute('name');
                $this->sheetList[$fid] = $sheetName;
            }
        }
        $this->xlsxLoad = true;
    }

    public function close() {
        $this->clean();
        $this->sharedFileObj = null;
        $this->sheetList = [];
        $this->zip = null;
        $this->xlsxLoad = false;
        $this->extractDir = '';
        $this->workspacedir = '';
        $this->xlFileObj = [];
    }

    /**
     * Get xlsx file of sheet list 
     * 
     * @return array
     */
    public function getSheetList() {
        return $this->sheetList;
    }

    /**
     * read a sheet of xlsx
     * 
     * @param int|string $index  the sheet name or order number
     * @param array $pos         the sheet max row and columns
     * @return $this
     * @throws BaseException
     */
    public function readSheet($index, &$pos) {
        if (is_numeric($index) && isset($this->sheetList[$index])) {
            $id = $index;
        } elseif (($idx = array_search($index, $this->sheetList)) !== false) {
            $id = $idx;
        } else {
            throw new BaseException("sheet $index not exists");
        }
        $xl = sprintf($this->extractDir . $this->worksheetsFile, $id);
        $this->xlFileObj[$id] = new File($xl, 'rb');
        $search = '<dimension';
        $offs = $this->xlFileObj[$id]->strpos($search);

        $dimension = $this->xlFileObj[$id]->findRange('ref="', '"');

        list(, $endPos) = explode(':', $dimension);

        if (preg_match('/^([A-Z]+)([0-9]+)$/i', $endPos, $matches)) {

            unset($matches[0]);
            $columns = $this->coverOrder2Alphabet($matches[1]);
            $rows = $matches[2];
        }
        $pos = ['col' => $columns, 'row' => $rows];

        $this->xlFileObj[$id]->strpos('<sheetData>');
        $this->sharedFileObj = new File($this->extractDir . $this->sharedStringsFile, 'rb');
        return $id;
    }

    /**
     * get a row from sheet and seek next row
     * 
     * @return boolean|array
     */
    public function row($id) {
        if(!$this->xlFileObj[$id]) {
            throw new BaseException('no read a sheet');
        }
        $data = $this->xlFileObj[$id]->findRange('<row ', '</row>');
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
                $res[$k] = $this->getShared($value);
            } else {
                $res[$k] = $value;
            }
        }
        return $res;
    }

    protected function getShared($k) {
        $this->sharedFileObj->fseek(0);
        $i = 0;
        while (true) {
            $this->sharedFileObj->strpos('<t ');
            if ($i == $k) {
                return $this->sharedFileObj->findRange('>', '</t>');
            }
            $i++;
        }
        return false;
    }

    /**
     * create a xlsx file
     * 
     * @param string $xlsx
     * @return $this
     * @throws BaseException
     */
    public function createXlsx($xlsx) {
        if ($this->xlsxInit) {
            throw new BaseException('previous xlsx not save, or call SimpleXlsx::clean()');
        }
        $this->xlsxInit = true;
        $xlsxName = basename($xlsx, '.xlsx');
        $this->xmlfile = $xlsx;
        $this->createTime = '2006-09-16T00:00:00Z';

        $this->createDirStruct($xlsxName);
        $zipdir = $this->workspacedir;
        Kernel::single()->attachShutdownFunction(function() use($zipdir) {
            if (is_dir($zipdir)) {
                Kernel::rmdir($zipdir, true);
            }
        });
        $this->createRels();
        $this->createAppXml();
        $this->createCoreXml();
        $this->createStyleXml();
        //$this->createTheme();
        return $this;
    }

    protected function getTmpDir() {
        $this->rootPath = sys_get_temp_dir();
    }

    protected function alphabetOrder() {
        $this->alphabet = range('A', 'Z');
    }

    protected function covertAlphabetOrder($number) {
        $number = base_convert($number, 10, 26);
        $ret = '';
        $len = strlen($number);
        for ($i = 0; $i < $len; $i++) {
            $index = base_convert($number{$i}, 26, 10);
            ($i < $len - 1) && $index--;
            $ret = $ret . $this->alphabet[$index];
        }
        return $ret;
    }

    protected function coverOrder2Alphabet($str) {
        $index = $len = strlen($str);
        $re = 0;
        for ($i = 0; $i < $len; $i++) {
            $index--;
            $n = array_search($str{$i}, $this->alphabet) + 1;
            $re = $re + pow($n * 26, $index);
        }
        return $re;
    }

    protected function saveXml($file, $xml, $flag = 0) {
        file_put_contents("{$this->workspacedir}/$file", $xml, $flag);
    }

    protected function createTypesXML() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';

        $item = '<Override PartName="%s" ContentType="%s"/>';

        $rels = 'application/vnd.openxmlformats-package.relationships+xml';
        $xml .= sprintf($item, $this->relsFile, $rels);

        $app = 'application/vnd.openxmlformats-officedocument.extended-properties+xml';
        $xml .= sprintf($item, $this->appFile, $app);

        $core = 'application/vnd.openxmlformats-package.core-properties+xml';
        $xml .= sprintf($item, $this->coreFile, $core);

        $workbookRels = 'application/vnd.openxmlformats-package.relationships+xml';
        $xml .= sprintf($item, $this->workbookRelsFile, $workbookRels);

        $workbook = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml';
        $xml .= sprintf($item, $this->workbookFile, $workbook);


        $styles = 'application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml';
        $xml .= sprintf($item, $this->stylesFile, $styles);

        $worksheets = 'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml';
        for ($i = 1; $i <= $this->sheetnum; $i++) {
            $xml .= sprintf($item, sprintf($this->worksheetsFile, $i), $worksheets);
        }

        $sharedStrings = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml';
        $xml .= sprintf($item, $this->sharedStringsFile, $sharedStrings);

        //$theme = 'application/vnd.openxmlformats-officedocument.theme+xml';
        //$xml .= sprintf($item, $this->themeFile, $theme);
        $xml .= PHP_EOL . '</Types>';
        $this->saveXml($this->typeFile, $xml);
    }

    protected function createTheme() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Toknot SimpleXlsx Theme"><a:themeElements/><a:objectDefaults/><a:extraClrSchemeLst/></a:theme>';
        $this->saveXml($this->themeFile, $xml);
    }

    protected function createWorkbookRels() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        for ($i = 1; $i <= $this->sheetnum; $i++) {
            $id = $i + 1;
            $xml .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $xml .= '<Relationship Id="rId' . ($id + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
        $this->saveXml($this->workbookRelsFile, $xml);
    }

    protected function createRels() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';
        $this->saveXml($this->relsFile, $xml);
    }

    protected function createDirStruct($xlsxName) {
        $this->workspacedir = "{$this->rootPath}/{$xlsxName}";
        mkdir($this->workspacedir);
        mkdir("{$this->workspacedir}/_rels");
        mkdir("{$this->workspacedir}/docProps");
        mkdir("{$this->workspacedir}/xl");
        mkdir("{$this->workspacedir}/xl/_rels");
        mkdir("{$this->workspacedir}/xl/worksheets");
        //mkdir("{$this->zipdir}/xl/theme");
    }

    protected function createAppXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>PHP Toknot SimpleXlsx</Application></Properties>';
        $this->saveXml($this->appFile, $xml);
    }

    protected function time() {
        return date('Y-m-d\TH:i:s\Z');
    }

    protected function createCoreXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dcterms:created xsi:type="dcterms:W3CDTF">%s</dcterms:created><dc:creator></dc:creator><dc:description></dc:description><dc:language>zh-CN</dc:language><cp:lastModifiedBy></cp:lastModifiedBy><dcterms:modified xsi:type="dcterms:W3CDTF">%s</dcterms:modified><cp:revision></cp:revision><dc:subject></dc:subject><dc:title></dc:title></cp:coreProperties>';
        $this->saveXml($this->coreFile, sprintf($xml, $this->createTime, $this->time()));
    }

    protected function createWorkbookXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><fileVersion appName="Calc"/><workbookPr backupFile="false" showObjects="all" date1904="false"/><workbookProtection/><bookViews><workbookView showHorizontalScroll="true" showVerticalScroll="true" showSheetTabs="true" xWindow="0" yWindow="0" windowWidth="16384" windowHeight="8192" tabRatio="500" firstSheet="0" activeTab="0"/></bookViews><sheets>';
        foreach ($this->sheetNames as $i => $name) {
            $id = $i + 2;
            $sn = empty($name) ? "Sheet{$id}" : $name;
            $xml .= '<sheet name="' . $sn . '" sheetId="' . $id . '" state="visible" r:id="rId' . $id . '"/>';
        }
        $xml .= '</sheets><calcPr iterateCount="100" refMode="A1" iterate="false" iterateDelta="0.001"/><extLst><ext xmlns:loext="http://schemas.libreoffice.org/" uri="{7626C862-2A13-11E5-B345-FEFF819CDC9F}"><loext:extCalcPr stringRefSyntax="CalcA1ExcelA1"/></ext></extLst></workbook>';
        $this->saveXml($this->workbookFile, $xml);
    }

    protected function createSheet() {
        $endPos = $this->covertAlphabetOrder($this->columMaxNum - 1) . ($this->rowNum[$this->sheetnum] - 1);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetPr filterMode="false"><pageSetUpPr fitToPage="false"/></sheetPr><dimension ref="A1:' . $endPos . '"/><sheetViews><sheetView showFormulas="false" showGridLines="true" showRowColHeaders="true" showZeros="true" rightToLeft="false" tabSelected="true" showOutlineSymbols="true" defaultGridColor="true" view="normal" topLeftCell="A1" colorId="64" zoomScale="100" zoomScaleNormal="100" zoomScalePageLayoutView="100" workbookViewId="0"><selection pane="topLeft" activeCell="' . $endPos . '" activeCellId="0" sqref="' . $endPos . '"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="13.8" outlineLevelRow="0" outlineLevelCol="0"></sheetFormatPr><cols><col collapsed="false" customWidth="true" hidden="false" outlineLevel="0" max="1025" min="1" style="0" width="8.67"/></cols><sheetData>';

        $sheetsFile = sprintf($this->worksheetsFile, $this->sheetnum);

        $this->saveXml($sheetsFile, $xml);
        $dataFile = "{$this->workspacedir}/_sheet_tmpfile.$this->sheetnum.xml";
        $data = new \SplFileObject($dataFile, 'r');
        $sfp = new \SplFileObject("{$this->workspacedir}/{$sheetsFile}", 'a+');
        foreach ($data as $line) {
            $sfp->fwrite(trim($line));
        }
        unlink($dataFile);
        $end = '</sheetData><printOptions headings="false" gridLines="false" gridLinesSet="true" horizontalCentered="false" verticalCentered="false"/><pageMargins left="0.7875" right="0.7875" top="1.05277777777778" bottom="1.05277777777778" header="0.7875" footer="0.7875"/><pageSetup paperSize="9" scale="100" firstPageNumber="1" fitToWidth="1" fitToHeight="1" pageOrder="downThenOver" orientation="portrait" blackAndWhite="false" draft="false" cellComments="none" useFirstPageNumber="true" horizontalDpi="300" verticalDpi="300" copies="1"/><headerFooter differentFirst="false" differentOddEven="false"><oddHeader></oddHeader><oddFooter></oddFooter></headerFooter></worksheet>';
        $this->saveXml($sheetsFile, $end, FILE_APPEND);
        return $this;
    }

    /**
     * create a sheet of xlsx file
     * 
     * @param string $sheetname     the sheet name
     * @return int                  the sheet index number
     */
    public function newSheet($sheetname = '') {
        $this->sheetNames[] = $sheetname;
        $this->sheetnum++;
        $this->rowNum[$this->sheetnum] = 1;
        return $this->sheetnum;
    }

    protected function createStyleXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="General"/></numFmts><fonts count="4"><font><sz val="10"/><name val="思源黑体 CN Regular"/><family val="2"/></font><font><sz val="10"/><name val="Arial"/><family val="0"/></font><font><sz val="10"/><name val="Arial"/><family val="0"/></font><font><sz val="10"/><name val="Arial"/><family val="0"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border diagonalUp="false" diagonalDown="false"><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="20"><xf numFmtId="164" fontId="0" fillId="0" borderId="0" applyFont="true" applyBorder="true" applyAlignment="true" applyProtection="true"><alignment horizontal="general" vertical="bottom" textRotation="0" wrapText="false" indent="0" shrinkToFit="false"/><protection locked="true" hidden="false"/></xf><xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="43" fontId="1" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="41" fontId="1" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="44" fontId="1" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="42" fontId="1" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf><xf numFmtId="9" fontId="1" fillId="0" borderId="0" applyFont="true" applyBorder="false" applyAlignment="false" applyProtection="false"></xf></cellStyleXfs><cellXfs count="1"><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="false" applyBorder="false" applyAlignment="false" applyProtection="false"><alignment horizontal="general" vertical="bottom" textRotation="0" wrapText="false" indent="0" shrinkToFit="false"/><protection locked="true" hidden="false"/></xf></cellXfs><cellStyles count="6"><cellStyle name="Normal" xfId="0" builtinId="0" customBuiltin="false"/><cellStyle name="Comma" xfId="15" builtinId="3" customBuiltin="false"/><cellStyle name="Comma [0]" xfId="16" builtinId="6" customBuiltin="false"/><cellStyle name="Currency" xfId="17" builtinId="4" customBuiltin="false"/><cellStyle name="Currency [0]" xfId="18" builtinId="7" customBuiltin="false"/><cellStyle name="Percent" xfId="19" builtinId="5" customBuiltin="false"/></cellStyles></styleSheet>';
        $this->saveXml($this->stylesFile, $xml);
    }

    /**
     * add a row to sheet
     * 
     * @param array $row     the row data
     * @param type $index    the sheet index number
     * @return $this
     */
    public function addRow($row, $index) {
        $xml = '<row r="%d" customFormat="false" ht="12.8" hidden="false" customHeight="false" outlineLevel="0" collapsed="false">';

        $xml = sprintf($xml, $this->rowNum[$index]);
        $columPos = 0;

        foreach ($row as $v) {
            $alp = $this->covertAlphabetOrder($columPos);
            if (is_numeric($v)) {
                $t = '';
            } else {
                $t = 's="0" t="s"';
                $this->addShared($v);
            }

            $xml .= "<c r=\"{$alp}{$this->rowNum[$index]}\" $t><v>$v</v></c>";
            $columPos++;
        }
        if ($columPos > $this->columMaxNum) {
            $this->columMaxNum = $columPos;
        }
        $xml .= '</row>' . PHP_EOL;
        $this->rowNum[$index] ++;
        $this->saveXml("_sheet_tmpfile.$index.xml", $xml, FILE_APPEND);
        return $this;
    }

    protected function addShared(&$str) {
        $f = new \SplFileObject("{$this->workspacedir}{$this->sharedStringsFile}", 'a+');
        $xml = "<si><t xml:space=\"preserve\">{$str}</t></si>" . PHP_EOL;
        $this->shareAllCnt++;
        foreach ($f as $n => $line) {
            if ($line == $xml) {
                $str = $n;
                return;
            }
        }

        $f->fwrite($xml);
        $str = $this->shareStringCnt;
        $this->hasShared++;
        $this->shareStringCnt++;
    }

    protected function setShared() {
        if ($this->hasShared > 0) {
            rename("{$this->workspacedir}{$this->sharedStringsFile}", "{$this->workspacedir}{$this->sharedStringsFile}.data");
        }
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $this->shareAllCnt . '" uniqueCount="' . $this->shareStringCnt . '">';
        $this->saveXml($this->sharedStringsFile, $xml);
        $fp = new \SplFileObject("{$this->workspacedir}{$this->sharedStringsFile}", 'a+');
        if ($this->hasShared > 0) {
            $f = new \SplFileObject("{$this->workspacedir}{$this->sharedStringsFile}.data");
            foreach ($f as $row) {
                $fp->fwrite(trim($row));
            }
        }
        $fp->fwrite('</sst>');
        return $this;
    }

    /**
     * save data to xlsx file
     */
    public function save() {
        $this->zip = new \ZipArchive();
        $this->zip->open("{$this->workspacedir}.zip", \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($this->sheetNames as $i => $name) {
            $this->createSheet();
            $this->zipAddFile(sprintf($this->worksheetsFile, $i + 1));
        }
        $this->createWorkbookRels();
        $this->createWorkbookXml();
        $this->setShared();
        $this->createTypesXML();
        $this->zipAddFile($this->relsFile);
        $this->zipAddFile($this->appFile);
        $this->zipAddFile($this->coreFile);
        $this->zipAddFile($this->sharedStringsFile);
        $this->zipAddFile($this->workbookFile);
        $this->zipAddFile($this->workbookRelsFile);
        $this->zipAddFile($this->stylesFile);
        $this->zipAddFile($this->typeFile);
        //$this->zipAddFile($this->themeFile);
        //$this->zip->addFile($this->zipdir);
        $this->zip->close();
        rename("{$this->workspacedir}.zip", $this->xmlfile);
        $this->clean();
    }

    protected function zipAddFile($file) {
        $this->zip->addFile("{$this->workspacedir}/$file", ltrim($file, '/'));
    }

    public function clean() {
        if (is_dir($this->workspacedir)) {
            Kernel::rmdir($this->workspacedir, true);
        }
        $this->xlsxInit = false;
    }

    public function __destruct() {
        $this->clean();
    }

}
