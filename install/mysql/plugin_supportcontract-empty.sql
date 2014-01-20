DROP TABLE IF EXISTS `glpi_plugin_supportcontract_configs`;

CREATE TABLE `glpi_plugin_supportcontract_configs` (
   `id` int(11) NOT NULL auto_increment,
   `ticket_category` tinyint(1) NOT NULL DEFAULT '0',
   `time_creation` tinyint(1) NOT NULL DEFAULT '0',
   `date_deb` tinyint(1) NOT NULL DEFAULT '0',
   `duration` tinyint(1) NOT NULL DEFAULT '0',
   `contract_type` tinyint(1) NOT NULL DEFAULT '0',
   `task_category` tinyint(1) NOT NULL DEFAULT '0',
   `no_renewal` tinyint(1) NOT NULL DEFAULT '0',
   `color_priority` tinyint(1) NOT NULL DEFAULT '0',
   `ratiocontrat` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
   `colormail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
   `destinataires` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;



DROP TABLE IF EXISTS `glpi_plugin_supportcontract_contracts`;

CREATE TABLE `glpi_plugin_supportcontract_contracts` (
   `id` int(11) NOT NULL auto_increment,
   `contracts_id` int(11) NOT NULL DEFAULT '0',
   `illimite` tinyint(1) NOT NULL DEFAULT '0',
   `unit_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
   `definition` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `contracts_id` (`contracts_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;



DROP TABLE IF EXISTS `glpi_plugin_supportcontract_entities`;

CREATE TABLE `glpi_plugin_supportcontract_entities` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL DEFAULT '0',
   `entete` tinyint(1) NOT NULL DEFAULT '0',
   `logo` tinyint(1) NOT NULL DEFAULT '0',
   `titre` VARCHAR(255) NULL,
   `auteur` VARCHAR(255) NULL,
   `sujet` VARCHAR(255) NULL,
   `adresse` text NULL,
   `cp` VARCHAR(255) NULL,
   `ville` VARCHAR(255) NULL,
   `tel` VARCHAR(255) NULL,
   `fax` VARCHAR(255) NULL,
   `web` VARCHAR(255) NULL,
   `mail` VARCHAR(255) NULL,
   `footer` TEXT NULL,
   `cgv` CHAR(1) NULL,
   PRIMARY KEY  (`id`),
   KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;



DROP TABLE IF EXISTS `glpi_plugin_supportcontract_invoices`;

CREATE TABLE `glpi_plugin_supportcontract_invoice` (
   `id` int(11) NOT NULL auto_increment,
   `tickets_id` int(11) NOT NULL DEFAULT '0',
   `invoice_state` tinyint(1) NOT NULL DEFAULT '0',
   `invoice_number` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `tickets_id` (`tickets_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;



DROP TABLE IF EXISTS `glpi_plugin_supportcontract_logs`;

CREATE TABLE `glpi_plugin_supportcontract_logs` (
   `id` int(11) NOT NULL auto_increment,
   `contracts_id` int(11) NOT NULL DEFAULT '0',
   `date_deb` DATE NOT NULL,
   `duree` int(11) NOT NULL DEFAULT '0',
   `Type_Compteur` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
   `ID_Compteur` int(11) NOT NULL DEFAULT '0',
   `Type_Unit` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
   `achat` FLOAT NOT NULL,
   `report` FLOAT NOT NULL,
   `conso` FLOAT NOT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;



DROP TABLE IF EXISTS `glpi_plugin_supportcontract_mailings`;

CREATE TABLE `glpi_plugin_supportcontract_mailings` (
   `id` int(11) NOT NULL auto_increment,
   `contratended` tinyint(1) NOT NULL DEFAULT '0',
   `contratending` tinyint(1) NOT NULL DEFAULT '0',
   `consoexceeded` tinyint(1) NOT NULL DEFAULT '0',
   `ratioexceeded` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;



DROP TABLE IF EXISTS `glpi_plugin_supportcontract_profiles`;

CREATE TABLE `glpi_plugin_supportcontract_profiles` (
   `id` int(11) NOT NULL auto_increment,
   `profiles_id` int(11) NOT NULL DEFAULT '0',
   `recapglobal` CHAR(1) COLLATE utf8_unicode_ci DEFAULT NULL,
   `recapcontrat` CHAR(1) COLLATE utf8_unicode_ci DEFAULT NULL,
   `historicalpurchase` CHAR(1) COLLATE utf8_unicode_ci DEFAULT NULL,
   `historicalperiode` CHAR(1) COLLATE utf8_unicode_ci DEFAULT NULL,
   `addpurchase` CHAR(1) COLLATE utf8_unicode_ci DEFAULT NULL,
   `facturationcontrat` CHAR(1) COLLATE utf8_unicode_ci DEFAULT NULL,
   `renewal` CHAR(1) COLLATE utf8_unicode_ci DEFAULT NULL,
   `mailing` CHAR(1) COLLATE utf8_unicode_ci DEFAULT NULL,
   `linkticketcontrat` CHAR(1) COLLATE utf8_unicode_ci DEFAULT NULL,
   `facturationticket` CHAR(1) COLLATE utf8_unicode_ci DEFAULT NULL,
   `modifcontentmailing` CHAR(1) COLLATE utf8_unicode_ci DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `profiles_id` (`profiles_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;



DROP TABLE IF EXISTS `glpi_plugin_supportcontract_purchases`;

CREATE TABLE `glpi_plugin_supportcontract_purchases` (
   `id` int(11) NOT NULL auto_increment,
   `contracts_id` int(11) NOT NULL DEFAULT '0',
   `begin_date` date DEFAULT NULL,
   `definitions_id` int(11) NOT NULL DEFAULT '0',
   `unit` int(3) NOT NULL DEFAULT '0',
   `amendment` tinyint(1) NOT NULL DEFAULT '0',
   `invoice_state` tinyint(1) NOT NULL DEFAULT '0',
   `invoice_number` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
   `comment` text DEFAULT NULL,
   `date_save` datetime DEFAULT NULL,
   `close_date` datetime DEFAULT NULL,
   `plugin_supportcontract_contracts_periods` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
   KEY `contracts_id` (`contracts_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;



DROP TABLE IF EXISTS `glpi_plugin_supportcontract_contracts_periods`;

CREATE TABLE `glpi_plugin_supportcontract_contracts_periods` (
   `id` int(11) NOT NULL auto_increment,
   `date_save` DATE NOT NULL,
   `contracts_id` int(11) NOT NULL DEFAULT '0',
   `begin` DATE,
   `end` DATE,
   `report_credit` int(11) NOT NULL DEFAULT '0',
   PRIMARY KEY (`id`),
   KEY `contracts_id` (`contracts_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;



DROP TABLE IF EXISTS `glpi_plugin_supportcontract_reports`;

CREATE TABLE `glpi_plugin_supportcontract_reports` (
   `id` int(11) NOT NULL auto_increment,
   `plugin_supportcontract_reconductions_id` int(11) NOT NULL DEFAULT '0',
   `ID_Compteur` int(11) NOT NULL DEFAULT '0',
   `Nb_Unit` FLOAT NOT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;



DROP TABLE IF EXISTS `glpi_plugin_supportcontract_tickets_contracts`;

CREATE TABLE `glpi_plugin_supportcontract_tickets_contracts` (
   `id` int(11) NOT NULL auto_increment,
   `tickets_id` int(11) NOT NULL DEFAULT '0',
   `contracts_id` int(11) NOT NULL DEFAULT '0',
   `unit_number` int(2) NOT NULL DEFAULT '0',
   `invoice_state` tinyint(1) NOT NULL DEFAULT '0',
   `invoice_number` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
   `close_date` datetime DEFAULT NULL,
   `plugin_supportcontract_contracts_periods` int(11) NOT NULL DEFAULT '0',
   PRIMARY KEY (`id`),
   KEY `tickets_id` (`tickets_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
