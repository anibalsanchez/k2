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

if (K2_JVERSION != '15') {
    $language = JFactory::getLanguage();
    $language->load('com_k2.dates', JPATH_ADMINISTRATOR, null, true);
}

require_once dirname(__FILE__).'/helper.php';

// Params
$moduleclass_sfx = $params->get('moduleclass_sfx', '');
$getTemplate = $params->get('getTemplate', 'Default');
$userName = $params->get('userName', 1);
$userAvatar = $params->get('userAvatar', 1);
$userAvatarWidthSelect = $params->get('userAvatarWidthSelect', 'custom');
$userAvatarWidth = $params->get('userAvatarWidth', 50);
$userDescription = $params->get('userDescription', 1);
$userDescriptionWordLimit = $params->get('userDescriptionWordLimit');
$userURL = $params->get('userURL', 1);
$userEmail = $params->get('userEmail', 0);
$userFeed = $params->get('userFeed', 1);
$userItemCount = $params->get('userItemCount', 1);

// User avatar
if ($userAvatarWidthSelect == 'inherit') {
    $componentParams = JComponentHelper::getParams('com_k2');
    $avatarWidth = $componentParams->get('userImageWidth');
} else {
    $avatarWidth = $userAvatarWidth;
}

$users = modK2UsersHelper::getUsers($params);

require JModuleHelper::getLayoutPath('mod_k2_users', $getTemplate.'/default');
