<?php
// Author: Patrick Bose
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: ClassicList.inc 3 2003-07-17 15:19:15Z will $

// description:
// ClassicList is an implementation of ScrollList->toHtml() which
// generates the look and feel of the original Cobalt products.
//
// applicability:
// NOTE: This implementation and style are scheduled for removal.

global $isClassicListDefined;
if($isClassicListDefined)
  return;
$isClassicListDefined = true;

  // description: turn the object into HTML form
	// param: scrollList: the ScrollList object
  // param: style: the style to show in (optional)
  // returns: HTML that represents the object or
  //      "" if pageIndex is out of range
  function toClassicListHtml($scrollList, $style = "") {
		$this = $scrollList;
    $widgetid = $this->widgetid;

    if($style == null || $style->getPropertyNumber() == 0) {
      $page = $this->getPage();
      $style = $this->getDefaultStyle($page->getStylist());
    }

    // find out style properties
    $borderColor = $style->getProperty("borderColor");
    $borderThickness = $style->getProperty("borderThickness");
    $controlAlign = $style->getProperty("controlAlign");
    $controlStyleStr = $style->toBackgroundStyle("controlCell");
    $controlLabelStyleStr = $style->toTextStyle("controlLabel");
    $formFieldStyleStr = $style->toBackgroundStyle("entryCell").$style->toTextStyle("entryCell");
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
    $entryNum = count($entries);

    $sortedIndex = $this->getSortedIndex();
    $sortOrder = $this->getSortOrder();

    // sort the entries
    if($sortedIndex != -1) 
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
    $page = $this->getPage();
    $i18n = $page->getI18n();
    $form = $page->getForm();
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
    $entryCount = "<TD ALIGN=\"RIGHT\" NOWRAP WIDTH=\"20%\"><FONT STYLE=\"$controlLabelStyleStr\">".$i18n->interpolate($messageTag, array("count" => $entryNum))."</FONT></TD>";

    // make page selection widget
    if($entryNum <= $length)
      $pageSelect = "";
    else {
      // make button for selection
      $pageIndexId = "_ScrollList_pageIndex_$widgetid";

      $multiButton = new MultiButton($page);
      $pageIndexCount = 0;
      for($i = 0; $i < $entryNum; $i += $desiredLength) {
	// find the range
	$fromEntry = $desiredLength*$pageIndexCount+1;
	$toEntry = $desiredLength*($pageIndexCount+1);
	$toEntry = ($toEntry <= $entryNum) ? $toEntry : $entryNum;

	// add action
        $multiButton->addAction("javascript: document.$formId.$pageIndexId.value = $pageIndexCount; document.$formId.submit()", $i18n->get("entryRange", "palette", array("from" => $fromEntry, "to" => $toEntry)));

	// next page
	$pageIndexCount++;
      }
      $multiButton->setSelectedIndex($pageIndex);

      // make hidden field to store page index
      $result .= $builder->makeHiddenField($pageIndexId, $pageIndex);

      $pageSelect .= "<TD ALIGN=\"RIGHT\" NOWRAP WIDTH=\"20%\">".$multiButton->toHtml()."</TD>";
    }

    // make buttons
    $buttons = $this->getButtons();
    $allButtons = "";
    if(count($buttons) > 0) {
      $allButtons .= "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>";

      for($i = 0; $i < count($buttons); $i++) {
	if($i > 0)
	  $allButtons .=  "<TD>&nbsp;</TD>";
	$allButtons .= "<TD>".$buttons[$i]->toHtml()."</TD>";
      }

      $allButtons .= "</TR></TABLE>";
    }

    if (!$this->entryCountHidden) {
      $controlRow = "
  <TR>
    <TD STYLE=\"$controlStyleStr\" COLSPAN=\"$columnNum\"><TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" WIDTH=\"100%\"><TR><TD ALIGN=\"$controlAlign\">$allButtons</TD>$pageSelect $entryCount</TR></TABLE></TD>
  </TR>
";
    } else {
      $controlRow = "";
    }

    $labelObj = $this->getLabel();
    $label = $labelObj->toHtml($style->getSubstyle("titleLabel"));
    $result .= "
<STYLE TYPE=\"text/css\">
.formField-$widgetid \{$formFieldStyleStr}
</STYLE>
<TABLE BORDER=\"1\" CELLPADDING=\"0\" CELLSPACING=\"0\" BGCOLOR=\"$borderColor\" WIDTH=\"$width\">
  <TR>
    <TD ALIGN=\"$titleAlign\" STYLE=\"$titleStyleStr\" COLSPAN=\"$columnNum\"><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\">$label</TD>
  </TR>
$controlRow
";

    if($length == 0) {
      $emptyList = ($this->emptyMsg == "") ? $i18n->get("emptyList", "palette") : $i18n->interpolate($this->emptyMsg);
      $result .= "
  <TR>
    <TD STYLE=\"$labelStyleStr\" COLSPAN=\"$columnNum\">$emptyList</TD>
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
            $result .= "&nbsp;";
          }
          $result .=  "</TD>\n";
        }
      }

      for($i = 0; $i < $entryLabelNum; $i++) {
        if (!$this->headerRowHidden) {
	  $label = is_object($entryLabelObjs[$i]) ? $entryLabelObjs[$i]->toHtml($style->getSubstyle("labelLabel")) : "&nbsp;";

			$mouseOver = $entryLabelObjs[$i]->getDescription();
			$mouseOver = $i18n->interpolateJs($mouseOver);
			$entryLabelObjs[$i]->setDescription("");
			$label = $entryLabelObjs[$i]->toHtml( $style->getSubstyle("labelLabel") );
			$entryLabelObjs[$i]->setDescription( $mouseOver );
      $label = "<A HREF=\"javascript: void 0\" onMouseOver=\"return top.code.info_mouseOver('$mouseOver')\" onMouseOut=\"return top.code.info_mouseOut();\"><IMG BORDER=\"0\" SRC=\"/.cobalt/images/smallest_help_but\"></A><FONT SIZE=\"+1\"> $label </FONT>";		
        }

	// get sorted widget
	$sortWidget = "";
	if(in_array($i, $sortables)) {
	  // use the correct order
	  // use the correct icon
	  if($sortedIndex == $i && $sortOrder == "ascending") {
	    $order = "descending";
	    $icon = $sortedAscendingIcon;
	  }
	  else if($sortedIndex == $i && $sortOrder == "descending") {
	    $order = "ascending";
	    $icon = $sortedDescendingIcon;
	  }
	  else if($sortedIndex != $i && $sortOrder == "ascending") {
	    $order = "ascending";
	    $icon = $sortAscendingIcon;
	  }
	  else if($sortedIndex != $i && $sortOrder == "descending") {
	    $order = "descending";
	    $icon = $sortDescendingIcon;
	  }

	  $sortWidget = "<A HREF=\"javascript: document.$formId.$sortedIndexId.value = $i; document.$formId.$sortOrderId.value = '$order'; document.$formId.submit()\"><IMG BORDER=\"0\" SRC=\"$icon\"></A>";
	}

      	// get the width for this column
	$columnWidths = $this->getColumnWidths();
	$width = (is_array($columnWidths) && $columnWidths[$i] != "") ? "WIDTH=\"$columnWidths[$i]\"" : "";

        if (!$this->headerRowHidden) {
	  $result .= "    <TD ALIGN=\"CENTER\" STYLE=\"$labelStyleStr\" $width><TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR><TD>$label</TD><TD>$sortWidget</TD></TR></TABLE></TD>\n";
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
        $html = "&nbsp;";
        if ($entry[$j]) 
	  $html = $entry[$j]->toHtml();
	if($html == "")
	  $html = "&nbsp;";

	// find out alignment
	// Note: alignment can be empty
	$alignment = is_array($alignments) ? $alignments[$j] : "";

	// make padding
	$padding = "<TD><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\"></TD>";
	$leftPadding = ($alignment == "" || $alignment == "left") ? $padding : "";
	$rightPadding = ($alignment == "right") ? $padding : "";

	if($leftPadding == "" && $rightPadding == "")
	  $result .= "<TD ALIGN=\"$alignment\" CLASS=\"formField-$widgetid\">$html</TD>";
	else
	  $result .= "<TD ALIGN=\"$alignment\" CLASS=\"formField-$widgetid\"><TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>$leftPadding<TD CLASS=\"formField-$widgetid\">$html</TD>$rightPadding</TR></TABLE></TD>";
      }
      $result .= "</TR>\n";
    }

    // duplicate control row if length is greater than the limit
    $duplicateLimit = $this->getDuplicateLimit();
    if($length > $duplicateLimit)
      $result .= $controlRow;

    $result .= "
  </TR>
</TABLE>
";
    return $result;
  }
