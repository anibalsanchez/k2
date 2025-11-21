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

class K2ViewCategories extends K2View
{
    public $lists;

    public $filter_trash;

    public function display($tpl = null)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $document = Joomla\CMS\Factory::getDocument();
        $user = Joomla\CMS\Factory::getUser();

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $this->assignRef('params', $params);

        $option = K2Request::getCmd('option');
        $view = K2Request::getCmd('view');

        $context = K2Request::getCmd('context');

        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.$view.'.limitstart', 'limitstart', 0, 'int');

        $filter_order = $app->getUserStateFromRequest($option.$view.'filter_order', 'filter_order', 'c.ordering', 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($option.$view.'filter_order_Dir', 'filter_order_Dir', '', 'word');
        $filter_trash = $app->getUserStateFromRequest($option.$view.'filter_trash', 'filter_trash', 0, 'int');
        $filter_category = $app->getUserStateFromRequest($option.$view.'filter_category', 'filter_category', 0, 'int');
        $filter_state = $app->getUserStateFromRequest($option.$view.'filter_state', 'filter_state', -1, 'int');

        $language = $app->getUserStateFromRequest($option.$view.'language', 'language', '', 'string');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = \Joomla\String\StringHelper::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-.,:!?\'"()]/u', '', $search));

        $model = $this->getModel();
        $categories = $model->getData();
        $total = $model->getTotal();

        $task = K2Request::getCmd('task');
        if ($limitstart > $total - $limit) {
            $limitstart = max(0, (int) (ceil($total / $limit) - 1) * $limit);
            K2Request::setVar('limitstart', $limitstart);
        }

        // JS
        $document->addScriptDeclaration("
            var K2SelectItemsError = '".Joomla\CMS\Language\Text::_('K2_SELECT_SOME_ITEMS_FIRST', true)."';
            Joomla.submitbutton = function(pressbutton) {
                if (pressbutton == 'trash') {
                    var answer = confirm('".Joomla\CMS\Language\Text::_('K2_WARNING_YOU_ARE_ABOUT_TO_TRASH_THE_SELECTED_CATEGORIES_THEIR_CHILDREN_CATEGORIES_AND_ALL_THEIR_INCLUDED_ITEMS', true)."')
                    if (answer) {
                        submitform(pressbutton);
                    } else {
                        return;
                    }
                } else {
                    submitform(pressbutton);
                }
            };
        ");

        if (K2_JVERSION != '15') {
            $langs = Joomla\CMS\Language\LanguageHelper::getLanguages();
            $langsMapping = [];
            $langsMapping['*'] = Joomla\CMS\Language\Text::_('K2_ALL');
            foreach ($langs as $lang) {
                $langsMapping[$lang->lang_code] = $lang->title;
            }
        }

        $categoryModel = K2Model::getInstance('Category', 'K2Model');
        $counter = count($categories);
        for ($i = 0; $i < $counter; $i++) {
            $categories[$i]->status = (K2_JVERSION == '15') ? Joomla\CMS\HTML\HTMLHelper::_('grid.published', $categories[$i], $i) : Joomla\CMS\HTML\HTMLHelper::_('jgrid.published', $categories[$i]->published, $i, '', $filter_trash == 0 && $context != 'modalselector');
            if ($params->get('showItemsCounterAdmin')) {
                $categories[$i]->numOfItems = $categoryModel->countCategoryItems($categories[$i]->id);
                $categories[$i]->numOfTrashedItems = $categoryModel->countCategoryItems($categories[$i]->id, 1);
            }

            if (K2_JVERSION == '30') {
                $categories[$i]->canChange = $user->authorise('core.edit.state', 'com_k2.category.'.$categories[$i]->id);
            }

            // Detect the category template
            if (K2_JVERSION != '15') {
                $categoryParams = json_decode($categories[$i]->params);
                $categories[$i]->template = $categoryParams->theme;
                $categories[$i]->language = $categories[$i]->language ?: '*';
                if (isset($langsMapping)) {
                    $categories[$i]->language = $langsMapping[$categories[$i]->language];
                }
            } elseif (function_exists('parse_ini_string')) {
                $categoryParams = parse_ini_string($categories[$i]->params);
                $categories[$i]->template = $categoryParams['theme'];
            } else {
                $categoryParams = new JParameter($categories[$i]->params);
                $categories[$i]->template = $categoryParams->get('theme');
            }

            if (!$categories[$i]->template) {
                $categories[$i]->template = 'default';
            }
        }

        $this->assignRef('rows', $categories);

        // Show message for trash entries in Categories
        if (count($categories) && $filter_trash) {
            $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_ALL_TRASHED_ITEMS_IN_A_CATEGORY_MUST_BE_DELETED_FIRST'));
        }

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

        $filter_trash_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_CURRENT'));
        $filter_trash_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 1, Joomla\CMS\Language\Text::_('K2_TRASHED'));
        $lists['trash'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $filter_trash_options, 'filter_trash', '', 'value', 'text', $filter_trash);

        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', -1, Joomla\CMS\Language\Text::_('K2_SELECT_STATE'));
        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 1, Joomla\CMS\Language\Text::_('K2_PUBLISHED'));
        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_UNPUBLISHED'));
        $lists['state'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $filter_state_options, 'filter_state', '', 'value', 'text', $filter_state);

        require_once JPATH_ADMINISTRATOR.'/components/com_k2/models/categories.php';
        $categoriesModel = K2Model::getInstance('Categories', 'K2Model');
        $categories_option[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_SELECT_CATEGORY'));
        $categoriesFilter = $categoriesModel->categoriesTree(null, true, false);
        $categoriesTree = $categoriesFilter;
        $categories_options = @array_merge($categories_option, $categoriesFilter);
        $lists['categories'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $categories_options, 'filter_category', '', 'value', 'text', $filter_category);

        // Batch Operations
        $extraFieldsModel = K2Model::getInstance('ExtraFields', 'K2Model');
        $extraFieldsGroups = $extraFieldsModel->getGroups(true); // Fetch entire extra field group list
        $options = [];
        $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '', '- '.Joomla\CMS\Language\Text::_('K2_LEAVE_UNCHANGED').' -');
        $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '0', Joomla\CMS\Language\Text::_('K2_NONE_ONSELECTLISTS'));
        foreach ($extraFieldsGroups as $extraFieldGroup) {
            $name = $extraFieldGroup->name;
            $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $extraFieldGroup->id, $name);
        }

        $lists['batchExtraFieldsGroups'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $options, 'batchExtraFieldsGroups', '', 'value', 'text', null);

        array_unshift($categoriesTree, Joomla\CMS\HTML\HTMLHelper::_('select.option', '0', Joomla\CMS\Language\Text::_('K2_NONE_ONSELECTLISTS')));
        array_unshift($categoriesTree, Joomla\CMS\HTML\HTMLHelper::_('select.option', '', '- '.Joomla\CMS\Language\Text::_('K2_LEAVE_UNCHANGED').' -'));

        $lists['batchCategories'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $categoriesTree, 'batchCategory', '', 'value', 'text', null);

        $lists['batchAccess'] = version_compare(JVERSION, '2.5', 'ge') ? Joomla\CMS\HTML\HTMLHelper::_('access.level', 'batchAccess', null, '', [Joomla\CMS\HTML\HTMLHelper::_('select.option', '', '- '.Joomla\CMS\Language\Text::_('K2_LEAVE_UNCHANGED').' -')]) : str_replace('size="3"', '', Joomla\CMS\HTML\HTMLHelper::_('list.accesslevel', ''));

        if (version_compare(JVERSION, '2.5.0', 'ge')) {
            $languages = Joomla\CMS\HTML\HTMLHelper::_('contentlanguage.existing', true, true);
            array_unshift($languages, Joomla\CMS\HTML\HTMLHelper::_('select.option', '', '- '.Joomla\CMS\Language\Text::_('K2_LEAVE_UNCHANGED').' -'));
            $lists['batchLanguage'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $languages, 'batchLanguage', '', 'value', 'text', null);
        }

        if (version_compare(JVERSION, '1.6.0', 'ge')) {
            $languages = Joomla\CMS\HTML\HTMLHelper::_('contentlanguage.existing', true, true);
            array_unshift($languages, Joomla\CMS\HTML\HTMLHelper::_('select.option', '', Joomla\CMS\Language\Text::_('K2_SELECT_LANGUAGE')));
            $lists['language'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $languages, 'language', '', 'value', 'text', $language);
        }

        $this->assignRef('lists', $lists);

        // Toolbar
        Joomla\CMS\Toolbar\ToolbarHelper::title(Joomla\CMS\Language\Text::_('K2_CATEGORIES'), 'k2.png');
        $toolbar = Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');

        if ($filter_trash == 1) {
            Joomla\CMS\Toolbar\ToolbarHelper::deleteList('K2_ARE_YOU_SURE_YOU_WANT_TO_DELETE_SELECTED_CATEGORIES', 'remove', 'K2_DELETE');
            Joomla\CMS\Toolbar\ToolbarHelper::custom('restore', 'publish.png', 'publish_f2.png', 'K2_RESTORE', true);
        } else {
            Joomla\CMS\Toolbar\ToolbarHelper::addNew();
            Joomla\CMS\Toolbar\ToolbarHelper::editList();
            Joomla\CMS\Toolbar\ToolbarHelper::publishList();
            Joomla\CMS\Toolbar\ToolbarHelper::unpublishList();
            Joomla\CMS\Toolbar\ToolbarHelper::trash('trash');
            Joomla\CMS\Toolbar\ToolbarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'K2_COPY', true);
            if (K2_JVERSION == '30') {
                $batchButton = '<a id="K2BatchButton" class="btn btn-small" href="#"><i class="icon-edit"></i>'.Joomla\CMS\Language\Text::_('K2_BATCH').'</a>';
            } else {
                $batchButton = '<a id="K2BatchButton" href="#"><span class="icon-32-edit" title="'.Joomla\CMS\Language\Text::_('K2_BATCH').'"></span>'.Joomla\CMS\Language\Text::_('K2_BATCH').'</a>';
            }

            $toolbar->appendButton('Custom', $batchButton);
        }

        // Preferences (Parameters/Settings)
        if (K2_JVERSION != '15') {
            Joomla\CMS\Toolbar\ToolbarHelper::preferences('com_k2', '(window.innerHeight) * 0.9', '(window.innerWidth) * 0.7', 'K2_SETTINGS');
        } else {
            $toolbar->appendButton('Popup', 'config', 'K2_SETTINGS', 'index.php?option=com_k2&view=settings', '(window.innerWidth) * 0.7', '(window.innerHeight) * 0.9');
        }

        $this->loadHelper('html');
        K2HelperHTML::subMenu();

        $this->assignRef('filter_trash', $filter_trash);
        $template = $app->getTemplate();
        $this->assignRef('template', $template);
        $ordering = (($this->lists['order'] == 'c.ordering' || $this->lists['order'] == 'c.parent, c.ordering') && (!$this->filter_trash));
        $this->assignRef('ordering', $ordering);

        // Joomla 3.x drag-n-drop sorting variables
        if (K2_JVERSION == '30') {
            if ($ordering) {
                Joomla\CMS\HTML\HTMLHelper::_('sortablelist.sortable', 'k2CategoriesList', 'adminForm', strtolower($this->lists['order_Dir']), 'index.php?option=com_k2&view=categories&task=saveorder&format=raw');
            }

            $document->addScriptDeclaration('
                Joomla.orderTable = function() {
                    table = document.getElementById("sortTable");
                    direction = document.getElementById("directionTable");
                    order = table.options[table.selectedIndex].value;
                    if (order != "'.$this->lists['order'].'") {
                        dirn = "asc";
                    } else {
                        dirn = direction.options[direction.selectedIndex].value;
                    }
                    Joomla.tableOrdering(order, dirn, "");
                }
            ');
        }

        parent::display($tpl);
    }
}
