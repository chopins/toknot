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
        $dropSQLs = ['mysql' => "DROP TABLE IF EXISTS $hit",
            'sqlserver' => "IF OBJECT_ID('$hit','U') IS NOT NULL DROP TABLE $hit",
            'oracle' => "BEGIN EXECUTE IMMEDIATE 'DROP TABLE $hit';
                        EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE;
                        END IF;END;",
            'postgresql' => "DROP TABLE IF EXISTS $hit",
            'drizzle' => "DROP TABLE IF EXISTS $hit",
            'sqlazure' => "DROP TABLE IF EXISTS $hit",
            'sqlite' => "DROP TABLE IF EXISTS $hit",
            'sqlanywhere' => "DROP TABLE IF EXISTS $hit"];

        $platformName = strtolower($platform->getName());

        if (!isset($dropSQLs[$platformName])) {
            throw new BaseException("$platformName not support 'IF EXISTS' for check table");
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
