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
 * @copyright 2014-2015 Duncan Cameron
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
    private $dao;

/*
 *  Inherited variables
 */
    public $name = "Segmentation";
    public $authors = 'Duncan Cameron';
    public $description = 'Send to a subset of subscribers using custom conditions';
    public $documentationUrl = 'https://resources.phplist.com/plugin/segment';
    public $settings;

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
            } catch (SegmentPlugin_ValueException $e) {
                // do nothing
            } catch (SegmentPlugin_ConditionException $e) {
                // do nothing
            }
        }
        return $joins;
    }

    private function loadSubscribers($messageId, array $conditions, $combine)
    {
        $joins = $this->selectionQueryJoins($conditions);
        $this->selectedSubscribers = array();

        if (count($joins) > 0) {
            foreach ($this->dao->subscribers($messageId, $joins, $combine) as $row) {
                $this->selectedSubscribers[$row['id']] = 1;
            }
        }
    }

    private function calculateSubscribers($messageId, array $conditions, $combine)
    {
        $this->logger->debug(sprintf(
            "Prior usage %s\nPrior peak usage %s\nPrior peak real usage %s",
            memory_get_usage(), memory_get_peak_usage(), memory_get_peak_usage(true)
        ));
        $joins = $this->selectionQueryJoins($conditions);

        $count = (count($joins) > 0)
            ? $this->dao->calculateSubscribers($messageId, $joins, $combine)
            : 0;
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
        require($this->coderoot . $template);
        return ob_get_clean();
    }

    /**
     * Constructor
     *
     * @access  public
     */
    public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/' . __CLASS__ . '/';
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        $this->settings = array(
            'segment_campaign_max' => array (
              'description' => s('The maximum number of earlier campaigns to select from'),
              'type' => 'integer',
              'value' => 10,
              'allowempty' => 0,
              'min' => 4,
              'max' => 25,
              'category'=> 'Segmentation',
            ),
            'segment_saved_summary' => array (
              'description' => s('Summary of saved segments'),
              'type' => 'textarea',
              'value' => '',
              'allowempty' => true,
              'category'=> 'Segmentation',
            ),
        );
        parent::__construct();
    }

    /**
     * Provide the dependencies for enabling this plugin
     *
     * @access  public
     * @return  array
     */
    public function dependencyCheck()
    {
        global $plugins;

        return array(
            'Common plugin v3 installed' =>
                phpListPlugin::isEnabled('CommonPlugin')
                    && preg_match('/\d+\.\d+\.\d+/', $plugins['CommonPlugin']->version, $matches)
                    && version_compare($matches[0], '3') > 0,
            'PHP version 5.3.0 or greater' => version_compare(PHP_VERSION, '5.3') > 0,
        );
    }

    /**
     * Use this method as a hook to create the dao
     * Need to create autoloader because of the unpredictable order in which plugins are called
     *
     * @access  public
     * @return  null
     */
    public function sendFormats()
    {
        global $plugins;

        require_once $plugins['CommonPlugin']->coderoot . 'Autoloader.php';
        $this->dao = new SegmentPlugin_DAO(new CommonPlugin_DB());
        $this->logger = CommonPlugin_Logger::instance();
        return null;
    }

    /**
     * List of items to add to menu
     *
     * @access  public
     * @return  array
     */
    public function adminmenu()
    {
        return array();
    }

    /**
     * Build contents of additional tab
     *
     * @access  public
     * @param   int  $messageId 
     * @param   array  $messageData 
     * @return  string the html for the tab
     */
    public function sendMessageTab($messageId = 0, $messageData = array())
    {
        $er = error_reporting(-1);
        global $plugins, $pagefooter;

        if (!phplistPlugin::isEnabled('CommonPlugin')) {
            return s('CommonPlugin must be installed in order to use segments');
        }
        $segment = isset($messageData['segment']) ? $messageData['segment'] : array();
        $conditions = (isset($segment['c']))
            ? array_values($this->filterEmptyFields($segment['c']))
            : array();

        $combine = isset($segment['combine']) 
            ? $segment['combine'] : SegmentPlugin_Operator::ALL;

        $saved = new SegmentPlugin_SavedSegments;

        if (isset($segment['save']) && $segment['savename'] != '') {
            $saved->addSegment($segment['savename'], $combine, $this->filterIncompleteConditions($conditions));
            $segment['savename'] = '';
            setMessageData($messageId, 'segment', $segment);
        }

        if (isset($segment['usesaved']) && $segment['usesaved'] !== '') {
            try {
                list($combine, $conditions) = $saved->segmentById($segment['usesaved']);
            } catch (Exception $e) {
                // do nothing
            }
        }
        $conditions[] = array('field' => '');
        $selectPrompt = s('Select ...');
        $params = array();
        $params['condition'] = array();
        $params['selectPrompt'] = $selectPrompt;
        $cf = new SegmentPlugin_ConditionFactory($this->dao);

        foreach ($conditions as $i => $c) {
            $s = new stdClass;
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
                echo $e->getMessage();
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

        // display drop-down list of saved segments
        $params['savedList'] = CHtml::dropDownList(
            "segment[usesaved]",
            '',
            $saved->selectListData(),
            array('prompt' => $selectPrompt, 'onchange' => 'this.form.submit()')
        );

        // display calculate button
        $params['calculateButton'] = CHtml::submitButton(s('Calculate'), array('name' => 'segment[calculate]'));

        // display combine drop-down list
        $params['combineList'] = CHtml::dropDownList(
            "segment[combine]",
            $combine,
            array(SegmentPlugin_Operator::ONE => s('any'), SegmentPlugin_Operator::ALL => s('all'))
        );

        // display calculated number of subscribers
        if (isset($segment['calculate'])) {
            $params['totalSubscribers'] = $this->calculateSubscribers(
                $messageId,
                $this->filterIncompleteConditions($segment['c']),
                $combine
            );
        }

        // display save button and input field
        if (count($conditions) > 1) {
            $params['saveButton'] = CHtml::submitButton(s('Save segment'), array('name' => 'segment[save]'));
            $params['saveName'] = CHtml::textField("segment[savename]", '', array('size' => 20));
        }

        // display link to Settings page
        $params['settings'] = new CommonPlugin_PageLink(
            new CommonPlugin_PageURL('configure', array(), 'segmentation'),
            'Edit saved segments',
            array('target' => '_blank')
        );
        $html = $this->render('sendtab.tpl.php', $params);
        $pagefooter[basename(__FILE__)] = file_get_contents($this->coderoot . 'date.js');
        error_reporting($er);
        return $html;
    }

    /**
     * The title of the additional tab
     *
     * @access  public
     * @param   int  $messageId 
     * @return  string the title
     */
    public function sendMessageTabTitle($messageid = 0)
    {
        return s('Segment');
    }

    /**
     * Use this hook to delete the 'not sent' rows from the usermessage table 
     * so that they will be re-evaluated
     *
     * @access  public
     * @param   int  $id the message id
     * @return  none
     */
    public function messageQueued($id)
    {
        $this->deleteNotSent($id);
    }

    /**
     * The same processing as when queueing a message
     *
     * @access  public
     * @param   int  $id the message id
     * @return  none
     */
    public function messageReQueued($id)
    {
        $this->messageQueued($id);
    }

    /**
     * Use this hook to select the subscribers who meet the segment conditions 
     * $noConditions indicates whether there are any conditions
     * $selectedSubscribers contains the selected subscribers
     *
     * @access  public
     * @param   array  $messageData the message data
     * @return  none
     */
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

    /**
     * Determine whether the campaign should be sent to a specific user
     *
     * @access  public
     * @param   array  $messageData the message data
     * @param   array  $userData the user data
     * @return  boolean
     */
    public function canSend($messageData, $userData)
    {
        if ($this->noConditions) {
            return true;
        }

        return isset($this->selectedSubscribers[$userData['id']]);
    }

    /**
     * Build the html to be added to the view message page
     *
     * @access  public
     * @param   int  $messageId the message id
     * @param   array  $messageData the message data
     * @return  string the html to be added
     */
    public function viewMessage($messageId, $messageData)
    {
        global $plugins;

        $er = error_reporting(-1);

        if (!phplistPlugin::isEnabled('CommonPlugin')) {
            return s('CommonPlugin must be installed in order to use segments');
        }

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
            $s = new stdClass;
            $params['condition'][] = $s;
            $field = $c['field'];

            // display field selection
            $fields = $cf->subscriberFields() + $cf->attributeFields();
            $s->field = $fields[$field];

            try {
                $condition = $cf->createCondition($field);
            } catch (SegmentPlugin_ConditionException $e) {
                echo $e->getMessage();
                continue;
            }
            $condition->messageData = $messageData;

            // display operator
            $operators = $condition->operators();
            $s->operator = $operators[$c['op']];

            // display value field
            $value = isset($c['value']) ? $c['value'] : '';
            $s->display = $condition->display($c['op'], $value, "segment[c][$i]");
        }

        // display combine
        $combineOps = array(SegmentPlugin_Operator::ONE => s('any'), SegmentPlugin_Operator::ALL => s('all'));
        $params['combine'] = $combineOps[$combine];

        $html = $this->render('viewmessage.tpl.php', $params);
        error_reporting($er);
        return array('Segment conditions', $html);
    }
 }
