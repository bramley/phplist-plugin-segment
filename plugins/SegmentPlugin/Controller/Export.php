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

namespace phpList\plugin\SegmentPlugin\Controller;

use phpList\plugin\Common\Controller;
use phpList\plugin\SegmentPlugin\Segment;
use phpList\plugin\SegmentPlugin\SelectedSubscribersExport;

class Export extends Controller
{
    public function __construct($conditionFactory)
    {
        parent::__construct();
        $this->conditionFactory = $conditionFactory;
    }

    public function actionDefault()
    {
        $messageId = $_GET['id'];
        $messageData = loadMessageData($messageId);

        $segment = new Segment(
            $messageId,
            $messageData['segment']['c'],
            $messageData['segment']['combine'],
            $this->conditionFactory
        );
        list($count, $subscribers) = $segment->calculateSubscribers();
        $exportable = new SelectedSubscribersExport($messageId, $subscribers);
        parent::actionExportCSV($exportable);
    }
}
