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
 * @copyright 2014-2015 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

/**
 * 
 * 
 * @category  phplist
 * @package   SegmentPlugin
 */

abstract class SegmentPlugin_DateConditionBase extends SegmentPlugin_Condition
{
    protected function validateDates($op, $value)
    {
        if (!(is_array($value) && $value[0])) {
            throw new SegmentPlugin_ValueException;
        }

        try {
            $target1 = new DateTime($value[0]);
            $target1 = $target1->format('Y-m-d');
        } catch (Exception $e) {
            throw new SegmentPlugin_ValueException;
        }

        if ($op == SegmentPlugin_Operator::BETWEEN) {
            if (!$value[1]) {
                throw new SegmentPlugin_ValueException;
            }
            try {
                $target2 = new DateTime($value[1]);
                $target2 = $target2->format('Y-m-d');
            } catch (Exception $e) {
                throw new SegmentPlugin_ValueException;
            }
        } else {
            $target2 = null;
        }
        return array($target1, $target2);
    }

    public function operators()
    {
        return array(
            SegmentPlugin_Operator::IS => s('is'),
            SegmentPlugin_Operator::AFTER => s('is after'),
            SegmentPlugin_Operator::BEFORE => s('is before'),
            SegmentPlugin_Operator::BETWEEN => s('is between')
        );
    }

    public function display($op, $value, $namePrefix)
    {
        $value = (array)$value;
        $html = CHtml::textField(
            $namePrefix . '[value][0]',
            $value[0],
            array('class' => 'datepicker')
        );
        $html .= '&nbsp;';
        $html .= CHtml::textField(
            $namePrefix . '[value][1]',
            $op == SegmentPlugin_Operator::BETWEEN && isset($value[1]) ? $value[1] : '',
            array('class' => 'datepicker')
        );
        return $html;
    }
}
