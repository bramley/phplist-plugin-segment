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
 * SegmentPlugin is distributed in the hope that it will be useful,
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
 * Plugin class.
 *
 * @category  phplist
 */
?>
<?php echo file_get_contents($this->coderoot . 'styles.html'); ?>

<div class="segment">
    <div>
<?php
    echo s("Select one or more subscriber fields or attributes.
The campaign will be sent only to those subscribers who match any or all of the conditions.
To remove a condition, choose '%s' from the drop-down list.", $selectPrompt);
?>
    </div>
    <div><?php echo s('Subscribers match %s of the following:', $combineList); ?></div>
    <ul>
<?php foreach ($condition as $c) : ?>
        <li class="selfclear">
    <?php if (isset($c->error)): ?>
            <div class="note"><?php echo $c->error; ?></div>
    <?php else: ?>
        <?php if (isset($c->caption)): ?>
            <div>
                <b><?php echo $c->caption; ?></b>
            </div>
        <?php endif; ?>
            <div class="segment-block"><?php echo $c->fieldList, $c->hiddenField; ?></div>
            <div class="segment-block">
        <?php
            if (isset($c->operatorList)):
                echo $c->operatorList;
            endif; ?>
            </div>
            <div class="segment-block">
        <?php if (isset($c->display)): ?>
            <?php echo $c->display; ?>
        <?php endif; ?>
            </div>
    <?php endif; ?>
        </li>
<?php endforeach; ?>
    </ul>
    <div>
        <label>
<?php echo s('Use one or more saved segments. They will be added to any conditions already entered.'); ?>
<br/>
<?php echo $savedList; ?>
&nbsp;<?php echo $loadButton ?>
        </label>
    </div>
    <div id="recalculate">
<?php echo $removeButton ?>
<?php echo $calculateButton ?>
<?php if (isset($totalSubscribers)):
    echo s('%d subscribers will be selected', $totalSubscribers);
endif; ?>
<?php if (isset($warning)): ?> <span class="error"><?php echo $warning; ?></span><?php endif; ?>
    </div>
    <label>
<?php echo s('Save the current segment (set of conditions).'); ?>
        <br/>
        <div class="segment-block">
    <?php echo $saveName; ?>
        </div>
        <div class="segment-block">
    <?php echo $saveButton; ?>
        </div>
    </label>
    <div class="segment-block">
<?php echo $settings; ?>
        <a href="https://resources.phplist.com/plugin/segment#add_segment_conditions" target="_blank">Guidance on usage</a>
    </div>
</div>
