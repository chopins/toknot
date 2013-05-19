<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\View;

use Toknot\View\View;
use Toknot\Exception\StandardException;
use \DOMComment;

class HTML extends View {

    private $htmlDOM = null;
    private $head = array();
    private $title = null;
    public $scanPath = '';
    public $defautlSuffix = 'html';

    public static function singleton() {
        return parent::__singleton();
    }

    protected function __construct() {
        if (!class_exists('DOMDocument')) {
            throw new StandardException('Requires DOM extension');
        }
        $this->htmlDOM = new DOMDocument();
        libxml_use_internal_errors(true);
    }

    public function loop($id, $data, $callable) {
        $loopNode = $this->htmlDOM->getElementById($id);
        foreach ($data as $key => $value) {
            $line = $loopNode->getLineNo();
            $html = '<li>
            <a href = "https://github.com/chopins/toknot/blob/master/js-document.md">About</a>
            </li>';
        }
    }

    public function loadHTMLFile($file) {
        $this->loadHTMLFile($file);
        libxml_clear_errors();
    }

    public function newPage($page) {
        $file = "{$this->scanPath}/{$page}.{$this->defautlSuffix}";
        $this->loadHTMLFile($file);
    }

    public function display() {
        
    }

    public function title($title) {
        $titleNode = $this->htmlDOM->getElementsByTagName('title')->item[0]->getLineNo();
    }

    public function newMeta($string) {
        
    }

}

?>
 