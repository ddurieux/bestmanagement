<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Toutes les fonctions du plugin sont écrites ici
// ----------------------------------------------------------------------

function isTicketOutPeriode($id)
{
	global $DB;
	
	$query_ticket = "SELECT ID_Contrat
					 FROM glpi_plugin_bestmanagement_link_ticketcontrat
					 WHERE ID_Ticket = $id";
	
	if ($result=$DB->query($query_ticket))
		if ($row=$DB->fetch_assoc($result))
		{
			$id_contrat = $row["ID_Contrat"];
			$contrat = new PluginBestmanagementContrat($id_contrat);
			return ($_SESSION["glpi_currenttime"] > $contrat->dateFin()) ? true : false;
		}	
	return false;
} // isTicketOutPeriode()

/**  Get structure of a table
* @param $DB DB object
* @param $table table  name
*/
function get_def2($DB, $table) {

   $def = "### Dump table $table\n\n";
   $def .= "DROP TABLE IF EXISTS `$table`;\n";
   $query = "SHOW CREATE TABLE `$table`";
   $result = $DB->query($query);
   $DB->query("SET SESSION sql_quote_show_create = 1");
   $row = $DB->fetch_array($result);

   $def .= preg_replace("/AUTO_INCREMENT=\w+/i","",$row[1]);
   $def .= ";";
   return $def."\n\n";
}


/**  Get data of a table
* @param $DB DB object
* @param $table table  name
* @param $from begin from
* @param $limit limit to
*/
function get_content2($DB, $table,$from,$limit) {
//Toolbox::logInFile('php-errors',"get_content($table,$from,$limit)\n");
   $content = "";
   $gmqr = "";
   $result = $DB->query("SELECT *
                         FROM `$table`
                         LIMIT ".intval($from).",".intval($limit));
   if ($result) {
      $num_fields = $DB->num_fields($result);
      if (Toolbox::get_magic_quotes_runtime()) {
         $gmqr = true;
      }
      while ($row = $DB->fetch_row($result)) {
         if ($gmqr) {
            $row = Toolbox::addslashes_deep($row);
         }
         $insert = "INSERT INTO `$table` VALUES (";

         for( $j=0 ; $j<$num_fields ; $j++) {
            if (is_null($row[$j])) {
               $insert .= "NULL,";
            } else if ($row[$j] != "") {
               $insert .= "'".addslashes($row[$j])."',";
            } else {
               $insert .= "'',";
            }
         }
         $insert = preg_replace("/,$/","",$insert);
         $insert .= ");\n";
         $content .= $insert;
      }
   }
   return $content;
}

/**
 * Insère un tuple dans un table
 *
 * @param $table string: table name
 * @param $datas string: datas to insert
 *
 * return Nothing
 */
function insertToDB($table,$datas="")
{
	global $DB;
	
	$query="INSERT INTO $table
			VALUES ($datas)";
	
	$DB->query($query) or die("erreur de la requete $query ". $DB->error());
} // insertToDB()

// met à jour $table avec $_POST
function updateTable($table)
{
	global $DB;
	
	unset ($_POST["update"]);
	Session::checkRight("profile","w");
	
	$query_select = "SELECT *
					 FROM $table";
			  
	$result = $DB->query($query_select);
	
	for ($i = 1 ; $i < mysql_numfields($result) ; $i++)
	{
		if (mysql_field_type($result,$i) == "string") continue;	// sinon, on va modifier nbjours, ratio etc.

		$champ = $DB->field_name($result, $i);
		$query_del = "UPDATE $table
					  SET $champ = 1";
		$DB->query($query_del) or die ($query_del);

	}
	foreach (array_keys($_POST) as $i)
	{
		$col = getItemName($i, $table);
		$query_update = "UPDATE $table
						 SET $col = 0";
		$DB->query($query_update) or die ($query_update);
	}
} // updateTable()


// met à jour $table avec $_POST
function updateTypeIllim($table)
{
	global $DB;
	
	unset ($_POST["update"]);
	Session::checkRight("profile","w");
	
	// on remet tout à zéro d'abord
	//$query_update = "UPDATE $table
	//			 SET illimite = 1";
	$query_update = "delete FROM $table";
	$DB->query($query_update) or die ($query_update);

	foreach (array_keys($_POST) as $i)
	{
		/*$query_update = "UPDATE $table
						 SET illimite = 0
						 WHERE id = $i";*/
		 $query_update = "INSERT INTO $table VALUES ($i,0)";
		 
		$DB->query($query_update) or die ($query_update);
	}
} // updateTypeIllim()

function whichContratSend($item)
{
	global $DB;
	
	$query_item = "SELECT $item item
				   FROM  glpi_plugin_bestmanagement_mailing";
	
	if($res_item = $DB->query($query_item))
		if ($row = $DB->fetch_assoc($res_item))
			return !$row["item"];
	return 1;
} // whichContratSend()

/**
 * Retourne le nom de l'élément
 *
 * @param $i	 int :		indice de l'élément dans la table
 * @param $table string :	table dans laquelle se fait la recherche
 *
 * return string: nom de l'élément
 */
function getItemName($i, $table)
{
	global $DB;

	$i++; // on ne souhaite pas avoir comme attribut l'id
	
	$query_select = "SELECT *
					 FROM $table";
			  
	$result = $DB->query($query_select);
	
	for ($j = 0 ; $j < mysql_numfields($result) ; $j++)
		if ($j == $i)	return $DB->field_name($result, $j);

	return false;
} // getItemName()

/**
 * Retourne les adresses e-mails
 *
 * return array: tableau des adresses
 */
function getAdresses()
{
	return preg_split("/[\s,;]+/", getItem("destinataires", "glpi_plugin_bestmanagement_config"));
} // getAdresses()

// vérifie les contrats en double
// param : tableau contenant les contrat
// retourne la condition pour le WHERE
function verifDoublonsQuery($all_contrats)
{
	$contrats_deja_selected = "AND cont.id NOT IN (";
	foreach($all_contrats as $key => $k)
	{
		foreach($k as $id)
			$contrats_deja_selected .= "$id,";
		
		$au_moins_un = true;
	}
	if(isset($au_moins_un))	// on remplace la dernière virgule par ")"
		return substr_replace($contrats_deja_selected, ")", -1, 1);
	else
		return "";	// pas de contrats
} // verifDoublonsQuery()

/**
 * Retourne l'état de la checkbox
 *
 * @param $i	 int :		indice de l'élément dans la table
 * @param $table string :	table dans laquelle se fait la recherche
 *
 * return boolean
 */
function isItemChecked($i, $table)
{
	global $DB;
	
	$att = getItemName($i, $table);
	
	$query_checked = "SELECT $att att
					  FROM $table";

	if ($result=$DB->query($query_checked))
		$row=$DB->fetch_assoc($result);
			return !$row["att"];

	return false;
} // isItemChecked

/**
 * Retourne la fréquence de la téche périodique
 *
 * return int
 */
function getFrequency()
{
	global $DB;
	
	$query_frequency = "SELECT frequency
						FROM glpi_crontasks
						WHERE itemtype = 'PluginBestmanagementContrat'
							AND name = 'Verif'";

	if ($result=$DB->query($query_frequency))
		$row=$DB->fetch_assoc($result);
			return $row["frequency"];
} // getFrequency()

// return true or false
// check if fields must be completed
function VerifAddMsg($element)
{
	global $DB;
	
	$query_checked = "SELECT $element att
					  FROM glpi_plugin_bestmanagement_config";

	if ($result=$DB->query($query_checked))
		$row=$DB->fetch_assoc($result);
			return !$row["att"];
} // VerifAddMsg()

/**
 * Retourne un booléen pour savoir si ou non on affiche
 * la couleur de fond quand l'élément est une priorité
 *
 * return boolean
 */
function isBgColor()
{
	global $DB;
	
	$query_color = "SELECT color_priority
					FROM glpi_plugin_bestmanagement_config";

	if ($result=$DB->query($query_color))
		if ($row=$DB->fetch_assoc($result))
			return $row["color_priority"];
			
	return false;
} // isBgColor()

/**
 * Retourne la valeur d'un attribut
 *
 * @param $item	 string :	attribut
 * @param $table string :	table dans laquelle se fait la recherche
 *
 * return string
 */
function getItem($item, $table)
{
	global $DB;
	
	$query_item = "SELECT $item
					FROM $table";
	
	if ($result=$DB->query($query_item))
		if ($row=$DB->fetch_assoc($result))
			return stripslashes($row[$item]);
	
	return 0;
} // getItem()

/**
 * Arrange le tableau d'identifiants des tickets
 * pour séparer le contrat du hors contrat
 *
 * @param $tabID	array :	tableau d'id de tickets
 *
 * return array
 */
function arrangeForHC($TabID)
{
	global $DB;
	
	$tabOrderID = array();
	
	$trackID = "(";
	foreach($TabID as $i)
		$trackID .= $i . ",";
		
	$trackID = substr($trackID, 0, -1);	// pour enlever la virgule � la fin
	$trackID .= ")";

	// requète pour avoir le titre et le temps total du ticket
	$query =   "SELECT ticket.id
				FROM glpi_tickets ticket
					LEFT JOIN glpi_plugin_bestmanagement_link_ticketcontrat link
						ON ticket.id = link.ID_Ticket
				WHERE ticket.id IN $trackID
				ORDER BY link.ID_Contrat DESC";
	
	if($result = $DB->query($query))
		if($DB->numrows($result) > 0)
			while ($row = $DB->fetch_assoc($result))
				array_push($tabOrderID, $row["id"]);
	
	return $tabOrderID;
} // arrangeForHC()

/**
 * Est-ce qu'on intègre les CGV dans le PDF ?
 *
 * return boolean : true si oui
 */
function integrerCGV()
{
	return isItemChecked(13, "glpi_plugin_bestmanagement_pdf");
} // integrerCGV()

function isTypeIllim($id)
{
	global $DB;
	
	$query = "SELECT illimite
			  FROM glpi_plugin_bestmanagement_typecontrat
			  WHERE id = $id";
	
	if($res = $DB->query($query))
		if ($row = $DB->fetch_assoc($res))
			return !$row["illimite"];
	return 0;
} // isTypeIllim()

function showAllContracts($all_contrats)
{
	global $LANG;

	echo "<table class='tab_cadre' style='margin-top:1em; margin-bottom:1em;'>";
	echo "<tr> <th colspan='6'>" . $LANG["bestmanagement"]["allrecap"][0] . "</th> </tr>"; // titre

	foreach($all_contrats as $id)
	{
		
		if (isset($contrat))	// ligne vierge pour s�parer les contrats
			echo "<tr><th colspan='6'>&nbsp;</th></tr>";

		$contrat = new PluginBestmanagementContrat($id);
		
		if($contrat->isContratIllim())
			$colonnes = array("", "", $LANG["bestmanagement"]["allrecap"][4], "");
		else
			$colonnes = array($LANG["bestmanagement"]["allrecap"][2],
					  $LANG["bestmanagement"]["allrecap"][3],
					  $LANG["bestmanagement"]["allrecap"][4],
					  $LANG["bestmanagement"]["allrecap"][5]);
		
		$th3= "<th class='tab_bg_2' colspan='5'>";	// th normal colspan=5
		
		echo "<tr>";
		// lien vers le contrat
		echo $th3."<a href=\"".GLPI_ROOT."/front/contract.form.php?id=$id\">".$contrat->giveRealName()."</a>";
		// date de fin format�e
		echo "&nbsp;&nbsp;(" . $LANG["bestmanagement"]["allrecap"][11] . Html::convDate($contrat->dateFin()) . ")";
		echo "&nbsp;&nbsp;-&nbsp;&nbsp;" . $contrat->giveManagement() . "</th>";
		echo "</tr>";
		
		if ($contrat->infoCompteur())
		{
				$info_compteur = $contrat->infoCompteur();
				if ($info_compteur["compteur"] == "category")
					$compteur = $LANG['common'][36];		// cat
				else
					$compteur = $LANG['joblist'][2];		// prio
					
				array_unshift($colonnes, $compteur);
				echo "<tr>";
				foreach ($colonnes as $col)
					echo "<th>".$col."</th>";
				echo "</tr>";
				array_shift($colonnes);
				echo $contrat->currentRecap();	// tableau r�capitulatif
				
		}	
		
	} // foreach
	
	echo "</table>";
} // showAllContracts()


/**
 * Retourne un tableau contenant les
 * couleurs des lignes du mailing
 *
 * return array()
 */
function getMailColors()
{
	$colors = array();
	
	$color = getItem("colormail", "glpi_plugin_bestmanagement_config");
	
	switch ($color)
	{
	  case "blue" :
		$colors["titre"]= "#04497B";
		$colors["tr1"]	= "#E3F0F4";
		$colors["tr2"]	= "#C8E0EA";
		break;
	  case "red" :
		$colors["titre"]= "#7C0F03";
		$colors["tr1"]	= "#F4F0E4";
		$colors["tr2"]	= "#EAC8C8";
		break;
	  case "green" :
		$colors["titre"]= "#065F04";
		$colors["tr1"]	= "#E3F0E4";
		$colors["tr2"]	= "#C8E0C8";
		break;
	}
	return $colors;
} // getMailColors()
?>