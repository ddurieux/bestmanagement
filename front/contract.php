<?php

/*
   ------------------------------------------------------------------------
   Supportcontract
   Copyright (C) 2014-2014 by the Supportcontract Development Team.

   https://github.com/ddurieux/bestmanagement   
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Supportcontract project.

   Supportcontract is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Supportcontract is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Supportcontract. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Supportcontract
   @author    David Durieux
   @co-author
   @copyright Copyright (c) 2014-2014 Supportcontract team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://github.com/ddurieux/bestmanagement
   @since     2014

   ------------------------------------------------------------------------
 */

define('GLPI_ROOT', '../../..');

include (GLPI_ROOT . "/inc/includes.php");

$psContract = new PluginSupportcontractContract();

Html::header(__('Support contract', 'supportcontract'),$_SERVER["PHP_SELF"], "plugins", 
             "supportcontract", "contract");

if (!isset($_GET['display'])) {
   $_GET['display'] = 'summary';
}

$psContract->displayMenu();

if ($_GET['display'] == 'summary') {
   $psContract->showSummaryAllContracts();
} else if ($_GET['display'] == 'unaffectedtickets') {
   $psTicket_Contract = new PluginSupportcontractTicket_Contract();
   $psTicket_Contract->showUnaffectedTickets();   
}

Html::footer();

?>