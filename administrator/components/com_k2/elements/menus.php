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
defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_k2/elements/base.php';

class K2ElementMenus extends K2Element
{
    public function fetchElement($name, $value, &$node, $control_name)
    {
        $fieldName = (K2_JVERSION != '15') ? $name : $control_name.'['.$name.']';
        $db = JFactory::getDbo();
        $query = 'SELECT menutype, title FROM #__menu_types';
        $db->setQuery($query);
        $menus = $db->loadObjectList();
        $options = [];
        $options[] = JHTML::_('select.option', '', JText::_('K2_NONE_ONSELECTLISTS'));
        foreach ($menus as $menu) {
            $options[] = JHTML::_('select.option', $menu->menutype, $menu->title);
        }

        return JHTML::_('select.genericlist', $options, $fieldName, 'class="inputbox"', 'value', 'text', $value);
    }
}

class JFormFieldMenus extends K2ElementMenus
{
    public $type = 'menus';
}

class JElementMenus extends K2ElementMenus
{
    public $_name = 'menus';
}
