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
 * @copyright 2014-2016 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

/**
 * @category  phplist
 */
class SegmentPlugin_Operator
{
    const IS = 1;
    const ISNOT = 2;
    const MATCHES = 3;
    const NOTMATCHES = 4;
    const REGEXP = 5;
    const NOTREGEXP = 6;
    const BLANK = 7;
    const NOTBLANK = 8;
    const BEFORE = 9;
    const AFTER = 10;
    const OPENED = 11;
    const NOTOPENED = 12;
    const ONE = 13;
    const ALL = 14;
    const NONE = 15;
    const SENT = 16;
    const NOTSENT = 17;
    const CLICKED = 18;
    const NOTCLICKED = 19;
    const BETWEEN = 20;
    const AFTERINTERVAL = 21;
    const ISINCLUDED = 22;
    const ANNIVERSARY = 23;

    private function __construct()
    {
    }
}
