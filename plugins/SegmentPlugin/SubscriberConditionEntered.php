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
class SegmentPlugin_SubscriberConditionEntered extends SegmentPlugin_DateConditionBase
{
    protected function queryCallBacks()
    {
        return [
            'JOIN' => function () {
                return '';
            },
            SegmentPlugin_Operator::AFTERINTERVAL => function ($interval) {
                return "CURDATE() = DATE(u.entered) + INTERVAL $interval";
            },
            SegmentPlugin_Operator::BETWEEN => function ($start, $end) {
                return "DATE(u.entered) BETWEEN '$start' AND '$end'";
            },
            SegmentPlugin_Operator::IS => function ($date) {
                return "DATE(u.entered) = '$date'";
            },
            SegmentPlugin_Operator::BEFORE => function ($date) {
                return "DATE(u.entered) < '$date'";
            },
            SegmentPlugin_Operator::AFTER => function ($date) {
                return "DATE(u.entered) > '$date'";
            },
        ];
    }
}
