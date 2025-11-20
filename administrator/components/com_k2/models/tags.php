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

class K2ModelTags extends K2Model
{
    public function getData()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $db = Joomla\CMS\Factory::getDbo();
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.$view.'.limitstart', 'limitstart', 0, 'int');
        $filter_order = $app->getUserStateFromRequest($option.$view.'filter_order', 'filter_order', 'id', 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($option.$view.'filter_order_Dir', 'filter_order_Dir', 'DESC', 'word');
        $filter_state = $app->getUserStateFromRequest($option.$view.'filter_state', 'filter_state', -1, 'int');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = JString::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search));

        $query = 'SELECT #__k2_tags.*, (SELECT COUNT(*) FROM #__k2_tags_xref WHERE #__k2_tags_xref.tagID = #__k2_tags.id) AS numOfItems FROM #__k2_tags';

        $conditions = [];

        if ($filter_state > -1) {
            $conditions[] = 'published='.$filter_state;
        }

        if ($search !== '' && $search !== '0') {
            $escaped = K2_JVERSION == '15' ? $db->getEscaped($search, true) : $db->escape($search, true);
            $conditions[] = 'LOWER(name) LIKE '.$db->Quote('%'.$escaped.'%', false);
        }

        if ($conditions !== []) {
            $query .= ' WHERE '.implode(' AND ', $conditions);
        }

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
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.'.limitstart', 'limitstart', 0, 'int');
        $filter_state = $app->getUserStateFromRequest($option.$view.'filter_state', 'filter_state', 1, 'int');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = JString::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search));

        $query = 'SELECT COUNT(*) FROM #__k2_tags WHERE id > 0';

        if ($filter_state > -1) {
            $query .= ' AND published='.$filter_state;
        }

        if ($search !== '' && $search !== '0') {
            $escaped = K2_JVERSION == '15' ? $db->getEscaped($search, true) : $db->escape($search, true);
            $query .= ' AND LOWER(name) LIKE '.$db->Quote('%'.$escaped.'%', false);
        }

        $db->setQuery($query);
        $total = $db->loadresult();

        return $total;
    }

    public function publish()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Tag', 'Table');
            $row->load($id);
            $row->published = 1;
            $row->store();
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=tags&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=tags');
        }
    }

    public function unpublish()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Tag', 'Table');
            $row->load($id);
            $row->published = 0;
            $row->store();
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=tags&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=tags');
        }
    }

    public function remove()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Tag', 'Table');
            $row->load($id);
            $row->delete($id);
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_DELETE_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=tags');
    }

    public function getFilter()
    {
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT name, id FROM #__k2_tags ORDER BY name';
        $db->setQuery($query);
        $rows = $db->loadObjectList();

        return $rows;
    }

    public function countTagItems($id)
    {
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT COUNT(*) FROM #__k2_tags_xref WHERE tagID = '.(int) $id;
        $db->setQuery($query);
        $result = $db->loadResult();

        return $result;
    }

    public function removeOrphans()
    {
        $db = Joomla\CMS\Factory::getDbo();
        $db->setQuery('DELETE FROM #__k2_tags WHERE id NOT IN (SELECT tagID FROM #__k2_tags_xref GROUP BY tagID)');
        $db->execute();

        $app = Joomla\CMS\Factory::getApplication();
        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_DELETE_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=tags');
    }
}
