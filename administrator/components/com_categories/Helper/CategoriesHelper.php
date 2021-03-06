<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_categories
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Categories\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Table\Table;

/**
 * Categories helper.
 *
 * @since  1.6
 */
class CategoriesHelper
{
	/**
	 * Configure the Submenu links.
	 *
	 * @param   string  $extension  The extension being used for the categories.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public static function addSubmenu($extension)
	{
		// Avoid nonsense situation.
		if ($extension == 'com_categories')
		{
			return;
		}

		$parts = explode('.', $extension);
		$component = $parts[0];

		if (count($parts) > 1)
		{
			$section = $parts[1];
		}

		// Try to find the component helper.
		$eName = str_replace('com_', '', $component);
		$file = Path::clean(JPATH_ADMINISTRATOR . '/components/' . $component . '/helpers/' . $eName . '.php');

		if (file_exists($file))
		{
			$prefix = ucfirst(str_replace('com_', '', $component));
			$cName = $prefix . 'Helper';

			\JLoader::register($cName, $file);

			if (class_exists($cName))
			{
				if (is_callable(array($cName, 'addSubmenu')))
				{
					$lang = Factory::getLanguage();

					// Loading language file from the administrator/language directory then
					// loading language file from the administrator/components/*extension*/language directory
					$lang->load($component, JPATH_BASE, null, false, true)
					|| $lang->load($component, Path::clean(JPATH_ADMINISTRATOR . '/components/' . $component), null, false, true);

					call_user_func(array($cName, 'addSubmenu'), 'categories' . (isset($section) ? '.' . $section : ''));
				}
			}
		}
	}

	/**
	 * Gets a list of associations for a given item.
	 *
	 * @param   integer  $pk         Content item key.
	 * @param   string   $extension  Optional extension name.
	 *
	 * @return  array of associations.
	 */
	public static function getAssociations($pk, $extension = 'com_content')
	{
		$langAssociations = Associations::getAssociations($extension, '#__categories', 'com_categories.item', $pk, 'id', 'alias', '');
		$associations     = array();
		$user             = Factory::getUser();
		$groups           = implode(',', $user->getAuthorisedViewLevels());

		foreach ($langAssociations as $langAssociation)
		{
			// Include only published categories with user access
			$arrId    = explode(':', $langAssociation->id);
			$assocId  = $arrId[0];
			$db       = Factory::getDbo();

			$query = $db->getQuery(true)
				->select($db->quoteName('published'))
				->from($db->quoteName('#__categories'))
				->where('access IN (' . $groups . ')')
				->where($db->quoteName('id') . ' = ' . (int) $assocId);

			$result = (int) $db->setQuery($query)->loadResult();

			if ($result === 1)
			{
				$associations[$langAssociation->language] = $langAssociation->id;
			}
		}

		return $associations;
	}

	/**
	 * Check if Category ID exists otherwise assign to ROOT category.
	 *
	 * @param   mixed   $catid      Name or ID of category.
	 * @param   string  $extension  Extension that triggers this function
	 *
	 * @return  integer  $catid  Category ID.
	 */
	public static function validateCategoryId($catid, $extension)
	{
		$categoryTable = Table::getInstance('CategoryTable', '\\Joomla\\Component\\Categories\\Administrator\\Table\\');

		$data = array();
		$data['id'] = $catid;
		$data['extension'] = $extension;

		if (!$categoryTable->load($data))
		{
			$catid = 0;
		}

		return (int) $catid;
	}

	/**
	 * Create new Category from within item view.
	 *
	 * @param   array  $data  Array of data for new category.
	 *
	 * @return  integer
	 */
	public static function createCategory($data)
	{
		$categoryModel = Factory::getApplication()->bootComponent('com_categories')
			->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request' => true]);
		$categoryModel->save($data);

		$catid = $categoryModel->getState('category.id');

		return $catid;
	}
}
