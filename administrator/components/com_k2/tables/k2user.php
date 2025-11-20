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

class TableK2User extends K2Table
{
    public $id = null;

    public $userID = null;

    public $userName = null;

    public $gender = null;

    public $description = null;

    public $image = null;

    public $url = null;

    public $group = null;

    public $plugins = null;

    public $ip = null;

    public $hostname = null;

    public $notes = null;

    public function __construct(&$db)
    {
        parent::__construct('#__k2_users', 'id', $db);
    }

    public function check()
    {
        if (trim($this->url) !== '' && !str_starts_with($this->url, 'http')) {
            $this->url = 'https://'.$this->url;
        }

        return true;
    }

    public function bind($array, $ignore = '')
    {
        if (array_key_exists('plugins', $array) && is_array($array['plugins'])) {
            $jRegistry = new JRegistry();
            $jRegistry->loadArray($array['plugins']);
            $array['plugins'] = $jRegistry->toString();
        }

        return parent::bind($array, $ignore);
    }
}
