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

class K2ControllerExtraField extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        JRequest::setVar('view', 'extrafield');
        parent::display();
    }

    public function apply()
    {
        $this->save();
    }

    public function save()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('extraField');
        $model->save();
    }

    public function saveAndNew()
    {
        $this->save();
    }

    public function cancel()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $app->redirect('index.php?option=com_k2&view=extrafields');
    }
}
