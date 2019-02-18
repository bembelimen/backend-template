<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.Atum
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use OzdemirBurak\Iris\Color\Hex;
use Joomla\CMS\Filesystem\Path;

/** @var JDocumentHtml $this */

$app  = Factory::getApplication();
$lang = Factory::getLanguage();

// Add JavaScript Frameworks
HTMLHelper::_('script', 'vendor/focus-visible/focus-visible.min.js', ['version' => 'auto', 'relative' => true]);
HTMLHelper::_('script', 'vendor/css-vars-ponyfill/css-vars-ponyfill.min.js', ['version' => 'auto', 'relative' => true]);

// Load template JS file
HTMLHelper::_('script', 'media/templates/' . $this->template . '/js/template.min.js', ['version' => 'auto']);

// Load template CSS file
HTMLHelper::_('stylesheet', 'bootstrap.css', ['version' => 'auto', 'relative' => true]);
HTMLHelper::_('stylesheet', 'font-awesome.css', ['version' => 'auto', 'relative' => true]);
HTMLHelper::_('stylesheet', 'template' . ($this->direction === 'rtl' ? '-rtl' : '') . '.css', ['version' => 'auto', 'relative' => true]);

// Load custom CSS file
HTMLHelper::_('stylesheet', 'user.css', array('version' => 'auto', 'relative' => true));

// Load specific language related CSS
HTMLHelper::_('stylesheet', 'administrator/language/' . $lang->getTag() . '/' . $lang->getTag() . '.css', array('version' => 'auto'));

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', 'default');
$sitename = $app->get('sitename');

// Template params
$siteLogo  = $this->params->get('siteLogo');
$loginLogo = $this->params->get('loginLogo', $this->baseurl . '/templates/' . $this->template . '/images/logo-blue.svg');

// Set some meta data
$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
// @TODO sync with _variables.scss
$this->setMetaData('theme-color', '#1c3d5c');

// Set page title
$this->setTitle(Text::sprintf('TPL_ATUM_LOGIN_SITE_TITLE', $sitename));

$this->addScriptDeclaration('cssVars();');

// Opacity must be set before displaying the DOM, so don't move to a CSS file
$css = '
	.container-main > * {
		opacity: 0;
	}
	.sidebar-wrapper > * {
		opacity: 0;
	}
';

$root = [];

$steps = 10;

if ($this->params->get('bg-dark'))
{
	$root[] = '--atum-bg-dark: ' . $this->params->get('bg-dark') . ';';
}

if ($this->params->get('bg-light'))
{
	$root[] = '--atum-bg-light: ' . $this->params->get('bg-light') . ';';
}

if ($this->params->get('text-dark'))
{
	$root[] = '--atum-text-dark: ' . $this->params->get('text-dark') . ';';
}

if ($this->params->get('text-light'))
{
	$root[] = '--atum-text-light: ' . $this->params->get('text-light') . ';';
}

if ($this->params->get('link-color'))
{
	$linkcolor = trim($this->params->get('link-color'), '#');

	$root[] = '--atum-link-color: #' . $linkcolor . ';';

	try {
		$color = new Hex($linkcolor);
		$color->darken(40);

		$root[] = '--atum-link-hover-color: ' . $color . ';';
	} catch (Exception $ex) {

	}
}

if ($this->params->get('special-color'))
{
	$root[] = '--atum-special-color: ' . $this->params->get('special-color') . ';';
}

if (count($root))
{
	$css .= ':root {' . implode($root) . '}';
}

$this->addStyleDeclaration($css);

?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="metas"/>
	<jdoc:include type="styles"/>
</head>
<body class="site <?php echo $option . ' view-' . $view . ' layout-' . $layout; ?>">
<header id="header" class="header">
	<div class="d-flex align-items-center">
		<div class="header-title mr-auto">
			<a class="logo" href="<?php echo Route::_('index.php'); ?>"
			   aria-label="<?php echo Text::_('TPL_BACK_TO_CONTROL_PANEL'); ?>">
				<?php if (is_file(Path::check(JPATH_BASE . '/' . $siteLogo))) : ?>
				<img id="site-logo" src="<?php echo $siteLogo; ?>" alt="">
				<?php else : ?>
				<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
						viewBox="0 0 376.3 74.8" enable-background="new 0 0 376.3 74.8" xml:space="preserve" id="site-logo">
				   <g id="logo">
					   <path fill="#28466a" d="M116.4,14.8v31.3c0,2.8,0.2,5.4-2.3,7.3c-2.3,1.9-6.2,2.5-10.4,2.5c-6.4,0-13.2-1.5-13.2-1.5l-1.5,4
						   c0,0,9.5,2,16.4,2.1c5.8,0.1,10.9-1.2,13.7-4.4c2.3-2.6,3-5.6,2.9-10.7V14.8H116.4"/>
					   <path fill="#28466a" d="M163.1,32.1c-4.2-2.3-9.3-3.5-15.1-3.5c-5.7,0-10.8,1.2-15.1,3.5h0c-5.4,2.9-8.1,7.2-8.1,12.6
						   c0,5.4,2.7,9.7,8.1,12.6c4.3,2.3,9.3,3.5,15.1,3.5c5.7,0,10.8-1.2,15-3.5c5.4-2.9,8.1-7.2,8.1-12.6C171.2,39.2,168.4,35,163.1,32.1
							M159.8,54.1c-3.3,1.9-7.2,2.8-11.8,2.8c-4.7,0-8.7-0.9-11.9-2.7h0c-3.9-2.2-5.8-5.3-5.8-9.5c0-4.1,2-7.3,5.8-9.5
						   c3.2-1.8,7.2-2.7,11.9-2.7c4.6,0,8.6,0.9,11.9,2.7c3.8,2.2,5.8,5.4,5.8,9.5C165.7,48.7,163.7,51.9,159.8,54.1z"/>
					   <path fill="#28466a" d="M212.3,32.1c-4.2-2.3-9.3-3.5-15.1-3.5c-5.7,0-10.8,1.2-15.1,3.5h0c-5.4,2.9-8.1,7.2-8.1,12.6
						   c0,5.4,2.7,9.7,8.1,12.6c4.3,2.3,9.3,3.5,15.1,3.5c5.7,0,10.8-1.2,15-3.5c5.4-2.9,8.1-7.2,8.1-12.6C220.4,39.2,217.7,35,212.3,32.1
							M209.1,54.1c-3.3,1.9-7.2,2.8-11.8,2.8c-4.7,0-8.7-0.9-11.9-2.7c-3.9-2.2-5.8-5.3-5.8-9.5c0-4.1,2-7.3,5.8-9.5
						   c3.2-1.8,7.2-2.7,11.9-2.7c4.6,0,8.6,0.9,11.9,2.7c3.8,2.2,5.8,5.4,5.8,9.5C214.9,48.7,212.9,51.9,209.1,54.1z"/>
					   <path fill="#28466a" d="M280.2,31.3c-3-1.8-6.9-2.7-11.5-2.7c-5.9,0-10.6,1.8-14.2,5.4c-3.4-3.6-8.2-5.4-14.1-5.4
						   c-4.8,0-8.7,1-11.7,2.9c0-0.7,0-2.5,0-2.5h-5.3v31.1h5.3V38.9c0.4-1.5,1.4-2.9,3-4.1c2.2-1.5,5-2.3,8.5-2.3c3.1,0,5.7,0.6,7.9,1.9
						   c2.6,1.5,3.8,3.5,3.8,6.3v19.6h5.2V40.6c0-2.8,1.2-4.8,3.8-6.3c2.2-1.2,4.9-1.9,8-1.9c3.1,0,5.8,0.6,8,1.9c2.6,1.5,3.8,3.5,3.8,6.3
						   v19.6h5.3V41.3C285.9,36.9,284,33.5,280.2,31.3"/>
					   <polyline fill="#28466a" points="290.2,14.8 290.2,60.2 295.5,60.2 295.5,14.8 290.2,14.8 	"/>
					   <polyline fill="#28466a" points="354.5,14.8 354.5,49.9 359.8,49.9 359.8,14.8 354.5,14.8 	"/>
					   <path fill="#28466a" d="M340.7,29c0,0,0,4.3,0,5.3c-4.5-3.8-10.5-5.8-17.9-5.8c-5.9,0-11,1.1-15.2,3.4c-5.2,2.9-7.9,7.1-7.9,12.7
						   c0,5.5,2.7,9.8,8.1,12.6c4.2,2.3,9.3,3.4,15.2,3.4c2.9,0,5.8-0.3,8.4-1c3.7-1,6.8-2.4,9.2-4.3c0,1.1,0,4.8,0,4.8h5.3V29H340.7
							M305.2,44.7c0-4.1,2-7.3,5.8-9.5c3.2-1.8,7.3-2.7,12-2.7c5.8,0,10.3,1.4,13.5,4.2c2.8,2.5,4.2,5.7,4.2,9.6c0,0,0,3.4,0,3.7
						   c-2.2,2.5-5.5,4.4-9.7,5.7c-2.5,0.8-5.2,1.2-8,1.2c-4.8,0-8.8-0.9-12-2.6C307.1,52,305.2,48.9,305.2,44.7z"/>
					   <path fill="#28466a" d="M357.2,54.3c-3.7,0-4.2,1.9-4.2,3.1c0,1.2,0.6,3.1,4.2,3.1c3.7,0,4.2-2,4.2-3.1
						   C361.4,56.3,360.9,54.3,357.2,54.3z"/>
					   <path fill="#28466a" d="M376.3,20.4c0,3.1-2,5.7-5.6,5.7c-3.6,0-5.6-2.6-5.6-5.7c0-3.1,2-5.7,5.6-5.7
						   C374.3,14.7,376.3,17.3,376.3,20.4z M366.3,20.4c0,2.6,1.6,4.6,4.4,4.6c2.8,0,4.4-2,4.4-4.6c0-2.6-1.6-4.6-4.4-4.6
						   C367.9,15.8,366.3,17.8,366.3,20.4L366.3,20.4z M371.8,21.1c2.2-0.4,2-3.7-0.5-3.7h-2.7v5.8h1.1v-2h1l1.6,2h1.2V23L371.8,21.1
						   L371.8,21.1z M371.2,18.4c1.3,0,1.3,1.9,0,1.9h-1.6v-1.9H371.2z"/>
				   </g>
				   <g id="brandmark">
					   <path id="j-green" fill="#28466a" d="M13.5,37.7L12,36.3c-4.5-4.5-5.8-10.8-4.2-16.5c-4.5-1-7.8-5-7.8-9.8c0-5.5,4.5-10,10-10
						   c5,0,9.1,3.6,9.9,8.4c5.4-1.3,11.3,0.2,15.5,4.4l0.6,0.6l-7.4,7.4l-0.6-0.6c-2.4-2.4-6.3-2.4-8.7,0c-2.4,2.4-2.4,6.3,0,8.7l1.4,1.4
						   l7.4,7.4l7.8,7.8l-7.4,7.4l-7.8-7.8L13.5,37.7L13.5,37.7z"/>
					   <path id="j-orange" fill="#28466a" d="M21.8,29.5l7.8-7.8l7.4-7.4l1.4-1.4C42.9,8.4,49.2,7,54.8,8.6C55.5,3.8,59.7,0,64.8,0
						   c5.5,0,10,4.5,10,10c0,5.1-3.8,9.3-8.7,9.9c1.6,5.6,0.2,11.9-4.2,16.3l-0.6,0.6l-7.4-7.4l0.6-0.6c2.4-2.4,2.4-6.3,0-8.7
						   c-2.4-2.4-6.3-2.4-8.7,0l-1.4,1.4L37,29l-7.8,7.8L21.8,29.5L21.8,29.5z"/>
					   <path id="j-red" fill="#28466a" d="M55,66.8c-5.7,1.7-12.1,0.4-16.6-4.1l-0.6-0.6l7.4-7.4l0.6,0.6c2.4,2.4,6.3,2.4,8.7,0
						   c2.4-2.4,2.4-6.3,0-8.7L53,45.1l-7.4-7.4l-7.8-7.8l7.4-7.4l7.8,7.8l7.4,7.4l1.5,1.5c4.2,4.2,5.7,10.2,4.4,15.7
						   c4.9,0.7,8.6,4.9,8.6,9.9c0,5.5-4.5,10-10,10C60,74.8,56,71.3,55,66.8L55,66.8z"/>
					   <path id="j-blue" fill="#28466a" d="M52.2,46l-7.8,7.8L37,61.2l-1.4,1.4c-4.3,4.3-10.3,5.7-15.7,4.4c-1,4.5-5,7.8-9.8,7.8
						   c-5.5,0-10-4.5-10-10C0,60,3.3,56.1,7.7,55C6.3,49.5,7.8,43.5,12,39.2l0.6-0.6L20,46l-0.6,0.6c-2.4,2.4-2.4,6.3,0,8.7
						   c2.4,2.4,6.3,2.4,8.7,0l1.4-1.4l7.4-7.4l7.8-7.8L52.2,46L52.2,46z"/>
				   </g>
				</svg>
				<?php endif; ?>
			</a>
		</div>
	</div>
</header>

<div id="wrapper" class="d-flex wrapper">

	<?php // Sidebar ?>
	<div id="sidebar-wrapper" class="sidebar-wrapper">
		<div id="main-brand" class="main-brand">
			<h2><?php echo $sitename; ?></h2>
			<a href="<?php echo Uri::root(); ?>"><?php echo Text::_('TPL_ATUM_LOGIN_SIDEBAR_VIEW_WEBSITE'); ?></a>
		</div>
		<div id="sidebar">
			<jdoc:include type="modules" name="sidebar" style="body"/>
		</div>
	</div>

	<div class="container-fluid container-main">
		<section id="content" class="content h-100">
			<main class="d-flex justify-content-center align-items-center h-100">
				<div class="login">
					<div class="main-brand d-flex align-items-center justify-content-center">
						<img src="<?php echo $loginLogo; ?>" alt="">
					</div>
					<h1><?php echo Text::_('TPL_ATUM_LOGIN_HEADING'); ?></h1>
					<jdoc:include type="message"/>
					<jdoc:include type="component"/>
				</div>
			</main>
		</section>
	</div>
</div>
<jdoc:include type="modules" name="debug" style="none"/>
<jdoc:include type="scripts"/>
</body>
</html>
