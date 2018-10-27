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
class SegmentPlugin_SubscriberConditionLists extends SegmentPlugin_Condition
{
    public function operators()
    {
        return array(
            SegmentPlugin_Operator::ALL => s('Belongs to all selected lists'),
        );
    }

    public function display($op, $value, $namePrefix)
    {
        return '';
    }

    public function joinQuery($operator, $value)
    {
        $lu = $this->createUniqueAlias('lu');
        $lm = $this->createUniqueAlias('lm');
        $r = new stdClass();
        $r->join = '';
        $r->where = <<<END
            (
                SELECT COUNT(*)
                FROM {$this->tables['listuser']} AS $lu
                JOIN {$this->tables['listmessage']} AS $lm ON $lm.listid = $lu.listid
                WHERE $lu.userid = u.id AND $lm.messageid = {$this->messageData['id']}
            )
            =
            (
                SELECT COUNT(*)
                FROM {$this->tables['listmessage']}
                WHERE messageid = {$this->messageData['id']}
            )
END;

        return $r;
    }
}
