<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\SimpleXlsx;

use Toknot\Share\SimpleXlsx\Xlsx;
use Toknot\Exception\BaseException;

/**
 * SimpleXlsx
 *
 */
class SimpleXlsx {

    /**
     * 
     * <code>
     *  $s = new SimpleXlsx();
     *  $xlsx = $s->createXlsx('/your_file_save_path/test.xlsx');
     *  $sheet = $xlsx->newSheet('test');
     *  for ($i = 0; $i < 1000; $i++) {
     *       $row = range(1, 100);
     *       $sheet->addRow($row);
     *  }
     *  $xlsx->save();
     * 
     *  $xlsx = $s->loadXlsx('your_file_save_path/test.xlsx');
     *  $sheetList = $xlsx->getSheetList();
     *  $sheet = $xlsx->getSheet(1);
     *  while($r = $sheet->readRow()) {
     *      
     *  }
     * </code>
     * 
     * @throws BaseException
     */
    public function __construct() {
        if (!class_exists('ZipArchive', false)) {
            throw new BaseException('xlsx need php zip extension');
        }
        if (!class_exists('DOMDocument', false)) {
            throw new BaseException('xlsx need php dom extension');
        }
    }

    /**
     * 
     * @param string $xlsx
     * @return Xlsx
     * @throws BaseException
     */
    public function loadXlsx($xlsx) {
        $xlsx = realpath($xlsx);
        if (!file_exists($xlsx)) {
            throw new BaseException("$xlsx not exists");
        }

        $xlsObj = new Xlsx($xlsx);
        $xlsObj->load();
        return $xlsObj;
    }

    /**
     * 
     * @param string $xlsx
     * @return Xlsx
     * @throws BaseException
     */
    public function createXlsx($xlsx) {
        $xlsx = realpath($xlsx);
        if (file_exists($xlsx)) {
            throw new BaseException("$xlsx is exists");
        }
        $xlsxObj = new Xlsx($xlsx);
        $xlsxObj->create();
        return $xlsxObj;
    }

}
