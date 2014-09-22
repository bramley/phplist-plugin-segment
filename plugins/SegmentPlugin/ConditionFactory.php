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
 * @copyright 2014 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

/**
 * 
 * 
 * @category  phplist
 * @package   SegmentPlugin
 */
class SegmentPlugin_ConditionFactory
{
    public function __construct()
    {
        $dao = new CommonPlugin_DAO_Attribute(new CommonPlugin_DB());
        $this->attributes = iterator_to_array($dao->attributes());
        $this->attributesById = $dao->attributesById();
    }

    public function createCondition($field)
    {
        if (ctype_digit($field)) {
            $attr = $this->attributesById[$field];

            switch ($attr['type']) {
                case 'select':
                case 'radio':
                    return new SegmentPlugin_AttributeConditionSelect($attr);
                    break;
                case 'checkbox':
                    return new SegmentPlugin_AttributeConditionCheckbox($attr);
                    break;
                case 'textline':
                case 'textarea':
                case 'hidden':
                    return new SegmentPlugin_AttributeConditionText($attr);
                    break;
                case 'date':
                    return new SegmentPlugin_AttributeConditionDate($attr);
                    break;
                default:
                    throw new Exception("unrecognised type {$attr['type']}");
            }
        } else {
            switch ($field) {
                case 'activity':
                    return new SegmentPlugin_SubscriberConditionActivity($field);
                    break;
                case 'entered':
                    return new SegmentPlugin_SubscriberConditionEntered($field);
                    break;
                case 'email':
                    return new SegmentPlugin_SubscriberConditionEmail($field);
                    break;
                default:
                    throw new Exception("unrecognised field $field");
            }
        }
    }

    public function attributeFields()
    {
        return array_column($this->attributes, 'name', 'id');
    }

    public function subscriberFields()
    {
        return array(
            'activity' => 'Campaign activity',
            'entered' => 'Entered date',
            'email' => 'email address',
        );
    }
}
