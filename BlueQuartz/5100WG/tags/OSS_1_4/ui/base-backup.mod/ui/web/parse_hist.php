<?php
// Author: Tim Hockin
// Copyright 2000 Cobalt Networks.  All rights reserved

$_el_list;
$_cur_backup = array();
$_cur_idx = -1;

function startElement($parser, $name)
{
	global $_cur_idx;
	
	$_cur_idx = $name;
}

function getData($parser, $data)
{
	global $_cur_backup; 
	global $_cur_idx;

	$e = trim($data);

	if ($_cur_idx != -1 && !empty($e)) {
		if ($_cur_backup[$_cur_idx]) {
			$_cur_backup[$_cur_idx] .= $data;
		} else {
			$_cur_backup[$_cur_idx] = $data;
		}
	}
}

function endElement($parser, $name)
{
	global $_cur_backup; 
	global $_cur_idx;
	global $_el_list;

	if ($name == "BACKUP") {
		$_el_list[] = $_cur_backup;
		$_cur_backup = array();
    	}	
	$_cur_idx = -1;
}

//return an array of hashes
function parse_hist($file)
{
	global $_el_list;

	$_el_list = array();

	$xml = xml_parser_create();
	xml_set_element_handler($xml, "startElement", "endElement");
	xml_set_character_data_handler($xml, "getData");

	if ((file_exists($file)) && ($fp = fopen($file, "r"))) {
		while ($data = fread($fp, 4096)) {
			if (!xml_parse($xml, $data, feof($fp))) {
			    die(sprintf("XML error: %s at line %d",
				xml_error_string(xml_get_error_code($xml)),
				xml_get_current_line_number($xml)));
			}
		}
		fclose($fp);
	}
	xml_parser_free($xml);

	return $_el_list;
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

