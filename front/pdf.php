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

include ("../../../inc/includes.php");

require_once(GLPI_ROOT."/plugins/supportcontract/lib/dompdf/dompdf_config.inc.php");
require_once(GLPI_ROOT."/plugins/supportcontract/lib/dompdf/include/canvas.cls.php");
//require_once(GLPI_ROOT."/plugins/supportcontract/lib/dompdf/include/cpdf_adapter.cls.php");
require_once(GLPI_ROOT."/plugins/supportcontract/lib/dompdf/include/positioner.cls.php");
require_once(GLPI_ROOT."/plugins/supportcontract/lib/dompdf/include/frame.cls.php");
require_once(GLPI_ROOT."/plugins/supportcontract/lib/dompdf/include/frame_decorator.cls.php");
require_once(GLPI_ROOT."/plugins/supportcontract/lib/dompdf/include/frame_reflower.cls.php");
//require_once(GLPI_ROOT."/plugins/supportcontract/lib/dompdf/include/cached_pdf_decorator.cls.php");

foreach (glob(GLPI_ROOT.'/plugins/supportcontract/lib/dompdf/include/*.php') as $file) {
   if (!strstr($file, 'cache')) {
      require_once($file);
   }
}

$html = "coucou";
  $dompdf = new DOMPDF();
  $dompdf->load_html($html);
  $dompdf->set_paper("A4");
  $dompdf->render();

//  $dompdf->stream("dompdf_out.pdf", array("Attachment" => false));

  exit(0);


?>