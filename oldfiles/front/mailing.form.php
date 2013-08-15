<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Page de configuration du contenu du mail
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include_once (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("profile","r");
Plugin::load("bestmanagement",true);
Html::header($LANG["bestmanagement"]["config"][3], $_SERVER["PHP_SELF"],"config","plugins");

$table = "glpi_plugin_bestmanagement_mailing";
// Partie traitements
if (isset($_POST["update"]))
{
	unset ($_POST["update"]);
	Session::checkRight("profile","w");
	
	$query_del = "UPDATE $table
				  SET contratended = 1,
					  contratending = 1,
					  consoexceeded = 1,
					  ratioexceeded = 1";
	$DB->query($query_del) or die ($query_del);
	
	// état à 1 (waiting)
	$query_state = "UPDATE glpi_crontasks
					SET state = 1
						WHERE itemtype = 'PluginBestmanagementContrat'
							AND name = 'Verif'";
	$DB->query($query_state) or die ($query_state);
		
	foreach (array_keys($_POST) as $i)
	{
		$aucun = true;
		$col = getItemName($i, $table);
		$query_update = "UPDATE $table
						 SET $col = 0";
		$DB->query($query_update) or die ($query_update);
	}
	
	if (!isset($aucun))
	{ // mettre l'état à 0 (disabled)
		unset($aucun);
		$query_state = "UPDATE glpi_crontasks
						SET state = 0
						WHERE itemtype = 'PluginBestmanagementContrat'
							AND name = 'Verif'";
		$DB->query($query_state) or die ($query_state);
	}
}
else if (isset($_POST["frequence"]))
{
	$frequency = $_POST["frequency"];

	$query_frequence = "UPDATE glpi_crontasks
						SET frequency = $frequency
						WHERE itemtype = 'PluginBestmanagementContrat'
							AND name = 'Verif'";
	$DB->query($query_frequence) or die ($query_frequence);
	
	if (isset($_POST["destinataires"]))
	{
		$destinataires = $_POST["destinataires"];

		$query_destinataires = "UPDATE glpi_plugin_bestmanagement_config
								SET destinataires = '$destinataires'";
		$DB->query($query_destinataires) or die ($query_destinataires);
	}
}
// Fin traitements

$showFreq = false;

// Quels tableaux récapitulatifs envoyer ?
echo "<div class='center'><form method='post' action=\"".$_SERVER["PHP_SELF"]."\">";
echo "<input type='hidden' name='update'>";
echo "<table class='tab_cadre'><tr><th colspan='2'><a href='config.form.php'>";
echo $LANG["bestmanagement"]["config"][0]."</a><br>&nbsp;<br>";
echo $LANG["bestmanagement"]["config"][3] . "</th></tr>\n";

$query_items_mail = "SELECT *
					 FROM $table";

$res=$DB->query($query_items_mail) or die ($query_items_mail);

$nbcols = $DB->num_fields($res) - 1; // on enlève l'attribut ID pour le comptage des colonnes
$tab = array();

for ($i = 0 ; $i < $nbcols ; $i++)
{
	$name = getItemName($i, $table);
	$checked = (isItemChecked($i, $table)) ? "checked" : "";
	
	if ($checked == "checked")
		$showFreq = true;
	echo "<tr class='tab_bg_1'><td>" . $LANG["bestmanagement"]["config"]["mailing_".$name]. "</td>";
	echo "<td class='center'><input type='checkbox' $checked name='$i'></td></tr>";

}
echo "<tr class='tab_bg_1 center'><td colspan='2'><input type='submit' value='".$LANG["buttons"][2]."' class='submit' ></td></tr>";
echo "</form></div>";

if ($showFreq)
{
	// Fréquence de l'envoi des mails
	echo "<div class='center'><form method='post' action=\"".$_SERVER["PHP_SELF"]."\">";
	echo "<tr><th colspan='2'>" . $LANG["crontask"][37] . "</th></tr>";

	echo "<input type='hidden' name='frequence'>";
	$ct = new CronTask();
	echo "<tr class='tab_bg_1 center'><td colspan='2'>";
	$ct->dropdownFrequency('frequency',getFrequency());
	echo "</td></tr>";
	
	// Destinataires
	$value = getItem("destinataires", "glpi_plugin_bestmanagement_config");
	
	echo "<tr><th colspan='2'>" . $LANG['mailing'][121] . "</th></tr>";
	echo "<tr class='tab_bg_1 center'><td colspan='2'>";
	echo "<input type='text' size='50' maxlength='255' name='destinataires' value='$value'></td></tr>";
	
	echo "<tr class='tab_bg_1 center'><td colspan='2'><input type='submit' value='".$LANG["buttons"][2]."' class='submit' ></td></tr>";
}
echo "</table></form></div>";
Html::footer();
?>
