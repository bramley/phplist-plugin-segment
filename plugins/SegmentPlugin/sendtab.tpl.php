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
 * SegmentPlugin is distributed in the hope that it will be useful,
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
 * Plugin class
 * 
 * @category  phplist
 * @package   SegmentPlugin
 */
?>
<?php echo file_get_contents($this->coderoot . 'styles.css'); ?>

<div class="segment">
    <div class="instructions">
        <?php echo s($this->i18n->get('instructions'), $selectPrompt); ?>
    </div>
    <div class="segment-help">
        <a href="https://resources.phplist.com/plugin/segment#add_segment_conditions" target="_blank">
            <?php echo s($this->i18n->get('usage_guidance')); ?>
        </a>
    </div>    
    <div class="bold"><?php echo s($this->i18n->get('match_%s_criteria'), $combineList); ?></div>
    <ul>
    <?php foreach ($condition as $c) : ?>
        <li class="selfclear">
        <?php if (isset($c->caption)): ?><div ><b><?php echo $c->caption; ?></b></div><?php endif; ?>
        <div class="segment-block"><?php echo $c->fieldList, $c->hiddenField; ?></div>
        <div class="segment-block"><?php if (isset($c->operatorList)) {
    echo $c->operatorList;
} ?></div>
        <div class="segment-block"><?php if (isset($c->display)) {
    echo $c->display;
} ?></div>
        </li>
    <?php endforeach; ?>
    </ul>
    <div class="segment-subheading"><?php echo s($this->i18n->get('use_saved_segment')); ?></div>
    <?php echo $savedList; ?>
    <div id="recalculate">
        <?php echo $calculateButton ?>
        <?php if (isset($totalSubscribers)) {
    echo s($this->i18n->get('%d_subscribers_selected'), $totalSubscribers);
} ?>
        <?php if (isset($warning)): ?> <span class="error"><?php echo $warning;?></span><?php endif; ?>
    </div>
    <?php if (isset($saveButton)): ?>
    <div class="segment-subheading"><?php echo s($this->i18n->get('save_current_segment')); ?> </div>
    <div class="segment-block">
        <?php echo $saveName; ?>
    </div>
    <div class="segment-block">
        <?php echo $saveButton; ?>
    </div>
    <?php endif; ?>
    <div class="segment-block">
        <div class="edit-segments"><?php echo $settings; ?></div>
    </div>
</div>
