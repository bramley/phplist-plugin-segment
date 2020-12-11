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
use phpList\plugin\Common\Container;
use phpList\plugin\Common\ImageTag;
use phpList\plugin\Common\PageLink;
use phpList\plugin\Common\PageURL;
use phpList\plugin\SegmentPlugin\Segment;

class SegmentPlugin extends phplistPlugin
{
    const VERSION_FILE = 'version.txt';
    const GUIDANCE = 'https://resources.phplist.com/plugin/segment#add_segment_conditions';

    /*
     *  Inherited variables
     */
    public $name = 'Segmentation';
    public $authors = 'Duncan Cameron';
    public $description = 'Send to a subset of subscribers using custom conditions';
    public $documentationUrl = 'https://resources.phplist.com/plugin/segment';
    public $settings;

    private $error_level;
    private $selectedSubscribers = array();
    private $dao;
    private $conditionFactory;
    private $logger;
    private $segment = null;

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
            'segment_subscribers_max' => array(
                'description' => s('The maximum number of selected subscribers to display'),
                'type' => 'integer',
                'value' => 50,
                'allowempty' => 0,
                'min' => 5,
                'max' => 500,
                'category' => 'Segmentation',
            ),
            'segment_saved_summary' => array(
                'description' => s('Summary of saved segments'),
                'type' => 'textarea',
                'value' => '',
                'allowempty' => true,
                'category' => 'Segmentation',
            ),
            'segment_attribute_max_length' => array(
                'description' => s('Limit the display length of an attribute name. Enter 0 to always display the full attribute name'),
                'type' => 'integer',
                'value' => 0,
                'allowempty' => true,
                'min' => 0,
                'max' => 100,
                'category' => 'Segmentation',
            ),
        );
        $this->error_level = E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT;

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
            'phpList version 3.3.2 or later' => version_compare(VERSION, '3.3.2') >= 0,
            'Common plugin version 3.11.0 or greater must be enabled' => (
                phpListPlugin::isEnabled('CommonPlugin')
                && version_compare($plugins['CommonPlugin']->version, '3.11.0') >= 0
            ),
            'PHP version 5.4.0 or greater' => version_compare(PHP_VERSION, '5.4') > 0,
        );
    }

    public function activate()
    {
        parent::activate();

        $depends = require $this->coderoot . 'depends.php';
        $container = new Container($depends);
        $this->dao = $container->get('SegmentPlugin_DAO');
        $this->conditionFactory = $container->get('ConditionFactory');
        $this->logger = $container->get('Logger');
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

        $formFields = isset($messageData['segment']) ? $messageData['segment'] : array();
        $segment = $this->createSegment($messageId, $formFields);
        $this->processSegmentCommands($segment, $formFields);

        $selectPrompt = s('Select ...');
        $params = array();
        $params['condition'] = array();
        $params['selectPrompt'] = $selectPrompt;

        foreach ($segment->conditions() as $i => $c) {
            $s = new stdClass();
            $params['condition'][] = $s;
            $field = $c['field'];

            try {
                $type = $this->conditionFactory->createConditionType($field, $messageData);
            } catch (SegmentPlugin_ConditionException $e) {
                $s->error = s('Unable to create condition - %s', $e->getMessage());
                continue;
            }

            // display field selection drop-down list
            $s->fieldList = $this->fieldDropDownList($field, $i, $selectPrompt);

            // display hidden input to detect when field changes
            $s->hiddenField = CHtml::hiddenField("segment[c][$i][_field]", $field);

            // display operators drop-down list
            $operators = $type->operators();
            $selected = isset($c['op']) ? $c['op'] : key($operators);
            $s->operatorList = CHtml::dropDownList(
                "segment[c][$i][op]",
                $selected,
                $operators,
                array('class' => 'autosubmit')
            );

            // display value
            $value = isset($c['value']) ? $c['value'] : '';
            $s->display = $type->display($selected, $value, "segment[c][$i]");
        }

        // add empty field
        $s = new stdClass();
        $i = count($segment->conditions());
        $s->fieldList = $this->fieldDropDownList('', $i, $selectPrompt);
        $s->hiddenField = CHtml::hiddenField("segment[c][$i][_field]", '');
        $params['condition'][] = $s;

        // display warning if no lists have been selected
        if (!(is_array($messageData['targetlist']) && count($messageData['targetlist']) > 0)) {
            $params['warning'] = s('Please select at least one list on the Lists tab before adding segmentation conditions');
        }

        // display fields for saved segments only where there are some
        $saved = new SegmentPlugin_SavedSegments();
        $savedListData = $saved->selectListData();

        if (count($savedListData) > 0) {
            $params['savedList'] = CHtml::dropDownList(
                'segment[usesaved][]',
                '',
                $savedListData,
                array('multiple' => 'multiple', 'style' => 'width: 50%')
            );
            $params['loadButton'] = CHtml::submitButton(s('Load segments'), array('name' => 'segment[load]'));
            $params['settingsButton'] = new PageLink(
                new PageURL('configure', array(), 'segmentation'),
                'Edit saved segments',
                array('target' => '_blank', 'class' => 'button')
            );
        }

        // display combine drop-down list
        $params['combineList'] = CHtml::dropDownList(
            'segment[combine]',
            $segment->combine(),
            array(SegmentPlugin_Operator::ONE => s('any'), SegmentPlugin_Operator::ALL => s('all'))
        );

        // display calculate button
        $params['calculateButton'] = CHtml::submitButton(s('Calculate'), array('name' => 'segment[calculate]'));

        // display calculated number of subscribers
        if (isset($formFields['calculate'])) {
            try {
                list($params['totalSubscribers'], $params['subscribers']) = $segment->calculateSubscribers(getConfig('segment_subscribers_max'));
                $params['exportCalculatedButton'] = new PageLink(
                    new PageURL('export', array('pi' => 'SegmentPlugin', 'id' => $messageId)),
                    'Export subscribers',
                    array('class' => 'button dialog')
                );
            } catch (SegmentPlugin_ValueException $e) {
                $params['warning'] = s('Invalid value for segment condition');
            } catch (SegmentPlugin_ConditionException $e) {
                $params['warning'] = s('Unable to create condition - %s', $e->getMessage());
            }
        }

        // display remove all, save button and input field only when there is at least one entered condition
        if (count($segment->conditions()) > 0) {
            $params['removeButton'] = CHtml::submitButton(s('Remove all'), array('name' => 'segment[remove]'));
            $params['saveButton'] = CHtml::submitButton(s('Save segment'), array('name' => 'segment[save]'));
            $params['saveName'] = CHtml::textField('segment[savename]', '', array('size' => 25, 'placeholder' => 'Name of segment'));
        }

        // display link to Help page
        $params['help'] = CHtml::tag(
            'a',
            array('href' => self::GUIDANCE, 'target' => '_blank'),
            new ImageTag('info.png', 'Guidance')
        );
        $html = $this->render('sendtab.tpl.php', $params);
        $pagefooter[basename(__FILE__)] = file_get_contents($this->coderoot . 'script.html');
        error_reporting($er);

        return $html;
    }

    /**
     * Validate that conditions are valid for the message to be submitted by calculating the number of subscribers.
     *
     * @param array $messageData
     *
     * @return string empty string for allow, otherwise error text to be displayed
     */
    public function allowMessageToBeQueued($messageData = array())
    {
        if (!isset($messageData['segment']['c'])) {
            return '';
        }
        $segment = $this->createSegment($messageData['id'], $messageData['segment']);

        try {
            $segment->validateSegment();
        } catch (SegmentPlugin_ValueException $e) {
            return s('Invalid value for segment condition');
        } catch (SegmentPlugin_ConditionException $e) {
            return s('Unable to create condition - %s', $e->getMessage());
        }

        return '';
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
        $this->dao->deleteNotSent($id);
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
        $messageId = $messageData['id'];
        $this->selectedSubscribers[$messageId] = null;

        if (isset($messageData['segment']['c'])) {
            $segment = $this->createSegment($messageId, $messageData['segment']);

            try {
                list($total, $this->selectedSubscribers[$messageId]) = $segment->loadSubscribers();
                logEvent(s('Segment plugin selected %d subscribers for campaign %d', $total, $messageId));
            } catch (SegmentPlugin_ValueException $e) {
                logEvent(s('Invalid value for segment condition'));
            } catch (SegmentPlugin_ConditionException $e) {
                logEvent(s('Unable to create condition - %s', $e->getMessage()));
            } catch (SegmentPlugin_NoConditionsException $ex) {
                // do nothing
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
        $messageId = $messageData['id'];
        $userId = (int) $userData['id'];

        return ($this->selectedSubscribers[$messageId] === null)
            ? true
            : (bool) $this->selectedSubscribers[$messageId][$userId];
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
        if (!isset($messageData['segment'])) {
            return false;
        }
        $formFields = $messageData['segment'];

        if (!isset($formFields['c'])) {
            return false;
        }
        $segment = $this->createSegment($messageId, $formFields);
        $conditions = $segment->conditions();

        if (count($conditions) == 0) {
            return false;
        }
        $params = array();
        $params['condition'] = array();

        foreach ($conditions as $i => $c) {
            $s = new stdClass();
            $params['condition'][] = $s;
            $field = $c['field'];

            try {
                $type = $this->conditionFactory->createConditionType($field, $messageData);
            } catch (SegmentPlugin_ConditionException $e) {
                $s->error = sprintf('Unable to create condition - %s', $e->getMessage());
                continue;
            }

            // display field selection
            $fields = $this->conditionFactory->subscriberFields() + $this->conditionFactory->attributeFields();
            $s->field = $fields[$field];

            // display operator
            $operators = $type->operators();
            $op = isset($c['op']) ? $c['op'] : key($operators);
            $s->operator = $operators[$op];

            // display value field
            $value = isset($c['value']) ? $c['value'] : '';
            $s->display = $type->display($op, $value, "segment[c][$i]");
        }

        // display combine
        $combineOps = array(SegmentPlugin_Operator::ONE => s('any'), SegmentPlugin_Operator::ALL => s('all'));
        $params['combine'] = $combineOps[$segment->combine()];
        $html = $this->render('viewmessage.tpl.php', $params);

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

    private function render($template, $params)
    {
        extract($params);
        ob_start();
        require $this->coderoot . $template;

        return ob_get_clean();
    }

    private function fieldDropDownList($field, $seq, $selectPrompt)
    {
        return CHtml::dropDownList(
            "segment[c][$seq][field]",
            $field,
            array('Subscriber Data' => $this->conditionFactory->subscriberFields(), 'Attributes' => $this->conditionFactory->attributeFields()),
            array('prompt' => $selectPrompt, 'class' => 'autosubmit searchable')
        );
    }

    /**
     * Process segment commands that can change the set of conditions.
     *
     * @param Segment $segment
     * @param array   $formFields
     */
    private function processSegmentCommands($segment, $formFields)
    {
        $saved = new SegmentPlugin_SavedSegments();

        if (isset($formFields['remove'])) {
            /*
             *  Remove all conditions
             */
            $segment->removeAll();
        } elseif (isset($formFields['save']) && $formFields['savename'] != '') {
            /*
             *  Save the current set of conditions
             */
            $saved->addSegment($formFields['savename'], $segment->conditions());
        } elseif (isset($formFields['load']) && isset($formFields['usesaved']) && is_array($formFields['usesaved'])) {
            /*
             *  Load the selected saved segments
             */
            try {
                foreach ($formFields['usesaved'] as $savedId) {
                    $savedConditions = $saved->segmentById($savedId);
                    $segment->addConditions($savedConditions);
                }
            } catch (Exception $e) {
                // do nothing
            }
        }
    }

    /**
     * Create a segment from the message form fields.
     *
     * @param int   $messageId
     * @param array $formFields
     *
     * @return Segment
     */
    private function createSegment($messageId, $formFields)
    {
        return new Segment(
            $messageId,
            isset($formFields['c']) ? $formFields['c'] : array(),
            isset($formFields['combine']) ? $formFields['combine'] : SegmentPlugin_Operator::ALL,
            $this->conditionFactory
        );
    }
}
