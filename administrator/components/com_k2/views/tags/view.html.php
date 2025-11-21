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

jimport('joomla.application.component.view');

class K2ViewTags extends K2View
{
    public function display($tpl = null)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $document = Joomla\CMS\Factory::getDocument();
        $user = Joomla\CMS\Factory::getUser();

        $option = K2Request::getCmd('option');
        $view = K2Request::getCmd('view');
        $task = K2Request::getCmd('task');

        $context = K2Request::getCmd('context');

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $this->assignRef('params', $params);

        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.$view.'.limitstart', 'limitstart', 0, 'int');
        $filter_order = $app->getUserStateFromRequest($option.$view.'filter_order', 'filter_order', 'id', 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($option.$view.'filter_order_Dir', 'filter_order_Dir', 'DESC', 'word');
        $filter_state = $app->getUserStateFromRequest($option.$view.'filter_state', 'filter_state', -1, 'int');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = \Joomla\String\StringHelper::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search));

        $model = $this->getModel();
        $total = $model->getTotal();
        $tags = $model->getData();

        if ($limitstart > $total - $limit) {
            $limitstart = max(0, (int) (ceil($total / $limit) - 1) * $limit);
            K2Request::setVar('limitstart', $limitstart);
        }

        foreach ($tags as $key => $tag) {
            $tag->status = (K2_JVERSION == '15') ? Joomla\CMS\HTML\HTMLHelper::_('grid.published', $tag, $key) : Joomla\CMS\HTML\HTMLHelper::_('jgrid.published', $tag->published, $key, '', $context != 'modalselector');
        }

        $this->assignRef('rows', $tags);

        jimport('joomla.html.pagination');
        $jPagination = new JPagination($total, $limitstart, $limit);
        $this->assignRef('page', $jPagination);

        $lists = [];
        $lists['search'] = $search;
        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order'] = $filter_order;

        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', -1, Joomla\CMS\Language\Text::_('K2_SELECT_STATE'));
        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 1, Joomla\CMS\Language\Text::_('K2_PUBLISHED'));
        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_UNPUBLISHED'));
        $lists['state'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $filter_state_options, 'filter_state', '', 'value', 'text', $filter_state);

        $this->assignRef('lists', $lists);

        // JS
        $document->addScriptDeclaration("
            Joomla.submitbutton = function(pressbutton) {
                if (pressbutton == 'remove') {
                    if (confirm('".Joomla\CMS\Language\Text::_('K2_ARE_YOU_SURE_YOU_WANT_TO_DELETE_SELECTED_TAGS', true)."')) {
                        submitform(pressbutton);
                    }
                } else {
                    submitform(pressbutton);
                }
            };
        ");

        // Toolbar
        Joomla\CMS\Toolbar\ToolbarHelper::title(Joomla\CMS\Language\Text::_('K2_TAGS'), 'k2.png');

        Joomla\CMS\Toolbar\ToolbarHelper::addNew();
        Joomla\CMS\Toolbar\ToolbarHelper::editList();
        Joomla\CMS\Toolbar\ToolbarHelper::publishList();
        Joomla\CMS\Toolbar\ToolbarHelper::unpublishList();
        Joomla\CMS\Toolbar\ToolbarHelper::deleteList('', 'remove', 'K2_DELETE');
        Joomla\CMS\Toolbar\ToolbarHelper::custom('removeOrphans', 'delete', 'delete', 'K2_DELETE_ORPHAN_TAGS', false);

        // Preferences (Parameters/Settings)
        if (K2_JVERSION != '15') {
            Joomla\CMS\Toolbar\ToolbarHelper::preferences('com_k2', '(window.innerHeight) * 0.9', '(window.innerWidth) * 0.7', 'K2_SETTINGS');
        } else {
            $toolbar = Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');
            $toolbar->appendButton('Popup', 'config', 'K2_SETTINGS', 'index.php?option=com_k2&view=settings', '(window.innerWidth) * 0.7', '(window.innerHeight) * 0.9');
        }

        $this->loadHelper('html');
        K2HelperHTML::subMenu();

        parent::display($tpl);
    }
}
