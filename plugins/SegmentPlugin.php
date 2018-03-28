<?php

use chdemko\BitArray\BitArray;

/**
 * SegmentPlugin for phplist.
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
 *
 * @author    Duncan Cameron
 * @copyright 2014-2017 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

/**
 * Plugin class.
 *
 * @category  phplist
 */
class SegmentPlugin extends phplistPlugin
{
    const VERSION_FILE = 'version.txt';
    const GUIDANCE = 'https://resources.phplist.com/plugin/segment#add_segment_conditions';

    private $error_level = E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT;
    private $selectedSubscribers = null;
    private $dao;

    /*
     *  Inherited variables
     */
    public $name = 'Segmentation';
    public $authors = 'Duncan Cameron';
    public $description = 'Send to a subset of subscribers using custom conditions';
    public $documentationUrl = 'https://resources.phplist.com/plugin/segment';
    public $settings;

    private function filterEmptyFields(array $conditions)
    {
        return array_filter(
            $conditions,
            function ($c) {
                return $c['field'] !== '';
            }
        );
    }

    private function filterIncompleteConditions(array $conditions)
    {
        return array_filter(
            $conditions,
            function ($c) {
                return $c['field'] !== '' && isset($c['op']);
            }
        );
    }

    private function deleteNotSent($campaign)
    {
        $this->dao->deleteNotSent($campaign);
    }

    private function selectionQueryJoins(array $conditions)
    {
        $cf = new SegmentPlugin_ConditionFactory($this->dao);
        $joins = array();

        foreach ($conditions as $i => $c) {
            $field = $c['field'];

            try {
                $condition = $cf->createCondition($field);
                $joins[] = $condition->joinQuery($c['op'], isset($c['value']) ? $c['value'] : '');
            } catch (SegmentPlugin_ConditionException $e) {
                // do nothing
            }
        }

        return $joins;
    }

    private function loadSubscribers($messageId, array $conditions, $combine)
    {
        $highest = $this->dao->highestSubscriberId();
        $subscribers = BitArray::fromInteger($highest + 1);
        $joins = $this->selectionQueryJoins($conditions);

        if (count($joins) > 0) {
            foreach ($this->dao->subscribers($messageId, $joins, $combine) as $row) {
                $subscribers[(int) $row['id']] = 1;
            }
        }

        return $subscribers;
    }

    private function calculateSubscribers($messageId, array $conditions, $combine)
    {
        $this->logger->debug(sprintf(
            "Prior usage %s\nPrior peak usage %s\nPrior peak real usage %s",
            memory_get_usage(), memory_get_peak_usage(), memory_get_peak_usage(true)
        ));
        $joins = $this->selectionQueryJoins($conditions);
        $count = $this->dao->calculateSubscribers($messageId, $joins, $combine);
        $this->logger->debug(sprintf(
            "Post usage %s\nPost peak usage %s\nPost peak real usage %s",
            memory_get_usage(), memory_get_peak_usage(), memory_get_peak_usage(true)
        ));

        return $count;
    }

    private function render($template, $params)
    {
        extract($params);
        ob_start();
        require $this->coderoot . $template;

        return ob_get_clean();
    }

    /**
     * Saves the segment as a message data field.
     *
     * @param int   $messageId message id
     * @param array $segment   segment
     * @param array $toUnset   optional segment fields to be unset
     */
    private function saveMessageSegment($messageId, array $segment, array $toUnset = [])
    {
        foreach ($toUnset as $item) {
            unset($segment[$item]);
        }
        setMessageData($messageId, 'segment', $segment);
    }

    /**
     * Remove duplicate entries from a multi-dimensional array.
     *
     * @param array $input
     *
     * @return array
     */
    private function array_unique(array $input)
    {
        return array_values(
            array_intersect_key(
                $input,
                array_unique(
                    array_map('serialize', $input)
                )
            )
        );
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/' . __CLASS__ . '/';
        $this->settings = array(
            'segment_campaign_max' => array(
              'description' => s('The maximum number of earlier campaigns to select from'),
              'type' => 'integer',
              'value' => 10,
              'allowempty' => 0,
              'min' => 4,
              'max' => 25,
              'category' => 'Segmentation',
            ),
            'segment_saved_summary' => array(
              'description' => s('Summary of saved segments'),
              'type' => 'textarea',
              'value' => '',
              'allowempty' => true,
              'category' => 'Segmentation',
            ),
        );
        parent::__construct();
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
    }

    /**
     * Provide the dependencies for enabling this plugin.
     *
     * @return array
     */
    public function dependencyCheck()
    {
        global $plugins;

        return array(
            'Common plugin version 3.5.6 or greater installed' => (
                phpListPlugin::isEnabled('CommonPlugin')
                && version_compare($plugins['CommonPlugin']->version, '3.5.6') >= 0
            ),
            'PHP version 5.4.0 or greater' => version_compare(PHP_VERSION, '5.4') > 0,
        );
    }

    /**
     * Use this method as a hook to create the dao
     * Need to create autoloader because of the unpredictable order in which plugins are called.
     */
    public function sendFormats()
    {
        global $plugins;

        require_once $plugins['CommonPlugin']->coderoot . 'Autoloader.php';
        $this->dao = new SegmentPlugin_DAO(new CommonPlugin_DB());
        $this->logger = CommonPlugin_Logger::instance();

        return;
    }

    /**
     * List of items to add to menu.
     *
     * @return array
     */
    public function adminmenu()
    {
        return array();
    }

    /**
     * Build contents of additional tab.
     *
     * @param int   $messageId
     * @param array $messageData
     *
     * @return string the html for the tab
     */
    public function sendMessageTab($messageId = 0, $messageData = array())
    {
        global $pagefooter;

        $er = error_reporting($this->error_level);
        $segment = isset($messageData['segment']) ? $messageData['segment'] : array();
        $conditions = (isset($segment['c']))
            ? array_values($this->filterEmptyFields($segment['c']))
            : array();
        $saved = new SegmentPlugin_SavedSegments();

        if (isset($segment['remove'])) {
            $this->saveMessageSegment($messageId, $segment, ['c', 'combine', 'remove']);
            $conditions = [];
        } elseif (isset($segment['save']) && $segment['savename'] != '') {
            /*
             *  Save the current set of conditions
             */
            $saved->addSegment($segment['savename'], $this->filterIncompleteConditions($conditions));
            $this->saveMessageSegment($messageId, $segment, ['save']);
        } elseif (isset($segment['load']) && isset($segment['usesaved']) && is_array($segment['usesaved'])) {
            /*
             *  Load saved segments and save the message data
             */
            try {
                foreach ($segment['usesaved'] as $savedId) {
                    $savedConditions = $saved->segmentById($savedId);
                    $conditions = array_merge($conditions, $savedConditions);
                }
            } catch (Exception $e) {
                // do nothing
            }
            $conditions = $this->array_unique($conditions);
            $segment['c'] = $conditions;
            $this->saveMessageSegment($messageId, $segment, ['load', 'usesaved']);
        }
        $combine = isset($segment['combine'])
            ? $segment['combine'] : SegmentPlugin_Operator::ALL;

        $conditions[] = array('field' => '');
        $selectPrompt = s('Select ...');
        $params = array();
        $params['condition'] = array();
        $params['selectPrompt'] = $selectPrompt;
        $cf = new SegmentPlugin_ConditionFactory($this->dao);

        foreach ($conditions as $i => $c) {
            $s = new stdClass();
            $params['condition'][] = $s;

            // display field selection drop-down list
            $s->fieldList = CHtml::dropDownList(
                "segment[c][$i][field]",
                $c['field'],
                array('Subscriber Data' => $cf->subscriberFields(), 'Attributes' => $cf->attributeFields()),
                array('prompt' => $selectPrompt, 'class' => 'autosubmit')
            );

            // display hidden input to detect when field changes
            $s->hiddenField = CHtml::hiddenField("segment[c][$i][_field]", $c['field']);
            $field = $c['field'];

            if ($field == '') {
                continue;
            }

            try {
                $condition = $cf->createCondition($field);
            } catch (SegmentPlugin_ConditionException $e) {
                $s->error = sprintf('Unable to display condition: %s', $e->getMessage());
                continue;
            }
            $condition->messageData = $messageData;

            // display operators drop-down list
            $operators = $condition->operators();

            $op = ($field == $c['_field'] && isset($c['op'])) ? $c['op'] : key($operators);
            $s->operatorList = CHtml::dropDownList(
                "segment[c][$i][op]",
                $op,
                $operators,
                array('class' => 'autosubmit')
            );

            // display value field
            $value = ($field == $c['_field'] && isset($c['value'])) ? $c['value'] : '';
            $s->display = $condition->display($op, $value, "segment[c][$i]");
        }

        // display warning if no lists have been selected
        if (!(is_array($messageData['targetlist']) && count($messageData['targetlist']) > 0)) {
            $params['warning'] = s('Please select at least one list on the Lists tab before adding segmentation conditions');
        }

        // display fields for saved segments only where there are some
        $savedListData = $saved->selectListData();

        if (count($savedListData) > 0) {
            $params['savedList'] = CHtml::dropDownList(
                'segment[usesaved][]',
                '',
                $savedListData,
                array('multiple' => 'multiple', 'style' => 'width: 50%')
            );
            $params['loadButton'] = CHtml::submitButton(s('Load segments'), array('name' => 'segment[load]'));
            $params['settingsButton'] = new CommonPlugin_PageLink(
                new CommonPlugin_PageURL('configure', array(), 'segmentation'),
                'Edit saved segments',
                array('target' => '_blank', 'class' => 'button')
            );
        }

        // display calculate button
        $params['calculateButton'] = CHtml::submitButton(s('Calculate'), array('name' => 'segment[calculate]'));

        // display combine drop-down list
        $params['combineList'] = CHtml::dropDownList(
            'segment[combine]',
            $combine,
            array(SegmentPlugin_Operator::ONE => s('any'), SegmentPlugin_Operator::ALL => s('all'))
        );

        // display calculated number of subscribers
        if (isset($segment['calculate'])) {
            try {
                $params['totalSubscribers'] = $this->calculateSubscribers(
                    $messageId,
                    $this->filterIncompleteConditions($segment['c']),
                    $combine
                );
            } catch (SegmentPlugin_ValueException $e) {
                $params['warning'] = s('One of the conditions has an invalid target value');
            }
        }

        // display remove all, save button and input field only when there is at least one entered condition
        if (count($conditions) > 1) {
            $params['removeButton'] = CHtml::submitButton(s('Remove all'), array('name' => 'segment[remove]'));
            $params['saveButton'] = CHtml::submitButton(s('Save segment'), array('name' => 'segment[save]'));
            $params['saveName'] = CHtml::textField('segment[savename]', '', array('size' => 25, 'placeholder' => 'Name of segment'));
        }

        // display link to Help page
        $params['help'] = CHtml::tag(
            'a',
            array('href' => self::GUIDANCE, 'target' => '_blank'),
            new \phpList\plugin\Common\ImageTag('info.png', 'Guidance')
        );
        $html = $this->render('sendtab.tpl.php', $params);
        $pagefooter[basename(__FILE__)] = file_get_contents($this->coderoot . 'script.html');
        error_reporting($er);

        return $html;
    }

    /**
     * The title of the additional tab.
     *
     * @param int $messageId
     *
     * @return string the title
     */
    public function sendMessageTabTitle($messageid = 0)
    {
        return s('Segment');
    }

    /**
     * Use this hook to delete the 'not sent' rows from the usermessage table
     * so that they will be re-evaluated.
     *
     * @param int $id the message id
     *
     * @return none
     */
    public function messageQueued($id)
    {
        $this->deleteNotSent($id);
    }

    /**
     * The same processing as when queueing a message.
     *
     * @param int $id the message id
     *
     * @return none
     */
    public function messageReQueued($id)
    {
        $this->messageQueued($id);
    }

    /**
     * Use this hook to select the subscribers who meet the segment conditions.
     * $selectedSubscribers will contain the selected subscribers.
     *
     * @param array $messageData the message data
     *
     * @return none
     */
    public function campaignStarted($messageData = array())
    {
        $er = error_reporting($this->error_level);

        if (isset($messageData['segment']['c'])) {
            $conditions = $this->filterIncompleteConditions($messageData['segment']['c']);

            if (count($conditions) > 0) {
                try {
                    $this->selectedSubscribers = $this->loadSubscribers(
                        $messageData['id'],
                        $conditions,
                        $messageData['segment']['combine']
                    );
                } catch (SegmentPlugin_ValueException $e) {
                    logEvent("Invalid segment condition, message {$messageData['id']}");
                }
            }
        }
        error_reporting($er);
    }

    /**
     * Determine whether the campaign should be sent to a specific user.
     *
     * @param array $messageData the message data
     * @param array $userData    the user data
     *
     * @return bool
     */
    public function canSend($messageData, $userData)
    {
        return ($this->selectedSubscribers === null)
            ? true
            : (bool) $this->selectedSubscribers[(int) $userData['id']];
    }

    /**
     * Build the html to be added to the view message page.
     *
     * @param int   $messageId   the message id
     * @param array $messageData the message data
     *
     * @return array|false the caption and html to be added, or false if the message
     *                     does not use segments
     */
    public function viewMessage($messageId, array $messageData)
    {
        $er = error_reporting($this->error_level);

        if (!isset($messageData['segment'])) {
            return false;
        }
        $segment = $messageData['segment'];

        if (!isset($segment['c'])) {
            return false;
        }
        $conditions = array_values($this->filterEmptyFields($segment['c']));

        if (count($conditions) == 0) {
            return false;
        }
        $combine = isset($segment['combine'])
            ? $segment['combine'] : SegmentPlugin_Operator::ALL;

        $params = array();
        $params['condition'] = array();
        $cf = new SegmentPlugin_ConditionFactory($this->dao);

        foreach ($conditions as $i => $c) {
            $s = new stdClass();
            $params['condition'][] = $s;
            $field = $c['field'];

            try {
                $condition = $cf->createCondition($field);
            } catch (SegmentPlugin_ConditionException $e) {
                $s->error = sprintf('Unable to display condition: %s', $e->getMessage());
                continue;
            }
            $condition->messageData = $messageData;

            // display field selection
            $fields = $cf->subscriberFields() + $cf->attributeFields();
            $s->field = $fields[$field];

            // display operator
            $operators = $condition->operators();
            $op = ($field == $c['_field'] && isset($c['op'])) ? $c['op'] : key($operators);
            $s->operator = $operators[$op];

            // display value field
            $value = ($field == $c['_field'] && isset($c['value'])) ? $c['value'] : '';
            $s->display = $condition->display($op, $value, "segment[c][$i]");
        }

        // display combine
        $combineOps = array(SegmentPlugin_Operator::ONE => s('any'), SegmentPlugin_Operator::ALL => s('all'));
        $params['combine'] = $combineOps[$combine];

        $html = $this->render('viewmessage.tpl.php', $params);
        error_reporting($er);

        return array('Segment conditions', $html);
    }

    /**
     * Called when a campaign is being copied.
     * Allows this plugin to specify which rows of the messagedata table should also
     * be copied.
     *
     * @return array rows of messagedata table that should be copied
     */
    public function copyCampaignHook()
    {
        return array('segment');
    }
}
