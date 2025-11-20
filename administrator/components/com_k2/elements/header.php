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

class K2ElementHeader extends K2Element
{
    public function fetchElement($name, $value, &$node, $control_name)
    {
        $additionalCssClass = '';
        if (version_compare(JVERSION, '2.5.0', 'ge')) {
            if ($node->attributes()->class) {
                $additionalCssClass = ' '.$node->attributes()->class;
            }
        } else {
            if ($node->attributes('class')) {
                $additionalCssClass = ' '.$node->attributes('class');
            }
        }

        if (version_compare(JVERSION, '2.5.0', 'ge')) {
            return '<div class="jwHeaderContainer'.$additionalCssClass.'"><div class="jwHeaderContent">'.JText::_($value).'</div><div class="jwHeaderClr"></div></div>';
        }

        return '<div class="jwHeaderContainer15'.$additionalCssClass.'"><div class="jwHeaderContent">'.JText::_($value).'</div><div class="jwHeaderClr"></div></div>';
    }

    public function fetchTooltip($label, $description, &$node, $control_name, $name)
    {
        return null;
    }
}

class JFormFieldHeader extends K2ElementHeader
{
    public $type = 'header';
}

class JElementHeader extends K2ElementHeader
{
    public $_name = 'header';
}
