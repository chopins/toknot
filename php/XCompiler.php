<?php
/**
 * toknot
 * XCompiler class
 *
 * php version 5.3
 * 
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 the authors
 * @license    http://opensource.org/licenses/bsd-license.php new bsd license
 * @link       http://blog.toknot.com
 * @since      file available since release $id$
 */


class XCompiler {
    public static function init() {
        if(!extension_loaded('bcompiler')) {
            throw new XException('bcompiler extension not load');
        }
    }
    public static function compile_file($file) {
        $dir = dirname($file);
        $byecode_file = basename($file).'o';
        $byecode_file = $dir.'/'.$byecode_file;
        if(file_exists($byecode_file) && filemtime($byecode_file) > filemtime($file)) {
            return;
        }
        $fh = fopen($byecode_file, 'w');
        bcompiler_write_header($fh);
        bcompiler_write_file($fh, $file);
        bcompiler_write_footer($fh);
        fclose($fh);
    }
    public static function compile_framework() {
        $dc = dir(__X_FRAMEWORK_ROOT__);
        while(false !== ($file = $dc->read())) {
            if('.'== $file || '..' == $file) continue;
            if(file_suffix($file) == 'php') {
                $this->compile_file(__X_FRAMEWORK_ROOT__."/{$file}");
            }
        }
    }
}
