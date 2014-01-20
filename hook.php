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


function plugin_pre_item_update_contractsupport($item) {
	global $DB, $LANG;
	
	// Check mandatory
	$mandatory_ok=true;
	
	// Do not check mandatory on auto import (mailgates)
	if (!isset($item->input["_auto_import"]))	{
		switch (get_class($item)) {
         
		   case "Contract" :
            $contractsaved = false;

            if (isset($item->input["begin_date"]) && $item->input["begin_date"] == "NULL"
               && VerifAddMsg("date_deb")) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][0], false, ERROR);
               $contractsaved = true;
            } else if (isset($item->input["duration"]) && $item->input["duration"] == 0
                   && VerifAddMsg("duration")) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][1], false, ERROR);
               $contractsaved = true;
            } else if (isset($item->input["contracttypes_id"]) && $item->input["contracttypes_id"] == 0
                   && VerifAddMsg("contract_type")) {
            // contract type
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][19], false, ERROR);
               $contractsaved = true;
            }

            if ($contractsaved) {
               $mandatory_ok = false;
               // on ne peut pas conserver les valeurs du contrat
               $item->input = false;
            }
            break;
			
		   case "Ticket" :
            $helpdesksaved = false;

            if (!isset($item->input["contracts_id"])) {
               continue;
            }			

            if ($item->input["contracts_id"] == -1) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][2], false, ERROR);
               $helpdesksaved = true;
            } else if (isset($item->input["ticketcategories_id"]) && $item->input["ticketcategories_id"] == 0
                   && VerifAddMsg("ticket_category")) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][3], false, ERROR);
               $helpdesksaved = true;
            } else if ($item->input["contracts_id"] == "NULL" && $item->input["id_facturation"] == 1) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][10], false, ERROR);
               $helpdesksaved = true;
            } else if ($item->input["contracts_id"] != "NULL" &&
                   isset($item->input["id_facturation"]) && $item->input["id_facturation"] == 2) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][11], false, ERROR);
               $helpdesksaved = true;
            }
            if ($helpdesksaved) {
               $mandatory_ok = false;
               $_SESSION["helpdeskSaved"] = $item->input;
               $item->input = false;
            } else {
               $num_ticket		= $item->input["id"];
               $num_contrat	= $item->input["contracts_id"];

               if (0 == countElementsInTable("glpi_plugin_bestmanagement_link_ticketcontrat",
                                      "ID_Ticket = $num_ticket")) {
                  insertToDB("glpi_plugin_bestmanagement_link_ticketcontrat", "NULL, $num_ticket, NULL");
               }			
               $query = "SELECT IFNULL(ID_Contrat,'NULL') ID_Contrat
                       FROM glpi_plugin_bestmanagement_link_ticketcontrat
                       WHERE ID_Ticket = $num_ticket";

               if ($res = $DB->query($query)) {
                  if($DB->numrows($res) > 0) {
                     if($row = $DB->fetch_assoc($res)) {
                        $old_contrat = $row["ID_Contrat"];
                     }
                  } else {
                     $old_contrat = 0;
                  }
               }
               if ($old_contrat != $num_contrat) {
                  $query = "UPDATE glpi_plugin_bestmanagement_link_ticketcontrat
                          SET ID_Contrat = $num_contrat
                          WHERE ID_Ticket = $num_ticket";

                  $DB->query($query) or die("error $query");
                  Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][5], false, INFO);
               }
               if (isset($item->input["id_facturation"])) {
                  $facturation	= $item->input["id_facturation"];

                  if (0 == countElementsInTable("glpi_plugin_bestmanagement_facturation_ticket",
                                         "ID_Ticket = $num_ticket")) {
                     insertToDB("glpi_plugin_bestmanagement_facturation_ticket", "$num_ticket, $facturation, NULL");
                     Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][8], false, INFO);
                  } else {
                     $query = "UPDATE glpi_plugin_bestmanagement_facturation_ticket
                             SET etat_fact = $facturation
                             WHERE ID_Ticket = $num_ticket";

                     $DB->query($query) or die("error $query");
                     Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][9], false, INFO);
                  }
               }
            }
            break;
			
		   case "TicketTask" :
            $helpdesksaved = false;
            if (isset($item->input["taskcategories_id"]) && $item->input["taskcategories_id"] != 0
               & VerifAddMsg("task_category")) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][27], false, ERROR);
               $helpdesksaved = false;
            } else { 
               $helpdesksaved = true;
            }
            if(isTicketOutPeriode($item->fields["tickets_id"])
               && VerifAddMsg("no_renewal")) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][28], false, INFO);
            }			
            break;
			
		} // switch()
			
		if (!$mandatory_ok) {
			return false;
      }
	}
}



function plugin_pre_item_add_bestmanagement($item) {
	global $DB, $LANG;
   
	$mandatory_ok=true;
		
	// Do not check mandatory on auto import (mailgates)
	// et si c'est un profil post-only, on ne fait pas de test
	if (!isset($item->input["_auto_import"]) 
           && $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
		switch (get_class($item)) {
         
         case "Contract" :
            $contractsaved = false;			
            // Il faut qu'une date de d�but soit saisie
            if (isset($item->input["begin_date"]) && $item->input["begin_date"] == "NULL"
               && VerifAddMsg("date_deb")) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][0], false, ERROR);
               $contractsaved = true;
            } else if (isset($item->input["duration"]) && $item->input["duration"] == 0
                   && VerifAddMsg("duration")) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][1], false, ERROR);
               $contractsaved = true;
            } else if (isset($_POST["contracttypes_id"]) && $_POST["contracttypes_id"] == 0
                   && VerifAddMsg("contract_type")) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][19], false, ERROR);
               $contractsaved = true;
            }

            if ($contractsaved) {
               $mandatory_ok = false;
               // on ne peut pas conserver les valeurs du contrat
               $item->input = false;
            }
            break;
			
		   case "Ticket" :
            $hour	= isset($item->input["hour"])	? $item->input["hour"]	: 0;
            $minute	= isset($item->input["minute"])	? $item->input["minute"]: 0;
            $helpdesksaved = false;

            if ($hour+$minute > 0) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][6], false, ERROR);
               $helpdesksaved = true;
            } else if (isset($item->input["contracts_id"]) && $item->input["contracts_id"] == -1) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][2], false, ERROR);
               $helpdesksaved = true;
            }
            if ($helpdesksaved) {
               $mandatory_ok = false;
               $_SESSION["helpdeskSaved"] = $item->input;	// conserve les valeurs du ticket
               $item->input = false;
            }
            break;
			
		   case "TicketTask" :
            $helpdesksaved = false; 
            if (isset($item->input["taskcategories_id"]) && $item->input["taskcategories_id"] == 0
               & VerifAddMsg("task_category")) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][27], false, ERROR);
               $helpdesksaved = true;
            }
            if(isTicketOutPeriode($item->fields["tickets_id"])
               && VerifAddMsg("no_renewal")) {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][28], false, INFO);
            }
            if ($helpdesksaved) {
               $mandatory_ok = false;
               $_SESSION["helpdeskSaved"] = $item->input;	// conserve les valeurs du ticket
               $item->input = false;
            }
            break;
            
		}
			
		if (!$mandatory_ok) {
			return false;
      }
	}
	return false;
}



function plugin_pre_item_purge_bestmanagement($item) {
	global $DB, $LANG;
	
	$num = $item->input["id"];

	switch (get_class($item)) {

      case "Contract" :
         if (TableExists("glpi_plugin_bestmanagement_report")) {
            $query = "DELETE FROM glpi_plugin_bestmanagement_report
                    WHERE ID_Reconduction IN (SELECT id
                                       FROM glpi_plugin_bestmanagement_reconduction
                                       WHERE ID_Contrat = $num)";
            $DB->query($query) or die("error deleting contract $num in glpi_plugin_bestmanagement_report");
         }

         $tables = array ("glpi_plugin_bestmanagement_link_ticketcontrat",
                     "glpi_plugin_bestmanagement_achat",
                     "glpi_plugin_bestmanagement_historique",
                     "glpi_plugin_bestmanagement_reconduction");

         foreach ($tables as $var) {
            if (TableExists($var)) {
               $query="DELETE FROM $var
                     WHERE ID_Contrat = $num";
               $DB->query($query) or die("$query error deleting contract $num in $var");
            }
         }
         break;
		
	   case "Ticket" :
         $tables = array ("glpi_plugin_bestmanagement_link_ticketcontrat",
                     "glpi_plugin_bestmanagement_facturation_ticket");

         foreach ($tables as $var) {
            if (TableExists($var)) {
               $query="DELETE FROM $var
                     WHERE ID_Ticket = $num";
               $DB->query($query) or die("$query error deleting ticket $num in $var");
            }
         }
         break;
		
	}
	return true;
}



function plugin_item_add_bestmanagement($item) {
	global $DB, $LANG;
	
	switch (get_class($item)) {
      
	   case "Ticket" :
         if (isset($item->input["contracts_id"])) {
            $num_ticket	 = $item->fields["id"];
            $num_contrat = $item->input["contracts_id"];

            // si le ticket est en Hors Contrat, $num_contrat vaut NULL
            $values = "NULL, $num_ticket, $num_contrat";

            insertToDB("glpi_plugin_bestmanagement_link_ticketcontrat", $values);

            // pour la facturation, requ�te d'insertion
            if (isset($item->input["id_facturation"])) {
               $facturation	= $item->input["id_facturation"];

               $values2		= "$num_ticket, $facturation, NULL";
               insertToDB("glpi_plugin_bestmanagement_facturation_ticket", $values2);
            }
         }
         break;
		
      case "TicketTask" :
         $id = $item->fields["id"];

         if (isset($item->input["madate"])) {
            $new = $item->input["madate"];

            if (date("Y-m-d") < date("Y-m-d-H-i", strtotime($new . "+ 5 DAY"))) {
               $query="UPDATE glpi_tickettasks SET	date = '$new'
                     WHERE id = $id";

               $DB->query($query) or die("erreur de la requete $query ". $DB->error());
            } else {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][30], false, INFO);
            }
         }
         break;
	}
	return true;
}



function plugin_get_headings_bestmanagement($item, $withtemplate) {
	global $LANG;
	
	switch (get_class($item)) {
      
	   case "Profile" :
         if ($item->fields["interface"]!="helpdesk") {
            return array(1 => $LANG["bestmanagement"]["title"][0]);
         }
         break;

		
      case TRACKING_TYPE:
         if ($item->fields["id"] != null 
                 && plugin_bestmanagement_haveRight("bestmanagement","linkticketcontrat", 1)) {
            return array(1 => $LANG["bestmanagement"]["config"]["linkticketcontrat"]);
         }
         break;
	
	   case CONTRACT_TYPE:
         if ($withtemplate) {
            return array();
         } else {
            return array(1 => $LANG["bestmanagement"]["title"][0]);
         }
         break;
         
   }
   return FALSE;
}



function plugin_headings_actions_bestmanagement($item) {
   
	switch (get_class($item)) {
      
	   case "Profile" :
         if ($item->getField("interface") == "central") {
            return array(1 => "plugin_headings_bestmanagement");
         }
         break;

	   case CONTRACT_TYPE:
	   case TRACKING_TYPE:
         return array(1 => "plugin_headings_bestmanagement");
         break;
      
	}
	return false;
}



function plugin_headings_bestmanagement($item, $withtemplate=0) {
	global $LANG, $CFG_GLPI;

	if (!$withtemplate) {
		echo "<div class='center'>";
		switch (get_class($item)) {
         
 		   case "Profile" :
            $prof = new PluginBestmanagementProfile();
            $prof->updatePluginRights();
            $id = $item->getField("id");
            if (!$prof->getFromDB($id)) {
               $prof->createaccess($id);
            }

            $prof->showForm(
                    $id,
                    array("target" => $CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/profile.form.php"));
            break;

		   case 'Contract':
            plugin_bestmanagement_fichecontrat($item->fields["id"]);
            break;

		   case 'Ticket':
            $ticket = new PluginBestmanagementTicket($item->fields["id"]);
            $ticket->formLinkContrat();
            $ticket->displayLinks();
            break;
         
	  }
	  echo "</div>";
	}
}



function plugin_bestmanagement_MassiveActions($type) {
	global $LANG;

	switch ($type) {
      
      case TRACKING_TYPE :
         return array("plugin_bestmanagement_generatePDF" => $LANG["bestmanagement"]["pdf"][0]);
         break;
      
	}
	return array();
}



function plugin_bestmanagement_MassiveActionsDisplay($options=array()) {
	global $LANG;
	
	switch ($options['itemtype']) {
      
	   case TRACKING_TYPE :
         switch ($options['action']) {
         
            case "plugin_bestmanagement_generatePDF" :
               echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".$LANG["buttons"][2]."'>";
               break;
         
         }
         break;
      
	}
}



function plugin_bestmanagement_MassiveActionsProcess($data) {
	global $LANG, $DB;
	
	switch ($data['action']) {
      
      case 'plugin_bestmanagement_generatePDF':
	   case 'plugin_bestmanagement_generatePDF2':
	   case 'plugin_bestmanagement_generatePDF3':
         if ($data['itemtype'] == TRACKING_TYPE) {
            $tabIDTickets = array_keys($data["item"]);

            sort($tabIDTickets);
            $trackID = "(";
            foreach($tabIDTickets as $i) {
               $trackID .= $i . ",";
            }

            $trackID = substr($trackID, 0, -1);	// pour enlever la virgule � la fin
            $trackID .= ")";

            $query =   "SELECT distinct ID_Contrat CtrID
                     FROM glpi_plugin_bestmanagement_link_ticketcontrat
                     WHERE ID_Ticket IN " . $trackID;

            $nbcontrat=0;
            if ($result = $DB->query($query)) {
               if ($DB->numrows($result) > 0) {
                  while ($row = $DB->fetch_assoc($result)) {
                     ++$nbcontrat;
                  }
               }
            }

            if ($nbcontrat <= 2)	{
               $_SESSION["bestmanagement"]["TabID"] = $tabIDTickets;
               echo "<script type='text/javascript'>location.href='../plugins/bestmanagement/front/export.massive.php'</script>";
            } else {
               Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][25], false, ERROR);
            }
         }
         break;
        
	}
}



/**
 * Installation of plugin
 */
function plugin_supportcontract_install() {
	global $DB;
	
   $query = "SHOW TABLES;";
   $result=$DB->query($query);
   $update = 0;
   while ($data=$DB->fetch_array($result)) {
      if (strstr($data[0],"glpi_plugin_supportcontract_")) {
         $update = 1;
      }
   }	
   if ($update == 1) {
      include_once (GLPI_ROOT . "/plugins/supportcontract/install/update.php");
   } else {
      include_once (GLPI_ROOT . "/plugins/supportcontract/install/install.php");
      pluginSupportcontractInstall();
   }
   
   
	// TODO : To verify cron (ddurieux)
	// To be called for each task the plugin manage
	CronTask::Register("PluginSupportcontractContrat", "Verif", WEEK_TIMESTAMP);
	CronTask::Register("PluginSupportcontractContrat", "SQL", MONTH_TIMESTAMP);
   
	return TRUE;
}



/**
 * Uninstall plugin
 */
function plugin_supportcontract_uninstall() {
	global $DB;

   $query = "SHOW TABLES;";
   $result=$DB->query($query);
   while ($data=$DB->fetch_array($result)) {
      if (strstr($data[0],"glpi_plugin_supportcontract_")) {
         $query_delete = "DROP TABLE `".$data[0]."`;";
         $DB->query($query_delete);
      }
   }
	return TRUE;
}

?>