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
$getTemplate = $params->get('getTemplate', 'Default');
$itemAuthorAvatarWidthSelect = $params->get('itemAuthorAvatarWidthSelect', 'custom');
$itemAuthorAvatarWidth = $params->get('itemAuthorAvatarWidth', 50);
$itemCustomLinkTitle = $params->get('itemCustomLinkTitle', '');
$itemCustomLinkURL = trim($params->get('itemCustomLinkURL'));
$itemCustomLinkMenuItem = $params->get('itemCustomLinkMenuItem');

if ($itemCustomLinkURL && $itemCustomLinkURL !== 'http://' && $itemCustomLinkURL !== 'https://') {
    if ($itemCustomLinkTitle == '') {
        if (str_contains($itemCustomLinkURL, '://')) {
            $linkParts = explode('://', $itemCustomLinkURL);
            $itemCustomLinkURL = $linkParts[1];
        }

        $itemCustomLinkTitle = $itemCustomLinkURL;
    }
} elseif ($itemCustomLinkMenuItem) {
    $menu = Joomla\CMS\Menu\AbstractMenu::getInstance('site');
    $menuLink = $menu->getItem($itemCustomLinkMenuItem);
    if (!empty($menuLink)) {
        if (!$itemCustomLinkTitle) {
            $itemCustomLinkTitle = (K2_JVERSION != '15') ? $menuLink->title : $menuLink->name;
        }

        $itemCustomLinkURL = Joomla\CMS\Router\Route::_('index.php?&Itemid='.$menuLink->id);
    } else {
        $itemCustomLinkTitle = '';
        $itemCustomLinkURL = '';
    }
}

// Make params backwards compatible
$params->set('itemCustomLinkTitle', $itemCustomLinkTitle);
$params->set('itemCustomLinkURL', $itemCustomLinkURL);

// Get component params
$componentParams = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');

// User avatar
if ($itemAuthorAvatarWidthSelect == 'inherit') {
    $avatarWidth = $componentParams->get('userImageWidth');
} else {
    $avatarWidth = $itemAuthorAvatarWidth;
}

$items = modK2ContentHelper::getItems($params);

if (is_array($items) && count($items)) {
    require Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_k2_content', $getTemplate.'/default');
}
