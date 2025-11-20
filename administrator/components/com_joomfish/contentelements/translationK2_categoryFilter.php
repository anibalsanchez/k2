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

class translationK2_categoryFilter extends translationFilter
{
    public function __construct($contentElement)
    {
        $this->filterNullValue = -1;
        $this->filterType = 'catid';
        $this->filterField = $contentElement->getFilter('K2_category');
        parent::translationFilter($contentElement);
    }

    public function _createFilter()
    {
        $database = Joomla\CMS\Factory::getDbo();
        if (!$this->filterField) {
            return '';
        }

        $filter = '';
        if ($this->filter_value != $this->filterNullValue) {
            $sql = 'SELECT tab.id FROM #__k2_items as tab WHERE tab.catid='.$this->filter_value;
            $database->setQuery($sql);
            $ids = $database->loadObjectList();
            $idstring = '';
            foreach ($ids as $id) {
                if (strlen($idstring) > 0) {
                    $idstring .= ',';
                }

                $idstring .= $id->id;
            }

            $filter = sprintf('c.id IN(%s)', $idstring);
        }

        return $filter;
    }

    public function _createfilterHTML()
    {
        if (!$this->filterField) {
            return '';
        }

        $db = Joomla\CMS\Factory::getDbo();
        $categoryOptions = [];
        $categoryOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '-1', Joomla\CMS\Language\Text::_('K2_SELECT_CATEGORY'));

        $sql = 'SELECT DISTINCT p.id, p.name FROM #__k2_categories as p, #__'.$this->tableName.' as c WHERE c.'.$this->filterField.'=p.id ORDER BY p.name';
        $db->setQuery($sql);
        $cats = $db->loadObjectList();
        $catcount = 0;
        foreach ($cats as $cat) {
            $categoryOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $cat->id, $cat->name);
            $catcount++;
        }

        $catnameList = [];
        $catnameList['title'] = Joomla\CMS\Language\Text::_('K2_CATEGORIES');
        $catnameList['html'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $categoryOptions, 'catid_filter_value', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'value', 'text', $this->filter_value);

        return $catnameList;
    }
}
