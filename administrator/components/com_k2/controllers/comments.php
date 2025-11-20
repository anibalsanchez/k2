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

class K2ControllerComments extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        require_once JPATH_SITE.'/components/com_k2/helpers/route.php';
        JRequest::setVar('view', 'comments');
        parent::display();
    }

    public function publish()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('comments');
        $model->publish();
    }

    public function unpublish()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('comments');
        $model->unpublish();
    }

    public function remove()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('comments');
        $model->remove();
    }

    public function deleteUnpublished()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('comments');
        $model->deleteUnpublished();
    }

    public function saveComment()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('comments');
        $model->save();
    }
}
