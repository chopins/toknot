<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\User;

use Toknot\Di\Object;

abstract class UserControl extends Object {

    /**
     * user's account of name
     *
     * @var string
     */
    protected $userName = 'nobody';

    /**
     * ID of username
     *
     * @var integer 
     */
    protected $uid = '';

    /**
     * ID of group
     *
     * @var mixed  May be array or string
     */
    protected $gid = '';

    /**
     * whether allow user login
     *
     * @var boolean
     */
    protected $allUserLogin = true;

    /**
     * whether enable admin group, if true and group id equal 1, the user will is admin
     *
     * @var boolean 
     */
    protected $enableAdmin = false;

    /**
     * whether allow change to root user
     *
     * @var boolean 
     */
    protected $allowSu = false;

    /**
     * name of algorithm for the hash function
     *
     * @var string
     */
    protected static $hashAlgo = 'sha512';

    /**
     * whether use hash extension of function
     *
     * @var boolean
     */
    protected static $useHashFunction = true;

    /**
     * set salt of the hash 
     *
     * @var string
     */
    protected static $hashSalt = '';

    /**
     * The password salt string
     */

    const PASSWORD_SALT = 'ToKnot-PHP-Framework-Password-Default-Salt';

    /**
     * Get user Id number
     * 
     * @return integer
     */
    final public function getUid() {
        return $this->uid;
    }

    /**
     * Get group id number
     * 
     * @return type
     */
    final public function getGid() {
        return $this->gid;
    }

    /**
     * Get current login user of name
     * 
     * @return string
     */
    final public function getLogin() {
        return $this->userName;
    }

    /**
     * Get current group of user or class
     * 
     * @return string
     */
    final public function getGroup() {
        return $this->groupName;
    }

    /**
     * find a best hash algos from current PHP hash algorithms list
     * 
     * @return boolean|string  If have sha and tiger algorithm, will return max bit algo otherise return false
     * @access public
     * @static
     */
    public static function bestHashAlgos() {
        if (CRYPT_BLOWFISH) {
            return 'BLOWFISH';
        }
        if (CRYPT_SHA256) {
            return 'SHA256';
        }
        if (CRYPT_SHA512) {
            return 'SHA512';
        }
        return 'SHA1';
    }

    /**
     * Passed a password text generate a hash sting for a string, the method use 
     * wrapper around crypt(), now only support Blowfish, sha1, sha512, sha256, 
     * you should {@see UserControl::bestHashAlgos()} get 
     * 
     * @param string $password  The string to be hashed
     * @param string $algo Value of hash name of the algorithms
     * @param string $salt An optional salt string to base the hashing on, the string from the 
     *                       alphabet "0-9A-Za-z"
     * @param integer $rounds range number, more info see {@see crypt()} of PHP, if calculate
     *                         the logarithm base-2 of the value outside the range 04-31 
     *                         will use to the nearest limit.
     * @return string the hashed string and salt, like {@see crypt()) of PHP
     */
    public static function getTextHash($password, $algo, $salt = '', $rounds = 5000) {
        if (!preg_match('/^[0-9A-Za-z]+$/', $salt)) {
            return false;
        }
        switch (strtoupper($algo)) {
            case 'SHA512':
                $salt = '$6$rounds=' . $rounds . '$' . $salt . '$';
                return crypt($password, $salt);
            case 'SHA256':
                $salt = '$5$rounds=' . $rounds . '$' . $salt . '$';
                return crypt($password, $salt);
            case 'BLOWFISH':
                $rounds = floor(log($rounds, 2));
                if ($rounds > 31)
                    $rounds = 31;
                if ($rounds < 04)
                    $rounds = 04;
                if ($rounds < 10)
                    $rounds = '0' . $rounds;
                $salt = '$2a$' . $rounds . '$' . $salt . '$';
                return crypt($password, $salt);
            default :
                for ($i = 0; $i < $rounds; $i++) {
                    $password = sha1($password . $salt);
                }
                return '$sha1$rounds=' . $rounds . '$' . $salt . '$' . $password;
        }
    }

    /**
     * return information about the given hash which created by {@see UserControl::getTextHash()}
     * 
     * @param string $hash a hash created by {@see UserControl::getTextHash()}
     * @return array|boolean The hash not created by {@see UserControl::getTextHash()} will return false
     *                        otherwise return a array with 4 elements order by:
     *                        hashStr     only hashed string
     *                        algorithm   use algorithm name
     *                        salt        use salt string
     *                        round       number of times the hashing loop be executed, 
     *                                    if BLOWFISH hashing the value is cost, which not 
     *                                    $rounds parameter of {@see UserControl::getTextHash()}
     */
    public static function getHashInfo($hash) {
        $hashInfo = explode('$', $hash);
        if (count($hashInfo) < 2) {
            return false;
        }
        if ($hashInfo[1] == '2a') {
            $rounds = $hashInfo[2];
            $algo = 'BLOWFISH';
        } else {
            switch ($hashInfo[1]) {
                case 'sha1':
                    $algo = 'SHA1';
                    break;
                case '6':
                    $algo = 'SHA512';
                    break;
                case '5':
                    $algo = 'SHA256';
                    break;
                default :
                    return false;
            }
            list(, $rounds) = explode('=', $hashInfo[2]);
        }
        $salt = $hashInfo[3];
        $hashStr = array_pop($hashInfo);
        return array($hashStr, $algo, $salt, $rounds);
    }

    /**
     * return hashed string, the method not supprot rounds paramter, so {@see self::getTextHash()}
     * will use 5000 for $rounds and use BLOWFISH hashing , cost is 12 be used
     * 
     * @param string $password  The string to be hashed
     * @param string $algo      use algorithm name {@see self::getTextHash()}
     * @param string $salt      {@see self::getTextHash()}
     * @return string hashed string
     */
    public static function getTextHashOutSalt($password, $algo, $salt) {
        $hash = self::getTextHash($password, $algo, $salt);
        $hashInfo = self::getHashInfo($hash);
        return $hashInfo[0];
    }

    /**
     * Get password be hashed string, the method use {@see CurrentUser::$hashSalt} static property
     * for the password of hash salt, if not set {@see CurrentUser::$hashSalt} will use
     * {@see CurrentUser::PASSWORD_SALT}
     * 
     * @param string $password
     * @return string
     * @access public
     * @static
     * @throws Toknot\Exception\StandardException if set use hash function and hash function not exists
     */
    public static function hashPassword($password) {
        $salt = empty(self::$hashSalt) ? self::PASSWORD_SALT : self::$hashSalt;
        return self::getTextHashOutSalt($password, self::$hashAlgo, $salt);
    }
    
    /**
     * Verifies that a password matches a hash string 
     * 
     * @param type $password
     * @param type $hash
     * @return boolean
     */
    public static function verifyPassword($password, $hash) {
        if(strcmp(self::hashPassword($password), $hash) == 0) {
            return true;
        }
        return false;
    }

    /**
     * Verifies that a password matches a hash which be created by {@see UserControl::getTextHash()}
     * 
     * @param string $password  The string to be verifyed
     * @param string $hash  a hash created by {@see UserControl::getTextHash()}
     * @return boolean  equal return true, otherwise return false
     */
    public static function verifyHash($password, $hash) {
        $hashInfo = self::getHashInfo($hash);
        if (strcmp(self::getTextHash($password, $hashInfo[1], $hashInfo[2], $hashInfo[3]), $hash) == 0) {
            return true;
        } else {
            return false;
        }
    }

}