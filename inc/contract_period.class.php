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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginBestmanagementContract_Period extends CommonDBTM {
   
   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName() {
      return 'Periodes du contrat';
   }



   function canCreate() {
      return true;
   }


   function canView() {
      return true;
   }

   
   
   function showForm(PluginBestmanagementContract $pbContract) {
      global $LANG, $DB;
      
      $this->check(-1,'w');
      $options = array();
      
      $this->showFormHeader($options);

      $a_period = $this->getCurrentPeriod($pbContract->fields['contracts_id']);
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo "Date de début de contrat :";
      echo "</td>";
      echo "<td>";
      echo Html::convDate($a_period['begin']);
      echo "</td>";
      echo "<td>";
      echo "Unité :";
      echo "</td>";
      echo "<td>";
      echo PluginBestmanagementContract::getUnit_typeNameForContract($pbContract->fields['contracts_id']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>";
      echo "</td>";
      echo "<td>";
      echo "Unités reportées de la période précédente :";
      echo "</td>";
      echo "<td>";
      echo $a_period['report_credit'];
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo "Unités achetées :";
      echo "</td>";
      echo "<td>";
      $query = "SELECT SUM(`unit`) FROM `glpi_plugin_bestmanagement_purchases`
         WHERE `plugin_bestmanagement_contracts_periods_id`='".$a_period['id']."'";
      if ($result = $DB->query($query)) {
         $nb = $DB->result($result,0,0);
         if (is_null($nb)) {
            echo '0';
         } else {
            echo $nb;
         }
      }
      echo "</td>";
      echo "<td>";
      echo "Unités non consommées :";
      echo "</td>";
      echo "<td>";
      echo PluginBestmanagementPurchase::getUnusedUnits($a_period['id']);
      echo "";
      echo "</td>";
      echo "</tr>";

      // Verify not have tickets not invoiced
      $nb_tickets_not_inv = PluginBestmanagementPurchase::getUninvoicedUnits($a_period['id']);
      if ($nb_tickets_not_inv > 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4' align='center'>";
         echo "<strong>Impossible de clôturer cette période car il reste des tickets à clôturer</strong>";
         echo "</td>";
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' align='center'>";
         echo "<input type='hidden' name='contracts_id' value='".$pbContract->fields['contracts_id']."' />";
         echo "<input type='hidden' name='pbcontracts_id' value='".$pbContract->fields['id']."' />";
         echo "<input type='hidden' name='id' value='".$a_period['id']."' />";
         echo "<input type='submit' name='reconduction' value=\"Clôture + reconduction sans report\" class='submit'>";
         echo "</td>";
         echo "<td colspan='2' align='center'>";
         echo "<input type='submit' name='reconduction_report' value=\"Clôture + reconduction avec report\" class='submit'>";
         echo "</td>";
         echo "</tr>";
         
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4' align='center'>";
         echo "<input type='submit' name='no_reconduction' value=\"Clôture sans reconduction\" class='submit'>";
         echo "</td>";
         echo "</tr>";

      }
      
      echo "</table>";
      Html::closeForm();
   }
   
   
   
   function closePurchases($contracts_id, $report=FALSE) {
      global $DB;
      
      // If have some units used but not bought, may display a message
      
      $pbPurchase = new PluginBestmanagementPurchase();
      
      $report_units = 0;
      
      $a_purchases = $pbPurchase->find("`contracts_id`='".$contracts_id."'
                           AND `close_date` IS NULL");
      
      // get total units used
      
      
      foreach ($a_purchases as $data) {
         $input = array();
         $input['id'] = $data['id'];
         $input['close_date'] = $_SESSION['glpi_currenttime'];
         
         if ($report) {
            // Get number of unit bought
            
            
            
         }
         $pbPurchase->update($input);
      }
      
      
      
   }
   
   
   
   function showList(PluginBestmanagementContract $pbContract) {
      
      $a_periods = $this->find("`contracts_id`='".$pbContract->fields['contracts_id']."'
         AND `end` IS NOT NULL");
      
      if (count($a_periods)) {
         echo "<table class='tab_cadre_fixe'>";
         
         echo "<tr class='tab_bg_1'>";
         echo '<th>';
         echo "Début";
         echo '</th>';
         echo '<th>';
         echo "Fin";
         echo '</th>';
         echo '<th>';
         echo "Unités reportées de la période précédente";
         echo '</th>';
         echo '</tr>';
      }
      
      foreach ($a_periods as $data) {
         echo "<tr class='tab_bg_1'>";
         echo '<td>';
         echo Html::convDate($data['begin']);
         echo '</td>';
         echo '<td>';
         echo Html::convDate($data['end']);
         echo '</td>';
         echo '<td>';
         echo $data['report_credit'];
         echo '</td>';
         echo '</tr>';
      }
      
      if (count($a_periods)) {
         echo '</table>';
      }
   }
   
   
   
   function getCurrentPeriod($contracts_id) {
      $a_periods = $this->find("`contracts_id`='".$contracts_id."'
         AND `end` IS NULL", "", 1);
      if (count($a_periods) == 0) {
         return FALSE;
      }
      return current($a_periods);
   }
}

?>