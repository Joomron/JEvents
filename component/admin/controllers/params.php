<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * This file based on Joomla config component Copyright (C) 2005 - 2008 Open Source Matters.
 *
 * @version     $Id: params.php 3548 2012-04-20 09:25:43Z geraintedwards $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd, 2006-2008 JEvents Project Group
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controlleradmin');

class AdminParamsController extends JControllerAdmin
{

	/**
	 * Custom Constructor
	 */
	function __construct($default = array())
	{
		$user = JFactory::getUser();

		if (!JEVHelper::isAdminUser())
		{
			JFactory::getApplication()->redirect("index.php?option=" . JEV_COM_COMPONENT . "&task=cpanel.cpanel", "Not Authorised - must be admin");
			return;
		}

		$default['default_task'] = 'edit';
		parent::__construct($default);

		$this->registerTask('apply', 'save');

	}

	/**
	 * Show the configuration edit form
	 * @param string The URL option
	 */
	function edit($key = NULL, $urlVar = NULL)
	{

		// get the view
		$this->view = $this->getView("params", "html");

                $model = $this->getModel('component');
		$table =  JTable::getInstance('extension');
		if (!$table->load(array("element" => "com_jevents", "type" => "component"))) // 1.6 mod
		{
			JError::raiseWarning(500, 'Not a valid component');
			return false;
		}
                // Sort out sites with more than one entry in the extensions table
                $db = JFactory::getDbo();
                $db->setQuery("SELECT * FROM #__extensions WHERE element='com_jevents' and type='component' ORDER BY extension_id ASC");
		$jevcomponents = $db->loadObjectList();
                if (count($jevcomponents)>1) {
		$duplicateExtensionWarning = JText::_('JEV_DUPLICATE_EXTENSION_WARNING');
		if ($duplicateExtensionWarning == 'JEV_DUPLICATE_EXTENSION_WARNING' ) {
			$duplicateExtensionWarning = 'We have duplicate entries in the extensions table.  These are being cleaned up.  <br/><br/><strong>Please check your configuration settings and save them</strong>';
		}
		JError::raiseWarning(106, $duplicateExtensionWarning);
                    $maxversion = "0.0.1";
                    $validExtensionId = 0;
                    foreach ($jevcomponents as $jevcomponent){
                        $manifest = new JRegistry($jevcomponent->manifest_cache);
                        $version = $manifest->get("version", "0.0.1");
                        if (version_compare($version, $maxversion, "gt")){
                            $maxversion = $version;
                            $validExtensionId = $jevcomponent->extension_id;
                        }
                    }
                    foreach ($jevcomponents as $jevcomponent){
                        $manifest = new JRegistry($jevcomponent->manifest_cache);
                        $version = $manifest->get("version", "0.0.1");
                        if (version_compare($version, $maxversion,"lt")){
                            // reset component id in any menu items and link to the old one
                            $db->setQuery("UPDATE #__menu set component_id=".$validExtensionId." WHERE component_id=".$jevcomponent->extension_id);
                            $db->query();
                            
                            // remove the older version
                            $db->setQuery("DELETE FROM #__extensions WHERE element='com_jevents' and type='component' and extension_id=".$jevcomponent->extension_id);
                            $db->query();

                        }
                    }
                }
                
		// Backwards compatatbility
		$table->id = $table->extension_id;
		$table->option = $table->element;

		// Set the layout
		$this->view->setLayout('edit');

		$this->view->assignRef('component', $table);
		$this->view->setModel($model, true);
		$this->view->display();

	}

	/**
	 * Save the configuration
	 */
	function save($key = NULL, $urlVar = NULL)
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');
		//echo $this->getTask();
		//exit;
		$component = JEV_COM_COMPONENT;

		$model = $this->getModel('params');
		$table =  JTable::getInstance('extension');
		//if (!$table->loadByOption( $component ))
		if (!$table->load(array("element" => "com_jevents", "type" => "component"))) // 1.6 mod
		{
			JError::raiseWarning(500, 'Not a valid component');
			return false;
		}

		$post = JRequest::get('post');
		$post['params'] = JRequest::getVar('jform', array(), 'post', 'array');
		$post['option'] = $component;
		$table->bind($post);

		// pre-save checks
		if (!$table->check())
		{
			JError::raiseWarning(500, $table->getError());
			return false;
		}

		// if switching from single cat to multi cat then reset the table entries
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		if (!$params->get("multicategory", 0) && isset($post["params"]['multicategory']) && $post["params"]['multicategory'] == 1)
		{
			$db = JFactory::getDbo();
			$sql = "DELETE FROM #__jevents_catmap";
			$db->setQuery($sql);
			$db->query();			
			
			$sql = "REPLACE INTO #__jevents_catmap (evid, catid) SELECT ev_id, catid from #__jevents_vevent WHERE catid in (SELECT id from #__categories where extension='com_jevents')";
			$db->setQuery($sql);
			$db->query();
		}

		// save the changes
		if (!$table->store())
		{
			JError::raiseWarning(500, $table->getError());
			return false;
		}

		// Now save the form permissions data
		$data = JRequest::getVar('jform', array(), 'post', 'array');
		$option = JEV_COM_COMPONENT;
		$comp = JComponentHelper::getComponent(JEV_COM_COMPONENT);
		$id = $comp->id;
		// Validate the posted data.
		JForm::addFormPath(JPATH_COMPONENT);

		$form = $model->getForm();
		$return = $model->validate($form, $data);

		// Check for validation errors.
		if ($return === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();
			$app = JFactory::getApplication();
			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if (JError::isError($errors[$i]))
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'notice');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'notice');
				}
			}

			// Save the data in the session.
			$app->setUserState('com_config.config.global.data', $data);
			// Redirect back to the edit screen.
			$this->setRedirect(JRoute::_('index.php?option=' . JEV_COM_COMPONENT . '&task=params.edit', false));
			return false;
		}

		// Attempt to save the configuration.
		$data = array(
			'params' => $return,
			'id' => $id,
			'option' => $option
		);
		$return = $model->saveRules($data);
		
//                $db = JFactory::getDbo();
//                $db->setQuery("Select * from #__extensions where element='com_jevents' and type='component'");
//                $jevcomp = $db->loadObjectList();
//               var_dump($jevcomp);exit();
                
		// Clear cache of com_config component.
		$this->cleanCache('_system', 1); // admin
		$this->cleanCache('_system', 0); // site

		// If caching is enabled then remove the component params from the cache!
		// Bug fixed in Joomla 3.2.1 ??
		$joomlaconfig = JFactory::getConfig();
		if ($joomlaconfig->get("caching",0)){
			$cacheController = JFactory::getCache('_system', 'callback');
			$cacheController->cache->remove("com_jevents");
		}
		
		//SAVE AND APPLY CODE FROM PRAKASH
		switch ($this->getTask()) {
			case 'apply':
				$this->setRedirect('index.php?option=' . JEV_COM_COMPONENT . '&task=params.edit', JText::_('CONFIG_SAVED'));
				break;
			default:
				$this->setRedirect('index.php?option=' . JEV_COM_COMPONENT . "&task=cpanel.cpanel", JText::_('CONFIG_SAVED'));
				break;
		}
		//$this->setRedirect( 'index.php?option='.JEV_COM_COMPONENT."&task=cpanel.cpanel", JText::_( 'CONFIG_SAVED' ) );
		//$this->setMessage(JText::_( 'CONFIG_SAVED' ));
		//$this->edit();

	}

	/**
	 * Cancel operation
	 */
	function cancel($key=NULL)
	{
		$this->setRedirect('index.php');

	}


	/**
	 * Clean the cache
	 *
	 * @param   string   $group      The cache group
	 * @param   integer  $client_id  The ID of the client
	 *
	 * @return  void
	 *
	 * @since   12.2
	 */
	protected function cleanCache($group = null, $client_id = 0)
	{
		$conf = JFactory::getConfig();
		$dispatcher = JDispatcher::getInstance();

		$options = array(
			'defaultgroup' => ($group) ? $group : (isset($this->option) ? $this->option : JFactory::getApplication()->input->get('option')),
			'cachebase' => ($client_id) ? JPATH_ADMINISTRATOR . '/cache' : $conf->get('cache_path', JPATH_SITE . '/cache'));

		$cache = JCache::getInstance('callback', $options);
		$cache->clean();

		// Trigger the onContentCleanCache event.
		$this->event_clean_cache = 'onContentCleanCache';
		$dispatcher->trigger($this->event_clean_cache, $options);
	}
	
}
