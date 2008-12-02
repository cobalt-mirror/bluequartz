<?php
// Author: Patrick Bose 
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: ClassicBlock.inc 259 2004-01-03 06:28:40Z shibuya $

// description:
// ClassicBlock is an implementation of PagedBlock->toHtml() which
// generates the look and feel of the original Cobalt products.
//
// applicability:
// NOTE: This implementation and style are scheduled for removal.

global $isClassicBlockDefined;
if($isClassicBlockDefined)
  return;
$isClassicBlockDefined = true;

  function toClassicBlockHtml($block, $style = "") {
		$this = $block;
    $page = $this->getPage();
    $i18n = $page->getI18n();

    if($style == null || $style->getPropertyNumber() == 0)
      $style = $this->getDefaultStyle($page->getStylist());

    // find out style properties
    $borderColor = $style->getProperty("borderColor");
    $borderThickness = $style->getProperty("borderThickness");
    $dividerAlign = $style->getProperty("dividerAlign");
    $dividerHeight = $style->getProperty("dividerHeight");
    $dividerStyleStr = $style->toBackgroundStyle("dividerCell");
    $formFieldStyleStr = $style->toBackgroundStyle("formFieldCell");
    $labelStyleStr = $style->toBackgroundStyle("labelCell");
    $subscriptStyleStr = $style->toTextStyle("subscript");
    $tabAlign = $style->getProperty("tabAlign");
    $tabDividerHeight = $style->getProperty("tabDividerHeight");
    $tabDividerStyleStr = $style->toBackgroundStyle("tabDivider");
    $tabSelectedLeft = $style->getProperty("leftImage", "tabSelected");
    $tabSelectedRight = $style->getProperty("rightImage", "tabSelected");
    $tabSelectedStyleStr = $style->toBackgroundStyle("tabSelected").$style->toTextStyle("tabSelected");
    $tabUnselectedLeft = $style->getProperty("leftImage", "tabUnselected");
    $tabUnselectedRight = $style->getProperty("rightImage", "tabUnselected");
    $tabUnselectedStyleStr = $style->toBackgroundStyle("tabUnselected").$style->toTextStyle("tabUnselected");
    $titleAlign = $style->getProperty("titleAlign");
    $titleStyleStr = $style->toBackgroundStyle("titleCell");
    $width = ($this->width == -1) ? $style->getProperty("width") : $this->width;
    $dividerLabelStyle = $style->getSubstyle("dividerLabel");
    $labelLabelStyle = $style->getSubstyle("labelLabel");

    $id = $this->getId();
    $form = $page->getForm();
    $formId = $form->getId();

    if ($this->selectedId === 'errors') {
      // search through pages for errors, switch to the first page with errors on it.
      $this->selectedId = $this->pageIds[0]; // the default
      for ($i = 0; $i < count($this->formFields); $i++) {
      	$id = $this->formFields[$i]->getId();
	if ($this->formFieldErrors[$id]) {
	  $this->selectedId = $this->formFieldPageIds[$id];
	  break;
	}
      }
    }
    $selectedId = $this->getSelectedId();
    $pageIds = $this->getPageIds();

    $titleLabelHtml= false;
    $titleLabel = $this->getLabel();
    if ($titleLabel) {
      $titleLabelHtml = $titleLabel->toHtml($style->getSubstyle("titleLabel"));
    }

    // separate all the form fields on the selected page and not
    $formFieldsInPage = array();
    $formFieldIdsInPage = array();
    $formFieldsOutPage = array();
    $formFieldIdsOutPage = array();
    $formFields = $this->getFormFields();
    for($i = 0; $i < count($formFields); $i++) {
      $formField = $formFields[$i];
      $formFieldId = $formField->getId();

      $formFieldPageId = $this->getFormFieldPageId($formField);
      if($formFieldPageId == $selectedId) {
	// form fields on the selected page
	$formFieldsInPage[] = $formField;
	$formFieldIdsInPage[] = $formFieldId;
      }
      else {
	// form fields not on the selected page
	$formFieldsOutPage[] = $formField;
	$formFieldIdsOutPage[] = $formFieldId;
      }
    }

    // find all dividers in page
    $dividers = $this->getDividers();
    $dividersInPage = array();
    $dividerIndexesInPage = array();
    for($i = 0; $i < count($dividers); $i++) {
      // divider not on this page?
      if($this->dividerPageIds[$i] != $selectedId)
	continue;

      $dividersInPage[] = $dividers[$i];
      $dividerIndexesInPage[] = $this->dividerIndexes[$i];
    }

    // make form field for selected ID
    $builder = new FormFieldBuilder();
    $selectedIdField .= $builder->makeHiddenField("_PagedBlock_selectedId_$id", $selectedId);

    // mark visited pages
    if ($selectedId) {
      $visitedPages .= $builder->makeHiddenField($selectedId, "true");
    }
    for($i = 0; $i < count($pageIds); $i++) {
      $pageId = $pageIds[$i];

      // marked already
      if($pageId == $selectedId)
	continue;

      // variable $<pageId> is true if it was visited
      global $$pageId;
      if($$pageId)
	$visitedPages .= $builder->makeHiddenField($pageId, "true");
    }

    // maintain all form fields outside this page as hidden values
    $hiddenFormFields = "";
    for($i = 0; $i < count($formFieldsOutPage); $i++) {
      $formField = $formFieldsOutPage[$i];
      $formFieldId = $formFieldIdsOutPage[$i];
      $formFieldPageId = $this->getFormFieldPageId($formField);

      // find the value of the form field
      $value = "";
      // see if the page for this form field is visited
      // if the page is visited, variable $<pageId> is set to true
      global $$formFieldPageId;
      if($$formFieldPageId) {
	// use user entered value
	global $$formFieldId;
	$value = $$formFieldId;
      }
      else
	// use value set to the form field
	$value = $formField->getValue();

      $hiddenFormFields .= $builder->makeHiddenField($formFieldId, $value);
    }

    // make title row
    $titleRow = $titleLabelHtml ? "<TR><TD ALIGN=\"$titleAlign\" COLSPAN=\"3\" STYLE=\"$titleStyleStr\"><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">$titleLabelHtml</TD></TR>" : "";

    // make tabs
    $tabs = "";
    for($i = 0; $i < count($pageIds); $i++) {
      $pageId = $pageIds[$i];

      $label = $this->getPageLabel($pageId);

      $labelLabel = $label->getLabel();
      $description = $label->getDescription();

      // find the right action
      // find the right icon
      // find the right style
      if($pageId == $selectedId) {
	$action = "javascript: void 0;";
	$tabImageLeft = $tabSelectedLeft;
	$tabImageRight = $tabSelectedRight;
	$tabStyleStr = $tabSelectedStyleStr;
      }
      else {
	global $SCRIPT_NAME;
	$action ="javascript: document.$formId._PagedBlock_selectedId_$id.value = '$pageId'; if(document.$formId.onsubmit()) { document.$formId.action = '$SCRIPT_NAME'; document.$formId.submit(); } else void 0;";
	$tabImageLeft = $tabUnselectedLeft;
	$tabImageRight = $tabUnselectedRight;
	$tabStyleStr = $tabUnselectedStyleStr;
      }

      if($description == "")
	$aTagStart = "<A HREF=\"$action\" onMouseOver=\"return true;\" STYLE=\"$tabStyleStr\">";
      else
	$aTagStart = "<A HREF=\"$action\" onMouseOver=\"return top.code.info_mouseOver('$description')\" onMouseOut=\"return top.code.info_mouseOut();\" STYLE=\"$tabStyleStr\">";

      $tabs .= "
	  <TD STYLE=\"$tabStyleStr\">$aTagStart<IMG BORDER=\"0\" SRC=\"$tabImageLeft\"></A></TD>
	  <TD NOWRAP STYLE=\"$tabStyleStr\">$aTagStart<IMG BORDER=\"0\" WIDTH=\"5\" HEIGHT=\"5\" SRC=\"/libImage/spaceHolder.gif\">$labelLabel<IMG BORDER=\"0\" WIDTH=\"5\" HEIGHT=\"5\" SRC=\"/libImage/spaceHolder.gif\"></A></TD>
	  <TD STYLE=\"$tabStyleStr\">$aTagStart<IMG BORDER=\"0\" SRC=\"$tabImageRight\"></A></TD>\n";
    }

    // make tabs row
    $tabsRow = "";
    if(count($pageIds) > 1) {
      // make padding
      $padding = "<TD><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\"></TD>";
      $leftPadding = ($tabAlign == "" || $tabAlign == "left") ? $padding : "";
      $rightPadding = ($tabAlign == "right") ? $padding : "";

      $tabsRow = "
  <TR>
    <TD ALIGN=\"$tabAlign\" COLSPAN=\"3\">
      <TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" WIDTH=\"1%\">
	<TR>
$leftPadding
$tabs
$rightPadding
	</TR>
      </TABLE>
    </TD>
  </TR>
";

      // add tab divider
      if($tabDividerHeight > 0)
	$tabsRow .= "
  <TR>
    <TD COLSPAN=\"3\" STYLE=\"$tabDividerStyleStr\"><IMG SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"$tabDividerHeight\"></TD>
  </TR>
";
    }

    $result = "
$selectedIdField
$visitedPages
$hiddenFormFields
 <TABLE BGCOLOR=\"$borderColor\" BORDER=\"1\" CELLPADDING=\"0\" CELLSPACING=\"0\" WIDTH=\"$width\"> \n";

    if($titleRow != "" || $tabsRow != "")
      $result .= "
  <TR>
    <TD COLSPAN=\"3\">
      <TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" STYLE=\"$titleStyleStr\" WIDTH=\"100%\">
$titleRow
$tabsRow
      </TABLE>
    </TD>
  </TR>";

    // is this page visited before?
    global $$selectedId;
    $isVisited = $$selectedId ? true : false;

    $optionalStr = $i18n->get("optional", "palette");

    for($i = 0; $i < count($formFieldsInPage); $i++) {
      // add dividers
      for($j = 0; $j < count($dividersInPage); $j++) {
	// divider at the right position?
	if($dividerIndexesInPage[$j] == $i) {
	  $labelObj = $dividersInPage[$j];
	  $label = is_object($labelObj) ? $labelObj->toHtml($dividerLabelStyle) : "";
	  $result .="<TR><TD ALIGN=\"$dividerAlign\" STYLE=\"$dividerStyleStr\" COLSPAN=\"3\" HEIGHT=\"$dividerHeight\"><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">$label</TD></TR>"; 
	}
      }

      $formFieldObj = $formFieldsInPage[$i];

      // maintain form field values entered by users
      // if the page is visited before
      if($isVisited) {
	$formFieldId = $formFieldObj->getId();

	// form fields have input fields using the ID as names
	global $$formFieldId;
	$formFieldObj->setValue($$formFieldId);
      }

      // get form field HTML
      $formField = $formFieldObj->toHtml();

      // hidden field is simple
      $access = $formFieldObj->getAccess();
      if($access == "") {
	$result .= $builder->makeHiddenField(
	  $formFieldObj->getId(),
	  $formFieldObj->getValue());
	continue;
      }

      // get label HTML
      $formFieldLabelObj = $this->getFormFieldLabel($formFieldObj);
      $label = is_object($formFieldLabelObj) ? $formFieldLabelObj->toHtml($labelLabelStyle) : "";

			/////////

			$mouseOver = $formFieldLabelObj->getDescription();
			$mouseOver = $i18n->interpolateJs($mouseOver);
			$formFieldLabelObj->setDescription("");
			$label = $formFieldLabelObj->toHtml( $labelLabelStyle );
			$formFieldLabelObj->setDescription( $mouseOver );
      $mouseOver = "<CENTER><A HREF=\"javascript: void 0\" onMouseOver=\"return top.code.info_mouseOver('$mouseOver')\" onMouseOut=\"return top.code.info_mouseOut();\"><IMG BORDER=\"0\" SRC=\"/.cobalt/images/smallest_help_but\"></A></CENTER>";		
			/////////

      $errormsg = $this->getFormFieldError($formFieldObj);
      if ($errormsg) {
	$errorflag = "<a href=\"javascript: void 0\" 
	  onMouseOver=\"return top.code.info_mouseOverError('"
	  . $i18n->interpolate($errormsg) . "')\" 
	  onMouseOut=\"return top.code.info_mouseOut();\"><img
	  alt=\"[ERROR]\" border=\"0\" src=\"/libImage/infoError.gif\"></a>";
      } else
	$errorflag = "&nbsp;";

      $optional = 
	($formFieldObj->isOptional() 
	&& (strval($formFieldObj->isOptional()) != "silent") )
	? "<FONT STYLE=\"$subscriptStyleStr\">($optionalStr)</FONT>" : "";
	$widlabel = ( $width - 26 ) * .4;
	settype( $widlabel, "integer" );
	$widvalue = $width - $widlabel;
      $result .= "
	<TR>
	<TD WIDTH=\"26\" STYLE=\"$labelStyleStr\"> $mouseOver
                </TD>
	  <TD WIDTH=\"$widlabel\" STYLE=\"$labelStyleStr\">
	    <TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>
	      <TD><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\"></TD>
	      <TD>$label $optional</TD>
	    </TR></TABLE>
	  </TD>
	  <TD WIDTH=\"$widvalue\" STYLE=\"$formFieldStyleStr\">
	    <table border=\"0\" cellpadding=\"5\" cellspacing=\"0\">
	      <tr><td>$formField</td>
	          <td>$errorflag</td>
	      </tr>
	    </table>
	  </TD>
	</TR>
";
    }

    // add last dividers
    $formFieldCount = count($formFieldsInPage);
    for($i = 0; $i < count($dividersInPage); $i++) {
      // divider at the last position?
      if($dividerIndexesInPage[$i] >= $formFieldCount) {
	$labelObj = $dividersInPage[$i];
	$label = (is_object($labelObj)) ? $labelObj->toHtml($dividerLabelStyle) : "";
	$result .="<TR><TD STYLE=\"$dividerStyleStr\" COLSPAN=\"3\" HEIGHT=\"$dividerHeight\"><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">$label</TD></TR>"; 
      }
    }

    // make buttons
    $buttons = $this->getButtons();
    if(count($buttons) > 0) {
      $allButtons .= "<BR>";
      $allButtons .= "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>";

      for($i = 0; $i < count($buttons); $i++) {
	if($i > 0)
	  $allButtons .=  "<TD>&nbsp;</TD>";
	$allButtons .= "<TD>".$buttons[$i]->toHtml()."</TD>";
      }

      $allButtons .= "</TR></TABLE>";
    }

    $result .= "</TABLE> \n$allButtons\n";
    
    $result .= $this->reportErrors();

    return $result;
  }

