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

class K2ModelItems extends K2Model
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
        $filter_order = $app->getUserStateFromRequest($option.$view.'filter_order', 'filter_order', 'i.id', 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($option.$view.'filter_order_Dir', 'filter_order_Dir', 'DESC', 'word');
        $filter_trash = $app->getUserStateFromRequest($option.$view.'filter_trash', 'filter_trash', 0, 'int');
        $filter_featured = $app->getUserStateFromRequest($option.$view.'filter_featured', 'filter_featured', -1, 'int');
        $filter_category = $app->getUserStateFromRequest($option.$view.'filter_category', 'filter_category', 0, 'int');
        $filter_author = $app->getUserStateFromRequest($option.$view.'filter_author', 'filter_author', 0, 'int');
        $filter_state = $app->getUserStateFromRequest($option.$view.'filter_state', 'filter_state', -1, 'int');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = JString::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-.,:!?\'"()]/u', '', $search));

        $tag = $app->getUserStateFromRequest($option.$view.'tag', 'tag', 0, 'int');
        $language = $app->getUserStateFromRequest($option.$view.'language', 'language', '', 'string');

        // --- Query containing initial SELECT ---
        $queryStart = '/* Backend / K2 / Items */ SELECT i.*, g.name AS groupname, c.name AS category, v.name AS author, w.name AS moderator, u.name AS editor';

        if (K2_JVERSION != '15') {
            $queryStart = str_ireplace('g.name', 'g.title', $queryStart);
        }

        // --- Query containing FROM to WHERE ---
        $query = ' FROM #__k2_items AS i FORCE INDEX (idx_items_common_backend)
            STRAIGHT_JOIN #__k2_categories AS c ON c.id = i.catid
            LEFT JOIN #__groups AS g ON g.id = i.access
            LEFT JOIN #__users AS u ON u.id = i.checked_out
            LEFT JOIN #__users AS v ON v.id = i.created_by
            LEFT JOIN #__users AS w ON w.id = i.modified_by';

        if (K2_JVERSION != '15') {
            $query = str_ireplace('#__groups', '#__viewlevels', $query);
        }

        if ($params->get('showTagFilter') && $tag) {
            $query .= ' LEFT JOIN #__k2_tags_xref AS tags_xref ON tags_xref.itemID = i.id';
        }

        $query .= ' WHERE i.trash = '.$filter_trash;

        if ($search !== '' && $search !== '0') {
            // Detect exact search phrase using double quotes in search string
            $exact = str_starts_with($search, '"') && str_ends_with($search, '"');

            // Now completely strip double quotes
            $search = trim(str_replace('"', '', $search));

            // Escape remaining string
            $escaped = (K2_JVERSION == '15') ? $db->getEscaped($search, true) : $db->escape($search, true);

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
                        $searchPerTerm[] = '
                            LOWER(i.title) LIKE '.$quotedWord.' OR
                            LOWER(i.introtext) LIKE '.$quotedWord.' OR
                            LOWER(i.`fulltext`) LIKE '.$quotedWord.' OR
                            LOWER(i.extra_fields_search) LIKE '.$quotedWord.' OR
                            LOWER(i.image_caption) LIKE '.$quotedWord.' OR
                            LOWER(i.image_credits) LIKE '.$quotedWord.' OR
                            LOWER(i.video_caption) LIKE '.$quotedWord.' OR
                            LOWER(i.video_credits) LIKE '.$quotedWord.' OR
                            LOWER(i.metadesc) LIKE '.$quotedWord.' OR
                            LOWER(i.metakey) LIKE '.$quotedWord.'
                        ';
                    }

                    $query .= implode(' OR ', $searchPerTerm);
                    $query .= ')';
                } else {
                    foreach ($quoted as $quotedWord) {
                        $query .= ' AND LOWER(i.title) LIKE '.$quotedWord;
                    }
                }
            }
            // Single word or exact phrase to search for (wrapped in double quotes in the search block)
            else {
                $quoted = $db->Quote('%'.$escaped.'%', false);

                if ($params->get('adminSearch') == 'full') {
                    $query .= ' AND (
                        LOWER(i.title) LIKE '.$quoted.' OR
                        LOWER(i.introtext) LIKE '.$quoted.' OR
                        LOWER(i.`fulltext`) LIKE '.$quoted.' OR
                        LOWER(i.extra_fields_search) LIKE '.$quoted.' OR
                        LOWER(i.image_caption) LIKE '.$quoted.' OR
                        LOWER(i.image_credits) LIKE '.$quoted.' OR
                        LOWER(i.video_caption) LIKE '.$quoted.' OR
                        LOWER(i.video_credits) LIKE '.$quoted.' OR
                        LOWER(i.metadesc) LIKE '.$quoted.' OR
                        LOWER(i.metakey) LIKE '.$quoted.'
                    )';
                } else {
                    $query .= ' AND LOWER(i.title) LIKE '.$quoted;
                }
            }
        }

        if ($filter_state > -1) {
            $query .= ' AND i.published = '.$filter_state;
        }

        if ($filter_featured > -1) {
            $query .= ' AND i.featured = '.$filter_featured;
        }

        if ($filter_category > 0) {
            if ($params->get('showChildCatItems')) {
                K2Model::addIncludePath(JPATH_SITE.'/components/com_k2/models');
                $itemListModel = K2Model::getInstance('Itemlist', 'K2Model');
                $categories = $itemListModel->getCategoryTree($filter_category);
                $sql = @implode(',', $categories);
                $query .= sprintf(' AND i.catid IN (%s)', $sql);
            } else {
                $query .= ' AND i.catid = '.$filter_category;
            }
        }

        if ($filter_author > 0) {
            $query .= ' AND i.created_by = '.$filter_author;
        }

        if ($params->get('showTagFilter') && $tag) {
            $query .= ' AND tags_xref.tagID = '.$tag;
        }

        if ($language) {
            $query .= ' AND (i.language = '.$db->Quote($language)." OR i.language = '*')";
        }

        // --- Query containing GROUP BY and ORDER BY ---
        $queryEnd = '';

        if ($filter_order == 'i.ordering') {
            $queryEnd .= ' ORDER BY i.catid, i.ordering '.$filter_order_Dir;
        } else {
            $queryEnd .= sprintf(' ORDER BY %s %s', $filter_order, $filter_order_Dir);
        }

        // --- Final query ---
        $combinedQuery = $queryStart.$query.$queryEnd;

        // Plugin Events
        Joomla\CMS\Plugin\PluginHelper::importPlugin('k2');
        $dispatcher = JDispatcher::getInstance();

        // Trigger K2 plugins
        $dispatcher->trigger('onK2BeforeSetQuery', [&$combinedQuery]);

        $db->setQuery($combinedQuery, $limitstart, $limit);
        $rows = $db->loadObjectList();

        // --- Row counter ---
        if (count($rows) > 0) {
            $countQuery = '/* Backend / K2 / Items Count */ SELECT COUNT(*)'.$query;
            $db->setQuery($countQuery);
            $this->getTotal = $db->loadResult();
        }

        return $rows;
    }

    public function getTotal()
    {
        return $this->getTotal;
    }

    public function publish()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $row->load($id);
            $row->published = 1;
            $row->store();
        }

        // Plugins Events
        Joomla\CMS\Plugin\PluginHelper::importPlugin('content');
        Joomla\CMS\Plugin\PluginHelper::importPlugin('finder');
        $dispatcher = JDispatcher::getInstance();

        // Trigger content & finder plugins when state changes
        $dispatcher->trigger('onContentChangeState', ['com_k2.item', $cid, 1]);
        $dispatcher->trigger('onFinderChangeState', ['com_k2.item', $cid, 1]);

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=items&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=items');
        }
    }

    public function unpublish()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $row->load($id);
            $row->published = 0;
            $row->store();
        }

        // Plugins Events
        Joomla\CMS\Plugin\PluginHelper::importPlugin('content');
        Joomla\CMS\Plugin\PluginHelper::importPlugin('finder');
        $dispatcher = JDispatcher::getInstance();

        // Trigger content & finder plugins when state changes
        $dispatcher->trigger('onContentChangeState', ['com_k2.item', $cid, 0]);
        $dispatcher->trigger('onFinderChangeState', ['com_k2.item', $cid, 0]);

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=items&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=items');
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
            $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $row->load((int) $cid[$i]);
            $groupings[] = $row->catid;
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
                $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
                $row->reorder('catid = '.(int) $grouping.' AND trash=0');
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
        $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
        $row->load($cid[0]);
        $row->move(-1, 'catid = '.(int) $row->catid.' AND trash=0');
        if (!$params->get('disableCompactOrdering')) {
            $row->reorder('catid = '.(int) $row->catid.' AND trash=0');
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $msg = Joomla\CMS\Language\Text::_('K2_NEW_ORDERING_SAVED');
        $app->enqueueMessage($msg);
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=items&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=items');
        }
    }

    public function orderdown()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $cid = JRequest::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
        $row->load($cid[0]);
        $row->move(1, 'catid = '.(int) $row->catid.' AND trash=0');
        if (!$params->get('disableCompactOrdering')) {
            $row->reorder('catid = '.(int) $row->catid.' AND trash=0');
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $msg = Joomla\CMS\Language\Text::_('K2_NEW_ORDERING_SAVED');
        $app->enqueueMessage($msg);
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=items&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=items');
        }
    }

    public function savefeaturedorder()
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
            $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $row->load((int) $cid[$i]);
            $groupings[] = $row->catid;
            if ($row->featured_ordering != $order[$i]) {
                $row->featured_ordering = $order[$i];
                if (!$row->store()) {
                    JError::raiseError(500, $db->getErrorMsg());
                }
            }
        }

        if (!$params->get('disableCompactOrdering')) {
            $groupings = array_unique($groupings);
            foreach ($groupings as $grouping) {
                $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
                $row->reorder('featured = 1 AND trash=0', 'featured_ordering');
            }
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        return true;
    }

    public function featuredorderup()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $cid = JRequest::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
        $row->load($cid[0]);
        $row->move(-1, 'featured=1 AND trash=0', 'featured_ordering');
        if (!$params->get('disableCompactOrdering')) {
            $row->reorder('featured=1 AND trash=0', 'featured_ordering');
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $msg = Joomla\CMS\Language\Text::_('K2_NEW_ORDERING_SAVED');
        $app->enqueueMessage($msg);
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=items&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=items');
        }
    }

    public function featuredorderdown()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $cid = JRequest::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
        $row->load($cid[0]);
        $row->move(1, 'featured=1 AND trash=0', 'featured_ordering');
        if (!$params->get('disableCompactOrdering')) {
            $row->reorder('featured=1 AND trash=0', 'featured_ordering');
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $msg = Joomla\CMS\Language\Text::_('K2_NEW_ORDERING_SAVED');
        $app->enqueueMessage($msg);
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=items&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=items');
        }
    }

    public function accessregistered()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
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
        $app->redirect('index.php?option=com_k2&view=items');

        return null;
    }

    public function accessspecial()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
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
        $app->redirect('index.php?option=com_k2&view=items');

        return null;
    }

    public function accesspublic()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
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
        $app->redirect('index.php?option=com_k2&view=items');

        return null;
    }

    public function copy($batch = false)
    {
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $itemModel = K2Model::getInstance('Item', 'K2Model');
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid');
        JArrayHelper::toInteger($cid);
        $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
        $copies = [];
        $nullDate = $db->getNullDate();

        // Define media extensions
        $videoExtensions = [
            'avi',
            'm4v',
            'mkv',
            'mp4',
            'ogv',
            'webm',
        ];
        $audioExtensions = [
            'flac',
            'm4a',
            'mp3',
            'oga',
            'ogg',
            'wav',
        ];

        foreach ($cid as $id) {
            // Load source item
            $item = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $item->load($id);
            $item->id = (int) $item->id;

            // Source images
            $sourceImage = JPATH_ROOT.'/media/k2/items/src/'.md5('Image'.$item->id).'.jpg';
            $sourceImageXS = JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$item->id).'_XS.jpg';
            $sourceImageS = JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$item->id).'_S.jpg';
            $sourceImageM = JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$item->id).'_M.jpg';
            $sourceImageL = JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$item->id).'_L.jpg';
            $sourceImageXL = JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$item->id).'_XL.jpg';
            $sourceImageGeneric = JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$item->id).'_Generic.jpg';

            // Source gallery
            $sourceGallery = JPATH_ROOT.'/media/k2/galleries/'.$item->id;
            $sourceGalleryTag = $item->gallery;

            // Source media
            preg_match_all('#^{(.*?)}(.*?){#', $item->video, $matches, PREG_PATTERN_ORDER);

            $mediaType = $matches[1][0];
            $mediaFile = $matches[2][0];

            if (in_array($mediaType, $videoExtensions) || in_array($mediaType, $audioExtensions)) {
                // Videos
                if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/videos/'.$mediaFile.'.'.$mediaType)) {
                    $sourceMedia = $mediaFile.'.'.$mediaType;
                }

                // Audio
                if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/audio/'.$mediaFile.'.'.$mediaType)) {
                    $sourceMedia = $mediaFile.'.'.$mediaType;
                }
            }

            // Source tags
            $query = 'SELECT * FROM #__k2_tags_xref WHERE itemID='.$item->id;
            $db->setQuery($query);
            $sourceTags = $db->loadObjectList();

            // Source Attachments
            $sourceAttachments = $itemModel->getAttachments($item->id);

            // Save target item
            $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $row = $item;
            $row->id = null;
            $row->title = Joomla\CMS\Language\Text::_('K2_COPY_OF').' '.$item->title;
            $row->hits = 0;
            $row->published = 0;
            $datenow = Joomla\CMS\Factory::getDate();
            $row->created = K2_JVERSION == '15' ? $datenow->toMySQL() : $datenow->toSql();
            $row->modified = $nullDate;
            $row->store();
            $copies[] = $row->id;

            // Target images
            if (Joomla\CMS\Filesystem\File::exists($sourceImage)) {
                Joomla\CMS\Filesystem\File::copy($sourceImage, JPATH_ROOT.'/media/k2/items/src/'.md5('Image'.$row->id).'.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists($sourceImageXS)) {
                Joomla\CMS\Filesystem\File::copy($sourceImageXS, JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_XS.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists($sourceImageS)) {
                Joomla\CMS\Filesystem\File::copy($sourceImageS, JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_S.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists($sourceImageM)) {
                Joomla\CMS\Filesystem\File::copy($sourceImageM, JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_M.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists($sourceImageL)) {
                Joomla\CMS\Filesystem\File::copy($sourceImageL, JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_L.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists($sourceImageXL)) {
                Joomla\CMS\Filesystem\File::copy($sourceImageXL, JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_XL.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists($sourceImageGeneric)) {
                Joomla\CMS\Filesystem\File::copy($sourceImageGeneric, JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_Generic.jpg');
            }

            // Target gallery
            if ($sourceGalleryTag) {
                if (JString::strpos($sourceGalleryTag, 'http://') || JString::strpos($sourceGalleryTag, 'https://')) {
                    $row->gallery = $sourceGalleryTag;
                } else {
                    $row->gallery = '{gallery}'.$row->id.'{/gallery}';
                    if (Joomla\CMS\Filesystem\Folder::exists($sourceGallery)) {
                        Joomla\CMS\Filesystem\Folder::copy($sourceGallery, JPATH_ROOT.'/media/k2/galleries/'.$row->id);
                    }
                }
            }

            // Target media
            if (isset($sourceMedia)) {
                if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/videos/'.$sourceMedia)) {
                    Joomla\CMS\Filesystem\File::copy(JPATH_ROOT.'/media/k2/videos/'.$sourceMedia, JPATH_ROOT.'/media/k2/videos/'.$row->id.'.'.$mediaType);
                    $row->video = $row->id.'.'.$mediaType;
                    //$row->video = '{'.$mediaType.'}'.$row->id.'{/'.$mediaType.'}';
                }

                if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/audio/'.$sourceMedia)) {
                    Joomla\CMS\Filesystem\File::copy(JPATH_ROOT.'/media/k2/audio/'.$sourceMedia, JPATH_ROOT.'/media/k2/audio/'.$row->id.'.'.$mediaType);
                    $row->video = $row->id.'.'.$mediaType;
                    //$row->video = '{'.$mediaType.'}'.$row->id.'{/'.$mediaType.'}';
                }
            }

            // Target attachments
            $path = $params->get('attachmentsFolder', null);
            $savepath = is_null($path) ? JPATH_ROOT.'/media/k2/attachments' : $path;

            foreach ($sourceAttachments as $sourceAttachment) {
                if (Joomla\CMS\Filesystem\File::exists($savepath.'/'.$sourceAttachment->filename)) {
                    Joomla\CMS\Filesystem\File::copy($savepath.'/'.$sourceAttachment->filename, $savepath.'/'.$row->id.'_'.$sourceAttachment->filename);
                    $attachmentRow = Joomla\CMS\Table\Table::getInstance('K2Attachment', 'Table');
                    $attachmentRow->itemID = $row->id;
                    $attachmentRow->title = $sourceAttachment->title;
                    $attachmentRow->titleAttribute = $sourceAttachment->titleAttribute;
                    $attachmentRow->filename = $row->id.'_'.$sourceAttachment->filename;
                    $attachmentRow->hits = 0;
                    $attachmentRow->store();
                }
            }

            // Target tags
            foreach ($sourceTags as $sourceTag) {
                $query = 'INSERT INTO #__k2_tags_xref (`id`, `tagID`, `itemID`) VALUES (NULL, '.(int) $sourceTag->tagID.', '.(int) $row->id.')';
                $db->setQuery($query);
                $db->query();
            }

            $row->store();
        }

        if ($batch) {
            return $copies;
        }

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_COPY_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=items');

        return null;
    }

    public function featured()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid');
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $row->load($id);
            if ($row->featured == 1) {
                $row->featured = 0;
            } else {
                $row->featured = 1;
                $row->featured_ordering = 1;
            }

            $row->store();
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_ITEMS_CHANGED'));
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=items&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=items');
        }
    }

    public function trash()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid');
        JArrayHelper::toInteger($cid);
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $row->load($id);
            $row->trash = 1;
            $row->store();
        }

        // Plugins Events
        Joomla\CMS\Plugin\PluginHelper::importPlugin('content');
        Joomla\CMS\Plugin\PluginHelper::importPlugin('finder');
        $dispatcher = JDispatcher::getInstance();

        // Trigger content & finder plugins when state changes
        $dispatcher->trigger('onContentChangeState', ['com_k2.item', $cid, -2]);
        $dispatcher->trigger('onFinderChangeState', ['com_k2.item', $cid, 0]);

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_ITEMS_MOVED_TO_TRASH'));
        $app->redirect('index.php?option=com_k2&view=items');
    }

    public function restore()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid');
        $warning = false;
        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $row->load($id);
            $query = 'SELECT COUNT(*) FROM #__k2_categories WHERE id='.(int) $row->catid.' AND trash = 0';
            $db->setQuery($query);
            $result = $db->loadResult();
            if ($result) {
                $row->trash = 0;
                $row->store();
            } else {
                $warning = true;
            }
        }

        // Plugins Events
        Joomla\CMS\Plugin\PluginHelper::importPlugin('content');
        Joomla\CMS\Plugin\PluginHelper::importPlugin('finder');
        $dispatcher = JDispatcher::getInstance();

        // Trigger content & finder plugins when state changes
        $dispatcher->trigger('onContentChangeState', ['com_k2.item', $cid, $row->published]);
        $dispatcher->trigger('onFinderChangeState', ['com_k2.item', $cid, 1]);

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        if ($warning) {
            $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_SOME_OF_THE_ITEMS_HAVE_NOT_BEEN_RESTORED_BECAUSE_THEY_BELONG_TO_A_CATEGORY_WHICH_IS_IN_TRASH'), 'notice');
        }

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_ITEMS_RESTORED'));
        $app->redirect('index.php?option=com_k2&view=items');
    }

    public function remove()
    {
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $itemModel = K2Model::getInstance('Item', 'K2Model');
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid');

        // Plugin Events
        Joomla\CMS\Plugin\PluginHelper::importPlugin('content');
        Joomla\CMS\Plugin\PluginHelper::importPlugin('finder');
        $dispatcher = JDispatcher::getInstance();

        // Define media extensions
        $videoExtensions = [
            'avi',
            'm4v',
            'mkv',
            'mp4',
            'ogv',
            'webm',
        ];
        $audioExtensions = [
            'flac',
            'm4a',
            'mp3',
            'oga',
            'ogg',
            'wav',
        ];

        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $row->load($id);
            $row->id = (int) $row->id;

            // Delete images
            if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/items/src/'.md5('Image'.$row->id).'.jpg')) {
                Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/items/src/'.md5('Image'.$row->id).'.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_XS.jpg')) {
                Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_XS.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_S.jpg')) {
                Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_S.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_M.jpg')) {
                Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_M.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_L.jpg')) {
                Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_L.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_XL.jpg')) {
                Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_XL.jpg');
            }

            if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_Generic.jpg')) {
                Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/items/cache/'.md5('Image'.$row->id).'_Generic.jpg');
            }

            // Delete gallery
            if (Joomla\CMS\Filesystem\Folder::exists(JPATH_ROOT.'/media/k2/galleries/'.$row->id)) {
                Joomla\CMS\Filesystem\Folder::delete(JPATH_ROOT.'/media/k2/galleries/'.$row->id);
            }

            // Delete media
            preg_match_all('#^{(.*?)}(.*?){#', $row->video, $matches, PREG_PATTERN_ORDER);

            $mediaType = $matches[1][0];
            $mediaFile = $matches[2][0];

            if (in_array($mediaType, $videoExtensions) || in_array($mediaType, $audioExtensions)) {
                // Videos
                if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/videos/'.$mediaFile.'.'.$mediaType)) {
                    Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/videos/'.$mediaFile.'.'.$mediaType);
                }

                // Audio
                if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/audio/'.$mediaFile.'.'.$mediaType)) {
                    Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/audio/'.$mediaFile.'.'.$mediaType);
                }
            }

            // Delete attachments
            $path = $params->get('attachmentsFolder', null);
            $savepath = is_null($path) ? JPATH_ROOT.'/media/k2/attachments' : $path;

            $attachments = $itemModel->getAttachments($row->id);

            foreach ($attachments as $attachment) {
                if (Joomla\CMS\Filesystem\File::exists($savepath.'/'.$attachment->filename)) {
                    Joomla\CMS\Filesystem\File::delete($savepath.'/'.$attachment->filename);
                }
            }

            $query = 'DELETE FROM #__k2_attachments WHERE itemID='.$row->id;
            $db->setQuery($query);
            $db->query();

            // Delete tags
            $query = 'DELETE FROM #__k2_tags_xref WHERE itemID='.$row->id;
            $db->setQuery($query);
            $db->query();

            // Delete comments
            $query = 'DELETE FROM #__k2_comments WHERE itemID='.$row->id;
            $db->setQuery($query);
            $db->query();

            $row->delete($id);

            // Trigger content & finder plugins after the delete event
            $dispatcher->trigger('onContentAfterDelete', ['com_k2.item', $row]);
            $dispatcher->trigger('onFinderAfterDelete', ['com_k2.item', $row]);
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_DELETE_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=items');
    }

    public function import()
    {
        $app = Joomla\CMS\Factory::getApplication();
        jimport('joomla.filesystem.file');
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT * FROM #__sections';
        $db->setQuery($query);
        $sections = $db->loadObjectList();

        $query = 'SELECT COUNT(*) FROM #__k2_items';
        $db->setQuery($query);
        $result = $db->loadResult();
        $preserveItemIDs = !(bool) $result;

        $xml = new JSimpleXML();
        $xml->loadFile(JPATH_COMPONENT.'/models/category.xml');

        $categoryParams = class_exists('JParameter') ? new JParameter('') : new JRegistry('');

        foreach ($xml->document->params as $paramGroup) {
            foreach ($paramGroup->param as $param) {
                if ($param->attributes('type') != 'spacer' && $param->attributes('name')) {
                    $categoryParams->set($param->attributes('name'), $param->attributes('default'));
                }
            }
        }

        $categoryParams = $categoryParams->toString();

        $xml = new JSimpleXML();
        $xml->loadFile(JPATH_COMPONENT.'/models/item.xml');

        $itemParams = class_exists('JParameter') ? new JParameter('') : new JRegistry('');

        foreach ($xml->document->params as $paramGroup) {
            foreach ($paramGroup->param as $param) {
                if ($param->attributes('type') != 'spacer' && $param->attributes('name')) {
                    $itemParams->set($param->attributes('name'), $param->attributes('default'));
                }
            }
        }

        $itemParams = $itemParams->toString();

        $query = 'SELECT id, name FROM #__k2_tags';
        $db->setQuery($query);
        $tags = $db->loadObjectList();

        if (is_null($tags)) {
            $tags = [];
        }

        foreach ($sections as $section) {
            $K2Category = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $K2Category->name = $section->title;
            $K2Category->alias = $section->title;
            $K2Category->description = $section->description;
            $K2Category->parent = 0;
            $K2Category->published = $section->published;
            $K2Category->access = $section->access;
            $K2Category->ordering = $section->ordering;
            $K2Category->image = $section->image;
            $K2Category->trash = 0;
            $K2Category->params = $categoryParams;
            $K2Category->check();
            $K2Category->store();
            if (Joomla\CMS\Filesystem\File::exists(JPATH_SITE.'/images/stories/'.$section->image)) {
                Joomla\CMS\Filesystem\File::copy(JPATH_SITE.'/images/stories/'.$section->image, JPATH_SITE.'/media/k2/categories/'.$K2Category->image);
            }

            $query = 'SELECT * FROM #__categories WHERE section = '.(int) $section->id;
            $db->setQuery($query);
            $categories = $db->loadObjectList();

            foreach ($categories as $category) {
                $K2Subcategory = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
                $K2Subcategory->name = $category->title;
                $K2Subcategory->alias = $category->title;
                $K2Subcategory->description = $category->description;
                $K2Subcategory->parent = $K2Category->id;
                $K2Subcategory->published = $category->published;
                $K2Subcategory->access = $category->access;
                $K2Subcategory->ordering = $category->ordering;
                $K2Subcategory->image = $category->image;
                $K2Subcategory->trash = 0;
                $K2Subcategory->params = $categoryParams;
                $K2Subcategory->check();
                $K2Subcategory->store();
                if (Joomla\CMS\Filesystem\File::exists(JPATH_SITE.'/images/stories/'.$category->image)) {
                    Joomla\CMS\Filesystem\File::copy(JPATH_SITE.'/images/stories/'.$category->image, JPATH_SITE.'/media/k2/categories/'.$K2Subcategory->image);
                }

                $query = 'SELECT article.*, xref.content_id
                FROM #__content AS article
                LEFT JOIN #__content_frontpage AS xref ON article.id = xref.content_id
                WHERE catid = '.(int) $category->id;
                $db->setQuery($query);
                $items = $db->loadObjectList();

                foreach ($items as $item) {
                    $K2Item = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
                    $K2Item->title = $item->title;
                    $K2Item->alias = $item->title;
                    $K2Item->catid = $K2Subcategory->id;
                    if ($item->state < 0) {
                        $K2Item->trash = 1;
                    } else {
                        $K2Item->trash = 0;
                        $K2Item->published = $item->state;
                    }

                    $K2Item->featured = ($item->content_id) ? 1 : 0;
                    $K2Item->introtext = $item->introtext;
                    $K2Item->fulltext = $item->fulltext;
                    $K2Item->created = $item->created;
                    $K2Item->created_by = $item->created_by;
                    $K2Item->created_by_alias = $item->created_by_alias;
                    $K2Item->modified = $item->modified;
                    $K2Item->modified_by = $item->modified_by;
                    $K2Item->publish_up = $item->publish_up;
                    $K2Item->publish_down = $item->publish_down;
                    $K2Item->access = $item->access;
                    $K2Item->ordering = $item->ordering;
                    $K2Item->hits = $item->hits;
                    $K2Item->metadesc = $item->metadesc;
                    $K2Item->metadata = $item->metadata;
                    $K2Item->metakey = $item->metakey;
                    $K2Item->params = $itemParams;
                    $K2Item->check();
                    if ($preserveItemIDs) {
                        $K2Item->id = $item->id;
                        $db->insertObject('#__k2_items', $K2Item);
                    } else {
                        $K2Item->store();
                    }

                    if (!empty($item->metakey)) {
                        $itemTags = explode(',', $item->metakey);
                        foreach ($itemTags as $itemTag) {
                            $itemTag = JString::trim($itemTag);
                            if (in_array($itemTag, JArrayHelper::getColumn($tags, 'name'))) {
                                $query = 'SELECT id FROM #__k2_tags WHERE name='.$db->Quote($itemTag);
                                $db->setQuery($query);
                                $id = $db->loadResult();
                                $query = sprintf('INSERT INTO #__k2_tags_xref (`id`, `tagID`, `itemID`) VALUES (NULL, %s, %s)', $id, $K2Item->id);
                                $db->setQuery($query);
                                $db->query();
                            } else {
                                $K2Tag = Joomla\CMS\Table\Table::getInstance('K2Tag', 'Table');
                                $K2Tag->name = $itemTag;
                                $K2Tag->published = 1;
                                $K2Tag->store();
                                $tags[] = $K2Tag;
                                $query = sprintf('INSERT INTO #__k2_tags_xref (`id`, `tagID`, `itemID`) VALUES (NULL, %s, %s)', $K2Tag->id, $K2Item->id);
                                $db->setQuery($query);
                                $db->query();
                            }
                        }
                    }
                }
            }
        }

        // Handle uncategorized articles
        $query = 'SELECT * FROM #__content WHERE sectionid = 0';
        $db->setQuery($query);
        $items = $db->loadObjectList();

        if ($items) {
            $K2Uncategorised = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $K2Uncategorised->name = 'Uncategorized';
            $K2Uncategorised->alias = 'Uncategorized';
            $K2Uncategorised->parent = 0;
            $K2Uncategorised->published = 1;
            $K2Uncategorised->access = 0;
            $K2Uncategorised->ordering = 0;
            $K2Uncategorised->trash = 0;
            $K2Uncategorised->params = $categoryParams;
            $K2Uncategorised->check();
            $K2Uncategorised->store();

            foreach ($items as $item) {
                $K2Item = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
                $K2Item->title = $item->title;
                $K2Item->alias = $item->title;
                $K2Item->catid = $K2Uncategorised->id;
                if ($item->state < 0) {
                    $K2Item->trash = 1;
                } else {
                    $K2Item->trash = 0;
                    $K2Item->published = $item->state;
                }

                $K2Item->introtext = $item->introtext;
                $K2Item->fulltext = $item->fulltext;
                $K2Item->created = $item->created;
                $K2Item->created_by = $item->created_by;
                $K2Item->created_by_alias = $item->created_by_alias;
                $K2Item->modified = $item->modified;
                $K2Item->modified_by = $item->modified_by;
                $K2Item->publish_up = $item->publish_up;
                $K2Item->publish_down = $item->publish_down;
                $K2Item->access = $item->access;
                $K2Item->ordering = $item->ordering;
                $K2Item->hits = $item->hits;
                $K2Item->metadesc = $item->metadesc;
                $K2Item->metadata = $item->metadata;
                $K2Item->metakey = $item->metakey;
                $K2Item->params = $itemParams;
                $K2Item->check();
                if ($preserveItemIDs) {
                    $K2Item->id = $item->id;
                    $db->insertObject('#__k2_items', $K2Item);
                } else {
                    $K2Item->store();
                }

                if (!empty($item->metakey)) {
                    $itemTags = explode(',', $item->metakey);
                    foreach ($itemTags as $itemTag) {
                        $itemTag = JString::trim($itemTag);
                        if (in_array($itemTag, JArrayHelper::getColumn($tags, 'name'))) {
                            $query = 'SELECT id FROM #__k2_tags WHERE name='.$db->Quote($itemTag);
                            $db->setQuery($query);
                            $id = $db->loadResult();
                            $query = sprintf('INSERT INTO #__k2_tags_xref (`id`, `tagID`, `itemID`) VALUES (NULL, %s, %s)', $id, $K2Item->id);
                            $db->setQuery($query);
                            $db->query();
                        } else {
                            $K2Tag = Joomla\CMS\Table\Table::getInstance('K2Tag', 'Table');
                            $K2Tag->name = $itemTag;
                            $K2Tag->published = 1;
                            $K2Tag->store();
                            $tags[] = $K2Tag;
                            $query = sprintf('INSERT INTO #__k2_tags_xref (`id`, `tagID`, `itemID`) VALUES (NULL, %s, %s)', $K2Tag->id, $K2Item->id);
                            $db->setQuery($query);
                            $db->query();
                        }
                    }
                }
            }
        }

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_IMPORT_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=items');
    }

    public function importJ16()
    {
        jimport('joomla.filesystem.file');
        jimport('joomla.html.parameter');
        jimport('joomla.utilities.xmlelement');
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();

        $query = 'SELECT COUNT(*) FROM #__k2_categories';
        $db->setQuery($query);
        $result = $db->loadResult();
        $preserveCategoryIDs = !(bool) $result;

        $query = 'SELECT COUNT(*) FROM #__k2_items';
        $db->setQuery($query);
        $result = $db->loadResult();
        $preserveItemIDs = !(bool) $result;

        $xml = new JXMLElement(Joomla\CMS\Filesystem\File::read(JPATH_COMPONENT.'/models/category.xml'));
        $categoryParams = class_exists('JParameter') ? new JParameter('') : new JRegistry('');
        foreach ($xml->params as $paramGroup) {
            foreach ($paramGroup->param as $param) {
                if ((string) $param->attributes()->type !== 'spacer' && (string) $param->attributes()->name) {
                    $categoryParams->set((string) $param->attributes()->name, (string) $param->attributes()->default);
                }
            }
        }

        $categoryParams = $categoryParams->toString();

        $xml = new JXMLElement(Joomla\CMS\Filesystem\File::read(JPATH_COMPONENT.'/models/item.xml'));
        $itemParams = class_exists('JParameter') ? new JParameter('') : new JRegistry('');
        foreach ($xml->params as $paramGroup) {
            foreach ($paramGroup->param as $param) {
                if ((string) $param->attributes()->type !== 'spacer' && (string) $param->attributes()->name) {
                    $itemParams->set((string) $param->attributes()->name, (string) $param->attributes()->default);
                }
            }
        }

        $itemParams = $itemParams->toString();

        $query = 'SELECT id, name FROM #__k2_tags';
        $db->setQuery($query);
        $tags = $db->loadObjectList();

        if (is_null($tags)) {
            $tags = [];
        }

        $query = "SELECT * FROM #__categories WHERE extension = 'com_content'";
        $db->setQuery($query);
        $categories = $db->loadObjectList();
        $mapping = [];
        foreach ($categories as $category) {
            $category->params = json_decode($category->params);
            $category->image = $category->params->image;
            $K2Category = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $K2Category->name = $category->title;
            $K2Category->alias = $category->title;
            $K2Category->description = $category->description;
            $K2Category->parent = $category->parent_id;
            if ($K2Category->parent == 1) {
                $K2Category->parent = 0;
            }

            $K2Category->published = $category->published;
            $K2Category->access = $category->access;
            $K2Category->ordering = $K2Category->getNextOrder('parent='.(int) $category->parent_id);
            $K2Category->image = basename($category->image);
            $K2Category->trash = 0;
            $K2Category->language = $category->language;
            $K2Category->params = $categoryParams;
            $K2Category->check();
            if ($preserveCategoryIDs) {
                $K2Category->id = $category->id;
                $db->insertObject('#__k2_categories', $K2Category);
            } else {
                $K2Category->store();
                $mapping[$category->id] = $K2Category->id;
            }

            if ($K2Category->image && Joomla\CMS\Filesystem\File::exists(realpath(JPATH_SITE.'/'.$category->image))) {
                Joomla\CMS\Filesystem\File::copy(realpath(JPATH_SITE.'/'.$category->image), JPATH_SITE.'/media/k2/categories/'.$K2Category->image);
            }

            $query = 'SELECT article.*, xref.content_id
                FROM #__content AS article
                LEFT JOIN #__content_frontpage AS xref ON article.id = xref.content_id
                WHERE catid = '.(int) $category->id;
            $db->setQuery($query);
            $items = $db->loadObjectList();

            foreach ($items as $item) {
                $K2Item = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
                $K2Item->title = $item->title;
                $K2Item->alias = $item->title;
                $K2Item->catid = $K2Category->id;
                $K2Item->trash = $item->state < 0 ? 1 : 0;

                $K2Item->published = 1;
                if ($item->state == 0) {
                    $K2Item->published = 0;
                }

                $K2Item->featured = ($item->content_id) ? 1 : 0;
                $K2Item->introtext = $item->introtext;
                $K2Item->fulltext = $item->fulltext;
                $K2Item->created = $item->created;
                $K2Item->created_by = $item->created_by;
                $K2Item->created_by_alias = $item->created_by_alias;
                $K2Item->modified = $item->modified;
                $K2Item->modified_by = $item->modified_by;
                $K2Item->publish_up = $item->publish_up;
                $K2Item->publish_down = $item->publish_down;
                $K2Item->access = $item->access;
                $K2Item->ordering = $item->ordering;
                $K2Item->hits = $item->hits;
                $K2Item->metadesc = $item->metadesc;
                $K2Item->metadata = $item->metadata;
                $K2Item->metakey = $item->metakey;
                $K2Item->params = $itemParams;
                $K2Item->language = $item->language;
                $K2Item->check();

                if ($preserveItemIDs) {
                    $K2Item->id = $item->id;
                    $db->insertObject('#__k2_items', $K2Item);
                } else {
                    $K2Item->store();
                }

                $item->tags = [];
                if (class_exists('JHelperTags')) {
                    $tagsHelper = new JHelperTags();
                    $tagsHelper->getItemTags('com_content.article', $item->id);
                    $tags = $tagsHelper->itemTags;
                    foreach ($tags as $tag) {
                        $item->tags[] = $tag->title;
                    }
                }

                if (!empty($item->metakey) || count($item->tags)) {
                    $itemTags = array_merge(explode(',', $item->metakey), $item->tags);
                    $itemTags = array_filter($itemTags);
                    $itemTags = array_unique($itemTags);
                    foreach ($itemTags as $itemTag) {
                        $itemTag = JString::trim($itemTag);
                        if ($itemTag) {
                            // Check if the tag exists already, otherwise insert it as a K2 tag
                            $query = 'SELECT id FROM #__k2_tags WHERE name='.$db->Quote($itemTag);
                            $db->setQuery($query);
                            $id = $db->loadResult();
                            if ($id) {
                                $query = sprintf('INSERT INTO #__k2_tags_xref (`id`, `tagID`, `itemID`) VALUES (NULL, %s, %s)', $id, $K2Item->id);
                            } else {
                                $K2Tag = Joomla\CMS\Table\Table::getInstance('K2Tag', 'Table');
                                $K2Tag->name = $itemTag;
                                $K2Tag->published = 1;
                                $K2Tag->store();
                                $tags[] = $K2Tag;
                                $query = sprintf('INSERT INTO #__k2_tags_xref (`id`, `tagID`, `itemID`) VALUES (NULL, %s, %s)', $K2Tag->id, $K2Item->id);
                            }

                            $db->setQuery($query);
                            $db->query();

                            /*
                            // OLD
                            if (in_array($itemTag, JArrayHelper::getColumn($tags, 'name'))) {
                                $query = "SELECT id FROM #__k2_tags WHERE name=".$db->Quote($itemTag);
                                $db->setQuery($query);
                                $id = $db->loadResult();
                                if ($id) {
                                    $query = "INSERT INTO #__k2_tags_xref (`id`, `tagID`, `itemID`) VALUES (NULL, {$id}, {$K2Item->id})";
                                    $db->setQuery($query);
                                    $db->query();
                                }
                            } else {
                                $K2Tag = JTable::getInstance('K2Tag', 'Table');
                                $K2Tag->name = $itemTag;
                                $K2Tag->published = 1;
                                $K2Tag->store();
                                $tags[] = $K2Tag;
                                $query = "INSERT INTO #__k2_tags_xref (`id`, `tagID`, `itemID`) VALUES (NULL, {$K2Tag->id}, {$K2Item->id})";
                                $db->setQuery($query);
                                $db->query();
                            }
                            */
                        }
                    }
                }
            }
        }

        foreach ($mapping as $oldID => $newID) {
            $query = 'UPDATE #__k2_categories SET parent='.$newID.' WHERE parent='.$oldID;
            $db->setQuery($query);
            $db->query();
        }

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_IMPORT_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=items');
    }

    public function getItemsAuthors()
    {
        $db = $this->getDBO();
        $query = 'SELECT id, name, block
            FROM #__users
            WHERE id IN (SELECT DISTINCT created_by FROM #__k2_items)
            ORDER BY name';
        /*
        $query = "SELECT u.id, u.name, u.block
            FROM #__users as u
            RIGHT JOIN #__k2_items as i on u.id = i.created_by
            GROUP BY u.id
            ORDER BY u.name";
        */
        $db->setQuery($query);
        $rows = $db->loadObjectList();

        return $rows;
    }

    public function saveBatch()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        $batchMode = JRequest::getCmd('batchMode');
        $catid = JRequest::getInt('batchCategory');
        $access = JRequest::getCmd('batchAccess');
        $author = JRequest::getInt('batchAuthor');
        $language = JRequest::getVar('batchLanguage');
        if ($batchMode == 'clone') {
            $cid = $this->copy(true);
        }

        foreach ($cid as $id) {
            $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $row->load($id);
            if ($catid) {
                $row->catid = $catid;
                $row->ordering = $row->getNextOrder('catid = '.(int) $row->catid.' AND published = 1');
            }

            if ($access) {
                $row->access = $access;
            }

            if ($author) {
                $row->created_by = $author;
                $row->created_by_alias = '';
            }

            if ($language) {
                $row->language = $language;
            }

            $row->store();
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_BATCH_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=items');
    }
}
