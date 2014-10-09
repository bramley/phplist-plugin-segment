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

class SegmentPlugin_AttributeConditionText extends SegmentPlugin_AttributeConditionBase
{
    public function operators()
    {
        return array(
            SegmentPlugin_Operator::IS => 'is',
            SegmentPlugin_Operator::ISNOT => 'is not',
            SegmentPlugin_Operator::BLANK => 'is blank',
            SegmentPlugin_Operator::NOTBLANK => 'is not blank',
            SegmentPlugin_Operator::MATCHES => 'matches',
            SegmentPlugin_Operator::NOTMATCHES => 'does not match',
            SegmentPlugin_Operator::REGEXP => 'REGEXP',
            SegmentPlugin_Operator::NOTREGEXP => 'not REGEXP',
        );
    }

    public function valueEntry($value, $namePrefix)
    {
        return CHtml::textField(
            $namePrefix . '[value]',
            $value
        );
    }

    public function select($op, $value)
    {
        if (!is_string($value)) {
            throw new SegmentPlugin_ValueException;
        }

        return $this->dao->textSelect($this->attribute['id'], $op, $value);
    }
}
