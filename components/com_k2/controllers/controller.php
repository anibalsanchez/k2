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

jimport('joomla.application.component.controller');

if (version_compare(JVERSION, '3.0', 'ge')) {
    class K2Controller extends Joomla\CMS\MVC\Controller\BaseController
    {
        public function display($cachable = false, $urlparams = [])
        {
            parent::display($cachable, $urlparams);
        }
    }
} elseif (version_compare(JVERSION, '2.5', 'ge')) {
    class K2Controller extends JController
    {
        public function display($cachable = false, $urlparams = false)
        {
            parent::display($cachable, $urlparams);
        }
    }
} else {
    class K2Controller extends JController
    {
        public function display($cachable = false)
        {
            parent::display($cachable);
        }
    }
}
