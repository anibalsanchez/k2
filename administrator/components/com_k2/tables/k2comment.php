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

class TableK2Comment extends K2Table
{
    public $id = null;

    public $itemID = null;

    public $userID = null;

    public $userName = null;

    public $commentDate = null;

    public $commentText = null;

    public $commentEmail = null;

    public $commentURL = null;

    public $published = null;

    public function __construct(&$db)
    {
        parent::__construct('#__k2_comments', 'id', $db);
    }

    public function check()
    {
        $this->commentText = JString::trim($this->commentText);
    }
}
