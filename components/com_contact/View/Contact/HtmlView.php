<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Contact\Site\View\Contact;

defined('_JEXEC') or die;

use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Contact\Site\Helper\Route as ContactHelperRoute;

/**
 * HTML Contact View class for the Contact component
 *
 * @since  1.5
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The item model state
	 *
	 * @var    \Joomla\Registry\Registry
	 * @since  1.6
	 */
	protected $state;

	/**
	 * The form object for the contact item
	 *
	 * @var    \JForm
	 * @since  1.6
	 */
	protected $form;

	/**
	 * The item object details
	 *
	 * @var    \JObject
	 * @since  1.6
	 */
	protected $item;

	/**
	 * The page to return to on submission
	 * TODO: Implement this functionality
	 *
	 * @var         string
	 * @since       1.6
	 */
	protected $return_page = '';

	/**
	 * Should we show a captcha form for the submission of the contact request?
	 *
	 * @var   bool
	 * @since 3.6.3
	 */
	protected $captchaEnabled = false;

	/**
	 * The page parameters
	 *
	 * @var    \Joomla\Registry\Registry|null
	 * @since  4.0.0
	 */
	protected $params = null;

	/**
	 * The user object
	 *
	 * @var   \JUser
	 * @since 4.0.0
	 */
	protected $user;

	/**
	 * Other contacts in this contacts category
	 *
	 * @var   array
	 * @since 4.0.0
	 */
	protected $contacts;

	/**
	 * The page class suffix
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $pageclass_sfx = '';

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		$app        = Factory::getApplication();
		$user       = Factory::getUser();
		$state      = $this->get('State');
		$item       = $this->get('Item');
		$this->form = $this->get('Form');
		$params     = $state->get('params');

		$temp = clone $params;

		$active = $app->getMenu()->getActive();

		// Get submitted values
		$data = $app->getUserState('com_contact.contact.data', array());

		// Add catid for selecting custom fields
		$data['catid'] = $item->catid;

		$app->setUserState('com_contact.contact.data', $data);

		if ($active)
		{
			// If the current view is the active item and a contact view for this contact, then the menu item params take priority
			if (strpos($active->link, 'view=contact') && strpos($active->link, '&id=' . (int) $item->id))
			{
				// $item->params are the contact params, $temp are the menu item params
				// Merge so that the menu item params take priority
				$item->params->merge($temp);
			}
			else
			{
				// Current view is not a single contact, so the contact params take priority here
				// Merge the menu item params with the contact params so that the contact params take priority
				$temp->merge($item->params);
				$item->params = $temp;
			}
		}
		else
		{
			// Merge so that contact params take priority
			$temp->merge($item->params);
			$item->params = $temp;
		}

		if ($item)
		{
			// Get Category Model data
			$categoryModel = new \Joomla\Component\Contact\Site\Model\CategoryModel(array('ignore_request' => true));

			$categoryModel->setState('category.id', $item->catid);
			$categoryModel->setState('list.ordering', 'a.name');
			$categoryModel->setState('list.direction', 'asc');
			$categoryModel->setState('filter.published', 1);

			$contacts = $categoryModel->getItems();
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \JViewGenericdataexception(implode("\n", $errors), 500);
		}

		// Check if access is not public
		$groups = $user->getAuthorisedViewLevels();

		if ((!in_array($item->access, $groups)) || (!in_array($item->category_access, $groups)))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);

			return false;
		}

		$options['category_id'] = $item->catid;
		$options['order by']    = 'a.default_con DESC, a.ordering ASC';

		/**
		 * Handle email cloaking
		 *
		 * Keep a copy of the raw email address so it can
		 * still be accessed in the layout if needed.
		 */
		$item->email_raw = $item->email_to;

		if ($item->email_to && $item->params->get('show_email'))
		{
			$item->email_to = HTMLHelper::_('email.cloak', $item->email_to, (bool) $item->params->get('add_mailto_link', true));
		}

		if ($item->params->get('show_street_address') || $item->params->get('show_suburb') || $item->params->get('show_state')
			|| $item->params->get('show_postcode') || $item->params->get('show_country'))
		{
			if (!empty($item->address) || !empty($item->suburb) || !empty($item->state) || !empty($item->country) || !empty($item->postcode))
			{
				$item->params->set('address_check', 1);
			}
		}
		else
		{
			$item->params->set('address_check', 0);
		}

		// Manage the display mode for contact detail groups
		switch ($item->params->get('contact_icons'))
		{
			case 1 :
				// Text
				$item->params->set('marker_address',   Text::_('COM_CONTACT_ADDRESS') . ': ');
				$item->params->set('marker_email',     Text::_('JGLOBAL_EMAIL') . ': ');
				$item->params->set('marker_telephone', Text::_('COM_CONTACT_TELEPHONE') . ': ');
				$item->params->set('marker_fax',       Text::_('COM_CONTACT_FAX') . ': ');
				$item->params->set('marker_mobile',    Text::_('COM_CONTACT_MOBILE') . ': ');
				$item->params->set('marker_misc',      Text::_('COM_CONTACT_OTHER_INFORMATION') . ': ');
				$item->params->set('marker_class',     'jicons-text');
				break;

			case 2 :
				// None
				$item->params->set('marker_address',   '');
				$item->params->set('marker_email',     '');
				$item->params->set('marker_telephone', '');
				$item->params->set('marker_mobile',    '');
				$item->params->set('marker_fax',       '');
				$item->params->set('marker_misc',      '');
				$item->params->set('marker_class',     'jicons-none');
				break;

			default :
				if ($item->params->get('icon_address'))
				{
					$image1 = HTMLHelper::_('image', $item->params->get('icon_address', 'con_address.png'), Text::_('COM_CONTACT_ADDRESS') . ': ', null, false);
				}
				else
				{
					$image1 = HTMLHelper::_(
						'image', 'contacts/' . $item->params->get('icon_address', 'con_address.png'),
						Text::_('COM_CONTACT_ADDRESS') . ': ',
						null,
						true
					);
				}

				if ($item->params->get('icon_email'))
				{
					$image2 = HTMLHelper::_('image', $item->params->get('icon_email', 'emailButton.png'), Text::_('JGLOBAL_EMAIL') . ': ', null, false);
				}
				else
				{
					$image2 = HTMLHelper::_('image', 'contacts/' . $item->params->get('icon_email', 'emailButton.png'), Text::_('JGLOBAL_EMAIL') . ': ', null, true);
				}

				if ($item->params->get('icon_telephone'))
				{
					$image3 = HTMLHelper::_('image', $item->params->get('icon_telephone', 'con_tel.png'), Text::_('COM_CONTACT_TELEPHONE') . ': ', null, false);
				}
				else
				{
					$image3 = HTMLHelper::_(
						'image',
						'contacts/' . $item->params->get('icon_telephone', 'con_tel.png'),
						Text::_('COM_CONTACT_TELEPHONE') . ': ',
						null,
						true
					);
				}

				if ($item->params->get('icon_fax'))
				{
					$image4 = HTMLHelper::_('image', $item->params->get('icon_fax', 'con_fax.png'), Text::_('COM_CONTACT_FAX') . ': ', null, false);
				}
				else
				{
					$image4 = HTMLHelper::_('image', 'contacts/' . $item->params->get('icon_fax', 'con_fax.png'), Text::_('COM_CONTACT_FAX') . ': ', null, true);
				}

				if ($item->params->get('icon_misc'))
				{
					$image5 = HTMLHelper::_('image', $item->params->get('icon_misc', 'con_info.png'), Text::_('COM_CONTACT_OTHER_INFORMATION') . ': ', null, false);
				}
				else
				{
					$image5 = HTMLHelper::_(
						'image',
						'contacts/' . $item->params->get('icon_misc', 'con_info.png'),
						Text::_('COM_CONTACT_OTHER_INFORMATION') . ': ', null, true
					);
				}

				if ($item->params->get('icon_mobile'))
				{
					$image6 = HTMLHelper::_('image', $item->params->get('icon_mobile', 'con_mobile.png'), Text::_('COM_CONTACT_MOBILE') . ': ', null, false);
				}
				else
				{
					$image6 = HTMLHelper::_(
						'image',
						'contacts/' . $item->params->get('icon_mobile', 'con_mobile.png'),
						Text::_('COM_CONTACT_MOBILE') . ': ',
						null,
						true
					);
				}

				$item->params->set('marker_address',   $image1);
				$item->params->set('marker_email',     $image2);
				$item->params->set('marker_telephone', $image3);
				$item->params->set('marker_fax',       $image4);
				$item->params->set('marker_misc',      $image5);
				$item->params->set('marker_mobile',    $image6);
				$item->params->set('marker_class',     'jicons-icons');
				break;
		}

		// Add links to contacts
		if ($item->params->get('show_contact_list') && count($contacts) > 1)
		{
			foreach ($contacts as &$contact)
			{
				$contact->link = Route::_(ContactHelperRoute::getContactRoute($contact->slug, $contact->catid, $contact->language));
			}

			$item->link = Route::_(ContactHelperRoute::getContactRoute($item->slug, $item->catid, $item->language), false);
		}

		// Process the content plugins.
		PluginHelper::importPlugin('content');
		$offset = $state->get('list.offset');

		// Fix for where some plugins require a text attribute
		$item->text = null;

		if (!empty($item->misc))
		{
			$item->text = $item->misc;
		}

		Factory::getApplication()->triggerEvent('onContentPrepare', array ('com_contact.contact', &$item, &$this->params, $offset));

		// Store the events for later
		$item->event = new \stdClass;
		$results = Factory::getApplication()->triggerEvent('onContentAfterTitle', array('com_contact.contact', &$item, &$item->params, $offset));
		$item->event->afterDisplayTitle = trim(implode("\n", $results));

		$results = Factory::getApplication()->triggerEvent('onContentBeforeDisplay', array('com_contact.contact', &$item, &$item->params, $offset));
		$item->event->beforeDisplayContent = trim(implode("\n", $results));

		$results = Factory::getApplication()->triggerEvent('onContentAfterDisplay', array('com_contact.contact', &$item, &$item->params, $offset));
		$item->event->afterDisplayContent = trim(implode("\n", $results));

		if (!empty($item->text))
		{
			$item->misc = $item->text;
		}

		$contactUser = null;

		if ($item->params->get('show_user_custom_fields') && $item->user_id && $contactUser = Factory::getUser($item->user_id))
		{
			$contactUser->text = '';
			Factory::getApplication()->triggerEvent('onContentPrepare', array ('com_users.user', &$contactUser, &$item->params, 0));

			if (!isset($contactUser->jcfields))
			{
				$contactUser->jcfields = array();
			}
		}

		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($item->params->get('pageclass_sfx'));

		$this->params      = &$item->params;
		$this->state       = &$state;
		$this->item        = &$item;
		$this->user        = &$user;
		$this->contacts    = &$contacts;
		$this->contactUser = $contactUser;

		$item->tags = new TagsHelper;
		$item->tags->getItemTags('com_contact.contact', $this->item->id);

		// Override the layout only if this is not the active menu item
		// If it is the active menu item, then the view and item id will match
		if ((!$active) || ((strpos($active->link, 'view=contact') === false) || (strpos($active->link, '&id=' . (string) $this->item->id) === false)))
		{
			if (($layout = $item->params->get('contact_layout')))
			{
				$this->setLayout($layout);
			}
		}
		elseif (isset($active->query['layout']))
		{
			// We need to set the layout in case this is an alternative menu item (with an alternative layout)
			$this->setLayout($active->query['layout']);
		}

		$model = $this->getModel();
		$model->hit();

		$captchaSet = $item->params->get('captcha', Factory::getApplication()->get('captcha', '0'));

		foreach (PluginHelper::getPlugin('captcha') as $plugin)
		{
			if ($captchaSet === $plugin->name)
			{
				$this->captchaEnabled = true;
				break;
			}
		}

		$this->_prepareDocument();

		return parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function _prepareDocument()
	{
		$app     = Factory::getApplication();
		$menus   = $app->getMenu();
		$pathway = $app->getPathway();
		$title   = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_CONTACT_DEFAULT_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

		$id = (int) @$menu->query['id'];

		// If the menu item does not concern this contact
		if ($menu && ($menu->query['option'] !== 'com_contact' || $menu->query['view'] !== 'contact' || $id != $this->item->id))
		{
			// If this is not a single contact menu item, set the page title to the contact title
			if ($this->item->name)
			{
				$title = $this->item->name;
			}

			$path = array(array('title' => $this->item->name, 'link' => ''));
			$category = Categories::getInstance('Contact')->get($this->item->catid);

			while ($category && ($menu->query['option'] !== 'com_contact' || $menu->query['view'] === 'contact' || $id != $category->id) && $category->id > 1)
			{
				$path[] = array('title' => $category->title, 'link' => ContactHelperRoute::getCategoryRoute($category->id, $category->language));
				$category = $category->getParent();
			}

			$path = array_reverse($path);

			foreach ($path as $item)
			{
				$pathway->addItem($item['title'], $item['link']);
			}
		}

		if (empty($title))
		{
			$title = $app->get('sitename');
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		if (empty($title))
		{
			$title = $this->item->name;
		}

		$this->document->setTitle($title);

		if ($this->item->metadesc)
		{
			$this->document->setDescription($this->item->metadesc);
		}
		elseif ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->item->metakey)
		{
			$this->document->setMetaData('keywords', $this->item->metakey);
		}
		elseif ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetaData('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetaData('robots', $this->params->get('robots'));
		}

		$mdata = $this->item->metadata->toArray();

		foreach ($mdata as $k => $v)
		{
			if ($v)
			{
				$this->document->setMetaData($k, $v);
			}
		}
	}
}
