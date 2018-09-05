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
<?php if (isset($warning)): ?>
    <div class="error">
        <?php echo $warning; ?>
    </div>
<?php endif; ?>
    <div class="note">
<?php
    $sentence1 = s('Select one or more subscriber fields or attributes.');
    $sentence2 = s('The campaign will be sent only to those subscribers who match any or all of the conditions.');
    $sentence3 = s('To remove a condition set it to "%s" from the list.', '<em>' . $selectPrompt . '</em>');
    echo $sentence1, ' ', $sentence2, ' ', $sentence3;
?>
&nbsp;
<?php echo $help; ?>
    </div>
<?php if (isset($savedList)): ?>
    <div class="field">
        <label>
    <?php echo s('Use one or more saved segments. They will be added to any conditions below.'); ?>
        </label>
    <?php echo $savedList; ?>
    <?php echo $loadButton ?>
    <?php echo $settingsButton ?>
    </div>
    <hr class="separator"/>
<?php endif; ?>
    <div><?php echo s('Subscribers match %s of the following:', $combineList); ?></div>
    <ul>
<?php foreach ($condition as $c) : ?>
        <li class="selfclear">
    <?php if (isset($c->error)): ?>
            <div class="note"><?php echo $c->error; ?></div>
    <?php else: ?>
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
    <div id="recalculate">
<?php if (isset($removeButton)) echo $removeButton; ?>
<?php echo $calculateButton ?>
<?php if (isset($totalSubscribers)): ?>
    <?php echo $exportCalculatedButton ?>
        <div class="note">
    <?php echo s('%d subscribers will be selected.', $totalSubscribers); ?>
    <?php if ($totalSubscribers > count($subscribers)): echo s('First %d subscribers:', count($subscribers)); endif; ?>
            <br/>
    <?php foreach ($subscribers as $subscriber): ?>
        <?= $subscriber['email']; ?>
            <br/>
    <?php endforeach; ?>
        </div>
<?php endif; ?>
    </div>
<?php if (isset($saveName)): ?>
    <div class="field">
        <hr class="separator"/>
        <label>
    <?php echo s('Save the current set of conditions'); ?>
        </label>
        <div class="segment-block">
    <?php echo $saveName; ?>
        </div>
        <div class="segment-block">
    <?php echo $saveButton; ?>
        </div>
    </div>
<?php endif; ?>
</div>
<?php
global $plugins;

require $plugins['CommonPlugin']->coderoot . 'dialog_js.php';
