<?php
/* SVN FILE: $Id$ */
/** 
* 
* @author $Author$
* @copyright Copyright (c) 2009-2010 Adam Florizone. All rights reserved. Copyright (c) 2009-2010 Digihaven Technology and Design Canada. All rights reserved. 
* @version Revision:186
* @lastrevision Date:Mon, 09 Apr 2012 20:33:59 GMT
* @modifiedby $LastChangedBy$
* @lastmodified $LastChangedDate$
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
* @filesource $URL$
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

ob_start();

if(version_compare(JVERSION,'1.7.0','ge') || version_compare(JVERSION,'1.6.0','ge')) 
{
	if (JRequest::getVar("layout","")=="edit")
	{
		print "Cannot display mod_article while editing."; // due to class redefine errors....
		
		return;
	}
		
	// Joomla! 1.7 code here
	$pre="content";
	$sub="article";
	$option="com_".$pre;
	
	// Define component path.
	$JPATH_COMPONENT=JPATH_BASE . '/components/' . $option;
	$JPATH_COMPONENT_SITE=JPATH_SITE . '/components/' . $option;
	$JPATH_COMPONENT_ADMINISTRATOR=JPATH_ADMINISTRATOR . '/components/' . $option;
	
	$file	= substr($option, 4);
	$path = $JPATH_COMPONENT . '/' . $file.'.php';
	
	// If component is disabled throw error
	if (!JComponentHelper::isEnabled($option) || !file_exists($path)) {
		print "error $path";
	}
	else
	{
		$lang = JFactory::getLanguage();
		
		// Load common and local language files.
			$lang->load($option, JPATH_BASE, null, false, false)
		||	$lang->load($option, $JPATH_COMPONENT, null, false, false)
		||	$lang->load($option, JPATH_BASE, $lang->getDefault(), false, false)
		||	$lang->load($option, $JPATH_COMPONENT, $lang->getDefault(), false, false);

		require_once $JPATH_COMPONENT.'/helpers/route.php';
		require_once $JPATH_COMPONENT.'/helpers/query.php';
		JHTML::addIncludePath($JPATH_COMPONENT.DS.'helpers'); // NEed or else we get the "JHtml: :icon not supported. File not found" error
		
		// Get the model
		require_once $JPATH_COMPONENT.DS.'models'.DS.$sub.'.php';
		$foo=ucfirst($pre)."Model".ucfirst($sub);
		$model= new $foo;
		
		// Model settings
		$original_id = JRequest::getInt('id');
		JRequest::setVar("id",$params->get('id', 0));
		//$model->setState('params', $params);
		
		// Now the view
		require_once $JPATH_COMPONENT.'/views/'.$sub.'/view.html.php';
		$foo=ucfirst($pre)."View".ucfirst($sub);
		$view = new $foo; 	
		
		$pathway = $app->getPathway();
		$oldPath=$pathway->getPathway();
		
		// View settings
		$view->document=new JDocument(); // We dont want to mess with the real document
		
		$mainframe =& JFactory::getApplication();
		
		if(JFile::exists(JPATH_BASE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'mod_article'.DS.$sub.DS.'default.php')) 
			$view->addTemplatePath(JPATH_BASE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'mod_article'.DS.$sub);
		if($params->get('allowTemplateOveride', false) && JFile::exists(JPATH_BASE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.$option.DS.$sub.DS.'default.php')) 
			$view->addTemplatePath(JPATH_BASE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.$option.DS.$sub);
		else 
			$view->addTemplatePath($JPATH_COMPONENT.DS.'views'.DS.'article'.DS.'tmpl');
			
		// End template choice
	
		$view->setModel($model,"true");
		
		$state=$view->get('State');
			$temp=clone $state->get('params');
				$temp->merge($params);
			$state->set('params',$temp);
		$view->set('State',$state);
		
		// Send the display
		print $view->display();
		
		JRequest::setVar("id",$original_id);
		
		$pathway->setPathway($oldPath);
	}
} 
else 
{
	JHTML::addIncludePath(JPATH_BASE.DS.'components'.DS.'com_content'.DS.'helpers');
	require_once(JPATH_BASE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'query.php');
	require_once(JPATH_BASE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php');
	
	require_once (JPATH_BASE.DS.'components'.DS.'com_content'.DS.'models'.DS.'article.php');
	
	
	jimport('joomla.application.component.helper');
	
	
	require_once ('view.html.php');
	
	$id 	= (int) $params->get('id', 0);
	$articleModel= new ContentModelArticle;
	$articleModel->setId($id);
	
	$articleView = new ContentViewArticle2;
	
	$mainframe =& JFactory::getApplication();
	jimport( 'joomla.filesystem.file' );
	// Chose the template to use. Thanks to Jasper Jaklofsky of djeedjee.net for the code!
	if(JFile::exists(JPATH_BASE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'mod_article'.DS.'article'.DS.'default.php')) 
		$articleView->addTemplatePath(JPATH_BASE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'mod_article'.DS.'article');
	else if($params->get('allowTemplateOveride', false) && JFile::exists(JPATH_BASE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_content'.DS.'article'.DS.'default.php')) 
		$articleView->addTemplatePath(JPATH_BASE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'com_content'.DS.'article');
	else 
		$articleView->addTemplatePath(JPATH_BASE.DS.'components'.DS.'com_content'.DS.'views'.DS.'article'.DS.'tmpl');

	// End template choice
	
	$articleView->setModel($articleModel,"true");
	
	$articleView->set('mod_article_prams',$params);
	
	// Send the display
	$articleView->display();
}

$out = ob_get_contents();

ob_end_clean();

if (!function_exists("linkFixer"))
{
	function linkFixer($out,$contains2,$contains1,$replace)
	{
		if (($pos=strpos($out,$contains1))!==false)
		{
			$chunk1=substr($out,0,$pos);
			if (($pos2=strrpos($chunk1,"href=\""))!==false)
			{
				$chunk2=substr($chunk1,$pos2+strlen("href=\""));
				
				if (($pos3=strpos($chunk2,$contains2))!==false)
				{
					$chunk3=substr($chunk2,0, $pos3);
					
					$chunkNew=str_replace($chunk3, $replace, $chunk2);
					
					$out=str_replace($chunk3, $chunkNew, $out);
				}
			}
		}
		
		return $out;
	}
}

if ($params->get('pathOveride', "")!="")
{
	// SEO url:
	// 1.7 <a href="/component/content/?task=article.edit&amp;a_id=17&amp;return=aHR0cDovL3Byb3RvLmRpZ2loYXZlbi5jYTo5NjY2L2xvZ2luLmh0bWw="><img src="/media/system/images/edit.png" alt="Edit"></a>
	// 1.6 <a href="/index.php?view=article&amp;id=932423:alias&amp;task=edit&amp;ret=aHR0cDovL2pvb21sYTE1LnByb3RvLmludHJhLw=="><img alt="•edit•" src="/plugins/system/contentoptimizer/51b97d710f81a7151ec514672fd7f97ca22d0d99_18x18.png"></a>

	// 1.6
	$out=linkFixer($out,"?","&amp;task=edit&amp;ret=",$params->get('pathOveride', ""));
	
	// 1.7
	$out=linkFixer($out,"?","task=article.edit&amp;a_id=",$params->get('pathOveride', ""));
}

print $out;