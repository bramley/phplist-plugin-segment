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
class SegmentPlugin_ConditionFactory
{
    private $attributes;
    private $dao;

    /**
     * @param SegmentPlugin_DAO $dao
     * @param array             $attributes
     */
    public function __construct($dao, $attributes)
    {
        $this->dao = $dao;
        $this->attributes = $attributes;
    }

    /**
     * Create a condition type object using a subscriber field or attribute.
     * A field is treated as an attribute id if it is numeric, otherwise as a subscriber field.
     *
     * @param string $field attribute id or subscriber field
     *
     * @return SegmentPlugin_Condition
     */
    public function createConditionType($field, $messageData)
    {
        if (ctype_digit($field)) {
            if (!isset($this->attributes[$field])) {
                throw new SegmentPlugin_ConditionException("attribute id $field does not exist");
            }
            $attr = $this->attributes[$field];

            switch ($attr['type']) {
                case 'select':
                case 'radio':
                    $r = new SegmentPlugin_AttributeConditionSelect($attr);
                    break;
                case 'checkbox':
                    $r = new SegmentPlugin_AttributeConditionCheckbox($attr);
                    break;
                case 'checkboxgroup':
                    $r = new SegmentPlugin_AttributeConditionCheckboxgroup($attr);
                    break;
                case 'textline':
                case 'textarea':
                case 'hidden':
                    $r = new SegmentPlugin_AttributeConditionText($attr);
                    break;
                case 'date':
                    $r = new SegmentPlugin_AttributeConditionDate($attr);
                    break;
                default:
                    throw new SegmentPlugin_ConditionException("unrecognised attribute type {$attr['type']}");
            }
        } else {
            switch ($field) {
                case 'activity':
                    $r = new SegmentPlugin_SubscriberConditionActivity($field);
                    break;
                case 'entered':
                    $r = new SegmentPlugin_SubscriberConditionEntered($field);
                    break;
                case 'listentered':
                    $r = new SegmentPlugin_SubscriberConditionListEntered($field);
                    break;
                case 'email':
                    $r = new SegmentPlugin_SubscriberConditionEmail($field);
                    break;
                case 'id':
                    $r = new SegmentPlugin_SubscriberConditionIdentity($field);
                    break;
                case 'uniqid':
                    $r = new SegmentPlugin_SubscriberConditionIdentity($field);
                    break;
                case 'lists':
                    $r = new SegmentPlugin_SubscriberConditionLists($field);
                    break;
                default:
                    throw new SegmentPlugin_ConditionException("unrecognised subscriber field $field");
            }
        }
        $r->dao = $this->dao;
        $r->messageData = $messageData;

        return $r;
    }

    /**
     * Returns an array of attribute names indexed by attribute id.
     *
     * @return array
     */
    public function attributeFields()
    {
        return array_column($this->attributes, 'name', 'id');
    }

    /**
     * Returns an array of descriptive subscriber fields.
     *
     * @return array
     */
    public function subscriberFields()
    {
        return array(
            'email' => 'Email address',
            'id' => 'Subscriber id',
            'uniqid' => 'Subscriber unique id',
            'entered' => 'Date signed-up to phpList',
            'listentered' => 'Date subscribed to list',
            'lists' => 'List membership',
            'activity' => 'Campaign activity',
        );
    }
}
