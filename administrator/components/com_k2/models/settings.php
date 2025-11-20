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

class K2ModelSettings extends K2Model
{
    public function save()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $component = Joomla\CMS\Table\Table::getInstance('component');
        $component->loadByOption('com_k2');

        $post = K2Request::getPost();
        $component->bind($post);
        if (!$component->check()) {
            $app->enqueueMessage($component->getError(), 'error');

            return false;
        }

        if (!$component->store()) {
            $app->enqueueMessage($component->getError(), 'error');

            return false;
        }

        return true;
    }

    public function &getParams()
    {
        static $instance;
        if ($instance == null) {
            $component = Joomla\CMS\Table\Table::getInstance('component');
            $component->loadByOption('com_k2');
            $instance = new JParameter($component->params, JPATH_ADMINISTRATOR.'/components/com_k2/config.xml');
        }

        return $instance;
    }
}
