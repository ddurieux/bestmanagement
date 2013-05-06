<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Classe AllTickets servant pour l'affichage d'une
//				liste de plusieurs tickets
// ----------------------------------------------------------------------

class PluginBestmanagementAllTickets
{
	function __construct() {} // constructeur
	
	/**
	 * Affiche le listing des tickets
	 * 
	 * @return Nothing(Display)
	**/
	static function showForm($lesquels="whitoutcontrat", $ID=null)
	{
		global $DB, $CFG_GLPI, $LANG;
		
		switch ($lesquels)
		{
		  case "whitoutcontrat" :
			$query_tickets = "SELECT ticket.id ID, ticket.name Titre, ent.name Entite, ticket.status Statut,
									 ticket.priority Priorite, ticket.actiontime TempsTicket, ticket.date DateOuv,
									 cat.name CatName, ticket.urgency Urgence
							  FROM glpi_tickets ticket
								LEFT JOIN glpi_entities ent
									ON ticket.entities_id = ent.id
										LEFT JOIN glpi_itilcategories cat
											ON ticket.itilcategories_id = cat.id
							  WHERE ticket.id NOT IN (SELECT ID_Ticket
													  FROM glpi_plugin_bestmanagement_link_ticketcontrat) " .
								getEntitiesRestrictRequest("AND","ticket","entities_id","",false);
			break;
		  case "afacturer" :
			$query_tickets = "SELECT ticket.id ID, ticket.name Titre, ent.name Entite, ticket.status Statut,
									 ticket.priority Priorite, ticket.actiontime TempsTicket, ticket.date DateOuv,
									 cat.name CatName, ticket.urgency Urgence
							  FROM glpi_tickets ticket
								LEFT JOIN glpi_entities ent
									ON ticket.entities_id = ent.id
										LEFT JOIN glpi_itilcategories cat
											ON ticket.itilcategories_id = cat.id
							  WHERE ticket.id NOT IN (SELECT ID_Ticket
													  FROM glpi_plugin_bestmanagement_facturation_ticket
													  WHERE etat_fact != 0) " .
								getEntitiesRestrictRequest("AND","ticket","entities_id","",false);
			break;
		  case "linkedcontrat" :
		    $conditionwhere = ($ID == "NULL") ? "IS NULL" : " = $ID";
			$query_tickets = "SELECT ticket.id ID, ticket.name Titre, ent.name Entite, ticket.status Statut,
									 ticket.priority Priorite, ticket.actiontime TempsTicket, ticket.date DateOuv,
									 cat.name CatName, ticket.urgency Urgence, fact.num_fact_api num_fact_api
							  FROM glpi_tickets ticket
								LEFT JOIN glpi_entities ent
									ON ticket.entities_id = ent.id
										LEFT JOIN glpi_itilcategories cat
											ON ticket.itilcategories_id = cat.id
												LEFT JOIN glpi_plugin_bestmanagement_facturation_ticket fact
													ON ticket.id = fact.ID_Ticket
							  WHERE ticket.id IN (SELECT ID_Ticket
												  FROM glpi_plugin_bestmanagement_link_ticketcontrat
												  WHERE ID_Contrat $conditionwhere)" .
									getEntitiesRestrictRequest("AND","ticket","entities_id","",false) . "
							  ORDER BY ticket.id DESC";
		}
		$td	= "<td class='center'>";	// td normal
		
		echo "<script type='text/javascript' >";
		echo "function checkAll(frmName){     
		    var elem = document.getElementById(frmName).elements;
        for (var i = 0; i < elem.length; i++ ){
          if (elem[i].type == 'checkbox'){
              if (document.getElementById('ticket_all'+frmName).checked == true)
                elem[i].checked = true;
              else
                elem[i].checked = false;
          }
        }  
    }";
		echo "</script>";
		
		if($resultat = $DB->query($query_tickets))
			if($DB->numrows($resultat) > 0)
			{
				if (!isset($ID)){
					if ( $lesquels == "whitoutcontrat")
					    $checkall =  "<input type='checkbox' id='ticket_allfrmLink' onclick='checkAll(\"frmLink\")'>";
					else if ($lesquels == "afacturer")
              $checkall =  "<input type='checkbox' id='ticket_allfrmFact' onclick='checkAll(\"frmFact\")'>";
              					
				$colonnes = array($checkall,	// pour la checkbox
								  $LANG['common'][2],
								  $LANG['common'][57],
								  $LANG['entity'][0]);
				}
				else if ($ID == "NULL")
					$colonnes = array($LANG['common'][2],
									  $LANG['common'][57],
									  $LANG['entity'][0]);
				else	// cas sous la fiche contrat
					$colonnes = array($LANG['common'][2],
									  $LANG['common'][57],
									  $LANG['entity'][0]);
				
				array_push($colonnes, $LANG['joblist'][0],
									  $LANG['joblist'][2],
									  $LANG['job'][31],
									  $LANG['reports'][60],
									  $LANG['common'][36],
									  $LANG['joblist'][29]);
				
				// cas où on veut aussi le numéro de facturation
				if ($lesquels == "linkedcontrat")
					array_push($colonnes, $LANG["bestmanagement"]["facturation_ticket"][4]);
				
				echo "<tr>";
				foreach ($colonnes as $col)
					echo "<th style='padding:0px 10px;'>".$col."</th>";
				echo "</tr>";
				
				echo "<script type='text/javascript' >";
				//On ne pourra éditer qu'une valeur à la fois
				echo "var editionEnCours = false;";
				echo "</script>";
				
				while ($row = $DB->fetch_assoc($resultat))
				{
					echo "<tr class='tab_bg_2'>";
					
					$id = $row["ID"];
					// checkbox
					if (!isset($ID))
						echo $td . "<input type='checkbox' name='ticket_$id'></td>";
					// ID
					echo $td . $row["ID"] ."</td>";
					// Titre + lien
					echo $td . "<a href=\"".Toolbox::getItemTypeFormURL("Ticket")."?id=".$row["ID"]."\">".$row["Titre"]."</a></td>";
					// Entité, si 0, alors Entité Racine
					// pas si on se trouve dans la fiche contrat
				//	if (!isset($ID) || $ID == "NULL")
						echo $td . (empty($row["Entite"]) ? $LANG['entity'][2] : $row["Entite"]) ."</td>";
					// Statut + image
					echo $td . "<img src=\"".$CFG_GLPI["root_doc"]."/pics/".$row["Statut"].".png\"
					   alt='".Ticket::getStatus($row["Statut"])."' title='".
					   Ticket::getStatus($row["Statut"])."'>&nbsp;" . Ticket::getStatus($row["Statut"]) . "</td>";
					// Priorité + BG
					$key = $row["Priorite"];
					echo "<td align='center' style=\"background-color:".$_SESSION["glpipriority_$key"]."\">";
					echo Ticket::getPriorityName($row["Priorite"]) . "</td>";
					// Dernière modif
					echo $td . PluginBestmanagementContrat::arrangeIfHours($row["TempsTicket"]/3600, "hour") ."</td>"; 
					// Date ouverture
					echo $td . Html::convDateTime($row["DateOuv"]) ."</td>";
					// Catégorie
					echo $td . $row["CatName"] ."</td>";
					// Urgence
					echo $td . Ticket::getUrgencyName($row["Urgence"]) . "</td>";
					// N° facture
					if ($lesquels == "linkedcontrat")
					{
						echo "<input type='hidden' name='id' value='$ID'></td>";
						echo $td;
						if (isset($row["num_fact_api"]))
						{
							// PARTIE AJAX
							$rand = mt_rand();
							echo "<script type='text/javascript' >\n";
							echo "function showDesc$rand(){\n";
							echo "if(editionEnCours) return false;";
							echo "else editionEnCours = true;";
							echo "Ext.get('desc$rand').setDisplayed('none');";
							$params = array('cols'  => 10,
											'id'  => $row['ID'],
											'name'  => 'num_fact_ticket',
											'data'  => $row['num_fact_api']);
							Ajax::updateItemJsCode("viewdesc$rand",$CFG_GLPI["root_doc"]."/plugins/bestmanagement/ajax/textfield.php",$params,
							false);
							echo "}";
							echo "</script>\n";
							echo "<div id='desc$rand' class='tracking' onClick='showDesc$rand()'>\n";

							echo $row['num_fact_api'] ;

							echo "</div>\n";

							echo "<div id='viewdesc$rand'></div>\n";
							if (0)
							{
								echo "<script type='text/javascript' >\n
								showDesc$rand();
								</script>";
							}
							// FIN
						}
						else
							echo $row['num_fact_api'];	// pas de modifications possibles
					
						echo "</td>";
					}
					
					echo "</tr>";
				} // while
				
			}
			else
				echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][22] . "</div>";

	} // showForm()

	/**
	 * Affiche le select correspondant
	 * aux états de facturation
	 * 
	 * @return Nothing(Display)
	**/
	function selectFacturation()
	{
		global $LANG;
		
		$fact = array();
		$fact[0] = "selected";	// par défaut à non facturé
		
		echo "<select name='id_facturation' id='id_facturation'>";
		for ($i = 0 ; $i < 3 ; ++$i)
			echo "<option $fact[$i] value='$i'>".$LANG["bestmanagement"]["facturation_ticket"][$i]."</option>";
		echo "</select>";

	} // selectFacturation()
	
	/**
	 * Affiche le select correspondant
	 * aux contrats de l'entité
	 * 
	 * @return Nothing(Display)
	**/
	function selectContrats()
	{
		global $DB, $LANG;

		//$name,$entity_restrict=-1,$alreadyused=array(),$nochecklimit=false
		$p['name']           = 'contracts_id';
		$p['value']          = '';
		$p['entity']         = '';
		$p['entity_sons']    = false;
		$p['used']           = array();
		$p['nochecklimit']   = false;
		
		if (!($p['entity']<0) && $p['entity_sons'])
		{
			if (is_array($p['entity']))
				echo "entity_sons options is not available with array of entity";
			else
				$p['entity'] = getSonsOf('glpi_entities',$p['entity']);
		}

		$entrest="";
		$idrest="";
		if ($p['entity']>=0)
		 $entrest=getEntitiesRestrictRequest("AND","glpi_contracts","entities_id",$p['entity'],true);
		
		if (count($p['used']))
			$idrest=" AND `glpi_contracts`.`id` NOT IN(".implode("','",$p['used']).") ";
		
		$query = "SELECT glpi_contracts.*
				FROM glpi_contracts
				LEFT JOIN glpi_entities ON (glpi_contracts.entities_id = glpi_entities.id)
				WHERE glpi_contracts.is_deleted = 0
					AND CURDATE() BETWEEN begin_date AND DATE_ADD(begin_date, INTERVAL duration MONTH) 
					$entrest $idrest
				ORDER BY glpi_entities.completename,
						   glpi_contracts.name ASC,
						   glpi_contracts.begin_date DESC";
		$result=$DB->query($query);

		echo "<select name ='contrat_select' id='contrat_select' >";

			echo "<option value='NULL'>Hors Contrat</option>";

			$prev=-1;
			while ($data=$DB->fetch_array($result))
			{
				if ($p['nochecklimit'] || $data["max_links_allowed"]==0
				 || $data["max_links_allowed"]>countElementsInTable("glpi_contracts_items",
																   "contracts_id = '".$data['id']."'" ))
				{
					if ($data["entities_id"]!=$prev)
					{
						if ($prev>=0)
						  echo "</optgroup>";
						  
						$prev=$data["entities_id"];
						echo "<optgroup label=\"". Dropdown::getDropdownName("glpi_entities", $prev) ."\">";
					}

					if ($_SESSION["glpiis_ids_visible"] || empty($output))
					   $data["name"].=" (".$data["id"].")";

					echo "<option  value='".$data["id"]."'>";
					echo Toolbox::substr($data["name"]." - #".$data["num"]." - ".
									 Html::convDateTime($data["begin_date"]),0,$_SESSION["glpidropdown_chars_limit"]);
					echo "</option>";
				}
			} // while
			
			if ($prev>=0) echo "</optgroup>";
		
		echo "</select></td>";

	} // selectContrats()
	
	/**
	 * Retourne le nombre de tickets sans contrat
	 *
	 * @return int : Nombre de tickets
	**/
	function nbOrphanTickets()
	{
		global $DB;
		
		$tickets = 0;
		$link = 0;
				
		$query_nt = "SELECT COUNT(*) NbTickets
					 FROM glpi_tickets
					 WHERE " . getEntitiesRestrictRequest("","glpi_tickets","entities_id",
                                                 $_SESSION['glpiactiveentities'],false);
		
         if($res = $DB->query($query_nt))
			if ($row = $DB->fetch_assoc($res))
				$tickets = $row["NbTickets"];
				
		$query_nl = "SELECT COUNT(*) NbLink
					 FROM glpi_plugin_bestmanagement_link_ticketcontrat
						LEFT JOIN glpi_tickets
							ON glpi_plugin_bestmanagement_link_ticketcontrat.ID_Ticket = glpi_tickets.id
					 WHERE " . getEntitiesRestrictRequest("","glpi_tickets","entities_id",
                                                 $_SESSION['glpiactiveentities'],false);
		
         if($res = $DB->query($query_nl))
			if ($row = $DB->fetch_assoc($res))
				$link = $row["NbLink"];

		return $tickets - $link;
		
	} // nbOrphanTickets()

	/**
	 * Retourne le nombre de tickets non facturés
	 *
	 * @return int : Nombre de tickets
	**/
	function nbNonFactTickets()
	{
		global $DB;
		
		$tickets = 0;
		$fact = 0;
				
		$query_nt = "SELECT COUNT(*) NbTickets
					 FROM glpi_tickets
					 WHERE " . getEntitiesRestrictRequest("","glpi_tickets","entities_id",
                                                 $_SESSION['glpiactiveentities'],false);
		
         if($res = $DB->query($query_nt))
			if ($row = $DB->fetch_assoc($res))
				$tickets = $row["NbTickets"];
				
		$query_nl = "SELECT COUNT(*) NbLink
					 FROM glpi_plugin_bestmanagement_facturation_ticket
						LEFT JOIN glpi_tickets
							ON glpi_plugin_bestmanagement_facturation_ticket.ID_Ticket = glpi_tickets.id
					 WHERE etat_fact != 0 && " . getEntitiesRestrictRequest("","glpi_tickets","entities_id",
                                                 $_SESSION['glpiactiveentities'],false);
		
         if($res = $DB->query($query_nl))
			if ($row = $DB->fetch_assoc($res))
				$fact = $row["NbLink"];

		return $tickets - $fact;
		
	} // nbNonFactTickets()
	
} // class PluginBestmanagementAllTickets
?>