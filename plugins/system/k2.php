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

jimport('joomla.plugin.plugin');

abstract class K2Request
{
    private static $input;

    public static function getCmd($name, $default = '', $hash = 'default')
    {
        return self::getInput()->getCmd($name, $default, $hash);
    }

    public static function getInt($name, $default = 0, $hash = 'default')
    {
        return self::getInput()->getInt($name, $default, $hash);
    }

    public static function getString($name, $default = '', $hash = 'default')
    {
        return self::getInput()->getString($name, $default, $hash);
    }

    public static function getBool($name, $default = false, $hash = 'default')
    {
        return self::getInput()->getBool($name, $default, $hash);
    }

    public static function getWord($name, $default = '', $hash = 'default')
    {
        return self::getInput()->getWord($name, $default, $hash);
    }

    public static function getPost()
    {
        return self::getInput()->get('post', [], 'array');
    }

    public static function getFiles()
    {
        return self::getInput()->get('files', [], 'array');
    }

    public static function getVar($name, $default = null, $hash = 'default', $type = 'none', $mask = 0)
    {
        return self::getInput()->get($name, $default, $hash);
    }

    public static function setVar($name, $value)
    {
        self::getInput()->set($name, $value);
    }

    public static function checkToken($method = 'post')
    {
        return self::getInput()->checkToken($method);
    }

    private static function getInput()
    {
        if (self::$input === null) {
            self::$input = Joomla\CMS\Factory::getApplication()->input;
        }

        return self::$input;
    }
}

abstract class K2Behavior
{
    public static function framework()
    {
    }

    public static function mootools()
    {
    }

    public static function tooltip()
    {
    }
}

class K2Dispatcher
{
    public static function getInstance()
    {
        return new self();
    }

    public function trigger($event, $options = [])
    {
        $dispatcher = $this->jDispatcherGetInstance();

        if ($dispatcher) {
            if (method_exists($dispatcher, 'trigger')) {
                return $dispatcher->trigger('onContentCleanCache', $options);
            }

            if (method_exists($dispatcher, 'triggerEvent')) {
                return $dispatcher->triggerEvent('onContentCleanCache', $options);
            }
        }

        $app = Joomla\CMS\Factory::getApplication();

        if (method_exists($app, 'triggerEvent')) {
            return $app->triggerEvent('onContentCleanCache', $options);
        }
    }

    private function jDispatcherGetInstance()
    {
        if (class_exists('\Joomla\CMS\Factory')) {
            $app = Joomla\CMS\Factory::getApplication();

            if (method_exists($app, 'getDispatcher')) {
                return $app->getDispatcher();
            }
        }

        if (class_exists('JDispatcher')) {
            return \JDispatcher::getInstance();
        }

        if (class_exists('JEventDispatcher')) {
            return \JEventDispatcher::getInstance();
        }

        throw new Exception('Unable to load the Event Dispatcher');
    }
}

class plgSystemK2 extends Joomla\CMS\Plugin\CMSPlugin
{
    public function onAfterInitialise()
    {
        // Determine Joomla version
        if (version_compare(JVERSION, '3.0', 'ge')) {
            define('K2_JVERSION', '30');
        } elseif (version_compare(JVERSION, '2.5', 'ge')) {
            define('K2_JVERSION', '25');
        } else {
            define('K2_JVERSION', '15');
        }

        // Define K2 version & build here
        define('K2_CURRENT_VERSION', '2.16');

        // Define the DS constant (for backwards compatibility with old template overrides & 3rd party K2 extensions)
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        // Import Joomla classes
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');
        jimport('joomla.application.component.controller');
        jimport('joomla.application.component.model');
        jimport('joomla.application.component.view');

        // Get application & K2 component params
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $config = Joomla\CMS\Factory::getConfig();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');

        // Load the K2 classes
        JLoader::register('K2Table', JPATH_ADMINISTRATOR.'/components/com_k2/tables/table.php');
        JLoader::register('K2Controller', JPATH_BASE.'/components/com_k2/controllers/controller.php');
        JLoader::register('K2Model', JPATH_ADMINISTRATOR.'/components/com_k2/models/model.php');
        if ($app->isClient('site')) {
            K2Model::addIncludePath(JPATH_SITE.'/components/com_k2/models');
        } elseif (K2_JVERSION !== '15' || (K2_JVERSION === '15' && K2Request::getCmd('option') != 'com_users')) {
            // Fix warning under Joomla 1.5 caused by conflict in model names
            K2Model::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/models');
        }

        JLoader::register('K2View', JPATH_ADMINISTRATOR.'/components/com_k2/views/view.php');
        JLoader::register('K2HelperHTML', JPATH_ADMINISTRATOR.'/components/com_k2/helpers/html.php');
        JLoader::register('K2HelperUtilities', JPATH_SITE.'/components/com_k2/helpers/utilities.php');

        // Define JoomFish compatibility version.
        if (Joomla\CMS\Filesystem\File::exists(JPATH_ADMINISTRATOR.'/components/com_joomfish/joomfish.php')) {
            if (K2_JVERSION === '15') {
                $db = Joomla\CMS\Factory::getDbo();
                $config = Joomla\CMS\Factory::getConfig();
                $prefix = $config->getValue('config.dbprefix');
                if (array_key_exists($prefix.'_jf_languages_ext', $db->getTableList())) {
                    define('K2_JF_ID', 'lang_id');
                } else {
                    define('K2_JF_ID', 'id');
                }
            } else {
                define('K2_JF_ID', 'lang_id');
            }
        }

        // Backend only
        if (!$app->isClient('administrator')) {
            return;
        }

        // K2 Metrics
        if ($app->isClient('administrator') && $params->get('gatherStatistics', 1)) {
            $option = K2Request::getCmd('option');
            $view = K2Request::getCmd('view');
            $viewsToRun = ['items', 'categories', 'tags', 'comments', 'users', 'usergroups', 'extrafields', 'extrafieldsgroups', ''];
            if ($option == 'com_k2' && in_array($view, $viewsToRun)) {
                require_once JPATH_ADMINISTRATOR.'/components/com_k2/helpers/stats.php';

                // TODO: Move it to onAfterDispatch
                // if (K2HelperStats::shouldLog()) {
                //     K2HelperStats::getScripts();
                // }
            }
        }

        // --- JoomFish integration [start] ---
        if ((int) K2_JVERSION < 25) {
            $option = K2Request::getCmd('option');
            $task = K2Request::getCmd('task');
            $type = K2Request::getCmd('catid');
        } else {
            $option = Joomla\CMS\Factory::getApplication()->input->get('option');
            $task = Joomla\CMS\Factory::getApplication()->input->get('task');
            $type = K2Request::getCmd('catid');
        }

        if ($option == 'com_joomfish') {
            Joomla\CMS\Plugin\CMSPlugin::loadLanguage('com_k2', JPATH_ADMINISTRATOR);
            Joomla\CMS\Table\Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');

            if (($task == 'translate.apply' || $task == 'translate.save') && $type == 'k2_items') {
                $language_id = K2Request::getInt('select_language_id');
                $reference_id = K2Request::getInt('reference_id');
                $objects = [];
                $variables = K2Request::getPost();

                foreach ($variables as $key => $value) {
                    if ((bool) stristr($key, 'K2ExtraField_')) {
                        $object = new stdClass();
                        $object->id = substr($key, 13);
                        $object->value = $value;
                        $objects[] = $object;
                    }
                }

                $extra_fields = json_encode($objects);
                $extra_fields_search = '';

                foreach ($objects as $object) {
                    $extra_fields_search .= $this->getSearchValue($object->id, $object->value);
                    $extra_fields_search .= ' ';
                }

                $user = Joomla\CMS\Factory::getUser();

                $db = Joomla\CMS\Factory::getDbo();
                $query = sprintf("SELECT COUNT(*) FROM #__jf_content WHERE reference_field = 'extra_fields' AND language_id = %s AND reference_id = %s AND reference_table='k2_items'", $language_id, $reference_id);
                $db->setQuery($query);
                $result = $db->loadResult();

                if ($result > 0) {
                    $query = 'UPDATE #__jf_content SET value='.$db->Quote($extra_fields).sprintf(" WHERE reference_field = 'extra_fields' AND language_id = %s AND reference_id = %s AND reference_table='k2_items'", $language_id, $reference_id);
                    $db->setQuery($query);
                    $db->execute();
                } else {
                    $modified = date('Y-m-d H:i:s');
                    $modified_by = $user->id;
                    $published = K2Request::getVar('published', 0);
                    $query = sprintf("INSERT INTO #__jf_content (`id`, `language_id`, `reference_id`, `reference_table`, `reference_field` ,`value`, `original_value`, `original_text`, `modified`, `modified_by`, `published`) VALUES (NULL, %s, %s, 'k2_items', 'extra_fields', ", $language_id, $reference_id).$db->Quote($extra_fields).", '','', ".$db->Quote($modified).sprintf(', %s, %s )', $modified_by, $published);
                    $db->setQuery($query);
                    $db->execute();
                }

                $query = sprintf("SELECT COUNT(*) FROM #__jf_content WHERE reference_field = 'extra_fields_search' AND language_id = %s AND reference_id = %s AND reference_table='k2_items'", $language_id, $reference_id);
                $db->setQuery($query);
                $result = $db->loadResult();

                if ($result > 0) {
                    $query = 'UPDATE #__jf_content SET value='.$db->Quote($extra_fields_search).sprintf(" WHERE reference_field = 'extra_fields_search' AND language_id = %s AND reference_id = %s AND reference_table='k2_items'", $language_id, $reference_id);
                    $db->setQuery($query);
                    $db->execute();
                } else {
                    $modified = date('Y-m-d H:i:s');
                    $modified_by = $user->id;
                    $published = K2Request::getVar('published', 0);
                    $query = sprintf("INSERT INTO #__jf_content (`id`, `language_id`, `reference_id`, `reference_table`, `reference_field` ,`value`, `original_value`, `original_text`, `modified`, `modified_by`, `published`) VALUES (NULL, %s, %s, 'k2_items', 'extra_fields_search', ", $language_id, $reference_id).$db->Quote($extra_fields_search).", '','', ".$db->Quote($modified).sprintf(', %s, %s )', $modified_by, $published);
                    $db->setQuery($query);
                    $db->execute();
                }
            }

            if (($task == 'translate.edit' || $task == 'translate.apply') && $type == 'k2_items') {
                if ($task == 'translate.edit') {
                    $cid = K2Request::getVar('cid');
                    $array = explode('|', $cid[0]);
                    $reference_id = $array[1];
                }

                if ($task == 'translate.apply') {
                    $reference_id = K2Request::getInt('reference_id');
                }

                $item = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
                $item->load($reference_id);
                $category_id = $item->catid;
                $language_id = K2Request::getInt('select_language_id');
                $category = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
                $category->load($category_id);
                $group = $category->extraFieldsGroup;
                $db = Joomla\CMS\Factory::getDbo();
                $query = 'SELECT * FROM #__k2_extra_fields WHERE `group`='.$db->Quote($group).' AND published=1 ORDER BY ordering';
                $db->setQuery($query);
                $extraFields = $db->loadObjectList();

                $output = '';
                if (count($extraFields) > 0) {
                    $output .= '<h1>'.Joomla\CMS\Language\Text::_('K2_EXTRA_FIELDS').'</h1>';
                    $output .= '<h2>'.Joomla\CMS\Language\Text::_('K2_ORIGINAL').'</h2>';
                    foreach ($extraFields as $extrafield) {
                        $extraField = json_decode($extrafield->value);
                        $output .= trim($this->renderOriginal($extrafield, $reference_id));
                    }
                }

                if (count($extraFields) > 0) {
                    $output .= '<h2>'.Joomla\CMS\Language\Text::_('K2_TRANSLATION').'</h2>';
                    foreach ($extraFields as $extrafield) {
                        $extraField = json_decode($extrafield->value);
                        $output .= trim($this->renderTranslated($extrafield, $reference_id));
                    }
                }

                $pattern = '/\r\n|\r|\n/';

                // Load CSS & JS
                if (K2_JVERSION === '15') {
                    K2Behavior::mootools();
                } else {
                    K2Behavior::framework();
                }

                $document = Joomla\CMS\Factory::getDocument();
                $document->addScriptDeclaration("
                    window.addEvent('domready', function() {
                        var target = $$('table.adminform');
                        target.setProperty('id', 'adminform');
                        var div = new Element('div', {'id': 'K2ExtraFields'}).setHTML('".preg_replace($pattern, '', $output)."').injectInside($('adminform'));
                    });
                ");
            }

            if (($task == 'translate.apply' || $task == 'translate.save') && $type == 'k2_extra_fields') {
                $language_id = K2Request::getInt('select_language_id');
                $reference_id = K2Request::getInt('reference_id');
                $extraFieldType = K2Request::getVar('extraFieldType');

                $objects = [];
                $values = K2Request::getVar('option_value');
                $names = K2Request::getVar('option_name');
                $target = K2Request::getVar('option_target');
                $counter = count($values);

                for ($i = 0; $i < $counter; $i++) {
                    $object = new stdClass();
                    $object->name = $names[$i];

                    if ($extraFieldType == 'select' || $extraFieldType == 'multipleSelect' || $extraFieldType == 'radio') {
                        $object->value = $i + 1;
                    } elseif ($extraFieldType == 'link') {
                        $values[$i] = str_starts_with($values[$i], 'http') ? $values[$i] : 'http://'.$values[$i];

                        $object->value = $values[$i];
                    } else {
                        $object->value = $values[$i];
                    }

                    $object->target = $target[$i];
                    $objects[] = $object;
                }

                $value = json_encode($objects);

                $user = Joomla\CMS\Factory::getUser();

                $db = Joomla\CMS\Factory::getDbo();
                $query = sprintf("SELECT COUNT(*) FROM #__jf_content WHERE reference_field = 'value' AND language_id = %s AND reference_id = %s AND reference_table='k2_extra_fields'", $language_id, $reference_id);
                $db->setQuery($query);
                $result = $db->loadResult();

                if ($result > 0) {
                    $query = 'UPDATE #__jf_content SET value='.$db->Quote($value).sprintf(" WHERE reference_field = 'value' AND language_id = %s AND reference_id = %s AND reference_table='k2_extra_fields'", $language_id, $reference_id);
                    $db->setQuery($query);
                    $db->execute();
                } else {
                    $modified = date('Y-m-d H:i:s');
                    $modified_by = $user->id;
                    $published = K2Request::getVar('published', 0);
                    $query = sprintf("INSERT INTO #__jf_content (`id`, `language_id`, `reference_id`, `reference_table`, `reference_field` ,`value`, `original_value`, `original_text`, `modified`, `modified_by`, `published`) VALUES (NULL, %s, %s, 'k2_extra_fields', 'value', ", $language_id, $reference_id).$db->Quote($value).", '','', ".$db->Quote($modified).sprintf(', %s, %s )', $modified_by, $published);
                    $db->setQuery($query);
                    $db->execute();
                }
            }

            if (($task == 'translate.edit' || $task == 'translate.apply') && $type == 'k2_extra_fields') {
                if ($task == 'translate.edit') {
                    $cid = K2Request::getVar('cid');
                    $array = explode('|', $cid[0]);
                    $reference_id = $array[1];
                }

                if ($task == 'translate.apply') {
                    $reference_id = K2Request::getInt('reference_id');
                }

                $extraField = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
                $extraField->load($reference_id);
                $language_id = K2Request::getInt('select_language_id');

                if ($extraField->type == 'multipleSelect' || $extraField->type == 'select' || $extraField->type == 'radio') {
                    $subheader = '<strong>'.Joomla\CMS\Language\Text::_('K2_OPTIONS').'</strong>';
                } else {
                    $subheader = '<strong>'.Joomla\CMS\Language\Text::_('K2_DEFAULT_VALUE').'</strong>';
                }

                $objects = json_decode($extraField->value);
                $output = '<input type="hidden" value="'.$extraField->type.'" name="extraFieldType" />';
                if (count($objects) > 0) {
                    $output .= '<h1>'.Joomla\CMS\Language\Text::_('K2_EXTRA_FIELDS').'</h1>';
                    $output .= '<h2>'.Joomla\CMS\Language\Text::_('K2_ORIGINAL').'</h2>';
                    $output .= $subheader.'<br />';

                    foreach ($objects as $object) {
                        $output .= '<p>'.$object->name.'</p>';
                        if ($extraField->type == 'textfield' || $extraField->type == 'textarea') {
                            $output .= '<p>'.$object->value.'</p>';
                        }
                    }
                }

                $db = Joomla\CMS\Factory::getDbo();
                $query = sprintf("SELECT `value` FROM #__jf_content WHERE reference_field = 'value' AND language_id = %s AND reference_id = %s AND reference_table='k2_extra_fields'", $language_id, $reference_id);
                $db->setQuery($query);
                $result = $db->loadResult();

                $translatedObjects = json_decode($result);

                if (count($objects) > 0) {
                    $output .= '<h2>'.Joomla\CMS\Language\Text::_('K2_TRANSLATION').'</h2>';
                    $output .= $subheader.'<br />';
                    foreach ($objects as $key => $value) {
                        if (isset($translatedObjects[$key])) {
                            $value = $translatedObjects[$key];
                        }

                        if ($extraField->type == 'textarea') {
                            $output .= '<p><textarea name="option_name[]" cols="30" rows="15"> '.$value->name.'</textarea></p>';
                        } else {
                            $output .= '<p><input type="text" name="option_name[]" value="'.$value->name.'" /></p>';
                        }

                        $output .= '<p><input type="hidden" name="option_value[]" value="'.$value->value.'" /></p>';
                        $output .= '<p><input type="hidden" name="option_target[]" value="'.$value->target.'" /></p>';
                    }
                }

                $pattern = '/\r\n|\r|\n/';

                // Load CSS & JS
                if (K2_JVERSION === '15') {
                    K2Behavior::mootools();
                } else {
                    K2Behavior::framework();
                }

                $document = Joomla\CMS\Factory::getDocument();
                $document->addScriptDeclaration("
                    window.addEvent('domready', function() {
                        var target = $$('table.adminform');
                        target.setProperty('id', 'adminform');
                        var div = new Element('div', {'id': 'K2ExtraFields'}).setHTML('".preg_replace($pattern, '', $output)."').injectInside($('adminform'));
                    });
                ");
            }
        }

        // --- JoomFish integration [finish] ---
    }

    public function onAfterRoute()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $document = Joomla\CMS\Factory::getDocument();
        $user = Joomla\CMS\Factory::getUser();

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');

        $basepath = ($app->isClient('site')) ? JPATH_SITE : JPATH_ADMINISTRATOR;
        Joomla\CMS\Plugin\CMSPlugin::loadLanguage('com_k2', $basepath);
        if (K2_JVERSION != '15') {
            Joomla\CMS\Plugin\CMSPlugin::loadLanguage('com_k2.dates', JPATH_ADMINISTRATOR, null, true);
        }

        if ($app->isClient('administrator') || (K2Request::getCmd('option') == 'com_k2' && (K2Request::getCmd('task') == 'add' || K2Request::getCmd('task') == 'edit'))) {
            return;
        }

        // Load required CSS & JS
        K2HelperHTML::loadHeadIncludes();
    }

    public function onAfterDispatch()
    {
        $app = Joomla\CMS\Factory::getApplication();

        if ($app->isClient('administrator')) {
            return;
        }

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        if (!$params->get('K2UserProfile')) {
            return;
        }

        $document = Joomla\CMS\Factory::getDocument();

        $option = K2Request::getCmd('option');
        $view = K2Request::getCmd('view');
        $task = K2Request::getCmd('task');
        $layout = K2Request::getCmd('layout');
        $user = Joomla\CMS\Factory::getUser();

        // Import plugins
        Joomla\CMS\Plugin\PluginHelper::importPlugin('k2');
        $dispatcher = K2Dispatcher::getInstance();

        if (K2_JVERSION != '15') {
            $active = Joomla\CMS\Factory::getApplication()->getMenu()->getActive();
            if (isset($active->query['layout'])) {
                $layout = $active->query['layout'];
            }
        }

        // B/C code for reCAPTCHA
        $params->set('recaptchaV2', true);

        // Extend user forms with K2 fields
        if (($option == 'com_user' && $view == 'register') || ($option == 'com_users' && $view == 'registration')) {
            if ($params->get('recaptchaOnRegistration') && $params->get('recaptcha_public_key')) {
                if (K2_JVERSION != '30' && Joomla\CMS\Plugin\PluginHelper::isEnabled('system', \MTUPGRADE) !== false) {
                    $document->addScript(Joomla\CMS\Uri\Uri::root(true).'/media/k2/assets/js/k2.rc.patch.js?v='.K2_CURRENT_VERSION.'&b='.K2_BUILD_ID);
                }

                $document->addScript('https://www.google.com/recaptcha/api.js?onload=onK2RecaptchaLoaded&render=explicit');
                $document->addScriptDeclaration('
                function onK2RecaptchaLoaded() {
                    grecaptcha.render("recaptcha", {
                        "sitekey": "'.$params->get('recaptcha_public_key').'",
                        "theme": "'.$params->get('recaptcha_theme', 'light').'"
                    });
                }
                ');
                $recaptchaClass = 'k2-recaptcha-v2';
            }

            if (!$user->guest) {
                $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_YOU_ARE_ALREADY_REGISTERED_AS_A_MEMBER'), 'notice');
                $app->redirect(Joomla\CMS\Uri\Uri::root());
                $app->close();
            }

            if (K2_JVERSION != '15') {
                require_once JPATH_SITE.'/components/com_users/controller.php';
                $controller = new UsersController();
            } else {
                require_once JPATH_SITE.'/components/com_user/controller.php';
                $controller = new UserController();
            }

            $view = $controller->getView($view, 'html');
            $view->addTemplatePath(JPATH_SITE.'/components/com_k2/templates');
            $view->addTemplatePath(JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2/templates');
            $view->addTemplatePath(JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2');
            // Allow temporary template loading with ?template=
            $template = K2Request::getCmd('template');
            if (isset($template)) {
                $view->addTemplatePath(JPATH_SITE.'/templates/'.$template.'/html/com_k2');
            }

            $view->setLayout('register');

            $K2User = new stdClass();

            $K2User->description = '';
            $K2User->gender = 'n';
            $K2User->image = '';
            $K2User->url = '';
            $K2User->plugins = '';

            if ($params->get('K2ProfileEditor')) {
                $wysiwyg = Joomla\CMS\Factory::getEditor();
                $editor = $wysiwyg->display('description', $K2User->description, '100%', '250px', '', '', false);
            } else {
                $editor = '<textarea id="description" class="k2-plain-text-editor" name="description"></textarea>';
            }

            $view->assignRef('editor', $editor);

            $lists = [];
            $genderOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'n', Joomla\CMS\Language\Text::_('K2_NOT_SPECIFIED'));
            $genderOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'm', Joomla\CMS\Language\Text::_('K2_MALE'));
            $genderOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'f', Joomla\CMS\Language\Text::_('K2_FEMALE'));
            $lists['gender'] = Joomla\CMS\HTML\HTMLHelper::_('select.radiolist', $genderOptions, 'gender', '', 'value', 'text', $K2User->gender);

            $view->assignRef('lists', $lists);
            $view->assignRef('K2Params', $params);
            $view->assignRef('recaptchaClass', $recaptchaClass);

            $K2Plugins = $dispatcher->trigger('onRenderAdminForm', [
                &$K2User,
                'user',
            ]);
            $view->assignRef('K2Plugins', $K2Plugins);

            $view->assignRef('K2User', $K2User);
            if (K2_JVERSION != '15') {
                $view->assignRef('user', $user);
            }

            $pathway = $app->getPathway();
            $pathway->setPathway(null);

            $nameFieldName = K2_JVERSION != '15' ? 'jform[name]' : 'name';
            $view->assignRef('nameFieldName', $nameFieldName);
            $usernameFieldName = K2_JVERSION != '15' ? 'jform[username]' : 'username';
            $view->assignRef('usernameFieldName', $usernameFieldName);
            $emailFieldName = K2_JVERSION != '15' ? 'jform[email1]' : 'email';
            $view->assignRef('emailFieldName', $emailFieldName);
            $passwordFieldName = K2_JVERSION != '15' ? 'jform[password1]' : 'password';
            $view->assignRef('passwordFieldName', $passwordFieldName);
            $passwordVerifyFieldName = K2_JVERSION != '15' ? 'jform[password2]' : 'password2';
            $view->assignRef('passwordVerifyFieldName', $passwordVerifyFieldName);
            $optionValue = K2_JVERSION != '15' ? 'com_users' : 'com_user';
            $view->assignRef('optionValue', $optionValue);
            $taskValue = K2_JVERSION != '15' ? 'registration.register' : 'register_save';
            $view->assignRef('taskValue', $taskValue);
            ob_start();
            $view->display();
            $contents = ob_get_clean();
            $document->setBuffer($contents, 'component');
        }

        if (($option == 'com_user' && $view == 'user' && ($task == 'edit' || $layout == 'form')) || ($option == 'com_users' && $view == 'profile' && ($layout == 'edit' || $task == 'profile.edit'))) {
            if ($user->guest) {
                $uri = Joomla\CMS\Uri\Uri::getInstance();

                if (K2_JVERSION != '15') {
                    $url = 'index.php?option=com_users&view=login&return='.base64_encode($uri->toString());
                } else {
                    $url = 'index.php?option=com_user&view=login&return='.base64_encode($uri->toString());
                }

                $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_YOU_NEED_TO_LOGIN_FIRST'), 'notice');
                $app->redirect(Joomla\CMS\Router\Route::_($url, false));
            }

            if (K2_JVERSION != '15') {
                require_once JPATH_SITE.'/components/com_users/controller.php';
                $controller = new UsersController();
            } else {
                require_once JPATH_SITE.'/components/com_user/controller.php';
                $controller = new UserController();
            }

            $view = $controller->getView($view, 'html');
            $view->addTemplatePath(JPATH_SITE.'/components/com_k2/templates');
            $view->addTemplatePath(JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2/templates');
            $view->addTemplatePath(JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2');
            // Allow temporary template loading with ?template=
            $template = K2Request::getCmd('template');
            if (isset($template)) {
                $view->addTemplatePath(JPATH_SITE.'/templates/'.$template.'/html/com_k2');
            }

            $view->setLayout('profile');

            $model = K2Model::getInstance('Itemlist', 'K2Model');
            $K2User = $model->getUserProfile($user->id);
            if (!is_object($K2User)) {
                $K2User = new stdClass();
                $K2User->description = '';
                $K2User->gender = 'n';
                $K2User->url = '';
                $K2User->image = null;
            }

            if (K2_JVERSION == '15') {
                Joomla\CMS\Filter\OutputFilter::objectHTMLSafe($K2User);
            } else {
                Joomla\CMS\Filter\OutputFilter::objectHTMLSafe($K2User, ENT_QUOTES, [
                    'params',
                    'plugins',
                ]);
            }

            if ($params->get('K2ProfileEditor')) {
                $wysiwyg = Joomla\CMS\Factory::getEditor();
                $editor = $wysiwyg->display('description', $K2User->description, '100%', '250px', '', '', false);
            } else {
                $editor = '<textarea id="description" class="k2-plain-text-editor" name="description"></textarea>';
            }

            $view->assignRef('editor', $editor);

            $lists = [];
            $genderOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'n', Joomla\CMS\Language\Text::_('K2_NOT_SPECIFIED'));
            $genderOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'm', Joomla\CMS\Language\Text::_('K2_MALE'));
            $genderOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'f', Joomla\CMS\Language\Text::_('K2_FEMALE'));
            $lists['gender'] = Joomla\CMS\HTML\HTMLHelper::_('select.radiolist', $genderOptions, 'gender', '', 'value', 'text', $K2User->gender);

            $view->assignRef('lists', $lists);

            $K2Plugins = $dispatcher->trigger('onRenderAdminForm', [
                &$K2User,
                'user',
            ]);
            $view->assignRef('K2Plugins', $K2Plugins);

            $view->assignRef('K2User', $K2User);
            $view->assignRef('K2Params', $params);

            // Asssign some variables depending on Joomla version
            $nameFieldName = K2_JVERSION != '15' ? 'jform[name]' : 'name';
            $view->assignRef('nameFieldName', $nameFieldName);
            $emailFieldName = K2_JVERSION != '15' ? 'jform[email1]' : 'email';
            $view->assignRef('emailFieldName', $emailFieldName);
            $passwordFieldName = K2_JVERSION != '15' ? 'jform[password1]' : 'password';
            $view->assignRef('passwordFieldName', $passwordFieldName);
            $passwordVerifyFieldName = K2_JVERSION != '15' ? 'jform[password2]' : 'password2';
            $view->assignRef('passwordVerifyFieldName', $passwordVerifyFieldName);
            $usernameFieldName = K2_JVERSION != '15' ? 'jform[username]' : 'username';
            $view->assignRef('usernameFieldName', $usernameFieldName);
            $idFieldName = K2_JVERSION != '15' ? 'jform[id]' : 'id';
            $view->assignRef('idFieldName', $idFieldName);
            $optionValue = K2_JVERSION != '15' ? 'com_users' : 'com_user';
            $view->assignRef('optionValue', $optionValue);
            $taskValue = K2_JVERSION != '15' ? 'profile.save' : 'save';
            $view->assignRef('taskValue', $taskValue);

            ob_start();
            if (K2_JVERSION != '15') {
                $active = Joomla\CMS\Factory::getApplication()->getMenu()->getActive();
                if (isset($active->query['layout']) && $active->query['layout'] != 'profile') {
                    $active->query['layout'] = 'profile';
                }

                $view->assignRef('user', $user);
                $view->display();
            } else {
                $view->_displayForm();
            }

            $contents = ob_get_clean();
            $document->setBuffer($contents, 'component');
        }
    }

    public function onAfterRender()
    {
        $app = Joomla\CMS\Factory::getApplication();

        if ($app->isClient('site')) {
            $config = Joomla\CMS\Factory::getConfig();
            $document = Joomla\CMS\Factory::getDocument();
            $user = Joomla\CMS\Factory::getUser();
            $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
            $response = JResponse::getBody();

            // Use proper headers for JSON/JSONP
            if (K2Request::getCmd('format') == 'json') {
                if (K2_JVERSION == '15') {
                    $document->setMimeEncoding('application/json');
                    $document->setType('json');
                }

                if (K2Request::getCmd('callback')) {
                    $document->setMimeEncoding('application/javascript');
                }
            }

            // Check caching state in Joomla
            $cacheTime = 0;
            if (K2_JVERSION == '15') {
                $caching = $config->getValue('config.caching');
                $cacheTime = $config->getValue('config.cachetime');
            } else {
                $caching = $config->get('caching');
                $cacheTime = $config->get('cachetime');
            }

            $cacheTTL = $cacheTime * 60;

            // Set caching HTTP headers
            if ($user->guest) {
                if ($caching) {
                    JResponse::allowCache(true);
                    JResponse::setHeader('Cache-Control', 'public, max-age='.$cacheTTL.', stale-while-revalidate='.($cacheTTL * 2).', stale-if-error='.($cacheTTL * 5), true);
                    JResponse::setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $cacheTTL).' GMT', true);
                    JResponse::setHeader('Pragma', 'public', true);
                }

                JResponse::setHeader('X-Logged-In', 'False', true);
            } else {
                JResponse::setHeader('X-Logged-In', 'True', true);
            }

            JResponse::setHeader('X-Content-Powered-By', 'K2 v'.K2_CURRENT_VERSION.' (by JoomlaWorks)', true);

            // Set additional caching HTTP headers defined as custom script tag in the <head>
            if ($caching) {
                preg_match("#<script type=\"application/x\-k2\-headers\">(.*?)</script>#is", $response, $getK2CacheHeaders);
                if (is_array($getK2CacheHeaders) && (isset($getK2CacheHeaders[1]) && ($getK2CacheHeaders[1] !== '' && $getK2CacheHeaders[1] !== '0'))) {
                    $getK2CacheHeaders = json_decode(trim($getK2CacheHeaders[1]));
                    if (is_object($getK2CacheHeaders)) {
                        JResponse::allowCache(true);
                        foreach ($getK2CacheHeaders as $type => $value) {
                            JResponse::setHeader($type, $value, true);
                        }
                    }
                }
            }

            // OpenGraph meta tags
            if ($params->get('facebookMetatags', 1)) {
                $searches = [
                    '<meta name="og:url"',
                    '<meta name="og:title"',
                    '<meta name="og:type"',
                    '<meta name="og:image"',
                    '<meta name="og:description"',
                ];
                $replacements = [
                    '<meta property="og:url"',
                    '<meta property="og:title"',
                    '<meta property="og:type"',
                    '<meta property="og:image"',
                    '<meta property="og:description"',
                ];
                if (!str_contains($response, 'http://ogp.me/ns#')) {
                    $searches[] = '<html ';
                    $searches[] = '<html>';
                    $replacements[] = '<html prefix="og: http://ogp.me/ns#" ';
                    $replacements[] = '<html prefix="og: http://ogp.me/ns#">';
                }

                $response = str_ireplace($searches, $replacements, $response);
                JResponse::setBody($response);
            }
        }
    }

    /* ============================================ */
    /* ============= Helper Functions ============= */
    /* ============================================ */

    public function getSearchValue($id, $currentValue)
    {
        Joomla\CMS\Table\Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
        $row = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
        $row->load($id);

        $jsonObject = json_decode($row->value);
        $value = '';
        if ($row->type == 'textfield' || $row->type == 'textarea') {
            $value = $currentValue;
        } elseif ($row->type == 'multipleSelect' || $row->type == 'link') {
            foreach ($jsonObject as $option) {
                if (@in_array($option->value, $currentValue)) {
                    $value .= $option->name.' ';
                }
            }
        } else {
            foreach ($jsonObject as $option) {
                if ($option->value == $currentValue) {
                    $value .= $option->name;
                }
            }
        }

        return $value;
    }

    public function renderOriginal($extraField, $itemID)
    {
        $app = Joomla\CMS\Factory::getApplication();
        Joomla\CMS\Table\Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
        $item = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
        $item->load($itemID);

        $defaultValues = json_decode($extraField->value);

        foreach ($defaultValues as $defaultValue) {
            if ($extraField->type == 'textfield' || $extraField->type == 'textarea') {
                $active = $defaultValue->value;
            } elseif ($extraField->type == 'link') {
                $active[0] = $defaultValue->name;
                $active[1] = $defaultValue->value;
                $active[2] = $defaultValue->target;
            } else {
                $active = '';
            }
        }

        if (isset($item)) {
            $currentValues = json_decode($item->extra_fields);
            if (count($currentValues) > 0) {
                foreach ($currentValues as $currentValue) {
                    if ($currentValue->id == $extraField->id) {
                        $active = $currentValue->value;
                    }
                }
            }
        }

        $output = '';

        switch ($extraField->type) {
            case 'textfield':
                $output = '<div><strong>'.$extraField->name.'</strong><br /><input type="text" disabled="disabled" name="OriginalK2ExtraField_'.$extraField->id.'" value="'.$active.'" /></div><br /><br />';
                break;

            case 'textarea':
                $output = '<div><strong>'.$extraField->name.'</strong><br /><textarea disabled="disabled" name="OriginalK2ExtraField_'.$extraField->id.'" rows="10" cols="40">'.$active.'</textarea></div><br /><br />';
                break;

            case 'link':
                $output = '<div><strong>'.$extraField->name.'</strong><br /><input disabled="disabled" type="text" name="OriginalK2ExtraField_'.$extraField->id.'[]" value="'.$active[0].'" /></div><br /><br />';
                break;
        }

        return $output;
    }

    public function renderTranslated($extraField, $itemID)
    {
        $app = Joomla\CMS\Factory::getApplication();
        Joomla\CMS\Table\Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
        $item = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
        $item->load($itemID);

        $defaultValues = json_decode($extraField->value);

        foreach ($defaultValues as $defaultValue) {
            if ($extraField->type == 'textfield' || $extraField->type == 'textarea') {
                $active = $defaultValue->value;
            } elseif ($extraField->type == 'link') {
                $active[0] = $defaultValue->name;
                $active[1] = $defaultValue->value;
                $active[2] = $defaultValue->target;
            } else {
                $active = '';
            }
        }

        if (isset($item)) {
            $currentValues = json_decode($item->extra_fields);
            if (count($currentValues) > 0) {
                foreach ($currentValues as $currentValue) {
                    if ($currentValue->id == $extraField->id) {
                        $active = $currentValue->value;
                    }
                }
            }
        }

        $language_id = K2Request::getInt('select_language_id');
        $db = Joomla\CMS\Factory::getDbo();
        $query = sprintf("SELECT `value` FROM #__jf_content WHERE reference_field = 'extra_fields' AND language_id = %s AND reference_id = %s AND reference_table='k2_items'", $language_id, $itemID);
        $db->setQuery($query);
        $result = $db->loadResult();
        $currentValues = json_decode($result);
        if (count($currentValues) > 0) {
            foreach ($currentValues as $currentValue) {
                if ($currentValue->id == $extraField->id) {
                    $active = $currentValue->value;
                }
            }
        }

        $output = '';

        switch ($extraField->type) {
            case 'textfield':
                $output = '<div><strong>'.$extraField->name.'</strong><br /><input type="text" name="K2ExtraField_'.$extraField->id.'" value="'.$active.'" /></div><br /><br />';
                break;

            case 'textarea':
                $output = '<div><strong>'.$extraField->name.'</strong><br /><textarea name="K2ExtraField_'.$extraField->id.'" rows="10" cols="40">'.$active.'</textarea></div><br /><br />';
                break;

            case 'select':
                $output = '<div style="display:none;">'.Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $defaultValues, 'K2ExtraField_'.$extraField->id, '', 'value', 'name', $active).'</div>';
                break;

            case 'multipleSelect':
                $output = '<div style="display:none;">'.Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $defaultValues, 'K2ExtraField_'.$extraField->id.'[]', 'multiple="multiple"', 'value', 'name', $active).'</div>';
                break;

            case 'radio':
                $output = '<div style="display:none;">'.Joomla\CMS\HTML\HTMLHelper::_('select.radiolist', $defaultValues, 'K2ExtraField_'.$extraField->id, '', 'value', 'name', $active).'</div>';
                break;

            case 'link':
                $output = '<div><strong>'.$extraField->name.'</strong><br /><input type="text" name="K2ExtraField_'.$extraField->id.'[]" value="'.$active[0].'" /><br /><input type="hidden" name="K2ExtraField_'.$extraField->id.'[]" value="'.$active[1].'" /><br /><input type="hidden" name="K2ExtraField_'.$extraField->id.'[]" value="'.$active[2].'" /></div><br /><br />';
                break;
        }

        return $output;
    }
}
