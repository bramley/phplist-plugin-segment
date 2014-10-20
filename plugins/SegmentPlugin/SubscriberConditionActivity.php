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

class SegmentPlugin_SubscriberConditionActivity extends SegmentPlugin_SubscriberConditionBase
{
    public function operators()
    {
        return array(
            SegmentPlugin_Operator::SENT => 'was sent',
            SegmentPlugin_Operator::NOTSENT => 'was not sent',
            SegmentPlugin_Operator::OPENED => 'opened',
            SegmentPlugin_Operator::NOTOPENED => 'did not open',
            SegmentPlugin_Operator::CLICKED => 'clicked',
            SegmentPlugin_Operator::NOTCLICKED => 'did not click',
        );
    }

    public function valueEntry($value, $namePrefix)
    {
        if (count($this->messageData['targetlist']) > 0) {
            $selectData = CHtml::listData(
                $this->dao->campaigns(null, getConfig('segment_campaign_max'), array_keys($this->messageData['targetlist'])),
                'id', 'subject'
            );
        } else {
            $selectData = array();
        }

        return CHtml::dropDownList(
            $namePrefix . '[value]',
            $value,
            $selectData
        );
    }

    public function select($op, $value)
    {
        if (!ctype_digit($value)) {
            throw new SegmentPlugin_ValueException;
        }
        return $this->dao->activitySelect($op, $value);
    }
}
