<?php
// Author: Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: TimeZone.php 3 2003-07-17 15:19:15Z will $

global $isTimeZoneDefined;
if($isTimeZoneDefined)
  return;
$isTimeZoneDefined = true;

include("uifc/FormField.php");
include("uifc/FormFieldBuilder.php");

//
// protected class variables
//

$GLOBALS["_TimeZone_zones"] = array("Africa/Luanda:AO:Africa",
"Africa/Ouagadougou:BF:Africa",
"Africa/Bujumbura:BI:Africa",
"Africa/Porto-Novo:BJ:Africa",
"Africa/Gaborone:BW:Africa",
"Africa/Kinshasa,Africa/Lubumbashi:CD:Africa",
"Africa/Bangui:CF:Africa",
"Africa/Brazzaville:CG:Africa",
"Africa/Abidjan:CI:Africa",
"Africa/Douala:CM:Africa",
"Atlantic/Cape_Verde:CV:Africa",
"Africa/Djibouti:DJ:Africa",
"Africa/Algiers:DZ:Africa",
"Africa/Cairo:EG:Africa",
"Africa/El_Aaiun:EH:Africa",
"Africa/Asmera:ER:Africa",
"Africa/Ceuta:ES:Africa",
"Africa/Addis_Ababa:ET:Africa",
"Africa/Libreville:GA:Africa",
"Africa/Accra:GH:Africa",
"Africa/Banjul:GM:Africa",
"Africa/Conakry:GN:Africa",
"Africa/Malabo:GQ:Africa",
"Africa/Bissau:GW:Africa",
"Africa/Nairobi:KE:Africa",
"Africa/Monrovia:LR:Africa",
"Africa/Maseru:LS:Africa",
"Africa/Tripoli:LY:Africa",
"Africa/Casablanca:MA:Africa",
"Africa/Bamako:ML:Africa",
"Africa/Nouakchott:MR:Africa",
"Africa/Maputo:MZ:Africa",
"Africa/Windhoek:NA:Africa",
"Africa/Niamey:NE:Africa",
"Africa/Lagos:NG:Africa",
"Africa/Kigali:RW:Africa",
"Africa/Khartoum:SD:Africa",
"Africa/Freetown:SL:Africa",
"Africa/Dakar:SN:Africa",
"Africa/Mogadishu:SO:Africa",
"Africa/Sao_Tome:ST:Africa",
"Africa/Mbabane:SZ:Africa",
"Africa/Ndjamena:TD:Africa",
"Africa/Lome:TG:Africa",
"Africa/Tunis:TN:Africa",
"Africa/Dar_es_Salaam:TZ:Africa",
"Africa/Kampala:UG:Africa",
"Africa/Johannesburg:ZA:Africa",
"Africa/Lusaka:ZM:Africa",
"Africa/Harare:ZW:Africa",
"Antarctica/Casey,Antarctica/DumontDUrville,Antarctica/Mawson,Antarctica/McMurdo,Antarctica/Palmer,Antarctica/South_Pole:AQ:Antarctica",
"Asia/Dubai:AE:Asia",
"Asia/Kabul:AF:Asia",
"Asia/Yerevan:AM:Asia",
"Asia/Baku:AZ:Asia",
"Asia/Dacca:BD:Asia",
"Asia/Bahrain:BH:Asia",
"Asia/Brunei:BN:Asia",
"Asia/Thimbu:BT:Asia",
"Asia/Chungking,Asia/Harbin,Asia/Hong_Kong,Asia/Kashgar,Asia/Shanghai,Asia/Urumqi:CN:Asia",
"Asia/Nicosia:CY:Asia",
"Asia/Tbilisi:GE:Asia",
"Asia/Jakarta,Asia/Jayapura,Asia/Ujung_Pandang:ID:Asia",
"Asia/Gaza,Asia/Jerusalem:IL:Asia",
"Asia/Calcutta:IN:Asia",
"Asia/Tehran:IR:Asia",
"Asia/Amman:JO:Asia",
"Asia/Tokyo:JP:Asia",
"Asia/Bishkek:KG:Asia",
"Asia/Phnom_Penh:KH:Asia",
"Asia/Pyongyang:KP:Asia",
"Asia/Seoul:KR:Asia",
"Asia/Kuwait:KW:Asia",
"Asia/Almaty,Asia/Aqtau,Asia/Aqtobe:KZ:Asia",
"Asia/Vientiane:LA:Asia",
"Asia/Beirut:LB:Asia",
"Asia/Colombo:LK:Asia",
"Asia/Rangoon:MM:Asia",
"Asia/Ulan_Bator:MN:Asia",
"Asia/Macao:MO:Asia",
"Asia/Kuala_Lumpur,Asia/Kuching:MY:Asia",
"Asia/Katmandu:NP:Asia",
"Asia/Muscat:OM:Asia",
"Asia/Manila:PH:Asia",
"Asia/Karachi:PK:Asia",
"Asia/Qatar:QA:Asia",
"Asia/Baghdad:RQ:Asia",
"Asia/Yekaterinburg,Asia/Novosibirsk,Asia/Omsk,Asia/Krasnoyarsk,Asia/Irkutsk,Asia/Yakutsk,Asia/Vladivostok,Asia/Magadan,Asia/Kamchatka,Asia/Anadyr:RU:Asia",
"Asia/Riyadh:SA:Asia",
"Asia/Singapore:SG:Asia",
"Asia/Damascus:SY:Asia",
"Asia/Bangkok:TH:Asia",
"Asia/Dushanbe:TJ:Asia",
"Asia/Ashkhabad:TM:Asia",
"Asia/Istanbul:TR:Asia",
"Asia/Taipei:TW:Asia",
"Asia/Tashkent:UZ:Asia",
"Asia/Saigon:VN:Asia",
"Asia/Aden:YE:Asia",
"Atlantic/Bermuda:BM:Atlantic",
"Atlantic/Cape_Verde:CV:Atlantic",
"Atlantic/Canary:ES:Atlantic",
"Atlantic/Stanley:FK:Atlantic",
"Atlantic/Faeroe:FO:Atlantic",
"Atlantic/Reykjavik:IS:Atlantic",
"Atlantic/Azores,Atlantic/Madeira:PT:Atlantic",
"Atlantic/St_Helena:SH:Atlantic",
"Atlantic/Jan_Mayen:SJ:Atlantic",
"Europe/Andorra:AD:Europe",
"Europe/Tirane:AL:Europe",
"Europe/Vienna:AT:Europe",
"Europe/Sarajevo:BA:Europe",
"Europe/Brussels:BE:Europe",
"Europe/Sofia:BG:Europe",
"Europe/Minsk:BY:Europe",
"Europe/Zurich:CH:Europe",
"Europe/Prague:CZ:Europe",
"Europe/Berlin:DE:Europe",
"Europe/Copenhagen:DK:Europe",
"Europe/Tallinn:EE:Europe",
"Europe/Madrid:ES:Europe",
"Europe/Helsinki:FI:Europe",
"Europe/Paris:FR:Europe",
"Europe/Athens:GR:Europe",
"Europe/Zagreb:HR:Europe",
"Europe/Budapest:HU:Europe",
"Europe/Dublin:IE:Europe",
"Atlantic/Reykjavik:IS:Europe",
"Europe/Rome:IT:Europe",
"Europe/Vaduz:LI:Europe",
"Europe/Vilnius:LT:Europe",
"Europe/Luxembourg:LU:Europe",
"Europe/Riga:LV:Europe",
"Europe/Monaco:MC:Europe",
"Europe/Chisinau:MD:Europe",
"Europe/Skopje:MK:Europe",
"Europe/Malta:MT:Europe",
"Europe/Amsterdam:NL:Europe",
"Europe/Oslo:NO:Europe",
"Europe/Warsaw:PL:Europe",
"Europe/Lisbon:PT:Europe",
"Europe/Bucharest:RO:Europe",
"Europe/Kaliningrad,Europe/Moscow,Europe/Samara:RU:Europe",
"Europe/Stockholm:SE:Europe",
"Europe/Ljubljana:SI:Europe",
"Europe/Bratislava:SK:Europe",
"Europe/San_Marino:SM:Europe",
"Europe/Kiev,Europe/Simferopol:UA:Europe",
"Europe/Belfast,Europe/London:UK:Europe",
"Europe/Vatican:VA:Europe",
"Europe/Belgrade:YU:Europe",
"Indian/Christmas:CX:Indian",
"Indian/Kerguelen:FR:Indian",
"Indian/Comoro:KM:Indian",
"Indian/Antananarivo:MG:Indian",
"Indian/Mauritius:MU:Indian",
"Indian/Maldives:MV:Indian",
"Indian/Reunion:RE:Indian",
"Indian/Mahe:SC:Indian",
"Indian/Chagos:UK:Indian",
"America/St_Johns:AG:NorthAmerica",
"America/Barbados:BB:NorthAmerica",
"America/Nassau:BS:NorthAmerica",
"America/Belize:BZ:NorthAmerica",
"Canada/Pacific,Canada/Yukon,Canada/Central,Canada/Eastern,Canada/Mountain,Canada/Newfoundland,Canada/Saskatchewan,Canada/East-Saskatchewan,Canada/Atlantic:CA:NorthAmerica",
"America/Costa_Rica:CR:NorthAmerica",
"America/Havana:CU:NorthAmerica",
"America/Dominica:DM:NorthAmerica",
"America/Santo_Domingo:DO:NorthAmerica",
"America/Grenada:GD:NorthAmerica",
"America/Godthab:GL:NorthAmerica",
"America/Guatemala:GT:NorthAmerica",
"America/Tegucigalpa:HN:NorthAmerica",
"America/Port-au-Prince:HT:NorthAmerica",
"America/Jamaica:JM:NorthAmerica",
"America/St_Kitts:KN:NorthAmerica",
"America/St_Lucia:LC:NorthAmerica",
"Mexico/BajaNorte,Mexico/BajaSur,Mexico/General:MX:NorthAmerica",
"America/Managua:NI:NorthAmerica",
"America/Panama:PA:NorthAmerica",
"America/Miquelon:PM:NorthAmerica",
"America/Puerto_Rico:PR:NorthAmerica",
"America/El_Salvador:SV:NorthAmerica",
"America/Grand_Turk:TC:NorthAmerica",
"America/Port_of_Spain:TT:NorthAmerica",
"US/Samoa,US/Aleutian,US/Hawaii,US/Alaska,US/Pacific,US/Arizona,Navajo,US/Mountain,US/Central,US/East-Indiana,US/Eastern:US:NorthAmerica",
"America/St_Vincent:VC:NorthAmerica",
"Pacific/Pago_Pago:AS:Oceania",
"Australia/Perth,Australia/Darwin,Australia/Adelaide,Australia/Brisbane,Australia/Sydney,Australia/Broken_Hill,Australia/Queensland,Australia/Lindeman,Australia/Lord_Howe,Australia/Canberra,Australia/Melbourne,Australia/Hobart:AU:Oceania",
"Pacific/Rarotonga:CK:Oceania",
"Pacific/Easter:CL:Oceania",
"Pacific/Galapagos:EC:Oceania",
"Pacific/Apia:EH:Oceania",
"Pacific/Fiji:FJ:Oceania",
"Pacific/Kosrae,Pacific/Ponape,Pacific/Truk,Pacific/Yap:FM:Oceania",
"Pacific/Guam:GU:Oceania",
"Pacific/Enderbury,Pacific/Kiritimati,Pacific/Tarawa:KI:Oceania",
"Pacific/Kwajalein,Pacific/Majuro:MH:Oceania",
"Pacific/Saipan:MP:Oceania",
"Pacific/Noumea:NC:Oceania",
"Pacific/Norfolk:NF:Oceania",
"Pacific/Nauru:NR:Oceania",
"Pacific/Niue:NU:Oceania",
"Pacific/Auckland,Pacific/Chatham:NZ:Oceania",
"Pacific/Gambier,Pacific/Marquesas,Pacific/Tahiti:PF:Oceania",
"Pacific/Port_Moresby:PG:Oceania",
"Pacific/Pitcairn:PN:Oceania",
"Pacific/Palau:PW:Oceania",
"Pacific/Guadalcanal:SB:Oceania",
"Pacific/Fakaofo:TK:Oceania",
"Pacific/Tongatapu:TO:Oceania",
"Pacific/Funafuti:TV:Oceania",
"Pacific/Johnston,Pacific/Midway,Pacific/Wake:US:Oceania",
"Pacific/Efate:VU:Oceania",
"Pacific/Wallis:WF:Oceania",
"America/Buenos_Aires:AR:SouthAmerica",
"America/La_Paz:BO:SouthAmerica",
"Brazil/Acre,Brazil/DeNoronha,Brazil/East,Brazil/West:BR:SouthAmerica",
"Chile/Continental,Chile/EasterIsland:CH:SouthAmerica",
"America/Bogota:CO:SouthAmerica",
"America/Guayaquil:EC:SouthAmerica",
"America/Cayenne:GF:SouthAmerica",
"America/Guyana:GY:SouthAmerica",
"America/Lima:PE:SouthAmerica",
"America/Asuncion:PY:SouthAmerica",
"America/Paramaribo:SR:SouthAmerica",
"America/Montevideo:UY:SouthAmerica",
"America/Caracas:VE:SouthAmerica",
"Etc/GMT-14,Etc/GMT-13,Etc/GMT-12,Etc/GMT-11,Etc/GMT-10,Etc/GMT-9,Etc/GMT-8,Etc/GMT-7,Etc/GMT-6,Etc/GMT-5,Etc/GMT-4,Etc/GMT-3,Etc/GMT-2,Etc/GMT-1,Etc/UTC,Etc/GMT+1,Etc/GMT+2,Etc/GMT+3,Etc/GMT+4,Etc/GMT+5,Etc/GMT+6,Etc/GMT+7,Etc/GMT+8,Etc/GMT+9,Etc/GMT+10,Etc/GMT+11,Etc/GMT+12:UTC:UTC"
);

class TimeZone extends FormField {
  //
  // public methods
  //

  function toHtml($style = "") {
    $page =& $this->getPage();
    $form =& $page->getForm();
    $formId = $form->getId();
    $id = $this->getId();
    $i18n =& $page->getI18n();
    $value = $this->getValue();

    // make sure there is a default value
    $value = ($value == "") ? "US/Pacific" : $value;

    $selectedZone = $value;

    $regionId  = "_".$id."_regionlist";
    $countryId  = "_".$id."_countrylist";
    $zoneId  = "_".$id."_zonelist";

    $initLabels = array();
    $initValues = array();
    for($i = 0; $i < 50; $i++) {
      $initLabels[] = $i;
      $initValues[] = $i;
    }

    $timeZoneString = $this->_timeZone_String();
    $hiddenId = "_".$id."_element";   
 
    $builder = new FormFieldBuilder();
    $regionHtml = $builder->makeSelectField($regionId, $this->getAccess(), 1, 40, false, $formId, "top.code.TimeZone_newRegion(document.$formId.$hiddenId);", $initLabels, $initValues);
    $countryHtml = $builder->makeSelectField($countryId, $this->getAccess(), 1, 40, false, $formId, "top.code.TimeZone_newCountry(document.$formId.$hiddenId);", $initLabels, $initValues);
    $zoneHtml = $builder->makeSelectField($id, $this->getAccess(), 1, 40, false, $formId, "", $initLabels, $initValues);

    $systemTimeZone = $builder->makeHiddenField($hiddenId);
    $systemTimeZone .= $builder->makeHiddenField("_".$id."_initRegion");
    $systemTimeZone .= $builder->makeHiddenField("_".$id."_initCountry");
    $systemTimeZone .= $builder->makeHiddenField("_".$id."_initZone");
    $javascript = $builder->makeJavaScript($this, "", "top.code.TimeZone_submitHandler");

    $javascript .= "<SCRIPT LANGUAGE=\"javascript\">
    var element = document.$formId.$hiddenId;
    element.regionElement = document.$formId.$regionId;
    element.countryElement = document.$formId.$countryId;
    element.zoneElement = document.$formId.$id;
    element.regionElement.element = element;
    element.countryElement.element = element;
    element.zoneElement.element = element;
    element.initRegion = document.$formId._".$id."_initRegion;
    element.initCountry = document.$formId._".$id."_initCountry;
    element.initZone = document.$formId._".$id."_initZone;
    var ".$id."_regn = 0;
    var ".$id."_cntry = 0;
    var ".$id."_tz = 0;
    element.regn = ".$id."_regn;
    element.cntry = ".$id."_cntry;
    element.tz = ".$id."_tz;
    element.timezones = timezones_x;
    top.code.TimeZone_init(element,\"$value\");
     </SCRIPT>"   ;
 
   return $timeZoneString.$regionHtml."<IMG BORDER='0' SRC='/libImage/spaceHolder.gif' height='4'><br>".$countryHtml."<IMG BORDER='0' SRC='/libImage/spaceHolder.gif' height='4'><br>".$zoneHtml.$systemTimeZone.$javascript;
  }

  function _timeZone_String(){
    $page =& $this->getPage();
    $i18n =& $page->getI18n();
 $jsZoneArray = "[";
 $regionArray= array("Africa","Antarctica","Asia","Atlantic","Europe","Indian","NorthAmerica","Oceania","SouthAmerica", "UTC");
 for($k=0;$k<count($regionArray);$k++){ 
  $nameRegion = $i18n->getJs("$regionArray[$k]", "palette");
  $e=",";if($k==(count($regionArray) - 1)){$e="";}
  $jsZoneArray .= "[ \"$regionArray[$k]\",\"$nameRegion\",[";
  for($i=0;$i<count($GLOBALS["_TimeZone_zones"]);$i++){ 
   $breakZone=array(); $breakLocale=array();
   $breakZone = explode(":",$GLOBALS["_TimeZone_zones"][$i]); 
   if($breakZone[2] == $regionArray[$k]){
    $breakLocale = explode(",",$breakZone[0]); 
    $nameCountry = $i18n->getJs("$breakZone[2]/$breakZone[1]", "palette");
    $d=",";if($i < (count($GLOBALS["_TimeZone_zones"])-1)){ 
     $nextBreakZone = explode(":",$GLOBALS["_TimeZone_zones"][$i + 1]); 
     if($nextBreakZone[2] != $regionArray[$k]){$d="";}}else{$d="";}
//    $d=",";if($j==(count($breakLocale) - 1)){$d="";}
    $jsZoneArray .= "[ \"$breakZone[1]\",\"$nameCountry\",[";
    for($j=0;$j<count($breakLocale);$j++){
     $nameLocale = $i18n->getJs($breakLocale[$j], "palette"); $c=",";if($j==(count($breakLocale) - 1)){$c="";}
     $jsZoneArray .= "[ \"$breakLocale[$j]\",\"$nameLocale\" ]$c";    
    }
    $jsZoneArray .= "]]$d";
   }
  }
  $jsZoneArray .= "]]$e";
 }
 $jsZoneArray .= "]";  
 $timeZoneString = "<script>timezones_x =".$jsZoneArray."</script>";
 return $timeZoneString;
 }
}

/*
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>

