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
    public function __construct($dao)
    {
        $this->dao = $dao;
        $daoAttr = new CommonPlugin_DAO_Attribute(new CommonPlugin_DB(), 20, 0);
        $this->attributes = iterator_to_array($daoAttr->attributes());
        $this->attributesById = $daoAttr->attributesById();
    }

    public function createCondition($field)
    {
        if (ctype_digit($field)) {
            if (!isset($this->attributesById[$field])) {
                throw new SegmentPlugin_ConditionException("attribute id $field does not exist");
            }
            $attr = $this->attributesById[$field];

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
                case 'email':
                    $r = new SegmentPlugin_SubscriberConditionEmail($field);
                    break;
                case 'id':
                    $r = new SegmentPlugin_SubscriberConditionIdentity($field);
                    break;
                case 'uniqid':
                    $r = new SegmentPlugin_SubscriberConditionIdentity($field);
                    break;
                default:
                    throw new SegmentPlugin_ConditionException("unrecognised subscriber field $field");
            }
        }
        $r->dao = $this->dao;

        return $r;
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
            'id' => 'subscriber id',
            'uniqid' => 'subscriber unique id',
        );
    }
}
