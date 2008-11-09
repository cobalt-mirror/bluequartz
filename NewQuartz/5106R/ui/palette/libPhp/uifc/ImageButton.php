<?php
// Author: Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: ImageButton.php 1050 2008-01-23 11:45:43Z mstauber $

global $isImageButtonDefined;
if($isImageButtonDefined)
	return;
$isImageButtonDefined = true;

include_once("uifc/Button.php");
include_once("uifc/ImageLabel.php");

class ImageButton extends Button
{
	//
	// public methods
	//

	function getDefaultStyle($stylist)
	{
		return $stylist->getStyle("ImageButton");
	}

	// description: constructor
	// param: page: the Page object this object lives in
	// param: action: the string used within HREF attribute of the A tag
	function ImageButton($page, $action, $image, $lbl, $desc)
	{
		$i18n = $page->getI18n();
		$stylist = $page->getStylist();

		$label = $i18n->get($lbl);
		$description = $i18n->get($desc);

		$this->Button($page, $action, 
			new ImageLabel($page, $image, $label, $description), 
			new ImageLabel($page, $image, $label, $description));
	}

	function toHtml($style = "")
	{
		$page =& $this->getPage();

	  	if($style == null || $style->getPropertyNumber() == 0)
	    		$style = $this->getDefaultStyle($page->getStylist());

		$isDisabled = $this->isDisabled();

	  	// find the right style target
	  	$target = $isDisabled ? "disabled" : "normal";

	  	// find out style properties
	  	$styleStr = $style->toBackgroundStyle($target) .
			$style->toTextStyle($target);

		if ($isDisabled) {
			$label =& $this->getLabelDisabled();
			$labelHtml = $label->toHtml();

	    		return "
<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\">
<TR>
<TD NOWRAP STYLE=\"$styleStr\">$labelHtml</TD>
</TR>
</TABLE>
";
		} // end if ($isDisabled)
		
		// button not disabled
		$action = $this->getAction();
		$label =& $this->getLabel();
		$description = $label->getDescription(); 
		global $_BUTTON_ID; 
		$_BUTTON_ID++;
		$id = $_BUTTON_ID;

		// log activity if necessary
		$system = new System();
		if ($system->getConfig("logPath") != "") {
			$labelText = $label->getLabel(); 
			$logClick = "top.code.uiLog_log('click', 'Button', '$labelText');";
			$logMouseOver = "top.code.uiLog_log('mouseOver', 'Button', '$labelText');";
			$logMouseOut = "top.code.uiLog_log('mouseOut', 'Button', '$labelText');";
    		}  // end if ($system->getConfig("logPath")...

		$click = "$logClick return true;";
		$mouseOver = "$logMouseOver return true;";
		$mouseOut = "$logMouseOver return true;";
		if ($description) {
			$click = "$logClick return top.code.info_click();";

			// Javascript definitions were bypassing toHtml..  
			$i18n =& $page->getI18n();
			$langs = $i18n->getLocales();

			/*
			 * The HTML_ENTITIES translation table is only
			 * valid for the ISO-8859-1 character set. 
			 * Japanese is the only supported language
			 * which does not use the ISO-8859-1 charset,
			 * so we do a special case for that.
			 */
			$encoding = $i18n->getProperty("encoding", "palette");
			if ($encoding == "none" ||
			    !strpos($encoding, "8859-1") === false ) {
				$specialChars = array_merge(get_html_translation_table(HTML_SPECIALCHARS), get_html_translation_table(HTML_ENTITIES));
				$escaped_description = strtr(strtr($description, array_flip($specialChars)), $specialChars);
			} else {
				$description = htmlspecialchars($description);
			}

			/*
			 * using interpolateJs this way is not very
			 * clean, but this works for now
			 */
			$escaped_description = $i18n->interpolateJs("[[VAR.string]]", array("string" => $description));

			$javascript .= "document._button_description_$id = '$escaped_description'";
			$mouseOver = "$logMouseOver return top.code.info_mouseOver(document._button_description_$id)";
			$mouseOut = "$logMouseOut return top.code.info_mouseOut();";

			/*
			 * clear up description temporarily because
			 * the rollover help of the
			 * label prevents button click-through
			 */
			$label->setDescription("");
		} // end if ($description)

		$labelHtml = $label->toHtml();

		// restore description if necessary
		if($description)
			$label->setDescription($description);

		$targetFrame = $this->targetFrame;
		if ($targetFrame)
			$targetString = " TARGET=\"$targetFrame\" ";

		$linkHtml = "<A HREF=\"$action\" onClick=\"$click\" $targetString onMouseOver=\"$mouseOver\" onMouseOut=\"$mouseOut\">$labelHtml</A>";
	
		return "
<SCRIPT language=\"javascript\">
$javascript
</SCRIPT>
$linkHtml
";
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
