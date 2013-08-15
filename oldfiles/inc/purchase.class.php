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

class PluginBestmanagementPurchase extends CommonDBTM {
   
   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName() {
      global $LANG;

      return $LANG['bestmanagement']['achat'][9];
   }



   function canCreate() {
      return true;
   }


   function canView() {
      return true;
   }

   
   
   function showForm(PluginBestmanagementContract $pbContract) {
      global $LANG;
      
      $this->check(-1,'w');
      $options = array();
      
      $this->showFormHeader($options);
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      if ($pbContract->fields['definition'] == "TaskCategory") {
         echo TaskCategory::getTypeName()."&nbsp;:";
      } else if ($pbContract->fields['definition'] == "ItilCategory") {
         echo ItilCategory::getTypeName()."&nbsp;:";
      } else if ($pbContract->fields['definition'] == "priority") {
         echo $LANG['joblist'][2]."&nbsp;:";
      }
      echo "</td>";
      echo "<td>";
      if ($pbContract->fields['definition'] == "TaskCategory") {
         Dropdown::show('TaskCategory', array('name' => 'definitions_id'));
      } else if ($pbContract->fields['definition'] == "ItilCategory") {
         Dropdown::show('ItilCategory', array('name' => 'definitions_id'));
      } else if ($pbContract->fields['definition'] == "priority") {
         Ticket::dropdownPriority('definitions_id');
      }
      echo "</td>";
      echo "<td>";
      echo $LANG['bestmanagement']['tabrecap'][16]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      echo Dropdown::showInteger("unit", 1, 1);
      echo "</td>";      
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo $LANG["bestmanagement"]["historical"][2]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('amendment');
      echo "</td>";
      echo "<td>";
      echo $LANG["bestmanagement"]["tabs"][6]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      $a_input = array(
          0 => $LANG["bestmanagement"]["facturation_contrat"][0],
          1 => $LANG["bestmanagement"]["facturation_contrat"][1],
          2 => $LANG["bestmanagement"]["facturation_contrat"][2]
      );
      Dropdown::showFromArray("invoice_state", $a_input, array('value'=>1));
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo $LANG['common'][25]."&nbsp;";
      echo "</td>";
      echo "<td colspan='3'>";
      echo "<input type='text' name='comment' size='40' maxlength='255' value='' />";
      echo "<input type='hidden' name='contracts_id' value='".$pbContract->fields['contracts_id']."' />";
      $contract = new Contract();
      $contract->getFromDB($pbContract->fields['contracts_id']);
      echo "<input type='hidden' name='begin_date' value='".$contract->fields['begin_date']."' />";
      echo "</td>";
      echo "</tr>";
      
      $this->showFormButtons($options);
   }
   
   

   /**
    * Display purchase history
    */
   function showHistory($contracts_id, $a_pbContracts) {
      global $DB, $CFG_GLPI, $LANG;
      
      $onlyone=false;
      $presentcontrat=null;

      echo "<table class='tab_cadre_fixe'>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='6'>";
      echo $LANG["bestmanagement"]["tabs"][2];
      echo "</th>";
      echo "</tr>";
      
      $a_purchases = $this->find("`contracts_id`='".$contracts_id."'");
      
      if (count($a_purchases) == 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='6'>";
         echo $LANG["bestmanagement"]["msg"][16];
         echo "</td>";
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<th>";
         echo $LANG['common'][27];
         echo "</th>";
         echo "<th>";
         echo $LANG["bestmanagement"]["historical"][0];
         echo "</th>";
         echo "<th>";         
         if ($a_pbContracts['definition'] == "TaskCategory") {
            echo TaskCategory::getTypeName();
         } else if ($a_pbContracts['definition'] == "ItilCategory") {
            echo ItilCategory::getTypeName();
         } else if ($a_pbContracts['definition'] == "priority") {
            echo $LANG['joblist'][2];
         }
         echo "</th>";
         echo "<th>";
         echo $LANG['bestmanagement']['tabrecap'][16];
         echo "</th>";
         echo "<th>";
         echo $LANG['common'][25];
         echo "</th>";
         echo "<th>";
         echo $LANG["bestmanagement"]["facturation_contrat"][3];         
         echo "</th>";
         echo "</tr>";
         
         foreach ($a_purchases as $a_purchase) {
            echo "<tr class='tab_bg_3'>";
            echo "<td>";
            echo Html::convDate($a_purchase['date_save']);
            echo "</td>";
            echo "<td>";
            if ($a_purchase['amendment'] == 1) {
               echo $LANG["bestmanagement"]["historical"][2]." - ";
            }
            switch ($a_purchase["invoice_state"]) {
               
               case 0:
                  echo $LANG["bestmanagement"]["facturation_contrat"][0];
                  break;
               
               case 1:
                  echo $LANG["bestmanagement"]["facturation_contrat"][1];
                  break;
               
               case 2:
                  echo $LANG["bestmanagement"]["facturation_contrat"][2];
                  break;
               
               case 3:
                  echo $LANG["bestmanagement"]["facturation_contrat"][3];
                  break;
            }
            echo "</td>";
            echo "<td>";
            if ($a_pbContracts['definition'] == "priority") {
               echo Ticket::getPriorityName($a_purchase['definitions_id']);
            } else {
               echo Dropdown::getDropdownName(getTableForItemType($a_pbContracts['definition']), 
                                              $a_purchase['definitions_id']);
            }
            echo "</td>";
            echo "<td>";
            if ($a_pbContracts['unit_type'] == "hour") {
               echo PluginBestmanagementToolbox::displayHours($a_purchase['unit']);
            } else {
               echo $a_purchase['unit'];
            }
            echo "</td>"; 
            echo "<td>";
            echo $a_purchase['comment'];
            echo "</td>"; 
            echo "<td>";
            echo $a_purchase['invoice_number'];
            echo "</td>";            
            echo "</tr>";
         }
      }
      
      echo "</table>";
      

return;
      
      if($this->nbAchats() > 0) {  // on a donc au moins un achat
         $info_compteur = $this->infoCompteur();
         if (!isset($presentcontrat)) {
            echo "<table class='tab_cadre' style='margin-top: 10px;'>";
         }
         $colonnes = array();
         if ($onlyone) {
            array_push($colonnes, "");   // pour la checkbox
         }

         array_push($colonnes, 
                    "Date",
                    $LANG["bestmanagement"]["historical"][0]);

         if ($info_compteur["compteur"] == "category") {
            $cat_name = $this->tabCatName(); // association id<->nom de la cat�gorie
            array_push($colonnes, $LANG['common'][36]);      // cat
         } else {
            array_push($colonnes, $LANG['joblist'][2]);      // prio
         }
         // pour adapter l'affichage des colonnes
         $nb = ($info_compteur["unit"] == "nbtickets") ? 10 : 0;

         array_push($colonnes, $LANG["bestmanagement"]["tabrecap"][1 + $nb]);

         if (plugin_bestmanagement_haveRight("bestmanagement","facturationcontrat", 1))
            array_push($colonnes, 
                       $LANG['common'][25],
                       $LANG["bestmanagement"]["facturation_contrat"][3]);

         echo "<tr>";
         foreach ($colonnes as $col) {
            echo "<th style='padding:0px 10px;'>".$col."</th>";
         }
         echo "</tr>";

         $colspan = count($colonnes);

         // Donne le nom du contrat dans le cas o�
         // on ne se trouve pas sous la fiche contrat
         if ($presentcontrat) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='$colspan'><a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?id=$this->id'>";
            echo $this->giveRealName()."</a></td>";
            echo "</tr>";
         }

         $UNSEUL = "";
         $UNION = "";
         if ($onlyone) {
            $UNSEUL = "AND achat.date_deb = contrat.begin_date";
            // + les achats non factur�s
            $UNION = "UNION
                    SELECT achat.id IDA, date_deb, ID_Compteur, etat_fact, contrat.duration duration,
                         avenant, UnitBought, comments, num_fact_api, date_save
                    FROM glpi_plugin_bestmanagement_achat achat
                     INNER JOIN glpi_contracts contrat
                        ON achat.ID_Contrat = contrat.ID
                    WHERE ID_Contrat = $this->id
                     AND ID_Compteur IS NOT NULL
                     AND etat_fact = 1";
         }

         // requ�te sur l'historique des achats du contrat
         $query_historique = "SELECT achat.id IDA, date_deb, ID_Compteur, etat_fact, contrat.duration duration,
                              avenant, UnitBought, comments, num_fact_api, date_save
                         FROM glpi_plugin_bestmanagement_achat achat
                           INNER JOIN glpi_contracts contrat
                              ON achat.ID_Contrat = contrat.ID
                         WHERE ID_Contrat = $this->id
                           AND ID_Compteur IS NOT NULL
                           $UNSEUL
                         $UNION
                         ORDER BY date_deb, date_save";

         echo "<script type='text/javascript' >";
         //On ne pourra �diter qu'une valeur � la fois
         echo "var editionEnCours = false;";
         echo "</script>";

         if($resultat = $DB->query($query_historique)) {
            if($DB->numrows($resultat) > 0) {
               while ($row = $DB->fetch_assoc($resultat)) {
                  $key = $row["ID_Compteur"];

                  // si l'achat est factur� on ne l'affiche pas (que cas g�n�ral, pas sous la fiche)
                  if (isset($presentcontrat) 
                          && !$row["etat_fact"]) {
                     continue;
                  }

                  // rappel En-T�te p�riode du contrat
                  if (!isset($date_deb) || $date_deb != $row["date_deb"]) {
                     if (isset($date_deb)) {
                        echo "<tr class='tab_bg_1'><td 'colspan='$colspan'>&nbsp;</td></tr>";   // ligne vierge
                     }
                     echo "<tr class='tab_bg_1'>";
                     echo "<td colspan='$colspan'>" . $LANG["bestmanagement"]["contrat"][8]. Html::convDate($row["date_deb"]);
                     echo $LANG["bestmanagement"]["contrat"][9].Infocom::getWarrantyExpir($row["date_deb"],$row["duration"])."</td>";
                     echo "</tr>";
                  }

                  $date_deb   = $row["date_deb"];

                  echo "<tr class='tab_bg_2'>";
                  $td   = "<td class='center'>";   // td normal

                  $td_compteur = $td;

                  // que pour l'onglet Facturation
                  if ($onlyone) {
                     $id_achat = $row["IDA"];
                     echo "<td>";
                     // checkbox si pas factur�
                     echo ($row["etat_fact"]) ? "<input type='checkbox' name='CBFact_$id_achat'></td>" : "</td>";
                  }

                  echo $td . Html::convDate($row["date_save"]) ."</td>";         // Date

                  echo $td;   // D�tails
                  echo ($row["avenant"] == 0) ? $LANG["bestmanagement"]["historical"][2]." - " : "";
                  echo $LANG["bestmanagement"]["facturation_contrat"][$row["etat_fact"]]."</td>";

                  $name = $this->giveCompteurName($key, $info_compteur);

                  // adapte la couleur du fond selon la priorit�
                  if ($info_compteur["compteur"] == "priority" 
                          && isBgColor()) {
                     $td_compteur = "<td align='center' style=\"background-color:".$_SESSION["glpipriority_$key"]."\">";
                  }
                  echo $td_compteur . $name . "</td>";      // Compteur

                  echo $td . $this->arrangeIfHours($row["UnitBought"], $info_compteur["unit"])."</td>";   // Unit�s achet�es

                  if (plugin_bestmanagement_haveRight("bestmanagement","facturationcontrat", 1)) {
                     echo $td . $row["comments"] . "</td>";      // Commentaires

                     echo $td;

                     if ($onlyone) {
                        // PARTIE AJAX
                        $rand = mt_rand();
                        echo "<script type='text/javascript' >\n";
                        echo "function showDesc$rand(){\n";
                        echo "if(editionEnCours) return false;";
                        echo "else editionEnCours = true;";
                        echo "Ext.get('desc$rand').setDisplayed('none');";
                        $params = array('cols'  => 10,
                                     'id'  => $row['IDA'],
                                     'name'  => 'num_fact_api',
                                     'data'  => $row['num_fact_api']);
                        Ajax::updateItemJsCode("viewdesc$rand",$CFG_GLPI["root_doc"]."/plugins/bestmanagement/ajax/textfield.php",$params,
                                         false);
                        echo "}";
                        echo "</script>\n";
                        echo "<div id='desc$rand' class='tracking' onClick='showDesc$rand()'>\n";

                        echo $row['num_fact_api'];

                        echo "</div>\n";

                        echo "<div id='viewdesc$rand'></div>\n";
                        if (0) {
                           echo "<script type='text/javascript' >\n
                           showDesc$rand();
                           </script>";
                        }
                        // FIN
                     } else {
                        echo $row['num_fact_api'];   // pas de modifications possibles
                     }
                     echo "</td>";
                  }
                  echo "</tr>";
               } // while
            }
         }
         if (!isset($presentcontrat)) {
            echo "</table>";
         }
      } else {
         echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][16] . "</div>";
      }
   }

}

?>