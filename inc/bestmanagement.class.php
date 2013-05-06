<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file:
// ----------------------------------------------------------------------

class PluginBestmanagementContrat extends CommonDBTM
{
	private $id;
	public $table            = 'glpi_plugin_bestmanagement_achat';
	public $type             = 'PluginBestmanagementContrat';

	/**
	 * Constructeur
	 * 
	 * @param ID : identifiant du contrat
	**/
	function __construct ($ID)
	{
		$this->id = $ID;
	}
	
	/**
	 * Affiche l'historique des achats 
	 * Les p�riodes sont s�par�es par des lignes vierges
	 *
	 ** @param onlyone : if true => une seule p�riode (+ les achats des anciens non factur�s)
	 *
	 * @return Nothing(Display)
	**/
	function historical($onlyone=false, $presentcontrat=null)
	{
		global $DB, $CFG_GLPI, $LANG;
		
		if($this->nbAchats() > 0)	// on a donc au moins un achat
		{
			$info_compteur = $this->infoCompteur();
			
			if (!isset($presentcontrat))
				echo "<table class='tab_cadre' style='margin-top: 10px;'>";
			
			$colonnes = array();
			if ($onlyone)	array_push($colonnes, "");	// pour la checkbox
				
			array_push($colonnes, "Date",
								  $LANG["bestmanagement"]["historical"][0]);
			
			if ($info_compteur["compteur"] == "category")
			{
				$cat_name = $this->tabCatName(); // association id<->nom de la cat�gorie
				array_push($colonnes, $LANG['common'][36]);		// cat
			}
			else
				array_push($colonnes, $LANG['joblist'][2]);		// prio
			
			// pour adapter l'affichage des colonnes
			$nb = ($info_compteur["unit"] == "nbtickets") ? 10 : 0;
			
			array_push($colonnes, $LANG["bestmanagement"]["tabrecap"][1 + $nb],
								  $LANG['common'][25],
								  $LANG["bestmanagement"]["facturation_contrat"][3]);

			echo "<tr>";
			foreach ($colonnes as $col)
				echo "<th style='padding:0px 10px;'>".$col."</th>";
			echo "</tr>";
			
			$colspan = count($colonnes);
			
			// Donne le nom du contrat dans le cas o�
			// on ne se trouve pas sous la fiche contrat
			if ($presentcontrat)
			{
				echo "<tr class='tab_bg_1'>";
				echo "<td colspan='$colspan'><a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?id=$this->id'>";
				echo $this->giveRealName()."</a></td>";
				echo "</tr>";
			}
			
			$UNSEUL = "";
			$UNION = "";
			if ($onlyone)
			{
				$UNSEUL = "AND achat.date_deb = contrat.begin_date";
				// + les achats non factur�s
				$UNION = "UNION
						  SELECT achat.id IDA, date_deb, ID_Compteur, etat_fact, contrat.duration duration,
								 avenant, UnitBought, comments, num_fact_api, date_save
						  FROM glpi_plugin_bestmanagement_achat achat
							INNER JOIN glpi_contracts contrat
								ON achat.ID_Contrat = contrat.ID
						  WHERE ID_Contrat = $this->id
							AND ID_Compteur IS NOT NULL
							AND etat_fact = 1";
			}
			
			// requ�te sur l'historique des achats du contrat
			$query_historique = "SELECT achat.id IDA, date_deb, ID_Compteur, etat_fact, contrat.duration duration,
										avenant, UnitBought, comments, num_fact_api, date_save
								 FROM glpi_plugin_bestmanagement_achat achat
									INNER JOIN glpi_contracts contrat
										ON achat.ID_Contrat = contrat.ID
								 WHERE ID_Contrat = $this->id
									AND ID_Compteur IS NOT NULL
									$UNSEUL
								 $UNION
								 ORDER BY date_deb, date_save";
			
			echo "<script type='text/javascript' >";
			//On ne pourra �diter qu'une valeur � la fois
			echo "var editionEnCours = false;";
			echo "</script>";
						
			if($resultat = $DB->query($query_historique))
				if($DB->numrows($resultat) > 0)
					while ($row = $DB->fetch_assoc($resultat))
					{
						$key = $row["ID_Compteur"];
						
						// si l'achat est factur� on ne l'affiche pas (que cas g�n�ral, pas sous la fiche)
						if (isset($presentcontrat) && !$row["etat_fact"]) continue;
						
						// rappel En-T�te p�riode du contrat
						if (!isset($date_deb) || $date_deb != $row["date_deb"])
						{
							if (isset($date_deb))
								echo "<tr class='tab_bg_1'><td 'colspan='$colspan'>&nbsp;</td></tr>";	// ligne vierge
								
							echo "<tr class='tab_bg_1'>";
							echo "<td colspan='$colspan'>" . $LANG["bestmanagement"]["contrat"][8]. Html::convDate($row["date_deb"]);
							echo $LANG["bestmanagement"]["contrat"][9].Infocom::getWarrantyExpir($row["date_deb"],$row["duration"])."</td>";
							echo "</tr>";
						}
						
						$date_deb	= $row["date_deb"];
						
						echo "<tr class='tab_bg_2'>";
						$td	= "<td class='center'>";	// td normal
						
						$td_compteur = $td;
						
						// que pour l'onglet Facturation
						if ($onlyone)
						{
							$id_achat = $row["IDA"];
							
							echo "<td>";
							// checkbox si pas factur�
							echo ($row["etat_fact"]) ? "<input type='checkbox' name='CBFact_$id_achat'></td>" : "</td>";
						}
						
						echo $td . Html::convDate($row["date_save"]) ."</td>";			// Date

						echo $td;	// D�tails
						echo ($row["avenant"] == 0) ? $LANG["bestmanagement"]["historical"][2]." - " : "";
						echo $LANG["bestmanagement"]["facturation_contrat"][$row["etat_fact"]]."</td>";

						$name = $this->giveCompteurName($key, $info_compteur);

						// adapte la couleur du fond selon la priorit�
						if ($info_compteur["compteur"] == "priority" && isBgColor())
							$td_compteur = "<td align='center' style=\"background-color:".$_SESSION["glpipriority_$key"]."\">";

						echo $td_compteur . $name . "</td>";		// Compteur
						
						echo $td . $this->arrangeIfHours($row["UnitBought"], $info_compteur["unit"])."</td>";	// Unit�s achet�es
						echo $td . $row["comments"] . "</td>";		// Commentaires

						echo $td;
						
						if ($onlyone)
						{
							// PARTIE AJAX
							$rand = mt_rand();
							echo "<script type='text/javascript' >\n";
							echo "function showDesc$rand(){\n";
							echo "if(editionEnCours) return false;";
							echo "else editionEnCours = true;";
							 echo "Ext.get('desc$rand').setDisplayed('none');";
							 $params = array('cols'  => 10,
											 'id'  => $row['IDA'],
											 'name'  => 'num_fact_api',
											 'data'  => $row['num_fact_api']);
							 Ajax::updateItemJsCode("viewdesc$rand",$CFG_GLPI["root_doc"]."/plugins/bestmanagement/ajax/textfield.php",$params,
												  false);
							 echo "}";
							 echo "</script>\n";
							 echo "<div id='desc$rand' class='tracking' onClick='showDesc$rand()'>\n";
							 
								echo $row['num_fact_api'];
							 
							 echo "</div>\n";

							 echo "<div id='viewdesc$rand'></div>\n";
							 if (0) {
								echo "<script type='text/javascript' >\n
								showDesc$rand();
								</script>";
							 }
							// FIN
						}
						else
							echo $row['num_fact_api'];	// pas de modifications possibles
							
						echo "</td>";
						echo "</tr>";
					} // while
			
			if (!isset($presentcontrat))
				echo "</table>";
		}
		else
			echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][16] . "</div>";
	} // historical()
	

   
	/**
	 * Permet l'ajout d'un achat
	 *
	 * @return Nothing(Display)
	**/
	function addPurchase()
	{
		global $LANG, $CFG_GLPI;

		echo "	<script type='text/javascript'>

			function getXhr()
			{
				var xhr = null; 
				if(window.XMLHttpRequest) // Firefox et autres
					xhr = new XMLHttpRequest(); 
				else if(window.ActiveXObject)
				{ // Internet Explorer 
					try
					{
						xhr = new ActiveXObject(\"Msxml2.XMLHTTP\");
					}
					catch (e)
					{
						xhr = new ActiveXObject(\"Microsoft.XMLHTTP\");
					}
				}
				else
				{ // XMLHttpRequest non support� par le navigateur 
					alert(\"Votre navigateur ne supporte pas les objets XMLHTTPRequest...\"); 
					xhr = false; 
				} 
				return xhr;
			}
			
			/**
			* M�thode qui sera appel�e sur le clic du bouton
			*/
			function go()
			{
				var xhr = getXhr();
				// On d�fini ce qu'on va faire quand on aura la r�ponse
				xhr.onreadystatechange = function(){
					// On ne fait quelque chose que si on a tout re�u et que le serveur est ok
					if(xhr.readyState == 4 && xhr.status == 200){
						leselect = xhr.responseText;
						// On se sert de innerHTML pour rajouter les options a la liste
						document.getElementById('api').innerHTML = leselect;
					}
				}

				// Ici on va voir comment faire du post
				xhr.open(\"POST\",\"../plugins/bestmanagement/tabrecap.php\",true);
				// ne pas oublier �a pour le post
				xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
				// ne pas oublier de poster les arguments
				// ici, l'id de l'auteur
				sel = document.getElementById('id_facturation');
				fact = sel.options[sel.selectedIndex].value;
				idcontrat = $this->id;
				xhr.send(\"Fact=\"+fact+\"&idContratFactureContrat=\"+idcontrat);
			}
		</script>
";

		$info_compteur = $this->infoCompteur();
		$td	= "<td style='padding:0.2em 0.5em;' align=center>";	// td normal
		$tr = "<tr class='tab_bg_2'>";							// tr normal
		
		if(count($info_compteur) > 0)
		{
			echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/bestmanagement.form.php\">";
			echo "<input type='hidden' name='id_contrat'	value='$this->id'>";
				echo "<table class='tab_cadre'>";
				echo "<tr><th align=center colspan='5'>" . $LANG["bestmanagement"]["achat"][0]; // Titre
				echo ($info_compteur["unit"] == "hour")			? $LANG["bestmanagement"]["achat"][1] : $LANG["bestmanagement"]["achat"][2];
				echo ($info_compteur["compteur"] == "category")	? $LANG["bestmanagement"]["achat"][3] : $LANG["bestmanagement"]["achat"][4];
				echo"</th></tr>";	// fin Titre
				
				echo $tr . $td;		// Choisir une cat�gorie || Choisir une priorit�
				echo ($info_compteur["compteur"] == "category")	? $LANG["bestmanagement"]["achat"][5] : $LANG["bestmanagement"]["achat"][6];
				echo " :</td>";						
				echo "<td style='padding:0.2em 0.5em;' align=center colspan='4'>";
				
				if ($info_compteur["compteur"] == "category")
					Dropdown::show('TaskCategory');				// dropdown des cat�gories
				else
					Ticket::dropdownPriority("priority",3);		// dropdown des priorit�s
				echo "</td></tr>";
				
				// Achat
				echo $tr . $td;
				echo ($info_compteur["unit"] == "hour") ? $LANG["bestmanagement"]["achat"][7] : $LANG["bestmanagement"]["achat"][8];
				echo " : <input type='int' name='NbUnit' size='5' maxlength='5'></td>";
				
				// Avenant ou pas ?
				echo $td . "Avenant <input type='checkbox' name='Avenant'></td>";
				
				// Etat Facturation
				echo $td;
				
				echo "<select name='id_facturation' id='id_facturation' onchange='go()'>";
					echo "<option			value='0'>".$LANG["bestmanagement"]["facturation_contrat"][0]."</option>";
					echo "<option selected	value='1'>".$LANG["bestmanagement"]["facturation_contrat"][1]."</option>";
					echo "<option			value='2'>".$LANG["bestmanagement"]["facturation_contrat"][2]."</option>";
				echo "</select>";


				echo "</td>";
				// API
				echo $td . "<div id='api' style='display:inline'>";
				echo "</div></td>";
				echo "</tr>";
				
				// Commentaires
				echo $tr . "<td colspan='2'><input type='text' name='Comments' size='40' maxlength='255' value='".$LANG['common'][25]."' onfocus=\"this.value='';\"></td>";
				echo "<td colspan='2' align='center'><input type=\"submit\" name=\"addPurchase\" class=\"submit\" value=\"".$LANG["buttons"][51]."\" ></td>";
				echo "</tr>";
				echo "</table>";
			echo "</form>";
		}
	} // AddPurchase()

	/**
	 * V�rifie si les p�riodes sont vides
	 *
	 * @return boolean : true si z�ro achat
	**/
	function checkEmptyPeriode()
	{
		global $DB;
		
		$date_deb = $this->dateDeb();
		// requ�te sur l'historique des achats du contrat
		$query = "SELECT COUNT(*) Total
				  FROM glpi_plugin_bestmanagement_achat
				  WHERE ID_Compteur IS NOT NULL
					AND ID_Contrat = $this->id
					AND date_deb != '$date_deb'";
		
		if($resultat = $DB->query($query))
			$row = $DB->fetch_assoc($resultat);

		return ($row["Total"] == 0) ? true : false;
	} // checkEmptyPeriode()
	
	/**
	 * Retourne les lignes du tableau de bord pour le contrat en cours
	 *
	 * @return <tr> <td> ... </td> </tr>
	**/
	function currentRecap($tr1=null, $tr2=null)
	{
		$lignes = "";
		//-------------------------------------------------
		// Pr�paration des requ�tes
		// Les tableaux sont index�s selon l'ID du compteur
		//-------------------------------------------------
		$tab_achat	= $this->prepareTab("achat");
		$tab_report	= $this->prepareTab("report");
		$tab_conso	= $this->prepareTabConso();
		$tab_restant= $this->prepareTabRestant();
		$info_compteur = $this->infoCompteur();
		
		// on adapte l'affichage
		$tdnormal	= "<td class='tab_bg_1'		align=center>";			// td normal
		$tdred		= "<td class='tab_bg_2_2'	align=center>";			// td rouge quand HrsRest < 0
		$tdredstrg	= "<td class='tab_bg_2_2'	align=center style='color:red;'><strong>";	// td rouge et HrsRest �crit en rouge+d�but gras
		
		// alternance de couleurs
		$tr1 = (isset($tr1)) ? $tr1 : "<tr>";
		$tr2 = (isset($tr2)) ? $tr2 : "<tr>";
		$tr = $tr1;
		
		// remplissage des lignes du tableau
		foreach(array_keys($tab_restant) as $key)
		{
			// v�rifications pour savoir si les valeurs existent
			$tab_achat[$key]	= isset($tab_achat[$key])	? $tab_achat[$key]	: 0;
			$tab_report[$key]	= isset($tab_report[$key])	? $tab_report[$key]	: 0;
			$tab_conso[$key]	= isset($tab_conso[$key])	? $tab_conso[$key]	: 0;
			$tab_restant[$key]	= isset($tab_restant[$key])	? $tab_restant[$key]: 0;
			// fin v�rification
			
			// on adapte le td. Si aucune heure achet�e ni report�e, ligne enti�rement rouge
			$td = ($tab_achat[$key] + $tab_report[$key] == 0) ? $tdred : $tdnormal;
			$td_compteur = $td;
			
			// s'il n'y a ni heure achet�e, report�e ou consomm�e on n'affiche pas la ligne
			if ($tab_achat[$key] == 0 && $tab_report[$key] == 0 && $tab_conso[$key] == 0) continue;
			
			$name = $this->giveCompteurName($key, $this->infoCompteur());
			
			if ($info_compteur["compteur"] == "priority" && isBgColor())	// couleur de fond pour les priorit�s
				$td_compteur = "<td align='center' style=\"background-color:".$_SESSION["glpipriority_$key"]."\">";
			
			$tr = ($tr == $tr1) ? $tr2 : $tr1;
			
			$lignes .= $tr;
			$lignes .= $td_compteur .	$name . "</td>";

			$lignes .= $td . $this->arrangeIfHours($tab_achat[$key]	, $info_compteur["unit"])	. "</td>";
			$lignes .= $td . $this->arrangeIfHours($tab_report[$key]	, $info_compteur["unit"])	. "</td>";
			$lignes .= $td . $this->arrangeIfHours($tab_conso[$key]	, $info_compteur["unit"])	. "</td>";
			$lignes .= ($tab_restant[$key] < 0) ? $tdredstrg  : $td;
			$lignes .= $this->arrangeIfHours($tab_restant[$key]		, $info_compteur["unit"]);
			$lignes .= ($tab_restant[$key] < 0) ? "</strong>" : "";

			// pour avoir le % : reste / (achat+report)
			$lignes .= ($tab_restant[$key] < 0) ? "" : " (".round(100*$tab_restant[$key]/($tab_achat[$key]+$tab_report[$key]),0)."%)";
			$lignes .= ($tab_restant[$key] < 0) ? "</strong>" : "";
		
			$lignes .= "</tr>";
		} // fin remplissage des lignes
		return $lignes;
		
	} // currentRecap()

	/**
	 * Retourne un tableau r�capitulatif
	 *
	 * @param $tabrecap : quel tableau on veut afficher
	 * sachant que c'est aussi la fonction que l'on va appeler
	 *
	 * @return <table>...</table>
	 *		or <div>...</div> en cas d'�chec
	**/
	function showTabRecap($tabrecap="currentRecap")
	{
		global $DB, $LANG;
		$tab = "";
		$info_compteur = $this->infoCompteur();
		
		$class = get_class($this);
		//if(!is_callable($class.'::'.$tabrecap))	// erreur dans la fonction � appeler
		//	return false;
		
		// cas o� on ne peut pas afficher l'historique global
		if ($tabrecap == "histRecap")
		{
			if ($this->nbPeriodes() == 1)	// si il n'y a qu'une p�riode
				return "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][12] . "</div>";
			else if ($this->checkEmptyPeriode())	// si les p�riodes sont vides
				return "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][23] . "</div>";
		}
		if($tabrecap == "currentRecap" && !$this->areSetValues())
			return "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][24] . "</div>";
			
		if(count($info_compteur) == 2)
		{
			// on nomme les colonnes du tableau, selon le compteur et les unit�s
			$colonnes = array();
			if ($info_compteur["compteur"] == "category")
				array_push($colonnes, $LANG['common'][36]);
			else
				array_push($colonnes, $LANG['joblist'][2] );
			
			// pour adapter l'affichage des colonnes
			$nb = ($info_compteur["unit"] == "nbtickets") ? 10 : 0;
			
			if ($this->isContratIllim())
				array_push($colonnes, $LANG["bestmanagement"]["tabrecap"][3 + $nb]);
			else
			array_push($colonnes, $LANG["bestmanagement"]["tabrecap"][1 + $nb],
								  $LANG["bestmanagement"]["tabrecap"][2 + $nb],
								  $LANG["bestmanagement"]["tabrecap"][3 + $nb],
								  $LANG["bestmanagement"]["tabrecap"][4 + $nb]);
			
			$tab .= "<table class='tab_cadre'>";
			$tab .= "<tr> <th colspan='5'>" . $LANG["bestmanagement"]["tabrecap"][0] . "</th> </tr>"; // titre
			
			$tab .= "<tr>";
			foreach ($colonnes as $col)
				$tab .= "<th>".$col."</th>";
			$tab .= "</tr>";
			
			$tab .= call_user_func(array($class,$tabrecap));    // appel de la fonction qui affiche le r�capitulatif
			
			$tab .= "</table>";
		}
		return $tab;
		
	} // showTabRecap()

	/**
	 * Retourne les lignes du tableau de bord pour les p�riodes ant�rieures
	 *
	 * @return <tr> <td> ... </td> </tr>
	**/
	function histRecap()
	{
		global $DB, $LANG;
		$lignes = "";
		
		$query = "SELECT *
				  FROM glpi_plugin_bestmanagement_historique
				  WHERE ID_Contrat = $this->id
				  ORDER BY date_deb, ID_Compteur";
				  
		$td	= "<td class='tab_bg_1' align='center'>";		// td normal
		$info_compteur = $this->infoCompteur();
		
		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				while ($row = $DB->fetch_assoc($resultat))
				{
					// rappel p�riode du contrat
					if (!isset($datedeb) || $datedeb != $row["date_deb"])
					{
						if (isset($datedeb))
							$lignes .= "<tr class='tab_bg_1'><td 'colspan='5'>&nbsp;</td></tr>";	// ligne vierge
							
						$lignes .= "<tr class='tab_bg_1'>";
						$lignes .= "<td colspan='5'>" . $LANG["bestmanagement"]["contrat"][8]. Html::convDate($row["date_deb"]);
						$lignes .= $LANG["bestmanagement"]["contrat"][9].Infocom::getWarrantyExpir($row["date_deb"],$row["duree"])."</td>";
						$lignes .= "</tr>";
					}
					
					$datedeb = $row["date_deb"];
					// v�rifications pour savoir si les valeurs existent
					$row["achat"]	= isset($row["achat"])	? $row["achat"]	: 0;
					$row["report"]	= isset($row["report"])	? $row["report"]: 0;
					$row["conso"]	= isset($row["conso"])	? $row["conso"]	: 0;
					$reste = $row["achat"] + $row["report"] - $row["conso"];
					// fin v�rification

					$name = $this->giveCompteurName($row["ID_Compteur"], $this->infoCompteur());
					
					$td_compteur = $td;
					if ($info_compteur["compteur"] == "priority" && isBgColor())	// couleur de fond pour les priorit�s
						$td_compteur = "<td align='center' style='background-color:".$_SESSION["glpipriority_".$row["ID_Compteur"].""]."'>";
					
					$lignes .= "<tr>";
					$lignes .= $td_compteur .	$name . "</td>";

					$lignes .= $td . $this->arrangeIfHours($row["achat"]	, $info_compteur["unit"])	. "</td>";
					$lignes .= $td . $this->arrangeIfHours($row["report"]	, $info_compteur["unit"])	. "</td>";
					$lignes .= $td . $this->arrangeIfHours($row["conso"]	, $info_compteur["unit"])	. "</td>";
					$lignes .= $td . $this->arrangeIfHours($reste			, $info_compteur["unit"]);

					$lignes .= "</tr>";
		} // fin remplissage des lignes
		return $lignes;
		
	} // histRecap()

	/**
	 * Retourne le nombre de p�riodes pour le contrat
	 *
	 * @return int : Nb de p�riodes
	**/
	function nbPeriodes()
	{
		global $DB;
		
		$query_nb = "SELECT COUNT(DISTINCT date_deb) NbPeriodes
					 FROM glpi_plugin_bestmanagement_achat
					 WHERE ID_Contrat = $this->id";
					 
		if($res = $DB->query($query_nb))
			if ($row = $DB->fetch_assoc($res))
				return $row["NbPeriodes"];
		return 0;
	} // nbPeriodes()
	
		/**
	 * Retourne vrai si le contrat est illimit�
	**/
	function isContratIllim()
	{
		global $DB;
		
		$query_nb = "SELECT t.illimite
                FROM glpi_contracts c
                LEFT JOIN glpi_plugin_bestmanagement_typecontrat t
                ON c.contracttypes_id = t.id
					      WHERE c.id = $this->id";
					 
		if($res = $DB->query($query_nb))
			if ($row = $DB->fetch_assoc($res))
				return ($row["t.illimite"] = 0);
				
		return false;
	} 
	
	/**
	 * Retourne le nombre d'achat pour le contrat
	 *
	 * @return int : Nb d'achats
	**/
	function nbAchats()
	{
		global $DB;

		$query_nb = "SELECT COUNT(*) NbAchats
					 FROM glpi_plugin_bestmanagement_achat
					 WHERE ID_Contrat = $this->id
						AND ID_Compteur IS NOT NULL";

         if($res = $DB->query($query_nb))
			if ($row = $DB->fetch_assoc($res))
				return $row["NbAchats"];
		return 0;
	} // nbAchats()
	
	/**
	 * Indique si le contrat est encore valable
	 *
	 * @return boolean : true si encore valable
	**/
	function isAvailable()
	{
		global $DB;

		$query_nb = "SELECT is_deleted
					 FROM glpi_contracts
					 WHERE id = $this->id";

         if($res = $DB->query($query_nb))
			if ($row = $DB->fetch_assoc($res))
				return !$row["is_deleted"];
		
	} // isAvailable()
	
	/**
	 * Retourne le nom du compteur
	 *
	 * @param key : ID du compteur
	 * @param info_compteur : array
	 *
	 * @return Nom du Compteur
	**/
	function giveCompteurName($key, $info_compteur)
	{
		global $LANG;
		
		if ($info_compteur["compteur"] == "priority")	// d�finit le nom des priorit�s
		{
			if ($key == 0) continue;
			$name = "";	// nom du premier td
			$name = ($key == 5) ? $LANG["help"][3] : $name;
			$name = ($key == 4) ? $LANG["help"][4] : $name;
			$name = ($key == 3) ? $LANG["help"][5] : $name;
			$name = ($key == 2) ? $LANG["help"][6] : $name;
			$name = ($key == 1) ? $LANG["help"][7] : $name;
		}
		else // d�finit le nom des cat�gories
		{
			$cat_name = $this->tabCatName();
			if ($key == 0) $name = "(vide)";
			else $name = (isset($cat_name[$key])) ? $cat_name[$key] : "(non d&eacute;finie)";
		}
		return $name;
	} // giveCompteurName()

	/**
	 * Retourne le tableau associatif id => nom de cat�gorie
	 *
	 * @return array
	**/
	function tabCatName()
	{
		global $DB;
		
		$query_cat = "SELECT ID, name FROM glpi_taskcategories";
		if($res = $DB->query($query_cat))
			if($DB->numrows($res) > 0)
				while ($row = $DB->fetch_assoc($res))
					$cat_name[$row["ID"]] = $row["name"];
		
		return $cat_name;
	} // tabCatName()
	
	/**
	 * Si la chaine est une heure
	 * cette fonction la transforme pour faciliter la lisisbilit� de l'heure
	 *
	 * @param valeur sous forme 99,99
	 * @return string (si heure, sous forme HH:MM)
	**/
	function arrangeIfHours($val, $unit)
	{
		 if ($unit != "hour") return $val;
		
		$neg = ($val < 0) ? true : false;
		$val = round($val+0,2);
		$val *= ($neg) ? (-1) : 1;
		$h = floor($val); // heures
		
		$h += ($val < 0) ? 1 : 0;
		$m = round(($val - $h ) * 60); // minutes
		if ($m >= 0 && $m < 10) $m = "0" . $m;

		return (($neg) ? "-" : "") . $h . ":" . $m;
	
	} // arrangeHours()

	/**
	 * Retourne les informations sur le
	 * type de compteur et d'unit�s
	 *
	 * @return array
	**/
	function infoCompteur()
	{
		global $DB;
		
		$query_compteur = "SELECT DISTINCT Type_Compteur, Type_Unit
						   FROM glpi_plugin_bestmanagement_achat
						   WHERE ID_Contrat = $this->id";

		$info_compteur = array();
		
		if($resultat = $DB->query($query_compteur))
			if($DB->numrows($resultat) > 0)
			{
				$row = $DB->fetch_assoc($resultat);
				$info_compteur["compteur"]	= $row["Type_Compteur"];	// Type du compteur	(category, priorit�)
				$info_compteur["unit"]		= $row["Type_Unit"];		// Type d'unit�		(heures, nbtickets)
			}
		
		return $info_compteur;
	} // infoCompteur()
	
	/**
	 * Retourne le type du contrat, pr�fix� de son entit�
	 *
	 * @return string
	**/
	function giveRealName()
	{
		global $DB, $LANG;
		
		$query = "SELECT IFNULL(entite.name,'Entite Racine') entitename, IFNULL(type.name, '(Pas de type)') contratname
				  FROM glpi_contracts contrat
				    LEFT JOIN glpi_entities entite
						ON contrat.entities_id = entite.id
							LEFT JOIN glpi_contracttypes type
								ON contrat.contracttypes_id = type.id
				  WHERE contrat.id = $this->id";
		
		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				while($row = $DB->fetch_assoc($resultat))
					return $row["entitename"] . " - " . $row["contratname"];
		
		return "(ID " . $LANG['financial'][1] . " : $this->id)";
	} // giveRealName()
	
	/**
	 * Retourne la gestion du contrat
	 *
	 * @return string
	**/
	function giveManagement()
	{
		global $LANG;
		
		$cpt = $this->infoCompteur();
		
		if (!count($cpt)) return "<span class='red'>".$LANG["bestmanagement"]["allrecap"][10]."</span>";

		$unit		= ($cpt["unit"] == "hour")			? $LANG["bestmanagement"]["allrecap"][6] : $LANG["bestmanagement"]["allrecap"][7];
		$compteur	= ($cpt["compteur"] == "category")	? $LANG["bestmanagement"]["allrecap"][8] : $LANG["bestmanagement"]["allrecap"][9];
		
		return $unit . $compteur;
	} // giveManagement()
	
	/**
	 * Retourne la gestion du contrat (adapt�e au PDF)
	 *
	 * @return string
	**/
	function giveManagementForPDF()
	{
		global $LANG;
		
		$cpt = $this->infoCompteur();
		
		if (!count($cpt)) return $LANG["bestmanagement"]["pdf"][5];

		$unit		= ($cpt["unit"] == "hour")			? $LANG["bestmanagement"]["pdf"][1] : $LANG["bestmanagement"]["pdf"][2];
		$compteur	= ($cpt["compteur"] == "category")	? $LANG["bestmanagement"]["pdf"][3] : $LANG["bestmanagement"]["pdf"][3];
		
		return $unit . $compteur;
	} // giveManagementForPDF()
	
	/**
	 * Effectue la requ�te d'achat et de report
	 * pour ce contrat.
	 *
	 * Retourne un tableau associatif :
	 * compteur	=> unit�s
	 *
	 * @return array
	**/
	function prepareTab($what, $avenant=null)
	{
		global $DB;
		
		$query = "";
		switch ($what)
		{
		  case "achat" :
			// Requ�tes associ�es au report des unit�s
			// on v�rifie d'abort qu'il y a report
			$is_achat = "SELECT *
						  FROM glpi_plugin_bestmanagement_achat
						  WHERE ID_Contrat = $this->id
							AND ID_Compteur IS NOT NULL";
			
			if($res_achat = $DB->query($is_achat))
				if($DB->numrows($res_achat) > 0)
				{
					$row = $DB->fetch_assoc($res_achat);

					// requ�te sur les achats du contrat en cours
					// Selon un compteur, on fait la somme des unit�s achet�es
					$query =   "SELECT ID_Compteur CptID, SUM(UnitBought) Unit
								FROM glpi_plugin_bestmanagement_achat achat, glpi_contracts contrat
								WHERE ID_Contrat = $this->id
									AND achat.ID_Contrat = contrat.ID
									AND achat.date_deb = contrat.begin_date
									AND achat.ID_Compteur IS NOT NULL
									AND UnitBought IS NOT NULL
									$avenant
								GROUP BY ID_Compteur";
				}
			break;
		
		  case "report" :
			// Requ�tes associ�es au report des unit�s
			// on v�rifie d'abort qu'il y a report
			$is_report = "SELECT report_credit
						  FROM glpi_plugin_bestmanagement_reconduction reconduction
						  WHERE ID_Contrat = $this->id
							AND begin_date IN (SELECT MAX(begin_date)
											  FROM glpi_plugin_bestmanagement_reconduction
											  WHERE ID_Contrat = $this->id)";
			
			if($res_report = $DB->query($is_report))
				if($DB->numrows($res_report) > 0)
				{
					$row = $DB->fetch_assoc($res_report);
			
					if (!$row["report_credit"])	// il y a report
						// Selon un compteur, on fait la somme des unit�s report�es
						$query = "SELECT ID_Compteur CptID, Nb_Unit Unit
								  FROM glpi_plugin_bestmanagement_reconduction reconduction,
										glpi_plugin_bestmanagement_report report
								  WHERE reconduction.id = report.ID_Reconduction
									AND ID_Contrat = $this->id
									AND begin_date IN (SELECT MAX(begin_date)
													  FROM glpi_plugin_bestmanagement_reconduction
													  WHERE ID_Contrat = $this->id)";
				}

		} // swith
		
		// Puis on stocke le r�sultat dans le tableau $tab
		$tab = array();
		
		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				while($row = $DB->fetch_assoc($resultat))
					$tab[$row["CptID"]] = $row["Unit"];
					
		return $tab;
	} // prepareTab()
	

	/**
	 * Effectue la requ�te des consommations pour ce contrat
	 * et retourne un tableau associatif :
	 * compteur	=> unit�s consomm�es
	 *
	 * @return array
	**/
	function prepareTabConso()
	{
		global $DB;
		$info_compteur = $this->infoCompteur();
		
		// Requ�tes associ�es � la consommation
		// Selon un compteur, on fait la somme des unit�s consomm�es
		if ($info_compteur["compteur"] == "category") // compteur par cat�gorie
		{
			if ($info_compteur["unit"] == "hour") // type par heure
			{
				$conso = "SELECT task.taskcategories_id CptID, SUM(task.actiontime) UnitC
					  FROM glpi_tickettasks task, glpi_plugin_bestmanagement_link_ticketcontrat link,
							glpi_contracts contrat
					  WHERE  task.tickets_id = link.ID_Ticket
						AND link.ID_Contrat = contrat.id
						AND contrat.id = $this->id
						AND task.date BETWEEN contrat.begin_date
							AND DATE_ADD(contrat.begin_date, INTERVAL contrat.duration MONTH)
					   GROUP BY task.taskcategories_id";
			}
			else // type par nombre de tickets
			{ // cat par nb de tickets
				// TODO
				$conso = "";
			}
		}
		else	// compteur par priorit�
		{
			if ($info_compteur["unit"] == "hour")
				$AGREG = "SUM(ticket.actiontime)";
			else
				$AGREG = "COUNT(ticket.id)";
			
			$conso = "SELECT ticket.priority CptID, $AGREG UnitC
				  FROM glpi_plugin_bestmanagement_link_ticketcontrat link,
						glpi_contracts contrat, glpi_tickets ticket
				  WHERE  link.ID_Contrat = contrat.id
					AND link.ID_Ticket = ticket.id
					AND contrat.id = $this->id
					AND ticket.date BETWEEN contrat.begin_date
						AND DATE_ADD(contrat.begin_date, INTERVAL contrat.duration MONTH)
				   GROUP BY ticket.priority";
		}
		
		// Puis on stocke le r�sultat dans le tableau $tab_conso
		$tab_conso = array();
		if($result = $DB->query($conso))
			if($DB->numrows($result) > 0)
				while($row = $DB->fetch_assoc($result))
					$tab_conso[$row["CptID"]] = round($row["UnitC"],2);
					
		return $tab_conso;
		
	} // prepareTabConso()
	
	/**
	 * Effectue la requ�te des unit�s restantes pour ce contrat
	 * et retourne un tableau associatif :
	 * compteur	=> unit�s restantes
	 *
	 * @return array
	**/
	function prepareTabRestant()
	{
		$tab_achat	= $this->prepareTab("achat");		// Tableau r�capitulatif des achats
		$tab_report	= $this->prepareTab("report");		// Tableau r�capitulatif des reports
		$tab_conso	= $this->prepareTabConso();			// Tableau r�capitulatif des consommations
		
		$tab_total = array();	// tableau : achats + report
		foreach (array_keys($tab_achat) as $key_a)	// ajout du report, cas o� il ya des achats
		{
			if(array_key_exists($key_a, $tab_report))
				$tab_total[$key_a] = $tab_achat[$key_a] + $tab_report [$key_a];
			else
				$tab_total[$key_a] = $tab_achat[$key_a];
		}
		foreach (array_keys($tab_report) as $key_a)	// ajout du report, cas o� il n'y a pas d'achat
		{
			if(!array_key_exists($key_a, $tab_achat))
				$tab_total[$key_a] = $tab_report [$key_a];
		}
		
		$tab_restant = array();	// tableau : total - conso
		foreach (array_keys($tab_total) as $key_a)
		{
			if(array_key_exists($key_a, $tab_conso)) // on r�cup�re le total, retranch� des consommations
				$tab_restant[$key_a] = $tab_total[$key_a] - $tab_conso[$key_a];
			else
				$tab_restant[$key_a] = $tab_total[$key_a];	// cas o� il n'y a pas de consommation
		}
		foreach (array_keys($tab_conso) as $key_c)
		{
			if(!array_key_exists($key_c, $tab_total))	// cas o� il n'y a pas d'achat ni report mais consommation
				$tab_restant[$key_c] = -$tab_conso[$key_c];
		}
		return $tab_restant;
		
	} // prepareTabRestant()
	
	/**
	 * Retourne la date de d�but du contrat
	 *
	 * @return string (au format date)
	**/
	function dateDeb()
	{
		global $DB;
		
		$query = "SELECT begin_date
				  FROM glpi_contracts
				  WHERE id = $this->id";

		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				if ($row = $DB->fetch_assoc($resultat))
					return $row["begin_date"];
		
		return "";
	} // dateDeb()

	/**
	 * Retourne la dur�e du contrat
	 *
	 * @return int (en mois)
	**/
	function duree()
	{
		global $DB;
		
		$query = "SELECT duration
				  FROM glpi_contracts
				  WHERE id = $this->id";

		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				if ($row = $DB->fetch_assoc($resultat))
					return $row["duration"];
		return 0;
	} // duree()

	/**
	 * Retourne la date de fin du contrat
	 *
	 * @return string (au format date)
	**/
	function dateFin()
	{   
		return date("Y-m-d", strtotime($this->dateDeb() . "+". $this->duree() . " MONTH"));	
	} // dateFin()
		
	/**
	 * Retourne le nom du contrat
	 *
	 * @return string
	**/
	function name()
	{
		global $DB;
		
		$query = "SELECT name
				  FROM glpi_contracts
				  WHERE id = $this->id";

		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				if ($row = $DB->fetch_assoc($resultat))
					return $row["name"];
		return "";
	} // name()
	

	/**
	 * Retourne le num�ro du contrat
	 *
	 * @return string
	**/
	function number()
	{
		global $DB, $LANG;
		
		$query = "SELECT num
				  FROM glpi_contracts
				  WHERE id = $this->id";

		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				if ($row = $DB->fetch_assoc($resultat))
					return $row["num"];
		return "";
	} // number()
	
	/**
	 * Retourne l'interlocuteur
	 *
	 * @return string
	**/
	function giveContact()
	{
		global $DB, $LANG;
		
		$query = "SELECT firstname, realname, phone, mobile
				  FROM glpi_contracts contrat
					LEFT JOIN glpi_users user
						ON contrat.	entities_id = user.entities_id
				  WHERE contrat.id = $this->id";

		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				if ($row = $DB->fetch_assoc($resultat))
					return $LANG["bestmanagement"]["allrecap"][14] . " : " .
						   $row["firstname"] . " " . $row["realname"] . "&nbsp;&nbsp;&nbsp;" .
						   $row["phone"] . "&nbsp;&nbsp;&nbsp;" . $row["mobile"];
		return $LANG["bestmanagement"]["allrecap"][15];
	} // giveContact()
	
	/**
	 * Retourne le formulaire de reconduction
	 *
	 * @return Nothing(Display)
	**/
	function renewal()
	{
		global $DB, $CFG_GLPI, $LANG;
		
		if ($_SESSION["glpi_currenttime"] < $this->dateFin())	// contrat en cours
			echo $LANG["bestmanagement"]["renewal"][0] . Html::convDate($this->dateFin());
		else
		{
			echo "<div class='center'>";
			echo "<strong>" . $LANG["bestmanagement"]["renewal"][1] . "</strong>";
			echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/bestmanagement.form.php\">";
			echo "<input type='hidden' name='id_contrat'	value='$this->id'>";
			echo "<table class='tab_cadre' style='margin-top: 1em;'>";
			if ($this->areSetValues())	// on a des valeurs
			{
				//----------------------------------------------
				// Pr�paration des requ�tes
				// Le tableau est index� selon l'ID du compteur
				//----------------------------------------------
				$tab_restant= $this->prepareTabRestant();
				
				$info_compteur = $this->infoCompteur();
				
				if ($info_compteur["compteur"] == "category")
					$compteur = $LANG['common'][36];		// cat
				else
					$compteur = $LANG['joblist'][2];		// prio
				
				echo "<tr><th align=center colspan='2'>".$LANG["bestmanagement"]["renewal"][2]."</th>";
				echo "<tr><th align=center>" . $compteur . "</th>";
				echo "<th>".$LANG["bestmanagement"]["allrecap"][3]."</th></tr>"; // fin Titre
				$tr = "<tr class='tab_bg_2'>";
				$td = "<td align=center>";
				// remplissage des lignes du tableau
				foreach(array_keys($tab_restant) as $key)
				{
					// s'il n'y a ni heure achet�e, report�e ou consomm�e on n'affiche pas la ligne
					if ($key == 0 || $tab_restant[$key] == 0) continue;
					
					echo $tr;
					echo $td . $this->giveCompteurName($key, $info_compteur)					. "</td>";
					echo ($tab_restant[$key] < 0) ? "<td class='red tab_bg_2_2'	align=center><strong>"  : $td;
					echo $this->arrangeIfHours($tab_restant[$key], $info_compteur["unit"]). "</td>";
					echo "</tr>";
				}

				echo $tr;
				// Reporter les heures ?
				echo $td . $LANG["bestmanagement"]["renewal"][3] . "<input type='checkbox' name='report'></td>";

			}
			else	// aucun report vu que pas de valeurs
				echo "<tr><td>" . $LANG["bestmanagement"]["renewal"][5] . "</td>";
			
			echo "<td align='center'><input type=\"submit\" name=\"addRenewal\" class=\"submit\" value=\"".$LANG["bestmanagement"]["renewal"][4]."\" >";
			echo "&nbsp;<input type=\"submit\" name=\"deleteContrat\" class=\"submit\" value=\"".$LANG["bestmanagement"]["renewal"][6]."\" ></td>";
			echo "</tr>";
			echo "</table>";
			echo "</form>";
			echo "</div>";
		}		
	} // renewal()

	/**
	 * Retourne le formulaire de facturation
	 *
	 * @return Nothing(Display)
	**/
	function facture()
	{
		global $DB, $CFG_GLPI, $LANG;
		
		if($this->nbAchats() == 0)
			echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][16] . "</div>";
		else
		{
			echo "<div>";
			echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/bestmanagement.form.php\">";
				$this->historical(true);
				echo "<input type='hidden' name='id_contrat'	value='$this->id'>";
				echo "<table class='tab_cadre'>";
				echo "<tr class='tab_bg_2'><td>".$LANG["bestmanagement"]["facturation"][0]." : </td>";
				echo "<td>";
				$this->inputFacturation();
				echo "</td>";
				echo "<td align='center'><input type=\"submit\" name=\"addFacturation\" class=\"submit\" value=\"".$LANG['buttons'][51]."\" ></td>";
				echo "</tr>";
				echo "</table>";
			echo "</form>";
			echo "</div>";
		}
	} // facture()
	
	/**
	 * Affiche la zone de texte correspondant au 
	 * num�ro de facture.
	 *
	 * @return Nothing(Display)
	**/
	function inputFacturation()
	{
		global $LANG;

		echo "<input type='text' name='NumFact' size='15'
				value='" . $LANG["bestmanagement"]["facturation_contrat"][3] . "' onfocus=\"this.value='';\">";
	} // inputFacturation()

	/**
	 * Retourne l'�tat de facturation du contrat
	 * 0 => factur�
	 * else => non factur�
	 *
	 * @return int
	**/
	function etatFact()
	{
		global $DB;
		
		$query = "SELECT SUM(etat_fact) Etat
				  FROM glpi_plugin_bestmanagement_achat achat
					INNER JOIN glpi_contracts contrat
						ON achat.ID_Contrat = contrat.ID
				  WHERE achat.ID_Contrat = $this->id
					AND achat.date_deb = contrat.begin_date
					AND ID_Compteur IS NOT NULL
					AND UnitBought IS NOT NULL";

		if($resultat = $DB->query($query))
			if($DB->numrows($resultat) > 0)
				$row = $DB->fetch_assoc($resultat);
		
		$etat = (isset($row["Etat"]) && $row["Etat"] != 0) ? $row["Etat"] : 0;
		
		return $etat;
	} // etatFact()
	
	//-------//
	// CRON  //
	//-------//
	/**
	* Give localized information about 1 task
	*
	* @param $name of the task
	*
	* @return array of strings
	*/
	static function cronInfo($name)
	{
		global $LANG;
		
		switch ($name)
		{
		  case 'SQL' :
			return array('description' => $LANG["bestmanagement"]["cron"][4]);
		  case 'Verif' :
			return array('description' => $LANG["bestmanagement"]["cron"][5]);
		}
		return array();
	} // cronInfo()
	
	/**
	 * Retourne les lignes html pour le mail
	 *
	 * @return <tr> <td> ... </td> </tr>
	**/
	function ContratMailing($colonnes=array(), $colors)
	{
		global $LANG, $CFG_GLPI;
		$lignes = "";
		
		//================//
		// Premi�re ligne //
		//================//
		$td2= "<td colspan='2'>";	// td normal colspan=2
		$td3= "<td align='left'; colspan='3'>";	// td normal colspan=3
		
		$lignes .= "<tr style=\"background-color:" . $colors["tr2"] . "; color:#000000;\">";
		// lien vers le contrat
		$protocole = strstr($_SERVER["HTTP_REFERER"], $CFG_GLPI["root_doc"], true);
		
		$destination  = $protocole . $CFG_GLPI["root_doc"];
		$destination .= "/front/contract.form.php?id=$this->id";
		
		$lignes .= $td3."<a href=\"$destination\">".$this->number() . " - " . $this->giveRealName()."</a>";
		// date de fin format�e
		$lignes .= "&nbsp;&nbsp;(" . $LANG["bestmanagement"]["allrecap"][11] . Html::convDate($this->dateFin()) . ")</td>";
		$lignes .= $td2 . $this->giveManagement() . "</td>";
		$lignes .= "</tr>";
		
		//================//
		// Deuxi�me ligne //
		//================//
		$lignes .= "<tr style=\"background-color:" . $colors["tr1"] . "; color:#000000;\">";
		// contact
		$lignes .= "<td colspan='5' align='left'><i>" . $this->giveContact() . "</i></td>";

		$lignes .= "</tr>";
		
		// alternance de couleur pour l'affichage
		$tr1 = "<tr style=\"background-color:" . $colors["tr2"] . "; color:#000000;\">";
		$tr2 = "<tr style=\"background-color:" . $colors["tr1"] . "; color:#000000;\">";
		
		//=======================//
		// Tableau r�capitulatif //
		//=======================//
		if ($this->nbAchats())
		{
			$lignes .= $tr1;
			foreach ($colonnes as $col)
				$lignes .= "<td>".$col."</td>";
			$lignes .= "</tr>";
			
			$lignes .= $this->currentRecap($tr1, $tr2);	// tableau r�capitulatif
		}
		
		return $lignes;
	} // ContratMailing()
	
	/**
	* Execute la t�che d'envoi de mails
	*
	* @param $task Object of CronTask class for log / stat
	*
	* @return interger
	*    >0 : done
	*    <0 : to be run again (not finished)
	*     0 : nothing to do
	*/
	static function cronVerif($task)
	{
		global $DB, $CFG_GLPI, $LANG;
		
		if (!$CFG_GLPI["use_mailing"])
			return 0;
		
		$body = "";
		
		$body .= "<html>";
		$body .= "<head><style  type='text/css'>body {font-family: Verdana;font-size: 11px;text-align: left;}";
		$body .= "table {border: 1px solid #cccccc; border-color:black; border-collapse:collapse}";
		$body .= "table {class='tab_cadre'}";
		$body .= "th {text-align: center; border-width:1px; border-style:solid; padding: 2px;}";
		$body .= "tr {text-align: center; border-width:1px; border-style:solid; padding: 2px;}";
		$body .= "a {color: black; font-weight : bold }";
		$body .= "</style></head>";
		$body .="<body>";
		
		$all_contrats = array();
		
		if (whichContratSend("contratended"))
		{
			$query_contracts_end = "SELECT *
									FROM glpi_contracts
									WHERE begin_date IS NOT NULL AND duration IS NOT NULL
										AND DATE_ADD(begin_date, INTERVAL duration MONTH) BETWEEN
											DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND CURDATE()
									ORDER BY DATE_ADD(begin_date, INTERVAL duration MONTH)";
			
			if($res_end = $DB->query($query_contracts_end))
				if($DB->numrows($res_end) > 0)
					while ($row = $DB->fetch_assoc($res_end))
						$all_contrats[0][] = $row["id"];
		} // contrat_ended
		if (whichContratSend("contratending"))
		{
			$query_contracts_ending = "SELECT *
									   FROM glpi_contracts
									   WHERE begin_date IS NOT NULL AND duration IS NOT NULL
										AND DATE_ADD(begin_date, INTERVAL duration MONTH) BETWEEN
											CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
									   ORDER BY DATE_ADD(begin_date, INTERVAL duration MONTH)";
			
			if($res_ending = $DB->query($query_contracts_ending))
				if($DB->numrows($res_ending) > 0)
					while ($row = $DB->fetch_assoc($res_ending))
						$all_contrats[1][] = $row["id"];
		} // contrat_ending
		if (whichContratSend("consoexceeded"))
		{
			$contrats_deja_selected = verifDoublonsQuery($all_contrats);
			
			$query_id_contracts = "SELECT id
								   FROM glpi_contracts
								   WHERE is_deleted = 0
									AND begin_date IS NOT NULL AND duration IS NOT NULL
									$contrats_deja_selected";
			
			if($res = $DB->query($query_id_contracts))
				if($DB->numrows($res) > 0)
					while ($row = $DB->fetch_assoc($res))
					{
						$contrat = new PluginBestmanagementContrat($row["id"]);
						$info_compteur = $contrat->infoCompteur();
						if (count($info_compteur) == 0) continue;
						
						$tab_restant= $contrat->prepareTabRestant();
						
						$exit = false;
						// v�rification des r�sultats
						foreach(array_keys($tab_restant) as $key)
						{
							// si on a d�j� le contrat ou ni heure achet�e ni report�e ou consomm�e
							if ($exit || $key == 0 || $tab_restant[$key] == 0) continue;
							
							if ($tab_restant[$key] < 0)	// consommation n�gative
							{
								$all_contrats[2][] = $row["id"];
								$exit = true;	// on a le contrat, pas besoin de le reins�rer dans $all_contrats
							}
						}
					} // while
		} // contrat_consoexceeded
		if (whichContratSend("ratioexceeded"))
		{
			$contrats_deja_selected = verifDoublonsQuery($all_contrats);
			
			$query_id_contracts = "SELECT id
								   FROM glpi_contracts
								   WHERE is_deleted = 0
									AND begin_date IS NOT NULL AND duration IS NOT NULL
									$contrats_deja_selected";
			
			$ratio = getItem("ratiocontrat", "glpi_plugin_bestmanagement_config");
			
			if($res = $DB->query($query_id_contracts))
				if($DB->numrows($res) > 0)
					while ($row = $DB->fetch_assoc($res))
					{
						$contrat = new PluginBestmanagementContrat($row["id"]);
						$info_compteur = $contrat->infoCompteur();
						if (count($info_compteur) == 0) continue;
						
						//-------------------------------------------------
						// Pr�paration des requ�tes
						// Les tableau sont index�s selon l'ID du compteur
						//-------------------------------------------------
						$tab_achat	= $contrat->prepareTab("achat");
						$tab_report	= $contrat->prepareTab("report");
						$tab_restant= $contrat->prepareTabRestant();
						
						$exit = false;
						// v�rification des r�sultats
						foreach(array_keys($tab_restant) as $key)
						{
							// si on a d�j� le contrat ou ni heure achet�e ni report�e ou consomm�e
							if ($exit || $key == 0 || $tab_restant[$key] == 0) continue;
							
							// si les valeurs ne sont pas d�finies :
							$tab_report[$key]	= isset($tab_report[$key])	? $tab_report[$key]	: 0;
							$tab_achat[$key]	= isset($tab_achat[$key])	? $tab_achat[$key]	: 0;
							
							if (($tab_restant[$key]/$tab_achat[$key]+$tab_report[$key])*100 <= $ratio)
							{	// ratio d�pass�
								$all_contrats[3][] = $row["id"];
								$exit = true;	// on a le contrat, pas besoin de le reins�rer dans $all_contrats
							}
						}
					} // while
		} // contrat_ratioexceeded
		
		if (count($all_contrats) == 0) return 0;	// si pas de contrat, pas de mail
		
		$colonnes = array($LANG["bestmanagement"]["allrecap"][2],
						  $LANG["bestmanagement"]["allrecap"][3],
						  $LANG["bestmanagement"]["allrecap"][4],
						  $LANG["bestmanagement"]["allrecap"][5]);
		
		foreach(array_keys($all_contrats) as $key)
		{
			$colors = getMailColors();	// d�finit les couleurs des tableaux
			
			$body .= "<table>";
			// titre bleu fonc�
			$body .= "<tr><th style='background-color:" . $colors["titre"] . ";' colspan='5'>".$LANG["bestmanagement"]["cron"][$key]."</th></tr>";
			
			foreach($all_contrats[$key] as $id)
			{
				if (isset($un_contrat))	// ligne vierge pour s�parer les contrats
					$body .= "<tr style=\"background-color:#ffffff;\"><td colspan='5'>&nbsp;</td></tr>";

				$un_contrat = new PluginBestmanagementContrat($id);
				
				$info_cpt = $un_contrat->infoCompteur();
				
				if (isset($info_cpt["compteur"]))
				{
					if ($info_cpt["compteur"] == "category")
						$compteur = $LANG['common'][36];		// cat
					else
						$compteur = $LANG['joblist'][2];		// prio
					
					array_unshift($colonnes, $compteur);
				}
				else
					array_unshift($colonnes, "");
				
				$body .= $un_contrat->ContratMailing($colonnes, $colors);
				array_shift($colonnes);
				$task->addVolume(1);
				$task->log($LANG["bestmanagement"]["cron"][10+$key] . " : <a href=\"".Toolbox::getItemTypeFormURL("Contract")."?id=$id\">".$un_contrat->name()."</a>"); //TODO
			} // foreach 2
			$body .= "</table><br><br>";
			
			if (isset($un_contrat)) unset($un_contrat);
			
		} // foreach 1

		$body .= "</table><br><br>";
		$body.="</body></html>";
		
		// on r�cup�re les adresses e-mail
		foreach (getAdresses() as $ad)
		{
			$mmail= new NotificationMail;
			$mmail->From		= $CFG_GLPI["admin_email"];
			$mmail->FromName	= $CFG_GLPI["admin_email"];
			$mmail->AddAddress($ad, "GLPI");
			$mmail->Subject		= $LANG["bestmanagement"]["cron"][6];
			$mmail->Body		= $body;
			$mmail->isHTML(true);
			$mmail->Send();
		}
		return 1;
		
	} // cronVerif

	/**
	* Execute la t�che de sauvegarde de la BD
	*
	* @param $task Object of CronTask class for log / stat
	*
	* @return interger
	*    >0 : done
	*    <0 : to be run again (not finished)
	*     0 : nothing to do
	*/
	static function cronSQL($task)
	{
		global $DB, $CFG_GLPI, $LANG, $TPSCOUR;

		$time_file = date("Y-m-d-H-i");
		// $dumpFile, fichier source
		$dumpFile = GLPI_DUMP_DIR . "/glpi-".GLPI_VERSION."-$time_file.sql";
		
		$fileHandle = fopen($dumpFile, "a");
		
		$i = 0;
		$j = -1;
		$rowlimit = 5;
		
		$cur_time = date("Y-m-d H:i");
		$todump = "#GLPI Dump database on $cur_time\n";
		fwrite ($fileHandle,$todump);
		

		$result = $DB->list_tables();
		$numtab = 0;
		while ($t = $DB->fetch_array($result))
		{
			// on se  limite aux tables prefixees _glpi
			if (strstr($t[0],"glpi_"))
			{
				$tables[$numtab] = $t[0];
				$numtab++;
			}
		}
		
		for ( ; $i<$numtab ; $i++)
		{
			// Dump de la structure table
			if ($j == -1)
			{
				$todump = "\n".get_def2($DB,$tables[$i]);
				fwrite ($fileHandle,$todump);
				$j++;
			}

			$fin = 0;
			while (!$fin)
			{
				$todump = get_content2($DB,$tables[$i],$j,$rowlimit);
				$rowtodump = substr_count($todump, "INSERT INTO");
				if ($rowtodump >0)
				{
					fwrite ($fileHandle,$todump);
					$j += $rowlimit;
					if ($rowtodump<$rowlimit)
						$fin = 1;
				}
				else
				{
					$fin = 1;
					$j = -1;
				}
			} // while
			if ($fin)
				$j = -1;
		} // for
		
		$nb = 0;
		if ($DB->error())
			$nb = 1;
		else
			$task->addVolume(1);
		
		fclose($fileHandle);
		
		$task->log($LANG["bestmanagement"]["sql"][$nb] ." $dumpFile");
		return 1;
		
	} // cronSQL

	/**
	* V�rifie si des valeurs ont �t� saisies
	* dans achat, report et consommation
	*
	* @return boolean
	*/
	function areSetValues()
	{
		$val = (count($this->prepareTabRestant()) == 0) ? false : true;
		
		// cas exceptionnel : si on a des unit�s nulles (par exemple en cas de r�ajustement)
		// on v�rifie qu'on ait au moins une unit� non nulle, sinon return false
		if ($val)
			foreach ($this->prepareTabRestant() as $unit)
				if ($unit == 0)	$val = false;
				else	return true;

		return $val;
	} // areSetValues()
	
	
} // class PluginContrat

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
	 * Retourne le select correspondant aux �tats de facturation
	 *
	 * @return <select> ... </select>
	**/
	function selectEtatFacture()
	{
		global $DB, $LANG;
		
		$etat = $this->etatFact();
		$fact = array();
		$fact[0] = "selected";	// par d�faut � non factur�
		
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
	 * Retourne l'�tat de facturation
	 * 0 => non factur�
	 * 1 => factur� sous contrat
	 * 2 => factur� hors contrat
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
	 * d'un ticket, pour l'affectation � un contrat
	 *
	 * @return Nothing(Display)
	**/
	function formLinkContrat()
	{
		global $DB, $LANG, $CFG_GLPI;
		
		echo "<div>";
		echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/bestmanagement.form.php\">";
		if ($this->id)
			echo "<input type='hidden' name='ticket_".$this->id."'	value='$this->id'>";
		echo "<table class='tab_cadre'>";
		
		echo "<tr class='tab_bg_1'>";
		echo "<td class='left'>Contrat :</td>";
		echo "<td>";

		//$name,$entity_restrict=-1,$alreadyused=array(),$nochecklimit=false
		$p['name']           = 'contracts_id';
		$p['value']          = '';
		$p['entity']         = '';
		$p['entity_sons']    = false;
		$p['used']           = array();
		$p['nochecklimit']   = false;
		
		// on v�rifie si un contrat est d�j� reli� � ce ticket
		if (0 == countElementsInTable("glpi_plugin_bestmanagement_link_ticketcontrat", "ID_Ticket = $this->id"))
			$p['value'] = -1;
		else
		{ // contrat associ� (ou Hors Contrat, dans ce cas 0)
			$query	=  "SELECT IFNULL(ID_Contrat,0) ID_Contrat
						FROM glpi_plugin_bestmanagement_link_ticketcontrat
						WHERE ID_Ticket = $this->id";
			
			if($resultat = $DB->query($query))
				if($DB->numrows($resultat) > 0)
					while($row = $DB->fetch_assoc($resultat))
						$p['value'] = $row["ID_Contrat"];
		}
	
		
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
				else { // XMLHttpRequest non support� par le navigateur 
				   alert(\"Votre navigateur ne supporte pas les objets XMLHTTPRequest...\"); 
				   xhr = false; 
				} 
								return xhr;
			}
			
			/**
			* M�thode qui sera appel�e sur le click du bouton
			*/
			function go(id){
				var xhr = getXhr();
				// On d�fini ce qu'on va faire quand on aura la r�ponse
				xhr.onreadystatechange = function(){
					// On ne fait quelque chose que si on a tout re�u et que le serveur est ok
					if(xhr.readyState == 4 && xhr.status == 200){
						leselect = xhr.responseText;
						// On se sert de innerHTML pour rajouter les options a la liste
						document.getElementById(id).innerHTML = leselect;
					}
				}

				// Ici on va voir comment faire du post
				xhr.open(\"POST\",\"../plugins/bestmanagement/tabrecap.php\",true);
				// ne pas oublier �a pour le post
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
		
			if ($p['value'] >= 0) // affect� � un contrat
			{
				if ($p['value'] == 0)
				{ // hors contrat
					$hc = true;
					echo "<option value='NULL'>Hors Contrat</option>";
				}
				else	// d�tails du contrat
				{
					$output=Dropdown::getDropdownName('glpi_contracts',$p['value']);
					
					if ($_SESSION["glpiis_ids_visible"])
						$output.=" (".$p['value'].")";
					
					echo "<option selected value='".$p['value']."'>".$output."</option>";
				}
			}
			else // affect� � aucun contrat
				echo "<option value='-1'>-----</option>";
			
			
			if (!isset($hc))
			{
				echo "<option value='NULL'>Hors Contrat</option>";
				unset($hc);
			}
			 
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
		echo "<td>" . $LANG["bestmanagement"]["facturation_ticket"][3]. " : ";
		echo "</td><td>";
		echo $this->selectEtatFacture();
		echo "</td>";
		if (plugin_bestmanagement_haveRight("bestmanagement","facturationticket", 1))
		{
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
	 * Retourne le num�ro de facture du ticket
	 * 
	 * @return string : num�ro de facture
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

class PluginBestmanagementAllTickets
{
	function __construct() {} // constructeur
	
	/**
	 * Affiche le listing des tickets
	 * 
	 * @return Nothing(Display)
	**/
	function showForm($lesquels="whitoutcontrat", $ID=null)
	{
		global $DB, $CFG_GLPI, $LANG;
		
		switch ($lesquels)
		{
		  case "whitoutcontrat" :
			$query_tickets = "SELECT ticket.id ID, ticket.name Titre, ent.completename Entite, ticket.status Statut,
									 ticket.priority Priorite, ticket.date_mod DerModif, ticket.date DateOuv,
									 cat.name CatName, ticket.urgency Urgence
							  FROM glpi_tickets ticket
								LEFT JOIN glpi_entities ent
									ON ticket.entities_id = ent.id
										LEFT JOIN glpi_ticketcategories cat
											ON ticket.ticketcategories_id = cat.id
							  WHERE ticket.id NOT IN (SELECT ID_Ticket
													  FROM glpi_plugin_bestmanagement_link_ticketcontrat) " .
								getEntitiesRestrictRequest("AND","ticket","entities_id","",false);
			break;
		  case "afacturer" :
			$query_tickets = "SELECT ticket.id ID, ticket.name Titre, ent.completename Entite, ticket.status Statut,
									 ticket.priority Priorite, ticket.date_mod DerModif, ticket.date DateOuv,
									 cat.name CatName, ticket.urgency Urgence
							  FROM glpi_tickets ticket
								LEFT JOIN glpi_entities ent
									ON ticket.entities_id = ent.id
										LEFT JOIN glpi_ticketcategories cat
											ON ticket.ticketcategories_id = cat.id
							  WHERE ticket.id NOT IN (SELECT ID_Ticket
													  FROM glpi_plugin_bestmanagement_facturation_ticket
													  WHERE etat_fact != 0) " .
								getEntitiesRestrictRequest("AND","ticket","entities_id","",false);
			break;
		  case "linkedcontrat" :
		    $conditionwhere = ($ID == "NULL") ? "IS NULL" : " = $ID";
			$query_tickets = "SELECT ticket.id ID, ticket.name Titre, ent.completename Entite, ticket.status Statut,
									 ticket.priority Priorite, ticket.date_mod DerModif, ticket.date DateOuv,
									 cat.name CatName, ticket.urgency Urgence, fact.num_fact_api num_fact_api
							  FROM glpi_tickets ticket
								LEFT JOIN glpi_entities ent
									ON ticket.entities_id = ent.id
										LEFT JOIN glpi_ticketcategories cat
											ON ticket.ticketcategories_id = cat.id
												LEFT JOIN glpi_plugin_bestmanagement_facturation_ticket fact
													ON ticket.id = fact.ID_Ticket
							  WHERE ticket.id IN (SELECT ID_Ticket
												  FROM glpi_plugin_bestmanagement_link_ticketcontrat
												  WHERE ID_Contrat $conditionwhere)" .
									getEntitiesRestrictRequest("AND","ticket","entities_id","",false) . "
							  ORDER BY ticket.id DESC";
		}
		$td	= "<td class='center'>";	// td normal
		
		if($resultat = $DB->query($query_tickets))
			if($DB->numrows($resultat) > 0)
			{
				if (!isset($ID))
				$colonnes = array("",	// pour la checkbox
								  $LANG['common'][2],
								  $LANG['common'][57],
								  $LANG['entity'][0]);
				else if ($ID == "NULL")
					$colonnes = array($LANG['common'][2],
									  $LANG['common'][57],
									  $LANG['entity'][0]);
				else	// cas sous la fiche contrat
					$colonnes = array($LANG['common'][2],
									  $LANG['common'][57]);
				
				array_push($colonnes, $LANG['joblist'][0],
									  $LANG['joblist'][2],
									  $LANG['common'][26],
									  $LANG['reports'][60],
									  $LANG['common'][36],
									  $LANG['joblist'][29]);
				
				// cas o� on veut aussi le num�ro de facturation
				if ($lesquels == "linkedcontrat")
					array_push($colonnes, $LANG["bestmanagement"]["facturation_ticket"][4]);
				
				echo "<tr>";
				foreach ($colonnes as $col)
					echo "<th style='padding:0px 10px;'>".$col."</th>";
				echo "</tr>";
				
				echo "<script type='text/javascript' >";
				//On ne pourra �diter qu'une valeur � la fois
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
					// Entit�, si 0, alors Entit� Racine
					// pas si on se trouve dans la fiche contrat
					if (!isset($ID) || $ID == "NULL")
						echo $td . (empty($row["Entite"]) ? $LANG['entity'][2] : $row["Entite"]) ."</td>";
					// Statut + image
					echo $td . "<img src=\"".$CFG_GLPI["root_doc"]."/pics/".$row["Statut"].".png\"
					   alt='".Ticket::getStatus($row["Statut"])."' title='".
					   Ticket::getStatus($row["Statut"])."'>&nbsp;" . Ticket::getStatus($row["Statut"]) . "</td>";
					// Priorit� + BG
					$key = $row["Priorite"];
					echo "<td align='center' style=\"background-color:".$_SESSION["glpipriority_$key"]."\">";
					echo Ticket::getPriorityName($row["Priorite"]) . "</td>";
					// Derni�re modif
					echo $td . Html::convDateTime($row["DerModif"]) ."</td>";
					// Date ouverture
					echo $td . Html::convDateTime($row["DateOuv"]) ."</td>";
					// Cat�gorie
					echo $td . $row["CatName"] ."</td>";
					// Urgence
					echo $td . Ticket::getUrgencyName($row["Urgence"]) . "</td>";
					// N� facture
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
	 * aux �tats de facturation
	 * 
	 * @return Nothing(Display)
	**/
	function selectFacturation()
	{
		global $LANG;
		
		$fact = array();
		$fact[0] = "selected";	// par d�faut � non factur�
		
		echo "<select name='id_facturation' id='id_facturation'>";
		for ($i = 0 ; $i < 3 ; ++$i)
			echo "<option $fact[$i] value='$i'>".$LANG["bestmanagement"]["facturation_ticket"][$i]."</option>";
		echo "</select>";

	} // selectFacturation()
	
	/**
	 * Affiche le select correspondant
	 * aux contrats de l'entit�
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
	 * Retourne le nombre de tickets non factur�s
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
	 * D�finit l'en-t�te
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
	 * D�finit le pied de page
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
		
		//Positionnement � 1,5 cm du bas
		$this->SetY(-15);
		$this->SetFont('Arial','I',8);
		
		$footer	= utf8_decode(str_replace("&euro;",'�',$row["footer"]));
		
		$this->MultiCell(0,3, $footer . "\n" .$LANG["bestmanagement"]["pdf"][16] . " " . $this->PageNo().' / {nb}',0,'C');
	} // Footer()
	
	/**
	 * D�finit le titre de la page
	 *
	 * @return Nothing (display)
	**/
	function Titre($TabID)
	{
		global $DB, $LANG;

		$trackID = "(";
		foreach($TabID as $i)
			$trackID .= $i . ",";
			
		$trackID = substr($trackID, 0, -1);	// pour enlever la virgule � la fin
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
	 * D�finit la signature en fin de page
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
			// requ�te pour r�cup�rer le groupe de l'intervenant
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
	 * Pr�sentation du contrat et des tickets
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
			
		$trackID = substr($trackID, 0, -1);	// pour enlever la virgule � la fin
		$trackID .= ")";
		
		$this->SetFont('Arial','',9);
		$this->Cell(10);
		$this->Cell(50,0,$LANG['common'][27] . " : " . date("d.m.Y"));	// date
		
		// requ�te pour r�cup�rer le contrat
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
						// v�rifications pour savoir si les valeurs existent
						$tab_achat[$key]	= isset($tab_achat[$key])	? $tab_achat[$key]	: 0;
						$tab_report[$key]	= isset($tab_report[$key])	? $tab_report[$key]	: 0;
						$tab_conso[$key]	= isset($tab_conso[$key])	? $tab_conso[$key]	: 0;
						$tab_restant[$key]	= isset($tab_restant[$key])	? $tab_restant[$key]: 0;
						// fin v�rification
						$Tab = array("");
						
						if ($tab_achat[$key] == 0 && $tab_report[$key] == 0 && $tab_conso[$key] == 0) continue;
						
						($tab_restant[$key] < 0) ? $this->SetTextColor(245,0,0) : $this->SetTextColor(0,0,0);
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
					
				} // fin pr�sentation contrat
			}
		
		$tabConstraint = array("IS NOT NULL", "IS NULL");
		$i = 0;	// pour adapter l'affichage
		
		foreach($tabConstraint as $constraint)
		{
			// requ�te pour avoir le temps/nombre total de tous les tickets du rapport (ceux appartenant aux contrats)
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
							$min = ($row["TpsNb"] - $h) * 60;
							$min = round($min);						// nombre de minutes
							if	($min == 0)	$min = "";
							else			$min .= "min";
							
							$title = $LANG["bestmanagement"]["pdf"][20+$i];
							$time = $h . " " . $min;
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
						$this->Cell(15,0,utf8_decode($title . " : " .$time));		// � v�rifier selon l'intervalle
						$this->Ln(5);
					}
				}
			$i += 2;
		} // for deux fois
		$this->Ln(1);
		
	} // Present()
	
	/**
	 * Tableau r�capitulatif d'un ticket
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
		$col1 = 17;
		$col2 = 17;
		$col3 = 27;
		$col4 = 38;
		$col5 = 86;
		//
		
		$this->SetFillColor(0,75,100);		// Cadre de couleur
		$this->SetTextColor(255,255,255);
		$this->SetAligns('L');
		$this->SetFont('Arial','',9);

		// requ�te pour avoir le titre et le temps total du ticket
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
					$h = floor($row["Duree"]);											// nombre d'heures
					if	($h == 0)	$h = "";
					else			$h .= "h";
					$min = ($row["Duree"] - $h) * 60;
					$min = round($min);													// nombre de minutes
					if	($min == 0)	$min = "";
					else			$min .= "min";
					
					// on adapte l'affichage
					$time = ($h+$min == 0) ? "" : utf8_decode($LANG['job'][20] . " : $h $min");					
				}
		
		$this->CheckPageBreakWithoutCell(50);
		
		$intitule = utf8_decode($LANG['job'][38] . " $ID_tracking $horscontrat : $titre");	// texte du bandeau
		
		// Ticket
		$this->Ln(0);
		$this->Cell(0, 5, $intitule , 1, 0, '', true);
		$this->Cell(0, 5, $time, 1, 1, 'R', true);
		$this->Ln(3);

		$this->SetFillColor(255,255,255);		// on d�fait le cadre de couleur
		// Description du ticket
		$this->Cell($alinea);
		$this->SetTextColor(0,0,0);
		$query =   "SELECT ticket.content as Description, ticket.status as Statut, cat.name as CatName
					FROM glpi_tickets ticket
						LEFT JOIN glpi_ticketcategories cat
							ON ticket.ticketcategories_id = cat.id
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
			Construit une requ�te � partir de l'identifiant du ticket ($ID_tracking).
			R�cup�re pour toutes les taches :
			date, dur�e, planification �ventuelle, cat�gorie, auteur et description.
		*/
		$query =   "SELECT task.date as DateF, task.content as Description, task.actiontime as Duree,
							user.realname as Nom, user.firstname as Prenom,
							userplan.realname as PlanNom, userplan.firstname as PlanPrenom,
							plan.begin as PlanBegin, plan.end as PlanEnd, plan.state as Statut,
							taskcat.name as CatName
					FROM glpi_tickettasks task
						INNER JOIN glpi_tickets ticket
							ON task.tickets_id = ticket.id
								INNER JOIN glpi_users user
									ON task.users_id = user.id
										LEFT JOIN glpi_ticketplannings plan
											ON task.id = plan.tickettasks_id
												LEFT JOIN glpi_users userplan
													ON plan.users_id = userplan.id
														LEFT JOIN glpi_taskcategories taskcat
															ON task.taskcategories_id = taskcat.id
					WHERE task.tickets_id = " . $ID_tracking . "
						AND task.is_private = 0";

		if($result = $DB->query($query))
			if($DB->numrows($result) > 0)
			{
				$this->Cell($alinea);
				$this->Cell($col1, 5, utf8_decode($LANG['common'][27])	, 1, 0, 'C', true);	// date
				$this->Cell($col2, 5, utf8_decode($LANG['financial'][8]), 1, 0, 'C', true);	// dur�e
				$this->Cell($col3, 5, utf8_decode($LANG['common'][37])	, 1, 0, 'C', true);	// auteur
				$this->Cell($col4, 5, utf8_decode($LANG['common'][36])	, 1, 0, 'C', true);	// cat�gorie
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

					$h = floor($row["Duree"]);									// nombre d'heures
					if	($h == 0)	$h = "";
					else			$h .= "h";
					$min = ($row["Duree"] - $h) * 60;
					$min = round($min);											// nombre de minutes
					if	($min == 0)	$min = "";
					else			$min .= "min";
					
					array_push($Tab, $h . " " . $min);
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
	} // Ticket().
	
	/**
	 * Cr�� un tableau des largeurs de colonnes
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
		//Effectue un saut de page si n�cessaire
		$this->CheckPageBreak($h);
		//Dessine les cellules
		for($i=0;$i<count($data);$i++)
		{
			$w=$this->widths[$i];
			$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'C';	// on centre les donn�es
			//Sauve la position courante
			$x=$this->GetX();
			$y=$this->GetY();
			//Dessine le cadre
			$this->Rect($x,$y,$w,$h);
			//Imprime le texte
			$this->MultiCell($w,5,$data[$i],0,$a);
			//Repositionne � droite
			$this->SetXY($x+$w,$y);
		}
		//Va � la ligne
		$this->Ln($h);
	} // Row()
	
	/**
	 * V�rifie s'il faut sauter une page
	 * 
	 * @param $h hauteur de celulle
	 *
	 * @return Nothing
	**/
	function CheckPageBreak($h)
	{
		//Si la hauteur h provoque un d�bordement, saut de page manuel
		if($this->GetY()+$h>$this->PageBreakTrigger)
		{
			$this->AddPage($this->CurOrientation);
			$this->Cell(5);
		}
	} // CheckPageBreak()
	
	function CheckPageBreakWithoutCell($h)
	{
		//Si la hauteur h provoque un d�bordement, saut de page manuel
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
				// On r�cup�re une ligne
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
				else if ($Ligne == $derniere_ligne)		// derni�re ligne
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
	 * G�n�re le fichier .pdf
	 * 
	 * @param $TabID : tableau des id des tickets
	 *
	 * @return Nothing(Display pdf file)
	**/
	function generatePDF($TabID)
	{
		global $DB;
		
		// Propri�t�s du document
		$this->SetTitle		($this->getItem("titre"));
		$this->SetAuthor	($this->getItem("auteur"));
		$this->SetSubject	($this->getItem("sujet"));
		
		$this->AliasNbPages();
		$this->AddPage();
		$this->Titre($TabID);				// Nom du client et titre de la page
		$this->Present($TabID);				// Pr�sentation du contrat
		$this->SetFont('Times','',7);		// Police Times 7
		
		foreach ($TabID as $i)
			$this->Ticket($i);
		
		$this->Signature();
		$this->CGV();
		
		$this->Output();	
	} // generatePDF()

} // class PluginBestmanagementPDF


if (!defined('FPDF_VERSION')) define('FPDF_VERSION','1.6');

class FPDF
{
	var $page;               //current page number
	var $n;                  //current object number
	var $offsets;            //array of object offsets
	var $buffer;             //buffer holding in-memory PDF
	var $pages;              //array containing pages
	var $state;              //current document state
	var $compress;           //compression flag
	var $k;                  //scale factor (number of points in user unit)
	var $DefOrientation;     //default orientation
	var $CurOrientation;     //current orientation
	var $PageFormats;        //available page formats
	var $DefPageFormat;      //default page format
	var $CurPageFormat;      //current page format
	var $PageSizes;          //array storing non-default page sizes
	var $wPt,$hPt;           //dimensions of current page in points
	var $w,$h;               //dimensions of current page in user unit
	var $lMargin;            //left margin
	var $tMargin;            //top margin
	var $rMargin;            //right margin
	var $bMargin;            //page break margin
	var $cMargin;            //cell margin
	var $x,$y;               //current position in user unit
	var $lasth;              //height of last printed cell
	var $LineWidth;          //line width in user unit
	var $CoreFonts;          //array of standard font names
	var $fonts;              //array of used fonts
	var $FontFiles;          //array of font files
	var $diffs;              //array of encoding differences
	var $FontFamily;         //current font family
	var $FontStyle;          //current font style
	var $underline;          //underlining flag
	var $CurrentFont;        //current font info
	var $FontSizePt;         //current font size in points
	var $FontSize;           //current font size in user unit
	var $DrawColor;          //commands for drawing color
	var $FillColor;          //commands for filling color
	var $TextColor;          //commands for text color
	var $ColorFlag;          //indicates whether fill and text colors are different
	var $ws;                 //word spacing
	var $images;             //array of used images
	var $PageLinks;          //array of links in pages
	var $links;              //array of internal links
	var $AutoPageBreak;      //automatic page breaking
	var $PageBreakTrigger;   //threshold used to trigger page breaks
	var $InHeader;           //flag set when processing header
	var $InFooter;           //flag set when processing footer
	var $ZoomMode;           //zoom display mode
	var $LayoutMode;         //layout display mode
	var $title;              //title
	var $subject;            //subject
	var $author;             //author
	var $keywords;           //keywords
	var $creator;            //creator
	var $AliasNbPages;       //alias for total number of pages
	var $PDFVersion;         //PDF version number

	/*******************************************************************************
	*                                                                              *
	*                               Public methods                                 *
	*                                                                              *
	*******************************************************************************/
	function FPDF($orientation='P', $unit='mm', $format='A4')
	{
		//Some checks
		$this->_dochecks();
		//Initialization of properties
		$this->page=0;
		$this->n=2;
		$this->buffer='';
		$this->pages=array();
		$this->PageSizes=array();
		$this->state=0;
		$this->fonts=array();
		$this->FontFiles=array();
		$this->diffs=array();
		$this->images=array();
		$this->links=array();
		$this->InHeader=false;
		$this->InFooter=false;
		$this->lasth=0;
		$this->FontFamily='';
		$this->FontStyle='';
		$this->FontSizePt=12;
		$this->underline=false;
		$this->DrawColor='0 G';
		$this->FillColor='0 g';
		$this->TextColor='0 g';
		$this->ColorFlag=false;
		$this->ws=0;
		//Standard fonts
		$this->CoreFonts=array('courier'=>'Courier', 'courierB'=>'Courier-Bold', 'courierI'=>'Courier-Oblique', 'courierBI'=>'Courier-BoldOblique',
			'helvetica'=>'Helvetica', 'helveticaB'=>'Helvetica-Bold', 'helveticaI'=>'Helvetica-Oblique', 'helveticaBI'=>'Helvetica-BoldOblique',
			'times'=>'Times-Roman', 'timesB'=>'Times-Bold', 'timesI'=>'Times-Italic', 'timesBI'=>'Times-BoldItalic',
			'symbol'=>'Symbol', 'zapfdingbats'=>'ZapfDingbats');
		//Scale factor
		if($unit=='pt')
			$this->k=1;
		elseif($unit=='mm')
			$this->k=72/25.4;
		elseif($unit=='cm')
			$this->k=72/2.54;
		elseif($unit=='in')
			$this->k=72;
		else
			$this->Error('Incorrect unit: '.$unit);
		//Page format
		$this->PageFormats=array('a3'=>array(841.89,1190.55), 'a4'=>array(595.28,841.89), 'a5'=>array(420.94,595.28),
			'letter'=>array(612,792), 'legal'=>array(612,1008));
		if(is_string($format))
			$format=$this->_getpageformat($format);
		$this->DefPageFormat=$format;
		$this->CurPageFormat=$format;
		//Page orientation
		$orientation=strtolower($orientation);
		if($orientation=='p' || $orientation=='portrait')
		{
			$this->DefOrientation='P';
			$this->w=$this->DefPageFormat[0];
			$this->h=$this->DefPageFormat[1];
		}
		elseif($orientation=='l' || $orientation=='landscape')
		{
			$this->DefOrientation='L';
			$this->w=$this->DefPageFormat[1];
			$this->h=$this->DefPageFormat[0];
		}
		else
			$this->Error('Incorrect orientation: '.$orientation);
		$this->CurOrientation=$this->DefOrientation;
		$this->wPt=$this->w*$this->k;
		$this->hPt=$this->h*$this->k;
		//Page margins (1 cm)
		$margin=28.35/$this->k;
		$this->SetMargins($margin,$margin);
		//Interior cell margin (1 mm)
		$this->cMargin=$margin/10;
		//Line width (0.2 mm)
		$this->LineWidth=.567/$this->k;
		//Automatic page break
		$this->SetAutoPageBreak(true,2*$margin);
		//Full width display mode
		$this->SetDisplayMode('fullwidth');
		//Enable compression
		$this->SetCompression(true);
		//Set default PDF version number
		$this->PDFVersion='1.3';
	}

	function SetMargins($left, $top, $right=null)
	{
		//Set left, top and right margins
		$this->lMargin=$left;
		$this->tMargin=$top;
		if($right===null)
			$right=$left;
		$this->rMargin=$right;
	}

	function SetLeftMargin($margin)
	{
		//Set left margin
		$this->lMargin=$margin;
		if($this->page>0 && $this->x<$margin)
			$this->x=$margin;
	}

	function SetTopMargin($margin)
	{
		//Set top margin
		$this->tMargin=$margin;
	}

	function SetRightMargin($margin)
	{
		//Set right margin
		$this->rMargin=$margin;
	}

	function SetAutoPageBreak($auto, $margin=0)
	{
		//Set auto page break mode and triggering margin
		$this->AutoPageBreak=$auto;
		$this->bMargin=$margin;
		$this->PageBreakTrigger=$this->h-$margin;
	}

	function SetDisplayMode($zoom, $layout='continuous')
	{
		//Set display mode in viewer
		if($zoom=='fullpage' || $zoom=='fullwidth' || $zoom=='real' || $zoom=='default' || !is_string($zoom))
			$this->ZoomMode=$zoom;
		else
			$this->Error('Incorrect zoom display mode: '.$zoom);
		if($layout=='single' || $layout=='continuous' || $layout=='two' || $layout=='default')
			$this->LayoutMode=$layout;
		else
			$this->Error('Incorrect layout display mode: '.$layout);
	}

	function SetCompression($compress)
	{
		//Set page compression
		if(function_exists('gzcompress'))
			$this->compress=$compress;
		else
			$this->compress=false;
	}

	function SetTitle($title, $isUTF8=false)
	{
		//Title of document
		if($isUTF8)
			$title=$this->_UTF8toUTF16($title);
		$this->title=$title;
	}

	function SetSubject($subject, $isUTF8=false)
	{
		//Subject of document
		if($isUTF8)
			$subject=$this->_UTF8toUTF16($subject);
		$this->subject=$subject;
	}

	function SetAuthor($author, $isUTF8=false)
	{
		//Author of document
		if($isUTF8)
			$author=$this->_UTF8toUTF16($author);
		$this->author=$author;
	}

	function SetKeywords($keywords, $isUTF8=false)
	{
		//Keywords of document
		if($isUTF8)
			$keywords=$this->_UTF8toUTF16($keywords);
		$this->keywords=$keywords;
	}

	function SetCreator($creator, $isUTF8=false)
	{
		//Creator of document
		if($isUTF8)
			$creator=$this->_UTF8toUTF16($creator);
		$this->creator=$creator;
	}

	function AliasNbPages($alias='{nb}')
	{
		//Define an alias for total number of pages
		$this->AliasNbPages=$alias;
	}

	function Error($msg)
	{
		//Fatal error
		die('<b>FPDF error:</b> '.$msg);
	}

	function Open()
	{
		//Begin document
		$this->state=1;
	}

	function Close()
	{
		//Terminate document
		if($this->state==3)
			return;
		if($this->page==0)
			$this->AddPage();
		//Page footer
		$this->InFooter=true;
		$this->Footer();
		$this->InFooter=false;
		//Close page
		$this->_endpage();
		//Close document
		$this->_enddoc();
	}

	function AddPage($orientation='', $format='')
	{
		//Start a new page
		if($this->state==0)
			$this->Open();
		$family=$this->FontFamily;
		$style=$this->FontStyle.($this->underline ? 'U' : '');
		$size=$this->FontSizePt;
		$lw=$this->LineWidth;
		$dc=$this->DrawColor;
		$fc=$this->FillColor;
		$tc=$this->TextColor;
		$cf=$this->ColorFlag;
		if($this->page>0)
		{
			//Page footer
			$this->InFooter=true;
			$this->Footer();
			$this->InFooter=false;
			//Close page
			$this->_endpage();
		}
		//Start new page
		$this->_beginpage($orientation,$format);
		//Set line cap style to square
		$this->_out('2 J');
		//Set line width
		$this->LineWidth=$lw;
		$this->_out(sprintf('%.2F w',$lw*$this->k));
		//Set font
		if($family)
			$this->SetFont($family,$style,$size);
		//Set colors
		$this->DrawColor=$dc;
		if($dc!='0 G')
			$this->_out($dc);
		$this->FillColor=$fc;
		if($fc!='0 g')
			$this->_out($fc);
		$this->TextColor=$tc;
		$this->ColorFlag=$cf;
		//Page header
		$this->InHeader=true;
		$this->Header();
		$this->InHeader=false;
		//Restore line width
		if($this->LineWidth!=$lw)
		{
			$this->LineWidth=$lw;
			$this->_out(sprintf('%.2F w',$lw*$this->k));
		}
		//Restore font
		if($family)
			$this->SetFont($family,$style,$size);
		//Restore colors
		if($this->DrawColor!=$dc)
		{
			$this->DrawColor=$dc;
			$this->_out($dc);
		}
		if($this->FillColor!=$fc)
		{
			$this->FillColor=$fc;
			$this->_out($fc);
		}
		$this->TextColor=$tc;
		$this->ColorFlag=$cf;
	}

	function Header()
	{
		//To be implemented in your own inherited class
	}

	function Footer()
	{
		//To be implemented in your own inherited class
	}

	function PageNo()
	{
		//Get current page number
		return $this->page;
	}

	function SetDrawColor($r, $g=null, $b=null)
	{
		//Set color for all stroking operations
		if(($r==0 && $g==0 && $b==0) || $g===null)
			$this->DrawColor=sprintf('%.3F G',$r/255);
		else
			$this->DrawColor=sprintf('%.3F %.3F %.3F RG',$r/255,$g/255,$b/255);
		if($this->page>0)
			$this->_out($this->DrawColor);
	}

	function SetFillColor($r, $g=null, $b=null)
	{
		//Set color for all filling operations
		if(($r==0 && $g==0 && $b==0) || $g===null)
			$this->FillColor=sprintf('%.3F g',$r/255);
		else
			$this->FillColor=sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);
		$this->ColorFlag=($this->FillColor!=$this->TextColor);
		if($this->page>0)
			$this->_out($this->FillColor);
	}

	function SetTextColor($r, $g=null, $b=null)
	{
		//Set color for text
		if(($r==0 && $g==0 && $b==0) || $g===null)
			$this->TextColor=sprintf('%.3F g',$r/255);
		else
			$this->TextColor=sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);
		$this->ColorFlag=($this->FillColor!=$this->TextColor);
	}

	function GetStringWidth($s)
	{
		//Get width of a string in the current font
		$s=(string)$s;
		$cw=&$this->CurrentFont['cw'];
		$w=0;
		$l=strlen($s);
		for($i=0;$i<$l;$i++)
			$w+=$cw[$s[$i]];
		return $w*$this->FontSize/1000;
	}

	function SetLineWidth($width)
	{
		//Set line width
		$this->LineWidth=$width;
		if($this->page>0)
			$this->_out(sprintf('%.2F w',$width*$this->k));
	}

	function Line($x1, $y1, $x2, $y2)
	{
		//Draw a line
		$this->_out(sprintf('%.2F %.2F m %.2F %.2F l S',$x1*$this->k,($this->h-$y1)*$this->k,$x2*$this->k,($this->h-$y2)*$this->k));
	}

	function Rect($x, $y, $w, $h, $style='')
	{
		//Draw a rectangle
		if($style=='F')
			$op='f';
		elseif($style=='FD' || $style=='DF')
			$op='B';
		else
			$op='S';
		$this->_out(sprintf('%.2F %.2F %.2F %.2F re %s',$x*$this->k,($this->h-$y)*$this->k,$w*$this->k,-$h*$this->k,$op));
	}

	function AddFont($family, $style='', $file='')
	{
		//Add a TrueType or Type1 font
		$family=strtolower($family);
		if($file=='')
			$file=str_replace(' ','',$family).strtolower($style).'.php';
		if($family=='arial')
			$family='helvetica';
		$style=strtoupper($style);
		if($style=='IB')
			$style='BI';
		$fontkey=$family.$style;
		if(isset($this->fonts[$fontkey]))
			return;
		include($this->_getfontpath().$file);
		if(!isset($name))
			$this->Error('Could not include font definition file');
		$i=count($this->fonts)+1;
		$this->fonts[$fontkey]=array('i'=>$i, 'type'=>$type, 'name'=>$name, 'desc'=>$desc, 'up'=>$up, 'ut'=>$ut, 'cw'=>$cw, 'enc'=>$enc, 'file'=>$file);
		if($diff)
		{
			//Search existing encodings
			$d=0;
			$nb=count($this->diffs);
			for($i=1;$i<=$nb;$i++)
			{
				if($this->diffs[$i]==$diff)
				{
					$d=$i;
					break;
				}
			}
			if($d==0)
			{
				$d=$nb+1;
				$this->diffs[$d]=$diff;
			}
			$this->fonts[$fontkey]['diff']=$d;
		}
		if($file)
		{
			if($type=='TrueType')
				$this->FontFiles[$file]=array('length1'=>$originalsize);
			else
				$this->FontFiles[$file]=array('length1'=>$size1, 'length2'=>$size2);
		}
	}

	function SetFont($family, $style='', $size=0)
	{
		//Select a font; size given in points
		global $fpdf_charwidths;

		$family=strtolower($family);
		if($family=='')
			$family=$this->FontFamily;
		if($family=='arial')
			$family='helvetica';
		elseif($family=='symbol' || $family=='zapfdingbats')
			$style='';
		$style=strtoupper($style);
		if(strpos($style,'U')!==false)
		{
			$this->underline=true;
			$style=str_replace('U','',$style);
		}
		else
			$this->underline=false;
		if($style=='IB')
			$style='BI';
		if($size==0)
			$size=$this->FontSizePt;
		//Test if font is already selected
		if($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size)
			return;
		//Test if used for the first time
		$fontkey=$family.$style;
		if(!isset($this->fonts[$fontkey]))
		{
			//Check if one of the standard fonts
			if(isset($this->CoreFonts[$fontkey]))
			{
				if(!isset($fpdf_charwidths[$fontkey]))
				{
					//Load metric file
					$file=$family;
					if($family=='times' || $family=='helvetica')
						$file.=strtolower($style);
					include($this->_getfontpath().$file.'.php');
					if(!isset($fpdf_charwidths[$fontkey]))
						$this->Error('Could not include font metric file');
				}
				$i=count($this->fonts)+1;
				$name=$this->CoreFonts[$fontkey];
				$cw=$fpdf_charwidths[$fontkey];
				$this->fonts[$fontkey]=array('i'=>$i, 'type'=>'core', 'name'=>$name, 'up'=>-100, 'ut'=>50, 'cw'=>$cw);
			}
			else
				$this->Error('Undefined font: '.$family.' '.$style);
		}
		//Select it
		$this->FontFamily=$family;
		$this->FontStyle=$style;
		$this->FontSizePt=$size;
		$this->FontSize=$size/$this->k;
		$this->CurrentFont=&$this->fonts[$fontkey];
		if($this->page>0)
			$this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
	}

	function SetFontSize($size)
	{
		//Set font size in points
		if($this->FontSizePt==$size)
			return;
		$this->FontSizePt=$size;
		$this->FontSize=$size/$this->k;
		if($this->page>0)
			$this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
	}

	function AddLink()
	{
		//Create a new internal link
		$n=count($this->links)+1;
		$this->links[$n]=array(0, 0);
		return $n;
	}

	function SetLink($link, $y=0, $page=-1)
	{
		//Set destination of internal link
		if($y==-1)
			$y=$this->y;
		if($page==-1)
			$page=$this->page;
		$this->links[$link]=array($page, $y);
	}

	function Link($x, $y, $w, $h, $link)
	{
		//Put a link on the page
		$this->PageLinks[$this->page][]=array($x*$this->k, $this->hPt-$y*$this->k, $w*$this->k, $h*$this->k, $link);
	}

	function Text($x, $y, $txt)
	{
		//Output a string
		$s=sprintf('BT %.2F %.2F Td (%s) Tj ET',$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
		if($this->underline && $txt!='')
			$s.=' '.$this->_dounderline($x,$y,$txt);
		if($this->ColorFlag)
			$s='q '.$this->TextColor.' '.$s.' Q';
		$this->_out($s);
	}

	function AcceptPageBreak()
	{
		//Accept automatic page break or not
		return $this->AutoPageBreak;
	}

	function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
	{
		//Output a cell
		$k=$this->k;
		if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
		{
			//Automatic page break
			$x=$this->x;
			$ws=$this->ws;
			if($ws>0)
			{
				$this->ws=0;
				$this->_out('0 Tw');
			}
			$this->AddPage($this->CurOrientation,$this->CurPageFormat);
			$this->x=$x;
			if($ws>0)
			{
				$this->ws=$ws;
				$this->_out(sprintf('%.3F Tw',$ws*$k));
			}
		}
		if($w==0)
			$w=$this->w-$this->rMargin-$this->x;
		$s='';
		if($fill || $border==1)
		{
			if($fill)
				$op=($border==1) ? 'B' : 'f';
			else
				$op='S';
			$s=sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
		}
		if(is_string($border))
		{
			$x=$this->x;
			$y=$this->y;
			if(strpos($border,'L')!==false)
				$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
			if(strpos($border,'T')!==false)
				$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
			if(strpos($border,'R')!==false)
				$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
			if(strpos($border,'B')!==false)
				$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
		}
		if($txt!=='')
		{
			if($align=='R')
				$dx=$w-$this->cMargin-$this->GetStringWidth($txt);
			elseif($align=='C')
				$dx=($w-$this->GetStringWidth($txt))/2;
			else
				$dx=$this->cMargin;
			if($this->ColorFlag)
				$s.='q '.$this->TextColor.' ';
			$txt2=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
			$s.=sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$txt2);
			if($this->underline)
				$s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
			if($this->ColorFlag)
				$s.=' Q';
			if($link)
				$this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$this->GetStringWidth($txt),$this->FontSize,$link);
		}
		if($s)
			$this->_out($s);
		$this->lasth=$h;
		if($ln>0)
		{
			//Go to next line
			$this->y+=$h;
			if($ln==1)
				$this->x=$this->lMargin;
		}
		else
			$this->x+=$w;
	}

	function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false)
	{
		//Output text with automatic or explicit line breaks
		$cw=&$this->CurrentFont['cw'];
		if($w==0)
			$w=$this->w-$this->rMargin-$this->x;
		$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
		$s=str_replace("\r",'',$txt);
		$nb=strlen($s);
		if($nb>0 && $s[$nb-1]=="\n")
			$nb--;
		$b=0;
		if($border)
		{
			if($border==1)
			{
				$border='LTRB';
				$b='LRT';
				$b2='LR';
			}
			else
			{
				$b2='';
				if(strpos($border,'L')!==false)
					$b2.='L';
				if(strpos($border,'R')!==false)
					$b2.='R';
				$b=(strpos($border,'T')!==false) ? $b2.'T' : $b2;
			}
		}
		$sep=-1;
		$i=0;
		$j=0;
		$l=0;
		$ns=0;
		$nl=1;
		while($i<$nb)
		{
			//Get next character
			$c=$s[$i];
			if($c=="\n")
			{
				//Explicit line break
				if($this->ws>0)
				{
					$this->ws=0;
					$this->_out('0 Tw');
				}
				$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
				$i++;
				$sep=-1;
				$j=$i;
				$l=0;
				$ns=0;
				$nl++;
				if($border && $nl==2)
					$b=$b2;
				continue;
			}
			if($c==' ')
			{
				$sep=$i;
				$ls=$l;
				$ns++;
			}
			$l+=$cw[$c];
			if($l>$wmax)
			{
				//Automatic line break
				if($sep==-1)
				{
					if($i==$j)
						$i++;
					if($this->ws>0)
					{
						$this->ws=0;
						$this->_out('0 Tw');
					}
					$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
				}
				else
				{
					if($align=='J')
					{
						$this->ws=($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
						$this->_out(sprintf('%.3F Tw',$this->ws*$this->k));
					}
					$this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
					$i=$sep+1;
				}
				$sep=-1;
				$j=$i;
				$l=0;
				$ns=0;
				$nl++;
				if($border && $nl==2)
					$b=$b2;
			}
			else
				$i++;
		}
		//Last chunk
		if($this->ws>0)
		{
			$this->ws=0;
			$this->_out('0 Tw');
		}
		if($border && strpos($border,'B')!==false)
			$b.='B';
		$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
		$this->x=$this->lMargin;
	}

	function Write($h, $txt, $link='')
	{
		//Output text in flowing mode
		$cw=&$this->CurrentFont['cw'];
		$w=$this->w-$this->rMargin-$this->x;
		$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
		$s=str_replace("\r",'',$txt);
		$nb=strlen($s);
		$sep=-1;
		$i=0;
		$j=0;
		$l=0;
		$nl=1;
		while($i<$nb)
		{
			//Get next character
			$c=$s[$i];
			if($c=="\n")
			{
				//Explicit line break
				$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',0,$link);
				$i++;
				$sep=-1;
				$j=$i;
				$l=0;
				if($nl==1)
				{
					$this->x=$this->lMargin;
					$w=$this->w-$this->rMargin-$this->x;
					$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
				}
				$nl++;
				continue;
			}
			if($c==' ')
				$sep=$i;
			$l+=$cw[$c];
			if($l>$wmax)
			{
				//Automatic line break
				if($sep==-1)
				{
					if($this->x>$this->lMargin)
					{
						//Move to next line
						$this->x=$this->lMargin;
						$this->y+=$h;
						$w=$this->w-$this->rMargin-$this->x;
						$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
						$i++;
						$nl++;
						continue;
					}
					if($i==$j)
						$i++;
					$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',0,$link);
				}
				else
				{
					$this->Cell($w,$h,substr($s,$j,$sep-$j),0,2,'',0,$link);
					$i=$sep+1;
				}
				$sep=-1;
				$j=$i;
				$l=0;
				if($nl==1)
				{
					$this->x=$this->lMargin;
					$w=$this->w-$this->rMargin-$this->x;
					$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
				}
				$nl++;
			}
			else
				$i++;
		}
		//Last chunk
		if($i!=$j)
			$this->Cell($l/1000*$this->FontSize,$h,substr($s,$j),0,0,'',0,$link);
	}

	function Ln($h=null)
	{
		//Line feed; default value is last cell height
		$this->x=$this->lMargin;
		if($h===null)
			$this->y+=$this->lasth;
		else
			$this->y+=$h;
	}

	function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='')
	{
		//Put an image on the page
		if(!isset($this->images[$file]))
		{
			//First use of this image, get info
			if($type=='')
			{
				$pos=strrpos($file,'.');
				if(!$pos)
					$this->Error('Image file has no extension and no type was specified: '.$file);
				$type=substr($file,$pos+1);
			}
			$type=strtolower($type);
			if($type=='jpeg')
				$type='jpg';
			$mtd='_parse'.$type;
			if(!method_exists($this,$mtd))
				$this->Error('Unsupported image type: '.$type);
			$info=$this->$mtd($file);
			$info['i']=count($this->images)+1;
			$this->images[$file]=$info;
		}
		else
			$info=$this->images[$file];
		//Automatic width and height calculation if needed
		if($w==0 && $h==0)
		{
			//Put image at 72 dpi
			$w=$info['w']/$this->k;
			$h=$info['h']/$this->k;
		}
		elseif($w==0)
			$w=$h*$info['w']/$info['h'];
		elseif($h==0)
			$h=$w*$info['h']/$info['w'];
		//Flowing mode
		if($y===null)
		{
			if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
			{
				//Automatic page break
				$x2=$this->x;
				$this->AddPage($this->CurOrientation,$this->CurPageFormat);
				$this->x=$x2;
			}
			$y=$this->y;
			$this->y+=$h;
		}
		if($x===null)
			$x=$this->x;
		$this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
		if($link)
			$this->Link($x,$y,$w,$h,$link);
	}

	function GetX()
	{
		//Get x position
		return $this->x;
	}

	function SetX($x)
	{
		//Set x position
		if($x>=0)
			$this->x=$x;
		else
			$this->x=$this->w+$x;
	}

	function GetY()
	{
		//Get y position
		return $this->y;
	}

	function SetY($y)
	{
		//Set y position and reset x
		$this->x=$this->lMargin;
		if($y>=0)
			$this->y=$y;
		else
			$this->y=$this->h+$y;
	}

	function SetXY($x, $y)
	{
		//Set x and y positions
		$this->SetY($y);
		$this->SetX($x);
	}
	
	function Output($name='', $dest='')
	{
		//Output PDF to some destination
		if($this->state<3)
			$this->Close();
		$dest=strtoupper($dest);
		if($dest=='')
		{
			if($name=='')
			{
				$name='doc.pdf';
				$dest='I';
			}
			else
				$dest='F';
		}
		switch($dest)
		{
			case 'I':
				//Send to standard output
				if(ob_get_length())
					$this->Error('Some data has already been output, can\'t send PDF file');
				if(php_sapi_name()!='cli')
				{
					//We send to a browser
					header('Content-Type: application/pdf');
					if(headers_sent())
						$this->Error('Some data has already been output, can\'t send PDF file');	//
					header('Content-Length: '.strlen($this->buffer));
					header('Content-Disposition: inline; filename="'.$name.'"');
					header('Cache-Control: private, max-age=0, must-revalidate');
					header('Pragma: public');
					ini_set('zlib.output_compression','0');
				}
				echo $this->buffer;
				break;
			case 'D':
				//Download file
				if(ob_get_length())
					$this->Error('Some data has already been output, can\'t send PDF file');
				header('Content-Type: application/x-download');
				if(headers_sent())
					$this->Error('Some data has already been output, can\'t send PDF file');
				header('Content-Length: '.strlen($this->buffer));
				header('Content-Disposition: attachment; filename="'.$name.'"');
				header('Cache-Control: private, max-age=0, must-revalidate');
				header('Pragma: public');
				ini_set('zlib.output_compression','0');
				echo $this->buffer;
				break;
			case 'F':
				//Save to local file
				$f=fopen($name,'wb');
				if(!$f)
					$this->Error('Unable to create output file: '.$name);
				fwrite($f,$this->buffer,strlen($this->buffer));
				fclose($f);
				break;
			case 'S':
				//Return as a string
				return $this->buffer;
			default:
				$this->Error('Incorrect output destination: '.$dest);
		}
		return '';
	}

	/*******************************************************************************
	*                                                                              *
	*                              Protected methods                               *
	*                                                                              *
	*******************************************************************************/
	function _dochecks()
	{
		//Check availability of %F
		if(sprintf('%.1F',1.0)!='1.0')
			$this->Error('This version of PHP is not supported');
		//Check mbstring overloading
		if(ini_get('mbstring.func_overload') & 2)
			$this->Error('mbstring overloading must be disabled');
		//Disable runtime magic quotes
		if(Toolbox::get_magic_quotes_runtime())
			@set_magic_quotes_runtime(0);
	}

	function _getpageformat($format)
	{
		$format=strtolower($format);
		if(!isset($this->PageFormats[$format]))
			$this->Error('Unknown page format: '.$format);
		$a=$this->PageFormats[$format];
		return array($a[0]/$this->k, $a[1]/$this->k);
	}

	function _getfontpath()
	{
		if(!defined('FPDF_FONTPATH') && is_dir(dirname(__FILE__).'/font'))
			define('FPDF_FONTPATH',dirname(__FILE__).'/font/');
		return defined('FPDF_FONTPATH') ? FPDF_FONTPATH : '';
	}

	function _beginpage($orientation, $format)
	{
		$this->page++;
		$this->pages[$this->page]='';
		$this->state=2;
		$this->x=$this->lMargin;
		$this->y=$this->tMargin;
		$this->FontFamily='';
		//Check page size
		if($orientation=='')
			$orientation=$this->DefOrientation;
		else
			$orientation=strtoupper($orientation[0]);
		if($format=='')
			$format=$this->DefPageFormat;
		else
		{
			if(is_string($format))
				$format=$this->_getpageformat($format);
		}
		if($orientation!=$this->CurOrientation || $format[0]!=$this->CurPageFormat[0] || $format[1]!=$this->CurPageFormat[1])
		{
			//New size
			if($orientation=='P')
			{
				$this->w=$format[0];
				$this->h=$format[1];
			}
			else
			{
				$this->w=$format[1];
				$this->h=$format[0];
			}
			$this->wPt=$this->w*$this->k;
			$this->hPt=$this->h*$this->k;
			$this->PageBreakTrigger=$this->h-$this->bMargin;
			$this->CurOrientation=$orientation;
			$this->CurPageFormat=$format;
		}
		if($orientation!=$this->DefOrientation || $format[0]!=$this->DefPageFormat[0] || $format[1]!=$this->DefPageFormat[1])
			$this->PageSizes[$this->page]=array($this->wPt, $this->hPt);
	}

	function _endpage()
	{
		$this->state=1;
	}

	function _escape($s)
	{
		//Escape special characters in strings
		$s=str_replace('\\','\\\\',$s);
		$s=str_replace('(','\\(',$s);
		$s=str_replace(')','\\)',$s);
		$s=str_replace("\r",'\\r',$s);
		return $s;
	}

	function _textstring($s)
	{
		//Format a text string
		return '('.$this->_escape($s).')';
	}

	function _UTF8toUTF16($s)
	{
		//Convert UTF-8 to UTF-16BE with BOM
		$res="\xFE\xFF";
		$nb=strlen($s);
		$i=0;
		while($i<$nb)
		{
			$c1=ord($s[$i++]);
			if($c1>=224)
			{
				//3-byte character
				$c2=ord($s[$i++]);
				$c3=ord($s[$i++]);
				$res.=chr((($c1 & 0x0F)<<4) + (($c2 & 0x3C)>>2));
				$res.=chr((($c2 & 0x03)<<6) + ($c3 & 0x3F));
			}
			elseif($c1>=192)
			{
				//2-byte character
				$c2=ord($s[$i++]);
				$res.=chr(($c1 & 0x1C)>>2);
				$res.=chr((($c1 & 0x03)<<6) + ($c2 & 0x3F));
			}
			else
			{
				//Single-byte character
				$res.="\0".chr($c1);
			}
		}
		return $res;
	}

	function _dounderline($x, $y, $txt)
	{
		//Underline text
		$up=$this->CurrentFont['up'];
		$ut=$this->CurrentFont['ut'];
		$w=$this->GetStringWidth($txt)+$this->ws*substr_count($txt,' ');
		return sprintf('%.2F %.2F %.2F %.2F re f',$x*$this->k,($this->h-($y-$up/1000*$this->FontSize))*$this->k,$w*$this->k,-$ut/1000*$this->FontSizePt);
	}

	function _parsejpg($file)
	{
		//Extract info from a JPEG file
		$a=GetImageSize($file);
		if(!$a)
			$this->Error('Missing or incorrect image file: '.$file);
		if($a[2]!=2)
			$this->Error('Not a JPEG file: '.$file);
		if(!isset($a['channels']) || $a['channels']==3)
			$colspace='DeviceRGB';
		elseif($a['channels']==4)
			$colspace='DeviceCMYK';
		else
			$colspace='DeviceGray';
		$bpc=isset($a['bits']) ? $a['bits'] : 8;
		//Read whole file
		$f=fopen($file,'rb');
		$data='';
		while(!feof($f))
			$data.=fread($f,8192);
		fclose($f);
		return array('w'=>$a[0], 'h'=>$a[1], 'cs'=>$colspace, 'bpc'=>$bpc, 'f'=>'DCTDecode', 'data'=>$data);
	}

	function _parsepng($file)
	{
		//Extract info from a PNG file
		$f=fopen($file,'rb');
		if(!$f)
			$this->Error('Can\'t open image file: '.$file);
		//Check signature
		if($this->_readstream($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
			$this->Error('Not a PNG file: '.$file);
		//Read header chunk
		$this->_readstream($f,4);
		if($this->_readstream($f,4)!='IHDR')
			$this->Error('Incorrect PNG file: '.$file);
		$w=$this->_readint($f);
		$h=$this->_readint($f);
		$bpc=ord($this->_readstream($f,1));
		if($bpc>8)
			$this->Error('16-bit depth not supported: '.$file);
		$ct=ord($this->_readstream($f,1));
		if($ct==0)
			$colspace='DeviceGray';
		elseif($ct==2)
			$colspace='DeviceRGB';
		elseif($ct==3)
			$colspace='Indexed';
		else
			$this->Error('Alpha channel not supported: '.$file);
		if(ord($this->_readstream($f,1))!=0)
			$this->Error('Unknown compression method: '.$file);
		if(ord($this->_readstream($f,1))!=0)
			$this->Error('Unknown filter method: '.$file);
		if(ord($this->_readstream($f,1))!=0)
			$this->Error('Interlacing not supported: '.$file);
		$this->_readstream($f,4);
		$parms='/DecodeParms <</Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
		//Scan chunks looking for palette, transparency and image data
		$pal='';
		$trns='';
		$data='';
		do
		{
			$n=$this->_readint($f);
			$type=$this->_readstream($f,4);
			if($type=='PLTE')
			{
				//Read palette
				$pal=$this->_readstream($f,$n);
				$this->_readstream($f,4);
			}
			elseif($type=='tRNS')
			{
				//Read transparency info
				$t=$this->_readstream($f,$n);
				if($ct==0)
					$trns=array(ord(substr($t,1,1)));
				elseif($ct==2)
					$trns=array(ord(substr($t,1,1)), ord(substr($t,3,1)), ord(substr($t,5,1)));
				else
				{
					$pos=strpos($t,chr(0));
					if($pos!==false)
						$trns=array($pos);
				}
				$this->_readstream($f,4);
			}
			elseif($type=='IDAT')
			{
				//Read image data block
				$data.=$this->_readstream($f,$n);
				$this->_readstream($f,4);
			}
			elseif($type=='IEND')
				break;
			else
				$this->_readstream($f,$n+4);
		}
		while($n);
		if($colspace=='Indexed' && empty($pal))
			$this->Error('Missing palette in '.$file);
		fclose($f);
		return array('w'=>$w, 'h'=>$h, 'cs'=>$colspace, 'bpc'=>$bpc, 'f'=>'FlateDecode', 'parms'=>$parms, 'pal'=>$pal, 'trns'=>$trns, 'data'=>$data);
	}

	function _readstream($f, $n)
	{
		//Read n bytes from stream
		$res='';
		while($n>0 && !feof($f))
		{
			$s=fread($f,$n);
			if($s===false)
				$this->Error('Error while reading stream');
			$n-=strlen($s);
			$res.=$s;
		}
		if($n>0)
			$this->Error('Unexpected end of stream');
		return $res;
	}

	function _readint($f)
	{
		//Read a 4-byte integer from stream
		$a=unpack('Ni',$this->_readstream($f,4));
		return $a['i'];
	}

	function _parsegif($file)
	{
		//Extract info from a GIF file (via PNG conversion)
		if(!function_exists('imagepng'))
			$this->Error('GD extension is required for GIF support');
		if(!function_exists('imagecreatefromgif'))
			$this->Error('GD has no GIF read support');
		$im=imagecreatefromgif($file);
		if(!$im)
			$this->Error('Missing or incorrect image file: '.$file);
		imageinterlace($im,0);
		$tmp=tempnam('.','gif');
		if(!$tmp)
			$this->Error('Unable to create a temporary file');
		if(!imagepng($im,$tmp))
			$this->Error('Error while saving to temporary file');
		imagedestroy($im);
		$info=$this->_parsepng($tmp);
		unlink($tmp);
		return $info;
	}

	function _newobj()
	{
		//Begin a new object
		$this->n++;
		$this->offsets[$this->n]=strlen($this->buffer);
		$this->_out($this->n.' 0 obj');
	}

	function _putstream($s)
	{
		$this->_out('stream');
		$this->_out($s);
		$this->_out('endstream');
	}

	function _out($s)
	{
		//Add a line to the document
		if($this->state==2)
			$this->pages[$this->page].=$s."\n";
		else
			$this->buffer.=$s."\n";
	}

	function _putpages()
	{
		$nb=$this->page;
		if(!empty($this->AliasNbPages))
		{
			//Replace number of pages
			for($n=1;$n<=$nb;$n++)
				$this->pages[$n]=str_replace($this->AliasNbPages,$nb,$this->pages[$n]);
		}
		if($this->DefOrientation=='P')
		{
			$wPt=$this->DefPageFormat[0]*$this->k;
			$hPt=$this->DefPageFormat[1]*$this->k;
		}
		else
		{
			$wPt=$this->DefPageFormat[1]*$this->k;
			$hPt=$this->DefPageFormat[0]*$this->k;
		}
		$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
		for($n=1;$n<=$nb;$n++)
		{
			//Page
			$this->_newobj();
			$this->_out('<</Type /Page');
			$this->_out('/Parent 1 0 R');
			if(isset($this->PageSizes[$n]))
				$this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]',$this->PageSizes[$n][0],$this->PageSizes[$n][1]));
			$this->_out('/Resources 2 0 R');
			if(isset($this->PageLinks[$n]))
			{
				//Links
				$annots='/Annots [';
				foreach($this->PageLinks[$n] as $pl)
				{
					$rect=sprintf('%.2F %.2F %.2F %.2F',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
					$annots.='<</Type /Annot /Subtype /Link /Rect ['.$rect.'] /Border [0 0 0] ';
					if(is_string($pl[4]))
						$annots.='/A <</S /URI /URI '.$this->_textstring($pl[4]).'>>>>';
					else
					{
						$l=$this->links[$pl[4]];
						$h=isset($this->PageSizes[$l[0]]) ? $this->PageSizes[$l[0]][1] : $hPt;
						$annots.=sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>',1+2*$l[0],$h-$l[1]*$this->k);
					}
				}
				$this->_out($annots.']');
			}
			$this->_out('/Contents '.($this->n+1).' 0 R>>');
			$this->_out('endobj');
			//Page content
			$p=($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
			$this->_newobj();
			$this->_out('<<'.$filter.'/Length '.strlen($p).'>>');
			$this->_putstream($p);
			$this->_out('endobj');
		}
		//Pages root
		$this->offsets[1]=strlen($this->buffer);
		$this->_out('1 0 obj');
		$this->_out('<</Type /Pages');
		$kids='/Kids [';
		for($i=0;$i<$nb;$i++)
			$kids.=(3+2*$i).' 0 R ';
		$this->_out($kids.']');
		$this->_out('/Count '.$nb);
		$this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]',$wPt,$hPt));
		$this->_out('>>');
		$this->_out('endobj');
	}

	function _putfonts()
	{
		$nf=$this->n;
		foreach($this->diffs as $diff)
		{
			//Encodings
			$this->_newobj();
			$this->_out('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences ['.$diff.']>>');
			$this->_out('endobj');
		}
		foreach($this->FontFiles as $file=>$info)
		{
			//Font file embedding
			$this->_newobj();
			$this->FontFiles[$file]['n']=$this->n;
			$font='';
			$f=fopen($this->_getfontpath().$file,'rb',1);
			if(!$f)
				$this->Error('Font file not found');
			while(!feof($f))
				$font.=fread($f,8192);
			fclose($f);
			$compressed=(substr($file,-2)=='.z');
			if(!$compressed && isset($info['length2']))
			{
				$header=(ord($font[0])==128);
				if($header)
				{
					//Strip first binary header
					$font=substr($font,6);
				}
				if($header && ord($font[$info['length1']])==128)
				{
					//Strip second binary header
					$font=substr($font,0,$info['length1']).substr($font,$info['length1']+6);
				}
			}
			$this->_out('<</Length '.strlen($font));
			if($compressed)
				$this->_out('/Filter /FlateDecode');
			$this->_out('/Length1 '.$info['length1']);
			if(isset($info['length2']))
				$this->_out('/Length2 '.$info['length2'].' /Length3 0');
			$this->_out('>>');
			$this->_putstream($font);
			$this->_out('endobj');
		}
		foreach($this->fonts as $k=>$font)
		{
			//Font objects
			$this->fonts[$k]['n']=$this->n+1;
			$type=$font['type'];
			$name=$font['name'];
			if($type=='core')
			{
				//Standard font
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/BaseFont /'.$name);
				$this->_out('/Subtype /Type1');
				if($name!='Symbol' && $name!='ZapfDingbats')
					$this->_out('/Encoding /WinAnsiEncoding');
				$this->_out('>>');
				$this->_out('endobj');
			}
			elseif($type=='Type1' || $type=='TrueType')
			{
				//Additional Type1 or TrueType font
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/BaseFont /'.$name);
				$this->_out('/Subtype /'.$type);
				$this->_out('/FirstChar 32 /LastChar 255');
				$this->_out('/Widths '.($this->n+1).' 0 R');
				$this->_out('/FontDescriptor '.($this->n+2).' 0 R');
				if($font['enc'])
				{
					if(isset($font['diff']))
						$this->_out('/Encoding '.($nf+$font['diff']).' 0 R');
					else
						$this->_out('/Encoding /WinAnsiEncoding');
				}
				$this->_out('>>');
				$this->_out('endobj');
				//Widths
				$this->_newobj();
				$cw=&$font['cw'];
				$s='[';
				for($i=32;$i<=255;$i++)
					$s.=$cw[chr($i)].' ';
				$this->_out($s.']');
				$this->_out('endobj');
				//Descriptor
				$this->_newobj();
				$s='<</Type /FontDescriptor /FontName /'.$name;
				foreach($font['desc'] as $k=>$v)
					$s.=' /'.$k.' '.$v;
				$file=$font['file'];
				if($file)
					$s.=' /FontFile'.($type=='Type1' ? '' : '2').' '.$this->FontFiles[$file]['n'].' 0 R';
				$this->_out($s.'>>');
				$this->_out('endobj');
			}
			else
			{
				//Allow for additional types
				$mtd='_put'.strtolower($type);
				if(!method_exists($this,$mtd))
					$this->Error('Unsupported font type: '.$type);
				$this->$mtd($font);
			}
		}
	}

	function _putimages()
	{
		$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
		reset($this->images);
		while(list($file,$info)=each($this->images))
		{
			$this->_newobj();
			$this->images[$file]['n']=$this->n;
			$this->_out('<</Type /XObject');
			$this->_out('/Subtype /Image');
			$this->_out('/Width '.$info['w']);
			$this->_out('/Height '.$info['h']);
			if($info['cs']=='Indexed')
				$this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
			else
			{
				$this->_out('/ColorSpace /'.$info['cs']);
				if($info['cs']=='DeviceCMYK')
					$this->_out('/Decode [1 0 1 0 1 0 1 0]');
			}
			$this->_out('/BitsPerComponent '.$info['bpc']);
			if(isset($info['f']))
				$this->_out('/Filter /'.$info['f']);
			if(isset($info['parms']))
				$this->_out($info['parms']);
			if(isset($info['trns']) && is_array($info['trns']))
			{
				$trns='';
				for($i=0;$i<count($info['trns']);$i++)
					$trns.=$info['trns'][$i].' '.$info['trns'][$i].' ';
				$this->_out('/Mask ['.$trns.']');
			}
			$this->_out('/Length '.strlen($info['data']).'>>');
			$this->_putstream($info['data']);
			unset($this->images[$file]['data']);
			$this->_out('endobj');
			//Palette
			if($info['cs']=='Indexed')
			{
				$this->_newobj();
				$pal=($this->compress) ? gzcompress($info['pal']) : $info['pal'];
				$this->_out('<<'.$filter.'/Length '.strlen($pal).'>>');
				$this->_putstream($pal);
				$this->_out('endobj');
			}
		}
	}

	function _putxobjectdict()
	{
		foreach($this->images as $image)
			$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
	}

	function _putresourcedict()
	{
		$this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
		$this->_out('/Font <<');
		foreach($this->fonts as $font)
			$this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
		$this->_out('>>');
		$this->_out('/XObject <<');
		$this->_putxobjectdict();
		$this->_out('>>');
	}

	function _putresources()
	{
		$this->_putfonts();
		$this->_putimages();
		//Resource dictionary
		$this->offsets[2]=strlen($this->buffer);
		$this->_out('2 0 obj');
		$this->_out('<<');
		$this->_putresourcedict();
		$this->_out('>>');
		$this->_out('endobj');
	}

	function _putinfo()
	{
		$this->_out('/Producer '.$this->_textstring('FPDF '.FPDF_VERSION));
		if(!empty($this->title))
			$this->_out('/Title '.$this->_textstring($this->title));
		if(!empty($this->subject))
			$this->_out('/Subject '.$this->_textstring($this->subject));
		if(!empty($this->author))
			$this->_out('/Author '.$this->_textstring($this->author));
		if(!empty($this->keywords))
			$this->_out('/Keywords '.$this->_textstring($this->keywords));
		if(!empty($this->creator))
			$this->_out('/Creator '.$this->_textstring($this->creator));
		$this->_out('/CreationDate '.$this->_textstring('D:'.@date('YmdHis')));
	}

	function _putcatalog()
	{
		$this->_out('/Type /Catalog');
		$this->_out('/Pages 1 0 R');
		if($this->ZoomMode=='fullpage')
			$this->_out('/OpenAction [3 0 R /Fit]');
		elseif($this->ZoomMode=='fullwidth')
			$this->_out('/OpenAction [3 0 R /FitH null]');
		elseif($this->ZoomMode=='real')
			$this->_out('/OpenAction [3 0 R /XYZ null null 1]');
		elseif(!is_string($this->ZoomMode))
			$this->_out('/OpenAction [3 0 R /XYZ null null '.($this->ZoomMode/100).']');
		if($this->LayoutMode=='single')
			$this->_out('/PageLayout /SinglePage');
		elseif($this->LayoutMode=='continuous')
			$this->_out('/PageLayout /OneColumn');
		elseif($this->LayoutMode=='two')
			$this->_out('/PageLayout /TwoColumnLeft');
	}

	function _putheader()
	{
		$this->_out('%PDF-'.$this->PDFVersion);
	}

	function _puttrailer()
	{
		$this->_out('/Size '.($this->n+1));
		$this->_out('/Root '.$this->n.' 0 R');
		$this->_out('/Info '.($this->n-1).' 0 R');
	}

	function _enddoc()
	{
		$this->_putheader();
		$this->_putpages();
		$this->_putresources();
		//Info
		$this->_newobj();
		$this->_out('<<');
		$this->_putinfo();
		$this->_out('>>');
		$this->_out('endobj');
		//Catalog
		$this->_newobj();
		$this->_out('<<');
		$this->_putcatalog();
		$this->_out('>>');
		$this->_out('endobj');
		//Cross-ref
		$o=strlen($this->buffer);
		$this->_out('xref');
		$this->_out('0 '.($this->n+1));
		$this->_out('0000000000 65535 f ');
		for($i=1;$i<=$this->n;$i++)
			$this->_out(sprintf('%010d 00000 n ',$this->offsets[$i]));
		//Trailer
		$this->_out('trailer');
		$this->_out('<<');
		$this->_puttrailer();
		$this->_out('>>');
		$this->_out('startxref');
		$this->_out($o);
		$this->_out('%%EOF');
		$this->state=3;
	}

} // class FPDF

?>
