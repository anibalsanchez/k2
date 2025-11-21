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

class K2ViewExtraFields extends K2View
{
    public $lists;

    public function display($tpl = null)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $option = K2Request::getCmd('option');
        $view = K2Request::getCmd('view');

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $this->assignRef('params', $params);

        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.$view.'.limitstart', 'limitstart', 0, 'int');
        $filter_order = $app->getUserStateFromRequest($option.$view.'filter_order', 'filter_order', 'groupname', 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($option.$view.'filter_order_Dir', 'filter_order_Dir', 'ASC', 'word');
        $filter_state = $app->getUserStateFromRequest($option.$view.'filter_state', 'filter_state', -1, 'int');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = \Joomla\String\StringHelper::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search));

        $filter_type = $app->getUserStateFromRequest($option.$view.'filter_type', 'filter_type', '', 'string');
        $filter_group = $app->getUserStateFromRequest($option.$view.'filter_group', 'filter_group', '', 'string');

        $model = $this->getModel();
        $total = $model->getTotal();
        if ($limitstart > $total - $limit) {
            $limitstart = max(0, (int) (ceil($total / $limit) - 1) * $limit);
            K2Request::setVar('limitstart', $limitstart);
        }

        $extraFields = $model->getData();
        foreach ($extraFields as $key => $extraField) {
            $extraField->status = K2_JVERSION == '15' ? Joomla\CMS\HTML\HTMLHelper::_('grid.published', $extraField, $key) : Joomla\CMS\HTML\HTMLHelper::_('jgrid.published', $extraField->published, $key);
            $values = json_decode($extraField->value);
            if (isset($values[0]->alias) && !empty($values[0]->alias)) {
                $extraField->alias = $values[0]->alias;
            } else {
                $filter = Joomla\CMS\Filter\InputFilter::getInstance();
                $extraField->alias = $filter->clean($extraField->name, 'WORD');
            }
        }

        $this->assignRef('rows', $extraFields);

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

        $extraFieldGroups = $model->getGroups(true);
        $groups[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '0', Joomla\CMS\Language\Text::_('K2_SELECT_GROUP'));

        foreach ($extraFieldGroups as $extraFieldGroup) {
            $groups[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $extraFieldGroup->id, $extraFieldGroup->name);
        }

        $lists['group'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $groups, 'filter_group', '', 'value', 'text', $filter_group);

        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 0, Joomla\CMS\Language\Text::_('K2_SELECT_TYPE'));

        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'textfield', Joomla\CMS\Language\Text::_('K2_TEXT_FIELD'));
        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'textarea', Joomla\CMS\Language\Text::_('K2_TEXTAREA'));
        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'select', Joomla\CMS\Language\Text::_('K2_DROPDOWN_SELECTION'));
        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'multipleSelect', Joomla\CMS\Language\Text::_('K2_MULTISELECT_LIST'));
        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'radio', Joomla\CMS\Language\Text::_('K2_RADIO_BUTTONS'));
        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'link', Joomla\CMS\Language\Text::_('K2_LINK'));
        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'csv', Joomla\CMS\Language\Text::_('K2_CSV_DATA'));
        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'labels', Joomla\CMS\Language\Text::_('K2_SEARCHABLE_LABELS'));
        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'date', Joomla\CMS\Language\Text::_('K2_DATE'));
        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'image', Joomla\CMS\Language\Text::_('K2_IMAGE'));
        $typeOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'header', Joomla\CMS\Language\Text::_('K2_HEADER'));

        $lists['type'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $typeOptions, 'filter_type', '', 'value', 'text', $filter_type);

        $this->assignRef('lists', $lists);

        // Toolbar
        Joomla\CMS\Toolbar\ToolbarHelper::title(Joomla\CMS\Language\Text::_('K2_EXTRA_FIELDS'), 'k2.png');

        Joomla\CMS\Toolbar\ToolbarHelper::addNew();
        Joomla\CMS\Toolbar\ToolbarHelper::editList();
        Joomla\CMS\Toolbar\ToolbarHelper::publishList();
        Joomla\CMS\Toolbar\ToolbarHelper::unpublishList();
        Joomla\CMS\Toolbar\ToolbarHelper::deleteList('K2_ARE_YOU_SURE_YOU_WANT_TO_DELETE_SELECTED_EXTRA_FIELDS', 'remove', 'K2_DELETE');

        if (K2_JVERSION != '15') {
            Joomla\CMS\Toolbar\ToolbarHelper::preferences('com_k2', '(window.innerHeight) * 0.9', '(window.innerWidth) * 0.7', 'K2_SETTINGS');
        } else {
            $toolbar = Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');
            $toolbar->appendButton('Popup', 'config', 'K2_SETTINGS', 'index.php?option=com_k2&view=settings', '(window.innerWidth) * 0.7', '(window.innerHeight) * 0.9');
        }

        $this->loadHelper('html');
        K2HelperHTML::subMenu();

        $ordering = ($this->lists['order'] == 'ordering');
        $this->assignRef('ordering', $ordering);

        // Joomla 3.x drag-n-drop sorting variables
        if (K2_JVERSION == '30') {
            if ($ordering) {
                Joomla\CMS\HTML\HTMLHelper::_('sortablelist.sortable', 'k2ExtraFieldsList', 'adminForm', strtolower($this->lists['order_Dir']), 'index.php?option=com_k2&view=extrafields&task=saveorder&format=raw');
            }

            $document = Joomla\CMS\Factory::getDocument();
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
