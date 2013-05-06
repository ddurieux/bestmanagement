<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Page de configuration du contenu du rapport
//			d'intervention
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include_once (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("profile","r");
Plugin::load('bestmanagement',true);

Html::header($LANG["bestmanagement"]["config"][4], $_SERVER["PHP_SELF"],"config","plugins");

$table = "glpi_plugin_bestmanagement_pdf";

// Partie traitements
if (isset($_POST["update"]))
{
	global $DB;
	
	unset ($_POST["update"]);
	Session::checkRight("profile","w");
	
	// on vérifie si on a une ligne dans la table
	$query = "SELECT * FROM $table";

	if($resultat = $DB->query($query))
		if($DB->numrows($resultat) <= 0){
    // la table est vide : il faut insérer une ligne
      insertToDB($table,"0,0,0,0,0,0,0,0,0,0,0,0,0,0,0");  
    }
				
	$query_del = "UPDATE $table
				  SET entete = 1,
					  logo = 1,
					  cgv = 1";
	$DB->query($query_del) or die ($query_del);
		
	foreach (array_keys($_POST) as $key)
	{
		$_POST[$key] = ($_POST[$key] == "on") ? 0 : $_POST[$key];	// cas checkbox

		$query_update = "UPDATE $table
						 SET $key = '" . $_POST[$key] . "'";
		
		$DB->query($query_update) or die ($query_update);
	}
}

// Fin traitements

$showDetails = false;

// En-tete ?
echo "<div class='center'><form method='post' action=\"".$_SERVER["PHP_SELF"]."\">";
echo "<input type='hidden' name='update'>";
echo "<table class='tab_cadre'><tr><th colspan='2'><a href='config.form.php'>";
echo $LANG["bestmanagement"]["config"][0]."</a><br>&nbsp;<br>";
echo $LANG["bestmanagement"]["config"][4] . "</th></tr>";

$name = getItemName(0, $table);

$checked = (isItemChecked(0, $table)) ? "checked" : "";

if ($checked == "checked")
	$showDetails = true;

echo "<tr class='tab_bg_1'><td>" . $LANG["bestmanagement"]["propriete_pdf"][0]. "</td>";
echo "<td class='center'><input type='checkbox' $checked name='$name'></td></tr>";


echo "<tr><th colspan='2'>" . $LANG["bestmanagement"]["propriete_pdf"][2] . "</th></tr>";
for ($i = 3 ; $i <= 5 ; $i++)
{
	$j = $i - 1;
	$name = getItemName($j, $table);
	
	$value = getItem(getItemName($j, $table), $table);
	
	
	echo "<tr class='tab_bg_1'><td>" . $LANG["bestmanagement"]["propriete_pdf"][$i]. "</td>";
	echo "<td class='center'><input type='text' name='$name' value=\"$value\" size=40></td></tr>";
}


if ($showDetails)
{
	echo "<tr><th colspan='2'>" . $LANG["bestmanagement"]["propriete_pdf"][0] . "</th></tr>";
	
	// Logo
	$checked = (isItemChecked(1, $table)) ? "checked" : "";
	echo "<tr class='tab_bg_1'><td colspan='2'>" . $LANG["bestmanagement"]["propriete_pdf"][1]. " (glpi/plugins/bestmanagement/pics/logo_pdf.jpg)";
	echo "&nbsp;<input type='checkbox' $checked name='logo'></td></tr>";
	// ----
	
	for ($i = 0 ; $i <= 6 ; $i++)
	{
		$j = $i + 5;
		$name = getItemName($j, $table);
		
		$value = getItem(getItemName($j, $table), $table);
		
		echo "<tr class='tab_bg_1'><td>" . $LANG["bestmanagement"]["entete_pdf"][$i]. "</td>";
		echo "<td class='center'><input type='text' name='$name' value='$value' size=40></td></tr>";
	}
	// Footer
	echo "<tr><th colspan='2'>" . $LANG["bestmanagement"]["propriete_pdf"][6] . "</th></tr>";
	$value = getItem("footer", $table);
	echo "<tr><td class='center' colspan='2'><textarea name='footer' rows=4 cols=40>$value</textarea></td></tr>";
	// ------
	
	// CGV
	$checked = (isItemChecked(13, $table)) ? "checked" : "";
	echo "<tr class='tab_bg_1'><td colspan='2'>" . $LANG["bestmanagement"]["propriete_pdf"][7]. "<br>(glpi/plugins/bestmanagement/files/cgv.txt)";
	echo "&nbsp;<input type='checkbox' $checked name='cgv'></td></tr>";
	// ---
}

echo "<tr class='tab_bg_1 center'><td colspan='2'><input type='submit' value='".$LANG["buttons"][2]."' class='submit' ></td></tr>";
echo "</table></form></div>";

Html::footer();
?>
