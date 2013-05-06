<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Traitements sp�cifiques du plugin
// ----------------------------------------------------------------------

include_once (GLPI_ROOT . "/plugins/bestmanagement/inc/profile.class.php");
include_once (GLPI_ROOT . "/plugins/bestmanagement/inc/ticket.class.php");
include_once (GLPI_ROOT . "/plugins/bestmanagement/inc/plugin_bestmanagement_display.php");

//### HOOKS ADD, UPDATE, PURGE ###

// Pr�-Mise � jour d'un �l�ment
function plugin_pre_item_update_bestmanagement($item)
{
	global $DB, $LANG;
	
	// Check mandatory
	$mandatory_ok=true;
	
	// Do not check mandatory on auto import (mailgates)
	if (!isset($item->input["_auto_import"]))
	{
		switch (get_class($item))
		{
		  case "Contract" :
			$contractsaved = false;
			
			// Il faut qu'une date de d�but soit saisie
			if (isset($item->input["begin_date"]) && $item->input["begin_date"] == "NULL"
				&& VerifAddMsg("date_deb"))
			{				
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][0], false, ERROR);
				$contractsaved = true;
			}
			// Il faut qu'une dur�e soit saisie
			else if (isset($item->input["duration"]) && $item->input["duration"] == 0
					 && VerifAddMsg("duration"))
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][1], false, ERROR);
				$contractsaved = true;
			}
			// Type de contrat
			else if (isset($item->input["contracttypes_id"]) && $item->input["contracttypes_id"] == 0
					 && VerifAddMsg("contract_type"))
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][19], false, ERROR);
				$contractsaved = true;
			}
			
		  	if ($contractsaved)
			{
				$mandatory_ok = false;
				// on ne peut pas conserver les valeurs du contrat
				$item->input = false;
			}
			break;
			
		  case "Ticket" :
			$helpdesksaved = false;
			
			if (!isset($item->input["contracts_id"]))
				continue;
			
			// Il faut qu'un contrat soit associ� au ticket (-1 == pas de contrat)
			if ($item->input["contracts_id"] == -1)
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][2], false, ERROR);
				$helpdesksaved = true;
			}
			// La cat�gorie du ticket ne doit pas �tre vide.
			else if (isset($item->input["ticketcategories_id"]) && $item->input["ticketcategories_id"] == 0
					 && VerifAddMsg("ticket_category"))
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][3], false, ERROR);
				$helpdesksaved = true;
			}
			// Hors contrat et facturation sous contrat
			else if ($item->input["contracts_id"] == "NULL" && $item->input["id_facturation"] == 1)
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][10], false, ERROR);
				$helpdesksaved = true;
			}
			// Sous contrat et facturation hors contrat
			else if ($item->input["contracts_id"] != "NULL" &&
					 isset($item->input["id_facturation"]) && $item->input["id_facturation"] == 2)
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][11], false, ERROR);
				$helpdesksaved = true;
			}
			if ($helpdesksaved)	// le ticket ne doit pas �tre enregistr�
			{
				$mandatory_ok = false;
				$_SESSION["helpdeskSaved"] = $item->input;	// conserve les valeurs du ticket
				$item->input = false;
			}
			else // le ticket est ok, v�rification du contrat + facturation
			{
				$num_ticket		= $item->input["id"];
				$num_contrat	= $item->input["contracts_id"];

				// est-ce que le ticket provient d'un post-only ?
				if (0 == countElementsInTable("glpi_plugin_bestmanagement_link_ticketcontrat",
											  "ID_Ticket = $num_ticket"))
					// pas de tuple dans link_ticketcontrat, il faut en ins�rer
					insertToDB("glpi_plugin_bestmanagement_link_ticketcontrat", "NULL, $num_ticket, NULL");
				
				$query = "SELECT IFNULL(ID_Contrat,'NULL') ID_Contrat
						  FROM glpi_plugin_bestmanagement_link_ticketcontrat
						  WHERE ID_Ticket = $num_ticket";
				
				if($res = $DB->query($query))
				{
					if($DB->numrows($res) > 0)
						if($row = $DB->fetch_assoc($res))
							$old_contrat = $row["ID_Contrat"];
					else
						$old_contrat = 0;
				}
				// cas o� le contrat a chang�
				if ($old_contrat != $num_contrat)
				{
					$query = "UPDATE glpi_plugin_bestmanagement_link_ticketcontrat
							  SET ID_Contrat = $num_contrat
							  WHERE ID_Ticket = $num_ticket";
					
					$DB->query($query) or die("error $query");
					Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][5], false, INFO);
				}
				// pour la facturation, requ�te soit de mise � jour soit d'insertion
				if (isset($item->input["id_facturation"]))
				{
					$facturation	= $item->input["id_facturation"];
					
					if (0 == countElementsInTable("glpi_plugin_bestmanagement_facturation_ticket",
												  "ID_Ticket = $num_ticket"))
					{
						insertToDB("glpi_plugin_bestmanagement_facturation_ticket", "$num_ticket, $facturation, NULL");
						Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][8], false, INFO);
					}
					else
					{
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
				& VerifAddMsg("task_category"))
			{	
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][27], false, ERROR);
				$helpdesksaved = false;
			}
			else 
				$helpdesksaved = true;
			if(isTicketOutPeriode($item->fields["tickets_id"])
				&& VerifAddMsg("no_renewal"))
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][28], false, INFO);
			
			break;
			
		} // switch()
			
		if (!$mandatory_ok)
			return false;
	}

	
} // plugin_pre_item_update_bestmanagement()

// Pr�-Ajout d'un �l�ment
function plugin_pre_item_add_bestmanagement($item)
{
	global $DB, $LANG;
	// Check mandatory
	$mandatory_ok=true;
		
	// Do not check mandatory on auto import (mailgates)
	// et si c'est un profil post-only, on ne fait pas de test
	if (!isset($item->input["_auto_import"]) && $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk")
	{
		switch (get_class($item))
		{
		  case "Contract" :
			$contractsaved = false;			
			// Il faut qu'une date de d�but soit saisie
			if (isset($item->input["begin_date"]) && $item->input["begin_date"] == "NULL"
				&& VerifAddMsg("date_deb"))
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][0], false, ERROR);
				$contractsaved = true;
			}
			// Il faut qu'une dur�e soit saisie
			else if (isset($item->input["duration"]) && $item->input["duration"] == 0
					 && VerifAddMsg("duration"))
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][1], false, ERROR);
				$contractsaved = true;
			}
			else if (isset($_POST["contracttypes_id"]) && $_POST["contracttypes_id"] == 0
					 && VerifAddMsg("contract_type"))
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][19], false, ERROR);
				$contractsaved = true;
			}
			
		  	if ($contractsaved)
			{
				$mandatory_ok = false;
				// on ne peut pas conserver les valeurs du contrat
				$item->input = false;
			}
			break;
			
		  case "Ticket" :
			$hour	= isset($item->input["hour"])	? $item->input["hour"]	: 0;
			$minute	= isset($item->input["minute"])	? $item->input["minute"]: 0;
			$helpdesksaved = false;
			
			// On ne doit pas saisir de temps � la cr�ation
			if ($hour+$minute > 0)
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][6], false, ERROR);
				$helpdesksaved = true;
			}
			// Il faut qu'un contrat soit associ� au ticket (-1 == pas de contrat)
			else if (isset($item->input["contracts_id"]) && $item->input["contracts_id"] == -1)
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][2], false, ERROR);
				$helpdesksaved = true;
			}
			/*
			// La cat�gorie du ticket ne doit pas �tre vide. (sauf pour les post-only)
			else if (isset($item->input["ticketcategories_id"]) && $item->input["ticketcategories_id"] == 0)
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][3], false, ERROR);
				$helpdesksaved = true;
			}
			*/
			if ($helpdesksaved)
			{
				$mandatory_ok = false;
				$_SESSION["helpdeskSaved"] = $item->input;	// conserve les valeurs du ticket
				$item->input = false;
			}
			break;
			
		  case "TicketTask" :
		  $helpdesksaved = false; 
			if (isset($item->input["taskcategories_id"]) && $item->input["taskcategories_id"] == 0
				& VerifAddMsg("task_category"))
			{
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][27], false, ERROR);
				$helpdesksaved = true;
			}
			if(isTicketOutPeriode($item->fields["tickets_id"])
				&& VerifAddMsg("no_renewal"))
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][28], false, INFO);
				
			if ($helpdesksaved)
			{
				$mandatory_ok = false;
				$_SESSION["helpdeskSaved"] = $item->input;	// conserve les valeurs du ticket
				$item->input = false;
			}
			
			break;
		} // switch()
			
		if (!$mandatory_ok)
			return false;
	}

	return false;
} // plugin_pre_item_add_bestmanagement()

// Pr�-urge d'un �l�ment
function plugin_pre_item_purge_bestmanagement($item)
{
	global $DB, $LANG;
	
	/*si un �l�ment est d�finitivement supprim�, on
	peut aussi le supprimer des tables de notre plugin */
	$num = $item->input["id"];

	switch (get_class($item))
	{
	  case "Contract" :
		if (TableExists("glpi_plugin_bestmanagement_report"))
		{
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
		
		foreach ($tables as $var)
			if (TableExists($var))
			{
				$query="DELETE FROM $var
						WHERE ID_Contrat = $num";
				$DB->query($query) or die("$query error deleting contract $num in $var");
			}
		break;
		
	  case "Ticket" :
		$tables = array ("glpi_plugin_bestmanagement_link_ticketcontrat",
						"glpi_plugin_bestmanagement_facturation_ticket");
		
		foreach ($tables as $var)
			if (TableExists($var))
			{
				$query="DELETE FROM $var
						WHERE ID_Ticket = $num";
				$DB->query($query) or die("$query error deleting ticket $num in $var");
			}
		break;
		
	} // switch
	
	return true;
} // plugin_pre_item_purge_bestmanagement()

function plugin_item_add_bestmanagement($item)
{
	global $DB, $LANG;
	
	switch (get_class($item))
	{
	  case "Ticket" :
		if (isset($item->input["contracts_id"]))
		{
			$num_ticket	 = $item->fields["id"];
			$num_contrat = $item->input["contracts_id"];
			
			// si le ticket est en Hors Contrat, $num_contrat vaut NULL
			$values = "NULL, $num_ticket, $num_contrat";
			
			insertToDB("glpi_plugin_bestmanagement_link_ticketcontrat", $values);
		
			// pour la facturation, requ�te d'insertion
			if (isset($item->input["id_facturation"]))
			{
				$facturation	= $item->input["id_facturation"];
				
				$values2		= "$num_ticket, $facturation, NULL";
				insertToDB("glpi_plugin_bestmanagement_facturation_ticket", $values2);
			}
		}
		break;
		
	  case "TicketTask" :
		$id = $item->fields["id"];
		
		if (isset($item->input["madate"]))
		{
			$new = $item->input["madate"];
			
			if (date("Y-m-d") < date("Y-m-d-H-i", strtotime($new . "+ 5 DAY")))
			{
				$query="UPDATE glpi_tickettasks SET	date = '$new'
						WHERE id = $id";
				
				$DB->query($query) or die("erreur de la requete $query ". $DB->error());
			}
			else
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][30], false, INFO);
		}
	}
	return true;

} // plugin_item_add_bestmanagement()

//### END HOOKS ADD, UPDATE, PURGE ###


// Define headings added by the plugin
function plugin_get_headings_bestmanagement($item, $withtemplate)
{
	global $LANG;
	
	switch (get_class($item))
	{
	  case "Profile" :
		if ($item->fields["interface"]!="helpdesk")
			return array(1 => $LANG["bestmanagement"]["title"][0]);
		break;

		
	  case TRACKING_TYPE:
		if ($item->fields["id"] != null && plugin_bestmanagement_haveRight("bestmanagement","linkticketcontrat", 1))
			return array(1 => $LANG["bestmanagement"]["config"]["linkticketcontrat"]);
		break;
	
	  case CONTRACT_TYPE:
		// template case
		if ($withtemplate)
			return array();
		else // Non template case
			return array(1 => $LANG["bestmanagement"]["title"][0]);
		break;
   }
   return false;
   
} // plugin_get_headings_bestmanagement()

// Define headings actions added by the plugin
function plugin_headings_actions_bestmanagement($item)
{
	switch (get_class($item))
	{
	  case "Profile" :
		if ($item->getField("interface")=="central")
            return array(1 => "plugin_headings_bestmanagement");
		break;

	  case CONTRACT_TYPE:
	  case TRACKING_TYPE:
		return array(1 => "plugin_headings_bestmanagement");
		break;
	}
	return false;
} // plugin_headings_actions_bestmanagement()

// Example of an action heading
function plugin_headings_bestmanagement($item, $withtemplate=0)
{
	global $LANG, $CFG_GLPI;

	if (!$withtemplate)
	{
		echo "<div class='center'>";
		switch (get_class($item))
		{
		  case "Profile" :
			$prof = new PluginBestmanagementProfile();
			$prof->updatePluginRights();
			$id = $item->getField("id");
			if (!$prof->getFromDB($id))
				$prof->createaccess($id);
			 
			$prof->showForm($id,
							 array("target" => $CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/profile.form.php"));
			break;

		  case CONTRACT_TYPE :
			plugin_bestmanagement_fichecontrat($item->fields["id"]);
			break;
		
		  case TRACKING_TYPE :
			$ticket = new PluginBestmanagementTicket($item->fields["id"]);
			$ticket->formLinkContrat();
			break;
	  }
	  echo "</div>";
	}
} // plugin_headings_bestmanagement()


///////////////////////////////////////////////
////// SPECIFIC MODIF MASSIVE FUNCTIONS ///////
///////////////////////////////////////////////

// Define actions :
function plugin_bestmanagement_MassiveActions($type)
{
	global $LANG;

	switch ($type)
	{
	  case TRACKING_TYPE :
		return array("plugin_bestmanagement_generatePDF" => $LANG["bestmanagement"]["pdf"][0]);
	}
	return array();

} // plugin_bestmanagement_MassiveActions()

// How to display specific actions ?
// options contain at least itemtype and and action
function plugin_bestmanagement_MassiveActionsDisplay($options=array())
{
	global $LANG;
	
	switch ($options['itemtype'])
	{
	  case TRACKING_TYPE :
		switch ($options['action'])
		{
		  case "plugin_bestmanagement_generatePDF" :
			echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".$LANG["buttons"][2]."'>";
			break;
		}
		break;
	}
} // plugin_bestmanagement_MassiveActionsDisplay()

// How to process specific actions ?
function plugin_bestmanagement_MassiveActionsProcess($data)
{
	global $LANG, $DB;
	
	switch ($data['action'])
	{
	  case 'plugin_bestmanagement_generatePDF' :
	  case 'plugin_bestmanagement_generatePDF2' :
	  case 'plugin_bestmanagement_generatePDF3' :
		if ($data['itemtype'] == TRACKING_TYPE)
		{
			$tabIDTickets = array_keys($data["item"]);

			sort($tabIDTickets);
			$trackID = "(";
			foreach($tabIDTickets as $i)
				$trackID .= $i . ",";
				
			$trackID = substr($trackID, 0, -1);	// pour enlever la virgule � la fin
			$trackID .= ")";

			// On s�lectionne les ID des contrats des tickets
			$query =   "SELECT distinct ID_Contrat CtrID
						FROM glpi_plugin_bestmanagement_link_ticketcontrat
						WHERE ID_Ticket IN " . $trackID;
			
			$nbcontrat=0;
			if($result = $DB->query($query))
				if($DB->numrows($result) > 0)
					while ($row = $DB->fetch_assoc($result))
						++$nbcontrat;
			
			if ($nbcontrat <= 2)	// on v�rifie qu'il y ait au maximum le contrat + du hors contrat
			{
				$_SESSION["bestmanagement"]["TabID"] = $tabIDTickets;
				// g�nere et ouvre le PDF
				echo "<script type='text/javascript'>location.href='../plugins/bestmanagement/front/export.massive.php'</script>";
			}
			else
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][25], false, ERROR);
		}
		break;
	}
} // plugin_bestmanagement_MassiveActionsProcess()


// Installation des tables du plugin
// Retourne vrai si tout s'est bien pass�
function plugin_bestmanagement_install()
{
	global $DB, $LANG;
	
	if (!TableExists("glpi_plugin_bestmanagement_achat"))
	{
		$query = "CREATE TABLE `glpi_plugin_bestmanagement_achat` (
				  `id` int(11) NOT NULL auto_increment,
				  `ID_Contrat` int(11) NOT NULL,
				  `date_deb` DATE NOT NULL,
				  `Type_Compteur` VARCHAR(255) NOT NULL,
				  `ID_Compteur` int(11) NULL,
				  `Type_Unit` VARCHAR(255) NULL,
				  `UnitBought` FLOAT NULL,
				  `avenant` TINYINT NULL,
				  `etat_fact` TINYINT NULL,
				  `num_fact_api` VARCHAR(255) NULL,
				  `comments` VARCHAR(255) NULL,
				  `date_save` DATETIME,
				PRIMARY KEY (`id`)
			   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

		$DB->query($query) or die("error creating glpi_plugin_bestmanagement_achat ". $DB->error());
	}
	
	if (!TableExists("glpi_plugin_bestmanagement_config"))
	{
	  $query = "CREATE TABLE `glpi_plugin_bestmanagement_config` (
				  `id` int(11) NOT NULL auto_increment,
				  `ticket_category` INT(1) NULL,
				  `time_creation` INT(1) NULL,
				  `date_deb` INT(1) NULL,
				  `duration` INT(1) NULL,
				  `contract_type` INT(1) NULL,
				  `task_category` INT(1) NULL,
				  `no_renewal` INT(1) NULL,
				  `color_priority` INT(1) NULL,
				  `ratiocontrat` VARCHAR(2) NULL,
				  `colormail` VARCHAR(255) NULL,
				  `destinataires` VARCHAR(255) NULL,
				PRIMARY KEY  (`id`)
			   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

	  $DB->query($query) or die("error creating glpi_plugin_bestmanagement_config". $DB->error());
	}
	
	$ins_config = "INSERT INTO glpi_plugin_bestmanagement_config
				  (id, ticket_category, time_creation, date_deb, duration,
				   contract_type, task_category, no_renewal, color_priority, ratiocontrat, colormail, destinataires)
				  VALUES (NULL, 0, 0, 0, 0, 0, 0, 0, 0, 20, 'blue', NULL)";

	$DB->query($ins_config) or die("error inserting glpi_plugin_bestmanagement_config". $DB->error());
	
	if (!TableExists("glpi_plugin_bestmanagement_facturation_ticket"))
	{
	  $query = "CREATE TABLE `glpi_plugin_bestmanagement_facturation_ticket` (
				  `ID_Ticket` int(11) NOT NULL,
				  `etat_fact` TINYINT,
				  `num_fact_api` VARCHAR(45),
				PRIMARY KEY  (`id_ticket`)
			   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

	  $DB->query($query) or die("error creating glpi_plugin_bestmanagement_facturation_ticket". $DB->error());
	}
	
	if (!TableExists("glpi_plugin_bestmanagement_historique"))
	{
	  $query = "CREATE TABLE `glpi_plugin_bestmanagement_historique` (
				  `id` int(11) NOT NULL auto_increment,
				  `ID_Contrat` int(11) NOT NULL,
				  `date_deb` DATE NOT NULL,
				  `duree` int(11) NOT NULL,
				  `Type_Compteur` VARCHAR(45) NULL,
				  `ID_Compteur` int(11) NULL,
				  `Type_Unit` VARCHAR(45) NULL,
				  `achat` FLOAT NOT NULL,
				  `report` FLOAT NOT NULL,
				  `conso` FLOAT NOT NULL,
				PRIMARY KEY  (`id`)
			   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

	  $DB->query($query) or die("error creating glpi_plugin_bestmanagement_historique". $DB->error());
	}
	
	if (!TableExists("glpi_plugin_bestmanagement_link_ticketcontrat"))
	{
		$query = "CREATE TABLE `glpi_plugin_bestmanagement_link_ticketcontrat` (
				  `id` int(11) NOT NULL auto_increment,
				  `ID_Ticket` int(11) NOT NULL,
				  `ID_Contrat` int(11) NULL,
				PRIMARY KEY (`id`)
			   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

		$DB->query($query) or die("error creating glpi_plugin_bestmanagement_link_ticketcontrat ". $DB->error());
	}
	
	if (!TableExists("glpi_plugin_bestmanagement_mailing"))
	{
	  $query = "CREATE TABLE `glpi_plugin_bestmanagement_mailing` (
				  `id` int(11) NOT NULL auto_increment,
				  `contratended` CHAR(1) NULL,
				  `contratending` CHAR(1) NULL,
				  `consoexceeded` CHAR(1) NULL,
				  `ratioexceeded` CHAR(1) NULL,
				PRIMARY KEY  (`id`)
			   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

	  $DB->query($query) or die("error creating glpi_plugin_bestmanagement_mailing". $DB->error());
	}
	
	$ins_mailing = "INSERT INTO glpi_plugin_bestmanagement_mailing
					(id, contratended, contratending, consoexceeded, ratioexceeded)
					VALUES (NULL, 1, 1, 1, 1)";

	$DB->query($ins_mailing) or die("error inserting glpi_plugin_bestmanagement_mailing". $DB->error());
	
	if (!TableExists("glpi_plugin_bestmanagement_pdf"))
	{
	  $query = "CREATE TABLE `glpi_plugin_bestmanagement_pdf` (
				  `id` int(11) NOT NULL auto_increment,
				  `entete` CHAR(1) NULL,
				  `logo` CHAR(1) NULL,
				  `titre` VARCHAR(255) NULL,
				  `auteur` VARCHAR(255) NULL,
				  `sujet` VARCHAR(255) NULL,
				  `adresse` text NULL,
				  `cp` VARCHAR(255) NULL,
				  `ville` VARCHAR(255) NULL,
				  `tel` VARCHAR(255) NULL,
				  `fax` VARCHAR(255) NULL,
				  `web` VARCHAR(255) NULL,
				  `mail` VARCHAR(255) NULL,
				  `footer` TEXT NULL,
				  `cgv` CHAR(1) NULL,
				PRIMARY KEY  (`id`)
			   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

	  $DB->query($query) or die("error creating glpi_plugin_bestmanagement_pdf". $DB->error());
	}

	// Valeurs par d�faut
	$query_ent_datas = "SELECT *
						FROM glpi_entitydatas
						WHERE id = 1";
	
	if($resultat = $DB->query($query_ent_datas))
		if($DB->numrows($resultat) > 0)
		{
			$row = $DB->fetch_assoc($resultat);
			
			foreach(array_keys($row) as $val)
				$row[$val] = mysql_real_escape_string($row[$val]);
			
			$ins_datas = "INSERT INTO glpi_plugin_bestmanagement_pdf
						  (id, entete, logo, titre, auteur, sujet,
						   adresse, cp, ville,
						   tel, fax, web, mail,
						   footer, cgv)
						  VALUES (NULL, 0, 0, '".mysql_real_escape_string($LANG["bestmanagement"]["pdf"][10])."', NULL, NULL,
						  '".$row["address"]."', '".$row["postcode"]."', '".$row["town"]."',
						  '".$row["phonenumber"]."', '".$row["fax"]."', '".$row["website"]."', '".$row["email"]."',
						  NULL, 0)";
						  
			$DB->query($ins_datas) or die("error inserting : $ins_datas". $DB->error());
			
			$query_email = "UPDATE glpi_plugin_bestmanagement_config
							SET destinataires = '" . $row["admin_email"] . "'";
							
			$DB->query($query_email) or die("error inserting : $query_email". $DB->error());
		}
	// Fin valeurs par d�faut

	if (!TableExists("glpi_plugin_bestmanagement_profiles"))
	{
		$query = "CREATE TABLE `glpi_plugin_bestmanagement_profiles` (
				   `id` int(11) NOT NULL auto_increment,
				   `profile` varchar(255) NOT NULL,
				   `recapglobal` CHAR(1) NULL,
				   `recapcontrat` CHAR(1) NULL,
				   `historicalpurchase` CHAR(1) NULL,
				   `historicalperiode` CHAR(1) NULL,
				   `addpurchase` CHAR(1) NULL,
				   `facturationcontrat` CHAR(1) NULL,
				   `renewal` CHAR(1) NULL,
				   `mailing` CHAR(1) NULL,
				   `linkticketcontrat` CHAR(1) NULL,
				   `facturationticket` CHAR(1) NULL,
				   `modifcontentmailing` CHAR(1) NULL,
					PRIMARY KEY (`id`)
			   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

		$DB->query($query) or die("error creating glpi_plugin_bestmanagement_profiles ". $DB->error());
	}
	
	// Valeurs par d�faut
	$query_prof = "SELECT `id`, `name`
                  FROM glpi_profiles";
				   
	$query_prof_plug = "SELECT `id`, `name`
                       FROM glpi_plugin_bestmanagement_profiles";
	
	$res = $DB->query($query_prof_plug);
	
	if($resultat = $DB->query($query_prof))
		if($DB->numrows($resultat) > 0)
			while ($row = $DB->fetch_assoc($resultat))
			{
			//	if (!$res) {	
			if($DB->numrows($res) > 0){
					while($row2 = $DB->fetch_assoc($res))
					{				
						$pre_query = "UPDATE glpi_plugin_bestmanagement_profiles SET ";
						
						if (in_array($row["name"], array("normal", "admin", "super-admin")))
						{
							$query_nasa = "recapglobal = '1',
										   recapcontrat = '1',
										   historicalpurchase = '1',
										   historicalperiode = '1'
										   WHERE profile IN ('normal', 'admin', 'super-admin')";
										   
							$DB->query($pre_query . $query_nasa) or die("erreur de la requete $query_nasa ". $DB->error());
						} // normal, admin, super-admin
						if (in_array($row["name"], array("admin", "super-admin")))
						{
							$query_asa = "addpurchase = '1',
										  facturationcontrat = '1',
										  renewal = '1',
										  mailing = '1',
										  linkticketcontrat = '1',
										  modifcontentmailing = '1'
										  WHERE profile IN ('admin', 'super-admin')";
							$DB->query($pre_query . $query_asa) or die("erreur de la requete $query_asa ". $DB->error());
						} // admin, super-admin
						if ($row["name"] == "super-admin")
						{
							$query_sa = "facturationticket = '1'
										 WHERE profile = 'super-admin'";
							$DB->query($pre_query . $query_sa) or die("erreur de la requete $query_sa ". $DB->error());
						} // super_admin
					}
				}
				else {
						$ins_profile = "INSERT INTO glpi_plugin_bestmanagement_profiles
							 (id, profile, recapglobal, recapcontrat, historicalpurchase, historicalperiode, addpurchase,
							 facturationcontrat, renewal, mailing, linkticketcontrat, facturationticket, modifcontentmailing)
							 VALUES (NULL, '".$row["name"]."', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)";
							 
							 $DB->query($ins_profile) or die("error inserting : $ins_profile". $DB->error());		                             						
				
						$pre_query = "UPDATE glpi_plugin_bestmanagement_profiles SET ";
						
						if (in_array($row["name"], array("normal", "admin", "super-admin")))
						{
							$query_nasa = "recapglobal = '1',
										   recapcontrat = '1',
										   historicalpurchase = '1',
										   historicalperiode = '1'
										   WHERE profile IN ('normal', 'admin', 'super-admin')";
										   
							$DB->query($pre_query . $query_nasa) or die("erreur de la requete $query_nasa ". $DB->error());
						} // normal, admin, super-admin
						if (in_array($row["name"], array("admin", "super-admin")))
						{
							$query_asa = "addpurchase = '1',
										  facturationcontrat = '1',
										  renewal = '1',
										  mailing = '1',
										  linkticketcontrat = '1',
										  modifcontentmailing = '1'
										  WHERE profile IN ('admin', 'super-admin')";
							$DB->query($pre_query . $query_asa) or die("erreur de la requete $query_asa ". $DB->error());
						} // admin, super-admin
						if ($row["name"] == "super-admin")
						{
							$query_sa = "facturationticket = '1'
										 WHERE profile = 'super-admin'";
							$DB->query($pre_query . $query_sa) or die("erreur de la requete $query_sa ". $DB->error());
						} // super_admin
				}
				
			} // while
	// Fin valeurs par d�faut
	
	if (!TableExists("glpi_plugin_bestmanagement_reconduction"))
	{
	  $query = "CREATE TABLE `glpi_plugin_bestmanagement_reconduction` (
				  `id` int(11) NOT NULL auto_increment,
				  `date_save` DATE NOT NULL,
				  `ID_Contrat` int(11) NOT NULL,
				  `begin_date` DATE,
				  `report_credit` int(11),
				PRIMARY KEY  (`id`)
			   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

	  $DB->query($query) or die("error creating glpi_plugin_bestmanagement_reconduction". $DB->error());
	}
	
	if (!TableExists("glpi_plugin_bestmanagement_report"))
	{
		$query = "CREATE TABLE `glpi_plugin_bestmanagement_report` (
				  `id` int(11) NOT NULL auto_increment,
				  `ID_Reconduction` int(11) NOT NULL,
				  `ID_Compteur` int(11) NOT NULL,
				  `Nb_Unit` FLOAT NOT NULL,
				PRIMARY KEY (`id`)
			   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

		$DB->query($query) or die("error creating glpi_plugin_bestmanagement_report ". $DB->error());
	}


	if (!TableExists("glpi_plugin_bestmanagement_typecontrat"))
	{
		$query = "CREATE TABLE `glpi_plugin_bestmanagement_typecontrat` (
				   `id` int(11) NOT NULL,
				   `illimite` int(11) NOT NULL,
					PRIMARY KEY (`id`)
			   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

		$DB->query($query) or die("error creating glpi_plugin_bestmanagement_typecontrat ". $DB->error());
	}
	
	// Valeurs par d�faut
	$query_prof = "SELECT id
				   FROM glpi_contracttypes";
				   
	$query_best_cont = " SELECT id 
						FROM glpi_bestmanagement_typecontrat"; 
	
	$res = $DB->query($query_best_cont); 
	
	if($resultat = $DB->query($query_prof) )
			if($DB->numrows($resultat) > 0)
				while ($row = $DB->fetch_assoc($resultat))
				{
					if (!$res) {
						while ($row_best = $DB->fetch_assoc($res)) 
						{
							if ( $row == $row_best )
							{	$ins_illim = "INSERT INTO glpi_plugin_bestmanagement_typecontrat
											(id, illimite)
											VALUES (" . $row["id"].", 1)";
								$DB->query($ins_illim) or die("error inserting : $ins_illim". $DB->error());
							}
						}
					}
					else {
					$ins_illim = "INSERT INTO glpi_plugin_bestmanagement_typecontrat
											(id, illimite)
											VALUES (" . $row["id"].", 1)";
								$DB->query($ins_illim) or die("error inserting : $ins_illim". $DB->error());
					}
				} // while
	// Fin valeurs par d�faut
	
	
	// To be called for each task the plugin manage
	CronTask::Register("PluginBestmanagementContrat", "Verif", WEEK_TIMESTAMP);
	CronTask::Register("PluginBestmanagementContrat", "SQL", MONTH_TIMESTAMP);
	return true;
	
} // plugin_bestmanagement_install()


// D�sinstallation des tables du plugin
// Retourne vrai si tout s'est bien pass�
function plugin_bestmanagement_uninstall()
{
	global $DB;

	$tables = array ("glpi_plugin_bestmanagement_achat",
					 "glpi_plugin_bestmanagement_config",
					 "glpi_plugin_bestmanagement_facturation_ticket",
					 "glpi_plugin_bestmanagement_historique",
					 "glpi_plugin_bestmanagement_link_ticketcontrat",
					 "glpi_plugin_bestmanagement_mailing",
					 "glpi_plugin_bestmanagement_pdf",
					 "glpi_plugin_bestmanagement_profiles",
					 "glpi_plugin_bestmanagement_reconduction",
					 "glpi_plugin_bestmanagement_report",
					 "glpi_plugin_bestmanagement_typecontrat");
	
	foreach ($tables as $var)
		if (TableExists($var))
		{
			$query = "DROP TABLE " . $var;
			$DB->query($query) or die("error deleting $var");
		}

	return true;
} // plugin_bestmanagement_uninstall()

?>