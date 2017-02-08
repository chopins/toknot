<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\DB;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Toknot\Boot\Tookit;
use Toknot\Exception\BaseException;

/**
 * Schema
 *
 * @author chopin
 */
class DBSchema extends Schema {

    public function toDropIfExistsSql(AbstractPlatform $platform) {
        $hit = '____';
        $dropSQLs = ['MySql' => "DROP TABLE IF EXISTS $hit",
            'SQLServer' => "IF OBJECT_ID('$hit','U') IS NOT NULL DROP TABLE $hit",
            'Oracle' => "BEGIN EXECUTE IMMEDIATE 'DROP TABLE $hit';
                        EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE;
                        END IF;END;",
            'PostgreSql' => "DROP TABLE IF EXISTS $hit",
            'Drizzle' => "DROP TABLE IF EXISTS $hit",
            'SQLAzure' => "DROP TABLE IF EXISTS $hit",
            'SQLAnywhere' => "DROP TABLE IF EXISTS $hit"];
        
        $platformClass = get_class($platform);
        $platformName = Tookit::arrayPos(array_keys($dropSQLs), $platformClass);

        if (!$platformName) {
            throw new BaseException("$platformClass not support 'IF EXISTS' check SQL");
        }

        $dropSQL = $dropSQLs[$platformName];
        $query = [];
        foreach ($this->_tables as $table) {
            $tableName = $table->getQuotedName($platform);
            $query[] = str_replace($hit, $tableName, $dropSQL);
        }
        return $query;
    }

}
