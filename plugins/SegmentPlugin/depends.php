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
 * @copyright 2018 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

namespace phpList\plugin\SegmentPlugin;

use phpList\plugin\SegmentPlugin\Controller\Export;
use Psr\Container\ContainerInterface;
use SegmentPlugin_ConditionFactory;
use SegmentPlugin_DAO;

/*
 * This file provides the dependencies for a dependency injection container.
 */

return [
    'ConditionFactory' => function (ContainerInterface $container) {
        $daoAttr = $container->get('phpList\plugin\Common\DAO\Attribute');

        return new SegmentPlugin_ConditionFactory(
            $container->get('SegmentPlugin_DAO'),
            $daoAttr->attributesById(getConfig('segment_attribute_max_length'), 0)
        );
    },
    'SegmentPlugin_DAO' => function (ContainerInterface $container) {
        return new SegmentPlugin_DAO(
            $container->get('phpList\plugin\Common\DB')
        );
    },
    'phpList\plugin\SegmentPlugin\Controller\Export' => function (ContainerInterface $container) {
        return new Export(
            $container->get('ConditionFactory')
        );
    },
];
