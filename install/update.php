<?php

function pluginBestmanagementUpdate($current_version, $migrationname='Migration') {
   global $DB;

   ini_set("max_execution_time", "0");

   foreach (glob(GLPI_ROOT.'/plugins/bestmanagement/inc/*.php') as $file) {
      require_once($file);
   }

   $migration = new $migrationname($current_version);
  
   $DB->query("DROP TABLE `glpi_plugin_bestmanagement_facturation_ticket`");

   /*
    * Table glpi_plugin_bestmanagement_purchases
    */
      $a_table = array();
      $a_table['name'] = 'glpi_plugin_bestmanagement_purchases';
      $a_table['oldname'] = array('glpi_plugin_bestmanagement_achat');

      $a_table['fields']  = array();
      $a_table['fields']['id']            = array('type'    => 'autoincrement',
                                                  'value'   => '');
      $a_table['fields']['contracts_id']  = array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['begin_date']    = array('type'    => 'date',
                                                  'value'   => NULL);
      $a_table['fields']['definitions_id']= array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['unit']          = array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['avenant']       = array('type'    => 'bool',
                                                  'value'   => NULL);
      $a_table['fields']['invoice_state'] = array('type'    => 'bool',
                                                  'value'   => NULL);
      $a_table['fields']['invoice_number']  = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['comment']       = array('type'    => 'text',
                                                  'value'   => NULL);
      $a_table['fields']['date_save']     = array('type'    => 'datetime',
                                                  'value'   => NULL);

      $a_table['oldfields']  = array('Type_Compteur', 'Type_Unit');

      $a_table['renamefields'] = array();
      $a_table['renamefields']['ID_Contrat']    = 'contracts_id';
      $a_table['renamefields']['comments']      = 'comment';
      $a_table['renamefields']['ID_Compteur']   = 'value_name';
      $a_table['renamefields']['date_deb']      = 'begin_date';
      $a_table['renamefields']['UnitBought']    = 'unit';
      $a_table['renamefields']['value_name']    = 'definitions_id';
      $a_table['renamefields']['avenant']       = 'amendment';
      $a_table['renamefields']['etat_fact']     = 'invoice_state';
      $a_table['renamefields']['num_fact_api']  = 'invoice_number';
      
      $a_table['keys']   = array();
      $a_table['keys'][] = array('field' => 'contracts_id', 'name' => '', 'type' => 'INDEX');

      $a_table['oldkeys'] = array();

      migrateTablesBestmanagement($migration, $a_table);
 
      

   /*
    * Table glpi_plugin_bestmanagement_configs
    */
      $a_table = array();
      $a_table['name'] = 'glpi_plugin_bestmanagement_configs';
      $a_table['oldname'] = array();

      $a_table['fields']  = array();
      $a_table['fields']['id']               = array('type'    => 'autoincrement',
                                                     'value'   => '');
      $a_table['fields']['ticket_category']  = array('type'    => 'bool',
                                                     'value'   => NULL);
      $a_table['fields']['time_creation']    = array('type'    => 'bool',
                                                     'value'   => NULL);
      $a_table['fields']['duration']         = array('type'    => 'bool',
                                                     'value'   => NULL);
      $a_table['fields']['contract_type']    = array('type'    => 'bool',
                                                     'value'   => NULL);
      $a_table['fields']['task_category']    = array('type'    => 'bool',
                                                     'value'   => NULL);
      $a_table['fields']['no_renewal']       = array('type'    => 'bool',
                                                     'value'   => NULL);
      $a_table['fields']['color_priority']   = array('type'    => 'bool',
                                                     'value'   => NULL);
      $a_table['fields']['ratiocontrat']     = array('type'    => 'string',
                                                     'value'   => NULL);
      $a_table['fields']['colormail']        = array('type'    => 'string',
                                                     'value'   => NULL);
      $a_table['fields']['destinataires']    = array('type'    => 'string',
                                                     'value'   => NULL);
      
      $a_table['oldfields']  = array();

      $a_table['renamefields'] = array();
     
      $a_table['keys']   = array();

      $a_table['oldkeys'] = array();

      migrateTablesBestmanagement($migration, $a_table);
      
      

   /*
    * Table glpi_plugin_bestmanagement_logs
    */
      $a_table = array();
      $a_table['name'] = 'glpi_plugin_bestmanagement_logs';
      $a_table['oldname'] = array('glpi_plugin_bestmanagement_historique');

      $a_table['fields']  = array();
      $a_table['fields']['id']            = array('type'    => 'autoincrement',
                                                  'value'   => '');
      $a_table['fields']['contracts_id']  = array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['date_deb']      = array('type'    => 'date',
                                                  'value'   => NULL);
      $a_table['fields']['duree']         = array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['Type_Compteur'] = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['ID_Compteur']  = array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['Type_Unit']     = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['achat']         = array('type'    => 'float',
                                                  'value'   => NULL);
      $a_table['fields']['report']        = array('type'    => 'float',
                                                  'value'   => NULL);
      $a_table['fields']['conso']         = array('type'    => 'float',
                                                  'value'   => NULL);
       
      $a_table['oldfields']  = array();

      $a_table['renamefields'] = array();
      $a_table['renamefields']['ID_Contrat']    = 'contracts_id';
     
      $a_table['keys']   = array();

      $a_table['oldkeys'] = array();

      migrateTablesBestmanagement($migration, $a_table); 
      
            

   /*
    * Table glpi_plugin_bestmanagement_tickets_contracts
    */
      $a_table = array();
      $a_table['name'] = 'glpi_plugin_bestmanagement_tickets_contracts';
      $a_table['oldname'] = array('glpi_plugin_bestmanagement_link_ticketcontrat');

      $a_table['fields']  = array();
      $a_table['fields']['id']            = array('type'    => 'autoincrement',
                                                  'value'   => '');
      $a_table['fields']['tickets_id']    = array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['contracts_id']  = array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['unit_number']   = array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['invoice_state'] = array('type'    => 'bool',
                                                  'value'   => NULL);
      $a_table['fields']['invoice_number']= array('type'    => 'string',
                                                  'value'   => NULL);
       
      $a_table['oldfields']  = array();

      $a_table['renamefields'] = array();
      $a_table['renamefields']['ID_Ticket']   = 'tickets_id';
      $a_table['renamefields']['ID_Contrat']  = 'contracts_id';
     
      $a_table['keys']   = array();
      $a_table['keys'][] = array('field' => 'tickets_id', 'name' => '', 'type' => 'INDEX');

      $a_table['oldkeys'] = array();

      migrateTablesBestmanagement($migration, $a_table);
      
            

   /*
    * Table glpi_plugin_bestmanagement_mailings
    */
      $a_table = array();
      $a_table['name'] = 'glpi_plugin_bestmanagement_mailings';
      $a_table['oldname'] = array('glpi_plugin_bestmanagement_mailing');

      $a_table['fields']  = array();
      $a_table['fields']['id']            = array('type'    => 'autoincrement',
                                                  'value'   => '');
      $a_table['fields']['contratended'] = array('type'    => 'bool',
                                                  'value'   => NULL);
      $a_table['fields']['contratending'] = array('type'    => 'bool',
                                                  'value'   => NULL);
      $a_table['fields']['consoexceeded'] = array('type'    => 'bool',
                                                  'value'   => NULL);
      $a_table['fields']['ratioexceeded'] = array('type'    => 'bool',
                                                  'value'   => NULL);

       
      $a_table['oldfields']  = array();

      $a_table['renamefields'] = array();
     
      $a_table['keys']   = array();

      $a_table['oldkeys'] = array();

      migrateTablesBestmanagement($migration, $a_table);
      
            

   /*
    * Table glpi_plugin_bestmanagement_entities
    */
      $a_table = array();
      $a_table['name'] = 'glpi_plugin_bestmanagement_entities';
      $a_table['oldname'] = array('glpi_plugin_bestmanagement_pdf');

      $a_table['fields']  = array();
      $a_table['fields']['id']            = array('type'    => 'autoincrement',
                                                  'value'   => '');
      $a_table['fields']['entities_id']   = array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['entete']        = array('type'    => 'bool',
                                                  'value'   => NULL);
      $a_table['fields']['logo']          = array('type'    => 'bool',
                                                  'value'   => NULL);
      $a_table['fields']['titre']         = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['auteur']        = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['sujet']         = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['adresse']       = array('type'    => 'text',
                                                  'value'   => NULL);
      $a_table['fields']['cp']            = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['ville']         = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['tel']           = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['fax']           = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['web']           = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['mail']          = array('type'    => 'string',
                                                  'value'   => NULL);
      $a_table['fields']['footer']        = array('type'    => 'text',
                                                  'value'   => NULL);
      $a_table['fields']['cgv']           = array('type'    => 'string',
                                                  'value'   => NULL);
      
      $a_table['oldfields']  = array();

      $a_table['renamefields'] = array();
     
      $a_table['keys']   = array();
      $a_table['keys'][] = array('field' => 'entities_id', 'name' => '', 'type' => 'INDEX');

      $a_table['oldkeys'] = array();

      migrateTablesBestmanagement($migration, $a_table);
      
            

   /*
    * Table glpi_plugin_bestmanagement_profiles
    */
      $a_table = array();
      $a_table['name'] = 'glpi_plugin_bestmanagement_profiles';
      $a_table['oldname'] = array();

      $a_table['fields']  = array();
      $a_table['fields']['id']                  = array('type'    => 'autoincrement',
                                                        'value'   => '');
      $a_table['fields']['profiles_id']         = array('type'    => 'integer',
                                                        'value'   => NULL);
      $a_table['fields']['recapglobal']         = array('type'    => 'char',
                                                        'value'   => NULL);
      $a_table['fields']['recapcontrat']        = array('type'    => 'char',
                                                        'value'   => NULL);
      $a_table['fields']['historicalpurchase']  = array('type'    => 'char',
                                                        'value'   => NULL);
      $a_table['fields']['historicalperiode']   = array('type'    => 'char',
                                                        'value'   => NULL);
      $a_table['fields']['addpurchase']         = array('type'    => 'char',
                                                        'value'   => NULL);
      $a_table['fields']['facturationcontrat']  = array('type'    => 'char',
                                                        'value'   => NULL);
      $a_table['fields']['renewal']             = array('type'    => 'char',
                                                        'value'   => NULL);
      $a_table['fields']['mailing']             = array('type'    => 'char',
                                                        'value'   => NULL);
      $a_table['fields']['linkticketcontrat']   = array('type'    => 'char',
                                                        'value'   => NULL);
      $a_table['fields']['facturationticket']   = array('type'    => 'char',
                                                        'value'   => NULL);
      $a_table['fields']['modifcontentmailing'] = array('type'    => 'char',
                                                        'value'   => NULL);
      
      $a_table['oldfields']  = array();

      $a_table['renamefields'] = array();
     
      $a_table['keys']   = array();
      $a_table['keys'][] = array('field' => 'profiles_id', 'name' => '', 'type' => 'INDEX');

      $a_table['oldkeys'] = array();

      migrateTablesBestmanagement($migration, $a_table);
      
            

   /*
    * Table glpi_plugin_bestmanagement_reconductions
    */
      $a_table = array();
      $a_table['name'] = 'glpi_plugin_bestmanagement_reconductions';
      $a_table['oldname'] = array('glpi_plugin_bestmanagement_reconduction');

      $a_table['fields']  = array();
      $a_table['fields']['id']            = array('type'    => 'autoincrement',
                                                  'value'   => '');
      $a_table['fields']['date_save']     = array('type'    => 'date',
                                                  'value'   => NULL);
      $a_table['fields']['contracts_id']  = array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['begin_date']    = array('type'    => 'date',
                                                  'value'   => NULL);
      $a_table['fields']['report_credit'] = array('type'    => 'integer',
                                                  'value'   => NULL);
       
      $a_table['oldfields']  = array();

      $a_table['renamefields'] = array();
      $a_table['renamefields']['ID_Contrat']  = 'contracts_id';
     
      $a_table['keys']   = array();
      $a_table['keys'][] = array('field' => 'contracts_id', 'name' => '', 'type' => 'INDEX');

      $a_table['oldkeys'] = array();

      migrateTablesBestmanagement($migration, $a_table);
      
            

   /*
    * Table glpi_plugin_bestmanagement_reports
    */
      $a_table = array();
      $a_table['name'] = 'glpi_plugin_bestmanagement_reports';
      $a_table['oldname'] = array('glpi_plugin_bestmanagement_report');

      $a_table['fields']  = array();
      $a_table['fields']['id']            = array('type'    => 'autoincrement',
                                                  'value'   => '');
      $a_table['fields']['plugin_bestmanagement_reconductions_id'] = array('type'    => 'integer',
                                                                           'value'   => NULL);
      $a_table['fields']['ID_Compteur']                            = array('type'    => 'integer',
                                                                           'value'   => NULL);
      $a_table['fields']['Nb_Unit']                                = array('type'    => 'float',
                                                                           'value'   => NULL);
      
      $a_table['oldfields']  = array();

      $a_table['renamefields'] = array();
      $a_table['renamefields']['ID_Reconduction']  = 'plugin_bestmanagement_reconductions_id';
     
      $a_table['keys']   = array();
      
      $a_table['oldkeys'] = array();

      migrateTablesBestmanagement($migration, $a_table);
      
            

   /*
    * Table glpi_plugin_bestmanagement_contracts
    */
      $a_table = array();
      $a_table['name'] = 'glpi_plugin_bestmanagement_contracts';
      $a_table['oldname'] = array('glpi_plugin_bestmanagement_typecontrat');

      $a_table['fields']  = array();
      $a_table['fields']['id']            = array('type'    => 'autoincrement',
                                                  'value'   => '');
      $a_table['fields']['contracts_id']  = array('type'    => 'integer',
                                                  'value'   => NULL);
      $a_table['fields']['illimite']      = array('type'    => 'bool',
                                                  'value'   => NULL);
      $a_table['fields']['unit_type']     = array('type'    => 'varchar',
                                                  'value'   => NULL);
      $a_table['fields']['definition']    = array('type'    => 'varchar',
                                                  'value'   => NULL);
      
      $a_table['oldfields']  = array();

      $a_table['renamefields'] = array();
     
      $a_table['keys']   = array();
      $a_table['keys'][] = array('field' => 'contracts_id', 'name' => '', 'type' => 'INDEX');
      
      $a_table['oldkeys'] = array();

      migrateTablesBestmanagement($migration, $a_table);
}



/**
 * Fonction used to migrate mysql structure
 * 
 * @global type $DB
 * 
 * @param type $migration
 * @param type $a_table
 * 
 * @return nothing (reload table cache structure before end)
 */
function migrateTablesBestmanagement($migration, $a_table) {
   global $DB;

   foreach ($a_table['oldname'] as $oldtable) {
      $migration->renameTable($oldtable, $a_table['name']);
   }

   if (!TableExists($a_table['name'])) {
      $query = "CREATE TABLE `".$a_table['name']."` (
                     `id` int(11) NOT NULL AUTO_INCREMENT,
                     PRIMARY KEY (`id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1";
      $DB->query($query);
   }

   foreach ($a_table['renamefields'] as $old=>$new) {
      $migration->changeField($a_table['name'],
                              $old,
                              $new,
                              $a_table['fields'][$new]['type'],
                              array('value' => $a_table['fields'][$new]['value'],
                                    'update'=> TRUE));
   }

   foreach ($a_table['oldfields'] as $field) {
      $migration->dropField($a_table['name'],
                            $field);
   }
   $migration->migrationOneTable($a_table['name']);

   foreach ($a_table['fields'] as $field=>$data) {
      $migration->changeField($a_table['name'],
                              $field,
                              $field,
                              $data['type'],
                              array('value' => $data['value']));
   }
   $migration->migrationOneTable($a_table['name']);

   foreach ($a_table['fields'] as $field=>$data) {
      $migration->addField($a_table['name'],
                           $field,
                           $data['type'],
                           array('value' => $data['value']));
   }
   $migration->migrationOneTable($a_table['name']);

   foreach ($a_table['oldkeys'] as $field) {
      $migration->dropKey($a_table['name'],
                          $field);
   }
   $migration->migrationOneTable($a_table['name']);

   foreach ($a_table['keys'] as $data) {
      $migration->addKey($a_table['name'],
                         $data['field'],
                         $data['name'],
                         $data['type']);
   }
   $migration->migrationOneTable($a_table['name']);

   $DB->list_fields($a_table['name'], FALSE);
}

?>
