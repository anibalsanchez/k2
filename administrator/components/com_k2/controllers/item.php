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
        K2Request::setVar('view', 'item');
        parent::display();
    }

    public function save()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('item');
        $model->save();
    }

    public function apply()
    {
        $this->save();
    }

    public function cancel()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('item');
        $model->cancel();
    }

    public function deleteAttachment()
    {
        $model = $this->getModel('item');
        $model->deleteAttachment();
    }

    public function tag()
    {
        $model = $this->getModel('tag');
        $model->addTag();
    }

    public function tags()
    {
        $user = Joomla\CMS\Factory::getUser();
        if ($user->guest) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        $model = $this->getModel('tag');
        $model->tags();
    }

    public function download()
    {
        $model = $this->getModel('item');
        $model->download();
    }

    public function extraFields()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $id = K2Request::getInt('id', null);

        $categoryModel = $this->getModel('category');
        $category = $categoryModel->getData();

        $extraFieldModel = $this->getModel('extraField');
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

    public function resetHits()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('item');
        $model->resetHits();
    }

    public function resetRating()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('item');
        $model->resetRating();
    }
}
