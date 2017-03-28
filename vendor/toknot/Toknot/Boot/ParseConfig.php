<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 * @since 4.0
 * @filesource
 * @package Toknot.Boot
 */

namespace Toknot\Boot;

/**
 * ParseConfig
 */
interface ParseConfig {

    /**
     * parse your config file
     * 
     * @param string $file
     * @return array
     */
    public function parse($file);

    /**
     * check whether support the extension
     * 
     * @param string $ext
     */
    public function support($ext);
}
