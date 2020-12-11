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
class SegmentPlugin_SubscriberConditionActivity extends SegmentPlugin_Condition
{
    private $aggregatedCaptions;
    private $aggregatedIntervals;

    public function __construct($field)
    {
        parent::__construct($field);

        $this->aggregatedCaptions = [
            'last7day' => s('Any campaigns within the last 7 days'),
            'last1month' => s('Any campaigns within the last 1 month'),
            'last3month' => s('Any campaigns within the last 3 months'),
            'ever' => s('Any campaigns ever'),
        ];
        $this->aggregatedIntervals = [
            'last7day' => '7 DAY',
            'last1month' => '1 MONTH',
            'last3month' => '3 MONTH',
            'ever' => '100 YEAR',
        ];
    }

    public function operators()
    {
        return array(
            SegmentPlugin_Operator::SENT => s('was sent'),
            SegmentPlugin_Operator::NOTSENT => s('was not sent'),
            SegmentPlugin_Operator::OPENED => s('opened'),
            SegmentPlugin_Operator::NOTOPENED => s('did not open'),
            SegmentPlugin_Operator::CLICKED => s('clicked'),
            SegmentPlugin_Operator::NOTCLICKED => s('did not click'),
        );
    }

    public function display($op, $value, $namePrefix)
    {
        if (!(is_array($this->messageData['targetlist']) && count($this->messageData['targetlist']) > 0)) {
            return '';
        }
        $campaigns = $this->dao->campaigns(null, getConfig('segment_campaign_max'), array_keys($this->messageData['targetlist']));

        if (count($campaigns) == 0) {
            return s('No campaigns have been sent to the selected lists');
        }
        $listData = [];
        $listData['Aggregated Campaigns'] = $this->aggregatedCaptions;
        $listData['Sent Campaigns'] = CHtml::listData($campaigns, 'id', 'subject');

        return CHtml::dropDownList(
            $namePrefix . '[value]',
            $value,
            $listData,
            ['class' => 'campaigns']
        );
    }

    public function joinQuery($operator, $value)
    {
        if (ctype_digit($value)) {
            return $this->joinQuerySingleCampaign($operator, $value);
        }

        if (!isset($this->aggregatedIntervals[$value])) {
            throw new SegmentPlugin_ValueException();
        }

        return $this->joinQueryAggregate($operator, $this->aggregatedIntervals[$value]);
    }

    private function joinQueryAggregate($operator, $interval)
    {
        $r = new stdClass();

        if ($operator == SegmentPlugin_Operator::CLICKED || $operator == SegmentPlugin_Operator::NOTCLICKED) {
            $uml = $this->createUniqueAlias('uml');
            $negate = $operator == SegmentPlugin_Operator::CLICKED ? '' : 'NOT';
            $r->join = '';
            $r->where = <<<END
                $negate EXISTS (
                    SELECT * FROM {$this->tables['linktrack_uml_click']} $uml
                    WHERE u.id = $uml.userid AND DATE_SUB(CURDATE(), INTERVAL $interval) < $uml.latestclick
                )
END;
        } elseif ($operator == SegmentPlugin_Operator::OPENED || $operator == SegmentPlugin_Operator::NOTOPENED) {
            $umv = $this->createUniqueAlias('umv');
            $negate = $operator == SegmentPlugin_Operator::OPENED ? '' : 'NOT';
            $r->join = '';
            $r->where = <<<END
                $negate EXISTS (
                    SELECT * FROM {$this->tables['user_message_view']} $umv
                    WHERE u.id = $umv.userid AND DATE_SUB(CURDATE(), INTERVAL $interval) < $umv.viewed
                )
END;
        } elseif ($operator == SegmentPlugin_Operator::SENT || $operator == SegmentPlugin_Operator::NOTSENT) {
            $um = $this->createUniqueAlias('um');
            $negate = $operator == SegmentPlugin_Operator::SENT ? '' : 'NOT';
            $r->join = '';
            $r->where = <<<END
                $negate EXISTS (
                    SELECT * FROM {$this->tables['usermessage']} $um
                    WHERE u.id = $um.userid
                    AND $um.status = 'sent'
                    AND DATE_SUB(CURDATE(), INTERVAL $interval) < $um.entered
                )
END;
        }

        return $r;
    }

    private function joinQuerySingleCampaign($operator, $value)
    {
        $um = $this->createUniqueAlias('um');
        $uml = $this->createUniqueAlias('uml');
        $r = new stdClass();

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
