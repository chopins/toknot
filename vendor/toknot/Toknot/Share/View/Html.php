<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

use Toknot\Exception\BaseException;

/**
 * HTML
 *
 * @author chopin
 */
class Html extends TagBulid {

    private $htmlVer = 5;
    private $htmlMode = 'strict';

    const STR4_01 = '"-//W3C//DTD HTML 4.01//EN" 
            "http://www.w3.org/TR/html4/strict.dtd"';
    const TRA4_01 = ' PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd"';
    const FRA4_01 = '"-//W3C//DTD HTML 4.01 Frameset//EN"
   "http://www.w3.org/TR/html4/frameset.dtd"';
    const STR1_0 = '"-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"';
    const TRA1_0 = '"-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"';
    const FRA1_0 = '"-//W3C//DTD XHTML 1.0 Frameset//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd"';
    const BAS1_0 = '"-//W3C//DTD XHTML Basic 1.0//EN"    
    "http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd"';
    const DTD1_1 = '"-//W3C//DTD XHTML 1.1//EN" 
   "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"';
    const BAS1_1 = '"-//W3C//DTD XHTML Basic 1.1//EN"
    "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd"';
    const COM1_1 = '"-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN"
    "http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd"';
    const PRO1_1 = '"-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN"
    "http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd"';
    const HTML2 = '"-//IETF//DTD HTML 2.0//EN"';
    const HTML3_2 = '"-//W3C//DTD HTML 3.2 Final//EN"';

    public function __construct($param = '', $type = []) {
        $this->htmlVer = $type['version'];
        $this->htmlMode = $type['mode'];
        $this->setDoctype($type['version'], $type['mode']);
        $this->tagName = 'html';
        $this->initTag($param);
    }

    private function convertVersion($ver) {
        if (!in_array($ver, $this->supportVersion())) {
            $hit = var_export($this->supportVersion(), true);
            throw new BaseException("declared dcotype of version $ver not support; only supported in $hit");
        }
        return str_replace('.', '_', $ver);
    }

    private function convertMode($mode) {
        if (!in_array($mode, $this->supportMode())) {
            $hit = var_export($this->supportMode(), true);
            throw new BaseException("declared doctype of mode '$mode' not support; only supported in $hit");
        }
        return strtoupper(substr($mode, 0, 3));
    }

    public function setDoctype($ver, $mode) {

        $this->html .= "<!doctype ";
        $this->html .= $ver == '4.01' ? 'HTML' : 'html';
        if (version_compare($ver, 5) == -1) {
            $this->html .= ' PUBLIC ';

            switch ($ver) {
                case '2.0':
                    $this->html .= self::HTML2;
                    break;
                case '3.2':
                    $this->html .= self::HTML3_2;
                    break;
                default :
                    $ver = $this->convertVersion($ver);
                    $mode = $this->convertMode($mode);
                    $this->html .= constant("self::$mode$ver");
                    break;
            }
        }
        $this->html .= '>';
    }

    public function supportVersion() {
        return ['5 and beyond', '4.01', '3.2', '2.0', '1.1', '1.0'];
    }

    public function supportMode() {
        return ['strict', 'basic', 'transitional', 'frameset', 'dtd', 'compound', 'profile'];
    }

    public function getVer() {
        return $this->htmlVer;
    }

    public function getMode() {
        return $this->htmlMode;
    }

}
