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
 * SegmentPlugin is distributed in the hope that it will be useful,
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
 * Plugin class
 * 
 * @category  phplist
 * @package   SegmentPlugin
 */


class SegmentPlugin extends phplistPlugin
{
    const VERSION_FILE = 'version.txt';

    private $selectedSubscribers = array();
    private $noConditions = true;

/*
 *  Inherited variables
 */
    public $name = "Segmentation";
    public $authors = 'Duncan Cameron';
    public $description = 'Send to a subset of subscribers using custom conditions';
    public $settings = array(
        'segment_campaign_max' => array (
          'description' => 'The maximum number of earlier campaigns to select from',
          'type' => 'integer',
          'value' => 10,
          'allowempty' => 0,
          'min' => 4,
          'max' => 25,
          'category'=> 'Segmentation',
        )
    );

/*
 *  Private methods
 */
    private function filterEmptyFields(array $conditions)
    {
        return array_filter(
            $conditions,
            function($c) {return $c['field'] !== '';}
        );
    }

    private function filterIncompleteConditions(array $conditions)
    {
        return array_filter(
            $conditions,
            function($c) {return $c['field'] !== '' && isset($c['op']);}
        );
    }

    private function deleteNotSent($campaign)
    {
        global $plugins;
        include_once $plugins['CommonPlugin']->coderoot . 'Autoloader.php';

        $dao = new SegmentPlugin_DAO(new CommonPlugin_DB());
        $dao->deleteNotSent($campaign);
    }

    private function loadSubscribers($messageId, array $conditions, $combine)
    {
        global $plugins;
        include_once $plugins['CommonPlugin']->coderoot . 'Autoloader.php';

        $dao = new SegmentPlugin_DAO(new CommonPlugin_DB());
        $cf = new SegmentPlugin_ConditionFactory();
        $subquery = array();

        foreach ($conditions as $i => $c) {
            $field = $c['field'];
            $condition = $cf->createCondition($field);

            try {
                $subquery[] = $condition->subquery($c['op'], isset($c['value']) ? $c['value'] : '');
            } catch (SegmentPlugin_ValueException $e) {
                // do nothing
            }
        }

        if (count($subquery) > 0) {
            $this->selectedSubscribers = array_flip($dao->subscribers($messageId, $subquery, $combine));
        }
    }

/*
 *  Public methods
 */
    public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/' . __CLASS__ . '/';
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        parent::__construct();
    }

    public function adminmenu()
    {
        return array();
    }

    public function sendMessageTab($messageId = 0 , $data = array())
    {
        $er = error_reporting(-1);
        global $plugins, $pagefooter;

        if (!phplistPlugin::isEnabled('CommonPlugin')) {
            return 'CommonPlugin must be installed in order to use segments';
        }

        include_once $plugins['CommonPlugin']->coderoot . 'Autoloader.php';

        $cf = new SegmentPlugin_ConditionFactory();

        $conditions = (isset($data['segment']['c']))
            ? array_values($this->filterEmptyFields($data['segment']['c']))
            : array();

        $conditions[] = array('field' => '');
        $conditionArea = '';

        foreach ($conditions as $i => $c) {
            $fieldList = CHtml::dropDownList(
                "segment[c][$i][field]",
                $c['field'],
                array(
                    'Subscriber Data' => $cf->subscriberFields(),
                    'Attributes' => $cf->attributeFields()
                ),
                array(
                    'prompt' => 'Select ...',
                    'onchange' => 'this.form.submit()',
                )
            );
            // hidden input to detect when field changes
            $hiddenField = CHtml::hiddenField("segment[c][$i][_field]", $c['field']);
            $field = $c['field'];

            if ($field != '') {
                $condition = $cf->createCondition($field);
                $operators = $condition->operators();

                $op = ($field == $c['_field'] && isset($c['op']))
                    ? $c['op'] : key($operators);
                $operatorList = CHtml::dropDownList(
                    "segment[c][$i][op]",
                    $op,
                    $operators
                );

                $value = ($field == $c['_field'] && isset($c['value']))
                    ? $c['value'] : '';
                $valueInput = $condition->valueEntry($value, "segment[c][$i]");
            } else {
                $operatorList = '';
                $valueInput = '';
            }
            $conditionArea .= <<<END
        <li class="selfclear">
        <div class="segment-block">$fieldList$hiddenField</div>
        <div class="segment-block">$operatorList</div>
        <div class="segment-block">$valueInput</div>
        </li>
END;
        }
        $calculateButton = CHtml::submitButton('Calculate', array('name' => 'segment[calculate]'));
        $combine = isset($data['segment']['combine']) 
            ? $data['segment']['combine'] : SegmentPlugin_Operator::ALL;
        $combineList = CHtml::dropDownList(
            "segment[combine]",
            $combine,
            array(SegmentPlugin_Operator::ONE => 'any', SegmentPlugin_Operator::ALL => 'all')
        );

        if (isset($data['segment']['calculate'])) {
            $this->loadSubscribers(
                $messageId,
                $this->filterIncompleteConditions($data['segment']['c']),
                $combine
            );
            $subscribers = count($this->selectedSubscribers) . ' subscribers will be selected';
        } else {
            $subscribers = '';
        }

        $html = file_get_contents($this->coderoot . '/styles.css');
        $html .= <<<END
<div class="segment">
    <div>Select one or more subscriber fields or attributes.
    The campaign will be sent only to those subscribers who match any or all of the conditions.
    To remove a condition, choose 'Select ...' from the drop-down list.
    </div>
    <div>Subscribers match $combineList of the following:</div>
    <ul>$conditionArea
    </ul>
    <div id="recalculate">$calculateButton $subscribers</div>
</div>
END;
        $pagefooter[basename(__FILE__)] = file_get_contents($this->coderoot . '/date.js');
        error_reporting($er);
        return $html;
    }

    public function sendMessageTabTitle($messageid = 0)
    {
        return s('Segment');
    }

    public function messageQueued($id)
    {
        $this->deleteNotSent($id);
    }

    public function messageReQueued($id)
    {
        $this->messageQueued($id);
    }

    public function campaignStarted($messageData = array())
    {
        $er = error_reporting(-1);
        $this->noConditions = true;
        $this->selectedSubscribers = array();

        if (isset($messageData['segment']['c'])) {
            $conditions = $this->filterIncompleteConditions($messageData['segment']['c']);

            if (count($conditions) > 0) {
                $this->noConditions = false;
                $this->loadSubscribers($messageData['id'], $conditions, $messageData['segment']['combine']);
            }
        }
        error_reporting($er);
    }

    public function canSend($messageData, $userData)
    {
        if ($this->noConditions) {
            return true;
        }

        return isset($this->selectedSubscribers[$userData['id']]);
    }
 }
