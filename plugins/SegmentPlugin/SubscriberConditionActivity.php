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

class SegmentPlugin_SubscriberConditionActivity extends SegmentPlugin_Condition
{
    public function operators()
    {
        return array(
            SegmentPlugin_Operator::SENT => s($this->i18n->get('was_sent')),
            SegmentPlugin_Operator::NOTSENT => s($this->i18n->get('was_not_sent')),
            SegmentPlugin_Operator::OPENED => s($this->i18n->get('opened')),
            SegmentPlugin_Operator::NOTOPENED => s($this->i18n->get('did_not_open')),
            SegmentPlugin_Operator::CLICKED => s($this->i18n->get('clicked')),
            SegmentPlugin_Operator::NOTCLICKED => s($this->i18n->get('did_not_click')),
        );
    }

    public function display($op, $value, $namePrefix)
    {
        if (is_array($this->messageData['targetlist']) && count($this->messageData['targetlist']) > 0) {
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

    public function joinQuery($operator, $value)
    {
        if (!ctype_digit($value)) {
            throw new SegmentPlugin_ValueException;
        }

        $um = 'um' . $this->id;
        $uml = 'uml' . $this->id;
        $r = new stdClass;

        if ($operator == SegmentPlugin_Operator::CLICKED || $operator == SegmentPlugin_Operator::NOTCLICKED) {
            $op = $operator == SegmentPlugin_Operator::CLICKED ? 'IS NOT NULL' : 'IS NULL';
            $r->join = <<<END
                JOIN {$this->tables['usermessage']} $um ON u.id = $um.userid AND $um.status = 'sent' AND $um.messageid = $value
                LEFT JOIN {$this->tables['linktrack_uml_click']} $uml ON u.id = $uml.userid AND $uml.messageid = $um.messageid
END;
            $r->where = "$uml.userid $op";
            
        } elseif ($operator == SegmentPlugin_Operator::OPENED || $operator == SegmentPlugin_Operator::NOTOPENED) {
            $op = $operator == SegmentPlugin_Operator::OPENED ? 'IS NOT NULL' : 'IS NULL';
            $r->join = <<<END
                JOIN {$this->tables['usermessage']} $um ON u.id = $um.userid AND $um.status = 'sent' AND $um.messageid = $value
END;
            $r->where = "$um.viewed $op";
        } elseif ($operator == SegmentPlugin_Operator::SENT || $operator == SegmentPlugin_Operator::NOTSENT) {
            $op = $operator == SegmentPlugin_Operator::SENT ? 'IS NOT NULL' : 'IS NULL';
            $r->join = <<<END
                LEFT JOIN {$this->tables['usermessage']} $um ON u.id = $um.userid AND $um.status = 'sent' AND $um.messageid = $value
END;
            $r->where = "$um.userid $op";
        }
        return $r;
    }
}
