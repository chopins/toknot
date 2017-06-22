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
 * Shared
 *
 */
class Shared {

    protected $shareStringCnt = 0;
    protected $shareAllCnt = 0;
    protected $hasShared = 0;
    protected $workspace = '';
    protected $shareFileObj = null;
    protected $shareStringsFile = '/xl/sharedStrings.xml';

    public function __construct(Xlsx $xlsx, $flag) {
        $this->worksapce = $xlsx->getWorkspace();
        $this->openTmpFile($flag);
    }

    public function openTmpFile($flag) {
        $flag == 'w' ? '.data' : '';
        $this->shareFileObj = new File("{$this->workspace}{$this->sharedStringsFile}{$flag}", 'a+');
    }

    public function addShared(&$str) {
        $this->shareFileObj->rewind();
        $xml = "<si><t xml:space=\"preserve\">{$str}</t></si>" . PHP_EOL;
        $this->shareAllCnt++;
        foreach ($this->shareFileObj as $n => $line) {
            if ($line == $xml) {
                $str = $n;
                return;
            }
        }

        $this->shareFileObj->fwrite($xml);
        $str = $this->shareStringCnt;
        $this->hasShared++;
        $this->shareStringCnt++;
    }

    public function saveShared() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $this->shareAllCnt . '" uniqueCount="' . $this->shareStringCnt . '">';
        $this->saveXml($this->sharedStringsFile, $xml);
        $fp = new File("{$this->workspace}{$this->sharedStringsFile}", 'a+');
        if ($this->hasShared > 0) {
            $this->shareFileObj->rewind();
            foreach ($this->shareFileObj as $row) {
                $fp->fwrite(trim($row));
            }
        }
        $fp->fwrite('</sst>');
        $this->shareFileObj->unlink();
        return $this->shareStringsFile;
    }

    public function getShared($k) {
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

}
