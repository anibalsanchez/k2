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

class K2ViewComments extends K2View
{
    /**
     * @var string
     */
    public $recaptchaClass;

    public function report($tpl = null)
    {
        $params = K2HelperUtilities::getParams('com_k2');
        $document = Joomla\CMS\Factory::getDocument();
        $user = Joomla\CMS\Factory::getUser();

        Joomla\CMS\Table\Table::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/tables');
        $row = Joomla\CMS\Table\Table::getInstance('K2Comment', 'Table');
        $row->load(K2Request::getInt('commentID'));
        if (!$row->published) {
            JError::raiseError(404, Joomla\CMS\Language\Text::_('K2_NOT_FOUND'));
        }

        if (!$params->get('comments') || !$params->get('commentsReporting') || ($params->get('commentsReporting') == '2' && $user->guest)) {
            JError::raiseError(403, Joomla\CMS\Language\Text::_('K2_ALERTNOTAUTH'));
        }

        // B/C code for reCAPTCHA
        if ($params->get('antispam') == 'recaptcha' || $params->get('antispam') == 'both') {
            $params->set('recaptcha', true);
        } else {
            $params->set('recaptcha', false);
        }

        $params->set('recaptchaV2', true);

        // Load reCAPTCHA
        if ($params->get('recaptcha') && ($user->guest || $params->get('recaptchaForRegistered', 1))) {
            $document->addScript('https://www.google.com/recaptcha/api.js?onload=onK2RecaptchaLoaded&render=explicit');
            $document->addScriptDeclaration('
                function onK2RecaptchaLoaded() {
                    grecaptcha.render("recaptcha", {
                        "sitekey": "'.$item->params->get('recaptcha_public_key').'",
                        "theme": "'.$item->params->get('recaptcha_theme', 'light').'"
                    });
                }
            ');
            $this->recaptchaClass = 'k2-recaptcha-v2';
        }

        $this->assignRef('row', $row);
        $this->assignRef('user', $user);
        $this->assignRef('params', $params);

        parent::display($tpl);
    }
}
