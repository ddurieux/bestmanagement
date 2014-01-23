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
   @author    David Durieux, Nicolas Mercier
   @co-author
   @copyright Copyright (c) 2014-2014 Supportcontract team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://github.com/ddurieux/bestmanagement
   @since     2014

   ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginSupportcontractTicket_Contract extends CommonDBTM {
   
   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName($nb=0) {
      return '';
   }



   static function canCreate() {
      return true;
   }


   
   static function canView() {
      return true;
   }

   
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      
      $itemtype = $item->getType();
      if ($itemtype == 'Ticket') {
         if (isset($_POST['glpi_tab']) 
                 && $_POST['glpi_tab'] == 'PluginPdfTicket$1') {
            return __('Intervention report', 'supportcontract')." (".__('Support contract', 'supportcontract').")";
         } else {        
            return __('Plugin configuration', 'supportcontract');
         }
      }
      return '';
   }
   
   
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      
      $psTicket_Contract = new self();
      if ($item->getID() > 0) {
         $psTicket_Contract->showContract();
      }
      echo __('Intervention report', 'supportcontract');
      return true;
   }
   
   
   
   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      if ($item->getType() == 'Ticket') {
         $psTicket_Contract = new self();
         $psTicket_Contract->showSummaryPDF($pdf, $item);
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

      $psContract_Period = new PluginSupportcontractContract_Period();
      
      $a_period = $psContract_Period->getCurrentPeriod($contracts_id);

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
         echo __('ID');
         echo "</th>";
         echo "<th>";
         echo __('Title');
         echo "</th>";
         echo "<th>";
         echo __('Entity');
         echo "</th>";
         echo "<th>";
         echo __('Status');
         echo "</th>";
         echo "<th>";
         echo __('Priority');
         echo "</th>";
         echo "<th>";
         echo __('New ticket');
         echo "</th>";
         echo "<th>";
         echo __('Opening date');
         echo "</th>";
         echo "<th>";
         echo ItilCategory::getTypeName();
         echo "</th>";
         echo "<th>";
         echo TaskCategory::getTypeName();
         echo "</th>";
         echo "<th>";
         echo __('Unit number', 'supportcontract');
         echo "</th>";
         echo "<th>";
            if ($invoice_state != 1
                    && $invoice_state != 3) {
            echo __('Invoice number', 'supportcontract');
         }
         echo "</th>";
         echo "</tr>";

         $a_links = $this->find("`contracts_id`='".$contracts_id."'
                                 AND `invoice_state`='".$invoice_state."'
                                 AND `plugin_supportcontract_contracts_periods_id`='".$a_period['id']."'");
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
      if (PluginSupportcontractContract::getUnit_typeForContract($contracts_id) == 'hour') {
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
            $elements[] = PluginSupportcontractToolbox::displayHours($time / 3600, 1)." (".
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
      global $DB, $CFG_GLPI;
      
      $tickets_id = $_POST['id'];
      $ticket = new Ticket();
      $ticket->getFromDB($tickets_id);
      
      $this->getEmpty();

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Contract')."&nbsp;:</td>";
      echo "<td>";
      $query = "SELECT `glpi_contracts`.`id` FROM `glpi_contracts`
         LEFT JOIN `glpi_plugin_supportcontract_contracts`
            ON `contracts_id`=`glpi_contracts`.`id`
         WHERE `glpi_plugin_supportcontract_contracts`.`id`IS NULL";
      $result = $DB->query($query);
      $a_contracts_used = array();
      while ($data=$DB->fetch_array($result)) {
         $a_contracts_used[] = $data['id'];         
      }     
      $rand = Dropdown::show('Contract', array('entity' => $ticket->fields['entities_id'],
                                               'used'   => $a_contracts_used,
                                               'toadd'  => array('-1' => __('Not in contract', 'supportcontract'))));
      echo "</td>";
      echo "<td>".__('Unit number', 'supportcontract')."&nbsp;:</td>";
      echo "<td>";
      
      $params=array('contracts_id'=>'__VALUE__',
                    'rand'=>$rand,
                    'myname'=>'contracts_id',
                    'name' => 'unitnumber');

      Ajax::updateItemOnEvent(
              'dropdown_contracts_id'.$rand,
              'show_unitnumber',
              $CFG_GLPI["root_doc"]."/plugins/supportcontract/ajax/dropdownunitnumber.php",
              $params);
      // Si contrat hour, afficher 'automatique', sinon dropdown::showinteger
      echo "<div id='show_unitnumber'></div>";
      
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Invoice state', 'supportcontract')."&nbsp;:</td>";
      echo "<td>";
      $elements = $this->getInvoiceState();
      Dropdown::showFromArray("invoice_state", $elements);      
      echo "</td>";
      echo "<td>".__('Invoice number', 'supportcontract')."&nbsp;:</td>";
      echo "<td>";
      echo "<input type='text' name='invoice_number' value='' />";      
      echo "<input type='hidden' name='tickets_id' value='".$tickets_id."' />";
      
      echo "</td>";
      echo "</tr>";      

      $this->showFormButtons($options);

      return true;
   }
   
   
   
   function showContract() {
      
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
                        "/plugins/supportcontract/front/ticket_contract.form.php'>";
         echo "<input type='hidden' name='id' value='".$data['id']."' />";
         echo "<table class='tab_cadre_fixe'>";

         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='7'>".__('Linked contract', 'supportcontract')." :</th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<th>".__('Contract')."</th>";
         echo "<th>".__('Type')."</th>";
         echo "<th>".__('Units', 'supportcontract')."</th>";
         echo "<th>".__('Invoice state', 'supportcontract')."</th>";
         echo "<th>".__('Invoice number', 'supportcontract')."</th>";
         echo "<th></th>";
         echo "<th></th>";
         echo "</tr>";
            
         echo "<tr class='tab_bg_1'>";
         $contract->getFromDB($data['contracts_id']);
         echo "<td>".$contract->getLink(1)."</td>";
         echo "<td>";            
         echo PluginSupportcontractContract::getUnit_typeNameForContract($data['contracts_id']);
         echo "</td>";
         echo "<td>";
         if (PluginSupportcontractContract::getUnit_typeForContract($data['contracts_id']) == 'hour') {
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
               $elements[] = PluginSupportcontractToolbox::displayHours($time / 3600, 1)." (".
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
         echo "<input type='submit' name='update' value='".__('Save')."' class='submit' />";
         echo "</td>";
         echo "<td>";
         if ($data['invoice_number'] == '') {
            echo "<input type='submit' name='delete' value=\"".__('Delete permanently')."\"
                         class='submit' ".Html::addConfirmationOnAction(__('Confirm the final deletion ?')).">";
         }
         echo "</td>";
         echo "</tr>";

         echo "</table>";
         Html::closeForm();
      }      
   }
   
   
   
   function showUnaffectedTickets() {
      global $DB;
      
      $entities_contract = array();
      $query = "SELECT `glpi_contracts`.* FROM `glpi_plugin_supportcontract_contracts`
         LEFT JOIN `glpi_contracts` ON `glpi_contracts`.`id`=`glpi_plugin_supportcontract_contracts`.`contracts_id`
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
      echo __('ID');
      echo "</th>";
      echo "<th>";
      echo __('Title');
      echo "</th>";
      echo "<th>";
      echo __('Entity');
      echo "</th>";
      echo "<th>";
      echo __('Ticket');
      echo "</th>";
      echo "<th>";
      echo __('Priority');
      echo "</th>";
      echo "<th>";
      echo __('Duration');
      echo "</th>";
      echo "<th>";
      echo __('Opening date');
      echo "</th>";
      echo "<th>";
      echo ItilCategory::getTypeName();
      echo "</th>";
      echo "<th>";
      echo TaskCategory::getTypeName();
      echo "</th>";
      echo "<th>";
         if ($invoice_state != 1) {
         echo __('Invoice number', 'supportcontract');
      }
      echo "</th>";
      echo "</tr>";
      while ($data=$DB->fetch_array($result)) {
         $this->showTicketsDetail($data['id']);
      }
      echo "</table>";
   }
   

   
   function getInvoiceState() {
      return array(
          0 => __('To be invoiced', 'supportcontract'),
          1 => __('Invoiced under contract', 'supportcontract'),
          2 => __('Invoiced out of contract', 'supportcontract'),
          3 => __('Hotline (no unit)', 'supportcontract')
      );
   }
   
   
   
   function showSummaryPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item) {
      global $DB;
      
      $psContract = new PluginSupportcontractContract();
      $contract = new Contract();
      
      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>'.__('Intervention report', 'supportcontract').'</b>');
      $pdf->displaySpace();
      
      $pdf->setColumnsSize(50,50);
      
      $a_tickets_crontracts = $this->find("`tickets_id`='".$item->getID()."'", "", 1);
      $data = current($a_tickets_crontracts);
      $contract->getFromDB($data['contracts_id']);      
      $psContract->showSummaryPDF($pdf, $contract);
      
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