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
class SegmentPlugin_ConditionFactory
{
    public function __construct($dao, $i18n)
    {
        $this->dao = $dao;
        $this->i18n = $i18n;
        $daoAttr = new CommonPlugin_DAO_Attribute(new CommonPlugin_DB(), 20, 0);
        $this->attributes = iterator_to_array($daoAttr->attributes());
        $this->attributesById = $daoAttr->attributesById();
    }

    public function createCondition($field)
    {
        if (ctype_digit($field)) {
            $attr = $this->attributesById[$field];

            switch ($attr['type']) {
                case 'select':
                case 'radio':
                    $r = new SegmentPlugin_AttributeConditionSelect($attr, $this->i18n);
                    break;
                case 'checkbox':
                    $r = new SegmentPlugin_AttributeConditionCheckbox($attr, $this->i18n);
                    break;
                case 'checkboxgroup':
                    $r = new SegmentPlugin_AttributeConditionCheckboxgroup($attr, $this->i18n);
                    break;
                case 'textline':
                case 'textarea':
                case 'hidden':
                    $r = new SegmentPlugin_AttributeConditionText($attr, $this->i18n);
                    break;
                case 'date':
                    $r = new SegmentPlugin_AttributeConditionDate($attr, $this->i18n);
                    break;
                default:
                    throw new Exception("unrecognised type {$attr['type']}");
            }
        } else {
            switch ($field) {
                case 'activity':
                    $r = new SegmentPlugin_SubscriberConditionActivity($field, $this->i18n);
                    break;
                case 'entered':
                    $r = new SegmentPlugin_SubscriberConditionEntered($field, $this->i18n);
                    break;
                case 'email':
                    $r = new SegmentPlugin_SubscriberConditionEmail($field, $this->i18n);
                    break;
                case 'id':
                    $r = new SegmentPlugin_SubscriberConditionId($field, $this->i18n);
                    break;
                case 'uniqid':
                    $r = new SegmentPlugin_SubscriberConditionUniqid($field, $this->i18n);
                    break;
                default:
                    throw new Exception("unrecognised field $field");
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
            'activity' => $this->i18n->get('campaign_activity'),
            'entered' => $this->i18n->get('entered_date'),
            'email' => $this->i18n->get('email_address'),
            'id' => $this->i18n->get('subscriber_id'),
            'uniqid' => $this->i18n->get('subscriber_unique_id'),
        );
    }
}
