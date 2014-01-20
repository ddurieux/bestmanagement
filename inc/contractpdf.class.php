<?php

class PluginBestmanagementContractPDF extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new PluginBestmanagementContract());
   }


   function defineAllTabs($options=array()) {

      $a_tabs = parent::defineAllTabs($options);
      unset($a_tabs['_main_']);
      unset($a_tabs['PluginBestmanagementContract$13']);
      unset($a_tabs['PluginBestmanagementContract$14']);
      unset($a_tabs['PluginBestmanagementContract$15']);
      
      
      unset($a_tabs['PluginBestmanagementContract$11']);
      unset($a_tabs['PluginBestmanagementContract$12']);
      unset($a_tabs['PluginBestmanagementContract$16']);
      unset($a_tabs['PluginBestmanagementContract$17']);
      return $a_tabs;
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case 'PluginBestmanagementContract$10' :
            $item->showSummaryPDF($pdf);
            break;

         default :
            return false;
      }
      return true;
   }
}