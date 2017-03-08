<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

use Toknot\Boot\Tookit;

/**
 * SimpleXlsx
 *
 * @author chopin
 */
class SimpleXlsx {

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
    protected $createTime = '';
    protected $zipdir = '';
    protected $alphabet = [];
    protected $rowNum = [];
    protected $columMaxNum = 1;
    protected $sheetnum = 0;
    protected $sheetNames = [];
    protected $xmlfile = '';
    protected $shareIndex = 0;
    protected $zip = null;
    protected $hasShared = 0;

    /**
     * Create a xlsx file
     * 
     * <code>
     *  xlsx = new SimpleXlsx('/your_file_save_path/test.xlsx');
     *  $index = $xlsx->newSheet('test');
     *  for ($i = 0; $i < 1000; $i++) {
     *       $row = range(1, 100);
     *       $xlsx->addRow($row, $index);
     *  }
     *  $xlsx->save();
     * </code>
     * 
     * @param string $xmlfile
     */
    public function __construct($xmlfile) {
        $this->getTmpDir();
        $xlsxName = basename($xmlfile, '.xlsx');
        $this->xmlfile = $xmlfile;
        $this->createTime = $this->time();
        $this->alphabetOrder();
        $this->createDirStruct($xlsxName);
        $zipdir = $this->zipdir;
        Tookit::attachShutdownFunction(function() use($zipdir) {
            if (is_dir($zipdir)) {
                Tookit::rmdir($zipdir, true);
            }
        });
        $this->createRels();
        $this->createAppXml();
        $this->createCoreXml();
        $this->createStyleXml();
    }

    public function getTmpDir() {
        $this->rootPath = sys_get_temp_dir();
    }

    public function alphabetOrder() {
        $this->alphabet = range('A', 'Z');
    }

    public function covertAlphabetOrder($number) {
        $number = base_convert($number, 10, 26);
        $ret = '';
        foreach (str_split($number) as $i) {
            $index = base_convert($i, 26, 10);
            $ret = $ret . $this->alphabet[$index];
        }
        return $ret;
    }

    public function saveXml($file, $xml, $flag = 0) {
        file_put_contents("{$this->zipdir}/$file", $xml, $flag);
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
        $this->saveXml($this->typeFile, $xml);
    }

    public function createWorkbookRels() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        for ($i = 1; $i <= $this->sheetnum; $i++) {
            $id = $i + 1;
            $xml .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $i++;
        $id = $i + 1;
        $xml .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
        $this->saveXml($this->workbookRelsFile, $xml);
    }

    protected function createRels() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
        $this->saveXml($this->relsFile, $xml);
    }

    protected function createDirStruct($xlsxName) {
        $this->zipdir = "{$this->rootPath}/{$xlsxName}";
        mkdir($this->zipdir);
        mkdir("{$this->zipdir}/_rels");
        mkdir("{$this->zipdir}/docProps");
        mkdir("{$this->zipdir}/xl");
        mkdir("{$this->zipdir}/xl/_rels");
        mkdir("{$this->zipdir}/xl/worksheets");
    }

    protected function createAppXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Template></Template><TotalTime>0</TotalTime><Application>PHP Toknot SimpleXlsx</Application></Properties>';
        $this->saveXml($this->appFile, $xml);
    }

    protected function time() {
        return date('Y-m-d\TH:i:s\Z');
    }

    protected function createCoreXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dcterms:created xsi:type="dcterms:W3CDTF">%s</dcterms:created><dc:creator></dc:creator><dc:description></dc:description><dc:language>zh-CN</dc:language><cp:lastModifiedBy></cp:lastModifiedBy><dcterms:modified xsi:type="dcterms:W3CDTF">%s</dcterms:modified><cp:revision>1</cp:revision><dc:subject></dc:subject><dc:title></dc:title></cp:coreProperties>';
        $this->saveXml($this->coreFile, sprintf($xml, $this->createTime, $this->time()));
    }

    protected function createWorkbookXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><fileVersion appName="Calc"/><workbookPr backupFile="false" showObjects="all" date1904="false"/><workbookProtection/><bookViews><workbookView showHorizontalScroll="true" showVerticalScroll="true" showSheetTabs="true" xWindow="0" yWindow="0" windowWidth="16384" windowHeight="8192" tabRatio="500" firstSheet="0" activeTab="0"/></bookViews><sheets>';
        foreach ($this->sheetNames as $i => $name) {
            $i++;
            $id = $i + 1;
            $sn = empty($name) ? "Sheet$i" : $name;
            $xml .= '<sheet name="' . $sn . '" sheetId="' . $i . '" state="visible" r:id="rId' . $id . '"/>';
        }
        $xml .= '</sheets><calcPr iterateCount="100" refMode="A1" iterate="false" iterateDelta="0.001"/><extLst/></workbook>';
        $this->saveXml($this->workbookFile, $xml);
    }

    protected function createSheet() {
        $endPos = $this->covertAlphabetOrder($this->columMaxNum) . $this->rowNum[$this->sheetnum];
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetPr filterMode="false"><pageSetUpPr fitToPage="false"/></sheetPr><dimension ref="A1:' . $endPos . '"/><sheetViews><sheetView showFormulas="false" showGridLines="true" showRowColHeaders="true" showZeros="true" rightToLeft="false" tabSelected="true" showOutlineSymbols="true" defaultGridColor="true" view="normal" topLeftCell="A1" colorId="64" zoomScale="100" zoomScaleNormal="100" zoomScalePageLayoutView="100" workbookViewId="0"><selection pane="topLeft" activeCell="' . $endPos . '" activeCellId="0" sqref="' . $endPos . '"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="12.8" outlineLevelRow="0" outlineLevelCol="0"></sheetFormatPr><cols><col collapsed="false" customWidth="false" hidden="false" outlineLevel="0" max="1025" min="1" style="0" width="11.52"/></cols><sheetData>';

        $sheetsFile = sprintf($this->worksheetsFile, $this->sheetnum);

        $this->saveXml($sheetsFile, $xml);
        $dataFile = "{$this->zipdir}/_sheet_tmpfile.$this->sheetnum.xml";
        $data = new \SplFileObject($dataFile, 'r');
        $sfp = new \SplFileObject("{$this->zipdir}/{$sheetsFile}", 'a+');
        foreach ($data as $line) {
            $sfp->fwrite(trim($line));
        }
        unlink($dataFile);
        $end = '</sheetData><printOptions headings="false" gridLines="false" gridLinesSet="true" horizontalCentered="false" verticalCentered="false"/><pageMargins left="0.7875" right="0.7875" top="1.05277777777778" bottom="1.05277777777778" header="0.7875" footer="0.7875"/><pageSetup paperSize="9" scale="100" firstPageNumber="1" fitToWidth="1" fitToHeight="1" pageOrder="downThenOver" orientation="portrait" blackAndWhite="false" draft="false" cellComments="none" useFirstPageNumber="true" horizontalDpi="300" verticalDpi="300" copies="1"/><headerFooter differentFirst="false" differentOddEven="false"></headerFooter></worksheet>';
        $this->saveXml($sheetsFile, $end, FILE_APPEND);
        return $this;
    }

    public function newSheet($sheetname = '') {
        $this->sheetNames[] = $sheetname;
        $this->sheetnum++;
        $this->rowNum[$this->sheetnum] = 1;
        return $this->sheetnum;
    }

    public function createStyleXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="General"/></numFmts><fonts count="1"><font><sz val="10"/><name val="Arial"/><family val="0"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border diagonalUp="false" diagonalDown="false"><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs /><cellXfs/><cellStyles/></styleSheet>';
        $this->saveXml($this->stylesFile, $xml);
    }

    public function addRow($row, $index) {
        $xml = '<row r="%d" customFormat="false" ht="12.8" hidden="false" customHeight="false" outlineLevel="0" collapsed="false">';

        $xml = sprintf($xml, $this->rowNum[$index]);
        $columPos = 0;

        foreach ($row as $v) {
            $alp = $this->covertAlphabetOrder($columPos);
            if (is_numeric($v)) {
                $t = 'n';
                $s = 0;
            } else {
                $t = 's';
                $s = 1;
                $this->addShared($v);
                $v = $this->shareIndex;
                $this->shareIndex++;
            }

            $xml .= "<c r=\"{$alp}{$this->rowNum[$index]}\" s=\"$s\" t=\"$t\"><v>$v</v></c>";
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

    protected function addShared($str) {
        $xml = "<si><t xml:space=\"preserve\">{$str}</t></si>" . PHP_EOL;
        $this->saveXml($this->sharedStringsFile, $xml, FILE_APPEND);
        $this->hasShared++;
    }

    protected function setShared() {
        if ($this->hasShared > 0) {
            rename("{$this->zipdir}{$this->sharedStringsFile}", "{$this->zipdir}{$this->sharedStringsFile}.data");
        }
        $xml = '<?xml version = "1.0" encoding = "UTF-8" standalone = "yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $this->shareIndex . '" uniqueCount="' . $this->shareIndex . '">';
        $this->saveXml($this->sharedStringsFile, $xml);
        $fp = new \SplFileObject("{$this->zipdir}{$this->sharedStringsFile}", 'a+');
        if ($this->hasShared > 0) {
            $f = new \SplFileObject("{$this->zipdir}{$this->sharedStringsFile}.data");
            foreach ($f as $row) {
                $fp->fwrite(trim($row));
            }
        }
        $fp->fwrite('</sst>');
        return $this;
    }

    public function save() {
        $this->zip = new \ZipArchive();
        $this->zip->open("{$this->zipdir}.zip", \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

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
        //$this->zip->addFile($this->zipdir);
        $this->zip->close();
        rename("{$this->zipdir}.zip", $this->xmlfile);
        $this->clean();
    }

    public function zipAddFile($file) {
        $this->zip->addFile("{$this->zipdir}/$file", ltrim($file, '/'));
    }

    public function clean() {
        if (is_dir($this->zipdir)) {
            Tookit::rmdir($this->zipdir, true);
        }
    }

    public function __destruct() {
        $this->clean();
    }

}
