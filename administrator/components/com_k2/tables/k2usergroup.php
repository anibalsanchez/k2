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

require_once JPATH_ADMINISTRATOR.'/components/com_k2/tables/table.php';

class TableK2UserGroup extends K2Table
{
    public $id = null;

    public $name = null;

    public $permissions = null;

    public function __construct(&$db)
    {
        parent::__construct('#__k2_user_groups', 'id', $db);
    }

    public function check()
    {
        $this->name = JString::trim($this->name);
        if ($this->name == '') {
            $this->setError(Joomla\CMS\Language\Text::_('K2_GROUP_CANNOT_BE_EMPTY'));

            return false;
        }

        return true;
    }

    public function bind($array, $ignore = '')
    {
        if (array_key_exists('params', $array) && is_array($array['params'])) {
            $jRegistry = new JRegistry();
            $jRegistry->loadArray($array['params']);
            if (K2Request::getVar('categories') == 'all' || K2Request::getVar('categories') == 'none') {
                $jRegistry->set('categories', K2Request::getVar('categories'));
            }

            $array['permissions'] = $jRegistry->toString();
        }

        return parent::bind($array, $ignore);
    }
}
