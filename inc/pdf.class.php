<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Classe PDF permattant de créer le contenu du
//				rapport d'intervention
// ----------------------------------------------------------------------

include_once (GLPI_ROOT . "/plugins/bestmanagement/inc/fpdf.class.php");

class PluginBestmanagementPDF extends FPDF
{
	private $titre;
	private $auteur;
	private $sujet;
	
	/**
	 * Accesseur
	**/
	function getItem ($item)
	{
		global $DB;
		
		$datas = "SELECT $item
				  FROM glpi_plugin_bestmanagement_pdf";
		
		if($result = $DB->query($datas))
			if ($row = $DB->fetch_assoc($result))
				return $row[$item];
		return "";
	} // getItem()
	
	/**
	 * Définit l'en-tête
	 *
	 * @return Nothing (display)
	**/
	function Header()
	{
		global $DB, $LANG;
		
		$datas = "SELECT *
				  FROM glpi_plugin_bestmanagement_pdf";
		
		if($result = $DB->query($datas))
			if($DB->numrows($result) > 0)
			{
				$row = $DB->fetch_assoc($result);
			
				if (!$row["entete"])
				{
					if (!$row["logo"] && file_exists(GLPI_ROOT . "/plugins/bestmanagement/pics/logo_pdf.jpg"))
					{
						$this->Image(GLPI_ROOT . "/plugins/bestmanagement/pics/logo_pdf.jpg",4,8,50);
						$espace = 45;
					}
					else
						$espace = 10;
					
					$this->SetFont('Arial','',8);
					$this->Cell($espace);
					$this->Cell($espace,3,utf8_decode($row["adresse"]), 0, 2);
					$this->Cell($espace,3,utf8_decode($row["cp"] . " " . $row["ville"]), 0, 2);
					$this->Cell($espace,3,utf8_decode($LANG["bestmanagement"]["pdf"][11] . " : " . $row["tel"]), 0, 2);
					$this->Cell($espace,3,utf8_decode($LANG["bestmanagement"]["pdf"][12] . " : " . $row["fax"]), 0, 2);
					$this->Cell($espace,3,utf8_decode($LANG["bestmanagement"]["pdf"][13] . " : " . $row["web"]), 0, 2);
					$this->Cell($espace,3,utf8_decode($LANG["bestmanagement"]["pdf"][14] . " : " . $row["mail"]), 0, 1);
					
					$this->Ln(5);
				}
			}
	} // Header()

	/**
	 * Définit le pied de page
	 *
	 * @return Nothing (display)
	**/
	function Footer()
	{
		global $LANG;
		
		global $DB, $LANG;
		
		$datas = "SELECT footer
				  FROM glpi_plugin_bestmanagement_pdf";
		
		if($result = $DB->query($datas))
			if($DB->numrows($result) > 0)
				$row = $DB->fetch_assoc($result);
		
		//Positionnement à 1,5 cm du bas
		$this->SetY(-15);
		$this->SetFont('Arial','I',8);
		
		$footer	= utf8_decode(str_replace("&euro;",'€',$row["footer"]));
		
		$this->MultiCell(0,3, $footer . "\n" .$LANG["bestmanagement"]["pdf"][16] . " " . $this->PageNo().' / {nb}',0,'C');
	} // Footer()
	
	/**
	 * Définit le titre de la page
	 *
	 * @return Nothing (display)
	**/
	function Titre($TabID)
	{
		global $DB, $LANG;

		$trackID = "(";
		foreach($TabID as $i)
			$trackID .= $i . ",";
			
		$trackID = substr($trackID, 0, -1);	// pour enlever la virgule à la fin
		$trackID .= ")";

		$this->Cell(10);
		
		$query =   "SELECT distinct ent.name as EntName, entdata.address as EntAddr, entdata.postcode as EntCP,
							entdata.town as EntTown, entdata.phonenumber as EntTel
					FROM glpi_tickets ticket
						LEFT JOIN glpi_entities ent
							ON ticket.entities_id = ent.ID
								LEFT JOIN glpi_entitydatas entdata
									ON ent.ID = entdata.entities_id
					WHERE ticket.ID IN " . $trackID;
		
		if($result = $DB->query($query))
			if($DB->numrows($result) > 0)
			{
				$row = $DB->fetch_assoc($result);

				// Client
				$this->SetFont('Arial','B',10);
				$this->Cell(0, 5, utf8_decode($row["EntName"]), 0, 1, 'R');
				$this->MultiCell(0,5, utf8_decode($row["EntAddr"]), 0, 'R');
				$this->Cell(0, 5, utf8_decode($row["EntCP"] . " " . $row["EntTown"]), 0, 1, 'R');
				$this->Cell(0, 5, utf8_decode($row["EntTel"]), 0, 1, 'R');
				$this->Ln(5);
				$this->SetFont('Arial','BU',12);
				$this->Cell(0, 5, utf8_decode($LANG["bestmanagement"]["pdf"][10]), 0, 1, 'C');
				$this->Ln(10);
				$this->Cell(80,0,"",0,1,0);
			}
	} // Titre()
	
	/**
	 * Définit la signature en fin de page
	 *
	 * @return Nothing (display)
	**/
	function Signature()
	{
		global $DB, $LANG;
		
		$this->CheckPageBreakWithoutCell(20);
		
		$intervenant	= (isset($_SESSION["glpifirstname"]) && isset($_SESSION["glpirealname"])) ?
								$_SESSION["glpifirstname"] . " " . $_SESSION["glpirealname"] : "";
		$group = "";
		if (isset($_SESSION["glpifirstname"]))
		{
			// requête pour récupérer le groupe de l'intervenant
			$query =   "SELECT groupe.name as GrpName
						FROM glpi_groups_users usergroup
							INNER JOIN glpi_groups groupe
								ON usergroup.groups_id = groupe.ID
						WHERE usergroup.users_id = " . $_SESSION["glpiID"];

			if($result = $DB->query($query))
				if($DB->numrows($result) > 0)
					while ($row = $DB->fetch_assoc($result))
						$group	= utf8_decode($row["GrpName"]);
		}
		$this->Ln(10);
		$this->SetFont('Arial','',10);
		$this->Cell(20);
		$this->Cell(0, 5, $LANG["bestmanagement"]["pdf"][17] . " : " . $intervenant, 0, 2);
		$this->Cell(0, 5, $LANG['common'][35] . " : " . $group);
		$this->Ln(15);
		$this->Cell(20);
		$this->Cell(100, 5, $LANG["bestmanagement"]["pdf"][18] . " :");
		$this->Cell(0, 5,	$LANG["bestmanagement"]["pdf"][19] . " :");
	} // Signature()
	
	/**
	 * Présentation du contrat et des tickets
	 *
	 * @return Nothing (display)
	**/
	function Present($TabID)
	{
		global $LANG;
		
		$DB = new DB;

		$trackID = "(";
		foreach($TabID as $i)
			$trackID .= $i . ",";
			
		$trackID = substr($trackID, 0, -1);	// pour enlever la virgule à la fin
		$trackID .= ")";
		
		$this->SetFont('Arial','',9);
		$this->Cell(10);
		$this->Cell(50,0,$LANG['common'][27] . " : " . date("d.m.Y"));	// date
		
		// requête pour récupérer le contrat
		$query =   "SELECT ID_Contrat
					FROM glpi_plugin_bestmanagement_link_ticketcontrat link
					WHERE link.ID_Ticket IN " . $trackID . "
						 AND ID_Contrat IS NOT NULL";
		
		if($result = $DB->query($query))
			if($DB->numrows($result) > 0)
			{
				while ($row = $DB->fetch_assoc($result))
					if ($row["ID_Contrat"] != null)
						$idcontrat	 = $row["ID_Contrat"];
		
				$contrat = new PluginBestmanagementContrat($idcontrat);
				$info_compteur = $contrat->infoCompteur();
				
			
				if ($contrat->name() != "" && (count($info_compteur) == 2))
				{
					$this->Cell(50,0,utf8_decode($LANG['financial'][1] . " : " . $contrat->name()),0,0,'C');
					$this->Cell(50,0,utf8_decode($LANG['financial'][4] . " : "),0,1,'R');
				
					$this->Ln(3);
					
					if (!$contrat->isContratIllim())                          //test si contrat est illimité ou non 
					{
						$tab_achat	= $contrat->prepareTab("achat");
						$tab_report	= $contrat->prepareTab("report");
						$tab_conso	= $contrat->prepareTabConso();
						$tab_restant= $contrat->prepareTabRestant();
						$this->Cell(10);
						$this->SetAligns('L');
						$this->SetDrawColor(255,255,255);
						$this->SetTextColor(0,0,0);
						
						$this->SetWidths(array(40,60,20,20, 20, 20));
						
						if ($info_compteur["compteur"] == "category")
							$unit = $LANG['common'][36];		// cat
						else
							$unit = $LANG['joblist'][2];		// prio
						
						
						$entete = array(utf8_decode($LANG["bestmanagement"]["allrecap"][12]), utf8_decode($unit));
						
						for($i = 2 ; $i < 6 ; $i++)
							array_push($entete, utf8_decode($LANG["bestmanagement"]["allrecap"][$i]));
						$this->Row($entete);
						$this->Cell(10);
						foreach(array_keys($tab_restant) as $key)
						{
							// vérifications pour savoir si les valeurs existent
							$tab_achat[$key]	= isset($tab_achat[$key])	? $tab_achat[$key]	: 0;
							$tab_report[$key]	= isset($tab_report[$key])	? $tab_report[$key]	: 0;
							$tab_conso[$key]	= isset($tab_conso[$key])	? $tab_conso[$key]	: 0;
							$tab_restant[$key]	= isset($tab_restant[$key])	? $tab_restant[$key]: 0;
							// fin vérification
							$Tab = array("");
							
							if ($tab_achat[$key] == 0 && $tab_report[$key] == 0 && $tab_conso[$key] == 0) continue;
							
							($tab_restant[$key] < 0) ? $this->SetTextColor(0,0,0) : $this->SetTextColor(0,0,0);
							
					
							array_push($Tab, utf8_decode($contrat->giveCompteurName($key, $info_compteur)));
							array_push($Tab, $contrat->arrangeIfHours($tab_achat[$key], $info_compteur["unit"]));
							array_push($Tab, $contrat->arrangeIfHours($tab_report[$key], $info_compteur["unit"]));
							array_push($Tab, $contrat->arrangeIfHours($tab_conso[$key], $info_compteur["unit"]));
							array_push($Tab, $contrat->arrangeIfHours($tab_restant[$key], $info_compteur["unit"]));
						

						$this->Row($Tab);
						$this->Cell(10);
					}	// foreach
					
					$this->Cell(10);
					$this->SetAligns('L');
					$this->SetDrawColor(255,255,255);
					$this->SetTextColor(0,0,0);
					$this->SetDrawColor(0,0,0);
					$this->SetTextColor(0,0,0);
					$this->Ln(4);
					}
					else {
						$tab_conso	= $contrat->prepareTabConso();
						$tab_restant= $contrat->prepareTabRestant();
						$this->Cell(10);
						$this->SetAligns('L');
						$this->SetDrawColor(255,255,255);
						$this->SetTextColor(0,0,0);
						
						$this->SetWidths(array(40,60,20,20, 20, 20));
						
						if ($info_compteur["compteur"] == "category")
							$unit = $LANG['common'][36];		// cat
						else
							$unit = $LANG['joblist'][2];		// prio
						
						
						$entete = array(utf8_decode($LANG["bestmanagement"]["allrecap"][12]), utf8_decode($unit));
						array_push($entete, utf8_decode($LANG["bestmanagement"]["allrecap"][4]));
						
						$this->Row($entete);
						$this->Cell(10);
						foreach(array_keys($tab_restant) as $key)
						{
							// vérifications pour savoir si les valeurs existent
							$tab_conso[$key]	= isset($tab_conso[$key])	? $tab_conso[$key]	: 0;
							// fin vérification
							$Tab = array("");
							
							if ($tab_conso[$key] == 0) continue;
							
							($tab_restant[$key] < 0) ? $this->SetTextColor(0,0,0) : $this->SetTextColor(0,0,0);
							
							array_push($Tab, utf8_decode($contrat->giveCompteurName($key, $info_compteur)));
							array_push($Tab, $contrat->arrangeIfHours($tab_conso[$key], $info_compteur["unit"]));
						

						$this->Row($Tab);
						$this->Cell(10);
					}	// foreach
					
					$this->Cell(10);
					$this->SetAligns('L');
					$this->SetDrawColor(255,255,255);
					$this->SetTextColor(0,0,0);
					$this->SetDrawColor(0,0,0);
					$this->SetTextColor(0,0,0);
					$this->Ln(4);
					
					
					} // fin isContratIllim
				} // fin présentation contrat
			}
		
		$tabConstraint = array("IS NOT NULL", "IS NULL");
		$i = 0;	// pour adapter l'affichage
		
		foreach($tabConstraint as $constraint)
		{
			// requête pour avoir le temps/nombre total de tous les tickets du rapport (ceux appartenant aux contrats)
			if (isset($info_compteur["unit"]) && $info_compteur["unit"] == "hour")
				$SELECT =   "SELECT SUM(actiontime) as TpsNb";
			else
				$SELECT =   "SELECT COUNT(*) as TpsNb";
			
			$query = "$SELECT
					  FROM glpi_tickets ticket
						INNER JOIN glpi_plugin_bestmanagement_link_ticketcontrat link
							ON link.ID_Ticket = ticket.id
					  WHERE ticket.id IN " . $trackID . "
						AND ID_Contrat $constraint";
			
			if($result = $DB->query($query))
				if($DB->numrows($result) > 0)
				{
					$row = $DB->fetch_assoc($result);
					$title = "";
					$time = "";
					if ($row["TpsNb"] != null)
					{
						$h = floor($row["TpsNb"]);				// nombre d'heures/de tickets
						
						if (isset($info_compteur["unit"]) && $info_compteur["unit"] == "hour")
						{
							if	($h == 0)	$h = "";
							else			$h .= "h";
							// $min = ($row["TpsNb"] - $h) * 60;
							// $min = round($min);						// nombre de minutes
							// if	($minute == 0)	$minute = "";
							// else			$minute .= "min";
							
							$units = Toolbox::getTimestampTimeUnits($row["TpsNb"]);
							$h   = $units['hour']+24*$units['day'];
							$minute = $units['minute'];
							if ($minute >= 0 && $minute < 10) $minute = "0" . $minute;
							
							$title = $LANG["bestmanagement"]["pdf"][20+$i];
							$time = $h . "h" . $minute;
							
						}
						else
						{
							if	($h == 0)	$h = "";
							else			$h .= " ticket";
							$h .= ($row["TpsNb"] > 1 ) ? "s" : "";
							$title = $LANG["bestmanagement"]["pdf"][21+$i];
							$time = $h;
						}
					}
					if ($time != "" && $time != " ")
					{
						$this->Cell(10);
						$this->Cell(15,0,utf8_decode($title . " : " .$time));		// à vérifier selon l'intervalle
						$this->Ln(5);
					}
				}
			$i += 2;
		} // for deux fois
		$this->Ln(5);
		
	} // Present()
	
	/**
	 * Tableau récapitulatif d'un ticket
	 * 
	 * @param $ID_tracking ID du ticket
	 *
	 * @return Nothing (display)
	**/
	function Ticket($ID_tracking)
	{
		global $LANG, $DB;
		
		// espacement, alignement, taille cellule
		$alinea = 5;
		$alinea2 = 10;
		$col1 = 17;
		$col2 = 17;
		$col3 = 27;
		$col4 = 38;
		$col5 = 86;
		$col6 = 130;
		//
		
		$this->SetFillColor(0,75,100);		// Cadre de couleur
		$this->SetTextColor(255,255,255);
		$this->SetAligns('L');
		$this->SetFont('Arial','',9);

		// requête pour avoir le titre et le temps total du ticket
		$query =   "SELECT name as Titre, actiontime as Duree, IFNULL(ID_Contrat, 'NULL') ID_Contrat
					FROM glpi_tickets ticket
						LEFT JOIN glpi_plugin_bestmanagement_link_ticketcontrat link
							ON ticket.id = link.ID_Ticket
					WHERE ticket.id = " . $ID_tracking;
		
		$titre		= "";
		$time		= "";
		$horscontrat= "";
		if($result = $DB->query($query))
			if($DB->numrows($result) > 0)
				while ($row = $DB->fetch_assoc($result))
				{
					$horscontrat = ($row["ID_Contrat"] != "NULL") ? ""
									: "(" . $LANG["bestmanagement"]["pdf"][15] . ")";
					
					$titre = $row["Titre"];
					// $h = floor($row["Duree"]);											// nombre d'heures
					// if	($h == 0)	$h = "";
					// else			$h .= "h";
					// $min = ($row["Duree"] - $h) * 60;
					// $min = round($min);													// nombre de minutes
					// if	($min == 0)	$min = "";
					// else			$min .= "min";
					
					$units = Toolbox::getTimestampTimeUnits($row["Duree"]);
					$h   = $units['hour']+24*$units['day'];
					$minute = $units['minute'];
					if ($minute >= 0 && $minute < 10) $minute = "0" . $minute;
					
					if	($h == 0)	$h = "";
					else			$h .= "h";
					
					if	($minute == 0)	$minute = "";
					else			$minute .= "min";
					
					// on adapte l'affichage
					$time = ($h+$minute == 0) ? "" : utf8_decode($LANG['job'][20] . " : $h $minute");					
				}
		
		$this->CheckPageBreakWithoutCell(50);
		
		$intitule = utf8_decode($LANG['job'][38] . " $ID_tracking $horscontrat : $titre");	// texte du bandeau
		
		// Ticket
		$this->Ln(0);
		$this->Cell(0, 5, $intitule , 1, 0, '', true);
		$this->Cell(0, 5, $time, 1, 1, 'R', true);
		$this->Ln(3);

		$this->SetFillColor(255,255,255);		// on défait le cadre de couleur
		// Description du ticket
		$this->Cell($alinea);
		$this->SetTextColor(0,0,0);
		$query =   "SELECT ticket.content as Description, ticket.status as Statut, cat.name as CatName
					FROM glpi_tickets ticket
						LEFT JOIN glpi_itilcategories cat
							ON ticket.itilcategories_id = cat.id
					WHERE ticket.id = " . $ID_tracking;

		if($result = $DB->query($query))
			if($DB->numrows($result) > 0)
				while ($row = $DB->fetch_assoc($result))
				{
					$desc	= $row["Description"];
					$cat	= ($row["CatName"] != null) ? $row["CatName"] : "(vide)";
					
					$Tab = array(utf8_decode($LANG['joblist'][6] . " : " . $desc),
								 "",
								 utf8_decode($LANG['joblist'][0] . " : " . Ticket::getStatus($row["Statut"])).
								 "\n" . utf8_decode($LANG['common'][36] . " : " . $cat));
					$this->SetDrawColor(255,255,255);
					$this->SetWidths(array(125,10,45));
					$this->Row($Tab);
					$this->Ln(3);
					$this->SetDrawColor(0,0,0);
				}
		
		$this->SetFillColor(192,192,192);		//Cadre de couleur gris clair
		$this->SetFont('Arial','',7);			//Police Arial 7

		/*
			Construit une requête à partir de l'identifiant du ticket ($ID_tracking).
			Récupère pour toutes les taches :
			date, durée, planification éventuelle, catégorie, auteur et description.
		*/
		$query =   "SELECT task.date as DateF, task.content as Description, task.actiontime as Duree,
							user.realname as Nom, user.firstname as Prenom,
							userplan.realname as PlanNom, userplan.firstname as PlanPrenom,
							task.begin as PlanBegin, task.end as PlanEnd, task.state as Statut,
							taskcat.name as CatName
					FROM glpi_tickettasks task
						INNER JOIN glpi_tickets ticket
							ON task.tickets_id = ticket.id
								INNER JOIN glpi_users user
									ON task.users_id = user.id									
												LEFT JOIN glpi_users userplan
													ON task.users_id_tech = userplan.id
														LEFT JOIN glpi_taskcategories taskcat
															ON task.taskcategories_id = taskcat.id
					WHERE task.tickets_id = " . $ID_tracking . "
						AND task.is_private = 0";

		if($result = $DB->query($query))
			if($DB->numrows($result) > 0)
			{
				$this->Cell($alinea);
				$this->Cell($col1, 5, utf8_decode($LANG['common'][27])	, 1, 0, 'C', true);	// date
				$this->Cell($col2, 5, utf8_decode($LANG['job'][31]), 1, 0, 'C', true);	// durée
				$this->Cell($col3, 5, utf8_decode($LANG['common'][37])	, 1, 0, 'C', true);	// auteur
				$this->Cell($col4, 5, utf8_decode($LANG['common'][36])	, 1, 0, 'C', true);	// catégorie
				$this->Cell($col5, 5, utf8_decode($LANG['job'][35])		, 1, 0, 'C', true);	// planification
				$this->Ln(6);
				
				while ($row = $DB->fetch_assoc($result))
				{
					$Tab	= array();
					$date	= substr($row["DateF"],0,10);
					$date	= explode('-',$date);
					$date	= $date[2] . '-' . $date[1] . '-' . $date[0];		// date au format JJ-MM-AAAA
					array_push($Tab, $date);

					$this->Cell(5);

					// $h = floor($row["Duree"]);									// nombre d'heures
					// if	($h == 0)	$h = "";
					// else			$h .= "h";
					// $min = ($row["Duree"] - $h) * 60;
					// $min = round($min);											// nombre de minutes
					// if	($min == 0)	$min = "";
					// else			$min .= "min";
					
					$units = Toolbox::getTimestampTimeUnits($row["Duree"]);
					$h   = $units['hour']+24*$units['day'];
					$minute = $units['minute'];
					if ($minute >= 0 && $minute < 10) $minute = "0" . $minute;
					
					if	($h == 0)	$h = "";
					else			$h .= "h";
					
					if	($minute == 0)	$minute = "";
					else			$minute .= "min";
					
					
					array_push($Tab, $h . " " . $minute);
					array_push($Tab, utf8_decode($row["Prenom"] . " " . $row["Nom"]));
					array_push($Tab, utf8_decode($row["CatName"]));
					
					if (isset($row["PlanBegin"]) || isset($row["PlanEnd"]))
					{
						switch ($row["Statut"])
						{
							case 0 :
								$statut = $LANG["planning"][16];
								break;
							case 1 :
								$statut = $LANG["planning"][17];
								break;
							case 2 :
								$statut = $LANG["planning"][18];
								break;
							default :
								$statut = "";
						}
						$planif = $statut . " / " . $row["PlanBegin"] . " -> " . $row["PlanEnd"] . " " . $row["PlanPrenom"] . " " . $row["PlanNom"];
						array_push($Tab, utf8_decode($planif));
					}
					else
						array_push($Tab, "-");
						
					$this->SetWidths(array($col1,$col2,$col3,$col4,$col5));
					$this->Row($Tab);
					
					$desc	= utf8_decode($row["Description"]);
					$desc	= str_replace("&gt;",'>',$desc);
					
					$this->SetWidths(array($col1+$col2+$col3+$col4+$col5));
					$this->Cell(5);
					$this->MultiCell(0,5, $desc, 1);
					$this->Ln(2);
				} // while
			}
			
			$this->Ln(10);
		/*
			Construit une requête à partir de l'identifiant du ticket ($ID_tracking).
			Récupère pour tous les suivis:
			date, catégorie, auteur et description.
		*/
			
			$query = " SELECT follow.date AS Date, follow.content AS Description, user.realname AS Nom,
								user.firstname AS Prenom
						FROM glpi_ticketfollowups follow
							INNER JOIN glpi_tickets ticket 
								ON follow.tickets_id = ticket.id
							INNER JOIN glpi_users user 
								ON follow.users_id = user.id
							LEFT JOIN glpi_requesttypes request 
								ON follow.requesttypes_id = request.id
						WHERE follow.tickets_id = " . $ID_tracking . "
						AND follow.is_private =0 ";
			
			
		if($result = $DB->query($query))
			if($DB->numrows($result) > 0)
			{
				$this->Cell($alinea);
				$this->Cell($col1, 5, utf8_decode($LANG['common'][27])	, 1, 0, 'C', true);	// date
				$this->Cell($col4, 5, utf8_decode($LANG['common'][37])	, 1, 0, 'C', true);	// auteur
				$this->Cell($col6, 5, utf8_decode($LANG['joblist'][6])	, 1, 0, 'C', true);	// description
				$this->Ln(6);
				
				while ($row = $DB->fetch_assoc($result))
				{
					$Tab	= array();
					$date	= substr($row["Date"],0,10);
					$date	= explode('-',$date);
					$date	= $date[2] . '-' . $date[1] . '-' . $date[0];		// date au format JJ-MM-AAAA
					array_push($Tab, $date);

					$this->Cell(5);
					
					array_push($Tab, utf8_decode($row["Prenom"] . " " . $row["Nom"]));  //ecriture nom et prenom dans tableau
					
					$desc	= utf8_decode($row["Description"]);
					$desc	= str_replace("&gt;",'>',$desc);
					
					array_push($Tab,$desc);										//ecriture de la description dans tableau
					
					$this->SetWidths(array($col1,$col4,$col6));
					$this->Row($Tab);
				}
			}
					
		$this->Ln(10);
	} // Ticket().
	
	/**
	 * Créé un tableau des largeurs de colonnes
	 * 
	 * @param $w largeur de colonne
	 *
	 * @return Nothing
	**/
	function SetWidths($w)
	{
		$this->widths=$w;
	} // SetWidths()
	
	
	function SetAligns($a)
	{
		//Tableau des alignements de colonnes
		$this->aligns=$a;
	} // SetAligns()
	
	function Row($data)
	{
		//Calcule la hauteur de la ligne
		$nb=0;
		for($i=0;$i<count($data);$i++)
			$nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
		$h=5*$nb;
		//Effectue un saut de page si nécessaire
		$this->CheckPageBreak($h);
		//Dessine les cellules
		for($i=0;$i<count($data);$i++)
		{
			$w=$this->widths[$i];
			$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'C';	// on centre les données
			//Sauve la position courante
			$x=$this->GetX();
			$y=$this->GetY();
			//Dessine le cadre
			$this->Rect($x,$y,$w,$h);
			//Imprime le texte
			$this->MultiCell($w,5,$data[$i],0,$a);
			//Repositionne à droite
			$this->SetXY($x+$w,$y);
		}
		//Va à la ligne
		$this->Ln($h);
	} // Row()
	
	/**
	 * Vérifie s'il faut sauter une page
	 * 
	 * @param $h hauteur de celulle
	 *
	 * @return Nothing
	**/
	function CheckPageBreak($h)
	{
		//Si la hauteur h provoque un débordement, saut de page manuel
		if($this->GetY()+$h>$this->PageBreakTrigger)
		{
			$this->AddPage($this->CurOrientation);
			$this->Cell(5);
		}
	} // CheckPageBreak()
	
	function CheckPageBreakWithoutCell($h)
	{
		//Si la hauteur h provoque un débordement, saut de page manuel
		if($this->GetY()+$h>$this->PageBreakTrigger)
			$this->AddPage($this->CurOrientation);
	} // CheckPageBreakWithoutCell()
	
	/**
	 * Calcule le nombre de lignes qu'occupe un MultiCell
	 * 
	 * @param $w largeur de colonne
	 *
	 * @return Nothing
	**/
	function NbLines($w,$txt)
	{
		$cw=&$this->CurrentFont['cw'];
		if($w==0)
			$w=$this->w-$this->rMargin-$this->x;
		$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
		$s=str_replace("\r",'',$txt);
		$nb=strlen($s);
		if($nb>0 and $s[$nb-1]=="\n")
			$nb--;
		$sep=-1;
		$i=0;
		$j=0;
		$l=0;
		$nl=1;
		while($i<$nb)
		{
			$c=$s[$i];
			if($c=="\n")
			{
				$i++;
				$sep=-1;
				$j=$i;
				$l=0;
				$nl++;
				continue;
			}
			if($c==' ')
				$sep=$i;
			$l+=$cw[$c];
			if($l>$wmax)
			{
				if($sep==-1)
				{
					if($i==$j)
						$i++;
				}
				else
					$i=$sep+1;
				$sep=-1;
				$j=$i;
				$l=0;
				$nl++;
			}
			else
				$i++;
		}
		return $nl;
	} // NbLines()
	
	// ouvre et lit le fichier .txt
	function CGV($file="../files/cgv.txt")
	{
		if (integrerCGV() && $fp = fopen($file,"r"))
		{
			$this->AddPage($this->CurOrientation);
			$this->Ln(10);
		
			$titre = true;
			$derniere_ligne = $this->lastLine($file);
			while(!feof($fp))
			{
				// On récupère une ligne
				$Ligne = fgets($fp);
				
				$align = 'J';
				if ($titre)								// Titre
				{
					$this->SetFont('Arial','B',14);
					$titre = false;
					$align = 'C';
				}
				else if ($Ligne == strtoupper($Ligne))	// Sous-titre
					$this->SetFont('Arial','B',9);
				else if ($Ligne == $derniere_ligne)		// dernière ligne
					$this->SetFont('Arial','',10);
				else
					$this->SetFont('Arial','',7);
				$this->MultiCell(0,3,"$Ligne", 0, $align);
			}
			
			fclose($fp); // On ferme le fichier
		}
	} // CGV()

	function lastLine($file)
	{
		$fp = @fopen($file, "r");
		$pos = -1;
		$t = " ";
		while ($t != "\n") {
			  fseek($fp, $pos, SEEK_END);
			  $t = fgetc($fp);
			  $pos = $pos - 1;
		}
		$t = fgets($fp);
		fclose($fp);
		return $t;
	} // lastLine()

	/**
	 * Génère le fichier .pdf
	 * 
	 * @param $TabID : tableau des id des tickets
	 *
	 * @return Nothing(Display pdf file)
	**/
	function generatePDF($TabID)
	{
		global $DB;
		
		// Propriétés du document
		$this->SetTitle		($this->getItem("titre"));
		$this->SetAuthor	($this->getItem("auteur"));
		$this->SetSubject	($this->getItem("sujet"));
		
		$this->AliasNbPages();
		$this->AddPage();
		$this->Titre($TabID);				// Nom du client et titre de la page
		$this->Present($TabID);				// Présentation du contrat
		$this->SetFont('Times','',7);		// Police Times 7
		
		foreach ($TabID as $i)
			$this->Ticket($i);
		
		$this->Signature();
		$this->CGV();
		
		$this->Output();	
	} // generatePDF()

} // class PluginBestmanagementPDF
?>