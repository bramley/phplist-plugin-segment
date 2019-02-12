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
class SegmentPlugin_AttributeConditionDate extends SegmentPlugin_DateConditionBase
{
    public function operators()
    {
        return parent::operators() + [SegmentPlugin_Operator::ANNIVERSARY => s('on anniversary')];
    }

    protected function queryCallBacks()
    {
        $ua = $this->createUniqueAlias('ua');

        return [
            'JOIN' => function () use ($ua) {
                return "LEFT JOIN {$this->tables['user_attribute']} $ua ON u.id = $ua.userid AND $ua.attributeid = {$this->field['id']} ";
            },
            SegmentPlugin_Operator::AFTERINTERVAL => function ($interval) use ($ua) {
                return "COALESCE($ua.value, '') != '' AND CURDATE() = DATE($ua.value) + INTERVAL $interval";
            },
            SegmentPlugin_Operator::BETWEEN => function ($start, $end) use ($ua) {
                return "(COALESCE($ua.value, '') != '' AND DATE($ua.value) BETWEEN '$start' AND '$end')";
            },
            SegmentPlugin_Operator::IS => function ($date) use ($ua) {
                return "(COALESCE($ua.value, '') != '' AND DATE($ua.value) = '$date')";
            },
            SegmentPlugin_Operator::BEFORE => function ($date) use ($ua) {
                return "(COALESCE($ua.value, '') != '' AND DATE($ua.value) < '$date')";
            },
            SegmentPlugin_Operator::AFTER => function ($date) use ($ua) {
                return "(COALESCE($ua.value, '') != '' AND DATE($ua.value) > '$date')";
            },
            SegmentPlugin_Operator::ANNIVERSARY => function () use ($ua) {
                return <<<END
                    (COALESCE($ua.value, '') != ''
                    AND (
                        DAYOFMONTH($ua.value) = DAYOFMONTH(CURDATE()) AND MONTH($ua.value) = MONTH(CURDATE())
                        OR
                        (DAYOFMONTH($ua.value) = 29 AND MONTH($ua.value) = 2 AND DAYOFMONTH(CURDATE()) = 1 AND MONTH(CURDATE()) = 3
                        AND NOT (YEAR(CURDATE()) % 4 = 0 AND (YEAR(CURDATE()) % 100 != 0 OR YEAR(CURDATE()) % 400 = 0)))
                        )
                    )
END;
            },
        ];
    }
}
