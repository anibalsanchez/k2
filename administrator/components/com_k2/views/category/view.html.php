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

class K2ViewCategory extends K2View
{
    public function display($tpl = null)
    {
        $document = Joomla\CMS\Factory::getDocument();

        Joomla\CMS\HTML\HTMLHelper::_('behavior.modal');

        $model = $this->getModel();
        $category = $model->getData();
        if (K2_JVERSION == '15') {
            Joomla\CMS\Filter\OutputFilter::objectHTMLSafe($category);
        } else {
            Joomla\CMS\Filter\OutputFilter::objectHTMLSafe($category, ENT_QUOTES, ['params', 'plugins']);
        }

        if (!$category->id) {
            $category->published = 1;
        }

        $this->assignRef('row', $category);

        // Editor
        $wysiwyg = Joomla\CMS\Factory::getEditor();
        $editor = $wysiwyg->display('description', $category->description, '100%', '250px', '', '', ['pagebreak', 'readmore']);
        $this->assignRef('editor', $editor);
        $onSave = '';
        if (K2_JVERSION == '30') {
            $onSave = $wysiwyg->save('description');
        }

        $this->assignRef('onSave', $onSave);

        // JS
        $document->addScriptDeclaration("
            var K2BasePath = '".Joomla\CMS\Uri\Uri::base(true)."/';
            Joomla.submitbutton = function(pressbutton) {
                if (pressbutton == 'cancel') {
                    submitform(pressbutton);
                    return;
                }
                if (\$K2.trim(\$K2('#name').val()) == '') {
                    alert('".Joomla\CMS\Language\Text::_('K2_A_CATEGORY_MUST_AT_LEAST_HAVE_A_TITLE', true)."');
                } else {
                    ".$onSave.'
                    submitform(pressbutton);
                }
            };
        ');

        $lists = [];
        $lists['published'] = Joomla\CMS\HTML\HTMLHelper::_('select.booleanlist', 'published', 'class="inputbox"', $category->published);
        $lists['access'] = version_compare(JVERSION, '2.5', 'ge') ? Joomla\CMS\HTML\HTMLHelper::_('access.level', 'access', $category->access, '', false) : str_replace('size="3"', '', Joomla\CMS\HTML\HTMLHelper::_('list.accesslevel', $category));
        $query = 'SELECT ordering AS value, name AS text FROM #__k2_categories ORDER BY ordering';
        $lists['ordering'] = version_compare(JVERSION, '3.0', 'ge') ? null : Joomla\CMS\HTML\HTMLHelper::_('list.specificordering', $category, $category->id, $query);
        $categories[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '0', Joomla\CMS\Language\Text::_('K2_NONE_ONSELECTLISTS'));

        require_once JPATH_ADMINISTRATOR.'/components/com_k2/models/categories.php';
        $categoriesModel = K2Model::getInstance('Categories', 'K2Model');
        $tree = $categoriesModel->categoriesTree($category, true, false);
        $categories = array_merge($categories, $tree);
        $lists['parent'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $categories, 'parent', 'class="inputbox"', 'value', 'text', $category->parent);

        $extraFieldsModel = K2Model::getInstance('ExtraFields', 'K2Model');
        $groups = $extraFieldsModel->getGroups(true); // Fetch entire extra field group list
        $group[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '0', Joomla\CMS\Language\Text::_('K2_NONE_ONSELECTLISTS'), 'id', 'name');
        $group = array_merge($group, $groups);
        $lists['extraFieldsGroup'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $group, 'extraFieldsGroup', 'class="inputbox" size="1" ', 'id', 'name', $category->extraFieldsGroup);

        if (version_compare(JVERSION, '1.6.0', 'ge')) {
            $languages = Joomla\CMS\HTML\HTMLHelper::_('contentlanguage.existing', true, true);
            $lists['language'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $languages, 'language', '', 'value', 'text', $category->language);
        }

        // Plugin Events
        Joomla\CMS\Plugin\PluginHelper::importPlugin('k2');
        $dispatcher = JDispatcher::getInstance();
        $K2Plugins = $dispatcher->trigger('onRenderAdminForm', [&$category, 'category']);
        $this->assignRef('K2Plugins', $K2Plugins);

        // Parameters
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $this->assignRef('params', $params);

        if (version_compare(JVERSION, '1.6.0', 'ge')) {
            jimport('joomla.form.form');
            $form = Joomla\CMS\Form\Form::getInstance('categoryForm', JPATH_COMPONENT_ADMINISTRATOR.'/models/category.xml');
            $values = ['params' => json_decode($category->params)];
            $form->bind($values);
            $inheritFrom = $values['params']->inheritFrom ?? 0;
        } else {
            $form = new JParameter('', JPATH_COMPONENT_ADMINISTRATOR.'/models/category.xml');
            $form->loadINI($category->params);
            $inheritFrom = $form->get('inheritFrom');
        }

        $this->assignRef('form', $form);

        $categories[0] = Joomla\CMS\HTML\HTMLHelper::_('select.option', '0', Joomla\CMS\Language\Text::_('K2_NONE_ONSELECTLISTS'));
        $lists['inheritFrom'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $categories, 'params[inheritFrom]', 'class="inputbox"', 'value', 'text', $inheritFrom);

        $this->assignRef('lists', $lists);

        // Disable Joomla menu
        K2Request::setVar('hidemainmenu', 1);

        // Toolbar
        $title = (K2Request::getInt('cid')) ? Joomla\CMS\Language\Text::_('K2_EDIT_CATEGORY') : Joomla\CMS\Language\Text::_('K2_ADD_CATEGORY');
        Joomla\CMS\Toolbar\ToolbarHelper::title($title, 'k2.png');

        Joomla\CMS\Toolbar\ToolbarHelper::apply();
        Joomla\CMS\Toolbar\ToolbarHelper::save();
        $saveNewIcon = version_compare(JVERSION, '2.5.0', 'ge') ? 'save-new.png' : 'save.png';
        Joomla\CMS\Toolbar\ToolbarHelper::custom('saveAndNew', $saveNewIcon, 'save_f2.png', 'K2_SAVE_AND_NEW', false);
        Joomla\CMS\Toolbar\ToolbarHelper::cancel();

        parent::display($tpl);
    }
}
