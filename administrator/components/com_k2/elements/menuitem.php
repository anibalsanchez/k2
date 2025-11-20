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

require_once JPATH_ADMINISTRATOR.'/components/com_k2/elements/base.php';

class K2ElementMenuItem extends K2Element
{
    public function fetchElement($name, $value, &$node, $control_name)
    {
        $db = Joomla\CMS\Factory::getDbo();

        // load the list of menu types
        // TODO: move query to model
        $query = 'SELECT menutype, title FROM #__menu_types ORDER BY title';
        $db->setQuery($query);
        $menuTypes = $db->loadObjectList();

        $where = '';
        if ($state = $node->attributes('state')) {
            $where .= ' AND published = '.(int) $state;
        }

        // load the list of menu items
        // TODO: move query to model
        if (K2_JVERSION != '15') {
            $query = 'SELECT id, parent_id, title, menutype, type, published FROM #__menu'.$where.' ORDER BY menutype, parent_id, ordering';
        } else {
            $query = 'SELECT id, parent, name, menutype, type, published FROM #__menu'.$where.' ORDER BY menutype, parent, ordering';
        }

        $db->setQuery($query);
        $menuItems = $db->loadObjectList();

        // establish the hierarchy of the menu
        // TODO: use node model
        $children = [];

        if ($menuItems) {
            // first pass - collect children
            foreach ($menuItems as $menuItem) {
                if (K2_JVERSION != '15') {
                    $menuItem->parent = $menuItem->parent_id;
                    $menuItem->name = $menuItem->title;
                }

                $pt = $menuItem->parent;
                $list = @$children[$pt] ? $children[$pt] : [];
                $list[] = $menuItem;
                $children[$pt] = $list;
            }
        }

        // second pass - get an indent list of the items
        $list = Joomla\CMS\HTML\HTMLHelper::_('menu.treerecurse', 0, '', [], $children, 9999, 0, 0);

        foreach ($list as $item) {
            $item->treename = JString::str_ireplace('&#160;', ' -', $item->treename);
            $mitems[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $item->id, '   '.$item->treename);
        }

        // assemble into menutype groups
        $n = count($list);
        $groupedList = [];
        foreach ($list as $k => $v) {
            $groupedList[$v->menutype][] = &$list[$k];
        }

        // assemble menu items to the array
        $options = [];
        $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '', '- '.Joomla\CMS\Language\Text::_('K2_SELECT_MENU_ITEM').' -');

        foreach ($menuTypes as $menuType) {
            if ($menuType != '') {
                $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '0', '&nbsp;', 'value', 'text', true);
                $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $menuType->menutype, $menuType->title.' - '.Joomla\CMS\Language\Text::_('K2_TOP'), 'value', 'text', true);
            }

            if (isset($groupedList[$menuType->menutype])) {
                $n = count($groupedList[$menuType->menutype]);
                for ($i = 0; $i < $n; $i++) {
                    $item = &$groupedList[$menuType->menutype][$i];

                    //If menutype is changed but item is not saved yet, use the new type in the list
                    if (K2Request::getString('option', '', 'get') == 'com_menus') {
                        $currentItemArray = K2Request::getVar('cid', [0], '', 'array');
                        $currentItemId = (int) $currentItemArray[0];
                        $currentItemType = K2Request::getString('type', $item->type, 'get');
                        if ($currentItemId == $item->id && $currentItemType != $item->type) {
                            $item->type = $currentItemType;
                        }
                    }

                    $disable = @strpos($node->attributes('disable'), (string) $item->type) !== false;

                    if ($item->published == 0) {
                        $item->treename .= ' [**'.Joomla\CMS\Language\Text::_('K2_UNPUBLISHED').'**]';
                    }

                    if ($item->published == -2) {
                        $item->treename .= ' [**'.Joomla\CMS\Language\Text::_('K2_TRASHED').'**]';
                    }

                    $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $item->id, $item->treename, 'value', 'text', $disable);
                }
            }
        }

        $fieldName = K2_JVERSION != '15' ? $name : $control_name.'['.$name.']';

        return Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $options, $fieldName, 'class="inputbox"', 'value', 'text', $value, $control_name.$name);
    }
}

class JFormFieldMenuItem extends K2ElementMenuItem
{
    public $type = 'MenuItem';
}

class JElementMenuItem extends K2ElementMenuItem
{
    public $_name = 'MenuItem';
}
