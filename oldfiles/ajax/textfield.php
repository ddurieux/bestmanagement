<?php
// ----------------------------------------------------------------------
// Original Author of file: 
// Purpose of file:
// ----------------------------------------------------------------------

$AJAX_INCLUDE=1;

define('GLPI_ROOT','../../..');
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['name']))
{
	echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/bestmanagement.form.php\">";
		echo "<input type='hidden' name='id' value='".$_POST['id']."'>";
		echo "<input type='text' ".(isset($_POST['cols'])?" size='".$_POST["cols"]."' ":"").
				"maxlength='255' name='".$_POST['name']."' value='".$_POST["data"]."'>";
		if ($_POST['name'] == "num_fact_api")
			echo "<input type=\"submit\" name=\"ModifAPI\" class=\"submit\" value=\"".$LANG["buttons"][14]."\" >";
		else if ($_POST['name'] == "num_fact_ticket")
			echo "<input type=\"submit\" name=\"ModifAPITicket\" class=\"submit\" value=\"".$LANG["buttons"][14]."\" >";
	echo "</form>";
}

?>