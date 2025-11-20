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

class K2ViewItems extends K2View
{
    public $lists;

    public $filter_featured;

    public $filter_trash;

    public function display($tpl = null)
    {
        jimport('joomla.filesystem.file');
        $app = Joomla\CMS\Factory::getApplication();
        $document = Joomla\CMS\Factory::getDocument();

        $user = Joomla\CMS\Factory::getUser();
        $option = K2Request::getCmd('option');
        $view = K2Request::getCmd('view');

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $this->assignRef('params', $params);

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

        $db = Joomla\CMS\Factory::getDbo();
        $nullDate = $db->getNullDate();

        // JS
        $document->addScriptDeclaration("
            var K2SelectItemsError = '".Joomla\CMS\Language\Text::_('K2_SELECT_SOME_ITEMS_FIRST', true)."';
            \$K2(document).ready(function() {
                \$K2('#K2ImportContentButton').click(function(event) {
                    var answer = confirm('".Joomla\CMS\Language\Text::_('K2_WARNING_YOU_ARE_ABOUT_TO_IMPORT_ALL_SECTIONS_CATEGORIES_AND_ARTICLES_FROM_JOOMLAS_CORE_CONTENT_COMPONENT_COM_CONTENT_INTO_K2_IF_THIS_IS_THE_FIRST_TIME_YOU_IMPORT_CONTENT_TO_K2_AND_YOUR_SITE_HAS_MORE_THAN_A_FEW_THOUSAND_ARTICLES_THE_PROCESS_MAY_TAKE_A_FEW_MINUTES_IF_YOU_HAVE_EXECUTED_THIS_OPERATION_BEFORE_DUPLICATE_CONTENT_MAY_BE_PRODUCED', true)."');
                    if (!answer) {
                        event.preventDefault();
                    }
                });
            });
        ");

        $this->assignRef('nullDate', $nullDate);

        if (K2_JVERSION == '30' && $filter_featured == 1 && $filter_order == 'i.ordering') {
            $filter_order = 'i.featured_ordering';
            K2Request::setVar('filter_order', 'i.featured_ordering');
        }

        if (K2_JVERSION == '30' && $filter_featured != 1 && $filter_order == 'i.featured_ordering') {
            $filter_order = 'i.ordering';
            K2Request::setVar('filter_order', 'i.ordering');
        }

        $model = $this->getModel();
        $items = $model->getData();
        $total = $model->getTotal();
        if ($limitstart > $total - $limit) {
            $limitstart = max(0, (int) (ceil($total / $limit) - 1) * $limit);
            K2Request::setVar('limitstart', $limitstart);
        }

        if (K2_JVERSION != '15') {
            $langs = Joomla\CMS\Language\LanguageHelper::getLanguages();
            $langsMapping = [];
            $langsMapping['*'] = Joomla\CMS\Language\Text::_('K2_ALL');
            foreach ($langs as $lang) {
                $langsMapping[$lang->lang_code] = $lang->title;
            }
        }

        foreach ($items as $key => $item) {
            if (K2_JVERSION != '15') {
                $item->status = Joomla\CMS\HTML\HTMLHelper::_('jgrid.published', $item->published, $key, '', ($filter_trash == 0), 'cb', $item->publish_up, $item->publish_down);
                $states = [
                    1 => [
                        'featured',
                        'K2_FEATURED',
                        'K2_REMOVE_FEATURED_FLAG',
                        'K2_FEATURED',
                        false,
                        'publish',
                        'publish',
                    ],
                    0 => [
                        'featured',
                        'K2_NOT_FEATURED',
                        'K2_FLAG_AS_FEATURED',
                        'K2_NOT_FEATURED',
                        false,
                        'unpublish',
                        'unpublish',
                    ],
                ];
                $item->featuredStatus = Joomla\CMS\HTML\HTMLHelper::_('jgrid.state', $states, $item->featured, $key, '', $filter_trash == 0);
                $item->canChange = $user->authorise('core.edit.state', 'com_k2.item.'.$item->id);
                $item->language = $item->language ?: '*';
                if (isset($langsMapping)) {
                    $item->language = $langsMapping[$item->language];
                }
            } else {
                $now = Joomla\CMS\Factory::getDate();
                $config = Joomla\CMS\Factory::getConfig();
                $publish_up = Joomla\CMS\Factory::getDate($item->publish_up);
                $publish_down = Joomla\CMS\Factory::getDate($item->publish_down);
                $publish_up->setOffset($config->getValue('config.offset'));
                $publish_down->setOffset($config->getValue('config.offset'));
                $img = 'tick.png';
                if ($now->toUnix() <= $publish_up->toUnix() && $item->published == 1) {
                    $img = 'publish_y.png';
                } elseif (($now->toUnix() <= $publish_down->toUnix() || $item->publish_down == $nullDate) && $item->published == 1) {
                    $img = 'tick.png';
                } elseif ($now->toUnix() > $publish_down->toUnix() && $item->published == 1) {
                    $img = 'publish_r.png';
                }

                $item->status = Joomla\CMS\HTML\HTMLHelper::_('grid.published', $item, $key, $img);
                if ($filter_trash) {
                    $item->status = strip_tags($item->status, '<img>');
                }

                $item->featuredStatus = '';
                if (!$filter_trash) {
                    $tmpTitle = $item->featured ? Joomla\CMS\Language\Text::_('K2_REMOVE_FEATURED_FLAG') : Joomla\CMS\Language\Text::_('K2_FLAG_AS_FEATURED');
                    $item->featuredStatus .= '<a href="javascript:void(0);" onclick="return listItemTask(\'cb'.$key.'\',\'featured\')" title="'.$tmpTitle.'">';
                }

                $item->state = $item->published;
                $item->published = $item->featured;
                $item->featuredStatus .= strip_tags(Joomla\CMS\HTML\HTMLHelper::_('grid.published', $item, $key), '<img>');
                $item->published = $item->state;
                if (!$filter_trash) {
                    $item->featuredStatus .= '</a>';
                }
            }
        }

        $this->assignRef('rows', $items);

        $lists = [];

        // Detect exact search phrase using double quotes in search string
        if (str_starts_with($search, '"') && str_ends_with($search, '"')) {
            $lists['search'] = '"'.trim(str_replace('"', '', $search)).'"';
        } else {
            $lists['search'] = trim(str_replace('"', '', $search));
        }

        if (!$filter_order) {
            $filter_order = 'category';
        }

        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order'] = $filter_order;

        $filter_trash_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_CURRENT'));
        $filter_trash_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 1, Joomla\CMS\Language\Text::_('K2_TRASHED'));
        $lists['trash'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $filter_trash_options, 'filter_trash', '', 'value', 'text', $filter_trash);

        require_once JPATH_ADMINISTRATOR.'/components/com_k2/models/categories.php';
        $categoriesModel = K2Model::getInstance('Categories', 'K2Model');
        $categories_option[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_SELECT_CATEGORY'));
        $categories = $categoriesModel->categoriesTree(null, true, false);
        $categories_options = @array_merge($categories_option, $categories);
        $lists['categories'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $categories_options, 'filter_category', '', 'value', 'text', $filter_category);

        $authors = $model->getItemsAuthors();
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

        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', -1, Joomla\CMS\Language\Text::_('K2_SELECT_PUBLISHING_STATE'));
        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 1, Joomla\CMS\Language\Text::_('K2_PUBLISHED'));
        $filter_state_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_UNPUBLISHED'));
        $lists['state'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $filter_state_options, 'filter_state', '', 'value', 'text', $filter_state);

        $filter_featured_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', -1, Joomla\CMS\Language\Text::_('K2_SELECT_FEATURED_STATE'));
        $filter_featured_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 1, Joomla\CMS\Language\Text::_('K2_FEATURED'));
        $filter_featured_options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_NOT_FEATURED'));
        $lists['featured'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $filter_featured_options, 'filter_featured', '', 'value', 'text', $filter_featured);

        if ($params->get('showTagFilter')) {
            $tagsModel = K2Model::getInstance('Tags', 'K2Model');
            $options = $tagsModel->getFilter();
            $option = new stdClass();
            $option->id = 0;
            $option->name = Joomla\CMS\Language\Text::_('K2_SELECT_TAG');
            array_unshift($options, $option);
            $lists['tag'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $options, 'tag', '', 'id', 'name', $tag);
        }

        if (version_compare(JVERSION, '1.6.0', 'ge')) {
            $languages = Joomla\CMS\HTML\HTMLHelper::_('contentlanguage.existing', true, true);
            array_unshift($languages, Joomla\CMS\HTML\HTMLHelper::_('select.option', '', Joomla\CMS\Language\Text::_('K2_SELECT_LANGUAGE')));
            $lists['language'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $languages, 'language', '', 'value', 'text', $language);
        }

        // Batch Operations
        $categoriesModel = K2Model::getInstance('Categories', 'K2Model');
        $categories = $categoriesModel->categoriesTree(null, true, false);
        array_unshift($categories, Joomla\CMS\HTML\HTMLHelper::_('select.option', '', '- '.Joomla\CMS\Language\Text::_('K2_LEAVE_UNCHANGED').' -'));
        $lists['batchCategories'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $categories, 'batchCategory', '', 'value', 'text');
        $lists['batchAccess'] = version_compare(JVERSION, '2.5', 'ge') ? Joomla\CMS\HTML\HTMLHelper::_('access.level', 'batchAccess', null, '', [Joomla\CMS\HTML\HTMLHelper::_('select.option', '', '- '.Joomla\CMS\Language\Text::_('K2_LEAVE_UNCHANGED').' -')]) : str_replace('size="3"', '', Joomla\CMS\HTML\HTMLHelper::_('list.accesslevel', $item));

        if (version_compare(JVERSION, '2.5.0', 'ge')) {
            $languages = Joomla\CMS\HTML\HTMLHelper::_('contentlanguage.existing', true, true);
            array_unshift($languages, Joomla\CMS\HTML\HTMLHelper::_('select.option', '', '- '.Joomla\CMS\Language\Text::_('K2_LEAVE_UNCHANGED').' -'));
            $lists['batchLanguage'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $languages, 'batchLanguage', '', 'value', 'text', null);
        }

        $model = $this->getModel('items');
        $authors = $model->getItemsAuthors();
        $options = [];
        $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '', '- '.Joomla\CMS\Language\Text::_('K2_LEAVE_UNCHANGED').' -');
        foreach ($authors as $author) {
            $name = $author->name;
            if ($author->block) {
                $name .= ' ['.Joomla\CMS\Language\Text::_('K2_USER_DISABLED').']';
            }

            $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $author->id, $name);
        }

        $lists['batchAuthor'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $options, 'batchAuthor', '', 'value', 'text', null);
        $this->assignRef('lists', $lists);

        // Pagination
        jimport('joomla.html.pagination');
        $pageNav = new JPagination($total, $limitstart, $limit);
        $this->assignRef('page', $pageNav);

        // Augment with plugin events
        $filters = [];
        $columns = [];

        Joomla\CMS\Plugin\PluginHelper::importPlugin('k2');
        $dispatcher = JDispatcher::getInstance();
        $dispatcher->trigger('onK2BeforeAssignFilters', [&$filters]);
        $this->assignRef('filters', $filters);
        $dispatcher->trigger('onK2BeforeAssignColumns', [&$columns]);
        $this->assignRef('columns', $columns);

        // Toolbar
        $toolbar = Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');
        Joomla\CMS\Toolbar\ToolbarHelper::title(Joomla\CMS\Language\Text::_('K2_ITEMS'), 'k2.png');

        if ($filter_trash == 1) {
            Joomla\CMS\Toolbar\ToolbarHelper::deleteList('K2_ARE_YOU_SURE_YOU_WANT_TO_DELETE_SELECTED_ITEMS', 'remove', 'K2_DELETE');
            Joomla\CMS\Toolbar\ToolbarHelper::custom('restore', 'publish.png', 'publish_f2.png', 'K2_RESTORE', true);
        } else {
            Joomla\CMS\Toolbar\ToolbarHelper::addNew();
            Joomla\CMS\Toolbar\ToolbarHelper::editList();
            if (K2_JVERSION == '30') {
                Joomla\CMS\Toolbar\ToolbarHelper::custom('featured', 'featured.png', 'featured_f2.png', 'K2_TOGGLE_FEATURED_STATE', true);
            } else {
                Joomla\CMS\Toolbar\ToolbarHelper::custom('featured', 'default.png', 'default_f2.png', 'K2_TOGGLE_FEATURED_STATE', true);
            }

            Joomla\CMS\Toolbar\ToolbarHelper::publishList();
            Joomla\CMS\Toolbar\ToolbarHelper::unpublishList();
            Joomla\CMS\Toolbar\ToolbarHelper::trash('trash');
            Joomla\CMS\Toolbar\ToolbarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'K2_COPY', true);
            // Batch button in modal
            if (K2_JVERSION == '30') {
                $batchButton = '<a id="K2BatchButton" class="btn btn-small" href="#"><i class="icon-edit"></i>'.Joomla\CMS\Language\Text::_('K2_BATCH').'</a>';
            } else {
                $batchButton = '<a id="K2BatchButton" href="#"><span class="icon-32-edit" title="'.Joomla\CMS\Language\Text::_('K2_BATCH').'"></span>'.Joomla\CMS\Language\Text::_('K2_BATCH').'</a>';
            }

            $toolbar->appendButton('Custom', $batchButton);

            // Display import button for Joomla content
            if ($user->gid > 23 && !$params->get('hideImportButton')) {
                $buttonUrl = Joomla\CMS\Uri\Uri::base().'index.php?option=com_k2&amp;view=items&amp;task=import';
                $buttonText = Joomla\CMS\Language\Text::_('K2_IMPORT_JOOMLA_CONTENT');
                if (K2_JVERSION == '30') {
                    $button = '<a id="K2ImportContentButton" class="btn btn-small" href="'.$buttonUrl.'"><i class="icon-archive"></i>'.$buttonText.'</a>';
                } else {
                    $button = '<a id="K2ImportContentButton" href="'.$buttonUrl.'"><span class="icon-32-archive" title="'.$buttonText.'"></span>'.$buttonText.'</a>';
                }

                $toolbar->appendButton('Custom', $button);
            }
        }

        // Preferences (Parameters/Settings)
        if (K2_JVERSION != '15') {
            Joomla\CMS\Toolbar\ToolbarHelper::preferences('com_k2', '(window.innerHeight) * 0.9', '(window.innerWidth) * 0.7', 'K2_SETTINGS');
        } else {
            $toolbar->appendButton('Popup', 'config', 'K2_SETTINGS', 'index.php?option=com_k2&view=settings', '(window.innerWidth) * 0.7', '(window.innerHeight) * 0.9');
        }

        $this->loadHelper('html');
        K2HelperHTML::subMenu();

        $template = $app->getTemplate();
        $this->assignRef('template', $template);
        $this->assignRef('filter_featured', $filter_featured);
        $this->assignRef('filter_trash', $filter_trash);
        $this->assignRef('user', $user);
        $dateFormat = K2_JVERSION != '15' ? Joomla\CMS\Language\Text::_('K2_J16_DATE_FORMAT') : Joomla\CMS\Language\Text::_('K2_DATE_FORMAT');

        $this->assignRef('dateFormat', $dateFormat);

        $ordering = (($this->lists['order'] == 'i.ordering' || $this->lists['order'] == 'category' || ($this->filter_featured > 0 && $this->lists['order'] == 'i.featured_ordering')) && (!$this->filter_trash));
        $this->assignRef('ordering', $ordering);

        Joomla\CMS\Table\Table::addIncludePath(JPATH_COMPONENT.'/tables');
        $table = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
        $this->assignRef('table', $table);

        // Joomla 3.x drag-n-drop sorting variables
        if (K2_JVERSION == '30') {
            if ($ordering) {
                $action = $this->filter_featured == 1 ? 'savefeaturedorder' : 'saveorder';
                Joomla\CMS\HTML\HTMLHelper::_('sortablelist.sortable', 'k2ItemsList', 'adminForm', strtolower($this->lists['order_Dir']), 'index.php?option=com_k2&view=items&task='.$action.'&format=raw');
            }

            $document->addScriptDeclaration('
                /* K2 */
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
