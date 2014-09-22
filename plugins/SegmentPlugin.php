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
    private $firstTime = true;

    public $name = "Segmentation";
    public $authors = 'Duncan Cameron';
    public $description = 'Use custom criteria to segment message';
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

    private function loadSubscribers($messageId, array $conditions)
    {
        global $plugins;
        include_once $plugins['CommonPlugin']->coderoot . 'Autoloader.php';

        $dao = new SegmentPlugin_DAO(new CommonPlugin_DB());
        $cf = new SegmentPlugin_ConditionFactory();
        $subquery = array();

        foreach ($conditions as $i => $c) {
            $field = $c['field'];
            $condition = $cf->createCondition($field);
            $subquery[] = $condition->subquery($c['op'], isset($c['value']) ? $c['value'] : '');
        }

        if (count($subquery) > 0) {
            $this->selectedSubscribers = array_flip($dao->subscribers($messageId, $subquery));
        }
    }

    public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/' . __CLASS__ . '/';
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        parent::__construct();
        $this->campaignListMax = getConfig('segment_campaign_max');
    }

    public function adminmenu()
    {
        return array();
    }

    public function sendMessageTab($messageId, $data)
    {
        error_reporting(-1);
        global $plugins, $pagefooter;

        if (!phplistPlugin::isEnabled('CommonPlugin')) {
            return 'CommonPlugin must be installed in order to use segments';
        }

        include_once $plugins['CommonPlugin']->coderoot . 'Autoloader.php';

        try {
            $cf = new SegmentPlugin_ConditionFactory();

            if (isset($data['segment']['c'])) {
                $conditions = array_values($this->filterEmptyFields($data['segment']['c']));
                $this->loadSubscribers(
                    $messageId,
                    $this->filterIncompleteConditions($data['segment']['c'])
                );
            } else {
                $conditions = array();
            }
            $conditions[] = array('field' => '');
            $area = '';
        } catch (Exception $e) {
            echo $e->getTraceAsString();
            return '';
        }

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
            $field = $c['field'];

            if ($field != '') {
                $condition = $cf->createCondition($field);
                $operatorList = CHtml::dropDownList(
                    "segment[c][$i][op]",
                    isset($c['op']) ? $c['op'] : '',
                    $condition->operators(),
                    array('onchange' => 'this.form.submit()')
                );
                $value = isset($c['value']) ? $c['value'] : '';
                $valueInput = $condition->valueEntry($value, "segment[c][$i]");
            } else {
                $operatorList = '';
                $valueInput = '';
            }
            $area .= <<<END
        <li class="selfclear">
        <div class="segment-block">$fieldList</div>
        <div class="segment-block">$operatorList</div>
        <div class="segment-block">$valueInput</div>
        </li>
END;
        }
        $calculateButton = new CommonPlugin_PageLink(
            (string) new CommonPlugin_PageURL($_GET['page'], array('id' => $_GET['id'], 'tab' => $_GET['tab'])),
            'Recalculate',
            array('class' => 'button savechanges')
        );
        $subscribers = count($this->selectedSubscribers);
        $html = file_get_contents($this->coderoot . '/styles.css');
        $html .= <<<END
<div class="segment">
    <div>
        <p>Select one or more subscriber fields or attributes.
        The campaign will be sent only to those subscribers who match all of the conditions.
        <br/>To remove a condition, choose 'Select ...' from the drop-down list.
        </p>
        <ul>$area
        </ul>
        <p id="recalculate">$calculateButton $subscribers subscribers will be selected</p>
    </div>
</div>
END;
        $pagefooter[basename(__FILE__)] = file_get_contents($this->coderoot . '/date.js');
        return $html;
    }

    public function sendMessageTabTitle($messageid = 0)
    {
        return s('Segment');
    }

    public function messageQueued($id)
    {
        error_reporting(-1);
        $this->deleteNotSent($id);

        //$messageData = loadMessageData($id);
        //$this->loadSubscribers(
            //$this->filterIncompleteConditions($messageData['segment']['c'])
        //);

    }

    public function messageReQueued($id)
    {
        $this->messageQueued($id);
    }

    public function canSend($messageData, $userData)
    {
        error_reporting(-1);

        if ($this->firstTime) {
            $this->firstTime = false;

            if (isset($messageData['segment']['c'])) {
                $conditions = $this->filterIncompleteConditions($messageData['segment']['c']);

                if (count($conditions) > 0) {
                    $this->noConditions = false;
                    $this->loadSubscribers($messageData['id'], $this->filterIncompleteConditions($conditions));
                }
            }
        }

        if ($this->noConditions) {
            return true;
        }

        return isset($this->selectedSubscribers[$userData['id']]);
    }
 }
