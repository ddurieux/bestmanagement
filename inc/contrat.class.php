<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Classe Contrat servant pour l'affichage du contenu
//            sous la fiche contrat
// ----------------------------------------------------------------------

class PluginBestmanagementContrat extends CommonDBTM {
   private $id;
   public $table            = 'glpi_plugin_bestmanagement_achat';
   public $type             = 'PluginBestmanagementContrat';

   /**
    * Constructeur
    *
    * @param ID : identifiant du contrat
   **/
   function __construct ($ID) {
      $this->id = $ID;
   }

   
   
   /**
    * Affiche l'historique des achats
    * Les périodes sont séparées par des lignes vierges
    *
    ** @param onlyone : if true => une seule période (+ les achats des anciens non facturés)
    *
    * @return Nothing(Display)
   **/
   function historical($onlyone=false, $presentcontrat=null){
      global $DB, $CFG_GLPI, $LANG;

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
   } // historical()

   
   
   
   /**
    * Définition du tri
    *
    * @return Nothing(Display)
   **/
   function sort() {
      global $CFG_GLPI, $LANG;

      $td = "<td style='padding:0.2em 0.5em;' align=center>";   // td normal

      echo "<div id='formsort'>";
      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/bestmanagement.form.php\">";
      echo "<input type='hidden' name='id_contrat' value='$this->id'>";
         echo "<table class='tab_cadre'>";
         echo "<tr><th align=center colspan='7'>".$LANG["bestmanagement"]["sort"][1]."</th></tr>";   // Titre
         echo "<tr class='tab_bg_2'>";
         echo $td . $LANG["bestmanagement"]["sort"][2] . " :</td>";   // tri
         echo $td . "<select name='compteur'>";
            echo "<option value='category'> "   . $LANG["bestmanagement"]["sort"][4] . "</option>";
            echo "<option value='priority'> "   . $LANG["bestmanagement"]["sort"][5] . "</option>";
         echo "</select>";
         echo "</td>";
         echo $td . $LANG["bestmanagement"]["sort"][3]." :</td>";   // unit�
         echo $td . "<select name='unit'>";
            echo "<option value='hour'> "      . $LANG["bestmanagement"]["sort"][6] . "</option>";
            echo "<option value='nbtickets'> "   . $LANG["bestmanagement"]["sort"][7] . "</option>";
              echo "<option value='nbhalfday'> "   . $LANG["bestmanagement"]["sort"][8] . "</option>";
         echo "</select>";
         echo "</td>";
         echo "<td colspan= '2' align='center'><input type=\"submit\" name=\"addSort\" class=\"submit\" value=\"".$LANG["buttons"][51]."\" ></td>";
         echo "</tr>";
         echo "</table>";
      echo "</form>";
      echo "</div>";
   } // sort()

   
   
   /**
    * Permet l'ajout d'un achat
    *
    * @return Nothing(Display)
   **/
   function addPurchase() {
      global $LANG, $CFG_GLPI;

      echo "   <script type='text/javascript'>

         function getXhr()
         {
            var xhr = null;
            if(window.XMLHttpRequest) // Firefox et autres
               xhr = new XMLHttpRequest();
            else if(window.ActiveXObject)
            { // Internet Explorer
               try
               {
                  xhr = new ActiveXObject(\"Msxml2.XMLHTTP\");
               }
               catch (e)
               {
                  xhr = new ActiveXObject(\"Microsoft.XMLHTTP\");
               }
            }
            else
            { // XMLHttpRequest non supporté par le navigateur
               alert(\"Votre navigateur ne supporte pas les objets XMLHTTPRequest...\");
               xhr = false;
            }
            return xhr;
         }

         /**
         * Méthode qui sera appelée sur le clic du bouton
         */
         function go()
         {
            var xhr = getXhr();
            // On défini ce qu'on va faire quand on aura la réponse
            xhr.onreadystatechange = function(){
               // On ne fait quelque chose que si on a tout reçu et que le serveur est ok
               if(xhr.readyState == 4 && xhr.status == 200){
                  leselect = xhr.responseText;
                  // On se sert de innerHTML pour rajouter les options a la liste
                  document.getElementById('api').innerHTML = leselect;
               }
            }

            // Ici on va voir comment faire du post
            xhr.open(\"POST\",\"../plugins/bestmanagement/tabrecap.php\",true);
            // ne pas oublier ça pour le post
            xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            // ne pas oublier de poster les arguments
            // ici, l'id de l'auteur
            sel = document.getElementById('id_facturation');
            fact = sel.options[sel.selectedIndex].value;
            idcontrat = $this->id;
            xhr.send(\"Fact=\"+fact+\"&idContratFactureContrat=\"+idcontrat);
         }
      </script>
";

      $info_compteur = $this->infoCompteur();
      $td   = "<td style='padding:0.2em 0.5em;' align=center>";   // td normal
      $tr = "<tr class='tab_bg_2'>";                     // tr normal

      if(count($info_compteur) > 0) {
         echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/bestmanagement.form.php\">";
         echo "<input type='hidden' name='id_contrat'   value='$this->id'>";
            echo "<table class='tab_cadre'>";
            echo "<tr><th align=center colspan='5'>" . $LANG["bestmanagement"]["achat"][0]; // Titre
            echo ($info_compteur["unit"] == "hour")         ? $LANG["bestmanagement"]["achat"][1] : $LANG["bestmanagement"]["achat"][2];
            echo ($info_compteur["compteur"] == "category")   ? $LANG["bestmanagement"]["achat"][3] : $LANG["bestmanagement"]["achat"][4];
            echo"</th></tr>";   // fin Titre

            echo $tr . $td;      // Choisir une cat�gorie || Choisir une priorit�
            echo ($info_compteur["compteur"] == "category")   ? $LANG["bestmanagement"]["achat"][5] : $LANG["bestmanagement"]["achat"][6];
            echo " :</td>";
            echo "<td style='padding:0.2em 0.5em;' align=center colspan='4'>";

            if ($info_compteur["compteur"] == "category") {
               Dropdown::show('TaskCategory');            // dropdown des catégories
            } else {
               Ticket::dropdownPriority("priority",3);      // dropdown des priorités
            }
            echo "</td></tr>";

            // Achat
            echo $tr . $td;
            echo ($info_compteur["unit"] == "hour") ? $LANG["bestmanagement"]["achat"][7] : $LANG["bestmanagement"]["achat"][8];
            echo " : <input type='int' name='NbUnit' size='5' maxlength='5'></td>";

            // Avenant ou pas ?
            echo $td . "Avenant <input type='checkbox' name='Avenant'></td>";

            // Etat Facturation
            echo $td;

            echo "<select name='id_facturation' id='id_facturation' onchange='go()'>";
               echo "<option         value='0'>".$LANG["bestmanagement"]["facturation_contrat"][0]."</option>";
               echo "<option selected   value='1'>".$LANG["bestmanagement"]["facturation_contrat"][1]."</option>";
               echo "<option         value='2'>".$LANG["bestmanagement"]["facturation_contrat"][2]."</option>";
            echo "</select>";


            echo "</td>";
            // API
            echo $td . "<div id='api' style='display:inline'>";
            echo "</div></td>";
            echo "</tr>";

            // Commentaires
            echo $tr . "<td colspan='2'><input type='text' name='Comments' size='40' maxlength='255' value='".$LANG['common'][25]."' onfocus=\"this.value='';\"></td>";
            echo "<td colspan='2' align='center'><input type=\"submit\" name=\"addPurchase\" class=\"submit\" value=\"".$LANG["buttons"][51]."\" ></td>";
            echo "</tr>";
            echo "</table>";
         echo "</form>";
      }
   } // AddPurchase()

   
   
   /**
    * Vérifie si les périodes sont vides
    *
    * @return boolean : true si z�ro achat
   **/
   function checkEmptyPeriode() {
      global $DB;

      $date_deb = $this->dateDeb();
      // requ�te sur l'historique des achats du contrat
      $query = "SELECT COUNT(*) Total
              FROM glpi_plugin_bestmanagement_achat
              WHERE ID_Compteur IS NOT NULL
               AND ID_Contrat = $this->id
               AND date_deb != '$date_deb'";

      if($resultat = $DB->query($query)) {
         $row = $DB->fetch_assoc($resultat);
      }

      return ($row["Total"] == 0) ? true : false;
   } // checkEmptyPeriode()



   /**
    * Retourne les lignes du tableau de bord pour le contrat en cours
    *
    * @return <tr> <td> ... </td> </tr>
   **/
   function currentRecap($tr1=null, $tr2=null) {
      $lignes = "";
      //-------------------------------------------------
      // Préparation des requètes
      // Les tableaux sont indexés selon l'ID du compteur
      // contrats illimités => juste consommation
      //-------------------------------------------------
      if (!$this->isContratIllim()) {
         $tab_achat   = $this->prepareTab("achat");
         $tab_report   = $this->prepareTab("report");
      }
      $tab_conso   = $this->prepareTabConso();
      $tab_restant= $this->prepareTabRestant();
      $info_compteur = $this->infoCompteur();

      // on adapte l'affichage
      $tdnormal   = "<td class='tab_bg_1'      align=center>";         // td normal
      $tdred      = "<td class='tab_bg_2_2'   align=center>";         // td rouge quand HrsRest < 0
      $tdredstrg   = "<td class='tab_bg_2_2'   align=center style='color:red;'><strong>";   // td rouge et HrsRest �crit en rouge+d�but gras

      // alternance de couleurs
      $tr1 = (isset($tr1)) ? $tr1 : "<tr>";
      $tr2 = (isset($tr2)) ? $tr2 : "<tr>";
      $tr = $tr1;

      // remplissage des lignes du tableau
      foreach(array_keys($tab_restant) as $key) {
         // vérifications pour savoir si les valeurs existent
         if (!$this->isContratIllim()) {
            $tab_achat[$key]   = isset($tab_achat[$key])   ? $tab_achat[$key]   : 0;
            $tab_report[$key]   = isset($tab_report[$key])   ? $tab_report[$key]   : 0;
            $tab_restant[$key]   = isset($tab_restant[$key])   ? $tab_restant[$key]: 0;
         }
         $tab_conso[$key]   = isset($tab_conso[$key])   ? $tab_conso[$key]   : 0;
         // fin v�rification

         $td = $tdnormal;

         if (!$this->isContratIllim()) {
            // on adapte le td. Si aucune heure achet�e ni report�e, ligne enti�rement rouge
            $td = ($tab_achat[$key] + $tab_report[$key] == 0) ? $tdred : $tdnormal;

            // s'il n'y a ni heure achet�e, report�e ou consomm�e on n'affiche pas la ligne
            if ($tab_achat[$key] == 0 && $tab_report[$key] == 0 && $tab_conso[$key] == 0) continue;
         }
         $name = $this->giveCompteurName($key, $this->infoCompteur());
         $td_compteur = $td;

         if ($info_compteur["compteur"] == "priority" 
                 && isBgColor()) {   // couleur de fond pour les priorités
            $td_compteur = "<td align='center' style=\"background-color:".$_SESSION["glpipriority_$key"]."\">";
         }

         $tr = ($tr == $tr1) ? $tr2 : $tr1;

         $lignes .= $tr;

         $lignes .= $td_compteur .   $name . "</td>";

         if (!$this->isContratIllim()) {
            $lignes .= $td . $this->arrangeIfHours($tab_achat[$key]   , $info_compteur["unit"])   . "</td>";
            $lignes .= $td . $this->arrangeIfHours($tab_report[$key]   , $info_compteur["unit"])   . "</td>";
         } else if (isset($_SERVER["REQUEST_URI"])
                && !strpos($_SERVER["REQUEST_URI"], "contract.tabs")   // pas dans la fiche contrat
                && !strpos($_SERVER["REQUEST_URI"], "tabrecap.ph")) {  // pas dans le tab dans cr�ation ticket
            $lignes .= $td . "</td>" . $td . "</td>";
         }

         $lignes .= $td . $this->arrangeIfHours($tab_conso[$key]   , $info_compteur["unit"])   . "</td>";


         if (!$this->isContratIllim()) {
            $lignes .= ($tab_restant[$key] < 0) ? $tdredstrg  : $td;
            $lignes .= $this->arrangeIfHours($tab_restant[$key]      , $info_compteur["unit"]);

            $lignes .= ($tab_restant[$key] < 0) ? "</strong>" : "";

            // pour avoir le % : reste / (achat+report)
            $lignes .= ($tab_restant[$key] < 0) ? "" : " (".round(100*$tab_restant[$key]/($tab_achat[$key]+$tab_report[$key]),0)."%)";
            $lignes .= ($tab_restant[$key] < 0) ? "</strong>" : "";
         } else if (isset($_SERVER["REQUEST_URI"])
                && !strpos($_SERVER["REQUEST_URI"], "contract.tabs")   // pas dans la fiche contrat
                && !strpos($_SERVER["REQUEST_URI"], "tabrecap.ph")) {  // pas dans le tab dans cr�ation ticket
      //      $lignes .= $td . "</td>";
         }

         $lignes .= "</tr>";
      } // fin remplissage des lignes
      return $lignes;

   } // currentRecap()

   
   
   /**
    * Retourne un tableau récapitulatif
    *
    * @param $tabrecap : quel tableau on veut afficher
    * sachant que c'est aussi la fonction que l'on va appeler
    *
    * @return <table>...</table>
    *      or <div>...</div> en cas d'�chec
   **/
   function showTabRecap($tabrecap="currentRecap") {
      global $DB, $LANG;
      
      $tab = "";
      $info_compteur = $this->infoCompteur();

      $class = get_class($this);
      //if(!is_callable($class.'::'.$tabrecap))   // erreur dans la fonction à appeler
      //   return false;

      // cas o� on ne peut pas afficher l'historique global
      if ($tabrecap == "histRecap") {
         if ($this->nbPeriodes() == 1) {   // si il n'y a qu'une période
            return "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][12] . "</div>";
         } else if ($this->checkEmptyPeriode()) {  // si les p�riodes sont vides
            return "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][23] . "</div>";
         }
      }
      if($tabrecap == "currentRecap" && !$this->areSetValues()) {
         return "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][24] . "</div>";
      }
      if(count($info_compteur) == 2) {
         // on nomme les colonnes du tableau, selon le compteur et les unit�s
         $colonnes = array();
         if ($info_compteur["compteur"] == "category") {
            array_push($colonnes, $LANG['common'][36]);
         } else {
            array_push($colonnes, $LANG['joblist'][2] );
         }
         // pour adapter l'affichage des colonnes
         $nb = ($info_compteur["unit"] == "nbtickets") ? 10 : 0;

         if ($this->isContratIllim()) {
            array_push($colonnes, $LANG["bestmanagement"]["tabrecap"][3 + $nb]);
         } else {
            array_push($colonnes, $LANG["bestmanagement"]["tabrecap"][1 + $nb],
                             $LANG["bestmanagement"]["tabrecap"][2 + $nb],
                             $LANG["bestmanagement"]["tabrecap"][3 + $nb],
                             $LANG["bestmanagement"]["tabrecap"][4 + $nb]);
         }

         $tab .= "<table class='tab_cadre'>";
         $tab .= "<tr> <th colspan='5'>" . $LANG["bestmanagement"]["tabrecap"][0] . "</th> </tr>"; // titre

         $tab .= "<tr>";
         foreach ($colonnes as $col) {
            $tab .= "<th>".$col."</th>";
         }
         $tab .= "</tr>";

         $tab .= call_user_func(array($class,$tabrecap));    // appel de la fonction qui affiche le récapitulatif

         $tab .= "</table>";
      }
      return $tab;

   } // showTabRecap()

   

   /*
     renvoie un tableau statistiques par entit�s
   */
   function showStatEntites(){
      global $DB, $LANG;

      $tab = "";

      $query ="
      select e.name, count(t.id) as nb_ticket,tps.temps
      from glpi_tickets t
      inner join glpi_entities e
      on t.entities_id = e.id
      inner join glpi_plugin_bestmanagement_link_ticketcontrat l
      on t.id = l.ID_Ticket and l.ID_Contrat = $this->id
      inner join (
      select e.name, sum(ta.actiontime)/3600 as temps
      from glpi_tickets t
      inner join glpi_entities e
      on t.entities_id = e.id
      inner join glpi_plugin_bestmanagement_link_ticketcontrat l
      on t.id = l.ID_Ticket and l.ID_Contrat = $this->id
      inner join glpi_tickettasks ta
      on t.id = ta.tickets_id
      inner join glpi_contracts c
      on l.ID_Contrat = c.id
      where ta.date between c.begin_date and date_add(c.begin_date, INTERVAL c.duration MONTH)
      group by e.name
      ) tps
      on e.name = tps.name
      inner join glpi_contracts c
      on l.ID_Contrat = c.id
      where t.date between c.begin_date and date_add(c.begin_date, INTERVAL c.duration MONTH)
      group by e.name";

      $tab = "<table class='tab_cadre'>";
      $tab .= "<tr><th>".$LANG["bestmanagement"]["tabs"][8]."</th><th>".$LANG["bestmanagement"]["sort"][7]."</th><th>".$LANG["bestmanagement"]["recap"][3]."</th></tr>";

      if ($resultat = $DB->query($query)) {
        if ($DB->numrows($resultat) > 0) {
          while ($row = $DB->fetch_assoc($resultat)) {
              $tab .= "<tr><td class='tab_bg_1' align='center'>".$row["name"]."</td>";
              $tab .= "<td class='tab_bg_1' align='center'>".$row["nb_ticket"]."</td>";
              $tab .= "<td class='tab_bg_1' align='center'>".$this->arrangeIfHours($row["temps"],"hour")."</td></tr>";
          }
        }
      }

      $tab .= "</table>";

      return $tab;
  }

   /**
    * Retourne les lignes du tableau de bord pour les périodes antérieures
    *
    * @return <tr> <td> ... </td> </tr>
   **/
   function histRecap() {
      global $DB, $LANG;
      
      $lignes = "";

      $query = "SELECT *
              FROM glpi_plugin_bestmanagement_historique
              WHERE ID_Contrat = $this->id
              ORDER BY date_deb, ID_Compteur";

      $td   = "<td class='tab_bg_1' align='center'>";      // td normal
      $info_compteur = $this->infoCompteur();

      if ($resultat = $DB->query($query)) {
         if ($DB->numrows($resultat) > 0) {
            while ($row = $DB->fetch_assoc($resultat)) {
               // rappel p�riode du contrat
               if (!isset($datedeb) || $datedeb != $row["date_deb"]) {
                  if (isset($datedeb)) {
                     $lignes .= "<tr class='tab_bg_1'><td 'colspan='5'>&nbsp;</td></tr>";   // ligne vierge
                  }
                  $lignes .= "<tr class='tab_bg_1'>";
                  $lignes .= "<td colspan='5'>" . $LANG["bestmanagement"]["contrat"][8]. Html::convDate($row["date_deb"]);
                  $lignes .= $LANG["bestmanagement"]["contrat"][9].Infocom::getWarrantyExpir($row["date_deb"],$row["duree"])."</td>";
                  $lignes .= "</tr>";
               }

               $datedeb = $row["date_deb"];
               // v�rifications pour savoir si les valeurs existent
               $row["achat"]   = isset($row["achat"])   ? $row["achat"]   : 0;
               $row["report"]   = isset($row["report"])   ? $row["report"]: 0;
               $row["conso"]   = isset($row["conso"])   ? $row["conso"]   : 0;
               $reste = $row["achat"] + $row["report"] - $row["conso"];
               // fin v�rification

               $name = $this->giveCompteurName($row["ID_Compteur"], $this->infoCompteur());

               $td_compteur = $td;
               if ($info_compteur["compteur"] == "priority" && isBgColor()) {  // couleur de fond pour les priorit�s
                  $td_compteur = "<td align='center' style='background-color:".$_SESSION["glpipriority_".$row["ID_Compteur"].""]."'>";
               }
               $lignes .= "<tr>";
               $lignes .= $td_compteur .   $name . "</td>";

               if (!$this->isContratIllim()) {
                  $lignes .= $td . $this->arrangeIfHours($row["achat"]   , $info_compteur["unit"])   . "</td>";
                  $lignes .= $td . $this->arrangeIfHours($row["report"]   , $info_compteur["unit"])   . "</td>";
               }

               $lignes .= $td . $this->arrangeIfHours($row["conso"]   , $info_compteur["unit"])   . "</td>";

               if (!$this->isContratIllim()) {
                  $lignes .= $td . $this->arrangeIfHours($reste         , $info_compteur["unit"]);
               }
               $lignes .= "</tr>";
            }
         }
      } // fin remplissage des lignes
      return $lignes;
   } // histRecap()

   
   
   /**
    * Retourne le nombre de périodes pour le contrat
    *
    * @return int : Nb de périodes
   **/
   function nbPeriodes() {
      global $DB;

      $query_nb = "SELECT COUNT(DISTINCT date_deb) NbPeriodes
                FROM glpi_plugin_bestmanagement_achat
                WHERE ID_Contrat = $this->id";

      if($res = $DB->query($query_nb)) {
         if ($row = $DB->fetch_assoc($res)) {
            return $row["NbPeriodes"];
         }
      }
      return 0;
   } // nbPeriodes()

   
   
   /**
    * Retourne le nombre d'achat pour le contrat
    *
    * @return int : Nb d'achats
   **/
   function nbAchats() {
      global $DB;

      $query_nb = "SELECT COUNT(*) NbAchats
                FROM glpi_plugin_bestmanagement_achat
                WHERE ID_Contrat = $this->id
                  AND ID_Compteur IS NOT NULL";

      if($res = $DB->query($query_nb)) {
         if ($row = $DB->fetch_assoc($res)) {
            return $row["NbAchats"];
         }
      }
      return 0;
   } // nbAchats()

   
   
   /**
    * Indique si le contrat est encore valable
    *
    * @return boolean : true si encore valable
   **/
   function isAvailable() {
      global $DB;

      $query_nb = "SELECT is_deleted
                FROM glpi_contracts
                WHERE id = $this->id";

      if($res = $DB->query($query_nb)) {
         if ($row = $DB->fetch_assoc($res)) {
            return !$row["is_deleted"];
         }
      }
   } // isAvailable()

   
   
   /**
    * Retourne le nom du compteur
    *
    * @param key : ID du compteur
    * @param info_compteur : array
    *
    * @return Nom du Compteur
   **/
   function giveCompteurName($key, $info_compteur) {
      global $LANG;

      if ($info_compteur["compteur"] == "priority") {  // définit le nom des priorités
         if ($key != 0) {
            $name = "";   // nom du premier td
            $name = ($key == 5) ? $LANG["help"][3] : $name;
            $name = ($key == 4) ? $LANG["help"][4] : $name;
            $name = ($key == 3) ? $LANG["help"][5] : $name;
            $name = ($key == 2) ? $LANG["help"][6] : $name;
            $name = ($key == 1) ? $LANG["help"][7] : $name;
         }
      } else { // définit le nom des catégories
         $cat_name = $this->tabCatName();
         if ($key == 0) {
            $name = "(vide)";
         } else {
            $name = (isset($cat_name[$key])) ? $cat_name[$key] : "(non d&eacute;finie)";
         }
      }
      return $name;
   } // giveCompteurName()

   
   
   /**
    * Retourne le tableau associatif id => nom de catégorie
    *
    * @return array
   **/
   function tabCatName() {
      global $DB;

      $query_cat = "SELECT ID, name FROM glpi_taskcategories";
      if($res = $DB->query($query_cat)) {
         if($DB->numrows($res) > 0) {
            while ($row = $DB->fetch_assoc($res)) {
               $cat_name[$row["ID"]] = $row["name"];
            }
         }
      }

      return $cat_name;
   } // tabCatName()

   
   
   /**
    * Si la chaine est une heure
    * cette fonction la transforme pour faciliter la lisisbilité de l'heure
    *
    * @param valeur sous forme 99,99
    * @return string (si heure, sous forme HH:MM)
   **/
   static function arrangeIfHours($val, $unit) {

      if ($unit != "hour") {
         return $val;
      }

      $neg = ($val < 0) ? true : false;
      $val = round($val+0,2);
      $val *= ($neg) ? (-1) : 1;
      $h = floor($val); // heures

      $h += ($val < 0) ? 1 : 0;
      $m = round(($val - $h ) * 60); // minutes
      if ($m >= 0 
              && $m < 10) {
         $m = "0" . $m;
      }
      return (($neg) ? "-" : "") . $h . ":" . $m;
   } // arrangeIfHours()

   
   
   /**
    * Retourne les informations sur le
    * type de compteur et d'unités
    *
    * @return array
   **/
   function infoCompteur() {
      global $DB;

      $query_compteur = "SELECT DISTINCT Type_Compteur, Type_Unit
                     FROM glpi_plugin_bestmanagement_achat
                     WHERE ID_Contrat = $this->id";

      $info_compteur = array();

      if($resultat = $DB->query($query_compteur)) {
         if($DB->numrows($resultat) > 0) {
            $row = $DB->fetch_assoc($resultat);
            $info_compteur["compteur"]   = $row["Type_Compteur"];   // Type du compteur   (category, priorit�)
            $info_compteur["unit"]      = $row["Type_Unit"];      // Type d'unit�      (heures, nbtickets)
         }
      }
      return $info_compteur;
   } // infoCompteur()


   
   /**
    * Retourne le type du contrat, préfixé de son entité
    *
    * @return string
   **/
   function giveRealName() {
      global $DB, $LANG;

      $query = "SELECT IFNULL(entite.name,'Entite Racine') entitename, IFNULL(type.name, '(Pas de type)') contratname
              FROM glpi_contracts contrat
                LEFT JOIN glpi_entities entite
                  ON contrat.entities_id = entite.id
                     LEFT JOIN glpi_contracttypes type
                        ON contrat.contracttypes_id = type.id
              WHERE contrat.id = $this->id";

      if($resultat = $DB->query($query)) {
         if($DB->numrows($resultat) > 0) {
            while($row = $DB->fetch_assoc($resultat)) {
               return $row["entitename"] . " - " . $row["contratname"];
            }
         }
      }
      return "(ID " . $LANG['financial'][1] . " : $this->id)";
   } // giveRealName()

   
   
   /**
    * Retourne la gestion du contrat
    *
    * @return string
   **/
   function giveManagement() {
      global $LANG;

      $cpt = $this->infoCompteur();

      if (!count($cpt)) {
         return "<span class='red'>".$LANG["bestmanagement"]["allrecap"][10]."</span>";
      }

      $unit     = ($cpt["unit"] == "hour")         ? $LANG["bestmanagement"]["allrecap"][6] : $LANG["bestmanagement"]["allrecap"][7];
      $compteur = ($cpt["compteur"] == "category")   ? $LANG["bestmanagement"]["allrecap"][8] : $LANG["bestmanagement"]["allrecap"][9];

      return $unit . $compteur;
   } // giveManagement()

   
   
   /**
    * Retourne la gestion du contrat (adaptée au PDF)
    *
    * @return string
   **/
   function giveManagementForPDF() {
      global $LANG;

      $cpt = $this->infoCompteur();

      if (!count($cpt)) {
         return $LANG["bestmanagement"]["pdf"][5];
      }

      $unit      = ($cpt["unit"] == "hour")         ? $LANG["bestmanagement"]["pdf"][1] : $LANG["bestmanagement"]["pdf"][2];
      $compteur  = ($cpt["compteur"] == "category")   ? $LANG["bestmanagement"]["pdf"][3] : $LANG["bestmanagement"]["pdf"][3];

      return $unit . $compteur;
   } // giveManagementForPDF()

   
   
   /**
    * Effectue la requête d'achat et de report
    * pour ce contrat.
    *
    * Retourne un tableau associatif :
    * compteur   => unités
    *
    * @return array
   **/
   function prepareTab($what, $avenant=null) {
      global $DB;

      $query = "";
      switch ($what) {
         
        case "achat" :
         // Requêtes associées au report des unités
         // on vérifie d'abort qu'il y a report
         $is_achat = "SELECT *
                    FROM glpi_plugin_bestmanagement_achat
                    WHERE ID_Contrat = $this->id
                     AND ID_Compteur IS NOT NULL";

         if($res_achat = $DB->query($is_achat)) {
            if($DB->numrows($res_achat) > 0) {
               $row = $DB->fetch_assoc($res_achat);

               // requête sur les achats du contrat en cours
               // Selon un compteur, on fait la somme des unités achetées
               $query =   "SELECT ID_Compteur CptID, SUM(UnitBought) Unit
                        FROM glpi_plugin_bestmanagement_achat achat, glpi_contracts contrat
                        WHERE ID_Contrat = $this->id
                           AND achat.ID_Contrat = contrat.ID
                           AND achat.date_deb = contrat.begin_date
                           AND achat.ID_Compteur IS NOT NULL
                           AND UnitBought IS NOT NULL
                           $avenant
                        GROUP BY ID_Compteur";
            }
         }
         break;

        case "report" :
         // Requêtes associées au report des unités
         // on v�rifie d'abort qu'il y a report
         $is_report = "SELECT report_credit
                    FROM glpi_plugin_bestmanagement_reconduction reconduction
                    WHERE ID_Contrat = $this->id
                     AND begin_date IN (SELECT MAX(begin_date)
                                   FROM glpi_plugin_bestmanagement_reconduction
                                   WHERE ID_Contrat = $this->id)";

         if($res_report = $DB->query($is_report)) {
            if($DB->numrows($res_report) > 0) {
               $row = $DB->fetch_assoc($res_report);

               if (!$row["report_credit"]) {  // il y a report
                  // Selon un compteur, on fait la somme des unit�s report�es
                  $query = "SELECT ID_Compteur CptID, Nb_Unit Unit
                          FROM glpi_plugin_bestmanagement_reconduction reconduction,
                              glpi_plugin_bestmanagement_report report
                          WHERE reconduction.id = report.ID_Reconduction
                           AND ID_Contrat = $this->id
                           AND begin_date IN (SELECT MAX(begin_date)
                                         FROM glpi_plugin_bestmanagement_reconduction
                                         WHERE ID_Contrat = $this->id)";
               }
            }
         }
         break;

      } // swith

      // Puis on stocke le r�sultat dans le tableau $tab
      $tab = array();

      if($resultat = $DB->query($query)) {
         if($DB->numrows($resultat) > 0) {
            while($row = $DB->fetch_assoc($resultat)) {
               $tab[$row["CptID"]] = $row["Unit"];
            }
         }
      }
      return $tab;
   } // prepareTab()

   

   /**
    * Effectue la requête des consommations pour ce contrat
    * et retourne un tableau associatif :
    * compteur   => unités consommées
    *
    * @return array
   **/
   function prepareTabConso()
   {
      global $DB;
      $info_compteur = $this->infoCompteur();

      // Requ�tes associ�es � la consommation
      // Selon un compteur, on fait la somme des unit�s consomm�es
      if ($info_compteur["compteur"] == "category") // compteur par cat�gorie
      {
         if ($info_compteur["unit"] == "hour") // type par heure
         {
            $conso = "SELECT task.taskcategories_id CptID, SUM(task.actiontime) UnitC
                 FROM glpi_tickettasks task, glpi_plugin_bestmanagement_link_ticketcontrat link,
                     glpi_contracts contrat
                 WHERE  task.tickets_id = link.ID_Ticket
                  AND link.ID_Contrat = contrat.id
                  AND contrat.id = $this->id
                  AND task.date BETWEEN contrat.begin_date
                     AND DATE_ADD(contrat.begin_date, INTERVAL contrat.duration MONTH)
                  GROUP BY task.taskcategories_id";

         }
         else // type par nombre de tickets
         { // cat par nb de tickets
            // TODO
            $conso = "";
         }
      }
      else   // compteur par priorit�
      {
         if ($info_compteur["unit"] != "hour")
            $AGREG = "SUM(ticket.actiontime)";
         else
            $AGREG = "COUNT(ticket.id)";

         $conso = "SELECT ticket.priority CptID, $AGREG UnitC
              FROM glpi_plugin_bestmanagement_link_ticketcontrat link,
                  glpi_contracts contrat, glpi_tickets ticket
              WHERE  link.ID_Contrat = contrat.id
               AND link.ID_Ticket = ticket.id
               AND contrat.id = $this->id
               AND ticket.date BETWEEN contrat.begin_date
                  AND DATE_ADD(contrat.begin_date, INTERVAL contrat.duration MONTH)
               GROUP BY ticket.priority";
      }

      // Puis on stocke le r�sultat dans le tableau $tab_conso
      $tab_conso = array();
      if($result = $DB->query($conso))
         if($DB->numrows($result) > 0)
            while($row = $DB->fetch_assoc($result)) {
               $tab_conso[$row["CptID"]] = round($row["UnitC"],2)/3600;         // division par 3600 car les heures consomm�es sont des entiers
                                                                  // et non des flottants comme c'�tait dans les versions pr�c�dentes.
            }
      return $tab_conso;

   } // prepareTabConso()



   /**
    * Effectue la requ�te des unit�s restantes pour ce contrat
    * et retourne un tableau associatif :
    * compteur   => unit�s restantes
    *
    * @return array
   **/
   function prepareTabRestant()
   {
      $tab_achat   = $this->prepareTab("achat");      // Tableau r�capitulatif des achats
      $tab_report   = $this->prepareTab("report");      // Tableau r�capitulatif des reports
      $tab_conso   = $this->prepareTabConso();         // Tableau r�capitulatif des consommations

      $tab_total = array();   // tableau : achats + report
      foreach (array_keys($tab_achat) as $key_a)   // ajout du report, cas o� il ya des achats
      {
         if(array_key_exists($key_a, $tab_report))
            $tab_total[$key_a] = $tab_achat[$key_a] + $tab_report [$key_a];
         else
            $tab_total[$key_a] = $tab_achat[$key_a];
      }
      foreach (array_keys($tab_report) as $key_a)   // ajout du report, cas o� il n'y a pas d'achat
      {
         if(!array_key_exists($key_a, $tab_achat))
            $tab_total[$key_a] = $tab_report [$key_a];
      }

      $tab_restant = array();   // tableau : total - conso
      foreach (array_keys($tab_total) as $key_a)
      {
         if(array_key_exists($key_a, $tab_conso)) { // on r�cup�re le total, retranch� des consommations
            $tab_restant[$key_a] = $tab_total[$key_a] - $tab_conso[$key_a];
         }
         else
            $tab_restant[$key_a] = $tab_total[$key_a] ;   // cas o� il n'y a pas de consommation
      }
      foreach (array_keys($tab_conso) as $key_c)
      {
         if(!array_key_exists($key_c, $tab_total)) {         // cas o� il n'y a pas d'achat ni report mais consommation
            $tab_restant[$key_c] = -$tab_conso[$key_c];
         }
      }
      return $tab_restant;

   } // prepareTabRestant()

   /**
    * Retourne la date de d�but du contrat
    *
    * @return string (au format date)
   **/
   function dateDeb()
   {
      global $DB;

      $query = "SELECT begin_date
              FROM glpi_contracts
              WHERE id = $this->id";

      if($resultat = $DB->query($query))
         if($DB->numrows($resultat) > 0)
            if ($row = $DB->fetch_assoc($resultat))
               return $row["begin_date"];

      return "";
   } // dateDeb()

   /**
    * Retourne si le contrat est g�r� en illimit�
    *
    * @return boolean : vrai si illim
   **/
   function isContratIllim()
   {
      global $DB;

      $query = "SELECT illimite
              FROM glpi_plugin_bestmanagement_typecontrat type_contrat
               LEFT JOIN glpi_contracts contrat
                  ON contrat.contracttypes_id = type_contrat.id
              WHERE contrat.id = $this->id";

      if($resultat = $DB->query($query))
         if($DB->numrows($resultat) > 0)
            if ($row = $DB->fetch_assoc($resultat))
               return !$row["illimite"];

      return false;
   } // isContratIllim()

   /*
     Retourne vrai si sous-entit�s est � Oui sur la fiche contrat
   */
   function isContratRecursif(){
    global $DB;

      $query = "SELECT is_recursive
              FROM glpi_contracts
              WHERE id = $this->id";

      if($resultat = $DB->query($query))
         if($DB->numrows($resultat) > 0)
            if ($row = $DB->fetch_assoc($resultat))
               return $row["is_recursive"];

      return false;
  }

   /**
    * Retourne la dur�e du contrat
    *
    * @return int (en mois)
   **/
   function duree()
   {
      global $DB;

      $query = "SELECT duration
              FROM glpi_contracts
              WHERE id = $this->id";

      if($resultat = $DB->query($query))
         if($DB->numrows($resultat) > 0)
            if ($row = $DB->fetch_assoc($resultat))
               return $row["duration"];
      return 0;
   } // duree()

   /**
    * Retourne la date de fin du contrat
    *
    * @return string (au format date)
   **/
   function dateFin()
   {
      return date("Y-m-d", strtotime($this->dateDeb() . "+". $this->duree() . " MONTH"));
   } // dateFin()

   /**
    * Retourne le nom du contrat
    *
    * @return string
   **/
   function name()
   {
      global $DB;

      $query = "SELECT name
              FROM glpi_contracts
              WHERE id = $this->id";

      if($resultat = $DB->query($query))
         if($DB->numrows($resultat) > 0)
            if ($row = $DB->fetch_assoc($resultat))
               return $row["name"];
      return "";
   } // name()


   /**
    * Retourne le num�ro du contrat
    *
    * @return string
   **/
   function number()
   {
      global $DB, $LANG;

      $query = "SELECT num
              FROM glpi_contracts
              WHERE id = $this->id";

      if($resultat = $DB->query($query))
         if($DB->numrows($resultat) > 0)
            if ($row = $DB->fetch_assoc($resultat))
               return $row["num"];
      return "";
   } // number()

   /**
    * Retourne l'interlocuteur
    *
    * @return string
   **/
   function giveContact()
   {
      global $DB, $LANG;

      $query = "SELECT firstname, realname, phone, mobile
              FROM glpi_contracts contrat
               LEFT JOIN glpi_users user
                  ON contrat.   entities_id = user.entities_id
              WHERE contrat.id = $this->id";

      if($resultat = $DB->query($query))
         if($DB->numrows($resultat) > 0)
            if ($row = $DB->fetch_assoc($resultat))
               return $LANG["bestmanagement"]["allrecap"][14] . " : " .
                     $row["firstname"] . " " . $row["realname"] . "&nbsp;&nbsp;&nbsp;" .
                     $row["phone"] . "&nbsp;&nbsp;&nbsp;" . $row["mobile"];
      return $LANG["bestmanagement"]["allrecap"][15];
   } // giveContact()

   /**
    * Retourne le formulaire de reconduction
    *
    * @return Nothing(Display)
   **/
   function renewal()
   {
      global $DB, $CFG_GLPI, $LANG;

      if ($_SESSION["glpi_currenttime"] < $this->dateFin())   // contrat en cours
         echo $LANG["bestmanagement"]["renewal"][0] . Html::convDate($this->dateFin());
      else
      {
         echo "<div class='center'>";
         echo "<strong>" . $LANG["bestmanagement"]["renewal"][1] . "</strong>";
         echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/bestmanagement.form.php\">";
         echo "<input type='hidden' name='id_contrat'   value='$this->id'>";
         echo "<table class='tab_cadre' style='margin-top: 1em;'>";
         if ($this->areSetValues() && !$this->isContratIllim())
         {
            //----------------------------------------------
            // Pr�paration des requ�tes
            // Le tableau est index� selon l'ID du compteur
            //----------------------------------------------
            $tab_restant= $this->prepareTabRestant();

            $info_compteur = $this->infoCompteur();

            if ($info_compteur["compteur"] == "category")
               $compteur = $LANG['common'][36];      // cat
            else
               $compteur = $LANG['joblist'][2];      // prio

            echo "<tr><th align=center colspan='2'>".$LANG["bestmanagement"]["renewal"][2]."</th>";
            echo "<tr><th align=center>" . $compteur . "</th>";
            echo "<th>".$LANG["bestmanagement"]["allrecap"][3]."</th></tr>"; // fin Titre
            $tr = "<tr class='tab_bg_2'>";
            $td = "<td align=center>";
            // remplissage des lignes du tableau
            foreach(array_keys($tab_restant) as $key)
            {
               // s'il n'y a ni heure achet�e, report�e ou consomm�e on n'affiche pas la ligne
               if ($key == 0 || $tab_restant[$key] == 0) continue;

               echo $tr;
               echo $td . $this->giveCompteurName($key, $info_compteur)               . "</td>";
               echo ($tab_restant[$key] < 0) ? "<td class='red tab_bg_2_2'   align=center><strong>"  : $td;
               echo $this->arrangeIfHours($tab_restant[$key], $info_compteur["unit"]). "</td>";
               echo "</tr>";
            }

            echo $tr;
            // Reporter les heures ?
            echo $td . $LANG["bestmanagement"]["renewal"][3] . "<input type='checkbox' name='report'></td>";

         }
         else   // aucun report vu que pas de valeurs
            echo "<tr><td>" . $LANG["bestmanagement"]["renewal"][5] . "</td>";

         echo "<td align='center'><input type=\"submit\" name=\"addRenewal\" class=\"submit\" value=\"".$LANG["bestmanagement"]["renewal"][4]."\" >";
         echo "&nbsp;<input type=\"submit\" name=\"deleteContrat\" class=\"submit\" value=\"".$LANG["bestmanagement"]["renewal"][6]."\" ></td>";
         echo "</tr>";
         echo "</table>";
         echo "</form>";
         echo "</div>";
      }
   } // renewal()

   /**
    * Retourne le formulaire de facturation
    *
    * @return Nothing(Display)
   **/
   function facture()
   {
      global $DB, $CFG_GLPI, $LANG;

      if($this->nbAchats() == 0)
         echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][16] . "</div>";
      else
      {
         echo "<div>";
         echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/plugins/bestmanagement/front/bestmanagement.form.php\">";
            $this->historical(true);
            echo "<input type='hidden' name='id_contrat'   value='$this->id'>";
            echo "<table class='tab_cadre'>";
            echo "<tr class='tab_bg_2'><td>".$LANG["bestmanagement"]["facturation"][0]." : </td>";
            echo "<td>";
            $this->inputFacturation();
            echo "</td>";
            echo "<td align='center'><input type=\"submit\" name=\"addFacturation\" class=\"submit\" value=\"".$LANG['buttons'][51]."\" ></td>";
            echo "</tr>";
            echo "</table>";
         echo "</form>";
         echo "</div>";
      }
   } // facture()

   /**
    * Affiche la zone de texte correspondant au
    * num�ro de facture.
    *
    * @return Nothing(Display)
   **/
   function inputFacturation()
   {
      global $LANG;

      echo "<input type='text' name='NumFact' size='15'
            value='" . $LANG["bestmanagement"]["facturation_contrat"][3] . "' onfocus=\"this.value='';\">";
   } // inputFacturation()

   /**
    * Retourne l'�tat de facturation du contrat
    * 0 => factur�
    * else => non factur�
    *
    * @return int
   **/
   function etatFact()
   {
      global $DB;

      $query = "SELECT SUM(etat_fact) Etat
              FROM glpi_plugin_bestmanagement_achat achat
               INNER JOIN glpi_contracts contrat
                  ON achat.ID_Contrat = contrat.ID
              WHERE achat.ID_Contrat = $this->id
               AND achat.date_deb = contrat.begin_date
               AND ID_Compteur IS NOT NULL
               AND UnitBought IS NOT NULL";

      if($resultat = $DB->query($query))
         if($DB->numrows($resultat) > 0)
            $row = $DB->fetch_assoc($resultat);

      $etat = (isset($row["Etat"]) && $row["Etat"] != 0) ? $row["Etat"] : 0;

      return $etat;
   } // etatFact()

   //-------//
   // CRON  //
   //-------//
   /**
   * Give localized information about 1 task
   *
   * @param $name of the task
   *
   * @return array of strings
   */
   static function cronInfo($name)
   {
      global $LANG;

      switch ($name)
      {
        case 'SQL' :
         return array('description' => $LANG["bestmanagement"]["cron"][4]);
        case 'Verif' :
         return array('description' => $LANG["bestmanagement"]["cron"][5]);
      }
      return array();
   } // cronInfo()

   /**
    * Retourne les lignes html pour le mail
    *
    * @return <tr> <td> ... </td> </tr>
   **/
   function ContratMailing($colonnes=array(), $colors)
   {
      global $LANG, $CFG_GLPI;
      $lignes = "";

      //================//
      // Premi�re ligne //
      //================//
      $td2= "<td colspan='2'>";   // td normal colspan=2
      $td3= "<td align='left'; colspan='3'>";   // td normal colspan=3

      $lignes .= "<tr style=\"background-color:" . $colors["tr2"] . "; color:#000000;\">";
      // lien vers le contrat
      $protocole = strstr($_SERVER["HTTP_REFERER"], $CFG_GLPI["root_doc"], true);

      $destination  = $protocole . $CFG_GLPI["root_doc"];
      $destination .= "/front/contract.form.php?id=$this->id";

      $lignes .= $td3."<a href=\"$destination\">".$this->number() . " - " . $this->giveRealName()."</a>";
      // date de fin format�e
      $lignes .= "&nbsp;&nbsp;(" . $LANG["bestmanagement"]["allrecap"][11] . Html::convDate($this->dateFin()) . ")</td>";
      $lignes .= $td2 . $this->giveManagement() . "</td>";
      $lignes .= "</tr>";

      //================//
      // Deuxi�me ligne //
      //================//
      $lignes .= "<tr style=\"background-color:" . $colors["tr1"] . "; color:#000000;\">";
      // contact
      $lignes .= "<td colspan='5' align='left'><i>" . $this->giveContact() . "</i></td>";

      $lignes .= "</tr>";

      // alternance de couleur pour l'affichage
      $tr1 = "<tr style=\"background-color:" . $colors["tr2"] . "; color:#000000;\">";
      $tr2 = "<tr style=\"background-color:" . $colors["tr1"] . "; color:#000000;\">";

      //=======================//
      // Tableau r�capitulatif //
      //=======================//
      if ($this->nbAchats())
      {
         $lignes .= $tr1;
         foreach ($colonnes as $col)
            $lignes .= "<td>".$col."</td>";
         $lignes .= "</tr>";

         $lignes .= $this->currentRecap($tr1, $tr2);   // tableau r�capitulatif
      }

      return $lignes;
   } // ContratMailing()

   /**
   * Execute la t�che d'envoi de mails
   *
   * @param $task Object of CronTask class for log / stat
   *
   * @return interger
   *    >0 : done
   *    <0 : to be run again (not finished)
   *     0 : nothing to do
   */
   static function cronVerif($task)
   {
      global $DB, $CFG_GLPI, $LANG;

      if (!$CFG_GLPI["use_mailing"])
         return 0;

      $body = "";

      $body .= "<html>";
      $body .= "<head><style  type='text/css'>body {font-family: Verdana;font-size: 11px;text-align: left;}";
      $body .= "table {border: 1px solid #cccccc; border-color:black; border-collapse:collapse}";
      $body .= "table {class='tab_cadre'}";
      $body .= "th {text-align: center; border-width:1px; border-style:solid; padding: 2px;}";
      $body .= "tr {text-align: center; border-width:1px; border-style:solid; padding: 2px;}";
      $body .= "a {color: black; font-weight : bold }";
      $body .= "</style></head>";
      $body .="<body>";

      $all_contrats = array();

      if (whichContratSend("contratended"))
      {
         $query_contracts_end = "SELECT *
                           FROM glpi_contracts
                           WHERE begin_date IS NOT NULL AND duration IS NOT NULL
                              AND DATE_ADD(begin_date, INTERVAL duration MONTH) BETWEEN
                                 DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND CURDATE()
                           ORDER BY DATE_ADD(begin_date, INTERVAL duration MONTH)";

         if($res_end = $DB->query($query_contracts_end))
            if($DB->numrows($res_end) > 0)
               while ($row = $DB->fetch_assoc($res_end))
                  $all_contrats[0][] = $row["id"];
      } // contrat_ended
      if (whichContratSend("contratending"))
      {
         $query_contracts_ending = "SELECT *
                              FROM glpi_contracts
                              WHERE begin_date IS NOT NULL AND duration IS NOT NULL
                              AND DATE_ADD(begin_date, INTERVAL duration MONTH) BETWEEN
                                 CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
                              ORDER BY DATE_ADD(begin_date, INTERVAL duration MONTH)";

         if($res_ending = $DB->query($query_contracts_ending))
            if($DB->numrows($res_ending) > 0)
               while ($row = $DB->fetch_assoc($res_ending))
                  $all_contrats[1][] = $row["id"];
      } // contrat_ending
      if (whichContratSend("consoexceeded"))
      {
         $contrats_deja_selected = verifDoublonsQuery($all_contrats);

         $query_id_contracts = "SELECT cont.id
                           FROM glpi_contracts cont, glpi_plugin_bestmanagement_typecontrat typeC
                           WHERE cont.is_deleted = 0
                           AND cont.begin_date IS NOT NULL AND cont.duration IS NOT NULL
                           AND cont.contracttypes_id = typeC.id
                           AND typeC.illimite = 1
                           $contrats_deja_selected
                           AND DATE_ADD(cont.begin_date, INTERVAL cont.duration MONTH) > CURDATE()";

         if($res = $DB->query($query_id_contracts))
            if($DB->numrows($res) > 0)
               while ($row = $DB->fetch_assoc($res))
               {
                  $contrat = new PluginBestmanagementContrat($row["id"]);
                  $info_compteur = $contrat->infoCompteur();
                  if (count($info_compteur) == 0) continue;

                  $tab_restant= $contrat->prepareTabRestant();

                  $exit = false;
                  // v�rification des r�sultats
                  foreach(array_keys($tab_restant) as $key)
                  {
                     // si on a d�j� le contrat ou ni heure achet�e ni report�e ou consomm�e
                     if ($exit || $key == 0 || $tab_restant[$key] == 0) continue;

                     if ($tab_restant[$key] < 0)   // consommation n�gative
                     {
                        $all_contrats[2][] = $row["id"];
                        $exit = true;   // on a le contrat, pas besoin de le reins�rer dans $all_contrats
                     }
                  }
               } // while
      } // contrat_consoexceeded
      if (whichContratSend("ratioexceeded"))
      {
         $contrats_deja_selected = verifDoublonsQuery($all_contrats);

         $query_id_contracts = "SELECT cont.id
                           FROM glpi_contracts cont, glpi_plugin_bestmanagement_typecontrat typeC
                           WHERE cont.is_deleted = 0
                           AND cont.begin_date IS NOT NULL AND cont.duration IS NOT NULL
                           AND cont.contracttypes_id = typeC.id
                           AND typeC.illimite = 1
                           $contrats_deja_selected
                           AND DATE_ADD(cont.begin_date, INTERVAL cont.duration MONTH) > CURDATE()";

         $ratio = getItem("ratiocontrat", "glpi_plugin_bestmanagement_config");

         if($res = $DB->query($query_id_contracts))
            if($DB->numrows($res) > 0)
               while ($row = $DB->fetch_assoc($res))
               {
                  $contrat = new PluginBestmanagementContrat($row["id"]);
                  $info_compteur = $contrat->infoCompteur();
                  if (count($info_compteur) == 0) continue;

                  //-------------------------------------------------
                  // Pr�paration des requ�tes
                  // Les tableau sont index�s selon l'ID du compteur
                  //-------------------------------------------------
                  $tab_achat   = $contrat->prepareTab("achat");
                  $tab_report   = $contrat->prepareTab("report");
                  $tab_restant= $contrat->prepareTabRestant();

                  $exit = false;
                  // v�rification des r�sultats
                  foreach(array_keys($tab_restant) as $key)
                  {
                     // si on a d�j� le contrat ou ni heure achet�e ni report�e ou consomm�e
                     if ($exit || $key == 0 || $tab_restant[$key] == 0) continue;

                     // si les valeurs ne sont pas d�finies :
                     $tab_report[$key]   = isset($tab_report[$key])   ? $tab_report[$key]   : 0;
                     $tab_achat[$key]   = isset($tab_achat[$key])   ? $tab_achat[$key]   : 0;

                     if (($tab_restant[$key]/$tab_achat[$key]+$tab_report[$key])*100 <= $ratio && ($tab_restant[$key]/$tab_achat[$key]+$tab_report[$key])*100 >= 0)
                     {   // ratio d�pass�
                        $all_contrats[3][] = $row["id"];
                        $exit = true;   // on a le contrat, pas besoin de le reins�rer dans $all_contrats
                     }
                  }
               } // while
      } // contrat_ratioexceeded

      if (count($all_contrats) == 0) return 0;   // si pas de contrat, pas de mail

      $colonnes = array($LANG["bestmanagement"]["allrecap"][2],
                    $LANG["bestmanagement"]["allrecap"][3],
                    $LANG["bestmanagement"]["allrecap"][4],
                    $LANG["bestmanagement"]["allrecap"][5]);

      foreach(array_keys($all_contrats) as $key)
      {
         $colors = getMailColors();   // d�finit les couleurs des tableaux

         $body .= "<table>";
         // titre bleu fonc�
         $body .= "<tr><th style='background-color:" . $colors["titre"] . ";' colspan='5'>".$LANG["bestmanagement"]["cron"][$key]."</th></tr>";

         foreach($all_contrats[$key] as $id)
         {
            if (isset($un_contrat))   // ligne vierge pour s�parer les contrats
               $body .= "<tr style=\"background-color:#ffffff;\"><td colspan='5'>&nbsp;</td></tr>";

            $un_contrat = new PluginBestmanagementContrat($id);

            $info_cpt = $un_contrat->infoCompteur();

            if (isset($info_cpt["compteur"]))
            {
               if ($info_cpt["compteur"] == "category")
                  $compteur = $LANG['common'][36];      // cat
               else
                  $compteur = $LANG['joblist'][2];      // prio

               array_unshift($colonnes, $compteur);
            }
            else
               array_unshift($colonnes, "");

            $body .= $un_contrat->ContratMailing($colonnes, $colors);
            array_shift($colonnes);
            $task->addVolume(1);
            $task->log($LANG["bestmanagement"]["cron"][10+$key] . " : <a href=\"".Toolbox::getItemTypeFormURL("Contract")."?id=$id\">".$un_contrat->name()."</a>"); //TODO
         } // foreach 2
         $body .= "</table><br><br>";

         if (isset($un_contrat)) unset($un_contrat);

      } // foreach 1

      $body .= "</table><br><br>";
      $body.="</body></html>";

      // on r�cup�re les adresses e-mail
      foreach (getAdresses() as $ad)
      {
         $mmail= new NotificationMail;
         $mmail->From      = $CFG_GLPI["admin_email"];
         $mmail->FromName   = $CFG_GLPI["admin_email"];
         $mmail->AddAddress($ad, "GLPI");
         $mmail->Subject      = $LANG["bestmanagement"]["cron"][6];
         $mmail->Body      = $body;
         $mmail->isHTML(true);
         $mmail->Send();
      }
      return 1;

   } // cronVerif

   /**
   * Execute la t�che de sauvegarde de la BD
   *
   * @param $task Object of CronTask class for log / stat
   *
   * @return interger
   *    >0 : done
   *    <0 : to be run again (not finished)
   *     0 : nothing to do
   */
   static function cronSQL($task)
   {
      global $DB, $CFG_GLPI, $LANG, $TPSCOUR;

      $time_file = date("Y-m-d-H-i");
      // $dumpFile, fichier source
      $dumpFile = GLPI_DUMP_DIR . "/glpi-".GLPI_VERSION."-$time_file.sql";

      $fileHandle = fopen($dumpFile, "a");

      $i = 0;
      $j = -1;
      $rowlimit = 5;

      $cur_time = date("Y-m-d H:i");
      $todump = "#GLPI Dump database on $cur_time\n";
      fwrite ($fileHandle,$todump);


      $result = $DB->list_tables();
      $numtab = 0;
      while ($t = $DB->fetch_array($result))
      {
         // on se  limite aux tables prefixees _glpi
         if (strstr($t[0],"glpi_"))
         {
            $tables[$numtab] = $t[0];
            $numtab++;
         }
      }

      for ( ; $i<$numtab ; $i++)
      {
         // Dump de la structure table
         if ($j == -1)
         {
            $todump = "\n".get_def2($DB,$tables[$i]);
            fwrite ($fileHandle,$todump);
            $j++;
         }

         $fin = 0;
         while (!$fin)
         {
            $todump = get_content2($DB,$tables[$i],$j,$rowlimit);
            $rowtodump = substr_count($todump, "INSERT INTO");
            if ($rowtodump >0)
            {
               fwrite ($fileHandle,$todump);
               $j += $rowlimit;
               if ($rowtodump<$rowlimit)
                  $fin = 1;
            }
            else
            {
               $fin = 1;
               $j = -1;
            }
         } // while
         if ($fin)
            $j = -1;
      } // for

      $nb = 0;
      if ($DB->error())
         $nb = 1;
      else
         $task->addVolume(1);

      fclose($fileHandle);

      $task->log($LANG["bestmanagement"]["sql"][$nb] ." $dumpFile");
      return 1;

   } // cronSQL

   /**
   * V�rifie si des valeurs ont �t� saisies
   * dans achat, report et consommation
   *
   * @return boolean
   */
   function areSetValues()
   {
      $val = (count($this->prepareTabRestant()) == 0) ? false : true;

      // cas exceptionnel : si on a des unit�s nulles (par exemple en cas de r�ajustement)
      // on v�rifie qu'on ait au moins une unit� non nulle, sinon return false
      if ($val)
         foreach ($this->prepareTabRestant() as $unit)
            if ($unit == 0)   $val = false;
            else   return true;

      return $val;
   } // areSetValues()

} // class PluginContrat
?>
