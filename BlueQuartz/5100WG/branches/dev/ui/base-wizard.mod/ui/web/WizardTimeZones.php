<?php

// This data organizes time zones by country and region


function WizGetTimeZoneControls(&$html, &$javascript)
{
	$WizTimeZonesByCountry = array(
		"Etc/GMT-14,Etc/GMT-13,Etc/GMT-12,Etc/GMT-11,Etc/GMT-10,Etc/GMT-9,Etc/GMT-8,Etc/GMT-7,Etc/GMT-6,Etc/GMT-5,Etc/GMT-4,Etc/GMT-3,Etc/GMT-2,Etc/GMT-1,Etc/UTC,Etc/GMT+1,Etc/GMT+2,Etc/GMT+3,Etc/GMT+4,Etc/GMT+5,Etc/GMT+6,Etc/GMT+7,Etc/GMT+8,Etc/GMT+9,Etc/GMT+10,Etc/GMT+11,Etc/GMT+12:UTC:UTC",
		"Africa/Luanda:AO:Africa",
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
	);

	$WizTimeZoneDefaults = array(
		"en:US/Pacific",
		"fr:Europe/Paris",
		"es:Europe/Madrid",
		"de:Europe/Berlin",
		"ja:Asia/Tokyo",
		"zh_TW:Asia/Taipei",
		"zh_CN:Asia/Shanghai",
  );


	$serverScriptHelper = new ServerScriptHelper();
	$i18n = $serverScriptHelper->getI18n("palette");

	// Set the default time zone to one appropriate for the user's language
	$locales = $i18n->getLocales();

	for ($wtzd = 0; $wtzd < count($WizTimeZoneDefaults); $wtzd++)
	{
		$parts = explode(":", $WizTimeZoneDefaults[$wtzd]);

		if ($parts[0] == $locales[0])
		{
			$defaultTimeZone = $parts[1];
		}
	}

	WizDebug("WizGetTimeZoneHTML: defaultTimeZone $defaultTimeZone\n");

	$c = count($WizTimeZonesByCountry);
	WizDebug("WizGetTimeZoneHTML: count(WizTimeZonesByCountry) $c\n");

	$html = "<SELECT name='WizTimeZoneRegion' size='1' ";
	$html .= "onChange='WizChangeRegion(this.options[this.selectedIndex].value);'";
	$html .= ">";

	$lastRegion = "";
	$setDefaultJS = "";
	for ($tzbc = 0; $tzbc < count($WizTimeZonesByCountry); $tzbc++)
	{
		$parts = explode(":", $WizTimeZonesByCountry[$tzbc]);

		$region = $parts[2];
		$country = $parts[1];

		$selected = (strstr($parts[0], $defaultTimeZone) != "") ? "SELECTED" : "";

		if ($selected != "" && setDefaultJS != "" )
		{
			$setDefaultJS .= "WizChangeRegion('$region');\n";
			$setDefaultJS .= "WizSetDefault('WizTimeZoneRegion', '$region');\n";
			$setDefaultJS .= "WizChangeCountry('$country');\n";
			$setDefaultJS .= "WizSetDefault('WizTimeZoneCountry', '$country');\n";
			$setDefaultJS .= "WizSetDefault('WizTimeZone', '$defaultTimeZone');\n";
		}

		$countryCode = $parts[1];
		$countries[$region] .= ($countries[$region] != "") ? ", " : "";
		$countries[$region] .= "'" . str_replace( "'", "\\'", $i18n->get($parts[2] . "/" . $countryCode)) . "', '" . $countryCode . "'";

		$zones = explode(",", $parts[0]);

		for ($tz = 0; $tz < count($zones); $tz++)
		{
			$timeZones[$countryCode] .= ($timeZones[$countryCode] != "") ? ", " : "";
			$timeZones[$countryCode] .= "'" . str_replace("'", "\\'", $i18n->get($zones[$tz])) . "', '" . $zones[$tz] . "'";
		}

		if ($region != $lastRegion)
		{
			$lastRegion = $region;
			$html .= "<OPTION value='$region'>";
			$html .= $i18n->get($region);
			$html .= "</OPTION>\n";
		}
	}

	$countryKeys = array_keys($countries);
	for ( $c = 0; $c < count($countryKeys); $c++)
	{
		$javascript .= "var CountriesIn$countryKeys[$c] = new Array(";
		$javascript .= $countries[$countryKeys[$c]];
		$javascript .= ");\n";
	}
	$timeZoneKeys = array_keys($timeZones);
	for ( $c = 0; $c < count($timeZoneKeys); $c++)
	{
		$javascript .= "var TimeZonesIn$timeZoneKeys[$c] = new Array(";
		$javascript .= $timeZones[$timeZoneKeys[$c]];
		$javascript .= ");\n";
	}

	$html .= "</SELECT><BR>";

	$html .= "<SELECT name='WizTimeZoneCountry' size='1' ";
	$html .= "onChange='WizChangeCountry(this.options[this.selectedIndex].value);'";
	$html .= ">";

	$html .= "<OPTION>___________________________</OPTION>";
	$html .= "</SELECT>&nbsp;";

	$html .= "<SELECT name='WizTimeZone' size='1'>";

	$html .= "<OPTION>___________</OPTION>";
	$html .= "</SELECT>";

	$javascript .= "function WizSetDefaults()\n";
	$javascript .= "{\n";
	$javascript .= $setDefaultJS;
	$javascript .= "}";
}

function WizGetTimeZoneJavaScript()
{
	$js = <<<END_OF_JS

		function WizSetDefault(control, value)
		{
			var controlObject = eval('document.WizardForm.' + control);

			for (r = 0; r < controlObject.length; r++)
			{
				if (controlObject.options[r].value == value)
				{
					// alert('Set def value of ' + control +' to ' +value);
					controlObject.selectedIndex = r;
					break;
				}
			}
		}

		function WizChangeRegion(regionCode)
		{
			var controlObject = document.WizardForm.WizTimeZoneCountry;
			var newOptionValues = eval('CountriesIn' + (regionCode ? regionCode : 'NorthAmerica'));
			var count = 0;

			for (i = 0; i < newOptionValues.length; i += 2)
			{
				newOption = new Option(newOptionValues[i], newOptionValues[i+1]);

				controlObject.options[count++] = newOption;
			}

			for (i = (controlObject.length-1); i >= count; i--)
			{
				controlObject.options[i] = null;
			}
			controlObject.selectedIndex = 0;
			WizChangeCountry(controlObject.options[0].value);
		}

		function WizChangeCountry(countryCode)
		{
			var controlObject = document.WizardForm.WizTimeZone;
			var newOptionValues = eval('TimeZonesIn' + (countryCode ? countryCode : 'US'));
			var count = 0;

			for (i = 0; i < newOptionValues.length; i += 2)
			{
				newOption = new Option(newOptionValues[i], newOptionValues[i+1]);

					// alert('Set value ' + newOption.value);
				controlObject.options[count++] = newOption;
			}

			for (i = (controlObject.length-1); i >= count; i--)
			{
				controlObject.options[i] = null;
			}
			controlObject.selectedIndex = 0;
		}

END_OF_JS;

	return $js;
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

