<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: PagedBlock.php 259 2004-01-03 06:28:40Z shibuya $

// description:
// PagedBlock represents a block that have multiple pages with each of them
// having their own form fields. The states of form fields on different pages
// are automagically maintained.
//
// applicability:
// Use this class to separate functionally cohesive, but context distant
// information. For example, use it to group "basic" information into one page
// and "advanced" information in another. Do not use this class simply for
// navigation purposes, use the navigation system instead.
//
// usage:
// To use this class for just one page, simply create a PagedBlock object and
// add form fields without specifying any page IDs. To support multiple pages,
// after constructing an object, add pages to it. Afterwards, add form fields
// to the pages. The page to display can be selected by using setSelectedId(),
// but this is optional. The page to display is maintained automagically based
// on user interaction. Changed form field values are passed back to the pages
// as $formFieldId. After submission, $pageId for visited pages are set to
// true. Use getStartMark() and getEndMark() to put HTML code outside the
// scope of PHP into the context of pages.

global $isPagedBlockDefined;
if($isPagedBlockDefined)
  return;
$isPagedBlockDefined = true;

include("uifc/FormFieldBuilder.php");
include("uifc/Block.php");

class PagedBlock extends Block {
  //
  // private variables
  //

  var $dividers;
  var $dividerIndexes;
  var $dividerPageIds;
  var $formFields;
  var $formFieldLabels;
  var $formFieldPageIds;
  var $formFieldErrors;
  var $generalErrors; 
  var $pageIds;
  var $pageLabels;
  var $selectedId;
  var $columnWidths;
  var $hideEmptyPages;
  
  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this block is in
  // param: id: an unique ID of the block in string
  // param: label: a Label object for the block title. Optional
  function PagedBlock($page, $id, $label = "") {
    // superclass constructor
    $this->Block($page, $label);

    $this->setId($id);

    // set selected ID from internal variable
    $variableName = "_PagedBlock_selectedId_$id";
    global $HTTP_POST_VARS;
    $this->setSelectedId($HTTP_POST_VARS[$variableName]);

    $this->dividers = array();
    $this->dividerIndexes = array();
    $this->dividerPageIds = array();
    $this->formFields = array();
    $this->formFieldLabels = array();
    $this->formFieldPageIds = array();
    $this->formFieldErrors = array();
    $this->generalErrors = array();
    $this->pageIds = array();
    $this->pageLabels = array();
    $this->hideEmptyPages = array();
    $this->setColumnWidths();
  }

  function &getDefaultStyle($stylist) {
    return $stylist->getStyle("PagedBlock");
  }

  // description: get the mark for marking the end of a HTML section
  //     specifically for a page. This is useful for adding page specific HTML
  // param: pageId: the ID of the page in string
  // returns: the mark in string
  // see: getStartMark()
  function getEndMark($pageId) {
    $selectedId = $this->getSelectedId();
    if($pageId == $selectedId)
      return "";
    else return " -->";
  }

  // description: get all the form fields of the block
  // returns: an array of FormField objects
  // see: addFormField()
  function &getFormFields() {
    return $this->formFields;
  }

  // description: add a form field to this block
  // param: formField: a FormField object
  // param: label: a label object. Optional
  //     hidden form fields are not shown and therefore do not need labels
  // param: pageId: the ID of the page the form field is in
  //     Optional if there is only one page
  // see: getFormFields()
  function addFormField(&$formField, $label = "", $pageId = "", $errormsg = false) {
    $this->formFields[] =& $formField;
    $this->formFieldLabels[$formField->getId()] = $label;
    $this->formFieldPageIds[$formField->getId()] = $pageId;
    if ($errormsg) {
      $this->formFieldErrors[$formField->getId()] = new Error($errormsg);
    }
  }

    function setFormFieldError($id, $error)
    {
        $this->formFieldErrors[$id] = $error;
    }

    // for backward compatibility, plus I don't feel like
    // finding every call to process_errors tonight
    function process_errors(&$errors, $mapping = array())
    {
        $this->processErrors($errors, $mapping);
    }
    
    // given an array of error objects, sorts them out for later display.
    function processErrors($errors, $mapping = array())
    {
        /* reset the general errors ! */
	$this->generalErrors = array();
        for ($i = 0; $i < count($errors); $i++)
        {
            $error =& $errors[$i];
	    $key = '';
	    if (method_exists($error, 'getKey')) {
		$key = $error->getKey();
	    }
            // remap schema attribute name to localized field name:
            if ($key && $mapping[$key]) 
            {
                $key = $mapping[$key];
            }
	    // disabling this case
	    // we're taking out field specific error messages
	    // because we don't have time to implement it
	    // consistently           
	    if (false && $key) 
            {
                $this->setFormFieldError($key, $error);
            } 
            else 
            {
                  $this->generalErrors[] = $error;
            }
        }
    }

  // description: get all dividers added to the block
  // returns: an array of Label objects
  // see: addDivider()
  function getDividers() {
    return $this->dividers;
  }

  // description: add a divider
  // param: label: a label object. Optional
  // param: pageId: the ID of the page the form field is in
  //     Optional if there is only one page
  // see: getDividers()
  function addDivider($label = "", $pageId = "") {
    $this->dividers[] = $label;
    $this->dividerPageIds[] = $pageId;

    // find the number of form fields before the divider on the page
    $formFieldsBefore = 0;
    $formFields =& $this->getFormFields();
    for($i = 0; $i < count($formFields); $i++) 
    {
        $formFieldPageId = $this->getFormFieldPageId($formFields[$i]);
        if($formFieldPageId == $pageId)
            $formFieldsBefore++;
    }

    $this->dividerIndexes[] = $formFieldsBefore;
  }

  // description: get the label for a form field
  // param: formField: a FormField object
  // returns: a Label object
  // see: addFormField()
  function getFormFieldLabel($formField) {
    return $this->formFieldLabels[$formField->getId()];
  }
  
    // getFormFieldError: get the error message (if any) associated 
    // with a form field.
    function &getFormFieldError(&$formField) 
    {
        $page = $this->getPage();
        $i18n = $page->getI18n();
        $tmperr =& $this->formFieldErrors[$formField->getId()];
        if (!$tmperr) { return ""; }
        return $i18n->interpolate(
                $tmperr->getMessage(),
                $tmperr->getVars());
    }

  // description: get the page ID of a form field
  // param: formField: a FormField object
  // returns: page ID in string
  function getFormFieldPageId($formField) {
    return $this->formFieldPageIds[$formField->getId()];
  }

  // description: get the widths of label and form field
  // returns: an array of widths in integer (pixel) or string (e.g. "60%"). The
  //     first element is for label and the second element is for form field.
  // see: setColumnWidths()
  function getColumnWidths() {
    return $this->columnWidths;
  }

  // description: set the widths of label and form field
  // param: widths: an array of widths in integer (pixel) or string (e.g.
  //     "60%"). The first element is for label and the second element is for
  //     form field.
  // see: getColumnWidths()
  function setColumnWidths($widths = array("40%", "60%")) {
    $this->columnWidths = $widths;
  }

  // description: get the ID of the block
  // returns: a string
  // see: setId()
  function getId() {
    return $this->id;
  }

  // description: set the ID of the block
  // param: Id: a string
  // see: getId()
  function setId($id) {
    $this->id = $id;
  }

  // description: get all the page IDs
  // returns: an array of IDs in string
  // see: addPage()
  function getPageIds() {
    return $this->pageIds;
  }

  // description: get the label of a page
  // param: pageId: the ID of the page
  // returns: a Label object
  // see: addPage()
  function getPageLabel($pageId) {
    return $this->pageLabels[$pageId];
  }

  // description: add a page into the paged block
  // param: pageId: the ID of the page in string
  // param: label: a Label object for the page
  // see: getPageId(), getPageLabel()
  function addPage($pageId, $label) {
    $this->pageIds[] = $pageId;
    $this->pageLabels[$pageId] = $label;

    // set selected ID to default
    if($this->getSelectedId() == "")
      $this->setSelectedId($pageId);
  }

  // description: get the ID of the selected page
  // returns: a string
  // see: setSelectedId()
  function getSelectedId() {
    return $this->selectedId;
  }

  // description: set the ID of the selected page
  // param: selectedId: a string
  // see: getSelectedId()
  function setSelectedId($selectedId) {
    $this->selectedId = $selectedId;
    return $this->getSelectedId();
  }

  // description: get the mark for marking the start of a HTML section
  //     specifically for a page. This is useful for page specific HTML
  // param: pageId: the ID of the page in string
  // returns: the mark in string
  // see: getEndMark()
  function getStartMark($pageId) {
    $selectedId = $this->getSelectedId();
    if($pageId == $selectedId)
      return "";
    else return "<!-- ";
  }

    // creates javascript to report non-field-specific errors.
    function reportErrors()
    {
        global $REQUEST_METHOD;
        if ($REQUEST_METHOD == "GET") { return ""; }
        $page = $this->getPage(); $i18n = $page->getI18n();
        $result = "";
        if (count($this->generalErrors) > 0) 
        {
            $errorInfo = "";
            for ($i = 0; $i < count($this->generalErrors); $i++) 
            {
                $error = $this->generalErrors[$i];
                $errMsg = "";
                if (get_class($error) == "CceError" && ($tag = $error->getKey())) 
                {
                    $tag .= "_invalid";
                    $errMsg = $i18n->getJs( $tag, "", $error->getVars());
                    if ($errMsg === $tag) { $errMsg = ""; }
                }
                if ($errMsg === "") 
                {
                    $errMsg = $i18n->interpolateJs($error->getMessage(), $error->getVars());
                }
                $errorInfo .= $errMsg . "<BR>";
            }
            $result = "<script language=\"javascript\">\n"
                    . "var errorInfo = '$errorInfo';\ntop.code.info_show(errorInfo, \"error\");"
                    . "</script>\n";
        } 
        else
        {
            $result = "<script language=\"javascript\">\n"
                    . "top.code.info_show(\"\", null);\n" . "</script>\n";
        }
        return $result;
    }

  // call this with an array of pageIds to hide if no form
  // fields will be shown for that tab
  // so if you have two tabs 'foo' and 'bar' and you want them to 
  // not show if nothing is under them pass in array('foo', 'bar')
  function setHideEmptyPages($pages)
  {
        $this->hideEmptyPages =& $pages;
  }

  function toHtml($style = "") 
  {
    $page = $this->getPage();
    $i18n = $page->getI18n();

    if($style == null || $style->getPropertyNumber() == 0)
        $style =& $this->getDefaultStyle($page->getStylist());

    if ("true" == $style->getProperty("classicStyle")) 
    {
        include_once("uifc/ClassicBlock.inc");
        return toClassicBlockHtml( $this, $style );
    }

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

    if ($this->getSelectedId() === 'errors') 
    {
        // search through pages for errors, switch to the first page with errors on it.
        $this->setSelectedId($this->pageIds[0]); // the default
        for ($i = 0; $i < count($this->formFields); $i++) 
        {
              $field_id = $this->formFields[$i]->getId();
            if ($this->formFieldErrors[$field_id]) 
            {
                $this->setSelectedId($this->formFieldPageIds[$field_id]);
                break;
            }
        }
    }
    $selectedId = $this->getSelectedId();
    $pageIds = $this->getPageIds();

    $titleLabelHtml= false;
    $titleLabel = $this->getLabel();
    if ($titleLabel) 
    {
        $titleLabelHtml = $titleLabel->toHtml($style->getSubstyle("titleLabel"));
    }

    // separate all the form fields on the selected page and not
    $formFieldsInPage = array();
    $formFieldIdsInPage = array();
    $formFieldsOutPage = array();
    $formFieldIdsOutPage = array();
    $formFields =& $this->getFormFields();
    $pageIdsWithFormFields = array();

    for($i = 0; $i < count($formFields); $i++) 
    {
        $formField =& $formFields[$i];
        $formFieldId = $formField->getId();

        $formFieldPageId = $this->getFormFieldPageId($formField);
        
        // keep track of tabs with no formfields
        if (!isset($pageIdsWithFormFields[$formFieldPageId]))
            $pageIdsWithFormFields[$formFieldPageId] = true;
            
        if($formFieldPageId == $selectedId) 
        {
            // form fields on the selected page
            // this should be a reference assignment, but php sucks
            $formFieldsInPage[] =& $formField;
            $formFieldIdsInPage[] = $formFieldId;
        }
        else 
        {
            // form fields not on the selected page
            $formFieldsOutPage[] =& $formField;
            $formFieldIdsOutPage[] = $formFieldId;
        }
    }

    // find all dividers in page
    $dividers = $this->getDividers();
    $dividersInPage = array();
    $dividerIndexesInPage = array();
    for($i = 0; $i < count($dividers); $i++) 
    {
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
    for($i = 0; $i < count($pageIds); $i++) 
    {
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
    for($i = 0; $i < count($formFieldsOutPage); $i++) 
    {
        $formField =& $formFieldsOutPage[$i];
        $formFieldId = $formFieldIdsOutPage[$i];
        $formFieldPageId = $this->getFormFieldPageId($formField);

        // find the value of the form field
        $value = "";
        
        // use value set to the form field, since the form field knows
        // how to preserve data
        $value = $formField->getValue();
        $hiddenFormFields .= $builder->makeHiddenField($formFieldId, $value);

	// FormFields that are containers of other form fields
	// are handled here. We call getFormFields on them
	// and add those to our list.
	if (method_exists($formField, "getFormFields")) {
	  foreach ($formField->getFormFields() as $a_field) {
	    if (method_exists($a_field, "getId")) {
	      array_push($formFieldsOutPage, $a_field);
	      array_push($formFieldIdsOutPage, $a_field->getId());
	    }
	  }
	}
    }

    // make title row
    $titleRow = $titleLabelHtml ? "<TR><TD ALIGN=\"$titleAlign\" COLSPAN=\"2\" STYLE=\"$titleStyleStr\"><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">$titleLabelHtml</TD></TR>" : "";

    // make tabs
    $tabs = "";
    $shown_pages = 0;
    for($i = 0; $i < count($pageIds); $i++) 
    {
        $pageId = $pageIds[$i];
        
        // drop tabs with no formfields that are in the
        // hideEmptyPages array
        if (!$pageIdsWithFormFields[$pageId] && 
                in_array($pageId, $this->hideEmptyPages))
            continue;
        else
            $shown_pages++;

        $label = $this->getPageLabel($pageId);

        $labelLabel = $label->getLabel();
        $description = $label->getDescription();

        // find the right action
        // find the right icon
        // find the right style
        if($pageId == $selectedId) 
        {
            $action = "javascript: void 0;";
            $tabImageLeft = $tabSelectedLeft;
            $tabImageRight = $tabSelectedRight;
            $tabStyleStr = $tabSelectedStyleStr;
        }
        else 
        {
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
    if($shown_pages > 1) 
    {
        // make padding
        $padding = "<TD><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\"></TD>";
        $leftPadding = ($tabAlign == "" || $tabAlign == "left") ? $padding : "";
        $rightPadding = ($tabAlign == "right") ? $padding : "";

        $tabsRow = "
  <TR>
    <TD ALIGN=\"$tabAlign\" COLSPAN=\"2\">
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
    <TD COLSPAN=\"2\" STYLE=\"$tabDividerStyleStr\"><IMG SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"$tabDividerHeight\"></TD>
  </TR>
";
    }

    $result = "
$selectedIdField
$visitedPages
$hiddenFormFields
<TABLE BGCOLOR=\"$borderColor\" BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" WIDTH=\"$width\"><TR><TD>
<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"$borderThickness\" WIDTH=\"100%\">\n";

    if($titleRow != "" || $tabsRow != "")
        $result .= "
  <TR>
    <TD COLSPAN=\"2\">
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

    for($i = 0; $i < count($formFieldsInPage); $i++) 
    {
        // add dividers
        for($j = 0; $j < count($dividersInPage); $j++) 
        {
            // divider at the right position?
            if($dividerIndexesInPage[$j] == $i) 
            {
                $labelObj = $dividersInPage[$j];
                $label = is_object($labelObj) ? $labelObj->toHtml($dividerLabelStyle) : "";
                $result .="<TR><TD ALIGN=\"$dividerAlign\" STYLE=\"$dividerStyleStr\" COLSPAN=\"2\" HEIGHT=\"$dividerHeight\"><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">$label</TD></TR>"; 
            }
        }

        $formFieldObj =& $formFieldsInPage[$i];
       
        // get form field HTML
        $formField =& $formFieldObj->toHtml();

        // hidden field is simple
        $access = $formFieldObj->getAccess();
        if($access == "") 
        {
            $result .= $builder->makeHiddenField(
                        $formFieldObj->getId(),
                        $formFieldObj->getValue());
            continue;
        }

        // get label HTML
        $formFieldLabelObj = $this->getFormFieldLabel($formFieldObj);
        $label = is_object($formFieldLabelObj) ? $formFieldLabelObj->toHtml($labelLabelStyle) : "";

        $errormsg =& $this->getFormFieldError($formFieldObj);
        if ($errormsg) 
        {
            $errorflag = "<TD><a href=\"javascript: void 0\" 
                onMouseOver=\"return top.code.info_mouseOverError('"
                . $errormsg . "')\" 
                onMouseOut=\"return top.code.info_mouseOut();\"><img
                alt=\"[ERROR]\" border=\"0\" src=\"/libImage/infoError.gif\"></a></TD>";
        }
        else
            $errorflag = "";

        $optional = "";
        if ($formFieldObj->isOptional() && (strval($formFieldObj->isOptional()) != "silent"))
        {
            $optional = "<FONT STYLE=\"$subscriptStyleStr\">($optionalStr)</FONT>";
        }

        $result .= "
    <TR>
      <TD WIDTH=\"{$this->columnWidths[0]}\" STYLE=\"$labelStyleStr\">
        <TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>
          <TD><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\"></TD>
          <TD>$label $optional</TD>
        </TR></TABLE>
      </TD>
      <TD WIDTH=\"{$this->columnWidths[1]}\" STYLE=\"$formFieldStyleStr\">
        <table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
          <tr><td>$formField</td>$errorflag</tr>
        </table>
      </TD>
    </TR>
";
    }

    // add last dividers
    $formFieldCount = count($formFieldsInPage);
    for($i = 0; $i < count($dividersInPage); $i++) 
    {
        // divider at the last position?
        if($dividerIndexesInPage[$i] >= $formFieldCount) 
        {
            $labelObj = $dividersInPage[$i];
            $label = (is_object($labelObj)) ? $labelObj->toHtml($dividerLabelStyle) : "";
            $result .="<TR><TD STYLE=\"$dividerStyleStr\" COLSPAN=\"2\" HEIGHT=\"$dividerHeight\"><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">$label</TD></TR>"; 
        }
    }

    // make buttons
    $buttons = $this->getButtons();
    if(count($buttons) > 0) 
    {
        $allButtons .= "<BR>";
        $allButtons .= "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>";

        for($i = 0; $i < count($buttons); $i++) 
        {
            if($i > 0)
                $allButtons .=  "<TD><IMG SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"5\"></TD>";
            $allButtons .= "<TD>".$buttons[$i]->toHtml()."</TD>";
        }

        $allButtons .= "</TR></TABLE>";
    }

    $result .= "</TABLE></TD></TR></TABLE>\n$allButtons\n";
    
    $result .= $this->reportErrors();

    return $result;
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
