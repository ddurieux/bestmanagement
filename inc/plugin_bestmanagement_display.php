<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Affichage dynamique des onglets sous la fiche contrat
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT'))
   die("Sorry. You can't access directly to this file");

// affichage sous la fiche contrat
function plugin_bestmanagement_fichecontrat($ID)
{
	global $LANG;
	// on cr�� un objet PluginBestmanagementContrat que l'on va manipuler tout au long de cette fonction
	$contrat = new PluginBestmanagementContrat($ID);
	
	if(0 == count($contrat->infoCompteur()))	// définir le tri
		if (Session::haveRight('contract', 'w'))
			$contrat->sort();
		else
			echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][13] . "</div>";
	else
	{
		echo "<div class='tab_cadre_fixe' style='width:1100px'>";
			echo "<div class='x-tab-panel-header x-unselectable x-tab-panel-header-plain' style='-moz-user-select: none;'>";
			echo "<div class='x-tab-strip-wrap'>";
				echo "<ul class='x-tab-strip x-tab-strip-top'>";
				$max = (Session::haveRight('contract', 'w') && $contrat->isAvailable()) ? 9 : 3;	// nb onglets
				$active = "class='x-tab-strip-active'";			// onglet actif
				for ($i = 1 ; $i <= $max ; $i++)
				{
					if ($i <= 3 || Session::haveRight('contract', 'w'))
					{
						echo "<li id='li$i' $active>";
							echo "<a class='x-tab-right' href='javascript:ChangeOnglet($i,$max, \"tab_\", \"content_\");' id=\"tab_$i\">";
							echo "<em class='x-tab-left'>";
								echo "<span class='x-tab-strip-inner'>";
									echo "<span class='x-tab-strip-text'>" . $LANG["bestmanagement"]["tabs"][$i]."</span>";
								echo "</span>";
							echo "</em>";
							echo "</a>";
						echo "</li>";
						$active = "";
					}
				} // for
				echo "</ul>";
			echo '</div>';
			echo '</div>';

			echo '<div id="tabbed_box">';
			echo '<div class="tabbed_area tab_cadre_fixe">';
				
				echo '<div id="content_1" class="content">';
					// affichage du tableau récapitulatif
					if (plugin_bestmanagement_haveRight("bestmanagement","recapcontrat", 1))
						echo $contrat->showTabRecap("currentRecap");
					else
						echo "<div class='x-tab-panel-header'>" . $LANG['common'][83] . "</div>";
				echo '</div>';
				
				echo '<div id="content_2" class="content" style="display:none;">';
					// affichage de l'historique des achats
					if (plugin_bestmanagement_haveRight("bestmanagement","historicalpurchase", 1))
						if($contrat->isContratIllim())
							echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][31] . "</div>";
						else
							$contrat->historical();
					else
						echo "<div class='x-tab-panel-header'>" . $LANG['common'][83] . "</div>";
				echo '</div>';

            echo '<div id="content_3" class="content" style="display:none;">';
					// affichage de l'historique par achat
					if (plugin_bestmanagement_haveRight("bestmanagement","historicalperiode", 1))
                  echo '';
					else
						echo "<div class='x-tab-panel-header'>" . $LANG['common'][83] . "</div>";
				echo '</div>';
				
            
				echo '<div id="content_4" class="content" style="display:none;">';
					// affichage de l'historique global
					if (plugin_bestmanagement_haveRight("bestmanagement","historicalperiode", 1))
						echo $contrat->showTabRecap("histRecap");
					else
						echo "<div class='x-tab-panel-header'>" . $LANG['common'][83] . "</div>";
				echo '</div>';
					
				if (Session::haveRight('contract', 'w') && $contrat->isAvailable())
				// si l'utilisateur a l'autorisation pour modifier un contrat
				// et le contrat n'a pas �t� supprim�
				{
					echo '<div id="content_5" class="content" style="display:none;">';
						// affichage d'insertion d'achat
						if (plugin_bestmanagement_haveRight("bestmanagement","addpurchase", 1))
							if($contrat->isContratIllim())
								echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][31] . "</div>";
							else
								$contrat->addPurchase();
						else
							echo "<div class='x-tab-panel-header'>" . $LANG['common'][83] . "</div>";
					echo '</div>';
					
					echo '<div id="content_6" class="content" style="display:none;">';
						// affichage de reconduction
						if (plugin_bestmanagement_haveRight("bestmanagement","renewal", 1))
							$contrat->renewal();
						else
							echo "<div class='x-tab-panel-header'>" . $LANG['common'][83] . "</div>";
					echo '</div>';
					
					echo '<div id="content_7" class="content" style="display:none;">';
						// affichage de facturation
						if (plugin_bestmanagement_haveRight("bestmanagement","facturationcontrat", 1))
							$contrat->facture();
						else
							echo "<div class='x-tab-panel-header'>" . $LANG['common'][83] . "</div>";
					echo '</div>';
					
					echo '<div id="content_8" class="content" style="display:none;">';
						// liste des tickets affect�s au contrat
						echo "<table class='tab_cadre' style='margin-top: 10px;'>";
						PluginBestmanagementAllTickets::showForm("linkedcontrat", $ID);
						echo "</table>";
					echo '</div>';
					
					echo '<div id="content_9" class="content" style="display:none;">';
					// stats par sous-entités
					     if ($contrat->isContratRecursif()){
                  echo $contrat->showStatEntites();
               }
					echo '</div>';
				}
			echo '</div>';
			echo '</div>';
		echo '</div>'; // div tab_cadre_fixe

	} // else

	//$contrat->DValues();

} // plugin_bestmanagement_fichecontrat()
?>