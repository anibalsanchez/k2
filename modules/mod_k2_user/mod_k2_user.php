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

$language = Joomla\CMS\Factory::getLanguage();

if (K2_JVERSION != '15') {
    $language->load('com_k2.dates', JPATH_ADMINISTRATOR, null, true);
    require_once JPATH_SITE.'/components/com_users/helpers/route.php';
}

require_once __DIR__.'/helper.php';

$moduleclass_sfx = $params->get('moduleclass_sfx', '');
$userGreetingText = $params->get('userGreetingText', '');
$userAvatarWidthSelect = $params->get('userAvatarWidthSelect', 'custom');
$userAvatarWidth = $params->get('userAvatarWidth', 50);

// Legacy params
$greeting = 0;

$type = modK2UserHelper::getType();
$return = modK2UserHelper::getReturnURL($params, $type);
$user = Joomla\CMS\Factory::getUser();

$componentParams = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
$K2CommentsEnabled = $componentParams->get('comments');

// User avatar
$avatarWidth = $userAvatarWidthSelect == 'inherit' ? $componentParams->get('userImageWidth') : $userAvatarWidth;

// Load the right template
if ($user->guest) {
    // OpenID stuff (do not edit)
    if (Joomla\CMS\Plugin\PluginHelper::isEnabled('authentication', 'openid')) {
        $language->load('plg_authentication_openid', JPATH_ADMINISTRATOR);
        $document = Joomla\CMS\Factory::getDocument();
        $document->addScriptDeclaration("
            var JLanguage = {};
            JLanguage.WHAT_IS_OPENID = '".Joomla\CMS\Language\Text::_('K2_WHAT_IS_OPENID')."';
            JLanguage.LOGIN_WITH_OPENID = '".Joomla\CMS\Language\Text::_('K2_LOGIN_WITH_OPENID')."';
            JLanguage.NORMAL_LOGIN = '".Joomla\CMS\Language\Text::_('K2_NORMAL_LOGIN')."';
            var modlogin = 1;
        ");
        Joomla\CMS\HTML\HTMLHelper::_('script', 'openid.js');
    }

    // Get user stuff (do not edit)
    $usersConfig = Joomla\CMS\Component\ComponentHelper::getParams('com_users');

    // Define some variables depending on Joomla version
    $passwordFieldName = (K2_JVERSION != '15') ? 'password' : 'passwd';
    $resetLink = Joomla\CMS\Router\Route::_((K2_JVERSION != '15') ? 'index.php?option=com_users&view=reset&Itemid='.UsersHelperRoute::getResetRoute() : 'index.php?option=com_user&view=reset');
    $remindLink = Joomla\CMS\Router\Route::_((K2_JVERSION != '15') ? 'index.php?option=com_users&view=remind&Itemid='.UsersHelperRoute::getRemindRoute() : 'index.php?option=com_user&view=remind');
    $registrationLink = Joomla\CMS\Router\Route::_((K2_JVERSION != '15') ? 'index.php?option=com_users&view=registration&Itemid='.UsersHelperRoute::getRegistrationRoute() : 'index.php?option=com_user&view=register');

    $option = (K2_JVERSION != '15') ? 'com_users' : 'com_user';
    $task = K2_JVERSION != '15' ? 'user.login' : 'login';

    require Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_k2_user', 'login');
} else {
    $user->profile = modK2UserHelper::getProfile($params);
    $user->numOfComments = modK2UserHelper::countUserComments($user->id);
    $menu = modK2UserHelper::getMenu($params);

    if (is_object($user->profile) && isset($user->profile->addLink)) {
        $addItemLink = $user->profile->addLink;
    }

    $viewProfileLink = Joomla\CMS\Router\Route::_(K2HelperRoute::getUserRoute($user->id));
    $editProfileLink = Joomla\CMS\Router\Route::_((K2_JVERSION != '15') ? 'index.php?option=com_users&view=profile&layout=edit&Itemid='.UsersHelperRoute::getProfileRoute() : 'index.php?option=com_user&view=user&task=edit');
    $profileLink = $editProfileLink; // B/C
    $editCommentsLink = Joomla\CMS\Router\Route::_('index.php?option=com_k2&view=comments&tmpl=component&template=system&context=modalselector');

    $option = (K2_JVERSION != '15') ? 'com_users' : 'com_user';
    $task = (K2_JVERSION != '15') ? 'user.logout' : 'logout';

    require Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_k2_user', 'userblock');
}
