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

require_once JPATH_ADMINISTRATOR.'/components/com_k2/elements/base.php';

class K2ElementK2Users extends K2Element
{
    public function fetchElement($name, $value, &$node, $control_name)
    {
        $fieldName = (K2_JVERSION != '15') ? $name.'[]' : $control_name.'['.$name.'][]';

        $document = Joomla\CMS\Factory::getDocument();
        $document->addStyleSheet('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
        $document->addScript('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js');
        $document->addScriptDeclaration('
			$K2(document).ready(function() {
				if (typeof($K2(".k2UsersElement").chosen) == "function") {
					$K2(".k2UsersElement").chosen("destroy");
				}
				$K2(".k2UsersElement").select2({
					width : "300px",
					minimumInputLength : 2,
					ajax: {
						dataType : "json",
						url: "'.Joomla\CMS\Uri\Uri::root(true).'/administrator/index.php?option=com_k2&view=users&task=search&format=raw",
						cache: "true",
						 data: function (params) {
						 	var queryParameters = {q: params.term};
						 	return queryParameters;
						 },
						 processResults: function (data) {
						 	var results = [];
						 	jQuery.each(data, function(index, value) {
						 		var row = {
						 			id : value.id,
						 			text : value.name
						 		};
								results.push(row);
						 	});
						 	return {results: results};
						 }

					}
				});
			});
		');

        $options = [];
        if (is_array($value) && count($value)) {
            $db = Joomla\CMS\Factory::getDbo();
            $query = 'SELECT id AS value, name AS text FROM #__users WHERE id IN('.implode(',', $value).')';
            $db->setQuery($query);
            $options = $db->loadObjectList();
        }

        return Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $options, $fieldName, 'class="k2UsersElement" multiple="multiple" size="15"', 'value', 'text', $value);
    }
}

class JFormFieldK2Users extends K2ElementK2Users
{
    public $type = 'k2users';
}

class JElementK2Users extends K2ElementK2Users
{
    public $_name = 'k2users';
}
