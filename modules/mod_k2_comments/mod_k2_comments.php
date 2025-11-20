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
defined('_JEXEC') || die;

if (K2_JVERSION != '15') {
    $language = Joomla\CMS\Factory::getLanguage();
    $language->load('com_k2.dates', JPATH_ADMINISTRATOR, null, true);
}

require_once __DIR__.'/helper.php';

// Params
$moduleclass_sfx = $params->get('moduleclass_sfx', '');
$module_usage = $params->get('module_usage', '0');

$commentAvatarWidthSelect = $params->get('commentAvatarWidthSelect', 'custom');
$commentAvatarWidth = $params->get('commentAvatarWidth', 50);

$commenterAvatarWidthSelect = $params->get('commenterAvatarWidthSelect', 'custom');
$commenterAvatarWidth = $params->get('commenterAvatarWidth', 50);

// Get component params
$componentParams = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');

// User avatar for latest comments
if ($commentAvatarWidthSelect == 'inherit') {
    $lcAvatarWidth = $componentParams->get('commenterImgWidth');
} else {
    $lcAvatarWidth = $commentAvatarWidth;
}

// User avatar for top commenters
if ($commenterAvatarWidthSelect == 'inherit') {
    $tcAvatarWidth = $componentParams->get('commenterImgWidth');
} else {
    $tcAvatarWidth = $commenterAvatarWidth;
}

switch ($module_usage) {
    case '0':
        $comments = modK2CommentsHelper::getLatestComments($params);
        require Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_k2_comments', 'comments');
        break;

    case '1':
        $commenters = modK2CommentsHelper::getTopCommenters($params);
        require Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_k2_comments', 'commenters');
        break;
}
