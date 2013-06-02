<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.beez3
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JLoader::import('joomla.filesystem.file');

/*
// Check modules
//$showbottom			= ($this->countModules('position-9') or $this->countModules('position-10') or $this->countModules('position-11'));
//$showleft			= ($this->countModules('position-4') or $this->countModules('position-7') or $this->countModules('position-5'));

if ($showRightColumn == 0 and $showleft == 0)
{
	$showno = 0;
}
*/
JHtml::_('behavior.framework', true);

// Get params
$color				= $this->params->get('templatecolor');
$logo				= $this->params->get('logo');
$navposition		= $this->params->get('navposition');
$headerImage		= $this->params->get('headerImage');
$app				= JFactory::getApplication();
$doc				= JFactory::getDocument();
$templateparams		= $app->getTemplate(true)->params;
$config = JFactory::getConfig();

$bootstrap = explode(',', $templateparams->get('bootstrap'));
$jinput = JFactory::getApplication()->input;
$option = $jinput->get('option', '', 'cmd');

$doc->addStyleSheet(JURI::base() . 'templates/' . $this->template . '/css/bootstrap-responsive.min.css', $type = 'text/css', $media = 'print');
$doc->addStyleSheet(JURI::base() . 'templates/' . $this->template . '/css/bootstrap.min.css', $type = 'text/css', $media = 'print');
$doc->addStyleSheet(JURI::base() . 'templates/' . $this->template . '/css/style.css', $type = 'text/css', $media = 'print');
$doc->addScript($this->baseurl . '/templates/' . $this->template . '/javascript/md_stylechanger.js', 'text/javascript');
$doc->addScript($this->baseurl . '/templates/' . $this->template . '/javascript/hide.js', 'text/javascript');
$doc->addScript($this->baseurl . '/templates/' . $this->template . '/javascript/respond.src.js', 'text/javascript');
$doc->addScript($this->baseurl . '/templates/' . $this->template . '/javascript/jquery-1-10.min.js', 'text/javascript');
$doc->addScript($this->baseurl . '/templates/' . $this->template . '/javascript/bootstrap.min.js', 'text/javascript');

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" >
	<head>
		<?php require __DIR__ . '/jsstrings.php';?>

		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=3.0, user-scalable=yes"/>
		<meta name="HandheldFriendly" content="true" />
		<meta name="apple-mobile-web-app-capable" content="YES" />

		<jdoc:include type="head" />

		<!--[if IE 7]>
		<link href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template; ?>/css/ie7only.css" rel="stylesheet" type="text/css" />
		<![endif]-->
	</head>
	<body>
		<div id="body" class="container">
			<header id="header">
					<div class="logoheader">
						<h1 id="logo">
						<?php if ($logo) : ?>
							<img src="<?php echo $this->baseurl ?>/<?php echo htmlspecialchars($logo); ?>"  alt="<?php echo htmlspecialchars($templateparams->get('sitetitle'));?>" />
						<?php endif;?>
						<?php if (!$logo AND $templateparams->get('sitetitle')) : ?>
							<?php echo htmlspecialchars($templateparams->get('sitetitle'));?>
						<?php elseif (!$logo AND $config->get('sitename')) : ?>
							<?php echo htmlspecialchars($config->get('sitename'));?>
						<?php endif; ?>
						<span class="header1">
						<?php echo htmlspecialchars($templateparams->get('sitedescription'));?>
						</span></h1>
					</div><!-- end logoheader -->
				<div class="menu-principal">
					<jdoc:include type="modules" name="menu-principal" />
				</div>
				<div id="line">
					<div id="fontsize"></div>
				</div> <!-- end line -->
			</header><!-- end header -->
			<div class="principal">
				<?php if ($this->countModules('slideshow')){ ?>
				<div class="slideshow-wrapper">
					<div class="slideshow">
						<jdoc:include type="modules" name="slideshow" />
						<?php if($this->countModules('menu-habitaciones')){ ?>
						<div class="habitacion">
							<jdoc:include type="component"/>
						</div>
						<?php } ?>
					</div>
					<?php if ($this->countModules('banner-premios') ||
							  $this->countModules('menu-habitaciones')){ ?>
					<div class="banner">
						<jdoc:include type="modules" name="banner-premios" />
						<jdoc:include type="modules" name="menu-habitaciones" />
					</div>
					<?php } ?>
				</div>
				<?php } ?>
				<div class="contenido">
					<?php if($this->countModules('info')){ ?>
					<div class="info">

					</div>
					<?php } ?>
				</div>
			</div>	
			<footer>

			</footer>
		</div>
		<jdoc:include type="modules" name="debug" />
	</body>
</html>
