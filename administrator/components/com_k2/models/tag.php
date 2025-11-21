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

class K2ModelTag extends K2Model
{
    public function getData()
    {
        $cid = K2Request::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2Tag', 'Table');
        $row->load($cid);

        return $row;
    }

    public function save()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $row = Joomla\CMS\Table\Table::getInstance('K2Tag', 'Table');

        // Plugin Events
        Joomla\CMS\Plugin\PluginHelper::importPlugin('k2');
        $dispatcher = K2Dispatcher::getInstance();

        if (!$row->bind(K2Request::getPost())) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=tags');
        }

        $isNew = !(bool) $row->id;

        // Trigger K2 plugins
        $dispatcher->trigger('onBeforeK2Save', [&$row, $isNew]);

        if (!$row->check()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=tag&cid='.$row->id);
        }

        if (!$row->store()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=tags');
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        // Trigger K2 plugins
        $dispatcher->trigger('onAfterK2Save', [&$row, $isNew]);

        switch (K2Request::getCmd('task')) {
            case 'apply':
                $msg = Joomla\CMS\Language\Text::_('K2_CHANGES_TO_TAG_SAVED');
                $link = 'index.php?option=com_k2&view=tag&cid='.$row->id;
                break;
            case 'saveAndNew':
                $msg = Joomla\CMS\Language\Text::_('K2_TAG_SAVED');
                $link = 'index.php?option=com_k2&view=tag';
                break;
            case 'save':
            default:
                $msg = Joomla\CMS\Language\Text::_('K2_TAG_SAVED');
                $link = 'index.php?option=com_k2&view=tags';
                break;
        }

        $app->enqueueMessage($msg);
        $app->redirect($link);
    }

    public function addTag()
    {
        $app = Joomla\CMS\Factory::getApplication();

        $user = Joomla\CMS\Factory::getUser();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        if ($user->gid < 24 && $params->get('lockTags')) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        $tag = K2Request::getString('tag');
        $tag = str_replace('-', '', $tag);
        $tag = str_replace('.', '', $tag);

        $response = new stdClass();
        $response->name = $tag;

        if (empty($tag)) {
            $response->msg = Joomla\CMS\Language\Text::_('K2_YOU_NEED_TO_ENTER_A_TAG', true);
            echo json_encode($response);
            $app->close();
        }

        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT COUNT(*) FROM #__k2_tags WHERE name='.$db->Quote($tag);
        $db->setQuery($query);
        $result = $db->loadResult();

        if ($result > 0) {
            $response->msg = Joomla\CMS\Language\Text::_('K2_TAG_ALREADY_EXISTS', true);
            echo json_encode($response);
            $app->close();
        }

        $row = Joomla\CMS\Table\Table::getInstance('K2Tag', 'Table');
        $row->name = $tag;
        $row->published = 1;
        $row->store();

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $response->id = $row->id;
        $response->status = 'success';
        $response->msg = Joomla\CMS\Language\Text::_('K2_TAG_ADDED_TO_AVAILABLE_TAGS_LIST', true);
        echo json_encode($response);

        $app->close();
    }

    public function tags()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $word = K2Request::getString('q', null);
        $id = K2Request::getInt('id');
        if (K2_JVERSION == '15') {
            $word = $db->Quote($db->getEscaped($word, true).'%', false);
        } else {
            $word = $db->Quote($db->escape($word, true).'%', false);
        }

        if ($id) {
            $query = 'SELECT id,name FROM #__k2_tags WHERE name LIKE '.$word;
            $db->setQuery($query);
            $result = $db->loadObjectList();
        } else {
            $query = 'SELECT name FROM #__k2_tags WHERE name LIKE '.$word;
            $db->setQuery($query);
            $result = (K2_JVERSION == '30') ? $db->loadColumn() : $db->loadResultArray();
        }

        echo json_encode($result);
        $app->close();
    }
}
