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
class SegmentPlugin_AttributeConditionText extends SegmentPlugin_Condition
{
    public function operators()
    {
        return array(
            SegmentPlugin_Operator::IS => s($this->i18n->get('is')),
            SegmentPlugin_Operator::ISNOT => s($this->i18n->get('is_not')),
            SegmentPlugin_Operator::BLANK => s($this->i18n->get('blank')),
            SegmentPlugin_Operator::NOTBLANK => s($this->i18n->get('not_blank')),
            SegmentPlugin_Operator::MATCHES => s($this->i18n->get('matches')),
            SegmentPlugin_Operator::NOTMATCHES => s($this->i18n->get('not_matches')),
            SegmentPlugin_Operator::REGEXP => s($this->i18n->get('REGEXP')),
            SegmentPlugin_Operator::NOTREGEXP => s($this->i18n->get('not_REGEXP')),
        );
    }

    public function display($op, $value, $namePrefix)
    {
        return CHtml::textField(
            $namePrefix . '[value]',
            $value
        );
    }

    public function joinQuery($operator, $value)
    {
        if (!is_string($value)) {
            throw new SegmentPlugin_ValueException();
        }

        if ($operator != SegmentPlugin_Operator::BLANK && $operator != SegmentPlugin_Operator::NOTBLANK && $value === '') {
            throw new SegmentPlugin_ValueException();
        }

        $ua = 'ua' . $this->id;
        $value = sql_escape($value);

        switch ($operator) {
            case SegmentPlugin_Operator::ISNOT:
                $op = '!=';
                break;
            case SegmentPlugin_Operator::BLANK:
                $op = '=';
                $value = '';
                break;
            case SegmentPlugin_Operator::NOTBLANK:
                $op = '!=';
                $value = '';
                break;
            case SegmentPlugin_Operator::MATCHES:
                $op = 'LIKE';
                break;
            case SegmentPlugin_Operator::NOTMATCHES:
                $op = 'NOT LIKE';
                break;
            case SegmentPlugin_Operator::REGEXP:
                $op = 'REGEXP';
                break;
            case SegmentPlugin_Operator::NOTREGEXP:
                $op = 'NOT REGEXP';
                break;
            case SegmentPlugin_Operator::IS:
            default:
                $op = '=';
                break;
        }

        $r = new stdClass();
        $r->join = "LEFT JOIN {$this->tables['user_attribute']} $ua ON u.id = $ua.userid AND $ua.attributeid = {$this->field['id']} ";
        $r->where = "COALESCE($ua.value, '') $op '$value'";

        return $r;
    }
}
