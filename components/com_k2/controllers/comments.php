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
        $document = Joomla\CMS\Factory::getDocument();
        $user = Joomla\CMS\Factory::getUser();

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');

        K2HelperHTML::loadHeadIncludes(true, true, true);

        // Message for guests
        if ($user->guest) {
            $uri = Joomla\CMS\Uri\Uri::getInstance();
            if (K2_JVERSION != '15') {
                $url = 'index.php?option=com_users&view=login&return='.base64_encode($uri->toString());
            } else {
                $url = 'index.php?option=com_user&view=login&return='.base64_encode($uri->toString());
            }

            $app = Joomla\CMS\Factory::getApplication();
            $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_YOU_NEED_TO_LOGIN_FIRST'), 'notice');
            $app->redirect(Joomla\CMS\Router\Route::_($url, false));
        }

        K2Request::setVar('tmpl', 'component');

        // Language
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        $this->addViewPath(JPATH_COMPONENT_ADMINISTRATOR.'/views');
        $this->addModelPath(JPATH_COMPONENT_ADMINISTRATOR.'/models');

        $view = $this->getView('comments', 'html');
        $view->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR.'/views/comments/tmpl');
        $view->addHelperPath(JPATH_COMPONENT_ADMINISTRATOR.'/helpers');
        $view->display();
    }

    public function publish()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        $user = Joomla\CMS\Factory::getUser();
        if ($user->guest) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        K2Model::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/models');
        $model = K2Model::getInstance('Comments', 'K2Model');
        $model->publish();
    }

    public function unpublish()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        $user = Joomla\CMS\Factory::getUser();
        if ($user->guest) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        K2Model::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/models');
        $model = K2Model::getInstance('Comments', 'K2Model');
        $model->unpublish();
    }

    public function remove()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        $user = Joomla\CMS\Factory::getUser();
        if ($user->guest) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        K2Model::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/models');
        $model = K2Model::getInstance('Comments', 'K2Model');
        $model->remove();
    }

    public function deleteUnpublished()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        $user = Joomla\CMS\Factory::getUser();
        if ($user->guest) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        K2Model::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/models');
        $model = K2Model::getInstance('Comments', 'K2Model');
        $model->deleteUnpublished();
    }

    public function saveComment()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        $user = Joomla\CMS\Factory::getUser();
        if ($user->guest) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        K2Model::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/models');
        $model = K2Model::getInstance('Comments', 'K2Model');
        $model->save();

        $app->close();
    }

    public function report()
    {
        K2Request::setVar('tmpl', 'component');
        $view = $this->getView('comments', 'html');
        $view->setLayout('report');
        $view->report();
    }

    public function sendReport()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $params = K2HelperUtilities::getParams('com_k2');
        $user = Joomla\CMS\Factory::getUser();
        if (!$params->get('comments') || !$params->get('commentsReporting') || ($params->get('commentsReporting') == '2' && $user->guest)) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        K2Model::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/models');
        $model = K2Model::getInstance('Comments', 'K2Model');
        $model->setState('id', K2Request::getInt('id'));
        $model->setState('name', K2Request::getString('name'));
        $model->setState('reportReason', K2Request::getString('reportReason'));
        if (!$model->report()) {
            echo $model->getError();
        } else {
            echo Joomla\CMS\Language\Text::_('K2_REPORT_SUBMITTED');
        }

        $app = Joomla\CMS\Factory::getApplication();
        $app->close();
    }

    public function reportSpammer()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $format = K2Request::getVar('format');
        $errors = [];
        if (K2_JVERSION != '15') {
            if (!$user->authorise('core.admin', 'com_k2')) {
                $format == 'raw' ? die(Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH')) : JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
            }
        } elseif ($user->gid < 25) {
            $format == 'raw' ? die(Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH')) : JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        K2Model::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/models');
        $model = K2Model::getInstance('User', 'K2Model');
        $model->setState('id', K2Request::getInt('id'));
        $model->reportSpammer();
        if ($format == 'raw') {
            $response = '';
            $messages = $app->getMessageQueue();
            foreach ($messages as $message) {
                $response .= $message['message']."\n";
            }

            die($response);
        }

        $this->setRedirect('index.php?option=com_k2&view=comments&tmpl=component');
    }
}
