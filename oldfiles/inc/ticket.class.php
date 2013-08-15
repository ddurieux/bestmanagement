<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Classe Ticket servant pour l'état de facturation,
//					ainsi que l'affectation à un contrat
// ----------------------------------------------------------------------

class PluginBestmanagementTicket
{
	private $id;
	
	/**
	 * Constructeur
	 * 
	 * @param ID : identifiant du ticket
	**/
	function __construct ($ID=0)
	{
		$this->id = $ID;
	}
	/**
	 * Retourne le select correspondant aux états de facturation
	 *
	 * @return <select> ... </select>
	**/
	function selectEtatFacture()
	{
		global $DB, $LANG;
		
		$etat = $this->etatFact();
		$fact = array();
		$fact[0] = "selected";	// par défaut à non facturé
		
		$select = "<select name='id_facturation' id='id_facturation'>";
		for ($i = 0 ; $i < 3 ; ++$i)
		{
			$fact[$i]	= ($etat == $i) ? "selected" : "";	// etat de facturation
			$select .= "<option $fact[$i] value='$i'>".$LANG["bestmanagement"]["facturation_ticket"][$i]."</option>";
		}
		$select .= "</select>";

		return $select;
	
	} // selectEtatFacture()
	
	/**
	 * Retourne l'état de facturation
	 * 0 => non facturé
	 * 1 => facturé sous contrat
	 * 2 => facturé hors contrat
	 *
	 * @return int
	**/
	function etatFact()
	{
		global $DB;
		
		$query = "SELECT etat_fact
				  FROM glpi_plugin_bestmanagement_facturation_ticket
				  WHERE ID_Ticket = $this->id";

		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				$row = $DB->fetch_assoc($resultat);
		
		$etat = isset($row["etat_fact"]) ? $row["etat_fact"] : 0;
		
		return $etat;
	} // selectedFact()
	
   
   
	/**
	 * Formulaire qui se place sous la fiche
	 * d'un ticket, pour l'affectation à un contrat
	 *
	 * @return Nothing(Display)
	**/
	function formLinkContrat() {
		global $DB, $LANG, $CFG_GLPI;
		
		echo "<div>";
		echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/bestmanagement.form.php\">";
		if ($this->id) {
			echo "<input type='hidden' name='ticket_".$this->id."'	value='$this->id'>";
      }
		echo "<table class='tab_cadre'>";
		
		echo "<tr class='tab_bg_1'>";
		echo "<td class='left'>Contrat :</td>";
		echo "<td>";

		//$name,$entity_restrict=-1,$alreadyused=array(),$nochecklimit=false
      $p = array();
		$p['name']           = 'contracts_id';
		$p['value']          = '';
		$p['entity']         = '';
		$p['entity_sons']    = false;
		$p['used']           = array();
		$p['nochecklimit']   = false;
		
		// on vérifie si un contrat est déjà relié à ce ticket
		if (0 == countElementsInTable("glpi_plugin_bestmanagement_link_ticketcontrat", "ID_Ticket = $this->id")) {
			$p['value'] = -1;
      } else { // contrat associé (ou Hors Contrat, dans ce cas 0)
			$query	=  "SELECT IFNULL(ID_Contrat,0) ID_Contrat
						FROM glpi_plugin_bestmanagement_link_ticketcontrat
						WHERE ID_Ticket = $this->id";
			
			if($resultat = $DB->query($query)) {
				if($DB->numrows($resultat) > 0) {
					while($row = $DB->fetch_assoc($resultat)) {
						$p['value'] = $row["ID_Contrat"];
               }
            }
         }
		}
	
      $ticket = new Ticket();
      $ticket->getFromDB($this->id);
      
		$p['entity'] = getSonsOf('glpi_entities',$ticket->fields['entities_id']);

		$idrest  = "";
      $entrest = getEntitiesRestrictRequest("AND",
                                            "glpi_contracts",
                                            "entities_id",
                                            $ticket->fields['entities_id'],
                                            true);
      
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
		
		
//echo "<script type='text/javascript' src='".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/bestmanagement.js'></script>";

echo "<script type='text/javascript'>
	 
			function getXhr(){
								var xhr = null; 
				if(window.XMLHttpRequest) // Firefox et autres
				   xhr = new XMLHttpRequest(); 
				else if(window.ActiveXObject){ // Internet Explorer 
				   try {
							xhr = new ActiveXObject(\"Msxml2.XMLHTTP\");
						} catch (e) {
							xhr = new ActiveXObject(\"Microsoft.XMLHTTP\");
						}
				}
				else { // XMLHttpRequest non supporté par le navigateur 
				   alert(\"Votre navigateur ne supporte pas les objets XMLHTTPRequest...\"); 
				   xhr = false; 
				} 
								return xhr;
			}
			
			/**
			* Méthode qui sera appelée sur le click du bouton
			*/
			function go(id){
				var xhr = getXhr();
				// On défini ce qu'on va faire quand on aura la réponse
				xhr.onreadystatechange = function(){
					// On ne fait quelque chose que si on a tout reçu et que le serveur est ok
					if(xhr.readyState == 4 && xhr.status == 200){
						leselect = xhr.responseText;
						// On se sert de innerHTML pour rajouter les options a la liste
						document.getElementById(id).innerHTML = leselect;
					}
				}

				// Ici on va voir comment faire du post
				xhr.open(\"POST\",\"../plugins/bestmanagement/tabrecap.php\",true);
				// ne pas oublier ça pour le post
				xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
				// ne pas oublier de poster les arguments
				// ici, l'id du contrat
				sel = document.getElementById('contrat_select');
				idticket = $this->id;
				idcontrat = sel.options[sel.selectedIndex].value;
				xhr.send(\"idContrat=\"+idcontrat+\"&idTicket=\"+idticket);
			}
			
		</script>
";

		echo "<select name ='contrat_select' id='contrat_select' onchange='go(\"tabrecap2\")'>";
		
			if ($p['value'] >= 0) // affecté à un contrat
			{
				if ($p['value'] == 0)
				{ // hors contrat
					$hc = true;
					echo "<option value='NULL'>Hors Contrat</option>";
				}
				else	// détails du contrat
				{
					$output=Dropdown::getDropdownName('glpi_contracts',$p['value']);
					
					if ($_SESSION["glpiis_ids_visible"])
						$output.=" (".$p['value'].")";
					
					echo "<option selected value='".$p['value']."'>".$output."</option>";
				}
			}
			else // affecté à aucun contrat
				echo "<option value='-1'>-----</option>";
			
			
			if (!isset($hc))
			{
				echo "<option value='NULL'>Hors Contrat</option>";
				unset($hc);
			}
			 
			$prev=-1;
			while ($data=$DB->fetch_array($result)) {
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
		
		if (plugin_bestmanagement_haveRight("bestmanagement","facturationticket", 1))
		{
			echo "<td>" . $LANG["bestmanagement"]["facturation_ticket"][3]. " : ";
			echo "</td>";
			echo "<td>" . $this->selectEtatFacture() . "</td>";
			echo "<td>";
			echo "<input type='text' name='NumFact' size='15'
				   value='" . $this->giveNumFacture() . "'>";
			echo "</td>";
		}
		echo "<td><input type=\"submit\" name=\"link_ticketcontrat\" class=\"submit\" value=\"".$LANG["buttons"][51]."\" ></td>";
		echo "</tr>";
		
		echo "<tr id='tabrecap2'  style='background-color: #f2f2f2;'>";
		
		echo "</tr>";

		echo "</table>";
		echo "</form>";
		echo "</div>";
	
	} // formLinkContrat()
	
	/**
	 * Retourne le numéro de facture du ticket
	 * 
	 * @return string : numéro de facture
	**/
	function giveNumFacture()
	{
		global $DB, $LANG;
		
		$query = "SELECT num_fact_api
				  FROM glpi_plugin_bestmanagement_facturation_ticket
				  WHERE ID_Ticket = $this->id";
		
		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				while($row = $DB->fetch_assoc($resultat))
					return $row["num_fact_api"];
		
		return "N Facture";
	} // giveNumFacture()
	
} // class PluginTicket
?>
