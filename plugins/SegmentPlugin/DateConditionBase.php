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
abstract class SegmentPlugin_DateConditionBase extends SegmentPlugin_Condition
{
    /**
     * To be implemented by sub-classes.
     * A subclass must provide a set of callbacks that return
     *     the join clause
     *     the where clause for each operator.
     *
     * @return array
     */
    abstract protected function queryCallBacks();

    protected function validateInterval($interval)
    {
        if (!preg_match('/^([+-]?\d+\s+(day|week|month|quarter|year))s?$/i', $interval, $matches)) {
            throw new SegmentPlugin_ValueException();
        }

        return $matches[1];
    }

    protected function validateDates($op, $value)
    {
        if (!(is_array($value) && $value[0])) {
            throw new SegmentPlugin_ValueException();
        }

        try {
            $target1 = new DateTime($value[0]);
            $target1 = $target1->format('Y-m-d');
        } catch (Exception $e) {
            throw new SegmentPlugin_ValueException();
        }

        if ($op == SegmentPlugin_Operator::BETWEEN) {
            if (empty($value[1])) {
                throw new SegmentPlugin_ValueException();
            }
            try {
                $target2 = new DateTime($value[1]);
                $target2 = $target2->format('Y-m-d');
            } catch (Exception $e) {
                throw new SegmentPlugin_ValueException();
            }

            if ($target2 < $target1) {
                throw new SegmentPlugin_ValueException();
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
            SegmentPlugin_Operator::BETWEEN => s('is between'),
            SegmentPlugin_Operator::AFTERINTERVAL => s('after interval'),
        );
    }

    public function display($op, $value, $namePrefix)
    {
        if ($op == SegmentPlugin_Operator::ANNIVERSARY) {
            return '';
        }
        $value = (array) $value;
        $htmlOptions = array();

        if ($op != SegmentPlugin_Operator::AFTERINTERVAL) {
            $htmlOptions['class'] = 'flatpickr';
        }

        $html = CHtml::textField(
            $namePrefix . '[value][0]',
            $value[0],
            $htmlOptions
        );

        if ($op == SegmentPlugin_Operator::BETWEEN) {
            $html .= '&nbsp;';
            $html .= CHtml::textField(
                $namePrefix . '[value][1]',
                isset($value[1]) ? $value[1] : '',
                $htmlOptions
            );
        }

        return $html;
    }

    public function joinQuery($operator, $value)
    {
        $callBack = $this->queryCallBacks();
        $r = new stdClass();
        $r->join = $callBack['JOIN']();

        if ($operator == SegmentPlugin_Operator::ANNIVERSARY) {
            $r->where = $callBack[$operator]();
        } elseif ($operator == SegmentPlugin_Operator::AFTERINTERVAL) {
            $interval = $this->validateInterval($value[0]);
            $r->where = $callBack[$operator]($interval);
        } else {
            list($value1, $value2) = $this->validateDates($operator, $value);

            if ($operator == SegmentPlugin_Operator::BETWEEN) {
                $r->where = $callBack[$operator]($value1, $value2);
            } else {
                $r->where = $callBack[$operator]($value1);
            }
        }

        return $r;
    }
}
