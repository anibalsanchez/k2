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

jimport('joomla.application.component.view');

if (version_compare(JVERSION, '3.0', 'ge')) {
    class K2View extends Joomla\CMS\MVC\View\HtmlView
    {
        public $_output;

        public function display($tpl = null)
        {
            // Allow for YOOtheme PRO Integration
            $app = Joomla\CMS\Factory::getApplication();

            // Only in YOOtheme PRO
            if ($app->isClient('site') && stripos($app->getTemplate(), 'yootheme') === 0) {
                // Trigger the custom YOOtheme Pro event
                $app->triggerEvent('onLoadTemplate', [$this, $tpl]);

                // If the event overrode the output, print that output and don't display anything else
                if ($this->_output) {
                    echo $this->_output;

                    return null;
                }
            }

            return parent::display($tpl);
        }
    }
} else {
    class K2View extends JView
    {
    }
}
