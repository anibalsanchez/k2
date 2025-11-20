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

class K2ElementCategories extends K2Element
{
    public function fetchElement($name, $value, &$node, $control_name)
    {
        $db = JFactory::getDbo();
        $query = 'SELECT m.* FROM #__k2_categories m WHERE trash = 0 ORDER BY parent, ordering';
        $db->setQuery($query);
        $mitems = $db->loadObjectList();
        $children = [];
        if ($mitems) {
            foreach ($mitems as $v) {
                if (K2_JVERSION != '15') {
                    $v->title = $v->name;
                    $v->parent_id = $v->parent;
                }
                $pt = $v->parent;
                $list = @$children[$pt] ? $children[$pt] : [];
                array_push($list, $v);
                $children[$pt] = $list;
            }
        }
        $list = JHTML::_('menu.treerecurse', 0, '', [], $children, 9999, 0, 0);
        $mitems = [];
        $mitems[] = JHTML::_('select.option', '0', JText::_('K2_NONE_ONSELECTLISTS'));

        foreach ($list as $item) {
            $item->treename = JString::str_ireplace('&#160;', ' -', $item->treename);
            $mitems[] = JHTML::_('select.option', $item->id, $item->treename);
        }

        $attributes = 'class="inputbox"';
        if (K2_JVERSION != '15') {
            $attribute = K2_JVERSION == '25' ? $node->getAttribute('multiple') : $node->attributes()->multiple;
            if ($attribute) {
                $attributes .= ' multiple="multiple" size="10"';
            }
        } else {
            if ($node->attributes('multiple')) {
                $attributes .= ' multiple="multiple" size="10"';
            }
        }

        if (K2_JVERSION != '15') {
            $fieldName = $name;
        } else {
            $fieldName = $control_name.'['.$name.']';
            if ($node->attributes('multiple')) {
                $fieldName .= '[]';
            }
        }

        return JHTML::_('select.genericlist', $mitems, $fieldName, $attributes, 'value', 'text', $value);
    }
}

class JFormFieldCategories extends K2ElementCategories
{
    public $type = 'categories';
}

class JElementCategories extends K2ElementCategories
{
    public $_name = 'categories';
}
