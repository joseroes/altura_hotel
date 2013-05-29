<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_falang
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.language.helper');
jimport('joomla.utilities.utility');
jimport('joomla.html.parameter');

JLoader::register('MenusHelper', JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php');

abstract class modFaLangHelper
{
	public static function getList(&$params)
	{
		$lang = JFactory::getLanguage();
		$languages	= JLanguageHelper::getLanguages();
		$app		= JFactory::getApplication();

        //use to remove default language code in url
        $lang_codes 	= JLanguageHelper::getLanguages('lang_code');
        $default_lang = JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
        $default_sef 	= $lang_codes[$default_lang]->sef;


        $menu = $app->getMenu();
        $active = $menu->getActive();
        $uri = & JFactory::getURI();

        if (FALANG_J30) {
            $assoc = isset($app->item_associations) ? $app->item_associations : 0;
        } else {
            $assoc = $app->get('menu_associations', 0);
        }

		if ($assoc) {
			if ($active) {
				$associations = MenusHelper::getAssociations($active->id);
			}
		}
   		foreach($languages as $i => &$language) {
			// Do not display language without frontend UI
			if (!JLanguage::exists($language->lang_code)) {
				unset($languages[$i]);
			}
            if (FALANG_J30) {
                $language_filter = JLanguageMultilang::isEnabled();
            } else {
                $language_filter = $app->getLanguageFilter();
            }

            if ($language_filter) {
                $language->active =  $language->lang_code == $lang->getTag();
                if (isset($associations[$language->lang_code]) && $menu->getItem($associations[$language->lang_code])) {
                    $itemid = $associations[$language->lang_code];
                    if ($app->getCfg('sef')=='1') {
                        $language->link = JRoute::_('index.php?lang='.$language->sef.'&Itemid='.$itemid);
                    }
                    else {
                        $language->link = 'index.php?lang='.$language->sef.'&Itemid='.$itemid;
                    }
                }
                else {
                    //sef case
                    if ($app->getCfg('sef')=='1') {

                         //$uri->setVar('lang',$language->sef);
                         $router = JApplication::getRouter();
                         $tmpuri = clone($uri);

                         $router->parse($tmpuri);

                         $vars = $router->getVars();
                         //workaround to fix index language
                         $vars['lang'] = $language->sef;

                        //case of category article
                        if (!empty($vars['view']) && $vars['view'] == 'article' && !empty($vars['option']) && $vars['option'] == 'com_content') {

                            if (FALANG_J30){
                                JModelLegacy::addIncludePath(JPATH_SITE.'/components/com_content/models', 'ContentModel');
                                $model =& JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request'=>true));
                                $appParams = JFactory::getApplication()->getParams();
                            } else {
                                JModel::addIncludePath(JPATH_SITE.'/components/com_content/models', 'ContentModel');
                                $model =& JModel::getInstance('Article', 'ContentModel', array('ignore_request'=>true));
                                $appParams = JFactory::getApplication()->getParams();
                            }


                            $model->setState('params', $appParams);

                            //in sef some link have this url
                            //index.php/component/content/article?id=39
                            //id is not in vars but in $tmpuri
                            if (empty($vars['id'])) {
                                $tmpid = $tmpuri->getVar('id');
                                if (!empty($tmpid)) {
                                    $vars['id'] = $tmpuri->getVar('id');
                                } else {
                                    continue;
                                }
                            }

                            $item =& $model->getItem($vars['id']);

                            //get alias of content item without the id , so i don't have the translation
                            $db = JFactory::getDbo();
                            $query = $db->getQuery(true);
                            $query->select('alias')->from('#__content')->where('id = ' . (int) $item->id);
                            $db->setQuery($query);
                            $alias = $db->loadResult();

                            $vars['id'] = $item->id.':'.$alias;
                            $vars['catid'] =$item->catid.':'.$item->category_alias;
                        }

                        $url = 'index.php?'.JURI::buildQuery($vars);
                        $language->link = JRoute::_($url);
                    }
                    //default case
             else {
                        //we can't remove default language in the link
                        $uri->setVar('lang',$language->sef);
                        $language->link = 'index.php?'.$uri->getQuery();
                    }
                }
            }
            else {
                $language->link = 'index.php';
            }

		}
		return $languages;
	}

    public static function isFalangDriverActive() {
        $db = JFactory::getDBO();
        if (!is_a($db,"JFalangDatabase")){
           return false;
        }
           return true;
    }

}
