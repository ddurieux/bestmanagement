<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Classe AllContrats servant pour l'affichage de 
//				plusieurs contrats
// ----------------------------------------------------------------------

class PluginBestmanagementAllContrats
{
	function __construct() {} // constructeur
	
	function showForm()
	{
		global $DB, $CFG_GLPI, $LANG;
		
		echo "<div>";
		echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/bestmanagement.form.php\">";
		echo "<table class='tab_cadre' style='margin-top: 10px;'>";
		foreach($this->listContratsNonFact() as $id)
		{
			$contrat = new PluginBestmanagementContrat($id);
			
			if($contrat->nbAchats() == 0)
				echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][16] . "</div>";
			else
				$contrat->historical(true, true);
		}
		echo "</table>";
		
		echo "<table class='tab_cadre'>";
		echo "<tr class='tab_bg_2'><td>".$LANG["bestmanagement"]["facturation"][0]." : </td>";
		echo "<td>";
		$contrat->inputFacturation();
		echo "</td>";
		echo "<td align='center'><input type=\"submit\" name=\"addFacturation\" class=\"submit\" value=\"".$LANG['buttons'][51]."\" ></td>";
		echo "</tr>";
					
		echo "</form>";
		echo "</div>";
		
	} // showForm()

	// return array
	function listContratsNonFact()
	{
		global $DB;
		
		$tabContrats = array();
		
		$query_nf = "SELECT distinct ID_Contrat ContratsNonFact
					 FROM glpi_plugin_bestmanagement_achat
						LEFT JOIN glpi_contracts
							ON glpi_plugin_bestmanagement_achat.ID_Contrat = glpi_contracts.id
					 WHERE ID_Compteur IS NOT NULL AND etat_fact != 0 " .
						getEntitiesRestrictRequest("AND","glpi_contracts","entities_id", $_SESSION['glpiactiveentities'],false);
		
         if($res = $DB->query($query_nf))
			while($row = $DB->fetch_assoc($res))
				$tabContrats[] = $row["ContratsNonFact"];
		
		return $tabContrats;
	} // listContratsNonFact()
	
} // class PluginBestmanagementAllContrats
?>
