<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

$user      = JFactory::getUser();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

JText::script('COM_FINDER_INDEX_CONFIRM_DELETE_PROMPT');
HTMLHelper::_('script', 'com_finder/filters.js', ['version' => 'auto', 'relative' => true]);
?>
<form action="<?php echo JRoute::_('index.php?option=com_finder&view=filters'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div id="j-sidebar-container" class="col-md-2">
			<?php echo $this->sidebar; ?>
		</div>
		<div class="col-md-10">
			<div id="j-main-container" class="j-main-container">
				<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-warning">
						<?php echo JText::_('COM_FINDER_NO_RESULTS_OR_FILTERS'); ?>
					</div>
				<?php else : ?>
				<table class="table">
					<caption id="captionTable" class="sr-only">
						<?php echo Text::_('COM_FINDER_FILTERS_TABLE_CAPTION'); ?>, <?php echo Text::_('JGLOBAL_SORTED_BY'); ?>
					</caption>
					<thead>
						<tr>
							<td style="width:1%" class="text-center">
								<?php echo JHtml::_('grid.checkall'); ?>
							</td>
							<th scope="col" style="width:1%">
								<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
							</th>
							<th scope="col">
								<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" style="width:10%" class="d-none d-md-table-cell">
								<?php echo JHtml::_('searchtools.sort', 'COM_FINDER_HEADING_CREATED_BY', 'a.created_by_alias', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" style="width:10%" class="d-none d-md-table-cell">
								<?php echo JHtml::_('searchtools.sort', 'COM_FINDER_HEADING_CREATED_ON', 'a.created', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" style="width:5%" class="d-none d-md-table-cell">
								<?php echo JHtml::_('searchtools.sort', 'COM_FINDER_HEADING_MAP_COUNT', 'a.map_count', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" style="width:1%" class="d-none d-md-table-cell">
								<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.filter_id', $listDirn, $listOrder); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$canCreate                  = $user->authorise('core.create',     'com_finder');
						$canEdit                    = $user->authorise('core.edit',       'com_finder');
						$userAuthoriseCoreManage    = $user->authorise('core.manage', 'com_checkin');
						$userAuthoriseCoreEditState = $user->authorise('core.edit.state', 'com_finder');
						$userId                     = $user->id;
						foreach ($this->items as $i => $item) :
							$canCheckIn   = $userAuthoriseCoreManage || $item->checked_out == $userId || $item->checked_out == 0;
							$canChange    = $userAuthoriseCoreEditState && $canCheckIn;
							$escapedTitle = $this->escape($item->title);
						?>
						<tr class="row<?php echo $i % 2; ?>">
							<td class="text-center">
								<?php echo JHtml::_('grid.id', $i, $item->filter_id); ?>
							</td>
							<td class="text-center">
								<?php echo JHtml::_('jgrid.published', $item->state, $i, 'filters.', $canChange); ?>
							</td>
							<th scope="row">
								<?php if ($item->checked_out) : ?>
									<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'filters.', $canCheckIn); ?>
								<?php endif; ?>
								<?php if ($canEdit) : ?>
									<a href="<?php echo JRoute::_('index.php?option=com_finder&task=filter.edit&filter_id=' . (int) $item->filter_id); ?>">
										<?php echo $escapedTitle; ?></a>
								<?php else : ?>
									<?php echo $escapedTitle; ?>
								<?php endif; ?>
							</th>
							<td class="d-none d-md-table-cell">
								<?php echo $item->created_by_alias ?: $item->user_name; ?>
							</td>
							<td class="d-none d-md-table-cell">
								<?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
							</td>
							<td class="d-none d-md-table-cell">
								<?php echo $item->map_count; ?>
							</td>
							<td class="d-none d-md-table-cell">
								<?php echo (int) $item->filter_id; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php // load the pagination. ?>
				<?php echo $this->pagination->getListFooter(); ?>

				<?php endif; ?>
				<input type="hidden" name="task" value="">
				<input type="hidden" name="boxchecked" value="0">
				<?php echo JHtml::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>
