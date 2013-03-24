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
     * dbtype 
     * set database type
     * 
     * @var mixed
     * @access public
     */
    public $dbtype = null;

    /**
     * dbhost 
     * set database connect host or open path
     * 
     * @var mixed
     * @access public
     */
    public $dbhost = null;

    /**
     * dbuser 
     * the database username
     * 
     * @var mixed
     * @access public
     */
    public $dbuser = null;

    /**
     * dbpass 
     * the database password of user
     * 
     * @var mixed
     * @access public
     */
    public $dbpass = null;


    /**
     * dbport 
     * if connect by network, set the connect port
     * 
     * @var mixed
     * @access public
     */
    public $dbport = null;
    public $pconnect = false;
}
