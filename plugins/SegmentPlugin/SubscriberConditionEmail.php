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

class SegmentPlugin_SubscriberConditionEmail extends SegmentPlugin_SubscriberConditionBase
{
    public function operators()
    {
        return array(
            'is' => 'is',
            'matches' => 'matches',
            'notmatches' => 'does not match',
        );
    }

    public function valueEntry($value, $namePrefix)
    {
        return CHtml::textField(
            $namePrefix . '[value]',
            $value
        );
    }

    public function subquery($op, $value)
    {
        return $this->dao->emailSubquery($op, $value);
    }
}
