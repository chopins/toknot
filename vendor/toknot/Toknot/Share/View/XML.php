<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

/**
 * XML
 *
 */
class XML {

    public $xml;
    private $encode = '';
    private $dtd = '';

    public function __construct($data, $encode = '') {
        $this->encode = $encode;
        $this->xml .= $this->build($data);
    }

    public function addDTD($dtd, $mode = 'null') {
        $this->dtd = "<!DOCTYPE root PUBLIC '$dtd' '$mode'>";
    }

    public function getXml() {
        $xml = '';
        if ($this->encode) {
            $xml = "<?xml version=\"1.0\" encoding=\"{$this->encode}\"?>";
        }
        if ($this->dtd) {
            $xml .= $this->dtd;
            $xml .= '<root>';
        }
        $xml .= $this->xml;
        $xml .= '</root>';
        return $xml;
    }

    public function build($data) {
        $xml = '';
        foreach ($data as $k => $v) {
            $xml .= "<$k>";
            $xml .= is_array($v) ? $this->build($v) : $v;
            $xml .= "</$k>";
        }
        return $xml;
    }

}
