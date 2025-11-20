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

class K2ModelCategories extends K2Model
{
    private $getTotal;

    public function getData()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $db = Joomla\CMS\Factory::getDbo();
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.$view.'.limitstart', 'limitstart', 0, 'int');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = JString::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-.,:!?\'"()]/u', '', $search));

        $language = $app->getUserStateFromRequest($option.$view.'language', 'language', '', 'string');

        $filter_category = $app->getUserStateFromRequest($option.$view.'filter_category', 'filter_category', 0, 'int');
        $filter_order = $app->getUserStateFromRequest($option.$view.'filter_order', 'filter_order', 'c.ordering', 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($option.$view.'filter_order_Dir', 'filter_order_Dir', '', 'word');
        $filter_state = $app->getUserStateFromRequest($option.$view.'filter_state', 'filter_state', -1, 'int');
        $filter_trash = $app->getUserStateFromRequest($option.$view.'filter_trash', 'filter_trash', 0, 'int');

        $queryStart = '/* Backend / K2 / Categories */ SELECT c.*, g.name AS groupname, exfg.name as extra_fields_group
            FROM #__k2_categories as c
            LEFT JOIN #__groups AS g ON g.id = c.access
            LEFT JOIN #__k2_extra_fields_groups AS exfg ON exfg.id = c.extraFieldsGroup
            WHERE c.id > 0';
        if (K2_JVERSION != '15') {
            $queryStart = JString::str_ireplace('g.name AS groupname', 'g.title AS groupname', $queryStart);
            $queryStart = JString::str_ireplace('#__groups', '#__viewlevels', $queryStart);
        }

        $query = '';

        if ($filter_category) {
            K2Model::addIncludePath(JPATH_SITE.'/components/com_k2/models');
            $ItemlistModel = K2Model::getInstance('Itemlist', 'K2Model');
            $tree = $ItemlistModel->getCategoryTree($filter_category);
            $query .= ' AND c.id IN ('.implode(',', $tree).')';
        }

        if ($filter_state > -1) {
            $query .= ' AND c.published = '.$filter_state;
        }

        if (!$filter_trash) {
            $query .= ' AND c.trash = 0';
        }

        if ($language) {
            $query .= ' AND (c.language = '.$db->Quote($language)." OR c.language = '*')";
        }

        // Search
        if ($search !== '' && $search !== '0') {
            // Detect exact search phrase using double quotes in search string
            $exact = str_starts_with($search, '"') && str_ends_with($search, '"');

            // Now completely strip double quotes
            $search = trim(str_replace('"', '', $search));

            // Escape remaining string
            $escaped = K2_JVERSION == '15' ? $db->getEscaped($search, true) : $db->escape($search, true);

            // Full phrase or set of words
            if (str_contains($escaped, ' ') && !$exact) {
                $escaped = explode(' ', $escaped);
                $quoted = [];
                foreach ($escaped as $key => $escapedWord) {
                    $quoted[] = $db->Quote('%'.$escapedWord.'%', false);
                }

                if ($params->get('adminSearch') == 'full') {
                    $searchPerTerm = [];
                    $query .= ' AND (';
                    foreach ($quoted as $quotedWord) {
                        $query .= 'LOWER(c.name) LIKE '.$quotedWord.' OR LOWER(c.description) LIKE '.$quotedWord;
                    }

                    $query .= implode(' OR ', $searchPerTerm);
                    $query .= ')';
                } else {
                    foreach ($quoted as $quotedWord) {
                        $query .= ' AND LOWER(c.name) LIKE '.$quotedWord;
                    }
                }
            }
            // Single word or exact phrase to search for (wrapped in double quotes in the search block)
            else {
                $quoted = $db->Quote('%'.$escaped.'%', false);
                if ($params->get('adminSearch') == 'full') {
                    $query .= ' AND (LOWER(c.name) LIKE '.$quoted.' OR LOWER(c.description) LIKE '.$quoted.')';
                } else {
                    $query .= ' AND LOWER(c.name) LIKE '.$quoted;
                }
            }
        }

        $queryEnd = sprintf(' ORDER BY %s %s', $filter_order, $filter_order_Dir);

        // --- Final query ---
        $combinedQuery = $queryStart.$query.$queryEnd;
        $db->setQuery($combinedQuery);
        $rows = $db->loadObjectList();

        // --- Row counter ---
        if (count($rows) > 0) {
            $countQuery = '/* Backend / K2 / Categories Count */ SELECT COUNT(c.id) FROM #__k2_categories as c WHERE c.id > 0'.$query;
            $db->setQuery($countQuery);
            $this->getTotal = (int) $db->loadResult();
        }

        // --- Continue to build the categories tree ---

        // For B/C - need to double check usage
        if (K2_JVERSION != '15') {
            foreach ($rows as $row) {
                $row->parent_id = $row->parent;
                $row->title = $row->name;
            }
        }

        $categories = [];

        if ($search !== '' && $search !== '0') {
            foreach ($rows as $row) {
                $row->treename = $row->name;
                $categories[] = $row;
            }
        } else {
            if ($filter_category) {
                $db->setQuery('SELECT parent FROM #__k2_categories WHERE id = '.$filter_category);
                $root = $db->loadResult();
            } elseif ($language && count($categories)) {
                $root = $categories[0]->parent;
            } else {
                $root = 0;
            }

            $categories = $this->indentRows($rows, $root);
        }

        // Pagination
        jimport('joomla.html.pagination');
        $total = count($categories);
        $jPagination = new JPagination($total, $limitstart, $limit);

        // Display category inheritance
        $categories = @array_slice($categories, $jPagination->limitstart, $jPagination->limit);
        foreach ($categories as $category) {
            $category->parameters = class_exists('JParameter') ? new JParameter($category->params) : new JRegistry($category->params);
            if ($category->parameters->get('inheritFrom')) {
                $db->setQuery('SELECT name FROM #__k2_categories WHERE id = '.(int) $category->parameters->get('inheritFrom'));
                $category->inheritFrom = $db->loadResult();
            } else {
                $category->inheritFrom = '';
            }
        }

        return $categories;
    }

    public function getTotal()
    {
        return $this->getTotal;
    }

    public function indentRows(&$rows, $root = 0)
    {
        $children = [];
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $pt = $row->parent;
                $list = @$children[$pt] ? $children[$pt] : [];
                $list[] = $row;
                $children[$pt] = $list;
            }
        }

        $categories = Joomla\CMS\HTML\HTMLHelper::_('menu.treerecurse', $root, '', [], $children);

        return $categories;
    }

    public function publish()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $row->load($id);
            $row->published = 1;
            $row->store();
        }

        Joomla\CMS\Plugin\PluginHelper::importPlugin('finder');
        $dispatcher = JDispatcher::getInstance();
        $dispatcher->trigger('onFinderChangeState', ['com_k2.category', $cid, 1]);

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=categories&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=categories');
        }
    }

    public function unpublish()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $row->load($id);
            $row->published = 0;
            $row->store();
        }

        Joomla\CMS\Plugin\PluginHelper::importPlugin('finder');
        $dispatcher = JDispatcher::getInstance();
        $dispatcher->trigger('onFinderChangeState', ['com_k2.category', $cid, 0]);

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=categories&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=categories');
        }
    }

    public function saveorder()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid', [0], 'post', 'array');
        $total = count($cid);
        $order = JRequest::getVar('order', [0], 'post', 'array');
        JArrayHelper::toInteger($order, [0]);
        $groupings = [];
        for ($i = 0; $i < $total; $i++) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $row->load((int) $cid[$i]);
            $groupings[] = $row->parent;
            if ($row->ordering != $order[$i]) {
                $row->ordering = $order[$i];
                if (!$row->store()) {
                    JError::raiseError(500, $db->getErrorMsg());
                }
            }
        }

        if (!$params->get('disableCompactOrdering')) {
            $groupings = array_unique($groupings);
            foreach ($groupings as $grouping) {
                $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
                $row->reorder('parent = '.(int) $grouping.' AND trash=0');
            }
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        return true;
    }

    public function orderup()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $cid = JRequest::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
        $row->load($cid[0]);
        $row->move(-1, 'parent = '.$row->parent.' AND trash=0');
        if (!$params->get('disableCompactOrdering')) {
            $row->reorder('parent = '.(int) $row->parent.' AND trash=0');
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $msg = Joomla\CMS\Language\Text::_('K2_NEW_ORDERING_SAVED');
        $app->enqueueMessage($msg);
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=categories&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=categories');
        }
    }

    public function orderdown()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $cid = JRequest::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
        $row->load($cid[0]);
        $row->move(1, 'parent = '.$row->parent.' AND trash=0');
        if (!$params->get('disableCompactOrdering')) {
            $row->reorder('parent = '.(int) $row->parent.' AND trash=0');
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $msg = Joomla\CMS\Language\Text::_('K2_NEW_ORDERING_SAVED');
        $app->enqueueMessage($msg);
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=categories&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=categories');
        }
    }

    public function accessregistered()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
        $cid = JRequest::getVar('cid');
        $row->load($cid[0]);
        $row->access = 1;
        if (!$row->check()) {
            return $row->getError();
        }

        if (!$row->store()) {
            return $row->getError();
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $msg = Joomla\CMS\Language\Text::_('K2_NEW_ACCESS_SETTING_SAVED');
        $app->enqueueMessage($msg);
        $app->redirect('index.php?option=com_k2&view=categories');

        return null;
    }

    public function accessspecial()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
        $cid = JRequest::getVar('cid');
        $row->load($cid[0]);
        $row->access = 2;
        if (!$row->check()) {
            return $row->getError();
        }

        if (!$row->store()) {
            return $row->getError();
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $msg = Joomla\CMS\Language\Text::_('K2_NEW_ACCESS_SETTING_SAVED');
        $app->enqueueMessage($msg);
        $app->redirect('index.php?option=com_k2&view=categories');

        return null;
    }

    public function accesspublic()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
        $cid = JRequest::getVar('cid');
        $row->load($cid[0]);
        $row->access = 0;
        if (!$row->check()) {
            return $row->getError();
        }

        if (!$row->store()) {
            return $row->getError();
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $msg = Joomla\CMS\Language\Text::_('K2_NEW_ACCESS_SETTING_SAVED');
        $app->enqueueMessage($msg);
        $app->redirect('index.php?option=com_k2&view=categories');

        return null;
    }

    public function trash()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
        JArrayHelper::toInteger($cid);
        K2Model::addIncludePath(JPATH_SITE.'/components/com_k2/models');
        $model = K2Model::getInstance('Itemlist', 'K2Model');
        $categories = $model->getCategoryTree($cid);
        $sql = @implode(',', $categories);
        $db = Joomla\CMS\Factory::getDbo();
        $query = sprintf('UPDATE #__k2_categories SET trash=1  WHERE id IN (%s)', $sql);
        $db->setQuery($query);
        $db->query();

        $query = sprintf('UPDATE #__k2_items SET trash=1  WHERE catid IN (%s)', $sql);
        $db->setQuery($query);
        $db->query();

        Joomla\CMS\Plugin\PluginHelper::importPlugin('finder');
        $dispatcher = JDispatcher::getInstance();
        $dispatcher->trigger('onFinderChangeState', ['com_k2.category', $cid, 0]);

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_CATEGORIES_MOVED_TO_TRASH'));
        $app->redirect('index.php?option=com_k2&view=categories');
    }

    public function restore()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid');
        $warning = false;
        $restored = [];
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $row->load($id);
            if ((int) $row->parent == 0) {
                $row->trash = 0;
                $row->store();
                $restored[] = $id;
            } else {
                $query = sprintf('SELECT COUNT(*) FROM #__k2_categories WHERE id=%s AND trash = 0', $row->parent);
                $db->setQuery($query);
                $result = $db->loadResult();
                if ($result) {
                    $row->trash = 0;
                    $row->store();
                    $restored[] = $id;
                } else {
                    $warning = true;
                }
            }
        }

        // Restore also the items of the categories
        if ($restored !== []) {
            JArrayHelper::toInteger($restored);
            $db->setQuery('UPDATE #__k2_items SET trash = 0 WHERE catid IN ('.implode(',', $restored).') AND trash = 1');
            $db->query();
        }

        Joomla\CMS\Plugin\PluginHelper::importPlugin('finder');
        $dispatcher = JDispatcher::getInstance();
        $dispatcher->trigger('onFinderChangeState', ['com_k2.category', $cid, 1]);

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();
        if ($warning) {
            $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_SOME_OF_THE_CATEGORIES_HAVE_NOT_BEEN_RESTORED_BECAUSE_THEIR_PARENT_CATEGORY_IS_IN_TRASH'), 'notice');
        }

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_CATEGORIES_MOVED_TO_TRASH'));
        $app->redirect('index.php?option=com_k2&view=categories');
    }

    public function remove()
    {
        $app = Joomla\CMS\Factory::getApplication();
        jimport('joomla.filesystem.file');
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid');
        JArrayHelper::toInteger($cid);
        Joomla\CMS\Plugin\PluginHelper::importPlugin('finder');
        $dispatcher = JDispatcher::getInstance();
        $warningItems = false;
        $warningChildren = false;
        $cid = array_reverse($cid);
        $counter = count($cid);
        for ($i = 0; $i < $counter; $i++) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $row->load($cid[$i]);

            $query = 'SELECT COUNT(*) FROM #__k2_items WHERE catid='.$cid[$i];
            $db->setQuery($query);
            $num = $db->loadResult();

            if ($num > 0) {
                $warningItems = true;
            }

            $query = 'SELECT COUNT(*) FROM #__k2_categories WHERE parent='.$cid[$i];
            $db->setQuery($query);
            $children = $db->loadResult();

            if ($children > 0) {
                $warningChildren = true;
            }

            if ($children == 0 && $num == 0) {
                if ($row->image) {
                    Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/categories/'.$row->image);
                }

                $row->delete($cid[$i]);
                $dispatcher->trigger('onFinderAfterDelete', ['com_k2.category', $row]);
            }
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        if ($warningItems) {
            $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_SOME_OF_THE_CATEGORIES_HAVE_NOT_BEEN_DELETED_BECAUSE_THEY_HAVE_ITEMS'), 'notice');
        }

        if ($warningChildren) {
            $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_SOME_OF_THE_CATEGORIES_HAVE_NOT_BEEN_DELETED_BECAUSE_THEY_HAVE_CHILD_CATEGORIES'), 'notice');
        }

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_DELETE_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=categories');
    }

    public function categoriesTree($row = null, $hideTrashed = false, $hideUnpublished = true)
    {
        $db = Joomla\CMS\Factory::getDbo();
        $idCheck = isset($row->id) ? ' AND id != '.(int) $row->id : null;

        if (!isset($row->parent)) {
            if (is_null($row)) {
                $row = new stdClass();
            }

            $row->parent = 0;
        }

        $query = 'SELECT m.* FROM #__k2_categories m WHERE id > 0 '.$idCheck;

        if ($hideUnpublished) {
            $query .= ' AND published = 1';
        }

        if ($hideTrashed) {
            $query .= ' AND trash = 0';
        }

        $query .= ' ORDER BY parent, ordering';
        $db->setQuery($query);
        $mitems = $db->loadObjectList();
        $children = [];
        if ($mitems) {
            foreach ($mitems as $mitem) {
                if (K2_JVERSION != '15') {
                    $mitem->title = $mitem->language != '*' ? $mitem->name.' ['.$mitem->language.']' : $mitem->name;

                    $mitem->parent_id = $mitem->parent;
                }

                $pt = $mitem->parent;
                $list = @$children[$pt] ? $children[$pt] : [];
                $list[] = $mitem;
                $children[$pt] = $list;
            }
        }

        $list = Joomla\CMS\HTML\HTMLHelper::_('menu.treerecurse', 0, '', [], $children, 9999, 0, 0);
        $mitems = [];
        foreach ($list as $item) {
            $item->treename = JString::str_ireplace('&#160;', '- ', $item->treename);
            if (!$item->published) {
                $item->treename .= ' [**'.Joomla\CMS\Language\Text::_('K2_UNPUBLISHED_CATEGORY').'**]';
            }

            if ($item->trash) {
                $item->treename .= ' [**'.Joomla\CMS\Language\Text::_('K2_TRASHED_CATEGORY').'**]';
            }

            $mitems[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $item->id, $item->treename);
        }

        return $mitems;
    }

    public function copy($batch = false)
    {
        jimport('joomla.filesystem.file');
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        JArrayHelper::toInteger($cid);
        $copies = [];
        foreach ($cid as $id) {
            // Load source category
            $category = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $category->load($id);

            // Save target category
            $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $row = $category;
            $row->id = null;
            $row->name = Joomla\CMS\Language\Text::_('K2_COPY_OF').' '.$category->name;
            $row->published = 0;
            $row->store();
            $copies[] = $row->id;
            // Target image
            if ($category->image && Joomla\CMS\Filesystem\File::exists(JPATH_SITE.'/media/k2/categories/'.$category->image)) {
                Joomla\CMS\Filesystem\File::copy(JPATH_SITE.'/media/k2/categories/'.$category->image, JPATH_SITE.'/media/k2/categories/'.$row->id.'.jpg');
                $row->image = $row->id.'.jpg';
                $row->store();
            }
        }

        if ($batch) {
            return $copies;
        }

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_COPY_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=categories');

        return null;
    }

    public function saveBatch()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        $batchMode = JRequest::getCmd('batchMode');
        $catid = JRequest::getCmd('batchCategory');
        $access = JRequest::getCmd('batchAccess');
        $extraFieldsGroups = JRequest::getCmd('batchExtraFieldsGroups');
        $language = JRequest::getVar('batchLanguage');
        if ($batchMode == 'clone') {
            $cid = $this->copy(true);
        }

        if (in_array($catid, $cid)) {
            $app->redirect('index.php?option=com_k2&view=categories');

            return;
        }

        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $row->load($id);
            if (is_numeric($catid) && $catid != '') {
                $row->parent = $catid;
                $row->ordering = $row->getNextOrder('parent = '.(int) $catid.' AND published = 1');
            }

            if ($access) {
                $row->access = $access;
            }

            if (is_numeric($extraFieldsGroups) && $extraFieldsGroups != '') {
                $row->extraFieldsGroup = intval($extraFieldsGroups);
            }

            if ($language) {
                $row->language = $language;
            }

            $row->store();
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_BATCH_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=categories');
    }
}
