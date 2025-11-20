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

require_once JPATH_ADMINISTRATOR.'/components/com_k2/tables/table.php';

class TableK2ExtraFieldsGroup extends K2Table
{
    public $id = null;

    public $name = null;

    public function __construct(&$db)
    {
        parent::__construct('#__k2_extra_fields_groups', 'id', $db);
    }

    public function check()
    {
        $this->name = JString::trim($this->name);
        if ($this->name == '') {
            $this->setError(JText::_('K2_GROUP_MUST_HAVE_A_NAME'));

            return false;
        }

        return true;
    }
}
