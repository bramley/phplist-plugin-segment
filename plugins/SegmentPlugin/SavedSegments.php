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

class SegmentPlugin_SavedSegments
{
    private $segments = array();
    private $summary = array();

    /**
     * Synchronise the summary and saved segments so that they contain the same entries
     *
     * @access  private
     */
    private function synchronise()
    {
        // remove from summary any entries that do not exist in saved segments
        $names = array_column($this->segments, 'name', 'name');
        $this->summary = array_values(
            array_filter(
                $this->summary,
                function($value) use($names) {
                    return isset($names[$value]);
                }
            )
        );

        // remove from saved segments any entries that do not exist in summary
        $newSegments = array();

        foreach ($this->segments as $k => $v) {
            $pos = array_search($v['name'], $this->summary, true);

            if ($pos !== false) {
                $newSegments[$pos] = $v;
            }
        }
        $this->segments = $newSegments;
        SaveConfig('segment_saved', serialize($this->segments));
        SaveConfig('segment_saved_summary', $this->stringify());
    }

    /**
     * Convert the summary array to text for displaying on the Settings page
     *
     * @access  private
     * @return  string  the summary array converted to a string
     */
    private function stringify()
    {
        return implode("\n", $this->summary);
    }

    /**
     * Convert the summary text to an array by splitting into an array of lines
     *
     * @access  private
     * @param   string  $summary 
     * @return  string  the summary array converted to a string
     */
    private function unstringify($summary)
    {
        return preg_split("/[\r\n|\r|\n]+/", $summary);
    }

    /**
     * Constructor
     *
     * @access  public
     */
    public function __construct()
    {
        $summary = getConfig('segment_saved_summary');
        $saved = getConfig('segment_saved');

        if ($summary) {
            $this->summary = $this->unstringify($summary);
        }

        if ($saved) {
            $this->segments = unserialize($saved);
            $this->synchronise();
        }
    }

    /**
     * Add a segment to the summary and saved segments
     * If the segment name already exists then the current segment is replaced
     *
     * @access  public
     * @param   string  $name the segment name 
     * @param   string  $combine the segment combine operator 
     * @param   array  $conditions array of conditions
     */
    public function addSegment($name, $combine, array $conditions)
    {

        $position = array_search($name, $this->summary, true);

        if ($position === false) {
            $position = count($this->summary);
        }
        $this->segments[$position] = array(
            'name' => $name,
            'combine' => $combine,
            'conditions' => $conditions
        );
        $this->summary[$position] = $name;

        SaveConfig('segment_saved', serialize($this->segments));
        SaveConfig('segment_saved_summary', $this->stringify());
    }

    /**
     * Return a saved segment
     *
     * @access  public
     * @param   int  $id the segment id 
     * @return  array the segment's combine operator and conditions
     */
    public function segmentById($id)
    {
        if (!isset($this->segments[$id])) {
            throw new Exception("Invalid segment id $id");
        }
        return array($this->segments[$id]['combine'], $this->segments[$id]['conditions']);
    }

    /**
     * Provides the data to populate a select list
     *
     * @access  public
     * @return  array data for select list options - value => display
     */
    public function selectListData()
    {
        return $this->summary;
    }
}
