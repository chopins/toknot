<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

/**
 * Data cache handle interface for server cache
 */
interface DataCacheServerInterface {
    
    /**
     * Set key value and set the key expired time
     * 
     * @param string $key
     * @param mixed $value
     * @param int $expireDate
     */
    public function set($key, $value, $expireDate);
    
    /**
     * Get key of value
     * 
     * @param string $key
     */
    public function get($key);
    
    /**
     * 
     * @param string $key
     * @return boolean
     */
    public function exist($key);
    
    /**
     * Delete a key
     * 
     * @param string $key
     * @return boolean 
     */
    public function del($key);
    
    /**
     * Rename a key to new keyname
     * 
     * @param string $oldKey
     * @param string $newKey
     */
    public function rename($oldKey, $newKey);
}
