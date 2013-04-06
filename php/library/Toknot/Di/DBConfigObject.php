<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use Toknot\Di\Object;

class DBConfigObject extends Object {

    /**
     * dbType 
     * set database type
     * 
     * @var string
     * @access public
     */
    public $dbType = null;

    /**
     * dbHost 
     * set database connect host or open path
     * 
     * @var string
     * @access public
     */
    public $dbHost = null;

    /**
     * dbUser 
     * the database username
     * 
     * @var string
     * @access public
     */
    public $dbUser = null;

    /**
     * dbPass 
     * the database password of user
     * 
     * @var string
     * @access public
     */
    public $dbPass = null;


    /**
     * dbPort 
     * if connect by network, set the connect port
     * 
     * @var string
     * @access public
     */
    public $dbPort = null;
    
    /**
     * pConnect
     * persistent connect config
     *
     * @var bool 
     * @access public
     */
    public $pConnect = false;
    
    public $protocol = 'tcp';
}
