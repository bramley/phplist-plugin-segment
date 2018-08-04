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

class Segment
{
    private $changed = false;
    private $combine;
    private $conditions;
    private $messageId;

    /**
     * Constructor.
     *
     * @param int   $messageId
     * @param array $conditions
     * @param int   $combine
     */
    public function __construct($messageId, $conditions, $combine)
    {
        $this->messageId = $messageId;
        $this->conditions = $this->filterEmptyFields($conditions);
        $this->combine = $combine;
    }

    /**
     * Destructor.
     *
     * Save the segment fields if they have changed by adding or removing conditions.
     */
    public function __destruct()
    {
        if ($this->changed) {
            $this->save();
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
     * Remove conditions that do not have an operator.
     */
    public function filterIncompleteConditions()
    {
        return array_filter(
            $this->conditions,
            function ($c) {
                return isset($c['op']);
            }
        );
    }

    /**
     * Saves the segment as a message data field.
     */
    private function save()
    {
        setMessageData($this->messageId, 'segment', ['c' => $this->conditions, 'combine' => $this->combine]);
    }

    /**
     * Return only those conditions that have a field.
     * Remove conditions that have field value of 0, due to being removed in the UI.
     * Remove the condition with an empty field.
     */
    private function filterEmptyFields($conditions)
    {
        return array_values(
            array_filter(
                $conditions,
                function ($c) {
                    if ($c['field'] === 0) {
                        $this->changed = true;

                        return false;
                    }

                    return $c['field'] !== '';
                }
            )
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
}
