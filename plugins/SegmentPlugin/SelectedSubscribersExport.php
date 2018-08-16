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
 * @copyright 2014-2018 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

namespace phpList\plugin\SegmentPlugin;

use phpList\plugin\Common\IExportable;

class SelectedSubscribersExport implements IExportable
{
    public function __construct($messageId, $subscribers)
    {
        $this->messageId = $messageId;
        $this->subscribers = $subscribers;
    }

    public function exportFileName()
    {
        return "segment_subscribers_$this->messageId";
    }

    public function exportRows()
    {
        return $this->subscribers;
    }

    public function exportFieldNames()
    {
        return ['email'];
    }

    public function exportValues(array $row)
    {
        return [$row['email']];
    }
}
