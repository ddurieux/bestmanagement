<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file:
// ----------------------------------------------------------------------

define("GLPI_ROOT", "../../..");
include (GLPI_ROOT . "/inc/includes.php");
include (GLPI_ROOT . "/plugins/bestmanagement/inc/contrat.class.php");

global $DB;

if (isset($_POST["addSort"]))
{
	$id_contrat	= $_POST["id_contrat"];
	$compteur	= $_POST["compteur"];
	$unit		= $_POST["unit"];
	$date		= $_SESSION["glpi_currenttime"];
	
	if ($compteur == "category" && $unit == "nbtickets")
		Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][21], false, ERROR);
	else
	{
		$query =   "SELECT begin_date
					FROM glpi_contracts
					WHERE id = $id_contrat";
		
		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				$row = $DB->fetch_assoc($resultat);
		$date_deb = $row["begin_date"];
		
		// on supprime l'ancienne valeur si existante (même contrat, même date de début)
		$query =	"DELETE FROM glpi_plugin_bestmanagement_achat
					 WHERE Id_Contrat = $id_contrat
						AND date_deb = '$date_deb'";
		$DB->query($query);
		
		// et on insère le nouveau compteur
		$query = "INSERT INTO glpi_plugin_bestmanagement_achat
				 (id, ID_Contrat, date_deb, Type_Compteur, ID_Compteur,
				  Type_Unit, UnitBought, avenant, etat_fact, num_fact_api, comments, date_save)
				  VALUES (NULL, $id_contrat, '$date_deb', '$compteur', NULL, '$unit', NULL, 0, 0, NULL, NULL, '$date')";
		$DB->query($query) or die("erreur de la requete $query ". $DB->error());
	}
}
else if (isset($_POST["addPurchase"]))
{
	if (isset($_POST["id_contrat"])	&& isset($_POST["NbUnit"]))
	{
		$id	= $_POST["id_contrat"];

		$contrat = new PluginBestmanagementContrat($id);
		$date_deb = $contrat->dateDeb();

		$info_compteur = $contrat->infoCompteur();
		
		$compteur	= $info_compteur["compteur"];
		if ($compteur == "category")
			$id_compteur	= $_POST["taskcategories_id"];
		else
			$id_compteur	= $_POST["priority"];
		
		$unit		= $info_compteur["unit"];
		$nb_unit	= $_POST["NbUnit"];
		
		$avenant	= (isset($_POST["Avenant"]))	? 0 : 1;	// 0 = avenant
		
		$num_fact	= (isset($_POST["NumFact"]) && $_POST["NumFact"] != ""
							&& $_POST["NumFact"] != $LANG["bestmanagement"]["facturation_contrat"][3])
						? $_POST["NumFact"] : "-";
						
						
		$etat_fact	= (isset($_POST["id_facturation"]))	? $_POST["id_facturation"] : 1;	// 1 = non facturé
		
		$comments	= (isset($_POST["Comments"]) && $_POST["Comments"] != $LANG["common"][25])
							? $_POST["Comments"] : "";
		
		$date		= $_SESSION["glpi_currenttime"];
		
		if (!$id_compteur)	// compteur vide
			Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][14], false, ERROR);
		else if ($_POST["NbUnit"] == null)	// nombre d'unités vide
			Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][15], false, ERROR);
		else if (!is_numeric($_POST["NbUnit"]))
			Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][18], false, ERROR);
		else
		{ // si tout est ok on insère l'achat
			$query = "INSERT INTO glpi_plugin_bestmanagement_achat
					  (id, ID_Contrat, date_deb, Type_Compteur, ID_Compteur,
					  Type_Unit, UnitBought, avenant, etat_fact, num_fact_api, comments, date_save)
					  VALUES (NULL, $id, '$date_deb', '$compteur', $id_compteur,
							 '$unit', $nb_unit, $avenant, $etat_fact, '$num_fact', '$comments', '$date')";
			$DB->query($query) or die("erreur de la requete $query ". $DB->error());
			Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][17], false, INFO);
		}
	}
	else
		Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][18], false, INFO);
}
else if (isset($_POST["addRenewal"]))
{
	//////////////////////////////////////////////
	// Processus de reconduction d'un contrat :	//
	// Insertion dans :							//
	// - reconduction							//
	// - report (facultatif)					//
	// - historique								//
	// - logs									//
	// - contracts								//
	// - achat									//
	//////////////////////////////////////////////

	$id = $_POST["id_contrat"];

	$contrat = new PluginBestmanagementContrat($id);
	$begin_date		= $contrat->dateDeb();
	$duree			= $contrat->duree();
	$new_begin_date	= $contrat->dateFin();
	
	$report = 0;
	
	if (!isset($_POST["report"])) // il n'y a pas report des unités
		$report = 1;

  //-------------------------------------------------
	// Préparation des requêtes
	// Les tableaux sont indexés selon l'ID du compteur
	//-------------------------------------------------
	$tab_achat	= $contrat->prepareTab("achat");
	$tab_report	= $contrat->prepareTab("report");
	$tab_conso	= $contrat->prepareTabConso();
	$tab_restant= $contrat->prepareTabRestant();

	//----------------------------
	// insertion dans reconduction
	//----------------------------
	$query_reconduction =	"INSERT INTO glpi_plugin_bestmanagement_reconduction
							(id, date_save, ID_Contrat, begin_date, report_credit)
							VALUES (NULL, '".$_SESSION["glpi_currenttime"]."', $id, '$begin_date', $report)";

	if ($DB->query($query_reconduction))
		$id_reconduction = $DB->insert_id();
	
	if ($report == 0)	// il y a report
	{
		foreach(array_keys($tab_restant) as $key)
		{
			//----------------------
			// insertion dans report
			//----------------------
			$query_report = "INSERT INTO glpi_plugin_bestmanagement_report
							(id, ID_Reconduction, ID_Compteur, Nb_Unit)
							VALUES (NULL, $id_reconduction, $key, $tab_restant[$key])";
			$DB->query($query_report) or die("erreur de la requete $query_report ". $DB->error());
		}
	}

	$info_compteur = $contrat->infoCompteur();
	$compteur	= $info_compteur["compteur"];
	$unit		= $info_compteur["unit"];

	// remplissage des lignes du tableau
	foreach(array_keys($tab_restant) as $key)
	{
		// vérifications pour savoir si les valeurs existent
		$achat	= (isset($tab_achat[$key])	&& !$contrat->isContratIllim()) ? $tab_achat[$key]	: 0;
		$report	= (isset($tab_report[$key])	&& !$contrat->isContratIllim()) ? $tab_report[$key]	: 0;
		$conso	= isset($tab_conso[$key])	? $tab_conso[$key]	: 0;
		// fin vérification

		// s'il n'y a ni heure achetée, reportée ou consommée on n'insère pas la ligne
		if ($achat == 0 && $report == 0 && $conso == 0) continue;

		//--------------------------
		// insertion dans historique
		//--------------------------
		$query_historique = "INSERT INTO glpi_plugin_bestmanagement_historique
							 (id, ID_Contrat, date_deb, duree, Type_Compteur,
							  ID_Compteur, Type_Unit, achat, report, conso)
							 VALUES (NULL, $id, '$begin_date', $duree, '$compteur',
									 $key, '$unit', $achat, $report, $conso)";
		$DB->query($query_historique) or die("erreur de la requete $query_report ". $DB->error());
	}
	
	//--------------------
	// insertion dans logs
	//--------------------
	$query_logs = "INSERT INTO glpi_logs
				  (id, itemtype, items_id, itemtype_link, linked_action, 
				   user_name, date_mod, id_search_option, old_value, new_value)
				   VALUES (NULL, 'Contract', $id, ' ', ' ',
						  'GLPI', '".$_SESSION["glpi_currenttime"]."', 5, '$begin_date', '$new_begin_date')";
	$DB->query($query_logs) or die("erreur de la requete $query_report ". $DB->error());

	//--------------------------
	// mise à jout date de début
	// dans la table contracts
	//--------------------------
	$query_contrat = "UPDATE glpi_contracts
					  SET begin_date = '$new_begin_date'
					  WHERE id = $id";
	$DB->query($query_contrat) or die("erreur de la requete $query_report ". $DB->error());

	//--------------------------------
	// insertion dans achat
	// (prise en compte des avenants)
	// remise à zéro de la facturation
	//--------------------------------
	$query_old_achat = "SELECT Type_Compteur, IFNULL(ID_Compteur,'NULL') ID_Compteur, Type_Unit, IFNULL(SUM(UnitBought),'NULL') UnitReconduit
						FROM glpi_plugin_bestmanagement_achat
						WHERE ID_Contrat = $id
							AND date_deb = '$begin_date'
							AND avenant = 0
						GROUP BY Type_Compteur, ID_Compteur, Type_Unit";
	
	if($resultat = $DB->query($query_old_achat))
		if($DB->numrows($resultat) > 0)
			while ($row = $DB->fetch_assoc($resultat))
			{
				$compteur	= $row["Type_Compteur"];
				$unit		= $row["Type_Unit"];
				$report		= $row["UnitReconduit"];
				$idcompteur	= $row["ID_Compteur"];
				
				$query_achat = "INSERT INTO glpi_plugin_bestmanagement_achat
								(id, ID_Contrat, date_deb, Type_Compteur, ID_Compteur,
								Type_Unit, UnitBought, avenant, etat_fact, comments, date_save)
								VALUES (NULL, $id, '$new_begin_date', '$compteur', $idcompteur,
								'$unit', $report, 0, 1, 'Reconduction', '$new_begin_date');";
				$DB->query($query_achat) or die("erreur de la requete $query_achat ". $DB->error());
			}
}
else if (isset($_POST["deleteContrat"]))
{
	$id = $_POST["id_contrat"];
	
	$delete_contrat = "UPDATE glpi_contracts
					   SET is_deleted = 1
					   WHERE id = $id";
					   
	$DB->query($delete_contrat) or die("erreur de la requete $delete_contrat ". $DB->error());

	//--------------------
	// insertion dans logs
	//--------------------
	$query_logs = "INSERT INTO glpi_logs
				  (id, itemtype, items_id, itemtype_link, linked_action, 
				   user_name, date_mod, id_search_option, old_value, new_value)
				   VALUES (NULL, 'Contract', $id, ' ', 13,
						  'GLPI', '".$_SESSION["glpi_currenttime"]."', 0, '', '')";
	$DB->query($query_logs) or die("erreur de la requete $query_report ". $DB->error());
	
}
else if (isset($_POST["addFacturation"]))
{
	//////////////////////////////////////////////
	// Mise à jour dans la table achat :		//
	// - attribut etat_fact	à 0					//
	// - attribut num_fact_api					//
	//////////////////////////////////////////////
	
	$num_fact	= (isset($_POST["NumFact"]) && $_POST["NumFact"] != ""
						&& $_POST["NumFact"] != $LANG["bestmanagement"]["facturation_contrat"][3])
					? $_POST["NumFact"] : "-";
		
	
	foreach(array_keys($_POST) as $key)
	{
		if (strlen($key) <= 7 ||substr($key, 0, 7) != "CBFact_")
			continue;
			
		$id = substr($key, 7);	
		
		$query_facturation =	"UPDATE glpi_plugin_bestmanagement_achat
								 SET etat_fact = 0,
									num_fact_api = '$num_fact'
								WHERE id = $id";
		
		$DB->query($query_facturation) or die("erreur de la requete $query_facturation ". $DB->error());
	}
}
else if (isset($_POST["deletePurchase"]))
{
	print_r($_POST);
	exit;
	/*
	$id_achat = $_POST["id_achat"];

	print_r($_POST); exit;
	
	
	$query =	"DELETE FROM glpi_plugin_bestmanagement_achat
				 WHERE id = $id_achat";
	$DB->query($query);
	*/
}
else if (isset($_POST["link_ticketcontrat"]))
{
	foreach(array_keys($_POST) as $key)
	{
		if (strlen($key) <= 7 ||substr($key, 0, 7) != "ticket_")
			continue;
			
		$id = substr($key, 7);

		if (isset($_POST["contrat_select"]) && $_POST["contrat_select"] != -1)
		{
			$num_ticket	= $id;
			$num_contrat= $_POST["contrat_select"];
			
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
			
			// cas où le contrat a changé
			if (isset($old_contrat))
			{
				$query = "UPDATE glpi_plugin_bestmanagement_link_ticketcontrat
						  SET ID_Contrat = $num_contrat
						  WHERE ID_Ticket = $num_ticket";
				
				$DB->query($query) or die("error $query");

				if (isset($_POST["id_facturation"]))	// on met à jour la facturation
				{
					$query_fact = "UPDATE glpi_plugin_bestmanagement_facturation_ticket
								   SET etat_fact = " . $_POST["id_facturation"] . ",
									   num_fact_api = '" . $_POST["NumFact"] . "'
								   WHERE ID_Ticket = $num_ticket";
					$DB->query($query_fact) or die("error $query_fact");
				}
			}
			else // il faut l'insérer
			{
				// si le ticket est en Hors Contrat, $num_contrat vaut NULL
				$values = "NULL, $num_ticket, $num_contrat";

				insertToDB("glpi_plugin_bestmanagement_link_ticketcontrat", $values);
				
				if (isset($_POST["id_facturation"]))	// on insère aussi la facturation si elle est présente				
				{
				  
				  $query_fact = "SELECT ID_Ticket  
                  FROM glpi_plugin_bestmanagement_facturation_ticket 								  
								   WHERE ID_Ticket = $num_ticket";
								   
						$nouvelle_fact = true;
								   
    		   if($res = $DB->query($query_fact)){    		      
           	  if($DB->numrows($res) > 0) 
           	       $nouvelle_fact = false;           	
           }
    		
				  if (!$nouvelle_fact){
				  // on a déjà une facturation présente => UPDATE
              	$query_fact = "UPDATE glpi_plugin_bestmanagement_facturation_ticket
						   SET etat_fact = " . $_POST["id_facturation"] . ",
							   num_fact_api = '" . $_POST["NumFact"] . "'
						   WHERE ID_Ticket = $num_ticket";
					     $DB->query($query_fact) or die("error $query_fact");
          }
          else{
				  // Nouvelle facturation => INSERT
				  
					 $values = "$num_ticket, " . $_POST["id_facturation"] . ",'" . $_POST["NumFact"] . "'";
					 insertToDB("glpi_plugin_bestmanagement_facturation_ticket", $values);
				  }
				}
				
				$href = "<a href=\"".Toolbox::getItemTypeFormURL("Ticket")."?id=$num_ticket\">($num_ticket)</a>";
				Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][5] . $href, false, INFO);
			}
		}
		else
			Session::addMessageAfterRedirect($LANG["bestmanagement"]["msg"][29], false, ERROR);
	} // foreach
}
else if (isset($_POST["ModifAPI"]))
{	
	$id = $_POST["id"];
	$num_fact = $_POST["num_fact_api"] != "" ? $_POST["num_fact_api"] : "-";

	$query_api = "UPDATE glpi_plugin_bestmanagement_achat
				  SET etat_fact = 0,
					  num_fact_api = '$num_fact'
				  WHERE id = $id";

	$DB->query($query_api) or die("erreur de la requete $query_api ". $DB->error());
	Html::redirect($_SERVER["HTTP_REFERER"]);
}
else if (isset($_POST["FacturationTicket"]))
{
	//////////////////////////////////////////////////////
	// Mise à jour dans la table facturation_ticket :	//
	// - attribut etat_fact								//
	// - attribut num_fact_api							//
	//////////////////////////////////////////////////////
	
	$num_fact	= (isset($_POST["NumFact"]) && $_POST["NumFact"] != ""
						&& $_POST["NumFact"] != $LANG["bestmanagement"]["facturation_contrat"][3])
					? $_POST["NumFact"] : "-";
	
	$etat_fact = $_POST["id_facturation"];
	
	foreach(array_keys($_POST) as $key)
	{
		if (strlen($key) <= 7 ||substr($key, 0, 7) != "ticket_")
			continue;
			
		$id = substr($key, 7);	
		
		$query_facturation =	"UPDATE glpi_plugin_bestmanagement_facturation_ticket
								 SET etat_fact = $etat_fact,
									num_fact_api = '$num_fact'
								WHERE ID_Ticket = $id";
	
		$DB->query($query_facturation) or die("erreur de la requete $query_facturation ". $DB->error());
	}
}
else if (isset($_POST["FacturationContrat"]))
{
	//////////////////////////////////////////////////////
	// Mise à jour dans la table facturation_ticket :	//
	// - attribut etat_fact								//
	// - attribut num_fact_api							//
	//////////////////////////////////////////////////////
	
	print_r($_POST); exit;
	
	$num_fact	= (isset($_POST["NumFact"]) && $_POST["NumFact"] != ""
						&& $_POST["NumFact"] != $LANG["bestmanagement"]["facturation_contrat"][3])
					? $_POST["NumFact"] : "-";
	
	$etat_fact = $_POST["id_facturation"];
	
	foreach(array_keys($_POST) as $key)
	{
		if (strlen($key) <= 7 ||substr($key, 0, 7) != "ticket_")
			continue;
			
		$id = substr($key, 7);	
		
		$query_facturation =	"UPDATE glpi_plugin_bestmanagement_facturation_ticket
								 SET etat_fact = $etat_fact,
									num_fact_api = '$num_fact'
								WHERE ID_Ticket = $id";
	
		$DB->query($query_facturation) or die("erreur de la requete $query_facturation ". $DB->error());
	}
}
else
	Html::redirect(GLPI_ROOT . "/index.php");
	
Html::redirect($_SERVER["HTTP_REFERER"]); // page de redirection;
?>