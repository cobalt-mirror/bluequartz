<?php
// $Id: SimpleList.php 3 2003-07-17 15:19:15Z will $
// 
// A simpler API for quickly building list tables.

/* example usage:
  include("SimpleList.php");

  list_init("test", "test", array( "foo", "bar", "bum" ) );

  list_add( array("a", "b", "c"), "", "");
  list_add( array("ed", "it", "me"), "editme.cgi", "");
  list_add( array("de", "lete", "me"), "editmetoo.cgi", "delme.cgi");

  list_render();
*/

include ("ServerScriptHelper.php");

// list_init
function list_init ( $domain, $tag_prefix, $headers )
{
  global $helper, $cce, $factory, $i18n, $page, $scrollList;
  global $scrollList_post;
  global $global_tag_prefix;
  $scrollList_post = "";
  $global_tag_prefix = $tag_prefix;

  $helper = new ServerScriptHelper() or die ("no ServerScriptHelper");
  $cce = $helper->getCceClient() or die ("no CCE client");
  $my_url = getenv("SCRIPT_URL");
  if (!$my_url) { $my_url = getenv("SCRIPT_NAME"); }
  $factory = $helper->getHtmlComponentFactory( $domain, $my_url );
  $i18n = $helper->getI18n($domain) or die ("no I18N");
  
  $page = $factory->getPage();

  $sortby = array();
  $align = array();
  for ($i = 0; $i < count($headers); $i++) {
    array_push($sortby, $i);
    array_push($align, "center");
  }
  array_push($align,"center");
  
  array_push ($headers, $tag_prefix . "_action_header");
  
  $scrollList = $factory->getScrollList( 
    $tag_prefix . "-list-title",  // CSS can't handle underscores (argh!)
    $headers,
    $sortby );

  $scrollList->addButton($factory->getAddButton($my_url . "?ADD=1"));

  $scrollList->setAlignments($align);
  
  
  print $page->toHeaderHtml();
}

// list_add
//    $data -- array of data to display in the table
//    $actions -- array of action buttons.  currently only two
//    	actions are supported:
//    	  edit -- the URL of the EDIT button for this item, or undef
//    	      	  if not editabled.
//    	  remove -- the URL of the REMOVE button for this item, or
//    	      	  undef if not removable.
function list_add ( $data, $actions )
{
  global $i18n, $scrollList, $factory, $global_tag_prefix;

  $entries = array ();
  
  for ($i = 0; $i < count($data); $i++) {
    array_push( $entries, 
// this is now broken:      $factory->getTextField("", $data[$i], "r") );
// this still works, at least:  (silly, no?)
      $factory->getFullName("", $data[$i], "r") );
  }

  $buttons = array ();
  
  if ($actions["edit"]) {
    array_push ($buttons, 
      $factory->getModifyButton( $actions["edit"] ));
  }
    
  if ($actions["remove"]) {
    array_push($buttons,
      $factory->getRemoveButton( $actions["remove"] ));
  }
  
  array_push ($entries, $factory->getCompositeFormField($buttons));

  $scrollList->addEntry($entries); 
}

function list_append($str)
{
	global $scrollList_post;
	$scrollList_post .= $str;
}

// renders the whole page.
function list_render()
{
  global $scrollList, $page, $scrollList_post;
  print $scrollList->toHtml();
  print $scrollList_post;
  print $page->toFooterHtml();
}

// end

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

