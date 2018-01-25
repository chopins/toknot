<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\DB;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * TinyintType
 *
 * @author chopin
 */
class TinyintType extends Type {

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration,
            AbstractPlatform $platform) {
        if (stripos(get_class($platform), 'mysql')) {
            $unsigned = (isset($fieldDeclaration['unsigned']) && $fieldDeclaration['unsigned']) ? ' UNSIGNED' : '';
            return 'TINYINT ' . $unsigned;
        }
        return $platform->getSmallIntTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform) {
        return (null === $value) ? null : (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'tinyint';
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType() {
        return \PDO::PARAM_INT;
    }

}
