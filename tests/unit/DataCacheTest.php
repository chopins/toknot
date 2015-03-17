<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace ToknotUnit;

class DataCacheTest extends TestCase {

    public function testReadData() {
        \Toknot\Boot\DataCacheControl::$appRoot = __DIR__;
        $cache = new \Toknot\Boot\DataCacheControl('cache_test_data_file');
        $saveData = array('1', 'sfsafa', 6 => 'tt');
        $saveStatus = $cache->save($saveData);
        $this->assertTrue($saveStatus);
        $getData = $cache->get();

        $this->assertEquals($saveData, $getData);
        $this->assertTrue($cache->exists());
        $this->assertTrue($cache->del());
    }

    public function testReadKeyData() {
        \Toknot\Boot\DataCacheControl::$appRoot = __DIR__;
        $cache = new \Toknot\Boot\DataCacheControl('cache_test_data_key_file');
        $saveData = array('1', 'sfsafa', 6 => 'tt');
        $key = 'test-key';
        $this->assertTrue($cache->save($saveData, $key));
        $testData = $cache->get($key);

        $this->assertEquals($saveData, $testData);
        $this->assertTrue($cache->exists($key));
        $this->assertTrue($cache->del($key));
    }

    public function testUpdateData() {
        \Toknot\Boot\DataCacheControl::$appRoot = __DIR__;
        $cache = new \Toknot\Boot\DataCacheControl('cache_test_data_update_file');
        $saveData = array('1', 'sfsafa', 6 => 'tt');
        $key = 'test-key';
        $this->assertTrue($cache->save($saveData, $key));
        $testData = $cache->get($key);

        $this->assertEquals($saveData, $testData);

        $testData2 = 'this safeard saf ';
        $cache->save($testData2, $key);
        $checkData2 = $cache->get($key);
        $this->assertEquals($testData2, $checkData2);

        $this->assertTrue($cache->exists($key));
        $this->assertTrue($cache->del($key));
    }

    public function testExpireData() {
        \Toknot\Boot\DataCacheControl::$appRoot = __DIR__;
        $cache = new \Toknot\Boot\DataCacheControl('cache_test_data_expire_file');
        $saveData = array('1', 'sfsafa', 6 => 'tt');
        $key = 'test-key';
        $this->assertTrue($cache->save($saveData, $key));
        $testData = $cache->get($key);
        $cache->useExpire(1);
        $this->assertEquals($saveData, $testData);
        sleep(2);
        $expireData = $cache->get($key);
        $this->assertFalse($expireData);
        $this->assertFalse($cache->exists($key));
        $this->assertTrue($cache->del($key));
    }

}
