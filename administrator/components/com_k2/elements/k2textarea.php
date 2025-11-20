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

class K2ElementK2textarea extends K2Element
{
    public function fetchElement($name, $value, &$node, $control_name)
    {
        // Attributes
        if (version_compare(JVERSION, '1.6.0', 'ge')) {
            $fieldName = $name;
            if ($node->attributes()->chars) {
                $chars = $node->attributes()->chars;
            }
            if ($node->attributes()->cols) {
                $cols = $node->attributes()->cols;
            }
            if ($node->attributes()->rows) {
                $rows = $node->attributes()->rows;
            }
        } else {
            $fieldName = $control_name.'['.$name.']';
            if ($node->attributes('chars')) {
                $chars = $node->attributes('chars');
            }
            if ($node->attributes('cols')) {
                $cols = $node->attributes('cols');
            }
            if ($node->attributes('rows')) {
                $rows = $node->attributes('rows');
            }
        }
        if (!$value) {
            $value = '';
        }

        // Output
        return '<textarea name="'.$fieldName.'" rows="'.$rows.'" cols="'.$cols.'" data-k2-chars="'.$chars.'">'.$value.'</textarea>';
    }
}

class JFormFieldK2textarea extends K2ElementK2textarea
{
    public $type = 'k2textarea';
}

class JElementK2textarea extends K2ElementK2textarea
{
    public $_name = 'k2textarea';
}
