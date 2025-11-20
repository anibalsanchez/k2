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

class K2ViewComments extends K2View
{
    public function display($tpl = null)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $document = Joomla\CMS\Factory::getDocument();
        $user = Joomla\CMS\Factory::getUser();
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $this->assignRef('params', $params);

        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.$view.'.limitstart', 'limitstart', 0, 'int');
        $filter_order = $app->getUserStateFromRequest($option.$view.'filter_order', 'filter_order', 'c.id', 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($option.$view.'filter_order_Dir', 'filter_order_Dir', 'DESC', 'word');
        $filter_state = $app->getUserStateFromRequest($option.$view.'filter_state', 'filter_state', -1, 'int');
        $filter_category = $app->getUserStateFromRequest($option.$view.'filter_category', 'filter_category', 0, 'int');
        $filter_author = $app->getUserStateFromRequest($option.$view.'filter_author', 'filter_author', 0, 'int');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = JString::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\"\.\@\-_]/u', '', $search));
        if ($app->isSite()) {
            $filter_author = $user->id;
            JRequest::setVar('filter_author', $user->id);
        }

        $this->loadHelper('html');

        // Head includes
        K2HelperHTML::loadHeadIncludes(true, false, true, true);

        // JS
        $document->addScriptDeclaration("
			var K2Language = [
				'".Joomla\CMS\Language\Text::_('K2_YOU_CANNOT_EDIT_TWO_COMMENTS_AT_THE_SAME_TIME', true)."',
				'".Joomla\CMS\Language\Text::_('K2_THIS_WILL_PERMANENTLY_DELETE_ALL_UNPUBLISHED_COMMENTS_ARE_YOU_SURE', true)."',
				'".Joomla\CMS\Language\Text::_('K2_REPORT_USER_WARNING', true)."'
			];

			Joomla.submitbutton = function(pressbutton) {
				if (pressbutton == 'remove') {
					if (document.adminForm.boxchecked.value==0) {
						alert('".Joomla\CMS\Language\Text::_('K2_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST_TO_DELETE', true)."');
						return false;
					}
					if (confirm('".Joomla\CMS\Language\Text::_('K2_ARE_YOU_SURE_YOU_WANT_TO_DELETE_SELECTED_COMMENTS', true)."')) {
						submitform(pressbutton);
					}
				} else if (pressbutton == 'deleteUnpublished') {
					if (confirm('".Joomla\CMS\Language\Text::_('K2_THIS_WILL_PERMANENTLY_DELETE_ALL_UNPUBLISHED_COMMENTS_ARE_YOU_SURE', true)."')) {
						submitform(pressbutton);
					}
				} else if (pressbutton == 'publish') {
					if (document.adminForm.boxchecked.value==0) {
						alert('".Joomla\CMS\Language\Text::_('K2_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST_TO_PUBLISH', true)."');
						return false;
					}
					submitform(pressbutton);
				} else if (pressbutton == 'unpublish') {
					if (document.adminForm.boxchecked.value==0) {
						alert('".Joomla\CMS\Language\Text::_('K2_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST_TO_UNPUBLISH', true)."');
						return false;
					}
					submitform(pressbutton);
				}  else {
					submitform(pressbutton);
				}
			};
		");

        K2Model::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/models');
        $model = K2Model::getInstance('Comments', 'K2Model');
        $total = $model->getTotal();
        $comments = $model->getData();

        if ($limitstart > $total - $limit) {
            $limitstart = max(0, (int) (ceil($total / $limit) - 1) * $limit);
            JRequest::setVar('limitstart', $limitstart);
        }

        $reportLink = $app->isAdmin() ? 'index.php?option=com_k2&view=user&task=report&id=' : 'index.php?option=com_k2&view=comments&task=reportSpammer&id=';
        foreach ($comments as $key => $comment) {
            $comment->reportUserLink = false;
            $comment->commenterLastVisitIP = null;
            if ($comment->userID) {
                $db = Joomla\CMS\Factory::getDbo();
                $db->setQuery('SELECT ip FROM #__k2_users WHERE userID = '.$comment->userID);
                $comment->commenterLastVisitIP = $db->loadResult();

                $commenter = Joomla\CMS\Factory::getUser($comment->userID);
                if ($commenter->name) {
                    $comment->userName = $commenter->name;
                }

                if ($app->isSite()) {
                    if (K2_JVERSION != '15') {
                        if ($user->authorise('core.admin', 'com_k2')) {
                            $comment->reportUserLink = Joomla\CMS\Router\Route::_($reportLink.$comment->userID);
                        }
                    } elseif ($user->gid > 24) {
                        $comment->reportUserLink = Joomla\CMS\Router\Route::_($reportLink.$comment->userID);
                    }
                } else {
                    $comment->reportUserLink = Joomla\CMS\Router\Route::_($reportLink.$comment->userID);
                }
            }

            if ($app->isSite()) {
                $comment->status = K2HelperHTML::stateToggler($comment, $key);
            } else {
                $comment->status = K2_JVERSION == '15' ? Joomla\CMS\HTML\HTMLHelper::_('grid.published', $comment, $key) : Joomla\CMS\HTML\HTMLHelper::_('jgrid.published', $comment->published, $key);
            }
        }

        $this->assignRef('rows', $comments);

        // Pagination
        jimport('joomla.html.pagination');
        $jPagination = new JPagination($total, $limitstart, $limit);
        $this->assignRef('page', $jPagination);

        $lists = [];

        // Detect exact search phrase using double quotes in search string
        if (str_starts_with($search, '"') && str_ends_with($search, '"')) {
            $lists['search'] = '"'.trim(str_replace('"', '', $search)).'"';
        } else {
            $lists['search'] = trim(str_replace('"', '', $search));
        }

        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order'] = $filter_order;

        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', -1, Joomla\CMS\Language\Text::_('K2_SELECT_STATE'));
        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 1, Joomla\CMS\Language\Text::_('K2_PUBLISHED'));
        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_UNPUBLISHED'));
        $lists['state'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $filter_state_options, 'filter_state', '', 'value', 'text', $filter_state);

        require_once JPATH_ADMINISTRATOR.'/components/com_k2/models/categories.php';
        $categoriesModel = K2Model::getInstance('Categories', 'K2Model');
        $categories_option[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_SELECT_CATEGORY'));
        $categories = $categoriesModel->categoriesTree(null, true, false);
        $categories_options = @array_merge($categories_option, $categories);
        $lists['categories'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $categories_options, 'filter_category', '', 'value', 'text', $filter_category);

        require_once JPATH_ADMINISTRATOR.'/components/com_k2/models/items.php';
        $itemsModel = K2Model::getInstance('Items', 'K2Model');
        $authors = $itemsModel->getItemsAuthors();
        $options = [];
        $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_NO_USER'));
        foreach ($authors as $author) {
            $name = $author->name;
            if ($author->block) {
                $name .= ' ['.Joomla\CMS\Language\Text::_('K2_USER_DISABLED').']';
            }

            $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $author->id, $name);
        }

        $lists['authors'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $options, 'filter_author', '', 'value', 'text', $filter_author);
        $this->assignRef('lists', $lists);
        $dateFormat = K2_JVERSION != '15' ? Joomla\CMS\Language\Text::_('K2_J16_DATE_FORMAT') : Joomla\CMS\Language\Text::_('K2_DATE_FORMAT');

        $this->assignRef('dateFormat', $dateFormat);

        if ($app->isAdmin()) {
            // Toolbar
            $toolbar = Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');
            Joomla\CMS\Toolbar\ToolbarHelper::title(Joomla\CMS\Language\Text::_('K2_COMMENTS'), 'k2.png');

            Joomla\CMS\Toolbar\ToolbarHelper::publishList();
            Joomla\CMS\Toolbar\ToolbarHelper::unpublishList();
            Joomla\CMS\Toolbar\ToolbarHelper::deleteList('', 'remove', 'K2_DELETE');
            Joomla\CMS\Toolbar\ToolbarHelper::custom('deleteUnpublished', 'delete', 'delete', 'K2_DELETE_ALL_UNPUBLISHED', false);

            // Preferences (Parameters/Settings)
            if (K2_JVERSION != '15') {
                Joomla\CMS\Toolbar\ToolbarHelper::preferences('com_k2', '(window.innerHeight) * 0.9', '(window.innerWidth) * 0.7', 'K2_SETTINGS');
            } else {
                $toolbar->appendButton('Popup', 'config', 'K2_SETTINGS', 'index.php?option=com_k2&view=settings', '(window.innerWidth) * 0.7', '(window.innerHeight) * 0.9');
            }

            K2HelperHTML::subMenu();

            $userEditLink = Joomla\CMS\Uri\Uri::base().'index.php?option=com_k2&view=user&cid=';
            $this->assignRef('userEditLink', $userEditLink);
        }

        if ($app->isSite()) {
            // Enforce the "system" template in the frontend
            JRequest::setVar('template', 'system');

            // CSS
            $document->addStyleSheet(Joomla\CMS\Uri\Uri::root(true).'/templates/system/css/general.css');
            $document->addStyleSheet(Joomla\CMS\Uri\Uri::root(true).'/templates/system/css/system.css');
        }

        parent::display($tpl);
    }
}
