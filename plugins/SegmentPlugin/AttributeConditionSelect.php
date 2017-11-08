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
class SegmentPlugin_AttributeConditionSelect extends SegmentPlugin_Condition
{
    public function operators()
    {
        return array(
            SegmentPlugin_Operator::ONE => s('is one of'),
            SegmentPlugin_Operator::NONE => s('is none of'),
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
        $in = ($operator == SegmentPlugin_Operator::ONE ? 'IN ' : 'NOT IN ') . $this->formatInList($value);

        $r = new stdClass();
        $r->join = "LEFT JOIN {$this->tables['user_attribute']} $ua ON u.id = $ua.userid AND $ua.attributeid = {$this->field['id']} ";
        $r->where = "COALESCE($ua.value, 0) $in";

        return $r;
    }
}
