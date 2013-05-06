<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

include_once (GLPI_ROOT . "/plugins/bestmanagement/inc/allcontrats.class.php");
include_once (GLPI_ROOT . "/plugins/bestmanagement/inc/alltickets.class.php");
include_once (GLPI_ROOT . "/plugins/bestmanagement/inc/plugin_bestmanagement_display.php");

plugin_bestmanagement_checkRight("bestmanagement","recapglobal", 1);


if ($_SESSION["glpiactiveprofile"]["interface"] == "central")
   Html::header($LANG["bestmanagement"]["allrecap"][0], $_SERVER['PHP_SELF'],"plugins","bestmanagement","a");
else
   Html::helpHeader($LANG["bestmanagement"]["allrecap"][0], $_SERVER['PHP_SELF']);

echo "<div class='tab_cadre_fixe'>";
	echo "<div class='x-tab-panel-header x-unselectable x-tab-panel-header-plain' style='-moz-user-select: none;'>";
	echo "<div class='x-tab-strip-wrap'>";
		echo "<ul class='x-tab-strip x-tab-strip-top'>";
		$nbonglets = 5;
		$active = "class='x-tab-strip-active'";			// onglet actif
		for ($i = 1 ; $i <= $nbonglets ; $i++)
		{
			if ($i <= 1 || Session::haveRight('contract', 'w'))
			{
				echo "<li id='li$i' $active>";
					echo "<a class='x-tab-right' href='javascript:ChangeOnglet($i,$nbonglets, \"tab_\", \"content_\");' id=\"tab_$i\">";
					echo "<em class='x-tab-left'>";
						echo "<span class='x-tab-strip-inner'>";
							echo "<span class='x-tab-strip-text'>" . $LANG["bestmanagement"]["tabs_global"][$i]."</span>";
						echo "</span>";
					echo "</em>";
					echo "</a>";
				echo "</li>";
			}
			$active = "";

		} // for
		echo "</ul>";
	echo '</div>';
	echo '</div>';

	echo '<div id="tabbed_box">';
	echo '<div class="tabbed_area tab_cadre_fixe">';
		
		echo '<div id="content_1" class="content">';
			recapAllContracts();
		echo '</div>';
		
		echo '<div id="content_2" class="content" style="display:none;">';
			if (plugin_bestmanagement_haveRight("bestmanagement","linkticketcontrat", 1))
				ticketsToLink();
			else
				echo "<div class='x-tab-panel-header'>" . $LANG['common'][83] . "</div>";
		echo '</div>';
		
		echo '<div id="content_3" class="content" style="display:none;">';
			if (plugin_bestmanagement_haveRight("bestmanagement","facturationticket", 1))
				ticketsAFacturer();
			else
				echo "<div class='x-tab-panel-header'>" . $LANG['common'][83] . "</div>";
		echo '</div>';

		echo '<div id="content_4" class="content" style="display:none;">';
			if (plugin_bestmanagement_haveRight("bestmanagement","facturationcontrat", 1))
				contratsAFacturer();
			else
				echo "<div class='x-tab-panel-header'>" . $LANG['common'][83] . "</div>";
		echo '</div>';
		
		echo '<div id="content_5" class="content" style="display:none;">';
			// liste des tickets affectés au hors contrat
			echo "<table class='tab_cadre' style='margin-top: 10px;'>";
			PluginBestmanagementAllTickets::showForm("linkedcontrat", "NULL");
			echo "</table>";
		echo '</div>';
		
	echo '</div>';
	echo '</div>';
echo '</div>'; // div tab_cadre_fixe


function recapAllContracts()
{
	global $DB, $LANG;
	
	echo "		<script type='text/javascript'>
		 
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
				

				function showContrats(){
					var xhr = getXhr();
					// On défini ce qu'on va faire quand on aura la réponse
					xhr.onreadystatechange = function(){
						// On ne fait quelque chose que si on a tout reçu et que le serveur est ok
						if(xhr.readyState == 4 && xhr.status == 200){
							leselect = xhr.responseText;
							// On se sert de innerHTML pour rajouter les options a la liste
							document.getElementById('tabcontrat').innerHTML = leselect;
						}
					}

					// Ici on va voir comment faire du post
					xhr.open(\"POST\",\"show_contracts.php\",true);
					// ne pas oublier ça pour le post
					xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
					// ne pas oublier de poster les arguments
					sel = document.getElementById('contrat');
					ctr = sel.options[sel.selectedIndex].value;
					xhr.send(\"allContrats=\"+ctr);
				}
			</script>";
			
	echo "<form>";
		echo "<h1 class='center'>" . $LANG["bestmanagement"]["contrat"][6] . " : ";
		echo "<select name='contrat' id='contrat' onchange='showContrats()'>";
		
		for ($i = 1 ; $i < 6 ; $i++)
			echo "<option value='$i'>" . $LANG["bestmanagement"]["contrat"][$i] . "</option>";

		echo "</select></h1>";
			
		echo "<div id='tabcontrat' style='display:inline'>
				<div name='tabcontrat'>";
					$query = "SELECT id
							  FROM glpi_contracts
							  WHERE begin_date IS NOT NULL AND duration IS NOT NULL AND is_deleted = 0 " .
							  getEntitiesRestrictRequest("AND","glpi_contracts","entities_id","",false) . "
							  ORDER BY DATE_ADD(begin_date, INTERVAL duration MONTH)";
					
					$all_contrats = array();
					
					if($res = $DB->query($query))
						if($DB->numrows($res) > 0)
						{
							while ($row = $DB->fetch_assoc($res))
								$all_contrats[] = $row["id"];
							
							showAllContracts($all_contrats);
						}
						else
							echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][26] . "</div>";
		echo "</div>
			</div>";

	echo "</form>";
} // recapAllContracts()

function ticketsToLink()
{
	global $LANG;
	
	$tickets = new PluginBestmanagementAllTickets();
	$nb_orphelins = $tickets->nbOrphanTickets();
	$s = ($nb_orphelins == 1) ? 1 : 0;	// pluriel si plusieurs tickets
	
	if ($nb_orphelins == 0)
		echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][22] . "</div>";
	else
	{
		echo "<div><br>";
		echo "<h1 class='center'>".$LANG["bestmanagement"]["ticket"][0] . $nb_orphelins . $LANG["bestmanagement"]["ticket"][1+$s]."</h1>";
		echo "<form method='post' id='frmLink' action=\"bestmanagement.form.php\">";
		echo "<table class='tab_cadre' style='margin-top: 10px;'>";
		$tickets->showForm();
		echo "<tr class='tab_bg_1'><td colspan='10'>&nbsp;</td></tr>";
		echo "<tr class='tab_bg_1'>";
		echo "<td colspan='2'></td>";

		echo "<td class='center' colspan='4'>" . $LANG['financial'][1] . " : ";
		$tickets->selectContrats();
		echo "</td>";
		
		if (plugin_bestmanagement_haveRight("bestmanagement","facturationticket", 1))
		{
			echo "<td class='center' colspan='2'>" . $LANG["bestmanagement"]["facturation_ticket"][3]. " : ";
			$tickets->selectFacturation();
			echo "</td>";
		}
		else
			echo "<td class='center' colspan='2'></td>";
		echo "<td colspan='2' align='center'><input type=\"submit\" name=\"link_ticketcontrat\" class=\"submit\" value=\"".$LANG["buttons"][51]."\" ></td>";
		echo "</tr>";
		
		echo "</table></form></div>";
	}
} // ticketsToLink()

function ticketsAFacturer()
{
	global $LANG;
	
	$tickets = new PluginBestmanagementAllTickets();
	$nb_non_fact = $tickets->nbNonFactTickets();
	
	$s = ($nb_non_fact == 1) ? 1 : 0;	// pluriel si plusieurs tickets
	
	if ($nb_non_fact == 0)
		echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][22] . "</div>";
	else
	{
		echo "<div><br>";
		echo "<h1 class='center'>".$LANG["bestmanagement"]["ticket"][0] . $nb_non_fact . $LANG["bestmanagement"]["ticket"][3+$s]."</h1>";
		echo "<form method='post' id='frmFact' action=\"bestmanagement.form.php\">";
		echo "<table class='tab_cadre' style='margin-top: 10px;'>";
		$tickets->showForm("afacturer");
		
		if (plugin_bestmanagement_haveRight("bestmanagement","facturationticket", 1))
		{			
			echo "<tr class='tab_bg_1'><td colspan='10'>&nbsp;</td></tr>";
			echo "<tr class='tab_bg_1'>";
			echo "<td colspan='2'></td>";
			echo "<td class='center' colspan='4'>" . $LANG["bestmanagement"]["facturation_ticket"][3]. " : ";
			echo "<select name='id_facturation' id='id_facturation'>";
				echo "<option value='1'>".$LANG["bestmanagement"]["facturation_ticket"][1]."</option>";
				echo "<option value='2'>".$LANG["bestmanagement"]["facturation_ticket"][2]."</option>";
			echo "</select>";
			echo "<td class='center' colspan='2'>" . $LANG["bestmanagement"]["facturation_ticket"][4]. " : ";
			echo "<input type='text' name='NumFact' size='20' maxlength='255'></td>";
			echo "</td>";
			echo "<td colspan='2' align='center'><input type=\"submit\" name=\"FacturationTicket\" class=\"submit\" value=\"".$LANG["buttons"][51]."\" ></td>";
			echo "</tr>";
		}
		echo "</table></form></div>";
	}
} // ticketsAFacturer()

function contratsAFacturer()
{
	global $LANG;
	
	$contrats = new PluginBestmanagementAllContrats();
	$nb_non_fact = count($contrats->listContratsNonFact());
	
	$s = ($nb_non_fact == 1) ? 1 : 0;	// pluriel si plusieurs contrats
	
	if ($nb_non_fact == 0)
		echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][26] . "</div>";
	else
	{
		echo "<div><br>";
		echo "<h1 class='center'>".$LANG["bestmanagement"]["contrat"][10] . $nb_non_fact . $LANG["bestmanagement"]["contrat"][11+$s]."</h1>";
		echo "<table class='tab_cadre' style='margin-top: 10px;'>";
		$contrats->showForm();
		echo "</table></div>";
	}
} // contratsAFacturer()

Html::footer();
?>
