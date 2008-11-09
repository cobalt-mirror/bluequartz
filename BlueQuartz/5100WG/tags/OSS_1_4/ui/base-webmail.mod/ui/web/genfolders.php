<?
include("./imapconnect.inc"); 
include("ServerScriptHelper.php");
global $serverScriptHelper;
global $i18n;

imapLock();
connectToImap();
$mailboxes = listMailboxes($connection);
imapUnlock();

?>
<SCRIPT language="Javascript">
function delItems(node) {
	var i;
	var children = node.getItems();
	for (i=0;i<children.length;i++) {
		delItems(children[i]);
		node.delItem(children[i].getId());
	}
}

function onSiteMapLoad(sitemap) {
<?
  global $mailboxDisplayNames;
  for ($key=0;$key<count($mailboxes);$key++) {
    $val = $mailboxes[$key];
    $displayName = $mailboxDisplayNames{$val};
    print "\tsitemap.webmail_".$key." = new top.code.mItem_Item(\"webmail_".$key."\", \"".  escapeForURL($displayName, $localePreference) ." \", \"" . escapeForURL($i18n->get("openThisFolder"), $localePreference) . "\", \"\", \"/base/webmail/inbox.php?mailbox=".rawurlencode(escapeForURL($val, $localePreference))."\", false, true);\n";
    print "\tsitemap.webmail_mailboxes.addItem(sitemap.webmail_".$key.");\n";
  }
  // get all the mailling lists this user is subscribed to...

 $product = $serverScriptHelper->getProductCode();
 $isMonterey = ereg("35[0-9][0-9]R", $product);
    
 if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
   	$cce = $serverScriptHelper->getCceClient();
	$listAr = $cce->find("MailList");
	while (list($key, $val) = each ($listAr)) {
		$obj = $cce->get($val);
		$obj_arch = $cce->get($val, "Archive");
		$obfName = ereg_replace( "/", "_", $obj["name"]);
		$isInGroup = false;
		if ($obj["group"]) {
			$grp_obj = $cce->getObject("Workgroup", array("name" => $obj["group"]), "");
			if (!(strpos($grp_obj["members"], "&" . $IMAP_username . "&")===false)) {
				$isInGroup = true;
			}
		}
		if (((!(strpos($obj["local_recips"], "&".$IMAP_username."&")===false))||($isInGroup ))&&$obj_arch["enabled"]&&file_exists("/home/mhonarc/data/$obfName/main.php")) {
			if (!$hasMaillists) {
				$hasMaillists = true;
				print "if (!sitemap.webmail_lists) {\n";
				print "\tsitemap.webmail_lists = new top.code.mItem_Item(\"webmail_lists\", \"". $i18n->get("maillists") . "\",\"". $i18n->get("maillistsDescription")."\",\"\",\"\",false,true);\n";
				print "\tsitemap.webmail_messages.addItem(sitemap.webmail_lists);\n";
				print "}\n";
			}
			print "\tsitemap.webmail_list_".$key." = new top.code.mItem_Item(\"webmail_list_".$key."\", \"".escapeForURL($obfName, $localePreference)."\",\"". $i18n->get("openThisMaillistFolder"). "\", \"\", \"/base/maillist/archives/".$obfName."/main.php\", false, true);\n";
			print "\tsitemap.webmail_lists.addItem(sitemap.webmail_list_".$key.");\n";
		}
		
	}
 } 
print "}\n";
	if ($refresh) {
		print "delItems(top.siteMap['webmail_mailboxes']);\n";	
		print "if(top.siteMap['webmail_lists'] != null) {\n";
		print "\tdelItems(top.siteMap['webmail_lists']);\n";
		print "}\n";
		print "onSiteMapLoad(top.siteMap);\n";
		print "top.code.cList_setRoot(top.siteMap['webmail_messages']);\n";
		print "top.code.cList_repaint(true);\n";
		print "top.code.tab_repaint(true);\n";
	}
	$serverScriptHelper->destructor();
?>
</SCRIPT>
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

