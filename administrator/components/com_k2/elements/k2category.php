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

class K2ElementK2Category extends K2Element
{
    public function fetchElement($name, $value, &$node, $control_name)
    {
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
        $option = K2Request::getCmd('option');
        $prefix = ($option == 'com_joomfish') ? 'refField_' : '';
        if ($name == 'categories' || $name == 'jform[params][categories]') {
            if (version_compare(JVERSION, '3.5', 'ge')) {
                Joomla\CMS\HTML\HTMLHelper::_('behavior.framework');
            }

            $doc = Joomla\CMS\Factory::getDocument();
            $js = "
            /* Mootools Snippet */
			window.addEvent('domready', function() {
				setTask();
			});

			function setTask() {
				var counter=0;
				$$('#".$prefix."paramscategories option').each(function(el) {
					if (el.selected) {
						value=el.value;
						counter++;
					}
				});
				if (counter>1 || counter==0) {
					$('urlparamsid').setProperty('value','');
					$('urlparamstask').setProperty('value','');
					$('".$prefix."paramssingleCatOrdering').setProperty('disabled', 'disabled');
					enableParams();
				}
				if (counter==1) {
					$('urlparamsid').setProperty('value',value);
					$('urlparamstask').setProperty('value','category');
					$('".$prefix."paramssingleCatOrdering').removeProperty('disabled');
					disableParams();
				}
			}

			function disableParams() {
				$('".$prefix."paramsnum_leading_items').setProperty('disabled','disabled');
				$('".$prefix."paramsnum_leading_columns').setProperty('disabled','disabled');
				$('".$prefix."paramsleadingImgSize').setProperty('disabled','disabled');
				$('".$prefix."paramsnum_primary_items').setProperty('disabled','disabled');
				$('".$prefix."paramsnum_primary_columns').setProperty('disabled','disabled');
				$('".$prefix."paramsprimaryImgSize').setProperty('disabled','disabled');
				$('".$prefix."paramsnum_secondary_items').setProperty('disabled','disabled');
				$('".$prefix."paramsnum_secondary_columns').setProperty('disabled','disabled');
				$('".$prefix."paramssecondaryImgSize').setProperty('disabled','disabled');
				$('".$prefix."paramsnum_links').setProperty('disabled','disabled');
				$('".$prefix."paramsnum_links_columns').setProperty('disabled','disabled');
				$('".$prefix."paramslinksImgSize').setProperty('disabled','disabled');
				$('".$prefix."paramscatCatalogMode').setProperty('disabled','disabled');
				$('".$prefix."paramscatFeaturedItems').setProperty('disabled','disabled');
				$('".$prefix."paramscatOrdering').setProperty('disabled','disabled');
				$('".$prefix."paramscatPagination').setProperty('disabled','disabled');
				$('".$prefix."paramscatPaginationResults0').setProperty('disabled','disabled');
				$('".$prefix."paramscatPaginationResults1').setProperty('disabled','disabled');
				$('".$prefix."paramscatFeedLink0').setProperty('disabled','disabled');
				$('".$prefix."paramscatFeedLink1').setProperty('disabled','disabled');
				$('".$prefix."paramscatFeedIcon0').setProperty('disabled','disabled');
				$('".$prefix."paramscatFeedIcon1').setProperty('disabled','disabled');
				$('".$prefix."paramstheme').setProperty('disabled','disabled');
			}

			function enableParams() {
				$('".$prefix."paramsnum_leading_items').removeProperty('disabled');
				$('".$prefix."paramsnum_leading_columns').removeProperty('disabled');
				$('".$prefix."paramsleadingImgSize').removeProperty('disabled');
				$('".$prefix."paramsnum_primary_items').removeProperty('disabled');
				$('".$prefix."paramsnum_primary_columns').removeProperty('disabled');
				$('".$prefix."paramsprimaryImgSize').removeProperty('disabled');
				$('".$prefix."paramsnum_secondary_items').removeProperty('disabled');
				$('".$prefix."paramsnum_secondary_columns').removeProperty('disabled');
				$('".$prefix."paramssecondaryImgSize').removeProperty('disabled');
				$('".$prefix."paramsnum_links').removeProperty('disabled');
				$('".$prefix."paramsnum_links_columns').removeProperty('disabled');
				$('".$prefix."paramslinksImgSize').removeProperty('disabled');
				$('".$prefix."paramscatCatalogMode').removeProperty('disabled');
				$('".$prefix."paramscatFeaturedItems').removeProperty('disabled');
				$('".$prefix."paramscatOrdering').removeProperty('disabled');
				$('".$prefix."paramscatPagination').removeProperty('disabled');
				$('".$prefix."paramscatPaginationResults0').removeProperty('disabled');
				$('".$prefix."paramscatPaginationResults1').removeProperty('disabled');
				$('".$prefix."paramscatFeedLink0').removeProperty('disabled');
				$('".$prefix."paramscatFeedLink1').removeProperty('disabled');
				$('".$prefix."paramscatFeedIcon0').removeProperty('disabled');
				$('".$prefix."paramscatFeedIcon1').removeProperty('disabled');
				$('".$prefix."paramstheme').removeProperty('disabled');
			}
			";

            if (K2_JVERSION != '15') {
                $js = "
                /* Mootools Snippet */
				function disableParams() {
					$('jform_params_num_leading_items').setProperty('disabled','disabled');
					$('jform_params_num_leading_columns').setProperty('disabled','disabled');
					$('jform_params_leadingImgSize').setProperty('disabled','disabled');
					$('jform_params_num_primary_items').setProperty('disabled','disabled');
					$('jform_params_num_primary_columns').setProperty('disabled','disabled');
					$('jform_params_primaryImgSize').setProperty('disabled','disabled');
					$('jform_params_num_secondary_items').setProperty('disabled','disabled');
					$('jform_params_num_secondary_columns').setProperty('disabled','disabled');
					$('jform_params_secondaryImgSize').setProperty('disabled','disabled');
					$('jform_params_num_links').setProperty('disabled','disabled');
					$('jform_params_num_links_columns').setProperty('disabled','disabled');
					$('jform_params_linksImgSize').setProperty('disabled','disabled');
					$('jform_params_catCatalogMode').setProperty('disabled','disabled');
					$('jform_params_catFeaturedItems').setProperty('disabled','disabled');
					$('jform_params_catOrdering').setProperty('disabled','disabled');
					$('jform_params_catPagination').setProperty('disabled','disabled');
					$('jform_params_catPaginationResults0').setProperty('disabled','disabled');
					$('jform_params_catPaginationResults1').setProperty('disabled','disabled');
					$('jform_params_catFeedLink0').setProperty('disabled','disabled');
					$('jform_params_catFeedLink1').setProperty('disabled','disabled');
					$('jform_params_catFeedIcon0').setProperty('disabled','disabled');
					$('jform_params_catFeedIcon1').setProperty('disabled','disabled');
					$('jformparamstheme').setProperty('disabled','disabled');
				}

				function enableParams() {
					$('jform_params_num_leading_items').removeProperty('disabled');
					$('jform_params_num_leading_columns').removeProperty('disabled');
					$('jform_params_leadingImgSize').removeProperty('disabled');
					$('jform_params_num_primary_items').removeProperty('disabled');
					$('jform_params_num_primary_columns').removeProperty('disabled');
					$('jform_params_primaryImgSize').removeProperty('disabled');
					$('jform_params_num_secondary_items').removeProperty('disabled');
					$('jform_params_num_secondary_columns').removeProperty('disabled');
					$('jform_params_secondaryImgSize').removeProperty('disabled');
					$('jform_params_num_links').removeProperty('disabled');
					$('jform_params_num_links_columns').removeProperty('disabled');
					$('jform_params_linksImgSize').removeProperty('disabled');
					$('jform_params_catCatalogMode').removeProperty('disabled');
					$('jform_params_catFeaturedItems').removeProperty('disabled');
					$('jform_params_catOrdering').removeProperty('disabled');
					$('jform_params_catPagination').removeProperty('disabled');
					$('jform_params_catPaginationResults0').removeProperty('disabled');
					$('jform_params_catPaginationResults1').removeProperty('disabled');
					$('jform_params_catFeedLink0').removeProperty('disabled');
					$('jform_params_catFeedLink1').removeProperty('disabled');
					$('jform_params_catFeedIcon0').removeProperty('disabled');
					$('jform_params_catFeedIcon1').removeProperty('disabled');
					$('jformparamstheme').removeProperty('disabled');
				}

				function setTask() {
					var counter=0;
					$$('#jformparamscategories option').each(function(el) {
						if (el.selected) {
							value=el.value;
							counter++;
						}
					});
					if (counter>1 || counter==0) {
						$('jform_request_id').setProperty('value','');
						$('jform_request_task').setProperty('value','');
						$('jform_params_singleCatOrdering').setProperty('disabled', 'disabled');
						enableParams();
					}
					if (counter==1) {
						$('jform_request_id').setProperty('value',value);
						$('jform_request_task').setProperty('value','category');
						$('jform_params_singleCatOrdering').removeProperty('disabled');
						disableParams();
					}
				}

				window.addEvent('domready', function() {
					if ($('request-options')) {
						$$('.panel')[0].setStyle('display', 'none');
					}
					setTask();
				});
				";
            }

            $doc->addScriptDeclaration($js);
        }

        foreach ($list as $item) {
            $item->treename = JString::str_ireplace('&#160;', '- ', $item->treename);
            @$mitems[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $item->id, $item->treename);
        }

        $fieldName = K2_JVERSION != '15' ? $name.'[]' : $control_name.'['.$name.'][]';

        $onChange = $name == 'categories' || $name == 'jform[params][categories]' ? 'onchange="setTask();"' : '';

        return Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $mitems, $fieldName, $onChange.' class="inputbox" multiple="multiple" size="15"', 'value', 'text', $value);
    }
}

class JFormFieldK2Category extends K2ElementK2Category
{
    public $type = 'k2category';
}

class JElementK2Category extends K2ElementK2Category
{
    public $_name = 'k2category';
}
