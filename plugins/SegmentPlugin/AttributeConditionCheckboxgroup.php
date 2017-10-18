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
class SegmentPlugin_AttributeConditionCheckboxgroup extends SegmentPlugin_Condition
{
    public function operators()
    {
        return array(
            SegmentPlugin_Operator::ONE => s('one checked'),
            SegmentPlugin_Operator::ALL => s('all checked'),
            SegmentPlugin_Operator::NONE => s('none checked'),
        );
    }

    public function display($op, $value, $namePrefix)
    {
        $selectData = CHtml::listData($this->dao->selectData($this->field), 'id', 'name');

        return CHtml::listBox(
            $namePrefix . '[value]',
            $value,
            $selectData,
            array('multiple' => 1)
        );
    }

    public function joinQuery($operator, $value)
    {
        if (!is_array($value) || count($value) == 0) {
            throw new SegmentPlugin_ValueException();
        }

        $ua = $this->createUniqueAlias('ua');
        $where = array();

        if ($operator == SegmentPlugin_Operator::ONE) {
            $compare = '>';
            $boolean = 'OR';
        } elseif ($operator == SegmentPlugin_Operator::ALL) {
            $compare = '>';
            $boolean = 'AND';
        } else {
            $compare = '=';
            $boolean = 'AND';
        }

        foreach ($value as $item) {
            $where[] = "FIND_IN_SET($item, COALESCE($ua.value, '')) $compare 0";
        }

        $r = new stdClass();
        $r->join = "LEFT JOIN {$this->tables['user_attribute']} $ua ON u.id = $ua.userid AND $ua.attributeid = {$this->field['id']} ";
        $r->where = '(' . implode(" $boolean ", $where) . ')';

        return $r;
    }
}
