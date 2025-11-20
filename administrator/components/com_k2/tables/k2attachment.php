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

class TableK2Attachment extends K2Table
{
    public $id = null;

    public $itemID = null;

    public $filename = null;

    public $title = null;

    public $titleAttribute = null;

    public $hits = null;

    public function __construct(&$db)
    {
        parent::__construct('#__k2_attachments', 'id', $db);
    }
}
