<?php

/*
   ------------------------------------------------------------------------
   Best Management
   Copyright (C) 2011-2013 by the Best Management Development Team.

   https://forge.indepnet.net/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Best Management project.

   Best Management is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Best Management is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Best Management. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Best Management
   @author    David Durieux
   @co-author 
   @copyright Copyright (c) 2011-2013 Best Management team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://forge.indepnet,net
   @since     2013
 
   ------------------------------------------------------------------------
 */

define('GLPI_ROOT', '../../..');

include (GLPI_ROOT . "/inc/includes.php");

$pbTicket_Contract = new PluginBestmanagementTicket_Contract();

Html::header($LANG["bestmanagement"]["title"][0],$_SERVER["PHP_SELF"], "plugins", 
             "bestmanagement", "contract");

if (isset ($_POST["add"])) {
   if ($_POST['invoice_state'] == 3) {
      $_POST['unit_number'] = 0;
      $_POST['invoice_number'] = '';
   }   
   $pbTicket_Contract->add($_POST);
   Html::back();
} else if (isset ($_POST["update"])) {
   $pbTicket_Contract->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   $pbTicket_Contract->delete($_POST);
   Html::back();
}

Html::footer();
?>