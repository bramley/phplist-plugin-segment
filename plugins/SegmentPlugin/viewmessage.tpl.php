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
    <div><?php echo s('Subscribers match %s of the following:', $combine); ?></div>
    <ul>
<?php foreach ($condition as $c) : ?>
        <li class="selfclear">
    <?php if (isset($c->error)): ?>
            <div class="note"><?php echo $c->error; ?></div>
    <?php else: ?>
            <div class="segment-block"><?php echo $c->field; ?></div>
            <div class="segment-block"><?php echo $c->operator; ?></div>
            <div class="segment-block">
                <fieldset disabled><?php echo $c->display; ?></fieldset>
            </div>
    <?php endif; ?>
        </li>
<?php endforeach; ?>
    </ul>
</div>
