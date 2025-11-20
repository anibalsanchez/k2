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

class K2ModelUserGroups extends K2Model
{
    public function getData()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $db = Joomla\CMS\Factory::getDbo();
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.$view.'.limitstart', 'limitstart', 0, 'int');
        $filter_order = $app->getUserStateFromRequest($option.$view.'filter_order', 'filter_order', '', 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($option.$view.'filter_order_Dir', 'filter_order_Dir', '', 'word');

        $query = 'SELECT userGroup.*, (SELECT COUNT(DISTINCT userID) FROM #__k2_users WHERE `group`=userGroup.id) AS numOfUsers FROM #__k2_user_groups AS userGroup';

        if (!$filter_order) {
            $filter_order = 'name';
        }

        $query .= sprintf(' ORDER BY %s %s', $filter_order, $filter_order_Dir);

        $db->setQuery($query, $limitstart, $limit);
        $rows = $db->loadObjectList();

        return $rows;
    }

    public function getTotal()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $db = Joomla\CMS\Factory::getDbo();

        $query = 'SELECT COUNT(*) FROM #__k2_user_groups';

        $db->setQuery($query);
        $total = $db->loadresult();

        return $total;
    }

    public function remove()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2UserGroup', 'Table');
            $row->load($id);
            $row->delete($id);
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_DELETE_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=usergroups');
    }
}
