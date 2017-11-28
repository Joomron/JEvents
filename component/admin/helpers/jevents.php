<?php

// No direct access
defined('_JEXEC') or die;
include_once(JPATH_ADMINISTRATOR . '/components/com_jevents/jevents.defines.php');

JLoader::register('JevJoomlaVersion', JPATH_ADMINISTRATOR . "/components/com_jevents/libraries/version.php");
//No need to globally define sidebar, and we need it here for com_categories.
JLoader::register('JevAdminJHtmlSidebar',JEV_ADMINPATH."helpers/jevsidebar.php");
//Include the defines, needed for com_categories

/**
 * JEvents component helper.
 *
 * @package        Jevents
 * @since          1.6
 */
class JEventsHelper
{

	public static $extention = 'com_jevents';

	/**
	 * Configure the Linkbar.
	 *
	 * @param    string    The name of the active view.
	 *
	 * @deprecated  3.5 & gone completely in 4.0  - Use addAdminSidebar
	 */
	public static function addSubmenu($vName = "")
	{
		$jinput = JFactory::getApplication()->input;

		$task   = $jinput->getCmd("task", "cpanel.cpanel");
		$option = $jinput->getCmd("option", "com_categories");

		if ($option == 'com_categories')
		{
			$doc = JFactory::getDocument();
			if (!JevJoomlaVersion::isCompatible("3.0"))
			{
				$hide_options = '#toolbar-popup-options {'
					. 'display:none;'
					. '}';
			}
			else
			{
				$hide_options = '#toolbar-options {'
					. 'display:none;'
					. '}';
			}
			$doc->addStyleDeclaration($hide_options);
			// Category styling
			$style = '#categoryList td.center a {border:none;}';

			JFactory::getDbo()->setQuery("SELECT * FROM #__categories WHERE extension='com_jevents'");
			$categories = JFactory::getDbo()->loadObjectList('id');
			foreach ($categories as $cat)
			{
				$catparams = new JRegistry($cat->params);
				if ($catparams->get("catcolour"))
				{
					$style .= "tr[item-id='$cat->id'] a {border-left:solid 3px " . $catparams->get("catcolour") . ";padding-left:5px;}\n";
				}
			}

			$doc->addStyleDeclaration($style);
		}

		if ($vName == "")
		{
			$vName = $task;
		}

		$com_params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		$difficulty = $com_params->get('com_difficulty', 0);

		// Links to addons
		// Lets do a single query and get an array of the addons! Wheyyy
		// Also make sure they are enabled!
		$jevaddons = array('com_jevlocations', 'com_rsvppro', 'jevcustomfields', 'com_jevpeople');
		// Links to addons
		$db = JFactory::getDbo();
		$db->setQuery("SELECT element FROM #__extensions WHERE element LIKE '%" . implode('%\' OR element LIKE \'%', $jevaddons) . "%' AND enabled = 1");
		$jevaddons_results = $db->loadColumn();

		// Custom Fields
		$db = JFactory::getDbo();
		$db->setQuery("SELECT * FROM #__extensions WHERE element = 'jevcustomfields' AND type='plugin' AND folder='jevents' ");
		$extension     = $db->loadObject();
		$manifestCache = json_decode($extension->manifest_cache);
		// Stop if user is not authorised to manage JEvents.
		$customfields = '';
		if ($manifestCache->version >= '3.5' && $extension && $extension->enabled && JEVHelper::isAdminUser())
		{
			$link = "index.php?option=com_jevents&task=plugin.jev_customfields.overview";
			JFactory::getLanguage()->load("plg_jevents_jevcustomfields", JPATH_ADMINISTRATOR);
			$customfields = '<li><a href="' . $link . '"><i class="fa fa-file-text-o"></i> <span>' . JText::_('JEV_CUSTOM_FIELDS') . '</span></a></li>';
		}

		$config_tabs = ($task === 'params.edit' ?  'onclick="storeTab(this);" data-toggle="tab" href="index.php?option=com_jevents&task=params.edit#' : "href=\"index.php?option=com_jevents&task=params.edit&default_tab=");

		//Could be called from categories component
		JLoader::register('JEVHelper', JPATH_SITE . '/components/com_jevents/libraries/helper.php');
		//Header items first for the foreach loop to work
		JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_EVENTS_MANAGEMENT'), '', false, 'jevents', 'header', 1, 0, 0, '');
		//First item
		JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_DASHBOARD'), 'index.php?option=com_jevents', $vName === 'cpanel.cpanel', 'jevents', 'fa-dashboard', 0, 0, 0);
		//Events Item
		JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_EVENTS'), '#', $vName === 'icalevent.list', 'jevents', 'fa-calendar-plus-o', 0, 1, 0, 'treeview ' . ($task === '' || $task === 'cpanel.cpanel' || $task === 'icalevent.list' || $task === 'icalevent.edit' ? 'active ' : ''));
		//Events Sub items
		JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADDEVENT'), 'index.php?option=com_jevents&task=icalevent.edit', $vName === 'icalevent.edit', 'jevents', 'fa-calendar-plus-o', 0, 0, 1, '');
		JevAdminJHtmlSidebar::addEntry(JText::_('JEV_INSTALL_MANAGE'), 'index.php?option=com_jevents&task=icalevent.list&state=3', $vName === 'icalevent.list', 'jevents', 'fa-calendar', 0, 0, 1, '');
		JevAdminJHtmlSidebar::addEntry(JText::_('JEV_TRASHED_EVENTS'), 'index.php?option=com_jevents&task=icalevent.list&state=-1', $vName === 'icalevent.list', 'jevents', 'fa-calendar-plus-o', 0, 0, 1, '');
		//Categories
		JevAdminJHtmlSidebar::addEntry(JText::_('JEV_CATEGORIES'), '#', $vName === 'categories', 'jevents', 'fa-folder', 0, 2, 0, 'treeview');
		//Categories Sub Items
		JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADD_CAT'), 'index.php?option=com_categories&view=category&layout=edit&extension=com_jevents', $vName === 'categories', 'jevents', 'fa-plus-square', 0, 0, 2, '');
		JevAdminJHtmlSidebar::addEntry(JText::_('JEV_INSTALL_CATS'), 'index.php?option=com_categories&extension=com_jevents', $vName === 'categories', 'jevents', 'fa-folder', 0, 0, 2, '');

		//JEvents Admin User Items:
		if (JEVHelper::isAdminUser())
		{
			// Calendars / Feeds
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_CALENDAR_FEEDS'), 'index.php?option=com_jevents&task=icals.list', $vName === 'icals.list', 'jevents', 'fa-calendar', 0, 0, 0, '');

			$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
			if ($params->get("authorisedonly", 0))
			{
				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_MANAGE_USERS'), 'index.php?option=com_jevents&task=user.list', $vName === 'user.list');
			}

			$hiddenDiff2 = ($difficulty <= 1) ? 'hiddenDifficulty' : '';
			$hiddenDiff3 = ($difficulty <= 2) ? 'hiddenDifficulty' : '';
 			//Configuration Parent
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_INSTALL_CONFIG'), '#', $vName === 'params.edit', 'jevents', 'fa-cogs', 0, 4, 0, 'treeview ' . ($task === 'params.edit' ? 'active' : ''));

			//Configuration Sub Items
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_TAB_COMPONENT'), $config_tabs . 'JEV_TAB_COMPONENT" data-jev-tab="JEV_TAB_COMPONENT', $vName ==='params.edit', 'jevents', 'fa-circle-o', 0, 0, 4, 'config_edit');
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_PERMISSIONS'), $config_tabs . 'JEV_PERMISSIONS" data-jev-tab="JEV_PERMISSIONS', $vName === 'params.edit', 'jevents', 'fa-circle-o', 0, 0, 4, 'config_edit');
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_EVENT_EDITING'), $config_tabs . 'JEV_EVENT_EDITING" data-jev-tab="JEV_EVENT_EDITING', $vName === 'params.edit', 'jevents', 'fa-circle-o', 0, 0, 4, 'difficulty1 config_edit');
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_EVENT_DETAIL_VIEW'), $config_tabs . 'JEV_EVENT_DETAIL_VIEW" data-jev-tab="JEV_EVENT_DETAIL_VIEW', $vName === 'params.edit', 'jevents', 'fa-circle-o', 0, 0, 4, 'difficulty1 config_edit');
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_MONTHLY_CALENDAR_VIEW'), $config_tabs . 'JEV_MAIN_MONTHLY_CALENDAR" data-jev-tab="JEV_MAIN_MONTHLY_CALENDAR', $vName === 'params.edit', 'jevents', 'fa-circle-o', 0, 0, 4, 'difficulty1 config_edit');
			JevAdminJHtmlSidebar::addEntry(JText::_('YEARCATEGORY_VIEW'), $config_tabs . 'JEV_YEAR_CATEGORY_VIEW" data-jev-tab="JEV_YEAR_CATEGORY_VIEW', $vName === 'params.edit', 'jevents', 'fa-circle-o', 0, 0, 4, 'difficulty1 config_edit');

			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ICAL_CALENDAR'), $config_tabs . 'JEV_ICAL_CALENDAR" data-jev-tab="JEV_ICAL_CALENDAR', $vName === 'params.edit', 'jevents', 'fa-circle-o', 0, 0, 4, 'difficulty2 config_edit ' . $hiddenDiff2);
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_TAB_RSS'), $config_tabs . 'JEV_TAB_RSS" data-jev-tab="JEV_TAB_RSS', $vName === 'params.edit', 'jevents', 'fa-circle-o', 0, 0, 4, 'difficulty2 config_edit ' . $hiddenDiff2);

			JevAdminJHtmlSidebar::addEntry(JText::_('ROBOT_SEF_OPTIONS'), $config_tabs . 'ROBOT_SEF_OPTIONS" data-jev-tab="ROBOT_SEF_OPTIONS', $vName === 'params.edit', 'jevents', 'fa-circle-o', 0, 0, 4, 'difficulty3 config_edit ' . $hiddenDiff3);
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_MODULE_CONFIG'), $config_tabs . 'JEV_MODULE_CONFIG" data-jev-tab="JEV_MODULE_CONFIG', $vName === 'params.edit', 'jevents', 'fa-circle-o', 0, 0, 4, 'difficulty3 config_edit ' . $hiddenDiff3);
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_PLUGIN_OPTIONS'), $config_tabs . 'plugin_options" data-jev-tab="plugin_options', $vName === 'params.edit', 'jevents', 'fa-circle-o', 0, 0, 4, 'difficulty3 config_edit ' . $hiddenDiff3);

			//Custom Layouts
			$flt = $jinput->get('filter_layout_type', '');
			$vNameCLayouts = (in_array($vName, array('defaults.list', 'defaults.overview')) ? 'defaults.list' : '');
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_LAYOUT_DEFAULTS'), '#', $vName === $vNameCLayouts, 'jevents', 'fa-files-o', 0, 5, 0, 'treeview');

			//JEvents Core
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_JEVENTS_CORE'), 'index.php?option=com_jevents&task=defaults.list&filter_layout_type=jevents', $vName ===  $vNameCLayouts, 'jevents', 'fa-circle-o', 0, 0, 5, ($task === $vNameCLayouts && $flt === 'jevents' ? 'active' : ''));

			//Run through club add ons
			if (in_array('com_jevpeople', $jevaddons_results))
			{
				//Managed People
				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_JEVENTS_MANAGED_PEOPLE'), 'index.php?option=com_jevents&task=defaults.list&filter_layout_type=jevpeople', $vName === $vNameCLayouts, 'jevents', 'fa-circle-o', 0, 0, 5, ($task === $vNameCLayouts && $flt === 'jevpeople' ? 'active' : ''));
			}
			if (in_array('com_jevlocations', $jevaddons_results))
			{
				//Managed Locations
				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_JEVENTS_MANAGED_LOCATIONS'), 'index.php?option=com_jevents&task=defaults.list&filter_layout_type=jevlocations', $vName ===  $vNameCLayouts, 'jevents', 'fa-circle-o', 0, 0, 5, ($task === $vNameCLayouts && $flt === 'jevlocations' ? 'active' : ''));
			}

			//Lets checkout what club themes are installed
			$themes = '';

			//Fetch the Club themes
			$haslayouts = false;

			$first = false;

			$themes =  JEV_CommonFunctions::getJEventsViewList();

			if (count($themes) > 0) {

				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_CLUB_THEMES'), '#', $vName === 'params.edit#iconic', 'jevents', 'fa-laptop', 0, 6, 0, 'treeview');

				foreach ($themes as $viewfile)
				{
					$config = JPATH_SITE . "/components/" . JEV_COM_COMPONENT . "/views/" . $viewfile . "/config.xml";
					if (file_exists($config) && !$first)
					{
						$first      = $viewfile;
						$class      = ' class="active "';
						$haslayouts = true;
						//$themes .= '<li ' . $class . '><a ' . $config_tabs . '#' . $viewfile . '" class="themes_link"><i class="fa fa-circle-o"></i>' . $viewfile . '</a></li>';
						JevAdminJHtmlSidebar::addEntry(JText::_($viewfile), $config_tabs . $viewfile .'" data-jev-tab="' . $viewfile, $vName === 'params.edit#' . $viewfile, 'jevents', 'fa-circle-o', 0, 0, 6, 'config_edit');
					}
					elseif (file_exists($config))
					{
						$class      = '';
						$haslayouts = true;
						JevAdminJHtmlSidebar::addEntry(JText::_($viewfile), $config_tabs . $viewfile .'" data-jev-tab="' . $viewfile, $vName === 'params.edit#' . $viewfile, 'jevents', 'fa-circle-o', 0, 0, 6, 'config_edit');
					}
				}

			}

			// Support Info
			JevAdminJHtmlSidebar::addEntry(JText::_('SUPPORT_INFO'), 'index.php?option=com_jevents&task=cpanel.support', $vName === 'cpanel.support', 'jevents', 'fa-support', 0, 7, 0);
			// Custom CSS
			JevAdminJHtmlSidebar::addEntry(JText::_('JEV_CUSTOM_CSS'), 'index.php?option=com_jevents&view=customcss', $vName === 'customcss', 'jevents', 'fa-css3', 0, 8, 0);

			// Lets do a single query and get an array of the addons! Wheyyy // Also make sure they are enabled!
			$jevaddons = array('com_jevlocations', 'com_rsvppro', 'jevcustomfields', 'com_jevpeople');
			$query = '';
			foreach ($jevaddons as $key=> $addon) {
				if ($key === 0) {
					$query .=  ' \'%' . $addon . '%\' AND enabled = 1';
				} else {
					$query .= ' OR element LIKE \'%' . $addon . '%\' AND enabled = 1';
				}
			}

			// Links to addons
			$db = JFactory::getDbo();
			$db->setQuery("SELECT element FROM #__extensions WHERE element LIKE " . $query);
			$jevaddons_results = $db->loadColumn();


			// Managed Locations
			if (in_array('com_jevlocations', $jevaddons_results))
			{
				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_MANAGED_LOCATIONS_HEADER_TITLE'), '#', $vName === 'cpanel.cpanel', 'jevlocations', 'fa-map-o', 0, 9, 0, 'treeview ' . ($task === '' ? 'active' : ''));
				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_LOCATIONS_OVERVIEW'), 'index.php?option=com_jevlocations&task=locations.overview', $vName === 'locations.overview', 'jevlocations', 'fa-map-marker', 0, 0, 9);
				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_LOCATIONS_ADD_LOCATION'), 'index.php?option=com_jevlocations&task=locations.edit', $vName === 'locations.edit', 'jevlocations', 'fa-edit', 0, 0, 9);
				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_CATEGORIES'), 'index.php?option=com_jevlocations', $vName === 'cpanel.cpanel', 'jevlocations', 'fa-folder', 0, 0, 9);
				// Config page not yet supported. Remove it.
				// JevAdminJHtmlSidebar::addEntry(JText::_('JEV_INSTALL_CONFIG'), 'index.php?option=com_jevlocations&task=params.edit', $vName === 'params.edit', 'jevlocations', 'fa-cogs', 0, 0, 9);
			}

			// Managed People
			if (in_array('com_jevpeople', $jevaddons_results))
			{
				JFactory::getLanguage()->load("com_jevpeople", JPATH_ADMINISTRATOR);
				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_MANAGED_PEOPLE_HEADER_TITLE'), '#', $vName === 'cpanel.cpanel', 'jevpeople', 'fa-puzzle-piece', 0, 11, 0, 'treeview ' . ($task === '' ? 'active' : ''));
				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_PEOPLE'), 'index.php?option=com_jevpeople&task=people.overview', $vName === 'people.overview', 'jevpeople', 'fa-users', 0, 0, 11);
				JevAdminJHtmlSidebar::addEntry(JText::_('PEOPLE_TYPES'), 'index.php?option=com_jevpeople&task=types.list', $vName === 'types.list', 'jevpeople', 'fa-random', 0, 0, 11);
				JevAdminJHtmlSidebar::addEntry(JText::_('CATEGORIES'), 'index.php?option=com_categories&extension=com_jevpeople', $vName === '', 'jevpeople', 'fa-folder', 0, 0, 11);
			}
			// RSVP Pro
			if (in_array('com_rsvppro', $jevaddons_results))
			{
				JFactory::getLanguage()->load("com_rsvppro", JPATH_ADMINISTRATOR);
				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_ADMIN_RSVP_PRO_HEADER_TITLE'), '#', $vName === 'cpanel.cpanel', 'jevrsvppro', 'fa-users', 0, 11, 0, 'treeview ' . ($task === '' ? 'active' : ''));
				JevAdminJHtmlSidebar::addEntry(JText::_('RSVP_SESSIONS'), 'index.php?option=com_rsvppro&task=sessions.list', $vName === 'sessions.list', 'jevrsvppro', 'fa-users', 0, 0, 11);
				JevAdminJHtmlSidebar::addEntry(JText::_('RSVP_TEMPLATES'), 'index.php?option=com_rsvppro&task=templates.list', $vName === 'template.list', 'jevrsvppro', 'fa-edit', 0, 0, 11);
				JevAdminJHtmlSidebar::addEntry(JText::_('RSVP_PRO_PAYMENT_METHODS'), 'index.php?option=com_rsvppro&task=pmethods.overview', $vName === 'pmethods.overview', 'jevrsvppro', 'fa-dollar', 0, 0, 11);
				JevAdminJHtmlSidebar::addEntry(JText::_('RSVP_CONFIGURATION'), 'index.php?option=com_rsvppro&task=params.edit', $vName === 'params.edit', 'jevrsvppro', 'fa-cogs', 0, 0, 11);
			}
			// Custom Fields
			if (in_array('jevcustomfields', $jevaddons_results))
			{
				JevAdminJHtmlSidebar::addEntry(JText::_('JEV_CUSTOM_FIELDS'), 'index.php?option=com_jevents&task=plugin.jev_customfields.overview', $vName === 'plugin.jev_customfields.overview', 'jevents', 'fa-object-group', 0, 12, 0);
			}
		}
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param    int        The category ID.
	 * @param    int        The article ID.
	 *
	 * @return    JObject
	 */
	public static function getActions($categoryId = 0, $articleId = 0)
	{
		$user   = JFactory::getUser();
		$result = new JObject;

		if (empty($articleId) && empty($categoryId))
		{
			$assetName = 'com_jevents';
		}
		else if (empty($articleId))
		{
			$assetName = 'com_jevents.category.' . (int) $categoryId;
		}
		else
		{
			$assetName = 'com_jevents.article.' . (int) $articleId;
		}

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;

	}

	public static function addAdminSidebar($toolbar = '')
	{

		$jinput = JFactory::getApplication()->input;

		$task = $jinput->getCmd("task", "cpanel.cpanel");

		$com_params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		$difficulty = $com_params->get('com_difficulty', 0);

		// Links to addons
		// Lets do a single query and get an array of the addons! Wheyyy
		// Also make sure they are enabled!
		$jevaddons = array('com_jevlocations', 'com_rsvppro', 'jevcustomfields', 'com_jevpeople');
		// Links to addons
		$db = JFactory::getDbo();
		$db->setQuery("SELECT element FROM #__extensions WHERE element LIKE '%" . implode('%\' OR element LIKE \'%', $jevaddons) . "%' AND enabled = 1");
		$jevaddons_results = $db->loadColumn();

		// Custom Fields
		$db = JFactory::getDbo();
		$db->setQuery("SELECT * FROM #__extensions WHERE element = 'jevcustomfields' AND type='plugin' AND folder='jevents' ");
		$extension     = $db->loadObject();
		$manifestCache = json_decode($extension->manifest_cache);
		// Stop if user is not authorised to manage JEvents.
		$customfields = '';
		if ($manifestCache->version >= '3.5' && $extension && $extension->enabled && JEVHelper::isAdminUser())
		{
			$link = "index.php?option=com_jevents&task=plugin.jev_customfields.overview";
			JFactory::getLanguage()->load("plg_jevents_jevcustomfields", JPATH_ADMINISTRATOR);
			$customfields = '<li><a href="' . $link . '"><i class="fa fa-file-text-o"></i> <span>' . JText::_('JEV_CUSTOM_FIELDS') . '</span></a></li>';
		}

		$config_tabs = ($task == 'params.edit' ? "data-toggle=\"tab\" href=\"" : "href=\"index.php?option=com_jevents&task=params.edit");

		//Lets checkout what club themes are installed
		$themes = '';

		//Fetch the Club themes
		$haslayouts = false;

		$first = false;

		foreach (JEV_CommonFunctions::getJEventsViewList() as $viewfile)
		{
			$config = JPATH_SITE . "/components/" . JEV_COM_COMPONENT . "/views/" . $viewfile . "/config.xml";
			if (file_exists($config) && !$first)
			{
				$first      = $viewfile;
				$class      = ' class="active "';
				$haslayouts = true;
				$themes .= '<li ' . $class . '><a ' . $config_tabs . '#' . $viewfile . '" class="themes_link"><i class="fa fa-circle-o"></i>' . $viewfile . '</a></li>';

			}
			elseif (file_exists($config))
			{
				$class      = '';
				$haslayouts = true;
				$themes .= '<li ' . $class . '><a ' . $config_tabs . '#' . $viewfile . '" class="themes_link"><i class="fa fa-circle-o"></i>' . $viewfile . '</a></li>';
			}
		}

		$sidebar_html = '<section class="sidebar" style="height: auto;">
			<!-- sidebar menu: : style can be found in sidebar.less -->
            <div class="je-sb-toolbar">
			    ' . $toolbar . '
			</div>
			<ul class="sidebar-menu">
				<li class="' . ($task == 'defaults.list' || $task == 'defaults.edit' ? 'active' : '') . ' treeview">
					<a href="#">
						<i class="fa fa-files-o"></i>
						<span>' . JText::_("JEV_LAYOUT_DEFAULTS") . '</span> <i class="fa fa-angle-left pull-right"></i>
						<span class="label label-primary pull-right"></span>
					</a>       
					<ul class="treeview-menu">
						<li><a href="index.php?option=com_jevents&task=defaults.list&filter_layout_type=jevents"><i class="fa fa-circle-o"></i> ' . JText::_("JEV_ADMIN_JEVENTS_CORE") . '</a></li>';
		if (in_array('com_jevpeople', $jevaddons_results))
		{
			$sidebar_html .= '<li><a href="index.php?option=com_jevents&task=defaults.list&filter_layout_type=jevpeople"><i class="fa fa-circle-o"></i> ' . JText::_("JEV_ADMIN_JEVENTS_MANAGED_PEOPLE") . '</a></li>';
		}
		if (in_array('com_jevlocations', $jevaddons_results))
		{
			$sidebar_html .= '<li><a href="index.php?option=com_jevents&task=defaults.list&filter_layout_type=jevlocations"><i class="fa fa-circle-o"></i> ' . JText::_("JEV_ADMIN_JEVENTS_MANAGED_LOCATIONS") . '</a></li>';
		}
		$sidebar_html .= '
					</ul>
				</li>';

		if ($haslayouts)
		{
			$sidebar_html .= '
            <li class="' . ($task == 'params.edit#club-layouts' ? 'active' : '') . ' treeview">
					<a href="#">
						<i class="fa fa-laptop"></i> <span>' . JText::_("JEV_ADMIN_CLUB_THEMES") . '</span> <i class="fa fa-angle-left pull-right"></i>
					</a>
					<ul class="treeview-menu">' . $themes . '</ul>
				</li>
				';
		}

		// Custom CSS
		$sidebar_html .= '<li class="' . ($task == 'cpanel.custom_css' ? 'active' : '') . ' ">
					<a href="index.php?option=com_jevents&task=cpanel.custom_css">
						<i class="fa fa-css3"></i> <span>' . JText::_("JEV_CUSTOM_CSS") . '</span>
					</a>
				</li>';


		$sidebar_html .= '<!-- Custom Fields -->
				    ' . $customfields . '
			</ul>
		';

		// RSVP Pro
		$sidebar_html .= '<ul class="sidebar-menu">
				<li class="header">' . JText::_("JEV_ADMIN_RSVP_PRO_HEADER_TITLE") . '</li>
				<li class="' . ($task == '' || $task == 'cpanel.cpanel' || $task == 'icalevent.list' || $task == 'icalevent.edit' ? 'active' : '') . ' ">				
					<a href="index.php?option=com_jevents&task=cpanel.cpanel">
						<i class="fa fa-dashboard"></i> <span>' . JText::_("JEV_ADMIN_DASHBOARD") . '</span>
					</a>
				</li>
				</ul>';

		// Managed Locations

		$sidebar_html .= '<ul class="sidebar-menu">
				<li class="header">' . JText::_("JEV_ADMIN_MANAGED_LOCATIONS_HEADER_TITLE") . '</li>
				<li class="' . ($task == '' || $task == 'cpanel.cpanel' || $task == 'icalevent.list' || $task == 'icalevent.edit' ? 'active' : '') . ' ">				
					<a href="index.php?option=com_jevlocations&task=locations.overview">
						<i class="fa fa-dashboard"></i> <span>' . JText::_("JEV_ADMIN_LOCATIONS_OVERVIEW") . '</span>
					</a>
				</li>
				<li class="' . ($task == '' || $task == 'cpanel.cpanel' || $task == 'icalevent.list' || $task == 'icalevent.edit' ? 'active' : '') . ' ">				
					<a href="index.php?option=com_jevlocations&task=locations.edit">
						<i class="fa fa-dashboard"></i> <span>' . JText::_("JEV_ADMIN_LOCATIONS_ADD_LOCATION") . '</span>
					</a>
				</li>
				<li>
					<a href="index.php?option=com_categories&extension=com_jevlocations">
						<i class="fa fa-folder"></i> <span>' . JText::_("JEV_CATEGORIES") . '</span>
					</a>
				</li>
				</ul>';

		// Managed People

		$sidebar_html .= '<ul class="sidebar-menu">
				<li class="header">' . JText::_("JEV_ADMIN_MANAGED_PEOPLE_HEADER_TITLE") . '</li>
				<li class="' . ($task == '' || $task == 'cpanel.cpanel' || $task == 'icalevent.list' || $task == 'icalevent.edit' ? 'active' : '') . ' ">				
					<a href="index.php?option=com_jevents&task=cpanel.cpanel">
						<i class="fa fa-dashboard"></i> <span>' . JText::_("JEV_ADMIN_DASHBOARD") . '</span>
					</a>
				</li>
				</ul>';

		$sidebar_html .= '</section>';


		return $sidebar_html;
	}

	public static function addAdminHeader($items = array(), $toolbar = '', $notifications = array(), $params = array())
	{
		// Load in the Libraries
		JEVHelper::stylesheet('jevadmin_lte.css', 'administrator/components/' . JEV_COM_COMPONENT . '/assets/css/');
		JEVHelper::stylesheet('jev_cp.css', 'administrator/components/' . JEV_COM_COMPONENT . '/assets/css/');
		JEVHelper::script('app.js', 'administrator/components/' . JEV_COM_COMPONENT . '/assets/js/');
		JEVHelper::script('jquery.slimscroll.min.js', 'administrator/components/' . JEV_COM_COMPONENT . '/assets/js/plugins/slimScroll/');
		JEVHelper::script('fastclick.min.js', 'administrator/components/' . JEV_COM_COMPONENT . '/assets/js/plugins/fastclick/');

//<!-- Ionicons -->
//<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
//<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
		JEVHelper::stylesheet('font-awesome.min.css', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/');
		JEVHelper::stylesheet('ionicons.min.css', 'https://code.ionicframework.com/ionicons/2.0.1/css//');

//Get the current user for user display.
		$user        = JFactory::getUser();
		$header_html = '<a href="index.php?option=com_jevents&task=cpanel.cpanel" class="logo">
			<!-- mini logo for sidebar mini 50x50 pixels -->
			<span class="logo-mini"><img src="components/' . JEV_COM_COMPONENT . '/assets/images/JeventsTransparent_icon.png" alt="JEvents Icon" /></span>
			<!-- logo for regular state and mobile devices -->
			<span class="logo-lg"><img style="width:50px;max-width:100%;margin-top:-18px;margin-left:-10px;margin-right:5px;"src="components/' . JEV_COM_COMPONENT . '/assets/images/JeventsTransparent_icon.png" alt="JEvents Icon" /><b>JE</b>vents</span>
		</a>
		<!-- Header Navbar: style can be found in header.less -->
		<nav class="navbar navbar-static-top" role="navigation">
			<!-- Sidebar toggle button-->
			<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
				<span class="sr-only">Toggle navigation</span>
			</a>
			<div class="je-toolbar">
			    ' . $toolbar . '
			</div>
			<!-- Navbar Right Menu -->
			<div class="navbar-custom-menu">
				<ul class="nav navbar-nav">';

		foreach ($items as $key => $value)
		{
			$header_html .= $value;
		}

		return $header_html;

	}

	public static function addAdminFooter()
	{
		$version = JEventsVersion::getInstance();

		$footer_html = '<footer class="main-footer">';
		$footer_html .= '<div class="pull-right hidden-xs"><b>Version: </b>' . JString::substr($version->getShortVersion(), 1) . '</div><strong>Copyright © 2015 <a href="http://www.jevents.net">JEvents - GWE Systems Ltd</a>.</strong> All rights reserved.';
		$footer_html .= '</footer>';

		return $footer_html;
	}
}