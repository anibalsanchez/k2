<?php
/**
 * @version    2.x (rolling release)
 * @author     JoomlaWorks https://www.joomlaworks.net
 * @copyright  Copyright (c) 2009 - 2025 JoomlaWorks Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

// no direct access
defined('_JEXEC') || die;

// Quick and dirty fix for Joomla 3.0 missing CSS tabs when creating tabs using the API.
// Should be removed when Joomla fixes that...
if (K2_JVERSION == '30') {
    $document = Joomla\CMS\Factory::getDocument();
    $document->addStyleDeclaration('
		dl.tabs {float:left;margin:10px 0 -1px 0;z-index:50;}
		dl.tabs dt {float:left;padding:4px 10px;border:1px solid #ccc;margin-left:3px;background:#e9e9e9;color:#666;}
		dl.tabs dt.open {background:#f9f9f9;border-bottom:1px solid #f9f9f9;z-index:100;color:#000;}
		div.current {clear:both;border:1px solid #ccc;padding:10px 10px;background:#f9f9f9;}
		dl.tabs h3 {font-size:12px;line-height:12px;margin:4px;}
	');
}

// Import Joomla tabs
jimport('joomla.html.pane');

?>

<?php if (K2_JVERSION != '30') {
    $pane = JPane::getInstance('Tabs');
} ?>

<div class="clr"></div>

<?php echo (K2_JVERSION == '30') ? Joomla\CMS\HTML\HTMLHelper::_('tabs.start') : $pane->startPane('myPane'); ?>

<?php if ($params->get('latestItems', 1)): ?>
<?php echo (K2_JVERSION == '30') ? Joomla\CMS\HTML\HTMLHelper::_('tabs.panel', Joomla\CMS\Language\Text::_('K2_LATEST_ITEMS'), 'latestItemsTab') : $pane->startPanel(Joomla\CMS\Language\Text::_('K2_LATEST_ITEMS'), 'latestItemsTab'); ?>
<table class="adminlist table table-striped">
	<thead>
		<tr>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_TITLE'); ?></td>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_CREATED'); ?></td>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_AUTHOR'); ?></td>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($latestItems as $latestItem): ?>
		<tr>
			<td><a href="<?php echo Joomla\CMS\Router\Route::_('index.php?option=com_k2&view=item&cid='.$latestItem->id); ?>"><?php echo $latestItem->title; ?></a></td>
			<td><?php echo Joomla\CMS\HTML\HTMLHelper::_('date', $latestItem->created, Joomla\CMS\Language\Text::_('K2_DATE_FORMAT')); ?></td>
			<td><?php echo $latestItem->author; ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php if (K2_JVERSION != '30') {
    echo $pane->endPanel();
} ?>
<?php endif; ?>

<?php if ($params->get('popularItems', 1)): ?>
<?php echo (K2_JVERSION == '30') ? Joomla\CMS\HTML\HTMLHelper::_('tabs.panel', Joomla\CMS\Language\Text::_('K2_POPULAR_ITEMS'), 'popularItemsTab') : $pane->startPanel(Joomla\CMS\Language\Text::_('K2_POPULAR_ITEMS'), 'popularItemsTab'); ?>
<table class="adminlist table table-striped">
	<thead>
		<tr>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_TITLE'); ?></td>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_HITS'); ?></td>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_CREATED'); ?></td>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_AUTHOR'); ?></td>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($popularItems as $popularItem): ?>
		<tr>
			<td><a href="<?php echo Joomla\CMS\Router\Route::_('index.php?option=com_k2&view=item&cid='.$popularItem->id); ?>"><?php echo $popularItem->title; ?></a></td>
			<td><?php echo $popularItem->hits; ?></td>
			<td><?php echo Joomla\CMS\HTML\HTMLHelper::_('date', $popularItem->created, Joomla\CMS\Language\Text::_('K2_DATE_FORMAT')); ?></td>
			<td><?php echo $popularItem->author; ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php if (K2_JVERSION != '30') {
    echo $pane->endPanel();
} ?>
<?php endif; ?>

<?php if ($params->get('mostCommentedItems', 1)): ?>
<?php echo (K2_JVERSION == '30') ? Joomla\CMS\HTML\HTMLHelper::_('tabs.panel', Joomla\CMS\Language\Text::_('K2_MOST_COMMENTED_ITEMS'), 'mostCommentedItemsTab') : $pane->startPanel(Joomla\CMS\Language\Text::_('K2_MOST_COMMENTED_ITEMS'), 'mostCommentedItemsTab'); ?>
<table class="adminlist table table-striped">
	<thead>
		<tr>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_TITLE'); ?></td>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_COMMENTS'); ?></td>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_CREATED'); ?></td>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_AUTHOR'); ?></td>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($mostCommentedItems as $mostCommentedItem): ?>
		<tr>
			<td><a href="<?php echo Joomla\CMS\Router\Route::_('index.php?option=com_k2&view=item&cid='.$mostCommentedItem->id); ?>"><?php echo $mostCommentedItem->title; ?></a></td>
			<td><?php echo $mostCommentedItem->numOfComments; ?></td>
			<td><?php echo Joomla\CMS\HTML\HTMLHelper::_('date', $mostCommentedItem->created, Joomla\CMS\Language\Text::_('K2_DATE_FORMAT')); ?></td>
			<td><?php echo $mostCommentedItem->author; ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php if (K2_JVERSION != '30') {
    echo $pane->endPanel();
} ?>
<?php endif; ?>

<?php if ($params->get('latestComments', 1)): ?>
<?php echo (K2_JVERSION == '30') ? Joomla\CMS\HTML\HTMLHelper::_('tabs.panel', Joomla\CMS\Language\Text::_('K2_LATEST_COMMENTS'), 'latestCommentsTab') : $pane->startPanel(Joomla\CMS\Language\Text::_('K2_LATEST_COMMENTS'), 'latestCommentsTab'); ?>
<table class="adminlist table table-striped">
	<thead>
		<tr>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_COMMENT'); ?></td>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_ADDED_ON'); ?></td>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_POSTED_BY'); ?></td>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($latestComments as $latestComment): ?>
		<tr>
			<td><?php echo $latestComment->commentText; ?></td>
			<td><?php echo Joomla\CMS\HTML\HTMLHelper::_('date', $latestComment->commentDate, Joomla\CMS\Language\Text::_('K2_DATE_FORMAT')); ?></td>
			<td><?php echo $latestComment->userName; ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php if (K2_JVERSION != '30') {
    echo $pane->endPanel();
} ?>
<?php endif; ?>

<?php if ($params->get('statistics', 1)): ?>
<?php echo (K2_JVERSION == '30') ? Joomla\CMS\HTML\HTMLHelper::_('tabs.panel', Joomla\CMS\Language\Text::_('K2_STATISTICS'), 'statsTab') : $pane->startPanel(Joomla\CMS\Language\Text::_('K2_STATISTICS'), 'statsTab'); ?>
<table class="adminlist table table-striped">
	<thead>
		<tr>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_TYPE'); ?></td>
			<td class="title"><?php echo Joomla\CMS\Language\Text::_('K2_COUNT'); ?></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo Joomla\CMS\Language\Text::_('K2_ITEMS'); ?></td>
			<td><?php echo $statistics->numOfItems; ?> (<?php echo $statistics->numOfDraftItems; ?> <?php echo Joomla\CMS\Language\Text::_('K2_DRAFTS'); ?> - <?php echo $statistics->numOfFeaturedItems; ?> <?php echo Joomla\CMS\Language\Text::_('K2_FEATURED'); ?> - <?php echo $statistics->numOfTrashedItems; ?> <?php echo Joomla\CMS\Language\Text::_('K2_TRASHED'); ?>)</td>
		</tr>
		<tr>
			<td><?php echo Joomla\CMS\Language\Text::_('K2_CATEGORIES'); ?></td>
			<td><?php echo $statistics->numOfCategories; ?> (<?php echo $statistics->numOfTrashedCategories.' '.Joomla\CMS\Language\Text::_('K2_TRASHED'); ?>)</td>
		</tr>
		<tr>
			<td><?php echo Joomla\CMS\Language\Text::_('K2_TAGS'); ?></td>
			<td><?php echo $statistics->numOfTags; ?></td>
		</tr>
		<tr>
			<td><?php echo Joomla\CMS\Language\Text::_('K2_COMMENTS'); ?></td>
			<td><?php echo $statistics->numOfComments; ?></td>
		</tr>
		<tr>
			<td><?php echo Joomla\CMS\Language\Text::_('K2_USERS'); ?></td>
			<td><?php echo $statistics->numOfUsers; ?></td>
		</tr>
		<tr>
			<td><?php echo Joomla\CMS\Language\Text::_('K2_USER_GROUPS'); ?></td>
			<td><?php echo $statistics->numOfUserGroups; ?></td>
		</tr>
	</tbody>
</table>
<?php if (K2_JVERSION != '30') {
    echo $pane->endPanel();
} ?>
<?php endif; ?>

<?php echo K2_JVERSION != '30' ? $pane->endPane() : Joomla\CMS\HTML\HTMLHelper::_('tabs.end'); ?>
