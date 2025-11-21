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

class K2ModelExtraFields extends K2Model
{
    public function getData()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $option = K2Request::getCmd('option');
        $view = K2Request::getCmd('view');
        $db = Joomla\CMS\Factory::getDbo();
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.$view.'.limitstart', 'limitstart', 0, 'int');
        $filter_order = $app->getUserStateFromRequest($option.$view.'filter_order', 'filter_order', 'groupname', 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($option.$view.'filter_order_Dir', 'filter_order_Dir', 'ASC', 'word');
        $filter_state = $app->getUserStateFromRequest($option.$view.'filter_state', 'filter_state', -1, 'int');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = \Joomla\String\StringHelper::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search));

        $filter_type = $app->getUserStateFromRequest($option.$view.'filter_type', 'filter_type', '', 'string');
        $filter_group = $app->getUserStateFromRequest($option.$view.'filter_group', 'filter_group', 0, 'int');

        $query = 'SELECT exf.*, exfg.name as groupname FROM #__k2_extra_fields AS exf LEFT JOIN #__k2_extra_fields_groups exfg ON exf.group=exfg.id  WHERE exf.id>0';

        if ($filter_state > -1) {
            $query .= ' AND published='.$filter_state;
        }

        if ($search !== '' && $search !== '0') {
            $escaped = K2_JVERSION == '15' ? $db->getEscaped($search, true) : $db->escape($search, true);
            $query .= ' AND LOWER(exf.name) LIKE '.$db->Quote('%'.$escaped.'%', false);
        }

        if ($filter_type) {
            $query .= ' AND `type`='.$db->Quote($filter_type);
        }

        if ($filter_group) {
            $query .= ' AND `group`='.$filter_group;
        }

        if (!$filter_order) {
            $filter_order = '`group`';
        }

        if ($filter_order == 'ordering') {
            $query .= ' ORDER BY `group`, ordering '.$filter_order_Dir;
        } else {
            $query .= sprintf(' ORDER BY %s %s, `group`, ordering', $filter_order, $filter_order_Dir);
        }

        $db->setQuery($query, $limitstart, $limit);
        $rows = $db->loadObjectList();

        return $rows;
    }

    public function getTotal()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $option = K2Request::getCmd('option');
        $view = K2Request::getCmd('view');
        $db = Joomla\CMS\Factory::getDbo();
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.'.limitstart', 'limitstart', 0, 'int');
        $filter_state = $app->getUserStateFromRequest($option.$view.'filter_state', 'filter_state', 1, 'int');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = \Joomla\String\StringHelper::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search));

        $filter_type = $app->getUserStateFromRequest($option.$view.'filter_type', 'filter_type', '', 'string');
        $filter_group = $app->getUserStateFromRequest($option.$view.'filter_group', 'filter_group', '', 'string');

        $query = 'SELECT COUNT(*) FROM #__k2_extra_fields WHERE id>0';

        if ($filter_state > -1) {
            $query .= ' AND published='.$filter_state;
        }

        if ($search !== '' && $search !== '0') {
            $escaped = K2_JVERSION == '15' ? $db->getEscaped($search, true) : $db->escape($search, true);
            $query .= ' AND LOWER(name) LIKE '.$db->Quote('%'.$escaped.'%', false);
        }

        if ($filter_type) {
            $query .= ' AND `type`='.$db->Quote($filter_type);
        }

        if ($filter_group) {
            $query .= ' AND `group`='.$db->Quote($filter_group);
        }

        $db->setQuery($query);
        $total = $db->loadresult();

        return $total;
    }

    public function publish()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = K2Request::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
            $row->load($id);
            $row->published = 1;
            $row->store();
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->redirect('index.php?option=com_k2&view=extrafields');
    }

    public function unpublish()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = K2Request::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
            $row->load($id);
            $row->published = 0;
            $row->store();
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->redirect('index.php?option=com_k2&view=extrafields');
    }

    public function saveorder()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $cid = K2Request::getVar('cid', [0], 'post', 'array');
        $total = count($cid);
        $order = K2Request::getVar('order', [0], 'post', 'array');
        JArrayHelper::toInteger($order, [0]);
        $groupings = [];
        for ($i = 0; $i < $total; $i++) {
            $row = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
            $row->load((int) $cid[$i]);
            $groupings[] = $row->group;
            if ($row->ordering != $order[$i]) {
                $row->ordering = $order[$i];
                if (!$row->store()) {
                    JError::raiseError(500, $db->getErrorMsg());
                }
            }
        }

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        if (!$params->get('disableCompactOrdering')) {
            $groupings = array_unique($groupings);
            foreach ($groupings as $grouping) {
                $row = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
                $row->reorder('`group` = '.(int) $grouping);
            }
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        return true;
    }

    public function orderup()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = K2Request::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
        $row->load($cid[0]);
        $row->move(-1, sprintf("`group` = '%s'", $row->group));

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        if (!$params->get('disableCompactOrdering')) {
            $row->reorder('`group` = '.(int) $row->group);
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $msg = Joomla\CMS\Language\Text::_('K2_NEW_ORDERING_SAVED');
        $app->enqueueMessage($msg);
        $app->redirect('index.php?option=com_k2&view=extrafields');
    }

    public function orderdown()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = K2Request::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
        $row->load($cid[0]);
        $row->move(1, sprintf("`group` = '%s'", $row->group));

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        if (!$params->get('disableCompactOrdering')) {
            $row->reorder('`group` = '.(int) $row->group);
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $msg = Joomla\CMS\Language\Text::_('K2_NEW_ORDERING_SAVED');
        $app->enqueueMessage($msg);
        $app->redirect('index.php?option=com_k2&view=extrafields');
    }

    public function remove()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $cid = K2Request::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
            $row->load($id);
            $row->delete($id);
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_DELETE_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=extrafields');
    }

    public function getExtraFieldsGroup()
    {
        $cid = K2Request::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2ExtraFieldsGroup', 'Table');
        $row->load($cid);

        return $row;
    }

    public function getGroups($filter = false)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $option = K2Request::getCmd('option');
        $view = K2Request::getCmd('view');
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.$view.'.limitstart', 'limitstart', 0, 'int');
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT * FROM #__k2_extra_fields_groups ORDER BY `name`';
        if ($filter) {
            $db->setQuery($query);
        } else {
            $db->setQuery($query, $limitstart, $limit);
        }

        $rows = $db->loadObjectList();
        $counter = count($rows);
        for ($i = 0; $i < $counter; $i++) {
            $query = 'SELECT name FROM #__k2_categories WHERE extraFieldsGroup = '.(int) $rows[$i]->id;
            $db->setQuery($query);
            $categories = K2_JVERSION == '30' ? $db->loadColumn() : $db->loadResultArray();
            $rows[$i]->categories = is_array($categories) ? implode(', ', $categories) : '';
        }

        return $rows;
    }

    public function getTotalGroups()
    {
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT COUNT(*) FROM #__k2_extra_fields_groups';
        $db->setQuery($query);
        $total = $db->loadResult();

        return $total;
    }

    public function saveGroup()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $id = K2Request::getInt('id');
        $row = Joomla\CMS\Table\Table::getInstance('K2ExtraFieldsGroup', 'Table');
        if (!$row->bind(K2Request::getPost())) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=extrafieldsgroups');
        }

        if (!$row->check()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=extrafieldsgroup&cid='.$row->id);
        }

        if (!$row->store()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=extrafieldsgroup');
        }

        switch (K2Request::getCmd('task')) {
            case 'apply':
                $msg = Joomla\CMS\Language\Text::_('K2_CHANGES_TO_GROUP_SAVED');
                $link = 'index.php?option=com_k2&view=extrafieldsgroup&cid='.$row->id;
                break;
            case 'saveAndNew':
                $msg = Joomla\CMS\Language\Text::_('K2_GROUP_SAVED');
                $link = 'index.php?option=com_k2&view=extrafieldsgroup';
                break;
            case 'save':
            default:
                $msg = Joomla\CMS\Language\Text::_('K2_GROUP_SAVED');
                $link = 'index.php?option=com_k2&view=extrafieldsgroups';
                break;
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage($msg);
        $app->redirect($link);
    }

    public function removeGroups()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $cid = K2Request::getVar('cid');
        JArrayHelper::toInteger($cid);
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2ExtraFieldsGroup', 'Table');
            $row->load($id);
            $query = 'DELETE FROM #__k2_extra_fields WHERE `group`='.$id;
            $db->setQuery($query);
            $db->execute();
            $row->delete($id);
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_DELETE_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=extrafieldsgroups');
    }
}
