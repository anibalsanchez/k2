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

K2HelperHTML::loadHeadIncludes(true, true, false, true);

if (K2_JVERSION == '15') {
    jimport('joomla.html.parameter.element');
    class K2Element extends JElement
    {
    }
} else {
    jimport('joomla.form.formfield');
    if (version_compare(JVERSION, '3.5.0', 'ge')) {
        class K2Element extends Joomla\CMS\Form\FormField
        {
            public $options;

            public $name;

            public $value;

            public $element;

            public $description;

            public function getInput()
            {
                /*
                if (method_exists($this,'fetchElement')) { // BC
                   return $this->fetchElement($this->name, $this->value, $this->element, $this->options['control']);
                }
                return $this->fetchElementValue($this->name, $this->value, $this->element, $this->options['control']);
                */
                $controls = (empty($this->options['control'])) ? [] : $this->options['control'];

                return $this->fetchElement($this->name, $this->value, $this->element, $controls);
            }

            public function getLabel()
            {
                /*
                if (method_exists($this, 'fetchElementName')) {
                    return $this->fetchElementName($this->element['label'], $this->description, $this->element, $this->options['control'], $this->element['name'] = '');
                }
                */
                if (method_exists($this, 'fetchTooltip')) { // BC
                    $controls = (empty($this->options['control'])) ? [] : $this->options['control'];

                    return $this->fetchTooltip($this->element['label'], $this->description, $this->element, $controls, $this->element['name'] = '');
                }

                return parent::getLabel();
            }

            public function render($layoutId, $data = [])
            {
                return $this->getInput();
            }
        }
    } else {
        class K2Element extends Joomla\CMS\Form\FormField
        {
            public $name;

            public $value;

            public $element;

            public $options;

            public $description;

            public function getInput()
            {
                /*
                if (method_exists($this, 'fetchElement')) { // BC
                    return $this->fetchElement($this->name, $this->value, $this->element, $this->options['control']);
                }
                return $this->fetchElementValue($this->name, $this->value, $this->element, $this->options['control']);
                */
                return $this->fetchElement($this->name, $this->value, $this->element, $this->options['control']);
            }

            public function getLabel()
            {
                if (method_exists($this, 'fetchTooltip')) { // BC
                    return $this->fetchTooltip($this->element['label'], $this->description, $this->element, $this->options['control'], $this->element['name'] = '');
                }

                /*
                if (method_exists($this, 'fetchElementName')) {
                    return $this->fetchElementName($this->element['label'], $this->description, $this->element, $this->options['control'], $this->element['name'] = '');
                }
                */
                return parent::getLabel();
            }

            public function render()
            {
                return $this->getInput();
            }
        }
    }
}
