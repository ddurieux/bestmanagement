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

class PluginBestmanagementTicket_Contract extends CommonDBTM {
   
   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName() {
      return '';
   }



   function canCreate() {
      return true;
   }


   function canView() {
      return true;
   }

   
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;
      
      $itemtype = $item->getType();
      if ($itemtype == 'Ticket') {
         if (isset($_POST['glpi_tab']) 
                 && $_POST['glpi_tab'] == 'PluginPdfTicket$1') {
            return $LANG["bestmanagement"]["pdf"][10]." (".$LANG["bestmanagement"]["title"][0].")";
         } else {        
            return $LANG["bestmanagement"]["config"][0];
         }
      }
      return '';
   }
   
   
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      
      $pbTicket_Contract = new self();
      if ($item->getID() > 0) {
         $pbTicket_Contract->showContract();
      }
      echo "Raport d'intervention";
      return true;
   }
   
   
   
   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      if ($item->getType() == 'Ticket') {
         $pbTicket_Contract = new self();
         $pbTicket_Contract->showSummaryPDF($pdf, $item);
         return true;
      }
      return false;
   }
   

   
   /**
    * Display tickets associated with contract
    * 
    * @param type $contracts_id
    */
   function showTickets($contracts_id) {
      global $LANG;

      $pbContract_Period = new PluginBestmanagementContract_Period();
      
      $a_period = $pbContract_Period->getCurrentPeriod($contracts_id);

      $elements = $this->getInvoiceState();
      foreach ($elements as $invoice_state=>$element) {
         echo "<table class='tab_cadre_fixe'>";
         
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='11'>";
         echo $element;
         echo "</th>";
         echo "</tr>";
         
         echo "<tr class='tab_bg_1'>";
         echo "<th>";
         echo $LANG['common'][2];
         echo "</th>";
         echo "<th>";
         echo $LANG['common'][57];
         echo "</th>";
         echo "<th>";
         echo $LANG['entity'][0];
         echo "</th>";
         echo "<th>";
         echo $LANG['joblist'][0];
         echo "</th>";
         echo "<th>";
         echo $LANG['joblist'][2];
         echo "</th>";
         echo "<th>";
         echo $LANG['job'][31];
         echo "</th>";
         echo "<th>";
         echo $LANG['reports'][60];
         echo "</th>";
         echo "<th>";
         echo ItilCategory::getTypeName();
         echo "</th>";
         echo "<th>";
         echo TaskCategory::getTypeName();
         echo "</th>";
         echo "<th>";
         echo $LANG["bestmanagement"]["sort"][11];
         echo "</th>";
         echo "<th>";
            if ($invoice_state != 1
                    && $invoice_state != 3) {
            echo $LANG["bestmanagement"]["facturation_ticket"][4];
         }
         echo "</th>";
         echo "</tr>";

         $a_links = $this->find("`contracts_id`='".$contracts_id."'
                                 AND `invoice_state`='".$invoice_state."'
                                 AND `plugin_bestmanagement_contracts_periods_id`='".$a_period['id']."'");
         foreach($a_links as $data) { 
            $this->showTicketsDetail($data['tickets_id'], 
                                     $invoice_state, 
                                     $data['invoice_number'],
                                     $contracts_id,
                                     $data['unit_number']);
         }      
         echo "</table>";
         echo "<br/>";
      }
   }
   
   
   
   function showTicketsDetail($tickets_id, $invoice_state=1, $invoice_number='', $contracts_id=0, $unit_number=0) {
      global $CFG_GLPI;
      
      $ticket = new Ticket();
      
      $ticket->getFromDB($tickets_id);

      echo "<tr class='tab_bg_3'>";
      echo "<td>";
      echo $ticket->fields['id'];
      echo "</td>";
      echo "<td>";
      echo $ticket->getLink(1);
      echo "</td>";
      echo "<td>";
      echo Dropdown::getDropdownName("glpi_entities", $ticket->fields['entities_id']);
      echo "</td>";
      echo "<td>";
      $status = Ticket::getStatus($ticket->fields['status']);
      echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/".$ticket->fields['status'].".png\"
                     alt=\"$status\" title=\"$status\">&nbsp;$status";
      echo "</td>";
      echo "<td style=\"background-color:".$_SESSION["glpipriority_".$ticket->fields['priority']].";\">";
      echo Ticket::getPriorityName($ticket->fields['priority']);
      echo "</td>";
      echo "<td>";

      echo "</td>";
      echo "<td>";
      echo Html::convDateTime($ticket->fields['date']);
      echo "</td>";
      echo "<td>";
      echo Dropdown::getDropdownName("glpi_itilcategories", $ticket->fields['itilcategories_id']);
      echo "</td>";
      echo "<td>";

      echo "</td>";     
      echo "<td>";
      if (PluginBestmanagementContract::getUnit_typeForContract($contracts_id) == 'hour') {
         // echo count task time
         $a_taskcategories = array();
         $a_tasks = getAllDatasFromTable("glpi_tickettasks", "`tickets_id`='".$tickets_id."'");
         foreach ($a_tasks as $a_task) {
            if (isset($a_taskcategories[$a_task['taskcategories_id']])) {
               $a_taskcategories[$a_task['taskcategories_id']] += $a_task['actiontime'];
            } else {
               $a_taskcategories[$a_task['taskcategories_id']] = $a_task['actiontime'];
            }
         }
         $elements = array();
         foreach ($a_taskcategories as $taskcategories_id=>$time) {
            $elements[] = PluginBestmanagementToolbox::displayHours($time / 3600, 1)." (".
                 Dropdown::getDropdownName("glpi_taskcategories", $taskcategories_id).")";
         }
         echo implode("<br/>", $elements);
      } else {
         echo $unit_number;
      }

      echo "</td>";     
      echo "<td>";
      if ($invoice_state != 1
              && $invoice_state != 3) {
         echo $invoice_number;
      }
      echo "</td>";
      echo "</tr>";
   }
   
   
   
   /**
    * Form to add link between a ticket and a contract
    */
   function showForm($options = array()) {
      global $LANG, $DB, $CFG_GLPI;
      
      $tickets_id = $_POST['id'];
      $ticket = new Ticket();
      $ticket->getFromDB($tickets_id);
      
      $this->getEmpty();

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][1]."&nbsp;:</td>";
      echo "<td>";
      $query = "SELECT `glpi_contracts`.`id` FROM `glpi_contracts`
         LEFT JOIN `glpi_plugin_bestmanagement_contracts`
            ON `contracts_id`=`glpi_contracts`.`id`
         WHERE `glpi_plugin_bestmanagement_contracts`.`id`IS NULL";
      $result = $DB->query($query);
      $a_contracts_used = array();
      while ($data=$DB->fetch_array($result)) {
         $a_contracts_used[] = $data['id'];         
      }     
      $rand = Dropdown::show('Contract', array('entity' => $ticket->fields['entities_id'],
                                               'used'   => $a_contracts_used,
                                               'toadd'  => array('-1' => $LANG["bestmanagement"]["contrat"][14])));
      echo "</td>";
      echo "<td>".$LANG["bestmanagement"]["sort"][11]."&nbsp;:</td>";
      echo "<td>";
      
      $params=array('contracts_id'=>'__VALUE__',
                    'rand'=>$rand,
                    'myname'=>'contracts_id',
                    'name' => 'unitnumber');

      Ajax::updateItemOnEvent(
              'dropdown_contracts_id'.$rand,
              'show_unitnumber',
              $CFG_GLPI["root_doc"]."/plugins/bestmanagement/ajax/dropdownunitnumber.php",
              $params);
      // Si contrat hour, afficher 'automatique', sinon dropdown::showinteger
      echo "<div id='show_unitnumber'></div>";
      
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG["bestmanagement"]["facturation_ticket"][3]."&nbsp;:</td>";
      echo "<td>";
      $elements = $this->getInvoiceState();
      Dropdown::showFromArray("invoice_state", $elements);      
      echo "</td>";
      echo "<td>".$LANG["bestmanagement"]["facturation_ticket"][4]."&nbsp;:</td>";
      echo "<td>";
      echo "<input type='text' name='invoice_number' value='' />";      
      echo "<input type='hidden' name='tickets_id' value='".$tickets_id."' />";
      
      echo "</td>";
      echo "</tr>";      

      $this->showFormButtons($options);

      return true;
   }
   
   
   
   function showContract() {
      global $LANG;
      
      $tickets_id = $_POST['id'];
      
      $contract = new Contract();
      
      $nb_contracts = countElementsInTable($this->getTable(), "`tickets_id`='".$tickets_id."'");
      // if no ticket_contract, add form (showForm)
      if ($nb_contracts == 0) {
         $this->showForm();
      } else {
         $a_tickets_crontracts = $this->find("`tickets_id`='".$tickets_id."'", "", 1);
         $data = current($a_tickets_crontracts);
         // Display link(s)
         echo "<form method='post' action='".GLPI_ROOT.
                        "/plugins/bestmanagement/front/ticket_contract.form.php'>";
         echo "<input type='hidden' name='id' value='".$data['id']."' />";
         echo "<table class='tab_cadre_fixe'>";

         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='7'>Contrat lié :</th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<th>Contrat</th>";
         echo "<th>Type</th>";
         echo "<th>Unités</th>";
         echo "<th>Etat facturation</th>";
         echo "<th>Numéro de facture</th>";
         echo "<th></th>";
         echo "<th></th>";
         echo "</tr>";
            
         echo "<tr class='tab_bg_1'>";
         $contract->getFromDB($data['contracts_id']);
         echo "<td>".$contract->getLink(1)."</td>";
         echo "<td>";            
         echo PluginBestmanagementContract::getUnit_typeNameForContract($data['contracts_id']);
         echo "</td>";
         echo "<td>";
         if (PluginBestmanagementContract::getUnit_typeForContract($data['contracts_id']) == 'hour') {
            // echo count task time
            $a_taskcategories = array();
            $a_tasks = getAllDatasFromTable("glpi_tickettasks", "`tickets_id`='".$tickets_id."'");
            foreach ($a_tasks as $a_task) {
               if (isset($a_taskcategories[$a_task['taskcategories_id']])) {
                  $a_taskcategories[$a_task['taskcategories_id']] += $a_task['actiontime'];
               } else {
                  $a_taskcategories[$a_task['taskcategories_id']] = $a_task['actiontime'];
               }
            }
            $elements = array();
            foreach ($a_taskcategories as $taskcategories_id=>$time) {
               $elements[] = PluginBestmanagementToolbox::displayHours($time / 3600, 1)." (".
                    Dropdown::getDropdownName("glpi_taskcategories", $taskcategories_id).")";
            }
            echo implode("<br/>", $elements);
         } else {
            if ($data['invoice_number'] == '') {
               Dropdown::showInteger("unit_number", $data['unit_number']);
            } else {
               echo $data['unit_number'];
            }
         }
         echo "</td>";
         echo "<td>";
         $elements = $this->getInvoiceState();
         Dropdown::showFromArray("invoice_state", $elements, array('value' => $data['invoice_state']));
         echo "</td>";
         echo "<td>";
         if ($data['invoice_state'] != 3) {
            echo "<input type='text' name='invoice_number' size='15' value='".$data['invoice_number']."'>";
         }
         echo "</td>";
         echo "<td>";
         echo "<input type='submit' name='update' value='".$LANG['buttons'][7]."' class='submit' />";
         echo "</td>";
         echo "<td>";
         if ($data['invoice_number'] == '') {
            echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][22]."\"
                         class='submit' ".Html::addConfirmationOnAction($LANG['common'][50]).">";
         }
         echo "</td>";
         echo "</tr>";

         echo "</table>";
         Html::closeForm();
      }      
   }
   
   
   
   function showUnaffectedTickets() {
      global $DB, $LANG;
      
      $entities_contract = array();
      $query = "SELECT `glpi_contracts`.* FROM `glpi_plugin_bestmanagement_contracts`
         LEFT JOIN `glpi_contracts` ON `glpi_contracts`.`id`=`glpi_plugin_bestmanagement_contracts`.`contracts_id`
         ORDER BY `name`";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $entities_contract = array_merge($entities_contract, 
                                          getSonsOf("glpi_entities", $data['entities_id']));
      }
      $a_entities = array_intersect($entities_contract, $_SESSION['glpiactiveentities']);
      
      $query = "SELECT `glpi_tickets`.* FROM `glpi_tickets`
         LEFT JOIN `".$this->getTable()."`
            ON `".$this->getTable()."`.`tickets_id`=`glpi_tickets`.`id`
         WHERE `".$this->getTable()."`.`id` IS NULL
            AND `entities_id` IN (".implode(',', $a_entities).")
         ORDER BY `glpi_tickets`.`id` DESC";
      $result = $DB->query($query);
      
      $invoice_state = 1;
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo $LANG['common'][2];
      echo "</th>";
      echo "<th>";
      echo $LANG['common'][57];
      echo "</th>";
      echo "<th>";
      echo $LANG['entity'][0];
      echo "</th>";
      echo "<th>";
      echo $LANG['joblist'][0];
      echo "</th>";
      echo "<th>";
      echo $LANG['joblist'][2];
      echo "</th>";
      echo "<th>";
      echo $LANG['job'][31];
      echo "</th>";
      echo "<th>";
      echo $LANG['reports'][60];
      echo "</th>";
      echo "<th>";
      echo ItilCategory::getTypeName();
      echo "</th>";
      echo "<th>";
      echo TaskCategory::getTypeName();
      echo "</th>";
      echo "<th>";
         if ($invoice_state != 1) {
         echo $LANG["bestmanagement"]["facturation_ticket"][4];
      }
      echo "</th>";
      echo "</tr>";
      while ($data=$DB->fetch_array($result)) {
         $this->showTicketsDetail($data['id']);
      }
      echo "</table>";
   }
   

   
   function getInvoiceState() {
      global $LANG;
      
      return array(
          0 => $LANG["bestmanagement"]["facturation_ticket"][0],
          1 => $LANG["bestmanagement"]["facturation_ticket"][1],
          2 => $LANG["bestmanagement"]["facturation_ticket"][2],
          3 => $LANG["bestmanagement"]["facturation_ticket"][5]
      );
      
   }
   
   
   
   function showSummaryPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item) {
      global $DB, $LANG;
      
      $pbContract = new PluginBestmanagementContract();
      $contract = new Contract();
      
      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.$LANG["bestmanagement"]["pdf"][10].'</b>');
      $pdf->displaySpace();
      
      $pdf->setColumnsSize(50,50);
      
      $a_tickets_crontracts = $this->find("`tickets_id`='".$item->getID()."'", "", 1);
      $data = current($a_tickets_crontracts);
      $contract->getFromDB($data['contracts_id']);      
      $pbContract->showSummaryPDF($pdf, $contract);
      
      $pdf->setColumnsSize(30,50, 20);
      $pdf->displayTitle("<b>Ticket ".$item->getID()."</b>", $item->fields['name'], Html::convDateTime($item->fields['date']));
      $pdf->setColumnsSize(100);
      $pdf->displayText('', $item->fields['content']);
      
      $ticketTask = new TicketTask();
      $user = new User();
      $a_ticketTasks = $ticketTask->find("`tickets_id`='".$item->getID()."'
         AND `is_private`='0'");
      foreach ($a_ticketTasks as $data) {
         $pdf->setColumnsSize(30,70);
         $pdf->displayTitle("Tâche ".$data['id'], Html::convDateTime($data['date']));
         $user->getFromDB($data['users_id']);
         $pdf->displayLine($user->getName(), $data['content']);
      }
      
      if ($item->fields['solution'] != '') {
         $pdf->displaySpace();
         $pdf->setColumnsSize(100);
         $pdf->displayTitle("Solution");
         $pdf->displayText('', $item->fields['solution']);
      }
      
   }
}

?>