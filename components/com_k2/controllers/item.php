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

class K2ControllerItem extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        $model = $this->getModel('itemlist');
        $document = Joomla\CMS\Factory::getDocument();
        $viewType = $document->getType();
        $view = $this->getView('item', $viewType);
        $view->setModel($model);
        K2Request::setVar('view', 'item');
        $user = Joomla\CMS\Factory::getUser();
        if ($user->guest) {
            $cache = true;
        } else {
            $cache = true;
            Joomla\CMS\Table\Table::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/tables');
            $row = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $row->load(K2Request::getInt('id'));
            if (K2HelperPermissions::canEditItem($row->created_by, $row->catid)) {
                $cache = false;
            }

            $params = K2HelperUtilities::getParams('com_k2');
            if ($row->created_by == $user->id && $params->get('inlineCommentsModeration')) {
                $cache = false;
            }

            if ($row->access > 0) {
                $cache = false;
            }

            $category = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $category->load($row->catid);
            if ($category->access > 0) {
                $cache = false;
            }

            if ($params->get('comments') && $document->getType() == 'html') {
                $itemListModel = K2Model::getInstance('Itemlist', 'K2Model');
                $profile = $itemListModel->getUserProfile($user->id);
                $script = "
                    \$K2(document).ready(function() {
                        \$K2('#userName').val(".json_encode($user->name).").attr('disabled', 'disabled');
                        \$K2('#commentEmail').val('".$user->email."').attr('disabled', 'disabled');
                ";
                if (is_object($profile) && $profile->url) {
                    $script .= "
                        \$K2('#commentURL').val('".htmlspecialchars($profile->url, ENT_QUOTES, 'UTF-8')."').attr('disabled', 'disabled');
                    ";
                }

                $script .= '
                    });
                ';
                $document->addScriptDeclaration($script);
            }
        }

        if (K2_JVERSION != '15') {
            $urlparams['id'] = 'INT';
            $urlparams['print'] = 'INT';
            $urlparams['lang'] = 'CMD';
            $urlparams['Itemid'] = 'INT';
            $urlparams['m'] = 'INT';
            $urlparams['amp'] = 'INT';
            $urlparams['tmpl'] = 'CMD';
            $urlparams['template'] = 'CMD';
        }

        parent::display($cache, $urlparams);
    }

    public function edit()
    {
        K2Request::setVar('tmpl', 'component');
        $app = Joomla\CMS\Factory::getApplication();
        $document = Joomla\CMS\Factory::getDocument();
        $params = K2HelperUtilities::getParams('com_k2');
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        K2HelperHTML::loadHeadIncludes(true, true, true);

        // CSS
        $document->addStyleSheet(Joomla\CMS\Uri\Uri::root(true).'/templates/system/css/general.css');
        $document->addStyleSheet(Joomla\CMS\Uri\Uri::root(true).'/templates/system/css/system.css');

        $this->addModelPath(JPATH_COMPONENT_ADMINISTRATOR.'/models');
        $this->addViewPath(JPATH_COMPONENT_ADMINISTRATOR.'/views');
        $view = $this->getView('item', 'html');
        $view->frontendTheme = $params->get('theme');
        $view->setLayout('itemform');

        if ($params->get('category')) {
            K2Request::setVar('catid', $params->get('category'));
        }

        $view->display();
    }

    public function add()
    {
        $this->edit();
    }

    public function cancel()
    {
        $this->setRedirect(Joomla\CMS\Uri\Uri::root(true));

        return false;
    }

    public function save()
    {
        $app = Joomla\CMS\Factory::getApplication();
        K2Request::checkToken() || jexit('Invalid Token');
        K2Request::setVar('tmpl', 'component');
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);
        require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/item.php';
        $model = new K2ModelItem();
        $model->save(true);

        $app->close();
    }

    public function deleteAttachment()
    {
        require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/item.php';
        $model = new K2ModelItem();
        $model->deleteAttachment();
    }

    public function tag()
    {
        require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/tag.php';
        $model = new K2ModelTag();
        $model->addTag();
    }

    public function tags()
    {
        $user = Joomla\CMS\Factory::getUser();
        if ($user->guest) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/tag.php';
        $model = new K2ModelTag();
        $model->tags();
    }

    public function download()
    {
        require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/item.php';
        $model = new K2ModelItem();
        $model->download();
    }

    public function extraFields()
    {
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        $app = Joomla\CMS\Factory::getApplication();
        $id = K2Request::getInt('id', null);

        require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/category.php';
        $categoryModel = new K2ModelCategory();
        $category = $categoryModel->getData();

        require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/extrafield.php';
        $extraFieldModel = new K2ModelExtraField();
        $extraFields = $extraFieldModel->getExtraFieldsByGroup($category->extraFieldsGroup);

        if (!empty($extraFields) && count($extraFields)) {
            $output = '<div id="extraFields">';
            foreach ($extraFields as $extraField) {
                if ($extraField->type == 'header') {
                    $output .= '
                    <div class="itemAdditionalField fieldIs'.ucfirst($extraField->type).'">
                        <h4>'.$extraField->name.'</h4>
                    </div>
                    ';
                } else {
                    $output .= '
                    <div class="itemAdditionalField fieldIs'.ucfirst($extraField->type).'">
                        <div class="itemAdditionalValue">
                            <label for="K2ExtraField_'.$extraField->id.'">'.$extraField->name.'</label>
                        </div>
                        <div class="itemAdditionalData">
                            '.$extraFieldModel->renderExtraField($extraField, $id).'
                        </div>
                    </div>
                    ';
                }
            }

            $output .= '</div>';
        } else {
            $output = '
                <div class="k2-generic-message">
                    <h3>'.Joomla\CMS\Language\Text::_('K2_NOTICE').'</h3>
                    <p>'.Joomla\CMS\Language\Text::_('K2_THIS_CATEGORY_DOESNT_HAVE_ASSIGNED_EXTRA_FIELDS').'</p>
                </div>
            ';
        }

        echo $output;

        $app->close();
    }

    public function checkin()
    {
        $model = $this->getModel('item');
        $model->checkin();
    }

    public function vote()
    {
        $model = $this->getModel('item');
        $model->vote();
    }

    public function getVotesNum()
    {
        $model = $this->getModel('item');
        $model->getVotesNum();
    }

    public function getVotesPercentage()
    {
        $model = $this->getModel('item');
        $model->getVotesPercentage();
    }

    public function comment()
    {
        $model = $this->getModel('item');
        $model->comment();
    }

    public function resetHits()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        K2Request::setVar('tmpl', 'component');
        require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/item.php';
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        $model = new K2ModelItem();
        $model->resetHits();
    }

    public function resetRating()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        K2Request::setVar('tmpl', 'component');
        require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/item.php';
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        $model = new K2ModelItem();
        $model->resetRating();
    }

    public function media()
    {
        K2Request::setVar('tmpl', 'component');
        $params = K2HelperUtilities::getParams('com_k2');
        $document = Joomla\CMS\Factory::getDocument();
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        $user = Joomla\CMS\Factory::getUser();
        if ($user->guest) {
            $uri = Joomla\CMS\Factory::getURI();
            if (K2_JVERSION != '15') {
                $url = 'index.php?option=com_users&view=login&return='.base64_encode($uri->toString());
            } else {
                $url = 'index.php?option=com_user&view=login&return='.base64_encode($uri->toString());
            }

            $app = Joomla\CMS\Factory::getApplication();
            $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_YOU_NEED_TO_LOGIN_FIRST'), 'notice');
            $app->redirect(Joomla\CMS\Router\Route::_($url, false));
        }

        K2HelperHTML::loadHeadIncludes(false, true, true);

        $this->addViewPath(JPATH_COMPONENT_ADMINISTRATOR.'/views');
        $view = $this->getView('media', 'html');
        $view->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR.'/views/media/tmpl');
        $view->setLayout('default');
        $view->display();
    }

    public function connector()
    {
        K2Request::setVar('tmpl', 'component');
        $user = Joomla\CMS\Factory::getUser();
        if ($user->guest) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        require_once JPATH_COMPONENT_ADMINISTRATOR.'/controllers/media.php';
        $controller = new K2ControllerMedia();
        $controller->connector();
    }

    public function users()
    {
        $itemID = K2Request::getInt('itemID');
        Joomla\CMS\Table\Table::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/tables');
        $item = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
        $item->load($itemID);
        if (!K2HelperPermissions::canAddItem() && !K2HelperPermissions::canEditItem($item->created_by, $item->catid)) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        $K2Permissions = K2Permissions::getInstance();
        if (!$K2Permissions->permissions->get('editAll')) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        K2Request::setVar('tmpl', 'component');
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $language = Joomla\CMS\Factory::getLanguage();
        $language->load('com_k2', JPATH_ADMINISTRATOR);

        $document = Joomla\CMS\Factory::getDocument();

        K2HelperHTML::loadHeadIncludes(true, true, true);

        $this->addViewPath(JPATH_COMPONENT_ADMINISTRATOR.'/views');
        $this->addModelPath(JPATH_COMPONENT_ADMINISTRATOR.'/models');
        $view = $this->getView('users', 'html');
        $view->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR.'/views/users/tmpl');
        $view->display();
    }
}
