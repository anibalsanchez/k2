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

class K2ElementCategoriesMultiple extends K2Element
{
    public function fetchElement($name, $value, &$node, $control_name)
    {
        $document = Joomla\CMS\Factory::getDocument();

        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT m.* FROM #__k2_categories m WHERE trash = 0 ORDER BY parent, ordering';
        $db->setQuery($query);
        $mitems = $db->loadObjectList();
        $children = [];
        if ($mitems) {
            foreach ($mitems as $mitem) {
                if (K2_JVERSION != '15') {
                    $mitem->title = $mitem->name;
                    $mitem->parent_id = $mitem->parent;
                }

                $pt = $mitem->parent;
                $list = @$children[$pt] ? $children[$pt] : [];
                $list[] = $mitem;
                $children[$pt] = $list;
            }
        }

        $list = Joomla\CMS\HTML\HTMLHelper::_('menu.treerecurse', 0, '', [], $children, 9999, 0, 0);
        $mitems = [];

        foreach ($list as $item) {
            $item->treename = JString::str_ireplace('&#160;', '- ', $item->treename);
            $mitems[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $item->id, '   '.$item->treename);
        }

        $doc = Joomla\CMS\Factory::getDocument();
        if (K2_JVERSION != '15') {
            $js = "
			\$K2(document).ready(function() {

				\$K2('#jform_params_catfilter0').click(function() {
					\$K2('#jformparamscategory_id').attr('disabled', 'disabled');
					\$K2('#jformparamscategory_id option').each(function() {
						\$K2(this).attr('selected', 'selected');
					});
					\$K2('#jformparamscategory_id').trigger('liszt:updated');
				});

				\$K2('#jform_params_catfilter1').click(function() {
					\$K2('#jformparamscategory_id').removeAttr('disabled');
					\$K2('#jformparamscategory_id option').each(function() {
						\$K2(this).removeAttr('selected');
					});
					\$K2('#jformparamscategory_id').trigger('liszt:updated');
				});

				if (\$K2('#jform_params_catfilter0').attr('checked')) {
					\$K2('#jformparamscategory_id').attr('disabled', 'disabled');
					\$K2('#jformparamscategory_id option').each(function() {
						\$K2(this).attr('selected', 'selected');
					});
					\$K2('#jformparamscategory_id').trigger('liszt:updated');
				}

				if (\$K2('#jform_params_catfilter1').attr('checked')) {
					\$K2('#jformparamscategory_id').removeAttr('disabled');
					\$K2('#jformparamscategory_id').trigger('liszt:updated');
				}

			});
			";
        } else {
            $js = "
			\$K2(document).ready(function() {

				\$K2('#paramscatfilter0').click(function() {
					\$K2('#paramscategory_id').attr('disabled', 'disabled');
					\$K2('#paramscategory_id option').each(function() {
						\$K2(this).attr('selected', 'selected');
					});
				});

				\$K2('#paramscatfilter1').click(function() {
					\$K2('#paramscategory_id').removeAttr('disabled');
					\$K2('#paramscategory_id option').each(function() {
						\$K2(this).removeAttr('selected');
					});

				});

				if (\$K2('#paramscatfilter0').attr('checked')) {
					\$K2('#paramscategory_id').attr('disabled', 'disabled');
					\$K2('#paramscategory_id option').each(function() {
						\$K2(this).attr('selected', 'selected');
					});
				}

				if (\$K2('#paramscatfilter1').attr('checked')) {
					\$K2('#paramscategory_id').removeAttr('disabled');
				}

			});
			";
        }

        $fieldName = K2_JVERSION != '15' ? $name.'[]' : $control_name.'['.$name.'][]';

        $doc->addScriptDeclaration($js);
        $output = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $mitems, $fieldName, 'class="inputbox" multiple="multiple" size="10"', 'value', 'text', $value);

        return $output;
    }
}

class JFormFieldCategoriesMultiple extends K2ElementCategoriesMultiple
{
    public $type = 'categoriesmultiple';
}

class JElementCategoriesMultiple extends K2ElementCategoriesMultiple
{
    public $_name = 'categoriesmultiple';
}
