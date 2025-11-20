<?php
/**
 * @version    2.x (rolling release)
 * @author     JoomlaWorks https://www.joomlaworks.net
 * @copyright  Copyright (c) 2009 - 2025 JoomlaWorks Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

// no direct access
defined('_JEXEC') || die;

$output = '';
if ($params->get('home')) {
    $output .= '<span class="bcTitle">'.Joomla\CMS\Language\Text::_('K2_YOU_ARE_HERE').'</span><a href="'.Joomla\CMS\Uri\Uri::root().'">'.$params->get('home', Joomla\CMS\Language\Text::_('K2_HOME')).'</a>';
    if (count($path) > 0) {
        foreach ($path as $link) {
            $output .= '<span class="bcSeparator">'.$params->get('seperator', '&raquo;').'</span>'.$link;
        }
    }
    if ($title) {
        $output .= '<span class="bcSeparator">'.$params->get('seperator', '&raquo;').'</span>'.$title;
    }
} else {
    if ($title) {
        $output .= '<span class="bcTitle">'.Joomla\CMS\Language\Text::_('K2_YOU_ARE_HERE').'</span>';
    }
    if (count($path) > 0) {
        foreach ($path as $link) {
            $output .= $link.'<span class="bcSeparator">'.$params->get('seperator', '&raquo;').'</span>';
        }
    }
    $output .= $title;
}

?>

<div id="k2ModuleBox<?php echo $module->id; ?>" class="k2BreadcrumbsBlock<?php if ($params->get('moduleclass_sfx')) {
    echo ' '.$params->get('moduleclass_sfx');
} ?>">
    <?php echo $output; ?>
</div>
