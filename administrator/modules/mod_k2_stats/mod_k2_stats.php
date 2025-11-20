<?php

/*
 * @package     k2-jx-ready
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2025 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 *
 * Based on K2 by JoomlaWorks Ltd. See: https://github.com/getk2/k2
 */

// no direct access
defined('_JEXEC') or die;

$user = JFactory::getUser();

if (K2_JVERSION != '15') {
    if (!$user->authorise('core.manage', 'com_k2')) {
        return;
    }
}

if (K2_JVERSION != '15') {
    $language = JFactory::getLanguage();
    $language->load('com_k2.dates', JPATH_ADMINISTRATOR);
}

require_once dirname(__FILE__).'/helper.php';

if ($params->get('latestItems', 1)) {
    $latestItems = modK2StatsHelper::getLatestItems();
}
if ($params->get('popularItems', 1)) {
    $popularItems = modK2StatsHelper::getPopularItems();
}
if ($params->get('mostCommentedItems', 1)) {
    $mostCommentedItems = modK2StatsHelper::getMostCommentedItems();
}
if ($params->get('latestComments', 1)) {
    $latestComments = modK2StatsHelper::getLatestComments();
}
if ($params->get('statistics', 1)) {
    $statistics = modK2StatsHelper::getStatistics();
}

require JModuleHelper::getLayoutPath('mod_k2_stats');
