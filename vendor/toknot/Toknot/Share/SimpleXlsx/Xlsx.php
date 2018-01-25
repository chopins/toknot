<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\SimpleXlsx;

use Toknot\Exception\BaseException;
use Toknot\Boot\Tookit;
use Toknot\Boot\Kernel;

/**
 * Xlsx
 *
 */
class Xlsx {

    protected $workspace = '';
    protected $xlsx = '';
    protected $relsFile = '/_rels/.rels';
    protected $appFile = '/docProps/app.xml';
    protected $coreFile = '/docProps/core.xml';
    protected $workbookRelsFile = '/xl/_rels/workbook.xml.rels';
    protected $workbookFile = '/xl/workbook.xml';
    protected $stylesFile = '/xl/styles.xml';
    protected $typeFile = '[Content_Types].xml';
    protected $themeFile = '/xl/theme/theme1.xml';
    protected $tmpPath = '';
    protected $alphabet = '';
    protected $sheetsArr = [];
    protected $sharedObj = null;
    protected $docRelURI = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/';
    protected $docPackageURI = 'http://schemas.openxmlformats.org/package/2006/';
    private $isLoad = false;

    public function __construct($file = '') {
        $this->xlsx = $file;
        $this->alphabetOrder();
        $this->getTmpDir();
        $this->sheetsArr = [];
    }

    protected function getTmpDir() {
        $this->tmpPath = sys_get_temp_dir();
    }

    protected function alphabetOrder() {
        $this->alphabet = range('A', 'Z');
    }

    public function setWorkspace($dir) {
        $this->workspace = $dir;
    }

    public function getWorkspace() {
        return $this->workspace;
    }

    public function xmlHead($type) {
        return '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="' . $this->docPackageURI . $type . '">';
    }

    public function covertAlphabetOrder($number) {
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

    public function coverOrder2Alphabet($str) {
        $index = $len = strlen($str);
        $re = 0;
        for ($i = 0; $i < $len; $i++) {
            $index--;
            $n = array_search($str{$i}, $this->alphabet) + 1;
            $re = $re + pow($n * 26, $index);
        }
        return $re;
    }

    public function initXlsxFile() {
        Kernel::single()->attachShutdownFunction(function() {
            if (is_dir($this->workspace)) {
                Tookit::rmdir($this->workspace, true);
            }
        });

        $this->createRels();
        $this->createAppXml();
        $this->createCoreXml();
        $this->createStyleXml();
        $this->sharedObj = new Shared($this, 'w');
    }

    public function newSheet($sheetName = '') {
        $sheet = new Sheet($this, $sheetName);
        $this->sheetsArr[] = $sheet;
        $index = count($this->sheetsArr);
        $sheet->setSheetIndex($index);
        $sheet->setShared($this->sharedObj);
        return $sheet;
    }

    public function load() {
        if ($this->isLoad) {
            throw new BaseException("file $this->xlsx has been loaded");
        }

        $this->isLoad = true;
        $xlsxName = basename($this->xlsx, '.xlsx');
        $uniqid = uniqid();
        $workspaceDir = "$this->tmpPath/$uniqid/$xlsxName";
        $this->sharedObj = new Shared($this, 'r');
        $this->setWorkspace($workspaceDir);

        mkdir($this->workspace);

        $zipfile = "{$this->workspace}/$xlsxName.zip";
        copy($this->xlsx, $zipfile);
        $this->zip = new \ZipArchive();
        $this->zip->open($zipfile);
        $this->zip->extractTo($this->workspace);
        $this->zip->close();
        $doc = new \DOMDocument();
        $workBook = $this->workspace . $this->workbookFile;
        $doc->load($workBook);
        $sheets = $doc->getElementsByTagName('sheets')->item(0)->getElementsByTagName('sheet');
        foreach ($sheets as $sheet) {
            if ($sheet->tagName == 'sheet') {
                $fid = $sheet->getAttribute('sheetId');
                $sheetName = $sheet->getAttribute('name');
                $sheet = new Sheet($this, $sheetName);
                $sheet->setShared($this->sharedObj);
                $sheet->setSheetIndex($fid - 1);
                $this->sheetsArr[$fid] = $sheet;
            }
        }
    }

    public function getSheet($index) {
        if ($index instanceof Sheet) {
            return $index;
        }
        if (is_numeric($index) && isset($this->sheetsArr[$index])) {
            $sheet = $this->sheetsArr[$index];
            $sheet->loadSheet();
            return $sheet;
        }
        foreach ($this->sheetsArr as $sheet) {
            if ($index == $sheet->getSheetName()) {
                $sheet->loadSheet();
                return $sheet;
            }
        }
        throw new BaseException("sheet $index not exists");
    }

    public function getSheetList() {
        return $this->sheetsArr;
    }

    public function create() {
        $xlsxName = basename($this->xlsx, '.xlsx');

        $workspaceDir = $this->createDirStruct($xlsxName);
        $this->setWorkspace($workspaceDir);

        $this->initXlsxFile();
    }

    protected function createDirStruct($xlsxName) {
        $uniqid = uniqid();
        $workspaceDir = "{$this->tmpPath}/$uniqid/{$xlsxName}";
        mkdir($workspaceDir);
        mkdir("{$workspaceDir}/_rels");
        mkdir("{$workspaceDir}/docProps");
        mkdir("{$workspaceDir}/xl");
        mkdir("{$workspaceDir}/xl/_rels");
        mkdir("{$workspaceDir}/xl/worksheets");
        //mkdir("{$this->zipdir}/xl/theme");
        return $workspaceDir;
    }

    public function saveXml($file, $xml, $flag = 0) {
        file_put_contents("{$this->workspace}/$file", $xml, $flag);
    }

    public function copyXml($file, $xml) {
        copy(__DIR__ . "/xml/$file", "$this->workspace/$xml");
    }

    public function readTemplateXml($file) {
        return file_get_contents(__DIR__ . "/xml/$file");
    }

    protected function createRels() {
        $xml = $this->xmlHead('relationships');

        $rels = ['officeDocument' => 'xl/workbook.xml',
            'metadata/core-properties' => 'docProps/core.xm',
            'extended-properties' => 'docProps/app.xml'];
        $i = 1;
        foreach ($rels as $k => $v) {
            $xml .= "<Relationship Id=\"rId1$i\" Type=\"{$this->docRelURI}$k\" Target=\"$v\"/>";
            $i++;
        }
        $xml .= '</Relationships>';
        $this->saveXml($this->relsFile, $xml);
    }

    protected function createAppXml() {
        $this->copyXml('app.xml', $this->appFile);
    }

    protected function createCoreXml() {
        $xml = $this->readTemplateXml('core.xml');
        $this->saveXml($this->coreFile, sprintf($xml, $this->createTime, date('Y-m-d\TH:i:s\Z')));
    }

    protected function createStyleXml() {
        $this->copyXml('style.xml', $this->stylesFile);
    }

    protected function createWorkbookRels() {
        $xml = $this->xmlHead('relationships');
        $xml .= '<Relationship Id="rId1" Type="' . $this->docRelURI . 'styles" Target="styles.xml"/>';
        for ($i = 1; $i <= $this->sheetnum; $i++) {
            $id = $i + 1;
            $xml .= '<Relationship Id="rId'
                    . $id
                    . '" Type="'
                    . $this->docRelURI
                    . 'worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $xml .= '<Relationship Id="rId'
                . ($id + 1)
                . '" Type="'
                . $this->docRelURI . 'sharedStrings" Target="sharedStrings.xml"/></Relationships>';
        $this->saveXml($this->workbookRelsFile, $xml);
    }

    protected function createTypesXML() {
        $xml = $this->xmlHead('content-types');
        $format = 'application/vnd.openxmlformats-';
        $item = '<Override PartName="%s" ContentType="%s"/>';

        $rels = "{$format}package.relationships+xml";
        $xml .= sprintf($item, $this->relsFile, $rels);

        $app = "{$format}officedocument.extended-properties+xml";
        $xml .= sprintf($item, $this->appFile, $app);

        $core = "{$format}package.core-properties+xml";
        $xml .= sprintf($item, $this->coreFile, $core);

        $workbookRels = "{$format}package.relationships+xml";
        $xml .= sprintf($item, $this->workbookRelsFile, $workbookRels);

        $workbook = "{$format}officedocument.spreadsheetml.sheet.main+xml";
        $xml .= sprintf($item, $this->workbookFile, $workbook);


        $styles = "{$format}officedocument.spreadsheetml.styles+xml";
        $xml .= sprintf($item, $this->stylesFile, $styles);

        $worksheets = "{$format}officedocument.spreadsheetml.worksheet+xml";
        for ($i = 1; $i <= $this->sheetnum; $i++) {
            $xml .= sprintf($item, sprintf($this->worksheetsFile, $i), $worksheets);
        }

        $sharedStrings = "{$format}officedocument.spreadsheetml.sharedStrings+xml";
        $xml .= sprintf($item, $this->sharedStringsFile, $sharedStrings);

        //$theme = "{$format}officedocument.theme+xml";
        //$xml .= sprintf($item, $this->themeFile, $theme);
        $xml .= PHP_EOL . '</Types>';
        $this->saveXml($this->typeFile, $xml);
    }

    protected function createWorkbookXml() {
        $xml = $this->readTemplateXml('workbook.head.xml');
        $id = 2;
        foreach ($this->sheetsArr as $sheet) {
            $name = $sheet->getSheetName();
            $sn = empty($name) ? "Sheet{$id}" : $name;
            $xml .= '<sheet name="' . $sn . '" sheetId="' . $id . '" state="visible" r:id="rId' . $id . '"/>';
            $id++;
        }
        $xml .= $this->readTemplateXml('workbook.end.xml');
        $this->saveXml($this->workbookFile, $xml);
    }

    public function save() {
        $this->zip = new \ZipArchive();
        $zipfile = "{$this->workspace}.zip";
        $this->zip->open($zipfile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($this->sheetsArr as $sheet) {
            $sheet = $sheet->saveSheet();
            $this->zipAddFile($sheet);
        }

        $this->createWorkbookRels();
        $this->createWorkbookXml();

        $shared = $this->sharedObj->saveShared();
        $this->zipAddFile($shared);

        $this->createTypesXML();
        $this->zipAddFile($this->relsFile);
        $this->zipAddFile($this->appFile);
        $this->zipAddFile($this->coreFile);
        $this->zipAddFile($this->workbookFile);
        $this->zipAddFile($this->workbookRelsFile);
        $this->zipAddFile($this->stylesFile);
        $this->zipAddFile($this->typeFile);
        //$this->zipAddFile($this->themeFile);
        //$this->zip->addFile($this->zipdir);
        $this->zip->close();
        rename($zipfile, $this->xlsx);
        $this->close();
    }

    public function clean() {
        if (is_dir($this->workspace)) {
            Tookit::rmdir($this->workspace, true);
        }
    }

    public function close() {
        $this->clean();
        $this->sheetsArr = [];
        $this->sharedObj = null;
        $this->zip = null;
        $this->isLoad = false;
        $this->workspacedir = '';
    }

}
