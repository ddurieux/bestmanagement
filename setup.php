<?php

/*
   ------------------------------------------------------------------------
   Supportcontract
   Copyright (C) 2014-2014 by the Supportcontract Development Team.

   https://github.com/ddurieux/bestmanagement   
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Supportcontract project.

   Supportcontract is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Supportcontract is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Supportcontract. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Supportcontract
   @author    David Durieux, Nicolas Mercier
   @co-author
   @copyright Copyright (c) 2014-2014 Supportcontract team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://github.com/ddurieux/bestmanagement
   @since     2014

   ------------------------------------------------------------------------
 */

define ("PLUGIN_SUPPORTCONTRACT_VERSION","0.80+1.0");

// Initialise les hooks du plugin
function plugin_init_supportcontract() {
	global $PLUGIN_HOOKS,$CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['supportcontract'] = true;
   
	Plugin::registerClass('PluginSupportcontractContract',
                         array('addtabon' => array('Contract')));
	Plugin::registerClass('PluginSupportcontractEntity',
                         array('addtabon' => array('Entity')));
	Plugin::registerClass('PluginSupportcontractPurchase');
   Plugin::registerClass('PluginSupportcontractTicket_Contract',
                         array('addtabon' => array('Ticket')));
	Plugin::registerClass('PluginSupportcontractProfile',
                         array('addtabon' => array('Profile')));
   
   
//   $PLUGIN_HOOKS['plugin_pdf']['PluginSupportcontractContract'] = 'PluginSupportcontractContractPDF';
   
   $PLUGIN_HOOKS["change_profile"]["supportcontract"] = array("PluginSupportcontractProfile","changeprofile");
   
   $PLUGIN_HOOKS["menu_entry"]["supportcontract"] = "front/contract.php";
   
   
   
   
//	Plugin::registerClass("PluginSupportcontractAllContrats");
//	Plugin::registerClass("PluginSupportcontractAllTickets");
//	
//	Plugin::registerClass("PluginSupportcontractTicket");
	
	// Change profile
//	
	
	// Display a menu entry ?
//	if (plugin_supportcontract_haveRight("supportcontract","recapglobal", 1))
//	{ // Right set in change_profile hook
//	  $PLUGIN_HOOKS["menu_entry"]["supportcontract"] = "front/supportcontract.php";
//	  $PLUGIN_HOOKS["helpdesk_menu_entry"]["supportcontract"] = true;
//	}

	
	if (Session::haveRight("config","w")) {
//		$PLUGIN_HOOKS["config_page"]["supportcontract"] = "front/config.form.php";	// Page de configuration du plugin
   }
	
	// Onglets
//	$PLUGIN_HOOKS["headings"]["supportcontract"]        = "plugin_get_headings_supportcontract";
//	$PLUGIN_HOOKS["headings_action"]["supportcontract"] = "plugin_headings_actions_supportcontract";
	

	// Item action event
//	$PLUGIN_HOOKS["pre_item_update"]["supportcontract"] = array("Contract"=>"plugin_pre_item_update_supportcontract",
//															"Ticket"=>"plugin_pre_item_update_supportcontract",
//															"TicketTask"=>"plugin_pre_item_update_supportcontract");
//	$PLUGIN_HOOKS["item_update"]["supportcontract"]     = array("Contract"=>"plugin_item_update_supportcontract",
//															"Ticket"=>"plugin_item_update_supportcontract",
//															"TicketTask"=>"plugin_item_update_supportcontract");
//
//	$PLUGIN_HOOKS["pre_item_add"]["supportcontract"]     = array("Contract"=>"plugin_pre_item_add_supportcontract",
//															"Ticket"=>"plugin_pre_item_add_supportcontract",
//															"TicketTask"=>"plugin_pre_item_add_supportcontract");
//	$PLUGIN_HOOKS["item_add"]["supportcontract"]		   = array("Contract"=>"plugin_item_add_supportcontract",
//															"Ticket"=>"plugin_item_add_supportcontract",
//															"TicketTask"=>"plugin_item_add_supportcontract");
//
// $PLUGIN_HOOKS["pre_item_purge"]["supportcontract"] = array("Contract"=>"plugin_pre_item_purge_supportcontract",
//															"Ticket"=>"plugin_pre_item_purge_supportcontract",
//															"Profile" => array("PluginSupportcontractProfile","cleanProfiles"));

   $PLUGIN_HOOKS["pre_item_purge"]["supportcontract"] = array(
       'Contract' => array('PluginSupportcontractContract' => 'purgeContract')
   );
   
//	$PLUGIN_HOOKS["redirect_page"]["supportcontract"] = "supportcontract.form.php";
//
//	// Massive Action definition
//   $PLUGIN_HOOKS['use_massive_action']['supportcontract'] = 1;


}



function plugin_version_supportcontract() {
   return array("name"           => "Support Contract",
                "version"        => PLUGIN_SUPPORTCONTRACT_VERSION,
                "author"         => "David Durieux",
                "homepage"       => "",
                "minGlpiVersion" => "0.84");
}



function plugin_supportcontract_check_prerequisites()
{
	if (GLPI_VERSION >= 0.84) {
		return true;
   } else {
		echo "GLPI version not compatible need 0.80";
   }	
}



function plugin_supportcontract_check_config($verbose=false) {

	if (true) {
		return true;
   }
   
	if ($verbose) {
		echo __('Installed / not configured');
   }
	return false;
}



function plugin_supportcontract_haveRight($plug, $module, $right) {
	$matches = array(""  => array("","r","w"),
					 "r" => array("r","w"),
					 "w" => array("w"),
					 "1" => array("1"),
					 "0" => array("0","1")); // ne doit pas arriver non plus
	
	if (isset($_SESSION["glpi_plugin_supportcontract_profile"][$module])
		&& in_array($_SESSION["glpi_plugin_supportcontract_profile"][$module],$matches[$right])) {
		return true;
   }
	
	return false;
}



function plugin_supportcontract_checkRight($plug, $module, $right) {
	global $CFG_GLPI;

	if (!plugin_supportcontract_haveRight($plug, $module, $right)) {
		if (!isset ($_SESSION["glpiID"])) {
			Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
			exit ();
		}
		Html::displayRightError();
	}
}

?>