<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace ToknotUnit;

class CallObject extends \Toknot\Boot\Object {
    private function testInvokePrivate() {
        
    }
    
    protected function testInvokeProtected() {
        return true;
    }
    
    
}

class ObjectTest extends TestCase {

    public function testStringObject() {
        $teststr = ' 22thisistest string  ';
        $str = new \Toknot\Boot\StringObject($teststr);
        $ms = $str->getSupportMethod();
        foreach ($ms as $k => $m) {
            if($m == 'str_shuffle') {
                continue;
            }
            $rf = new \ReflectionFunction($m);
            $isref = $rf->returnsReference();
            $pn = $rf->getNumberOfParameters();
            if ($pn <= 1 && !$isref) {
                $re1 = $str->$m();
                if($re1 instanceof \Toknot\Boot\StringObject) {
                    $re1 = (string)$re1;
                } elseif($re1 instanceof \Toknot\Boot\ArrayObject) {
                    $re1 = $re1->transformToArray();
                }
                $re2 = $m($teststr);
                
                $this->assertEquals($re1, $re2);
            }
        }
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testObjectInvokeException() {
        $obj3 = new CallObject;
        $obj3->invokeMethod('testInvokePrivate');
    }
    
    public function testObject() {
        $obj0 = CallObject::getInstance();
        $this->assertEquals($obj0, null);
        
        $obj1 = CallObject::singleton();
        $obj2 = CallObject::singleton();
        
        $this->assertEquals($obj1, $obj2);
        
        $obj3 = CallObject::getInstance();
        $this->assertEquals($obj1, $obj3);
        
        $obj3 = new CallObject;
        $obj4 = CallObject::getClassInstance();
        
        $this->assertEquals($obj3, $obj4);
        
        $this->assertTrue($obj3->invokeMethod('testInvokeProtected'));
        
    }
    /**
     * @expectedException \Toknot\Exception\FileIOException
     */
    public function testFileObjectException() {
        $file = new \Toknot\Boot\FileObject(__DIR__.'/un-exists-file');
    }
    
    public function testFileObject() {
        
        $f = \Toknot\Boot\FileObject::mkdir(__DIR__ .'/test-dir');
        $this->assertInstanceOf('\Toknot\Boot\FileObject', $f);
        $this->assertEquals(__DIR__ .'/test-dir',(string)$f);
        
        $this->assertNotTrue(\Toknot\Boot\FileObject::isDirCase(strtoupper(__DIR__)));
        
        $this->assertNotTrue(\Toknot\Boot\FileObject::fileExistCase(strtoupper(__FILE__)));
        
        $new_test_file_path = __DIR__ .'/test-dir/test2/testfile';
        $new_test_file_data = 'this is test data';
        $file = \Toknot\Boot\FileObject::saveContent($new_test_file_path, $new_test_file_data);
        
        $this->assertInstanceOf('\Toknot\Boot\FileObject', $file);
        $this->assertEquals($new_test_file_path,(string)$file);
        $data = file_get_contents($new_test_file_path);
        $this->assertEquals($new_test_file_data, $data);
        
        $f->rm(true);
    }
}
