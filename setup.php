<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Mise en place du plugin
// ----------------------------------------------------------------------

include_once(GLPI_ROOT . "/plugins/bestmanagement/inc/function.php");

// Initialise les hooks du plugin
function plugin_init_bestmanagement()
{
	global $PLUGIN_HOOKS,$LANG,$CFG_GLPI;

	Plugin::registerClass("PluginBestmanagementAllContrats");
	Plugin::registerClass("PluginBestmanagementAllTickets");
	Plugin::registerClass("PluginBestmanagementContrat");
	Plugin::registerClass("PluginBestmanagementProfile");
	Plugin::registerClass("PluginBestmanagementTicket");
	
	// Change profile
	$PLUGIN_HOOKS["change_profile"]["bestmanagement"] = array("PluginBestmanagementProfile","changeprofile");
	
	// Display a menu entry ?
	if (plugin_bestmanagement_haveRight("bestmanagement","recapglobal", 1))
	{ // Right set in change_profile hook
	  $PLUGIN_HOOKS["menu_entry"]["bestmanagement"] = "front/bestmanagement.php";
	  $PLUGIN_HOOKS["helpdesk_menu_entry"]["bestmanagement"] = true;
	}

	
	if (Session::haveRight("config","w"))
		$PLUGIN_HOOKS["config_page"]["bestmanagement"] = "front/config.form.php";	// Page de configuration du plugin
		
	
	// Onglets
	$PLUGIN_HOOKS["headings"]["bestmanagement"]        = "plugin_get_headings_bestmanagement";
	$PLUGIN_HOOKS["headings_action"]["bestmanagement"] = "plugin_headings_actions_bestmanagement";
	

	// Item action event
	$PLUGIN_HOOKS["pre_item_update"]["bestmanagement"] = array("Contract"=>"plugin_pre_item_update_bestmanagement",
															"Ticket"=>"plugin_pre_item_update_bestmanagement",
															"TicketTask"=>"plugin_pre_item_update_bestmanagement");
	$PLUGIN_HOOKS["item_update"]["bestmanagement"]     = array("Contract"=>"plugin_item_update_bestmanagement",
															"Ticket"=>"plugin_item_update_bestmanagement",
															"TicketTask"=>"plugin_item_update_bestmanagement");

	$PLUGIN_HOOKS["pre_item_add"]["bestmanagement"]     = array("Contract"=>"plugin_pre_item_add_bestmanagement",
															"Ticket"=>"plugin_pre_item_add_bestmanagement",
															"TicketTask"=>"plugin_pre_item_add_bestmanagement");
	$PLUGIN_HOOKS["item_add"]["bestmanagement"]		   = array("Contract"=>"plugin_item_add_bestmanagement",
															"Ticket"=>"plugin_item_add_bestmanagement",
															"TicketTask"=>"plugin_item_add_bestmanagement");

	$PLUGIN_HOOKS["pre_item_purge"]["bestmanagement"] = array("Contract"=>"plugin_pre_item_purge_bestmanagement",
															"Ticket"=>"plugin_pre_item_purge_bestmanagement",
															"Profile" => array("PluginBestmanagementProfile","cleanProfiles"));

	$PLUGIN_HOOKS["redirect_page"]["bestmanagement"] = "bestmanagement.form.php";

	// Massive Action definition
   $PLUGIN_HOOKS['use_massive_action']['bestmanagement'] = 1;
   
	// Fichier javascript et css
	$PLUGIN_HOOKS["add_javascript"]["bestmanagement"] = "bestmanagement.js";
	$PLUGIN_HOOKS["add_css"]["bestmanagement"] = "bestmanagement.css";

} // plugin_init_bestmanagement()


// Donne le nom et la version du plugin
function plugin_version_bestmanagement()
{
   return array("name"           => "Best Management",
                "version"        => "1.6.0",
                "author"         => "Nicolas Mercier <a href='http://www.one-id.fr/'><img src='".GLPI_ROOT."/plugins/bestmanagement/pics/favicon.ico'></a>",
                "homepage"       => "http://www.one-id.fr/",
                "minGlpiVersion" => "0.80");
} // plugin_version_bestmanagement()


// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_bestmanagement_check_prerequisites()
{
	if (GLPI_VERSION >= 0.80)
		return true;
	else
		echo "GLPI version not compatible need 0.80";
	
} // plugin_bestmanagement_check_prerequisites()


// Check configuration process for plugin : need to return true if succeeded
// Can display a message only if failure and $verbose is true
function plugin_bestmanagement_check_config($verbose=false)
{
	global $LANG;

	if (true) // Your configuration check
		return true;
	  
	if ($verbose)
		echo $LANG["plugins"][2];

	return false;
} // plugin_bestmanagement_check_config()

function plugin_bestmanagement_haveRight($plug, $module, $right)
{
	$matches = array(""  => array("","r","w"), // ne doit pas arriver normalement
					 "r" => array("r","w"),
					 "w" => array("w"),
					 "1" => array("1"),
					 "0" => array("0","1")); // ne doit pas arriver non plus
	
	if (isset($_SESSION["glpi_plugin_bestmanagement_profile"][$module])
		&& in_array($_SESSION["glpi_plugin_bestmanagement_profile"][$module],$matches[$right]))
		return true;
	
	return false;
} // plugin_bestmanagement_haveRight()

function plugin_bestmanagement_checkRight($plug, $module, $right)
{
	global $CFG_GLPI;

	if (!plugin_bestmanagement_haveRight($plug, $module, $right))
	{
		// Gestion timeout session
		if (!isset ($_SESSION["glpiID"]))
		{
			Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
			exit ();
		}
		Html::displayRightError();
	}
} // plugin_bestmanagement_checkRight()


?>