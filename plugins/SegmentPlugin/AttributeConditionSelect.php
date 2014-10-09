<?php
/**
 * SegmentPlugin for phplist
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
 * @package   SegmentPlugin
 * @author    Duncan Cameron
 * @copyright 2014 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

/**
 * 
 * 
 * @category  phplist
 * @package   SegmentPlugin
 */

class SegmentPlugin_AttributeConditionSelect extends SegmentPlugin_AttributeConditionBase
{
    public function operators()
    {
        return array(
            SegmentPlugin_Operator::ONE => 'is one of',
            SegmentPlugin_Operator::NONE => 'is none of',
        );
    }

    public function valueEntry($value, $namePrefix)
    {
        $selectData = CHtml::listData($this->dao->selectData($this->attribute), 'id', 'name');

        return CHtml::listBox(
            $namePrefix . '[value]',
            $value,
            $selectData,
            array(
                'multiple' => 1, 'size' => 4,
            )
        );
    }

    public function select($op, $value)
    {
        if (!is_array($value) || count($value) == 0) {
            throw new SegmentPlugin_ValueException;
        }
        return $this->dao->selectSelect($this->attribute['id'], $op, $value);
    }
}
