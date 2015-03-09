<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ScrollList
 *
 * A Class that renders a PJAX driven, sortable and searchable data table.
 *
 */


class ScrollList extends HtmlComponentFactory {
  //
  // private variables
  //

  var $entryLabels;
  var $entries;
  var $i18n;
  var $BxPage;

  var $alignments;
  var $buttons;
  var $duplicateLimit;
  var $entriesSelected;
  var $entryCountTagSingular;
  var $entryCountTagPlural;
  var $entryIds;
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
  var $Display;

  //
  // public methods
  //

  // description: constructor
  // param: page: the BxPage object this object lives in
  // param: id: the identifier in string
  // param: label: a label object for the list
  // param: entryLabels: an array Label object for the entries
  // param: entries: an array containing the data object for the actual entries
  // param: sortables: an array of indexes of the sortable components. Optional
  // param: $i18n         Var with the current i18n ID
  function ScrollList(&$BxPage, $id, $entryLabels = array(), $entries = array(), $sortables = array(), $i18n = array()) {

    // superclass constructor
    $this->BxPage = $BxPage;

    $this->i18n = $i18n;
    $this->entries = $entries;

    $this->getBxPage($BxPage);

    $this->Display = NULL;
    $this->setId($id);
    $this->setEntryLabels($entryLabels);
    $this->setSortables($sortables);
   
    $this->setArrowVisible(true);

    $this->entryIds = array();
    $this->entriesSelected = array();
    $this->columnWidths = array();
    $this->emptyMsg = "";

    $this->buttons = array();

    $this->errors = array();
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

  // description: set the message to be displayed when the list is empty
  // param: msg: an I18n tag of the form [[domain.messageId]] for interpolation
  function setEmptyMessage($msg = "") {
     $this->emptyMsg = $msg;
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

  // description: add an entry to the list
  // param: entry: an array of objects that consist the entry
  // param: entryId: an unique ID for the entry. Optional.
  //     If supplied, the entry can be selected
  // param: entrySelected: true if the entry is selected, false otherwise.
  //     Optional
  // param: entryNumber: the index of the entry on the list. Optional. If not
  //     supplied, the entry is appended to the end of the list
  function addEntry($entry, $entryId = "", $entrySelected = false, $entryIndex = -1) {
    if (!isset($this->entries['0']['0'])) {
      $entryIndex = "0";
    }
    else {
      $entryIndex = count($this->entries['0']);
    }

    if (is_array($entry)) {
      $num_of_labels = count($this->entryLabels);
      for ($i=0; $i < $num_of_labels; $i++) { 
        if (is_object($entry[$i])) {
          $ObjHtml = $entry[$i]->toHtml();
          $this->entries[$i][$entryIndex] = $ObjHtml;
        }
        else {
          $this->entries[$i][$entryIndex] = $entry[$i];
        }
      }
    }
    $this->entryIds[$entryIndex] = $entryId;
    $this->entriesSelected[$entryIndex] = $entrySelected;
  }

  // description: get the number of entries in the list
  // returns: an integer
  // see: setEntryNum(), addEntry()
  function getEntryNum() {
    if($this->entryNum != -1) {
      return $this->entryNum;
    }
    else {
      return count($this->getEntries());
    }
  }

  // description: set the column widths for items in entries
  // param: widths: an array of widths in numbers (e.g. 100), percentage
  //     strings (e.g. 25%) or "". "" or empty elements means no defined width
  // see: getColumnWidths()
  function setColumnWidths($columnWidths) {
    $this->columnWidths = $columnWidths;
  }

  // description: get the column widths for items in entries
  // returns: an array of widths
  // see: setColumnWidths()
  function getColumnWidths() {
    return $this->columnWidths;
  }
  
  // description: set the width of the scroll list
  // param: width: the width of the scroll list in pixels 
  // see: getWidth()
  function setWidth($width) {
    $this->width = $width;
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

  // Disable the Search function of the ScrollList:
  function setSearchDisabled($SearchDisabled) {
    $this->SearchDisabled = $SearchDisabled;
  }

  // Enable AutoWidth for Columns:
  function enableAutoWidth($AutoWidth) {
    $this->AutoWidth = $AutoWidth;
  }

  // Disable the Pagination function of the ScrollList:
  function setPaginateDisabled($PaginateDisabled) {
    $this->PaginateDisabled = $PaginateDisabled;
  }

  // Disable display of the number of entries in the datatable:
  function setInfoDisabled($HideInfo) {
    $this->HideInfo = $HideInfo;
  }

  // Disable the Pagination selector pulldown of the ScrollList:
  function setSelectorDisabled($SelectorDisabled) {
    $this->SelectorDisabled = $SelectorDisabled;
  }

  // description: see if sorting is done by the list
  // returns: a boolean
  // see: setSortEnabled()
  function isSortEnabled() {
    return $this->sortEnabled;
  }

  // Defines if ability to sort or not is allowed. TRUE = sorting allowed. FALSE: No sorting. Selective ability
  // to sort columns is also possible by passing an Array with numeric IDs of columns where we will NOT show a 
  // sort selector ('asc' or 'desc') or any kind.
  function setSortDisabled($SortDisabled) {
    $this->setSortDisabled = $SortDisabled;
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

  // description: How many items to display on a single page if pagination is enabled.
  // returns: an number
  // see: setSortables()
  function getDisplay() {
    return $this->Display;
  }

  // description: Set how many to display on a single page if pagination is enabled.
  // param: sortables: an array of indexes of the sortable components
  // see: getSortables()
  function setDisplay($num) {
    $this->Display = $num;
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

    include_once('BXCollator.php');
    $collator = new BXCollator();
    $collator->sort($keys, $entries);

    if($sortOrder == "descending")
      $entries = array_reverse($entries);
  }  

  // description: turn the object into HTML form
  // param: style: the style to show in (optional)
  // returns: HTML that represents the object or
  //      "" if pageIndex is out of range
  function toHtml($style = "") {

    // Special case: We have no entries. Construct an empty entries array with the right number
    // of columns. We know how many columns we have thanks to the number of entryLabels:
    if (empty($this->entries)) {
      $num_of_labels = count($this->entryLabels);
      for ($i=0; $i < $num_of_labels; $i++) { 
        $this->entries[$i] = array("0" => "");
      }
    }

    $result = "";
    // make buttons
    $buttons = $this->getButtons();
    $allButtons = "";
    if(count($buttons) > 0) {
      $result .= '<div class="button_bar clearfix">';
      for($i = 0; $i < count($buttons); $i++) {
        $result .= $buttons[0]->toHtml();
      }
      $result .= '</div>';
    }


    $result .= '
                <div class="box grid_16 single_datatable">
                        <div id="' . $this->id . '" class="no_margin">

                              <table class="datatable clearfix">
                                <thead>
                                  <tr>' . "\n";

    foreach($this->entryLabels as $key) {
      $result .= '                                    <th class="datatable_head"><label for="' . $key . '" title="' . $this->i18n->getWrapped($key . '_help') . '" class="tooltip hover">' . $this->i18n->getHtml($key) . '</label></th>' . "\n";
    }

    $result .= '
                                  </tr>
                                </thead>
                                <tbody>' . "\n";
    $result .= '                <tr>' . "\n";                                

    $numColumns = count(array_keys($this->entries));

    if ((count(array_values($this->entries))) == "0") {
      $numRows = "0";
    }
    else {
      if (is_array((array_shift(array_values($this->entries))))) {
        $numRows = count(array_values(array_shift(array_values($this->entries))));
      }
      else {
        $numRows = "0";
      }
    }

    $x_numRows = "0";
    $x_numColumns = "0";

    // get alignments
    $alignments = $this->getAlignments();

    while ($x_numRows < $numRows) {
      if ($x_numColumns == $numColumns) { 
        $x_numColumns = "0"; 
        $result .= '                </tr>' . "\n";
        if ($x_numRows < $numRows) {
           $result .= '               <tr>' . "\n";
        }
      }

      while ($x_numColumns != $numColumns) {

        // find out alignment
        // Note: alignment can be empty
        $alignment = is_array($alignments) ? $alignments[$x_numColumns] : "left";    

        // get the width for this column (if specified):
        if (isset($this->columnWidths)) {
          $columnWidths = $this->getColumnWidths();
          if (isset($columnWidths[$x_numColumns])) {
            $width = $columnWidths[$x_numColumns];
            if (preg_match('/(.*)%/', $width)) {
              $suffix = "";
            }
            else {
              $suffix = "px";
            }
            $result .= '                  <td style="vertical-align:middle; text-align:' . $alignment . '; width: ' . $width . $suffix . ';">' . $this->entries[$x_numColumns][$x_numRows] . '</td>' . "\n";
          }
          else {
            $result .= '                  <td style="vertical-align:middle; text-align:' . $alignment . ';">' . $this->entries[$x_numColumns][$x_numRows] . '</td>' . "\n";
          }
        }
        else {
          $result .= '                  <td class="' . $alignment . ' dt_' . $x_numColumns . '">' . $this->entries[$x_numColumns][$x_numRows] . '</td>' . "\n";
        }
        $x_numColumns++;
      }
      $x_numRows++;
    }

    $result .= '                </tr>' . "\n";
    $result .= '
                                  </tbody>
                                </table>
                        </div>
                </div>';

    // Sort the entries
    if(!isset($this->sortedIndex)) {
      $this->sortedIndex = "0";
    }

    if ($this->sortOrder == "ascending") {
      $this->sortOrder = "asc";
    }
    else {
      $this->sortOrder = "desc";
    }

    $sorting = '
                  aaSorting: [
                          [' . $this->sortedIndex . ', "' . $this->sortOrder . '"]
                  ],    ' . "\n";

    // Disable sort ability for selected fields:
    $sortdisabler = '';
    if ((isset($this->setSortDisabled)) && ($this->setSortDisabled == TRUE) && (!is_array($this->setSortDisabled))) {
      $SortDisabled = "\n" . '                  "bSort": false,';
      $this->setSortDisabled = "";
    }
    else {
      $SortDisabled = "\n" . '                  "bSort": true,';
    }
    if ((isset($this->setSortDisabled)) && (is_array($this->setSortDisabled))) {
      $num = 0;
      $sortdisabler .= '                  "aoColumns": [' . "\n";
      $sortdis_integr = array();
      foreach($this->entryLabels as $key) {
        if (in_array($num, $this->setSortDisabled)) {
          $sortdisabler .= '                    { "asSorting": [ "" ] },' . "\n";
          $sortdis_integr[$num] = '"asSorting": [ "" ] ';
        }
        else {
          $sortdisabler .= '                    null,' . "\n";
          $sortdis_integr[$num] = '';
        }
        $num++;
      }
      $sortdisabler .= '                  ],';
    }

    // Search box:
    if ((isset($this->SearchDisabled)) && ($this->SearchDisabled == TRUE)) {
      $filter = "\n" . '                  "bFilter": false,';
    }
    else {
      $filter = ''; 
    }

    // AutoWidth of Columns:
    if ((isset($this->AutoWidth)) && ($this->AutoWidth == TRUE)) {
      $AutoWidth = "\n" . '                  bAutoWidth: true,';
    }
    else {
      $AutoWidth = "\n" . '                  bAutoWidth: false,';
    }

    // HideInfo of number of entries:
    if ((isset($this->HideInfo)) && ($this->HideInfo == TRUE)) {
      $HideInfo = "\n" . '                  bInfo: false,';
    }
    else {
      $HideInfo = "\n" . '                  bInfo: true,';
    }

    // Pagination:
    if ((isset($this->PaginateDisabled)) && ($this->PaginateDisabled == TRUE)) {
      $paginate = "\n" . '                  "bPaginate": false,';
    }
    else {
      $paginate = '';
    }

    // Pulldown for pagination:
    if ((isset($this->SelectorDisabled)) && ($this->SelectorDisabled == TRUE)) {
      $LengthPulldown = "\n" . '                  "bLengthChange": false,';
    }
    else {
      $LengthPulldown = "\n" . '                  "bLengthChange": true,';
    }


    // get the width for this column (if specified):
    $oColumns = '';
    if (isset($this->columnWidths)) {
      $columnWidths = $this->getColumnWidths();
      if (isset($columnWidths[0])) {
        $oColumns = "\n" . '                  "aoColumns": [';
        $ncx = "0";
        $opener = "0";
        foreach (array_keys($this->entries) as $value) {
          if (isset($sortdis_integr[$ncx])) {
            $opener = "1";
            if ($sortdis_integr[$ncx] != "") {
              $oColumns .= "\n" . '                   { "sWidth": "' . $columnWidths[$value] . '", ' . $sortdis_integr[$ncx] . '}';
            }
            else {
              $oColumns .= "\n" . '                   { "sWidth": "' . $columnWidths[$value] . '" }';
            }
            if ($ncx < $numColumns-1) {
//              echo "NCX: - $ncx - $numColumns <br>";
              $oColumns .= ',' . "\n";
            }
          }
          else {
            $oColumns = "\n";
          }          
          $ncx++;
        }
        if ($opener == "1") {
          $oColumns .= '                  ],' . "\n";
        }
        // Disable sortdisabler as we already have it covered:
        $sortdisabler = "";
      }
    }

    // Handle how many items to display per page if pagination is enabled
    // and something else than the default is used:
    $iDisplayLength = '';
    if ((!isset($this->PaginateDisabled)) || ($this->PaginateDisabled == FALSE)) {
      if ($this->getDisplay() != NULL) {
        $iDisplayLength = '                  "iDisplayLength": ' . $this->getDisplay() . ',' . "\n";
      }
    }

    // This outright overrides the adminicaDataTables() function from '/.adm/scripts/adminica/adminica_all.js', 
    // as we need it to be more flexible and need it localized, too:
    $this->BxPage->setExtraHeaders('<script>

    /*properties
        aaSorting, accordion, addClass, aoColumns, asSorting, bAutoWidth, bInfo, 
        bJQueryUI, bLengthChange, bRetrieve, bScrollCollapse, bSort, bSortClasses, 
        change, css, dataTable, display, fn, fnAdjustColumnSizing, fnInitComplete, 
        length, oLanguage, oPaginate, panel, ready, resize, sFirst, sInfo, 
        sInfoEmpty, sInfoFiltered, sInfoPostFix, sLast, sLengthMenu, sNext, 
        sPaginationType, sPrevious, sProcessing, sScrollX, sSearch, sUrl, sWidth, 
        sZeroRecords, show, tabs, uniform, visibility
    */

      function ' . $this->id . '_extraDataTables() {
          if ($(".datatable").length > 0 && $.fn.dataTable) {
              var e = $("#' . $this->id . ' .datatable").dataTable({
                  bJQueryUI: !0,
                  sScrollX: "",
                  "bScrollAutoCss": false,
                  bSortClasses: !1,' .
                  $sorting .
                  $iDisplayLength . 
                  $sortdisabler . 
                  $AutoWidth . 
                  $oColumns . 
                  $HideInfo . '
                  bScrollCollapse: !0,' .
                  $filter . '
                  sPaginationType: "full_numbers",' .
                  $paginate . '
                  bRetrieve: !0,' .
                  $LengthPulldown . 
                  $SortDisabled . '

                  "oLanguage": {
                    "sProcessing":   "' . $this->i18n->get("[[palette.sProcessing]]") . '",
                    "sLengthMenu":   "' . $this->i18n->get("[[palette.sLengthMenu]]") . '",
                    "sZeroRecords":  "' . $this->i18n->get("[[palette.sZeroRecords]]") . '",
                    "sInfo":         "' . $this->i18n->get("[[palette.sInfo]]") . '",
                    "sInfoEmpty":    "' . $this->i18n->get("[[palette.sInfoEmpty]]") . '",
                    "sInfoFiltered": "' . $this->i18n->get("[[palette.sInfoFiltered]]") . '",
                    "sInfoPostFix":  "",
                    "sSearch":       "' . $this->i18n->get("[[palette.sSearch]]") . '",
                    "sUrl":          "",
                    "oPaginate": {
                        "sFirst":    "' . $this->i18n->get("[[palette.sFirst]]") . '",
                        "sPrevious": "' . $this->i18n->get("[[palette.sPrevious]]") . '",
                        "sNext":     "' . $this->i18n->get("[[palette.sNext]]") . '",
                        "sLast":     "' . $this->i18n->get("[[palette.sLast]]") . '"
                    }
                  },

                  fnInitComplete: function () {
                      $("#' . $this->id . ' .dataTables_length > label > select").uniform();
                      $("#' . $this->id . ' .dataTables_filter input[type=text]").addClass("text");
                      $(".datatable").css("visibility", "visible");
                  }
              });

              $(".tabs").tabs({
                  show: function (e, t) {
                      var n = $("div.dataTables_scrollBody > table", t.panel).dataTable();
                      if (n.length > 0) {
                          n.fnAdjustColumnSizing();
                          $(".tabs div.dataTables_scroll").css({
                              display: "none",
                              visibility: "visible"
                          }).show();
                      }
                  }
              });

              $(".content_accordion").accordion({
                  change: function (e, t) {
                      var n = $("div.dataTables_scrollBody > table", t.panel).dataTable();
                      if (n.length > 0) {
                          n.fnAdjustColumnSizing();
                          $(".content_accordion div.dataTables_scroll").css({
                              display: "none",
                              visibility: "visible"
                          }).show();
                      }
                  }
              });

              $(window).resize(function () {
                  e.fnAdjustColumnSizing();
                  t.fnAdjustColumnSizing();
                  n.fnAdjustColumnSizing(); });
          }
      }

      $(document).ready(function () {
          ' . $this->id . '_extraDataTables();
      });
    </script>');

    return $result;
  }
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>