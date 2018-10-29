<?php
/**
 * SegmentPlugin for phplist.
 *
 * This file is a part of SegmentPlugin.
 *
 * SegmentPlugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * CriteriaPlugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @category  phplist
 *
 * @author    Duncan Cameron
 * @copyright 2014-2016 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

/**
 * @category  phplist
 */
abstract class SegmentPlugin_Condition
{
    private static $count = 0;
    private $id;

    protected $field;
    protected $tables;
    protected $table_prefix;

    // set by ConditionFactory
    public $dao;
    public $messageData;

    protected function formatInList(array $values)
    {
        return '(' . implode(', ', $values) . ')';
    }

    /**
     * Create a comma separated list of the quoted array values for use in an sql statement.
     *
     * @param array values to be combined
     *
     * @return string
     */
    protected function commaQuotedList(array $values)
    {
        return implode(
            ', ',
            array_map(
                function ($item) {
                    return "'" . sql_escape($item) . "'";
                },
                $values
            )
        );
    }

    protected function createUniqueAlias($alias)
    {
        return $alias . $this->id;
    }

    public function __construct($field)
    {
        global $tables;
        global $table_prefix;

        $this->id = ++self::$count;
        $this->tables = $tables;
        $this->table_prefix = $table_prefix;
        $this->field = $field;
    }

    abstract public function operators();

    abstract public function display($op, $value, $namePrefix);

    abstract public function joinQuery($op, $value);
}
