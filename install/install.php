<?php

function pluginBestmanagementInstall($version, $migration='') {
   global $DB;

   if ($migration == '') {
      $migration = new Migration($version);
   }
   
   $migration->displayMessage("Installation of plugin BestManagement");
   
   // ** Insert in DB
   $migration->displayMessage("Creation tables in database");
   $DB_file = GLPI_ROOT ."/plugins/bestmanagement/install/mysql/plugin_bestmanagement-empty.sql";
   $DBf_handle = fopen($DB_file, "rt");
   $sql_query = fread($DBf_handle, filesize($DB_file));
   fclose($DBf_handle);
   foreach ( explode(";\n", "$sql_query") as $sql_line) {
      if (Toolbox::get_magic_quotes_runtime()) $sql_line=Toolbox::stripslashes_deep($sql_line);
      if (!empty($sql_line)) $DB->query($sql_line);
   }
}

?>