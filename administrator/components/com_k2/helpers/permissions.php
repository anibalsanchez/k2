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

jimport('joomla.html.parameter');

class K2HelperPermissions
{
    public static function checkPermissions()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $task = JRequest::getCmd('task');
        $id = ($task == 'apply' || $task == 'save') ? JRequest::getInt('id') : JRequest::getVar('cid');

        // Generic access check
        if (!$user->authorise('core.manage', $option)) {
            JError::raiseWarning(403, Joomla\CMS\Language\Text::_('JERROR_ALERTNOAUTHOR'));
            $app->redirect('index.php');
        }

        // Determine actions for everything else
        $action = false;
        if ($app->isAdmin() && $view != '' && $view != 'info') {
            switch ($task) {
                case '':
                case 'save':
                case 'apply':
                    $action = $id ? 'core.edit' : 'core.create';

                    break;
                case 'trash':
                case 'remove':
                    $action = 'core.delete';
                    break;
                case 'publish':
                case 'unpublish':
                case 'featured':
                    $action = 'core.edit.state';
            }

            // Edit or edit own action
            if ($action == 'core.edit' && $view == 'item' && $id) {
                Joomla\CMS\Table\Table::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/tables');
                $item = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
                $item->load($id);
                if ($item->created_by == $user->id) {
                    $action = 'core.edit.own';
                }
            }

            // Check the determined action
            if ($action && !$user->authorise($action, $option)) {
                JError::raiseWarning(403, Joomla\CMS\Language\Text::_('JERROR_ALERTNOAUTHOR'));
                $app->redirect('index.php?option=com_k2');
            }
        }
    }
}
