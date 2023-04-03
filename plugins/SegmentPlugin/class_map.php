<?php

$pluginsDir = dirname(__DIR__);

return [
    'SegmentPlugin_AttributeConditionCheckbox' => $pluginsDir . '/SegmentPlugin/AttributeConditionCheckbox.php',
    'SegmentPlugin_AttributeConditionCheckboxgroup' => $pluginsDir . '/SegmentPlugin/AttributeConditionCheckboxgroup.php',
    'SegmentPlugin_AttributeConditionDate' => $pluginsDir . '/SegmentPlugin/AttributeConditionDate.php',
    'SegmentPlugin_AttributeConditionSelect' => $pluginsDir . '/SegmentPlugin/AttributeConditionSelect.php',
    'SegmentPlugin_AttributeConditionText' => $pluginsDir . '/SegmentPlugin/AttributeConditionText.php',
    'SegmentPlugin_Condition' => $pluginsDir . '/SegmentPlugin/Condition.php',
    'SegmentPlugin_ConditionException' => $pluginsDir . '/SegmentPlugin/ConditionException.php',
    'SegmentPlugin_ConditionFactory' => $pluginsDir . '/SegmentPlugin/ConditionFactory.php',
    'SegmentPlugin_DAO' => $pluginsDir . '/SegmentPlugin/DAO.php',
    'SegmentPlugin_DateConditionBase' => $pluginsDir . '/SegmentPlugin/DateConditionBase.php',
    'SegmentPlugin_NoConditionsException' => $pluginsDir . '/SegmentPlugin/NoConditionsException.php',
    'SegmentPlugin_Operator' => $pluginsDir . '/SegmentPlugin/Operator.php',
    'SegmentPlugin_SavedSegments' => $pluginsDir . '/SegmentPlugin/SavedSegments.php',
    'SegmentPlugin_SubscriberConditionActivity' => $pluginsDir . '/SegmentPlugin/SubscriberConditionActivity.php',
    'SegmentPlugin_SubscriberConditionEmail' => $pluginsDir . '/SegmentPlugin/SubscriberConditionEmail.php',
    'SegmentPlugin_SubscriberConditionEntered' => $pluginsDir . '/SegmentPlugin/SubscriberConditionEntered.php',
    'SegmentPlugin_SubscriberConditionIdentity' => $pluginsDir . '/SegmentPlugin/SubscriberConditionIdentity.php',
    'SegmentPlugin_SubscriberConditionListEntered' => $pluginsDir . '/SegmentPlugin/SubscriberConditionListEntered.php',
    'SegmentPlugin_SubscriberConditionLists' => $pluginsDir . '/SegmentPlugin/SubscriberConditionLists.php',
    'SegmentPlugin_ValueException' => $pluginsDir . '/SegmentPlugin/ValueException.php',
    'phpList\plugin\SegmentPlugin\ControllerFactory' => $pluginsDir . '/SegmentPlugin/ControllerFactory.php',
    'phpList\plugin\SegmentPlugin\Controller\Export' => $pluginsDir . '/SegmentPlugin/Controller/Export.php',
    'phpList\plugin\SegmentPlugin\Segment' => $pluginsDir . '/SegmentPlugin/Segment.php',
    'phpList\plugin\SegmentPlugin\SelectedSubscribersExport' => $pluginsDir . '/SegmentPlugin/SelectedSubscribersExport.php',
];
