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
        
    }

    public function loop($id, $data, $callable) {
        $loopNode = $this->htmlDOM->getElementById($id);
    }

    public function loadHTMLFile($file) {
        $html = file_get_contents($file);
        $tag = strtok($html, '<');
        var_dump($tag);
        return;
        while ($tag) {
            $tag = strtok($html, '>');
            var_dump($tag);
        }
    }

    public function newPage($page) {
        $file = "{$this->scanPath}/{$page}.{$this->defautlSuffix}";
        $this->loadHTMLFile($file);
    }

    public function display() {
        
    }

    public function title($title) {
        $this->title = $title;
    }

    public function newMeta($string) {
        $this->head[] = "<meta $string />";
    }

}

?>
 