<?
include ("ServerScriptHelper.php");
include ("./addressbookPrivateCommon.php");
include ("uifc/PagedBlock.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-addressbook");
$page = $factory->getPage();
$i18n = $page->getI18n();
$block = $factory->getPagedBlock("", array("addressbookprivate", "addressbookcommon"));

$addAddressButton = $factory->getButton("javascript: EmailAddressList_AddAddress()", "addressesSelectButton");
$cancelButton = $factory->getCancelButton("javascript: top.close()");
print $page->toHeaderHtml();
print "<SCRIPT language=\"javascript\">";


$sysoids = $cceClient->find("System");
$system = $cceClient->get($sysoids[0]);
$hostname = ($system["hostname"] ? $system["hostname"] . "." : "" ) . $system["domainname"];

//do the addressbookcommon entries...
if ($block->getSelectedId() == "addressbookcommon") {	
	$scrollList = $factory->getScrollList(
					"", 
					array(
						"addressesSelect",	
						"addressesFullName", 
						"addressesEmail"
					), array(1,2));
	
//	if($came_from == "private")
//		$scrollList->setPageIndex(0);

	$scrollList->setSortEnabled(false);

	$pageLength = $scrollList->getLength();
	
	$start = $scrollList->getPageIndex() * $pageLength;

	$sortKeys = array("1" => "fullName", "2" => "name");
	$sortKey = $sortKeys[$scrollList->getSortedIndex()];

	$oids = $cceClient->findSorted("User", $sortKey);
	
	if ($scrollList->getSortOrder() == "descending")
		$oids = array_reverse($oids);

	$scrollList->setEntryNum(count($oids));

	for($i = $start; $i < count($oids) && $i < $start + $pageLength; $i++) { 
  		$addressbookUser =  $cceClient->get($oids[$i]);
		$safeFullName = $i18n->interpolateJs("[[VAR.string]]", 
			array("string" => $addressbookUser["fullName"]));

  		$addressbookUser["email"] = $addressbookUser["name"] . "@" . $hostname; 
  		$fullname = $factory->getTextField("", $addressbookUser["fullName"],"r");
  		$email = $factory->getTextField("", $addressbookUser["email"],"r");

		// construct full email address
		$fullEmail = "\"" . $safeFullName . "\" <" . $addressbookUser["email"] . ">";

		// find value ID
		$valueId = "valueC".$oids[$i];

		// set to selected already if in list
  		$boolean = $factory->getBoolean($valueId, in_array($fullEmail, explode(",", $selected)));

  		print "top.$valueId = new Object();";
  		print "top.$valueId.FullEmail = '$fullEmail';\n";
  		print "top.$valueId.fullName = \"" . $safeFullName . "\";";
  		print "top.$valueId.email = \"" . trim($addressbookUser["email"]) . "\";";

  		$scrollList->addEntry(array($boolean,$fullname,$email), "", false, $i);
	}
} else {
	$scrollList2 = $factory->getScrollList("", 
						array(
							"addressesSelect",
							"addressesFullName", 
							"addressesEmail"), array(1,2));

	if($came_from == "common")
		$scrollList2->setPageIndex(0);
	
	$addys = addressBookGetall($serverScriptHelper);
	
	$scrollList2->setEntryNum(count($addys));

	/* 
	FIXME:  No one has really tested what will happen, if you have a large
		number of private address book entries.  For most users, this
		will not be an issue, but you know someone is going to try 
		putting a few hundred entries in their private ab.  Depending
		on how much overhead occurs with CCE, they could end up with
		timeout issues.
	*/
	for($i = 0; $i < count($addys); $i++) { 
  		$addressbookEntry = $addys[$i];
		
		$safeFullName = $i18n->interpolateJs("[[VAR.string]]", 
			array("string" => $addressbookEntry[$FULLNAME]));

  		$fullname = $factory->getTextField("", $addressbookEntry[$FULLNAME],"r");
  		$email = $factory->getTextField("", $addressbookEntry[$EMAIL],"r");

		// make full email address		
		$fullEmail = "\"" . $safeFullName . "\" <" . $addressbookEntry[$EMAIL]. ">";

		// make value ID
		$valueId = "valueP$i";

		// mark as selected if already selected
  		$boolean = $factory->getBoolean($valueId, in_array($fullEmail, explode(",", $selected)));

  		//print "top.$valueId = \"".$addressbookEntry[$FULLNAME] . " <" . $addressbookEntry[$EMAIL]. ">\";\n";
  		print "top.$valueId = new Object();";
 		print "top.$valueId.FullEmail = '$fullEmail';\n";
  		print "top.$valueId.fullName = \"" . $safeFullName . "\";";
  		print "top.$valueId.email = \"" . trim($addressbookEntry[$EMAIL]) . "\";";
  		$scrollList2->addEntry(array($boolean,$fullname,$email));
	}
}
?>

function all_valid_emails() {
	var emptyEmails = "";
	var emptyEmailsCount = 0;
	// find any errors
	for (var i=0;i<document.form.elements.length;i++) {
		if (document.form.elements[i].name.substr(0,5)=="value") {
			if (document.form.elements[i].value) {
				if (unescape(top[document.form.elements[i].name].email) == "") {
					if (emptyEmailsCount) {
						emptyEmails = emptyEmails.concat(", ");	
					}
					emptyEmails = emptyEmails.concat(top[document.form.elements[i].name].fullName);
					emptyEmailsCount++;
			
				}
			}
		}
	}
	// show any errors
	if (emptyEmailsCount) { 
		var singleEmailMessage = "<?php print $i18n->get("singleEmailEmptyMessage") ?>";
		var pluralEmailMessage = "<?php print $i18n->get("pluralEmailEmptyMessage") ?>";
		var message = emptyEmailsCount==1 ? singleEmailMessage : pluralEmailMessage;
		var match = emptyEmailsCount==1 ? "[[VAR.name]]" : "[[VAR.names]]";
		message = top.code.string_substitute(message, match, emptyEmails);
		top.alert(message);

		return false;
	}

	return true;
}

// let's do what the user is expecting and save selected contacts if the user
// goes to another page in either address book or switches address books
function store_on_submit() {
	var selected = "";

	if (document.form.selected.value)
		selected = document.form.selected.value;

	// check for empties
	if (!all_valid_emails())
		return false;

	// get elements
	var selectedElements = selected.split(",");

	// go through form elements and add them to the selected list if checked
	// mostly copied from mwaychison EmailAddressList_AddAddress
	for (var i = 0; i < document.form.elements.length; i++)
		if (document.form.elements[i].name.substr(0,5) == "value") {
			// find the index in selectedElements
			var selectedIndex = -1;
			for(var j = 0; j < selectedElements.length; j++)
				if(selectedElements[j] == top[document.form.elements[i].name].FullEmail) {
					selectedIndex = j;
					break;
				}

			// only add if not already in list
			if (document.form.elements[i].value && selectedIndex == -1)
				selectedElements[selectedElements.length] = top[document.form.elements[i].name].FullEmail;
			else if (document.form.elements[i].value == "" && selectedIndex != -1) {
				// email was unselected remove it
				var newElements = new Array();
				if(selectedIndex > 0)
					newElements = newElements.concat(selectedElements.slice(0, selectedIndex));
				if(selectedIndex+1 < selectedElements.length)
					newElements = newElements.concat(selectedElements.slice(selectedIndex+1));
				selectedElements = newElements;
			}
		}

	document.form.selected.value = selectedElements.join(",");

	return true;
}

// override the default onsubmit function
document.form.onsubmit = store_on_submit;

function EmailAddressList_AddAddress() {

	if (document.form.onsubmit()) {
		selected = document.form.selected.value.split(',');	
	
		// add selected email addresses
		for (var i = 0; i < selected.length; i++)
			if (selected[i])
				top.code.EmailAddressList_AddAddress(
							top.opener.Refer,
							selected[i]);

		top.close();
	}
	// don't return anything if onsubmit fails, otherwise the page gets
	// blanked out
}
</SCRIPT>
<?php
print "<BR><table border=0 cellpadding=0 cellspacing=0><tr><td>". $addAddressButton->toHtml() . "</td><td>&nbsp;</td><td>" .  $cancelButton->toHtml() . "</td></tr></table><br>";
print $block->toHtml();

$previous_selects = $factory->getTextField("selected", $selected, "");

print $previous_selects->toHtml();

if ($block->getSelectedId() == "addressbookprivate") {
	$from = $factory->getTextField("came_from", "private", "");
	print $scrollList2->toHtml();
} else {
	$from = $factory->getTextField("came_from", "common", "");
	print $scrollList->toHtml();
}

print $from->toHtml();

print $page->toFooterHtml();

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

