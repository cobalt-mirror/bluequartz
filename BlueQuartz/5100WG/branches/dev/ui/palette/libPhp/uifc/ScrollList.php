<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: ScrollList.php 201 2003-07-18 19:11:07Z will $

// description:
// The class represents a list of elements. Elements are being put into pages.
// The number of pages and which one to display is automatically maintained by
// this class.
//
// applicability:
// Wherever a list of similar elements needs to be represented. Do not use this
// class for list of different elements.
//
// usage:
// Simply construct a ScrollList object with a list of entry labels specified.
// Entries can then be added using the addEntry() method. Remember to keep the
// number of elements of each entry the same as the number of entry labels. Use
// toHtml() to get the HTML representation. "_ScrollList_pageIndex_<id>" is a
// special variable used by ScrollList objects to maintain the current page
// index. When the page selection widget within the scroll list is triggered,
// this variable is filled and the form containing the scroll list is
// submitted. It is, therefore, possible to know if the form is submitted by
// the widget or something else by checking if it contains any value.

global $isScrollListDefined;
if($isScrollListDefined)
  return;
$isScrollListDefined = true;

include_once("System.php");
include_once("uifc/FormFieldBuilder.php");
include_once("uifc/HtmlComponent.php");
//include("I18n.php");

class ScrollList extends HtmlComponent {
  //
  // private variables
  //

  var $alignments;
  var $buttons;
  var $duplicateLimit;
  var $entries;
  var $entriesSelected;
  var $entryCountTagSingular;
  var $entryCountTagPlural;
  var $entryIds;
  var $entryLabels;
  var $entryNum;
  var $label;
  var $length;
  var $pageIndex;
  var $sortables;
  var $sortedIndex;
  var $sortEnabled;
  var $sortOrder;
  var $showArrows;
  var $columnWidths;
  var $emptyMsg;
  var $entryCountHidden;
  var $headerRowHidden;
  var $selectAllEnabled;
  var $widgetid;
  var $width;
  var $errors;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this object lives in
  // param: id: the identifier in string
  // param: label: a label object for the list
  // param: entryLabels: an array Label object for the entries
  // param: sortables: an array of indexes of the sortable components. Optional
  function ScrollList(&$page, $id, &$label, $entryLabels, $sortables = array()) {
    global $widget_counter;
    if (!$widget_counter) { $widget_counter = 1; }
    $widgetid = $widget_counter; $widget_counter++;
    $this->widgetid = $widgetid;

    // superclass constructor
    $this->HtmlComponent($page);

    $this->setId($id);
    $this->setLabel($label);
    $this->setEntryLabels($entryLabels);
    $this->setSortables($sortables);
   
    $this->setArrowVisible(true);

    $this->buttons = array();
    $this->entries = array();
    $this->entryIds = array();
    $this->entriesSelected = array();
    $this->columnWidths = array();
    $this->emptyMsg = "";

    $this->errors = array();

    // set page index from internal variable
    $variableName = "_ScrollList_pageIndex_$widgetid";
    global $$variableName;
    if($$variableName != "")
      $this->setPageIndex($$variableName);
    else
      $this->setPageIndex(0);

    // set sorted index
    $variableName = "_ScrollList_sortedIndex_$widgetid";
    global $$variableName;
    if($$variableName != "")
      $this->setSortedIndex($$variableName);
    else if(count($sortables) > 0)
      $this->setSortedIndex($sortables[0]);
    else
      $this->setSortedIndex(-1);

    $variableName = "_ScrollList_sortOrder_$widgetid";
    global $$variableName;
    if($$variableName != "")
      $this->setSortOrder($$variableName);
    else
      $this->setSortOrder("ascending");

    // set default
    $system = new System();
    $this->setDuplicateLimit($system->getConfig("defaultScrollListDuplicateLimit"));
    $this->setEntryNum(-1);
    $this->setLength($system->getConfig("defaultScrollListLength"));
    $this->setSelectAll(false);
    $this->setSortEnabled(true);
    $this->entryCountHidden = false;
    $this->entryCountTagSingular = "[[palette.entryCountSingular]]";
    $this->entryCountTagPlural = "[[palette.entryCountPlural]]";

    $this->headerRowHidden = false;
  }

  // description: given an array of error objects, processes them for display
  function processErrors($errors) {
    $this->errors = $errors;
  }

  // create javascript to report errors
  function reportErrors()
  {
    global $REQUEST_METHOD;
    if ($REQUEST_METHOD == "GET") {return "";}
    $page = $this->getPage(); $i18n = $page->getI18n();
    $result = "";
    if (count($this->errors) > 0)
    {
        $errorInfo = "";
	for ($i=0;$i<count($this->errors);$i++) {
	    $error = $this->errors[$i];
	    $errMsg = "";
	    if (get_class($error) == "CceError" && ($tag = $error->getKey())) {
	        $tag .= "_invalid";
		$errMsg = $i18n->getJs( $tag, "", $error->getVars());
		if ($errMsg == $tag) {$errMsg = ""; }
	    }
	    if ($errMsg == "")
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
            . "top.code.info_show(\"\", null)\n" 
	    . "</script>\n";
    }
    return $result; 
  }


  // description: get the horizontal alignments of items in entries
  // returns: an array of alignment strings
  // see: setAlignments()
  function getAlignments() {
    return $this->alignments;
  }

  // description: set the horizontal alignments of items in entries
  // param: alignments: an array of alignment strings (i.e. "", "left",
  //     "center" or "right"). "" and empty array element both means left.
  //     First alignment string for the first item in entries, second
  //     alignment string for the second item in entries and so forth
  // see: getAlignments()
  function setAlignments($alignments) {
    $this->alignments = $alignments;
  }

  function setArrowVisible($vis){
    $this->showArrows=$vis;
  }

  function getArrowVisible(){
    return $this->showArrows;
  }

  // description: get the column widths for items in entries
  // returns: an array of widths
  // see: setColumnWidths()
  function getColumnWidths() {
    return $this->columnWidths;
  }

  // description: set the column widths for items in entries
  // param: widths: an array of widths in numbers (e.g. 100), percentage
  //     strings (e.g. 25%) or "". "" or empty elements means no defined width
  // see: getColumnWidths()
  function setColumnWidths($columnWidths) {
    $this->columnWidths = $columnWidths;
  }
  
  // description: set the width of the scroll list
  // param: width: the width of the scroll list in pixels 
  // see: getWidth()
  function setWidth($width) {
    $this->width = $width;
  }

  // description: get the width of the scroll list in pixels
  // returns: the width of the scroll list in pixels 
  // see: setWidth()
  function getWidth() {
    return $this->width;
  }

  // description: get all buttons added to the list
  // returns: an array of Button objects
  // see: addButton()
  function getButtons() {
    return $this->buttons;
  }

  // description: add a button to the list
  // param: button: a Button object
  // see: getButtons()
  function addButton(&$button) {
    $this->buttons[] =& $button;
  }

  // description: when select all is on and entries can be selected, a widget
  //     is available on the list to select/unselect all entries at once
  // param: selectAll: a boolean
  // see: isSelectAll(), addEntry()
  function setSelectAll($selectAll = true) {
    $this->selectAllEnabled = $selectAll;
  }

  // description: get the select all flag
  // returns: true if select all is enabled, false otherwise
  // see: isSelectAll(), addEntry()
  function isSelectAll() {
    return $this->selectAllEnabled;
  }

  // description: set the message to be displayed when the list is empty
  // param: msg: an I18n tag of the form [[domain.messageId]] for interpolation
  function setEmptyMessage($msg = "") {
	$this->emptyMsg = $msg;
  }

  function &getDefaultStyle(&$stylist) {
    return $stylist->getStyle("ScrollList");
  }

  // description: get the limit above which should duplication of buttons
  //      happen at the end of the list
  // returns: an integer
  // see: setDuplicateLimit()
  function getDuplicateLimit() {
    return $this->duplicateLimit;
  }

  // description: set the limit above which should duplication of buttons
  //     happen at the end of the list
  // param: duplicateLimit: the limit in integer
  // see: getDuplicateLimit()
  function setDuplicateLimit($duplicateLimit) {
    $this->duplicateLimit = $duplicateLimit;
  }

  // description: add an entry to the list
  // param: entry: an array of objects that consist the entry
  // param: entryId: an unique ID for the entry. Optional.
  //     If supplied, the entry can be selected
  // param: entrySelected: true if the entry is selected, false otherwise.
  //     Optional
  // param: entryNumber: the index of the entry on the list. Optional. If not
  //     supplied, the entry is appended to the end of the list
  function addEntry($entry, $entryId = "", $entrySelected = false, $entryIndex = -1) {
    if($entryIndex == -1)
      $entryIndex = count($this->entries);

    $this->entries[$entryIndex] = $entry;
    $this->entryIds[$entryIndex] = $entryId;
    $this->entriesSelected[$entryIndex] = $entrySelected;
  }

  // description: get the number of entries in the list
  // returns: an integer
  // see: setEntryNum(), addEntry()
  function getEntryNum() {
    if($this->entryNum != -1)
      return $this->entryNum;
    else
      return count($this->getEntries());
  }

  // description: tell the list how many entries are there in the list. This is
  //     useful when you use addEntry() only to add a section of the list, so
  //     you need to tell the list how many entries are really there
  // param: entryNum: an integer
  // see: getEntryNum(), addEntry()
  function setEntryNum($entryNum) {
    $this->entryNum = $entryNum;
  }

  // description: set the i18n message tags used in entry count
  //     Message tags has the format of "[[<domain>.<messageId>]]"
  // param: singular: a string message tag used when only one entry is listed
  // param: plural: a string message tag used when many or zero are listed
  function setEntryCountTags($singular, $plural) {
    $this->entryCountTagSingular = $singular;
    $this->entryCountTagPlural = $plural;
  }

  // description: get all the entries added to the list
  // returns: an array of entries. Each entry is an array of HtmlComponent
  //     objects
  // see: addEntry()
  function getEntries() {
    return $this->entries;
  }

  // description: get the labels for each item of the entries
  // returns: an array of Label objects
  // see: setEntryLabels()
  function getEntryLabels() {
    return $this->entryLabels;
  }

  // description: set the labels for each item of the entries
  // param: entryLabels: an array of Label objects
  // see: getEntryLabels()
  function setEntryLabels($entryLabels) {
    $this->entryLabels = $entryLabels;
  }

  // description: get the ID of the list
  // returns: an ID string
  // see: setId()
  function getId() {
    return $this->id;
  }

  // description: set the ID of the list
  // param: id: an ID string
  // see: getId()
  function setId($id) {
    $this->id = $id;
  }

  // description: get the label of the list
  // returns: a Label object
  // see: setLabel()
  function &getLabel() {
    return $this->label;
  }

  // description: set the label of the list
  // param: label: a Label object
  // see: getLabel()
  function setLabel(&$label) {
    $this->label =& $label;
  }

  // description: get the maximum length of pages on the list.
  // returns: an integer
  // see: setLength()
  function getLength() {
    return $this->length;
  }

  // description: set the maximum length of pages on the list. For example, if
  //     length is set to 10 and there are 25 entries, the list is presented in
  //     3 pages of 10, 10 and 5 entries
  // param: length: an integer
  // see: getLength()
  function setLength($length) {
    $this->length = $length;
  }

  // description: get the index of the page the list is presenting
  // returns: an integer
  // see: setPageIndex(), setLength()
  function getPageIndex() {
    return $this->pageIndex;
  }

  // description: set the index of the page the list is presenting
  // param: pageIndex: an integer
  // see: getPageIndex(), setLength()
  function setPageIndex($pageIndex) {
    $this->pageIndex = $pageIndex;
  }

  // description: see if sorting is done by the list
  // returns: a boolean
  // see: setSortEnabled()
  function isSortEnabled() {
    return $this->sortEnabled;
  }

  // description: enable or disable sorting sone by the list. This method is
  //     useful if entries supplied are already sorted
  // param: sortEnabled: a boolean
  // see: getSortEnabled()
  function setSortEnabled($sortEnabled) {
    $this->sortEnabled = $sortEnabled;
  }

  // description: get the sortable components of the entries
  // returns: an array of indexes of the sortable components
  // see: setSortables()
  function getSortables() {
    return $this->sortables;
  }

  // description: set the sortable components of the entries
  // param: sortables: an array of indexes of the sortable components
  // see: getSortables()
  function setSortables($sortables) {
    $this->sortables = $sortables;
  }

  // description: get the index of the components that are sorted
  // returns: an integer
  // see: setSortedIndex()
  function getSortedIndex() {
    return $this->sortedIndex;
  }

  // description: set the index of the components that are sorted. This method
  //     always overrides user selection. Use setDefaultSortedIndex() if
  //     overriding is not desired
  // param: sortedIndex: an integer. If -1, no sorting is done
  // see: getSortedIndex()
  function setSortedIndex($sortedIndex) {
    $this->sortedIndex = $sortedIndex;
  }

  // description: set the index of the components that are sorted. If user has
  //     made selections, this method will not override it
  // param: sortedIndex: an integer. If -1, no sorting is done
  function setDefaultSortedIndex($sortedIndex) {
    $widgetid = $this->widgetid;
    $variableName = "_ScrollList_sortedIndex_$widgetid";
    global $$variableName;
    if($$variableName == "")
      $this->sortedIndex = $sortedIndex;
  }

  // description: get the order of sorting
  // returns: "ascending" or "descending"
  // see: setSortOrder()
  function getSortOrder() {
    return $this->sortOrder;
  }

  // description: set the order of sorting
  // param: sortOrder: "ascending" or "descending"
  //     Optional and ascending by default
  // see: getSortOrder()
  function setSortOrder($sortOrder = "ascending") {
    $this->sortOrder = $sortOrder;
  }

  // description: the method to sort the entries when displaying the list
  // param: entries: the array of entries to sort
  function sortEntries(&$entries) { 
    $sortedIndex = $this->getSortedIndex();

    // sorting not needed?
    if($sortedIndex == -1 || !$this->isSortEnabled())
      return;

    $entryNum = $this->getEntryNum();
    $sortOrder = $this->getSortOrder();

    // get the sort keys
    $keys = array();
    for($i = 0; $i < $entryNum; $i++)
      $keys[] = $entries[$i][$sortedIndex];

    include_once('Collator.php');
    $collator = new Collator();
    $collator->sort($keys, $entries);

    if($sortOrder == "descending")
      $entries = array_reverse($entries);
  }

  function setEntryCountHidden($val = false) {
    $this->entryCountHidden = $val;
  }

  function setHeaderRowHidden($val = false) {
    $this->headerRowHidden = $val;
  }

  // description: turn the object into HTML form
  // param: style: the style to show in (optional)
  // returns: HTML that represents the object or
  //      "" if pageIndex is out of range
  function toHtml($style = "") {
    $widgetid = $this->widgetid;

    if($style == null || $style->getPropertyNumber() == 0) {
      $page =& $this->getPage();
      $style =& $this->getDefaultStyle($page->getStylist());
    }

    if ( "true" == $style->getProperty("classicStyle") ) {
      include("uifc/ClassicList.inc");
      return toClassicListHtml( $this, $style );
    }

    // find out style properties
    $borderColor = $style->getProperty("borderColor");
    $borderThickness = $style->getProperty("borderThickness");
    $cellPadding = $style->getProperty("cellPadding");
    // Additions for making nice table Borders
    $useShadow = $style->getProperty("useShadow");
    $shadowTopLeftCorner = $style->getProperty("shadowTopLeftCorner");
    $shadowTop = $style->getProperty("shadowTop");
    // remove unwanted width and height attributes
    $shadowTop = substr($shadowTop, 0, strpos($shadowTop, "\""));
    $shadowTopLeft = $style->getProperty("shadowTopLeft");
    $shadowTopRight = $style->getProperty("shadowTopRight");
    $shadowTopRightCorner = $style->getProperty("shadowTopRightCorner");
    $shadowLeft = $style->getProperty("shadowLeft");
    // remove unwanted width and height attributes
    $shadowLeft = substr($shadowLeft, 0, strpos($shadowLeft, "\""));
    $shadowLeftTop = $style->getProperty("shadowLeftTop");
    $shadowRight = $style->getProperty("shadowRight");
    // remove unwanted width and height attributes
    $shadowRight = substr($shadowRight, 0, strpos($shadowRight, "\""));
    $shadowRightTop = $style->getProperty("shadowRightTop");
    $shadowLeftBottom = $style->getProperty("shadowLeftBottom");
    $shadowRightBottom = $style->getProperty("shadowRightBottom");
    $shadowBottomLeftCorner = $style->getProperty("shadowBottomLeftCorner");
    $shadowBottom = $style->getProperty("shadowBottom");
    // remove unwanted width and height attributes
    $shadowBottom = substr($shadowBottom, 0, strpos($shadowBottom, "\""));
    $shadowBottomLeft = $style->getProperty("shadowBottomLeft");
    $shadowBottomRight = $style->getProperty("shadowBottomRight");
    $shadowBottomRightCorner = $style->getProperty("shadowBottomRightCorner");
    // Back to normal properties
    $useActiveLabel = $style->getProperty("useActiveLabel");
    $activeColumnBackground = $style->getProperty("activeColumnBackground");
    $inactiveColumnBackground = $style->getProperty("inactiveColumnBackground");
    // remove unwanted width and height attributes
    $activeColumnBackground = substr($activeColumnBackground, 0, strpos($activeColumnBackground, "\""));
    $inactiveColumnBackground = substr($inactiveColumnBackground, 0, strpos($inactiveColumnBackground, "\""));
    $listDivider = $style->getProperty("listDivider");
    $titleHeightBuffer = $style->getProperty("titleHeightBuffer");
    $controlAlign = $style->getProperty("controlAlign");
    $controlStyleStr = $style->toBackgroundStyle("controlCell");
    $controlLabelStyleStr = $style->toTextStyle("controlLabel");
    $formFieldStyleStr = $style->toBackgroundStyle("entryCell").$style->toTextStyle("entryCell");
    $formTextStyleStr = $style->toTextStyle("entryCell");
    $labelStyleStr = $style->toBackgroundStyle("labelCell");
    $sortAscendingIcon = $style->getProperty("sortAscendingIcon");
    $sortDescendingIcon = $style->getProperty("sortDescendingIcon");
    $sortedAscendingIcon = $style->getProperty("sortedAscendingIcon");
    $sortedDescendingIcon = $style->getProperty("sortedDescendingIcon");
    $titleAlign = $style->getProperty("titleAlign");
    $titleStyleStr = $style->toBackgroundStyle("titleCell");
    $width = $this->width;
    if (!$width) { 
      $width = $style->getProperty("width");
    }
    if (!$width) { 
      $width = 550;
    }

    $entries = $this->getEntries();
    $entryIds = $this->entryIds;
    $entriesSelected = $this->entriesSelected;
    $entryNum = $this->getEntryNum();

    $sortedIndex = $this->getSortedIndex();
    $sortOrder = $this->getSortOrder();

    // sort the entries
    if($sortedIndex != -1 && $this->isSortEnabled())
      $this->sortEntries($entries);

/*
      // prepare a hash for sorting
      $sortHash = array();
      for($i = 0; $i < $entryNum; $i++) {
	$entry = $entries[$i];
	$sortHash[$entry[$sortedIndex]->getValue()] = $entry;
      }

      // sort
      if($sortOrder == "ascending")
	ksort($sortHash);
      else
	krsort($sortHash);

      // save result
      $entries = array_values($sortHash);
*/

    $desiredLength = $this->getLength();
    $length = $desiredLength;

    // out of range?
    $pageIndex = $this->getPageIndex();
    if($pageIndex < 0 || $pageIndex > $entryNum/$length)
      return "";

    // find out from where to start listing
    $from = $pageIndex*$length;

    // find out length
    if($from+$length > $entryNum)
      $length = $entryNum-$from;

    $id = $this->getId();
    $page =& $this->getPage();
    $i18n =& $page->getI18n();
    $form =& $page->getForm();
    $formId = $form->getId();

    $entryLabelObjs = $this->getEntryLabels();
    $entryLabelNum = count($entryLabelObjs);

    // find out if any entries can be selected
    $hasSelectColumn = false;
    for($i = 0; $i < $entryNum; $i++)
      if($entryIds[$i] != "") {
	$hasSelectColumn = true;
	break;
      }

    // find out number of columns
    $columnNum = $hasSelectColumn ? $entryLabelNum+1 : $entryLabelNum;

    $builder = new FormFieldBuilder();

    // make entry count
    $messageTag = ($entryNum == 1) ? $this->entryCountTagSingular : $this->entryCountTagPlural;
    $entryCount = "</TD><TD ALIGN=\"RIGHT\" NOWRAP WIDTH=\"20%\"><table border=0 cellpadding=0 cellspacing=0><tr><td nowrap><FONT STYLE=\"$controlLabelStyleStr\">".$i18n->interpolate($messageTag, array("count" => $entryNum))."</FONT></td><td><IMG BORDER=0 SRC=\"/libImage/spaceHolder.gif\" HEIGHT=20 WIDTH=5></td></tr></table>";

    // make page selection widget
    if($entryNum <= $length)
      $pageSelect = "";
    else {
      // make button for selection
      $pageIndexId = "_ScrollList_pageIndex_$widgetid";
      
      include_once('uifc/MultiButton.php');
      $multiButton = new MultiButton($page);
      $pageIndexCount = 0;
      for($i = 0; $i < $entryNum; $i += $desiredLength) {
	// find the range
	$fromEntry = $desiredLength*$pageIndexCount+1;
	$toEntry = $desiredLength*($pageIndexCount+1);
	$toEntry = ($toEntry <= $entryNum) ? $toEntry : $entryNum;

	// add action
        $multiButton->addAction("javascript: if(document.$formId.onsubmit()) {document.$formId.$pageIndexId.value = $pageIndexCount; document.$formId.submit()}", $i18n->get("entryRange", "palette", array("from" => $fromEntry, "to" => $toEntry)));

	// next page
	$pageIndexCount++;
      }
      $multiButton->setSelectedIndex($pageIndex);

      // make hidden field to store page index
      $result .= $builder->makeHiddenField($pageIndexId, $pageIndex);

       // make hidden field to store entryNum
      $result .= $builder->makeHiddenField("_entryNum", $entryNum);

      $pageSelect .= "<TD ALIGN=\"RIGHT\" NOWRAP WIDTH=\"20%\">".$multiButton->toHtml()."</TD>";
    }

    // make buttons
    $buttons = $this->getButtons();
    $allButtons = "";
    if(count($buttons) > 0) {
      $allButtons .= "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>";

      for($i = 0; $i < count($buttons); $i++) {
	if($i > 0)
	  $allButtons .=  "<TD><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\"></TD>";
	  $allButtons .= "<TD><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\"></TD><TD>".$buttons[$i]->toHtml()."</TD>";
      }

      $allButtons .= "</TR></TABLE>";
    }

    if (!$this->entryCountHidden) {
      $controlRow = "
  <TR>
    <TD STYLE=\"$controlStyleStr\" COLSPAN=\"$columnNum\"><TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" WIDTH=\"100%\"><TR><TD ALIGN=\"$controlAlign\">$allButtons $pageSelect $entryCount</TD></TR></TABLE></TD>
  </TR>
";
    } else {
      $controlRow = "";
    }
	
	// The addition of an optional frame will be done in a way
	// that won't break old styles.
	if ( $useShadow == "true" ) {
	
	$shadowFrameStart = "
			<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
			<tr>
				<td align=\"right\" valign=\"bottom\"><img src=\"$shadowTopLeftCorner\" alt=\"\" border=\"0\"></td>
				<td background=\"$shadowTop\"><img src=\"$shadowTopLeft\" alt=\"\" border=\"0\"></td>
				<td align=\"right\" background=\"$shadowTop\"><img src=\"$shadowTopRight\" alt=\"\" border=\"0\"></td>
				<td><img src=\"$shadowTopRightCorner\" alt=\"\" border=\"0\"></td>
			</tr><tr>
				<td align=\"right\" valign=\"top\" background=\"$shadowLeft\"><img src=\"$shadowLeftTop\" alt=\"\" border=\"0\"></td>
				<td rowspan=\"2\" colspan=\"2\">
	";
	
	$shadowFrameEnd = "
				</td>
				<td valign=\"top\" background=\"$shadowRight\"><img src=\"$shadowRightTop\" alt=\"\" border=\"0\"></td>
			</tr><tr>
				<td align=\"right\" valign=\"bottom\" background=\"$shadowLeft\"><img src=\"$shadowLeftBottom\" alt=\"\" border=\"0\"></td>
				<td valign=\"bottom\" background=\"$shadowRight\"><img src=\"$shadowRightBottom\" alt=\"\" border=\"0\"></td>
			</tr><tr>
				<td align=\"right\" valign=\"top\"><img src=\"$shadowBottomLeftCorner\" alt=\"\" border=\"0\"></td>
				<td valign=\"top\" background=\"$shadowBottom\"><img src=\"$shadowBottomLeft\" alt=\"\" border=\"0\"></td>
				<td align=\"right\" valign=\"top\" background=\"$shadowBottom\"><img src=\"$shadowBottomRight\" alt=\"\" border=\"0\"></td>
				<td align=\"left\" valign=\"top\"><img src=\"$shadowBottomRightCorner\" alt=\"\" border=\"0\"></td>
			</tr>
		</table>
	";

	
	} else {
	$shadowFrameStart = "";
	$shadowFrameEnd = "";
    }
    
    $labelObj =& $this->getLabel();
    $label = $labelObj->toHtml($style->getSubstyle("titleLabel"));
    $result .= "
<STYLE TYPE=\"text/css\">
.formField-$widgetid \{$formFieldStyleStr}
</STYLE>
$shadowFrameStart
<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" BGCOLOR=\"$borderColor\" WIDTH=\"$width\"><TR><TD>
<TABLE BORDER=\"0\" CELLPADDING=\"$cellPadding\" CELLSPACING=\"$borderThickness\" WIDTH=\"100%\">
  <TR>
    <TD ALIGN=\"$titleAlign\" STYLE=\"$titleStyleStr\" COLSPAN=\"$columnNum\"><table border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr>
    <td><IMG BORDER=\"0\" WIDTH=\"5\" HEIGHT=\"$titleHeightBuffer\" SRC=\"/libImage/spaceHolder.gif\"></td><td>$label</td></tr></table></TD>
 </TR>
$controlRow
";

    if($length == 0) {
      $emptyList = ($this->emptyMsg == "") ? $i18n->get("emptyList", "palette") : $i18n->interpolate($this->emptyMsg);
      $result .= "
  <TR>
    <TD STYLE=\"$labelStyleStr\" COLSPAN=\"$columnNum\">
    <table border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr>
    <td><IMG BORDER=\"0\" WIDTH=\"5\" HEIGHT=\"30\" SRC=\"/libImage/spaceHolder.gif\"></td><td><div style=\"$formTextStyleStr\">$emptyList</td></tr></table>
    </TD>
  </TR>
";
    }
    else {
      // entry labels
      $sortedIndexId = "_ScrollList_sortedIndex_$widgetid";
      $sortOrderId = "_ScrollList_sortOrder_$widgetid";

      $sortables = $this->getSortables();

      // make hidden fields
      $result .= $builder->makeHiddenField($sortedIndexId, $sortedIndex);
      $result .= $builder->makeHiddenField($sortOrderId, $sortOrder);

      if (!$this->headerRowHidden) {
        $result .= "<TR>\n";
      
        // put in place holder for select column
        if($hasSelectColumn) {
	  $result .= "    <TD ALIGN=\"CENTER\" STYLE=\"$labelStyleStr\">";
	  if ($this->isSelectAll()) {
            $result .= "<input type=\"hidden\" name=\"_entryIds\" value=\"" . implode(",",$entryIds) . "\"><input type=\"checkbox\" onClick=\"Javascript: top.code.ScrollList_selectAllSwitch(this)\">";
          } else {
            $result .= "<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">";
          }
          $result .=  "</TD>\n";
        }
      }

      for($i = 0; $i < $entryLabelNum; $i++) {
        if (!$this->headerRowHidden) {
	      $label = is_object($entryLabelObjs[$i]) ? $entryLabelObjs[$i]->toHtml($style->getSubstyle("labelLabel")) : "<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">";

	  if ($useActiveLabel == "true") {
	 	 $activeLabel = is_object($entryLabelObjs[$i]) ? $entryLabelObjs[$i]->toHtml($style->getSubstyle("activeLabelLabel")) : "<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">";
	  } else {
		 $activeLabel = is_object($entryLabelObjs[$i]) ? $entryLabelObjs[$i]->toHtml($style->getSubstyle("labelLabel")) : "<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">";
	  }

        }

	// get sorted widget
	$sortWidget = "";
	$render = 0;
	if(in_array($i, $sortables)) {
	  // use the correct order
	  // use the correct icon
	  if($sortedIndex == $i && $sortOrder == "ascending") {
	    $order = "descending";
	    $icon = $sortedAscendingIcon;
	    $render = 1;
	  }
	  else if($sortedIndex == $i && $sortOrder == "descending") {
	    $order = "ascending";
	    $icon = $sortedDescendingIcon;
	    $render = 1;
	  }
	  else if($sortedIndex != $i && $sortOrder == "ascending") {
	    $order = "ascending";
	    $icon = $sortAscendingIcon;
	    $render = 0;
	  }
	  else if($sortedIndex != $i && $sortOrder == "descending") {
	    $order = "descending";
	    $icon = $sortDescendingIcon;
	    $render = 0;
	  }

	 $mouseOver = "onMouseOver=\"return top.code.info_mouseOver('" . $i18n->interpolate("[[palette.sort_help]]") . "');\"";
	 $sortAlt = $i18n->interpolate("[[palette.sort]]");

     /* explicitly set action to the current script */
     global $SCRIPT_NAME;
	  $sortWidget = "<A HREF=\"javascript: if (document.$formId.onsubmit()) { document.$formId.action = '$SCRIPT_NAME'; document.$formId.$sortedIndexId.value = $i; document.$formId.$sortOrderId.value = '$order'; document.$formId.submit() }\" $mouseOver><IMG BORDER=\"0\" SRC=\"$icon\" ALT=\"$sortAlt\"></A>";
	  if(!$this->getArrowVisible())
		$sortWidget="&nbsp;";
	}

      	// get the width for this column
	$columnWidths = $this->getColumnWidths();
	$width = (is_array($columnWidths) && $columnWidths[$i] != "") ? "WIDTH=\"$columnWidths[$i]\"" : "";

        if (!$this->headerRowHidden) {
        	if ($render) {
        		$renderActiveColumnBackground = ($activeColumnBackground) ? "background=\"$activeColumnBackground\"" : "";
				$result .= "    <TD ALIGN=\"Left\" $renderActiveColumnBackground STYLE=\"$labelStyleStr\" $width><TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" width=\"100%\"><TR><TD><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" Height=\"20\" WIDTH=\"5\"></td><td nowrap>$activeLabel</td></tr></table></TD><TD align =\"right\">$sortWidget</TD></TR></TABLE></TD>\n";
        	} else {
        		$renderInactiveColumnBackground = ($inactiveColumnBackground) ? "background=\"$inactiveColumnBackground\"" : "";
	 			$result .= "    <TD ALIGN=\"Left\" $renderInactiveColumnBackground STYLE=\"$labelStyleStr\" $width><TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" width=\"100%\"><TR><TD><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" Height=\"20\" WIDTH=\"5\"></td><td nowrap>$label</td></tr></table></TD><TD align =\"right\">$sortWidget</TD></TR></TABLE></TD>\n";
        	}
        }
      }
      if (!$this->headerRowHidden) {
        $result .= "</TR>\n";
      }
    }

    // get alignments
    $alignments = $this->getAlignments();

    // entries
    for($i = $from; $i < $from+$length; $i++) {
      $result .= "<TR>";

      // add select column if necessary
      if($hasSelectColumn)
	$result .= "<TD CLASS=\"formField-$widgetid\">".$builder->makeCheckboxField($entryIds[$i], "true", "rw", $entriesSelected[$i])."</TD>";

      $entry = $entries[$i];
      for($j = 0; $j < $entryLabelNum; $j++) {
	// always show something. Otherwise, no background will be drawn
        $html = "<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">";
        if ($entry[$j]) 
	  $html = $entry[$j]->toHtml();
	if($html == "")
	  $html = "<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">";

	// find out alignment
	// Note: alignment can be empty
	$alignment = is_array($alignments) ? $alignments[$j] : "";

	// make padding
	$padding = "<TD><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\"></TD>";
	$leftPadding = ($alignment != "right") ? $padding : "";
	$rightPadding = ($alignment == "right" || $alignment == "center") ? $padding : "";

	if($leftPadding == "" && $rightPadding == "")
	  $result .= "<TD ALIGN=\"$alignment\" CLASS=\"formField-$widgetid\">$html</TD>";
	else
	  $result .= "<TD ALIGN=\"$alignment\" CLASS=\"formField-$widgetid\"><TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>$leftPadding<TD CLASS=\"formField-$widgetid\">$html</TD>$rightPadding</TR></TABLE></TD>";
      }
      if ($listDivider) {
        if ( $i < ( ($from+$length) - 1 ) ){
          $result .= "</tr><tr><td COLSPAN=\"$columnNum\"><div align=\"center\"><img src=\"$listDivider\" alt=\"\" border=\"0\"></div></td></tr>\n";
        } else {
          $result .= "</TR>";
        }
      } else {
      	$result .= "</TR>";
      }
    }

    // duplicate control row if length is greater than the limit
    $duplicateLimit = $this->getDuplicateLimit();
    if($length > $duplicateLimit)
      $result .= $controlRow;

    $result .= "
</TABLE>
</TD></TR></TABLE>
$shadowFrameEnd
";
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

