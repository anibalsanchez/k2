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

jimport('joomla.application.component.model');

if (version_compare(JVERSION, '2.5', 'ge')) {
    class K2Model extends Joomla\CMS\MVC\Model\BaseDatabaseModel
    {
        public static function addIncludePath($path = '', $prefix = 'K2Model')
        {
            return parent::addIncludePath($path, $prefix);
        }
    }
} else {
    class K2Model extends JModel
    {
        public function addIncludePath($path = '', $prefix = 'K2Model')
        {
            return parent::addIncludePath($path);
        }
    }
}
