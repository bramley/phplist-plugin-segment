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
        $cf = new SegmentPlugin_ConditionFactory($this->dao, $this->i18n);
        $joins = array();

        foreach ($conditions as $i => $c) {
            $field = $c['field'];
            $condition = $cf->createCondition($field);

            try {
                $joins[] = $condition->joinQuery($c['op'], isset($c['value']) ? $c['value'] : '');
            } catch (SegmentPlugin_ValueException $e) {
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

    private function render($params)
    {
        extract($params);
        ob_start();
        require($this->coderoot . 'view.tpl.php');
        return ob_get_clean();
    }

/*
 *  Public methods
 */
    public function __construct()
    {
        global $plugins;
        $this->coderoot = dirname(__FILE__) . '/' . __CLASS__ . '/';
        require_once $plugins['CommonPlugin']->coderoot . 'Autoloader.php';
        $this->i18n = new CommonPlugin_I18N($this);
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        $this->settings = array(
            'segment_campaign_max' => array (
              'description' => s($this->i18n->get('max_number_campaigns')),
              'type' => 'integer',
              'value' => 10,
              'allowempty' => 0,
              'min' => 4,
              'max' => 25,
              'category'=> 'Segmentation',
            )
        );
        parent::__construct();
    }

    public function dependencyCheck()
    {
        global $plugins;

        return array(
            'Common plugin installed' =>
                phpListPlugin::isEnabled('CommonPlugin') && 
                (strpos($plugins['CommonPlugin']->version, 'Git') === 0 || $plugins['CommonPlugin']->version >= '2015-03-23'),
            'PHP version 5.3.0 or greater' => version_compare(PHP_VERSION, '5.3') > 0,
        );
    }

/*
 *  Use this method as a hook to create the dao
 *  Need to create autoloader because of the unpredictable order in which plugins are called
 * 
 */
    public function sendFormats()
    {
        $this->dao = new SegmentPlugin_DAO(new CommonPlugin_DB());
        $this->logger = CommonPlugin_Logger::instance();
        return null;
    }

    public function adminmenu()
    {
        return array();
    }

    public function sendMessageTab($messageId = 0 , $messageData = array())
    {
        $er = error_reporting(-1);
        global $plugins, $pagefooter;

        if (!phplistPlugin::isEnabled('CommonPlugin')) {
            return s($this->i18n->get('commonplugin_required'));
        }
        $cf = new SegmentPlugin_ConditionFactory($this->dao, $this->i18n);

        $conditions = (isset($messageData['segment']['c']))
            ? array_values($this->filterEmptyFields($messageData['segment']['c']))
            : array();

        $conditions[] = array('field' => '');
        $selectPrompt = s($this->i18n->get('select_prompt'));
        $params = array();
        $params['condition'] = array();
        $params['selectPrompt'] = $selectPrompt;

        foreach ($conditions as $i => $c) {
            $s = new stdClass;
            $s->fieldList = CHtml::dropDownList(
                "segment[c][$i][field]",
                $c['field'],
                array($this->i18n->get('subscriber_data') => $cf->subscriberFields(), $this->i18n->get('attributes') => $cf->attributeFields()),
                array('prompt' => $selectPrompt, 'onchange' => 'this.form.submit()')
            );
            // hidden input to detect when field changes
            $s->hiddenField = CHtml::hiddenField("segment[c][$i][_field]", $c['field']);
            $field = $c['field'];

            if ($field != '') {
                $condition = $cf->createCondition($field);
                $condition->messageData = $messageData;
                $operators = $condition->operators();

                $op = ($field == $c['_field'] && isset($c['op'])) ? $c['op'] : key($operators);
                $s->operatorList = CHtml::dropDownList(
                    "segment[c][$i][op]",
                    $op,
                    $operators
                );

                $value = ($field == $c['_field'] && isset($c['value'])) ? $c['value'] : '';
                $s->display = $condition->display($op, $value, "segment[c][$i]");
            } else {
                $s->operatorList = '';
                $s->display = '';
            }
            $params['condition'][] = $s;
        }

        $params['calculateButton'] = CHtml::submitButton(s($this->i18n->get('calculate')), array('name' => 'segment[calculate]'));
        $combine = isset($messageData['segment']['combine']) 
            ? $messageData['segment']['combine'] : SegmentPlugin_Operator::ALL;
        $params['combineList'] = CHtml::dropDownList(
            "segment[combine]",
            $combine,
            array(SegmentPlugin_Operator::ONE => s($this->i18n->get('any')), SegmentPlugin_Operator::ALL => s($this->i18n->get('all')))
        );

        if (isset($messageData['segment']['calculate'])) {
            $params['totalSubscribers'] = $this->calculateSubscribers(
                $messageId,
                $this->filterIncompleteConditions($messageData['segment']['c']),
                $combine
            );
        }
        $html = $this->render($params);
        $pagefooter[basename(__FILE__)] = file_get_contents($this->coderoot . 'date.js');
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
