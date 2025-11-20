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

class K2ViewSettings extends K2View
{
    public function display($tpl = null)
    {
        Joomla\CMS\HTML\HTMLHelper::_('behavior.tooltip');

        jimport('joomla.html.pane');

        $model = $this->getModel();

        $params = $model->getParams();
        $this->assignRef('params', $params);

        $pane = JPane::getInstance('Tabs');
        $this->assignRef('pane', $pane);

        parent::display($tpl);
    }
}
