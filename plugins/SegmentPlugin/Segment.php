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
 * @copyright 2018 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

namespace phpList\plugin\SegmentPlugin;

use chdemko\BitArray\BitArray;
use phpList\plugin\Common\DB;
use phpList\plugin\Common\Logger;
use phpList\plugin\Common\StringCallback;
use SegmentPlugin_DAO;
use SegmentPlugin_NoConditionsException;

class Segment
{
    private $changed = false;
    private $combine;
    private $conditions;
    private $messageId;
    private $logger;
    private $dao;
    private $conditionFactory;

    /**
     * Constructor.
     *
     * @param int                            $messageId
     * @param array                          $conditions
     * @param int                            $combine
     * @param SegmentPlugin_ConditionFactory $conditionFactory
     */
    public function __construct($messageId, $conditions, $combine, $conditionFactory)
    {
        $this->messageId = $messageId;
        $this->conditions = $conditions;
        $this->filterEmptyFields();
        $this->resetChangedFields();
        $this->combine = $combine;
        $this->conditionFactory = $conditionFactory;

        $db = new DB();
        $this->dao = new SegmentPlugin_DAO($db);
        $this->logger = Logger::instance();
    }

    /**
     * Destructor.
     *
     * Save the segment fields if they have changed by adding or removing conditions.
     */
    public function __destruct()
    {
        if ($this->changed) {
            setMessageData($this->messageId, 'segment', ['c' => $this->conditions, 'combine' => $this->combine]);
            $this->changed = false;
        }
    }

    /**
     * Return the conditions.
     */
    public function conditions()
    {
        return $this->conditions;
    }

    /**
     * Return the combine field.
     */
    public function combine()
    {
        return $this->combine;
    }

    /**
     * Add extra conditions.
     *
     * @param array $extraConditions
     */
    public function addConditions($extraConditions)
    {
        $this->conditions = $this->array_unique(array_merge($this->conditions, $extraConditions));
        $this->changed = true;
    }

    /**
     * Remove all conditions.
     */
    public function removeAll()
    {
        $this->conditions = [];
        $this->changed = true;
    }

    /**
     * Validate that all conditions are valid by building the joins.
     * Exceptions can be thrown by the called methods.
     */
    public function validateSegment()
    {
        $this->filterIncompleteConditions();
        $joins = $this->selectionQueryJoins();
    }

    /**
     * Load all the subscribers who are to receive the campaign.
     *
     * @throws SegmentPlugin_NoConditionsException if there are not any conditions
     *
     * @return array [int, BitArray]
     */
    public function loadSubscribers()
    {
        $this->filterIncompleteConditions();

        if (count($this->conditions) == 0) {
            throw new SegmentPlugin_NoConditionsException();
        }
        $highest = $this->dao->highestSubscriberId();
        $subscribers = BitArray::fromInteger($highest + 1);
        $total = 0;
        $joins = $this->selectionQueryJoins();

        if (count($joins) > 0) {
            $subscriberIterator = $this->dao->subscribers($this->messageId, $joins, $this->combine);
            $total = count($subscriberIterator);

            foreach ($subscriberIterator as $row) {
                $subscribers[(int) $row['id']] = 1;
            }
        }

        return [$total, $subscribers];
    }

    /**
     * Query for the number of subscribers and their email addresses.
     *
     * @param int $limit
     *
     * @return array [0] int      number of subscribers
     *               [1] Iterator subscriber email addresses
     */
    public function calculateSubscribers($limit = 0)
    {
        $this->logger->debug(new StringCallback(function () {
            return sprintf(
                "Prior usage %s\nPrior peak usage %s\nPrior peak real usage %s",
                memory_get_usage(), memory_get_peak_usage(), memory_get_peak_usage(true)
            );
        }));
        $this->filterIncompleteConditions();
        $joins = $this->selectionQueryJoins();
        list($count, $subscribers) = $this->dao->calculateSubscribers($this->messageId, $joins, $this->combine, $limit);
        $this->logger->debug(new StringCallback(function () {
            return sprintf(
                "Post usage %s\nPost peak usage %s\nPost peak real usage %s",
                memory_get_usage(), memory_get_peak_usage(), memory_get_peak_usage(true)
            );
        }));

        return [$count, $subscribers];
    }

    /**
     * Remove any conditions that have an empty field.
     */
    private function filterEmptyFields()
    {
        $this->conditions = array_values(
            array_filter(
                $this->conditions,
                function ($c) {
                    return $c['field'] != '';
                }
            )
        );
    }

    /**
     * When a field has been changed unset the operator and value.
     */
    private function resetChangedFields()
    {
        $this->conditions = array_map(
            function ($c) {
                if ($c['field'] != $c['_field']) {
                    unset($c['op']);
                    unset($c['value']);
                }

                return $c;
            },
            $this->conditions
        );
    }

    /**
     * Remove conditions that do not have an operator.
     */
    private function filterIncompleteConditions()
    {
        $this->conditions = array_filter(
            $this->conditions,
            function ($c) {
                if (empty($c['op'])) {
                    $this->logger->debug(sprintf('Condition without an operator %s', print_r($c, true)));
                }

                return isset($c['op']);
            }
        );
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
     * Create the join and where clauses for each condition.
     *
     * @return array
     */
    private function selectionQueryJoins()
    {
        $joins = [];

        foreach ($this->conditions as $i => $c) {
            $field = $c['field'];
            $type = $this->conditionFactory->createConditionType($field, loadMessageData($this->messageId));
            $joins[] = $type->joinQuery($c['op'], isset($c['value']) ? $c['value'] : '');
        }

        return $joins;
    }
}
