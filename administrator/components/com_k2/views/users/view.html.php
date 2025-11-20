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

class K2ViewUsers extends K2View
{
    public function display($tpl = null)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $document = Joomla\CMS\Factory::getDocument();
        $user = Joomla\CMS\Factory::getUser();
        $db = Joomla\CMS\Factory::getDbo();

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $this->assignRef('params', $params);

        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $task = JRequest::getCmd('task');

        $context = JRequest::getCmd('context');

        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.$view.'.limitstart', 'limitstart', 0, 'int');
        $filter_order = $app->getUserStateFromRequest($option.$view.'filter_order', 'filter_order', 'juser.name', 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($option.$view.'filter_order_Dir', 'filter_order_Dir', '', 'word');
        $filter_status = $app->getUserStateFromRequest($option.$view.'filter_status', 'filter_status', -1, 'int');
        $filter_group = $app->getUserStateFromRequest($option.$view.'filter_group', 'filter_group', '', 'string');
        $filter_group_k2 = $app->getUserStateFromRequest($option.$view.'filter_group_k2', 'filter_group_k2', '', 'string');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = JString::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search));

        K2Model::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/models');
        $model = K2Model::getInstance('Users', 'K2Model');
        $total = $model->getTotal();
        if ($limitstart > $total - $limit) {
            $limitstart = max(0, (int) (ceil($total / $limit) - 1) * $limit);
            JRequest::setVar('limitstart', $limitstart);
        }

        $users = $model->getData();
        $counter = count($users);
        for ($i = 0; $i < $counter; $i++) {
            $users[$i]->loggedin = $model->checkLogin($users[$i]->id);
            $users[$i]->profileID = $model->hasProfile($users[$i]->id);
            if ($users[$i]->profileID) {
                $db->setQuery('SELECT ip FROM #__k2_users WHERE id = '.$users[$i]->profileID);
                $users[$i]->ip = $db->loadResult();
            } else {
                $users[$i]->ip = '';
            }

            $users[$i]->lvisit = $users[$i]->lastvisitDate == '0000-00-00 00:00:00' ? false : $users[$i]->lastvisitDate;

            $users[$i]->link = Joomla\CMS\Router\Route::_('index.php?option=com_k2&view=user&cid='.$users[$i]->id);
            if (K2_JVERSION == '15') {
                $users[$i]->published = $users[$i]->loggedin;
                $users[$i]->loggedInStatus = strip_tags(Joomla\CMS\HTML\HTMLHelper::_('grid.published', $users[$i], $i), '<img>');
                $users[$i]->blockStatus = '';
                if ($users[$i]->block) {
                    $users[$i]->blockStatus .= '<a title="'.Joomla\CMS\Language\Text::_('K2_ENABLE').'" onclick="return listItemTask(\'cb'.$i.',\'enable\')" href="#"><img alt="'.Joomla\CMS\Language\Text::_('K2_ENABLED').'" src="images/publish_x.png"></a>';
                } else {
                    $users[$i]->blockStatus .= '<a title="'.Joomla\CMS\Language\Text::_('K2_DISABLE').'" onclick="return listItemTask(\'cb'.$i.',\'disable\')" href="#"><img alt="'.Joomla\CMS\Language\Text::_('K2_DISABLED').'" src="images/tick.png"></a>';
                }

                if ($context == 'modalselector') {
                    $users[$i]->blockStatus = strip_tags($users[$i]->blockStatus, '<img>');
                }
            } else {
                $states = [1 => ['', 'K2_LOGGED_IN', 'K2_LOGGED_IN', 'K2_LOGGED_IN', false, 'publish', 'publish'], 0 => ['', 'K2_NOT_LOGGED_IN', 'K2_NOT_LOGGED_IN', 'K2_NOT_LOGGED_IN', false, 'unpublish', 'unpublish']];
                $users[$i]->loggedInStatus = Joomla\CMS\HTML\HTMLHelper::_('jgrid.state', $states, $users[$i]->loggedin, $i, '', false);
                $states = [
                    0 => ['disable', 'K2_ENABLED', 'K2_DISABLE', 'K2_ENABLED', false, 'publish', 'publish'],
                    1 => ['enable', 'K2_DISABLED', 'K2_ENABLE', 'K2_DISABLED', false, 'unpublish', 'unpublish']];
                $users[$i]->blockStatus = Joomla\CMS\HTML\HTMLHelper::_('jgrid.state', $states, $users[$i]->block, $i, '', $context != 'modalselector');
            }
        }

        $this->assignRef('rows', $users);

        jimport('joomla.html.pagination');
        $jPagination = new JPagination($total, $limitstart, $limit);
        $this->assignRef('page', $jPagination);

        $lists = [];
        $lists['search'] = $search;
        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order'] = $filter_order;

        $filter_status_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', -1, Joomla\CMS\Language\Text::_('K2_SELECT_STATE'));
        $filter_status_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_ENABLED'));
        $filter_status_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 1, Joomla\CMS\Language\Text::_('K2_BLOCKED'));
        $lists['status'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $filter_status_options, 'filter_status', '', 'value', 'text', $filter_status);

        $userGroups = $model->getUserGroups();
        $groups[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '0', Joomla\CMS\Language\Text::_('K2_SELECT_JOOMLA_GROUP'));

        foreach ($userGroups as $userGroup) {
            $groups[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $userGroup->value, $userGroup->text);
        }

        $lists['filter_group'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $groups, 'filter_group', '', 'value', 'text', $filter_group);

        $K2userGroups = $model->getUserGroups('k2');
        $K2groups[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '0', Joomla\CMS\Language\Text::_('K2_SELECT_K2_GROUP'));

        foreach ($K2userGroups as $K2userGroup) {
            $K2groups[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $K2userGroup->id, $K2userGroup->name);
        }

        $lists['filter_group_k2'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $K2groups, 'filter_group_k2', '', 'value', 'text', $filter_group_k2);

        $this->assignRef('lists', $lists);
        $dateFormat = K2_JVERSION != '15' ? Joomla\CMS\Language\Text::_('K2_J16_DATE_FORMAT') : Joomla\CMS\Language\Text::_('K2_DATE_FORMAT');

        $this->assignRef('dateFormat', $dateFormat);

        $template = $app->getTemplate();
        $this->assignRef('template', $template);

        if ($app->isClient('administrator')) {
            // JS
            $document->addScriptDeclaration("
                var K2Language = ['".Joomla\CMS\Language\Text::_('K2_REPORT_USER_WARNING', true)."'];

                \$K2(document).ready(function() {
                    \$K2('#K2ImportUsersButton').click(function(event) {
                        var answer = confirm('".Joomla\CMS\Language\Text::_('K2_WARNING_YOU_ARE_ABOUT_TO_IMPORT_JOOMLA_USERS_TO_K2_GENERATING_CORRESPONDING_K2_USER_GROUPS_IF_YOU_HAVE_EXECUTED_THIS_OPERATION_BEFORE_DUPLICATE_CONTENT_MAY_BE_PRODUCED', true)."');
                        if (!answer) {
                            event.preventDefault();
                        }
                    });
                });
            ");

            // Toolbar
            $toolbar = Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');
            Joomla\CMS\Toolbar\ToolbarHelper::title(Joomla\CMS\Language\Text::_('K2_USERS'), 'k2.png');

            Joomla\CMS\Toolbar\ToolbarHelper::editList();
            Joomla\CMS\Toolbar\ToolbarHelper::publishList('enable', 'K2_ENABLE');
            Joomla\CMS\Toolbar\ToolbarHelper::unpublishList('disable', 'K2_DISABLE');
            Joomla\CMS\Toolbar\ToolbarHelper::deleteList('K2_WARNING_YOU_ARE_ABOUT_TO_DELETE_THE_SELECTED_USERS_PERMANENTLY_FROM_THE_SYSTEM', 'delete', 'K2_DELETE');
            Joomla\CMS\Toolbar\ToolbarHelper::deleteList('K2_ARE_YOU_SURE_YOU_WANT_TO_RESET_SELECTED_USERS', 'remove', 'K2_RESET_USER_DETAILS');
            Joomla\CMS\Toolbar\ToolbarHelper::custom('move', 'move.png', 'move_f2.png', 'K2_MOVE', true);

            $canImport = false;
            $canImport = K2_JVERSION == '15' ? $user->gid > 23 : $user->authorise('core.admin', 'com_k2');

            if ($canImport && !$params->get('hideImportButton')) {
                $buttonUrl = Joomla\CMS\Uri\Uri::base().'index.php?option=com_k2&amp;view=users&amp;task=import';
                $buttonText = Joomla\CMS\Language\Text::_('K2_IMPORT_JOOMLA_USERS');
                if (K2_JVERSION == '30') {
                    $button = '<a id="K2ImportUsersButton" class="btn btn-small" href="'.$buttonUrl.'"><i class="icon-archive "></i>'.$buttonText.'</a>';
                } else {
                    $button = '<a id="K2ImportUsersButton" href="'.$buttonUrl.'"><span class="icon-32-archive" title="'.$buttonText.'"></span>'.$buttonText.'</a>';
                }

                $toolbar->appendButton('Custom', $button);
            }

            $this->loadHelper('html');
            K2HelperHTML::subMenu();

            // Preferences (Parameters/Settings)
            if (K2_JVERSION != '15') {
                Joomla\CMS\Toolbar\ToolbarHelper::preferences('com_k2', '(window.innerHeight) * 0.9', '(window.innerWidth) * 0.7', 'K2_SETTINGS');
            } else {
                $toolbar->appendButton('Popup', 'config', 'K2_SETTINGS', 'index.php?option=com_k2&view=settings', '(window.innerWidth) * 0.7', '(window.innerHeight) * 0.9');
            }
        }

        $isAdmin = $app->isClient('administrator');
        $this->assignRef('isAdmin', $isAdmin);

        // Head includes
        K2HelperHTML::loadHeadIncludes(true, false, true, true);
        if ($app->isClient('site')) {
            // CSS
            $document->addStyleSheet(Joomla\CMS\Uri\Uri::root(true).'/templates/system/css/general.css');
            $document->addStyleSheet(Joomla\CMS\Uri\Uri::root(true).'/templates/system/css/system.css');
        }

        parent::display($tpl);
    }

    public function move()
    {
        $app = Joomla\CMS\Factory::getApplication();

        $cid = JRequest::getVar('cid');
        JArrayHelper::toInteger($cid);
        Joomla\CMS\Table\Table::addIncludePath(JPATH_COMPONENT.'/tables');

        foreach ($cid as $id) {
            $row = Joomla\CMS\Factory::getUser($id);
            $rows[] = $row;
        }

        $this->assignRef('rows', $rows);

        $model = $this->getModel('users');
        $lists = [];
        $userGroups = $model->getUserGroups();
        $groups[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '', Joomla\CMS\Language\Text::_('K2_DO_NOT_CHANGE'));
        foreach ($userGroups as $userGroup) {
            $groups[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $userGroup->value, Joomla\CMS\Language\Text::_($userGroup->text));
        }

        $fieldName = 'group';
        $attributes = 'size="10"';
        if (K2_JVERSION != '15') {
            $attributes .= 'multiple="multiple"';
            $fieldName .= '[]';
        }

        $lists['group'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $groups, $fieldName, $attributes, 'value', 'text', '');

        $K2userGroups = $model->getUserGroups('k2');
        $K2groups[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '0', Joomla\CMS\Language\Text::_('K2_DO_NOT_CHANGE'));
        foreach ($K2userGroups as $K2userGroup) {
            $K2groups[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $K2userGroup->id, $K2userGroup->name);
        }

        $lists['k2group'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $K2groups, 'k2group', 'size="10"', 'value', 'text', 0);

        $this->assignRef('lists', $lists);

        // Toolbar
        Joomla\CMS\Toolbar\ToolbarHelper::title(Joomla\CMS\Language\Text::_('K2_MOVE_USERS'), 'k2.png');

        Joomla\CMS\Toolbar\ToolbarHelper::custom('saveMove', 'save.png', 'save_f2.png', 'K2_SAVE', false);
        Joomla\CMS\Toolbar\ToolbarHelper::custom('cancelMove', 'cancel.png', 'cancel_f2.png', 'K2_CANCEL', false);

        parent::display();
    }
}
