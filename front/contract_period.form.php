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

$Contract_Period = new PluginSupportcontractContract_Period();

Html::header($LANG["supportcontract"]["title"][0],$_SERVER["PHP_SELF"], "plugins", 
             "supportcontract", "reconduction");

if (isset($_POST['reconduction_report'])) {
   $input = array();
   $input['id'] = $_POST['id'];
   $input['end'] = date('Y-m-d');
   $input['date_save'] = date('Y-m-d');
   $Contract_Period->update($input);

   $input = array();
   $input['contracts_id'] = $_POST['contracts_id'];
   $input['begin'] = date('Y-m-d');
   $input['date_save'] = date('Y-m-d');
   $input['report_credit'] = PluginSupportcontractPurchase::getUnusedUnits($_POST['id']);
   $Contract_Period->add($input);
   Html::back();
} else if (isset($_POST['reconduction'])) {
   $input = array();
   $input['id'] = $_POST['id'];
   $input['end'] = date('Y-m-d');
   $input['date_save'] = date('Y-m-d');
   $Contract_Period->update($input);

   $input = array();
   $input['contracts_id'] = $_POST['contracts_id'];
   $input['begin'] = date('Y-m-d');
   $input['date_save'] = date('Y-m-d');
   $input['report_credit'] = 0;
   $Contract_Period->add($input);
   Html::back();
} else if (isset($_POST['no_reconduction'])) {
   $input = array();
   $input['id'] = $_POST['id'];
   $input['end'] = date('Y-m-d');
   $input['date_save'] = date('Y-m-d');
   $Contract_Period->update($input);
   Html::back();
}

Html::footer();

?>