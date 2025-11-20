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
    $user = Joomla\CMS\Factory::getUser();
    $user->gid = $user->authorise('core.admin', 'com_k2') ? 1000 : 1;
}

JLoader::register('K2Controller', JPATH_COMPONENT.'/controllers/controller.php');
JLoader::register('K2View', JPATH_COMPONENT_ADMINISTRATOR.'/views/view.php');
JLoader::register('K2Model', JPATH_COMPONENT_ADMINISTRATOR.'/models/model.php');

JLoader::register('K2HelperRoute', JPATH_COMPONENT.'/helpers/route.php');
JLoader::register('K2HelperPermissions', JPATH_COMPONENT.'/helpers/permissions.php');
JLoader::register('K2HelperUtilities', JPATH_COMPONENT.'/helpers/utilities.php');

K2HelperPermissions::setPermissions();
K2HelperPermissions::checkPermissions();

$controller = K2Request::getWord('view', 'itemlist');
$task = K2Request::getWord('task');

if ($controller == 'media') {
    $controller = 'item';
    if ($task != 'connector') {
        $task = 'media';
    }
}

if ($controller == 'users') {
    $controller = 'item';
    $task = 'users';
}

jimport('joomla.filesystem.file');
jimport('joomla.html.parameter');

if (Joomla\CMS\Filesystem\File::exists(JPATH_COMPONENT.'/controllers/'.$controller.'.php')) {
    $classname = 'K2Controller'.$controller;
    if (!class_exists($classname)) {
        require_once JPATH_COMPONENT.'/controllers/'.$controller.'.php';
    }

    $controller = new $classname();
    $controller->execute($task);
    $controller->redirect();
} else {
    JError::raiseError(404, Joomla\CMS\Language\Text::_('K2_NOT_FOUND'));
}

if (K2Request::getCmd('format') != 'json') {
    echo "\n<!-- JoomlaWorks \"K2\" (v".K2_CURRENT_VERSION.") | Learn more about K2 at https://getk2.org -->\n\n";
}
