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

jimport('joomla.application.component.model');

Joomla\CMS\Table\Table::addIncludePath(JPATH_COMPONENT.'/tables');

class K2ModelUserGroup extends K2Model
{
    public function getData()
    {
        $cid = K2Request::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2UserGroup', 'Table');
        $row->load($cid);

        return $row;
    }

    public function save()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $row = Joomla\CMS\Table\Table::getInstance('K2UserGroup', 'Table');

        if (!$row->bind(K2Request::getPost())) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=usergroups');
        }

        if (!$row->check()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=usergroup&cid='.$row->id);
        }

        if (!$row->store()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=usergroups');
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        switch (K2Request::getCmd('task')) {
            case 'apply':
                $msg = Joomla\CMS\Language\Text::_('K2_CHANGES_TO_USER_GROUP_SAVED');
                $link = 'index.php?option=com_k2&view=usergroup&cid='.$row->id;
                break;
            case 'saveAndNew':
                $msg = Joomla\CMS\Language\Text::_('K2_USER_GROUP_SAVED');
                $link = 'index.php?option=com_k2&view=usergroup';
                break;
            case 'save':
            default:
                $msg = Joomla\CMS\Language\Text::_('K2_USER_GROUP_SAVED');
                $link = 'index.php?option=com_k2&view=usergroups';
                break;
        }

        $app->enqueueMessage($msg);
        $app->redirect($link);
    }
}
