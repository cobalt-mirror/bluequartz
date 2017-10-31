<?php
// Author: Kevin K.M. Chiu
// $Id: PagedBlock.php

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
//
// Notes by Michael Stauber:
//
// This class has been ported to BlueOnyx 5200R and now uses jQuery and CSS for
// styling instead of JavaScript and plain HTML. This is one of the more complex
// classes for GUI elements and is used extensively throughout the GUI. The 
// complexity and the nature of the changes actually would warrant a complete
// rewrite from scratch, as there are now many dormant routines, variables and
// arrays in this mess. In fact in porting this to the "new" BlueOnyx I 
// deliberately broke some of the original functionality. I hope to clean this up
// in the longer run, but if there are things in here which don't work or don't
// make sense to you, then that's my fault.

global $isPagedBlockDefined;
if($isPagedBlockDefined)
  return;
$isPagedBlockDefined = true;

include_once("uifc/FormFieldBuilder.php");
include_once("uifc/Block.php");

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
  var $defaultPage;
  var $columnWidths;
  var $hideEmptyPages;
  var $Label;
  var $noErrorDisplay;
  var $FormDisabled;
  var $DivHeight;
  
  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this block is in
  // param: id: an unique ID of the block in string
  // param: label: a Label object for the block title. Optional
  public function PagedBlock($page, $id, $label = "", $selectedId = "") {
    // superclass constructor

    $this->Block($page, $label);

    $this->BxPage = $page;

    $this->setId($id);

    $this->setDefaultPage();

    $this->sideTabs = FALSE;

    $this->Label = $label;

    ;

    // PagedBlock has a built in area where errror messages are shown.
    // We inform BxPage of this, so it won't show the errors separately:
    if ($this->getDisplayErrors() == TRUE) {
      $this->BxPage->HaveErrorMsgDisplayArea(TRUE);
    }

    // set selected ID from internal variable
    $variableName = "_PagedBlock_selectedId_$id";
    $this->setSelectedId($selectedId);

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

    // Toggle elements:
    $this->elements = array();

  }

  // Disables inline display of error messages:
  function setDisplayErrors($errorOnOff) {
    $this->noErrorDisplay = $errorOnOff;
  }

  // Returns status of inline display of error messages:
  function getDisplayErrors() {
    if (!isset($this->noErrorDisplay)) {
      $this->noErrorDisplay = TRUE;
    }
    return $this->noErrorDisplay;
  }

  // Sets the current label
  function setCurrentLabel($label) {
    $this->Label = $label;
  }

  // Returns the current label
  function getCurrentLabel() {
    if (!isset($this->Label)) {
      $this->Label = $id;
    }
    return $this->Label;
  }

  // The next couple of functions define if a grabber, toggle or "open in new window" icon are visible in the header:
  public function setGrabber($grabber) {
    $this->elements["grabber"] = $grabber;
  }

  public function setToggle($toggle) {
    $this->elements["toggle"] = $toggle;
  }

  public function setSelf($toggle) {
    $this->elements["self"] = $toggle;
  }

  public function setWindow($window) {
    $this->elements["window"] = $window;
  }

  public function setFormDisabled($formDis) {
    $this->FormDisabled = $formDis;
  }

  public function getFormDisabled() {
    if (!isset($this->FormDisabled)) {
      return FALSE;
    }
    else {
      return $this->FormDisabled;
    }
  }

  public function setShowAllTabs($show_all_tabs) {
    $this->elements["show_all_tabs"] = $show_all_tabs;
  }
  
  // sideTabs (if set to TRUE) shows the tabbing of a PagedBlock on the lefthand side instead of on top.
  // However: Due to stylistic reasons it is only good for displaying info. NOT FOR FORM DATA!
  // It removes both the heading line with the grabber, toggle, setShowAllTabs and "open in new window" 
  // AND will remove any buttons that you might have added. So use this with caution and only on pages 
  // where you display informational data without the need to submit something back!
  //
  // But as a neat side effect you can also use misuse it: Try to use a single tab with a single
  // formField and setSideTabs(TRUE). It will show a stand alone FormField nicely formatted. I promise
  // that was purely by accident! ;-)
  public function setSideTabs($tabs) {
    $this->sideTabs = $tabs;
  }

  // Sets the current DivHeight:
  function setDivHeight($height) {
    $this->DivHeight = $height;
  }

  // Returns the DivHeight:
  function getDivHeight() {
    if (!isset($this->DivHeight)) {
      $this->DivHeight = '0';
    }
    return $this->DivHeight;
  }

  // description: get the mark for marking the end of a HTML section
  //     specifically for a page. This is useful for adding page specific HTML
  // param: pageId: the ID of the page in string
  // returns: the mark in string
  // see: getStartMark()
  public function getEndMark($pageId) {
    $selectedId = $this->getSelectedId();
    if($pageId == $selectedId)
      return "";
    else return " -->";
  }

  // description: get all the form fields of the block
  // returns: an array of FormField objects
  // see: addFormField()
  public function &getFormFields() {
    return $this->formFields;
  }

  // description: add a form field to this block
  // param: formField: a FormField object
  // param: label: a label object. Optional
  //     hidden form fields are not shown and therefore do not need labels
  // param: pageId: the ID of the page the form field is in
  //     Optional if there is only one page
  // see: getFormFields()
  public function addFormField(&$formField, $label = "", $pageId = "", $errormsg = false) {
    $this->formFields[] =& $formField;
    $this->formFieldLabels[$formField->getId()] = $label;

    // Pass the information from the LabelObject's labels to the class BxPage() so that it can store them for us
    // until we need to pull that information:
    if (isset($label->page->Label)) {
      if (isset($label->page->BXLabel[$formField->getId()])) {
        $lbl = array_keys($label->page->BXLabel[$formField->getId()]);
        $dsc = array_values($label->page->BXLabel[$formField->getId()]);
        $this->BxPage->setLabel($formField->getId(), $lbl[0], $dsc[0]);
      }
      else {
        $this->BxPage->setLabel($formField->getId(), $label->page->Label['label'], $label->page->Label['description']);
      }
    }
    else {
      $this->BxPage->setLabel($formField->getId(), "", "");
    }

    if($pageId=="") {
      $pageId=$this->defaultPage;
    }
    $this->formFieldPageIds[$formField->getId()] = $pageId;
    if ($errormsg) {
      $this->formFieldErrors[$formField->getId()] = new Error($errormsg);
    }
  }

  public function setDefaultPage($id=""){
    // There are cases where we want to go directly to a specific tab. 
    // We can do so and can override the setDefaultPage() command, provided we suffix the URL
    // of the target URL with the ID of the menu item and the anchor of the tab. 
    // Example URL: /email/emailsettings?mx#tabs-3 where "mx" is the ID of the tab, which happens 
    // to be tab #3. The settings of setDefaultPage() will then be ignored.
    if ((isset($_SERVER['QUERY_STRING'])) && ($_SERVER['QUERY_STRING'] != "")) {
      $this->defaultPage=$_SERVER['QUERY_STRING'];
    }
    else {
      $this->defaultPage=$id;
    }
  }

  public function getDefaultPage() {
    if ((isset($_SERVER['QUERY_STRING'])) && ($_SERVER['QUERY_STRING'] != "")) {
      $this->defaultPage = $_SERVER['QUERY_STRING'];
    }
    return $this->defaultPage;
  }

  public function setFormFieldError($id, &$error) {
    $this->formFieldErrors[$id] =& $error;
  }

  // for backward compatibility, plus I don't feel like
  // finding every call to process_errors tonight
  public function process_errors(&$errors, $mapping = array()) {
    $this->processErrors($errors, $mapping);
  }
  
  // given an array of error objects, sorts them out for later display.
  public function processErrors($errors, $mapping = array()) {
    /* reset the general errors ! */
    $this->generalErrors = array();
    for ($i = 0; $i < count($errors); $i++) {
      $error = $errors[$i];
      if (! $error) {
        continue;
      }
      if (method_exists($error, 'getKey')) {
        $key = $error->getKey();
      }
      // remap schema attribute name to localized field name:
      if ($key && $mapping[$key]) {
        $key = $mapping[$key];
      }
      if (false && $key) {
        // if ( $error->getKey() && !preg_match("/^\[\[/", $error->vars["key"])) 
        $this->setFormFieldError($key, $error);
      } 
      else {
       $this->generalErrors[] = $error;
      }
    }
  }

  // description: get all dividers added to the block
  // returns: an array of Label objects
  // see: addDivider()
  public function getDividers() {
    return $this->dividers;
  }

  // description: add a divider
  // param: label: a label object. Optional
  // param: pageId: the ID of the page the form field is in
  //     Optional if there is only one page
  // see: getDividers()
  public function addDivider($label = "", $pageId = "") {

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
  public function getFormFieldLabel($formField) {
    return $this->formFieldLabels[$formField->getId()];
  }
  
  // getFormFieldError: get the error message (if any) associated 
  // with a form field.
  public function &getFormFieldError(&$formField) {
    $page = $this->getPage();
    $i18n = $page->getI18n();
    $tmperr =& $this->formFieldErrors[$formField->getId()];
    if (!$tmperr) { 
      $nix = "";
      return $nix; 
    }
    return $i18n->interpolate($tmperr->getMessage(), $tmperr->getVars());
  }

  // description: get the page ID of a form field
  // param: formField: a FormField object
  // returns: page ID in string
  public function getFormFieldPageId($formField) {
    return $this->formFieldPageIds[$formField->getId()];
  }

  // description: get the widths of label and form field
  // returns: an array of widths in integer (pixel) or string (e.g. "60%"). The
  //     first element is for label and the second element is for form field.
  // see: setColumnWidths()
  public function getColumnWidths() {
    return $this->columnWidths;
  }

  // description: set the widths of label and form field
  // param: widths: an array of widths in integer (pixel) or string (e.g.
  //     "60%"). The first element is for label and the second element is for
  //     form field.
  // see: getColumnWidths()
  public function setColumnWidths($widths = array("165", "385")) {
    $this->columnWidths = $widths;
  }

  // description: get the ID of the block
  // returns: a string
  // see: setId()
  public function getId() {
    return $this->id;
  }

  // description: set the ID of the block
  // param: Id: a string
  // see: getId()
  public function setId($id) {
    $this->id = $id;
  }

  // description: get all the page IDs
  // returns: an array of IDs in string
  // see: addPage()
  public function getPageIds() {
    return $this->pageIds;
  }

  // description: get the label of a page
  // param: pageId: the ID of the page
  // returns: a Label object
  // see: addPage()
  public function getPageLabel($pageId) {
    return $this->pageLabels[$pageId];
  }

  // description: add a page into the paged block
  // param: pageId: the ID of the page in string
  // param: label: a Label object for the page
  // see: getPageId(), getPageLabel()
  public function addPage($pageId, $label) {
    $this->pageIds[] = $pageId;
    $this->pageLabels[$pageId] = $label;

    // set selected ID to default
    if($this->getSelectedId() == "") {
      $this->setSelectedId($pageId);
    }
  }

  // description: get the ID of the selected page
  // returns: a string
  // see: setSelectedId()
  public function getSelectedId() {
    return $this->selectedId;
  }

  // description: set the ID of the selected page
  // param: selectedId: a string
  // see: getSelectedId()
  public function setSelectedId($selectedId) {
    $this->selectedId = $selectedId;
    return $this->getSelectedId();
  }

  // description: get the mark for marking the start of a HTML section
  //     specifically for a page. This is useful for page specific HTML
  // param: pageId: the ID of the page in string
  // returns: the mark in string
  // see: getEndMark()
  public function getStartMark($pageId) {
    $selectedId = $this->getSelectedId();
    if($pageId == $selectedId) {
      return "";
    }
    else {
      return "<!-- ";
    }
  }

  // creates javascript to report non-field-specific errors.
  public function reportErrors() {
    global $REQUEST_METHOD;
    if ($REQUEST_METHOD == "GET") { return ""; }
    $page = $this->getPage(); $i18n = $page->getI18n();
    $result = "";
    if (count($this->generalErrors) > 0) {
        $errorInfo = "";
        for ($i = 0; $i < count($this->generalErrors); $i++) {
            $error = $this->generalErrors[$i];
            $errMsg = "";
            if (get_class($error) == "CceError" && ($tag = $error->getKey())) {
                $tag .= "_invalid";
                $errMsg = $i18n->getJs( $tag, "", $error->getVars());
                if ($errMsg === $tag) { $errMsg = ""; }
            }
            if ($errMsg === "") {
                $errMsg = $i18n->interpolateJs($error->getMessage(), $error->getVars());
            }
            $errorInfo .= $errMsg . "<BR>";
        }
//        $result = "<script language=\"javascript\">\n"
//                . "var errorInfo = '$errorInfo';\ntop.code.info_show(errorInfo, \"error\");"
//                . "</script>\n";
    } 
    else {
//        $result = "<script language=\"javascript\">\n"
//                . "top.code.info_show(\"\", null);\n" . "</script>\n";
    }
    return $result;
  }

  // call this with an array of pageIds to hide if no form
  // fields will be shown for that tab
  // so if you have two tabs 'foo' and 'bar' and you want them to 
  // not show if nothing is under them pass in array('foo', 'bar')
  public function setHideEmptyPages($pages) {
    $this->hideEmptyPages =& $pages;
  }

  public function toHtml($style = "") {
    $page = $this->getPage();
    $i18n = $page->getI18n();
    $id = $this->getId();

    $form = $page->getForm();
    $formId = $form->getId();

    if ($this->getSelectedId() === 'errors') {
      // search through pages for errors, switch to the first page with errors on it.
      $this->setSelectedId($this->pageIds[0]); // the default
      for ($i = 0; $i < count($this->formFields); $i++) {
        $field_id = $this->formFields[$i]->getId();
        if ($this->formFieldErrors[$field_id]) {
          $this->setSelectedId($this->formFieldPageIds[$field_id]);
          break;
        }
      }
    }
    $selectedId = $this->getSelectedId();
    $pageIds = $this->getPageIds();

    $titleLabelHtml= false;
    $titleLabel = $this->getLabel();

    $ms_FormFields = array();
    $ms_FormFields['PIDS'] = array();

    // Form validation errors.
    //
    // To keep it simple for now we don't show errors next to each FormFieldObject (yet).
    // Instead we show them all in one block.
    //
    // First of all we ask BxPage for the errors that got generated:
    //
    $my_BXErrors = $page->getErrors();

    // separate all the form fields on the selected page and not
    $formFieldsInPage = array();
    $formFieldIdsInPage = array();
    $formFieldsOutPage = array();
    $formFieldIdsOutPage = array();
    $formFields =& $this->getFormFields();
    $pageIdsWithFormFields = array();

    for($i = 0; $i < count($formFields); $i++) {
      $formField =& $formFields[$i];
      $formFieldId = $formField->getId();

      $formFieldPageId = $this->getFormFieldPageId($formField);
      
      // keep track of tabs with no formfields
      if (!isset($pageIdsWithFormFields[$formFieldPageId])) {
        $pageIdsWithFormFields[$formFieldPageId] = true;
      }
          
      if($formFieldPageId == $selectedId) {
        // form fields on the selected page
        // this should be a reference assignment, but php sucks
        // $formFieldsInPage[] =& $formField; <-- Baaad idea!
        $formFieldsInPage[] = $formField;
        $formFieldIdsInPage[] = $formFieldId;
      }
      else {
        // form fields not on the selected page
        //$formFieldsOutPage[] =& $formField; <-- Another baaad idea!
        $formFieldsOutPage[] = $formField;
        $formFieldIdsOutPage[] = $formFieldId;
      }

      // This is pure bullshit and a prime example why it is sometimes better to rewrite a Class
      // from scratch instead of trying to modify it to what you want/need it to do instead.
      //
      // The original Cobalt code built arrays that contained only the active form elements on 
      // the current tab. The other elements of other tabs were not shown as form fields, but
      // as hidden fields. Thx to jQuery we don't need to reload the page and juggle active 
      // and hidden fields around. But that also means that we can't use the arrays that the 
      // Cobalt guys set up. So I create my own here that has all active form elements neatly
      // set up. 
      //
      // 'PIDS' contains the page IDs.
      // 'FFID' contains arrays with the 'formFieldId' => 'formFieldPageId'
      // 'FF'   contains the full objects of the FormFields needed to render them
      //
      // This makes all data easily accessible whenever we need them for output generation.
      //

      if (!in_array($formFieldPageId, $ms_FormFields['PIDS'])) {
        // We have not seen a PageID with this name yet, so we add it to our array 'PIDS':
        $ms_FormFields['PIDS'][] = $formFieldPageId;
      }
      // Add a 'FFID' with the current 'formFieldId' and 'formFieldPageId':
      $ms_FormFields['FFID'][$i] = array($formFieldId => $formFieldPageId);
      // Add a 'FF' with the full object of the actual 'formField':
      $ms_FormFields['FF'][$i] = $formField;

    }

    // find all dividers in page
    $dividers = $this->getDividers();
    $dividersInPage = array();
    $dividerIndexesInPage = array();
    for($i = 0; $i < count($dividers); $i++) {
      // divider not on this page?
      if($this->dividerPageIds[$i] != $selectedId) {
        continue;
      }
      $dividersInPage[] = $dividers[$i];
      $dividerIndexesInPage[] = $this->dividerIndexes[$i];
    }

    // make form field for selected ID
    $builder = new FormFieldBuilder();
    $selectedIdField = $builder->makeHiddenField("_PagedBlock_selectedId_$id", $selectedId);

    // mark visited pages
    if ($selectedId) {
      $visitedPages = $builder->makeHiddenField($selectedId, "true");
    }
    for($i = 0; $i < count($pageIds); $i++) {
      $pageId = $pageIds[$i];

      // marked already
      if($pageId == $selectedId) {
        continue;
      }

      // variable $<pageId> is true if it was visited
      global $$pageId;
      if($$pageId) {
        //$visitedPages .= $builder->makeHiddenField($pageId, "true");
      }
    }

    // maintain all form fields outside this page as hidden values
    $hiddenFormFields = "";
    for($i = 0; $i < count($formFieldsOutPage); $i++) {
      $formField =& $formFieldsOutPage[$i];
      $formFieldId = $formFieldIdsOutPage[$i];
      $formFieldPageId = $this->getFormFieldPageId($formField);

      // find the value of the form field
      $value = "";
      
      // use value set to the form field, since the form field knows
      // how to preserve data
      if (!get_class($formField) == "BarGraph") {
        $value = $formField->getValue();
        $hiddenFormFields .= $builder->makeHiddenField($formFieldId, $value);
      }

      // we need some special treatment for MultiChoice objects because they
      // can contain Options with FormFields
      // PHP is case-insensitive, so it returns "multichoice" <- Lies! It's not!
      // FIXME:  this is a nasty hack, if we want to do this there
      //         should be a more general way like checking if the
      //         getFormFields method exists for the given object

      if ((get_class($formField) == "multichoice") || (get_class($formField) == "MultiChoice")) {
        $options =& $formField->getOptions();
        for($j = 0; $j < count($options); $j++) {
          $optionFields =& $options[$j]->getFormFields();

          for($k = 0; $k < count($optionFields); $k++) {

            $optionField =& $optionFields[$k];
            $optionFieldId = $optionField->getId();

            // Don't we just hate it? We have to set the labels of MultiChoice objects manually to BxPage!
            // Somehow this magically works all by itself for getTextFields, but not for getIntegers.
            // So for getTextfields this may be a bit redundant (but doesn't hurt), while it will also
            // fix it for getIntegers and wherever else it may be broken:
            $this->BxPage->setLabel($optionFieldId, $options[$j]->formFieldLabels[$optionFieldId]->label, $options[$j]->formFieldLabels[$optionFieldId]->description);

            // use value set to the form field
            $optionValue = $optionField->getValue();
            $hiddenFormFields .= $builder->makeHiddenField($optionFieldId, $optionValue);
          }
        }
      }
    }

    // Start: Tab integration

    // make tabs
    $tabs = "";
    $shown_pages = 0;
    $we_have_tabs = "0";
    $aTagStart = "";

    // Start with empty output strings:
    $result_not_used = "";
    $result_hidden = "";
    $result_head = "";
    $result_tabs = "";
    $result_formfield = "";
    $result_foot = "";
    $result_errors = "";

    $seenTabs = array();

    for($i = 0; $i < count($pageIds); $i++) {
        $pageId = $pageIds[$i];
        $seenTabs[] = $pageId;

        // Drop hidden tabs:
        if (($pageId == "hidden") || ($pageId == "") || (!$pageId)) {
          $shown_pages--;
          continue;
        }
        elseif (!isset($pageIdsWithFormFields[$pageId]) && in_array($pageId, $this->hideEmptyPages)) {
          // drop tabs with no formfields that are in the
          // hideEmptyPages array
          $shown_pages--;
          continue;
        }
        else {
          $shown_pages++;
        }


        $label = $this->getPageLabel($pageId);
        $labelLabel = $label->getLabel();
        $description = $label->getDescription();

        // find the right action
        if($pageId == $selectedId) {
          $action = "javascript: void 0;";
        }
        else {
          global $SCRIPT_NAME;
          $action ="javascript: document.$formId._PagedBlock_selectedId_$id.value = '$pageId'; if(document.$formId.onsubmit()) { document.$formId.action = '$SCRIPT_NAME'; document.$formId.submit(); } else void 0;";
        }

        // If a 'defaultPage' is set manually, we need to set the class 'btn-modal' to make it active by letting jQuery click on it for us:
        if ($this->getDefaultPage() == $pageId) {
          $click_this_tab_to_activate_it = 'btn-modal ';
        }
        else {
          $click_this_tab_to_activate_it = '';
        }

        // Get the tabs set up:
        if($description == "") {
          $aTagStart .= '             <li><a href="#tabs-' . $shown_pages . '" class="' . $click_this_tab_to_activate_it . '">' . $i18n->getClean($pageId) . '</a></li>' . "\n";
          $tabID[$pageId] = $shown_pages;
        }
        else {
          $aTagStart .= '             <li><a href="#tabs-' . $shown_pages . '" class="' . $click_this_tab_to_activate_it . ' tooltip hover" title="' . $i18n->getWrapped($description) .'">' . $i18n->getClean($pageId) . '</a></li>' . "\n";
          $tabID[$pageId] = $shown_pages;
        }
        $tabs .= "\n";
    }

    $is_tabbed_class = "";
    if ($shown_pages > "1") {
      $we_have_tabs = "1";
      // We have more than one tab to show!
      if ($this->sideTabs == FALSE) {
        $is_tabbed_class = " tabs";
        $tab_header_class = 'tab_header';
      }
      else {
        $is_tabbed_class = " side_tabs tabs";
        $tab_header_class = 'tab_sider';
      }
    }
    // End: Tab integration    

    // make title row
    $result_head .= '          <div class="box grid_16' . $is_tabbed_class . '">' . "\n";

    // Show ID as title of the PagedBlock:
    if ($this->sideTabs == FALSE) {
      if (isset($this->label->label)) {
        $result_head .= '            <h2 class="box_head">' .  $this->label->label . '</h2>' . "\n";
      }
      elseif (isset($this->Label)) {
        if ($this->Label != "") {
          if (is_object($this->Label)) {
            $result_head .= '            <h2 class="box_head">' .  $this->Label->label . '</h2>' . "\n";
          }
          else {
            $result_head .= '            <h2 class="box_head">' .  $this->Label . '</h2>' . "\n";
          }
        }
        else {
          $result_head .= '            <h2 class="box_head">' .  $i18n->getHtml($id) . '</h2>' . "\n";
        }
      }
      else {
        $result_head .= '            <h2 class="box_head">' .  $i18n->getHtml($id) . '</h2>' . "\n";
      }
    }

    // If we have tabs, then we show the tabbings as well. We won't show it if there is only a single tab, though:
    if (count($this->pageIds) > "1") {
      if ($this->sideTabs == TRUE) {
        $result_head .= '       <div class="side_holder">'. "\n";
      }

      $result_head .= '            <ul class="' . $tab_header_class . ' clearfix">' . "\n";
      $result_head .= $aTagStart;
      $result_head .= '            </ul>' . "\n";
      if ($this->sideTabs == TRUE) {
        $result_head .= '       </div>'. "\n";
      }
    }
    $result_head .= '            <div class="controls">' . "\n";

    if (isset($this->elements['grabber'])) {
      $result_head .= '           <a href="#" class="grabber tooltip hover" title="' . $i18n->getWrapped("[[palette.icon_grabber]]") .'"></a>' . "\n";
    }
    if (isset($this->elements['toggle'])) {
      $result_head .= '           <a href="#" class="toggle tooltip hover" title="' . $i18n->getWrapped("[[palette.icon_toggle]]") .'"></a>' . "\n";
    }
    if (isset($this->elements['window'])) {

      // Set the required JavaScript in the page header that we need to open this in a new Window:
      $this->BxPage->setExtraHeaders('<script>');
      $this->BxPage->setExtraHeaders('function open_win()');
      $this->BxPage->setExtraHeaders('{');
      $this->BxPage->setExtraHeaders('window.open("' . $this->elements['window'] . '","_blank","toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=1024, height=800");');
      $this->BxPage->setExtraHeaders('}');
      $this->BxPage->setExtraHeaders('</script>');

      $result_head .= '           <a href="#" class="show_window tooltip hover" onclick="open_win()" title="' . $i18n->getWrapped("[[palette.icon_window]]") .'"></a>' . "\n";

    } 
    if (isset($this->elements['self'])) {

      // Set the required JavaScript in the page header that we need to open this in a new Window:
      $this->BxPage->setExtraHeaders('<script>');
      $this->BxPage->setExtraHeaders('function open_win()');
      $this->BxPage->setExtraHeaders('{');
      $this->BxPage->setExtraHeaders('window.open("' . $this->elements['self'] . '","_top","toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=1024, height=800");');
      $this->BxPage->setExtraHeaders('}');
      $this->BxPage->setExtraHeaders('</script>');

      $result_head .= '           <a href="' . $this->elements['self'] . '" class="show_window tooltip hover" onclick="open_win()" title="' . $i18n->getWrapped("[[palette.icon_window]]") .'"></a>' . "\n";

    }
    if (isset($this->elements['show_all_tabs'])) {
      $result_head .= '           <a href="#" class="show_all_tabs tooltip hover" title="' . $i18n->getWrapped("[[palette.icon_show_all_tabs]]") .'"></a>' . "\n";
    }
    $result_head .= '              </div>
              <div class="toggle_container">' . "\n";

    //
    //--- Add the general (invisible) error that the form validation failed. This gets unhidden if it really fails:
    //
    $result_head .= '<div id="error_formfields" class="error_formfields alert alert_red display_none"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->get("[[base-alpine.errorFormfields]]") . '<br>&nbsp;</strong></div>' . "\n";

    if ($this->getDisplayErrors() == TRUE) {
      if (isset($errormsg)) {
        if (is_array($errormsg)) {
          if (count($errormsg) > 0) { 
            foreach ($errormsg as $key => $value) {
              $result_head .= $value; 
            }     
          }
        }
      }
    }

    // It's possible to set a 'form_action', which is an URL that form data is posted to. Let us see if it is set:
    $form = get_object_vars($page->getForm());
    if (!$this->getFormDisabled()) {
      if ($form['action']) {
        $result_head .= '                <form class="validate_form" method="post" action="' . $form['action'] . '" ENCTYPE="multipart/form-data" id="waiting_overlay">' . "\n";
      }
      else {
        $result_head .= '                <form class="validate_form" method="post" ENCTYPE="multipart/form-data" id="waiting_overlay">' . "\n";
      }
    }

    //-- Start: Errors
    // This modification shows the errors on all tabs (once) instead of showing
    // it just on the tab where it happened. Which was confusing, because the
    // error might have been on an inactive tab. So this is better. Also works
    // correctly if the tabs are unified into one page and then shows the error
    // once.
    //
    // Show BXErrors:
    if ($this->getDisplayErrors() == TRUE) {
      if (is_array($my_BXErrors)) {
        if (count($my_BXErrors) > 0) {
          foreach ($my_BXErrors as $key => $value) {
            if (!is_object($value)) {
              if (is_array($value)) {
                // Grrr .... got another array inside the array? Deal with it:
                foreach ($value as $newkey => $newvalue) {
                  $result_head .= $newvalue;
                }
              }
              else {
                // No separate array insite the error array? Out with it:
                $result_head .= $value;
              }
            }
            else {
              // Error is an object? Nice. Deal with that, too:
              if (is_array($value->vars)) {
                $result_head .= ErrorMessage($i18n->get($value->message, "", $value->vars)) . "<br>";
              }
              else {
                $result_head .= ErrorMessage($i18n->get($value->message)) . "<br>";
              }
            }
          }     
        }
      }
    }
    //-- Stop: Errors

    // Dump in the form fields that are vsible on this page or tab (tabbing is done later):

    // is this page visited before?
    global $$selectedId;
    $isVisited = $$selectedId ? true : false;

    $optionalStr = $i18n->get("optional", "palette");

    // The next var and for loop tally up how many items the user will see
    // so that I can figure out when not to draw a final divider.
    $userEditableItems = 0;
            
    for($i = 0; $i < count($formFieldsInPage); $i++) {
        if (!is_object($formFieldsInPage[$i])) {
            continue;
        }
        $formFieldObj =& $formFieldsInPage[$i];

        // get form field HTML
        $formField =& $formFieldObj->toHtml();

        // hidden field is simple
        if (method_exists($formFieldObj, 'getAccess')){
            $access = $formFieldObj->getAccess();
        }
        // Super! Now that we can add ButtonContainers as well, we need a special case for 
        // Buttons, as they usually don't have an "access" field set:
        if (isset($formFieldObj->Button)) {
          $access = "rw";
        }
        if($access != "") {
                $userEditableItems++;
        }
    }

    $userItem = 0;
    $result = "";
    for($i = 0; $i < count($formFieldsInPage); $i++) {
        $subHeader = 0;
        
        if (!is_object($formFieldsInPage[$i])) {
            continue;
        }

        $formFieldObj =& $formFieldsInPage[$i];
       
        // get form field HTML
        $formField =& $formFieldObj->toHtml();

        // hidden field is simple
        $access = $formFieldObj->getAccess();
        if($access == "") {
            $result_hidden .= $builder->makeHiddenField(
                        $formFieldObj->getId(),
                        $formFieldObj->getValue());
            continue;
        }

        // get label HTML
        $formFieldLabelObj = $this->getFormFieldLabel($formFieldObj);
        $label = is_object($formFieldLabelObj) ? $formFieldLabelObj->toHtml() : "";

        $errormsg =& $this->getFormFieldError($formFieldObj);
        if ($errormsg) {
            $errorflag = "<TD><a href=\"javascript: void 0\" 
                onMouseOver=\"return top.code.info_mouseOverError('"
                . $i18n->interpolate($errormsg) . "')\" 
                onMouseOut=\"return top.code.info_mouseOut();\"><img
                alt=\"[ERROR]\" border=\"0\" src=\"/libImage/infoError.gif\"></a></TD>";
        }
        else {
            $errorflag = "";
        }

        $optional = "";
        if ($formFieldObj->isOptional() && (strval($formFieldObj->isOptional()) != "silent")) {
            $optional = "<FONT STYLE=\"\">($optionalStr)</FONT>";
        }
        $result_formfield .= $formField;

        $userItem++;
    }

    if ($this->sideTabs == FALSE) {
      $result_foot .= '
                    <div class="button_bar clearfix">' . "\n";
    }

    // make buttons
    $buttons = $this->getButtons();
    $allButtons = "";
    if ($buttons) {
      for($i = 0; $i < count($buttons); $i++) {
        $allButtons .= $buttons[$i]->toHtml();
      }
      if ($this->sideTabs == FALSE) {
        $result_foot .= $allButtons;
      }
    }

    if ($this->sideTabs == FALSE) {
      $result_foot .= '                    </div>';
    }

    $result_foot .= '                                         

                  </form>
                </div>
              </div>' . "\n";
    
    $result_errors = $this->reportErrors();

    // Render the output:
    $result .= $result_head;
    $currentFFnum = '0';
    $already_shown = array();
    $corrector = '0';

    $altDivHeight = $this->getDivHeight();
    if ($altDivHeight != "") {
      $vertical_stretcher_open = '<div style="min-height: ' . $altDivHeight . 'px;">' . "\n";
      $vertical_stretcher_close = '</div>' . "\n";
    }
    else {
      $vertical_stretcher_open = '';
      $vertical_stretcher_close = '';
    }

    //--
    // Old location of the Error display.
    //--

    //for($i = 0; $i < count($ms_FormFields['PIDS']); $i++) {
    for($i = 0; $i < count($seenTabs); $i++) { // <-- Keeps better track of the order of tabs than $ms_FormFields['PIDS']!!!
      $currentTab = $seenTabs[$i];
      if (($currentTab == "hidden") || ($currentTab == "") || (!$currentTab)) {
        $corrector++;;
      }
      else {
        $z = $i-$corrector;
        $z++;
        $result .= '                  <div id="tabs-' . $z . '" class="block">' . "\n";
        $result .= $vertical_stretcher_open;


        // Check if the FormField belongs into this tab. If so, print it:
        foreach ($ms_FormFields['FFID'] as $IDnum => $FFvsTab) {
          // Get the name of the FormField in question:
          $FFname = array_shift(array_keys($FFvsTab));
          $FFtab = array_shift(array_values($FFvsTab));

          // Is the tab of this formfield in the tab that we're currently doing?
          if ($FFtab == $currentTab) {
            $formFieldObj = $ms_FormFields['FF'][$IDnum];

            // Is this formfield optional?
            $optional = "";
            if ($formFieldObj->isOptional() && (strval($formFieldObj->isOptional()) != "silent")) {
                $optional = "<FONT STYLE=\"\">($optionalStr)</FONT>";
            }

            // add dividers
            $my_dividers = array_shift(array_values($this->dividers));

            for($j = 0; $j < count($this->dividers); $j++) {
              // divider at the right position?
              if (in_array($currentTab, $this->dividerPageIds)) {
                if($this->dividerPageIds[$j] == $FFtab) {

                  if (($this->dividerIndexes[$j] <= $currentFFnum) && (!in_array($j, $already_shown))) {
                    $labelObj = $this->dividers[$j];
                    $label = is_object($labelObj) ? $labelObj->toHtml($labelObj) : "";
                    $result .= '<div class="shade section"><b>' . $label. '</b></div>';
                    $already_shown[] = $j;
                  }
                }
              }
            }

            // Is the current tab in the array of $this->pageIds? If it is, we want to show the formFieldObj.
            // If not, then we make this a hidden field instead.
            if (!in_array($FFtab, $this->pageIds)) {
              // formFieldObj is not on a visible tab, so make it a hidden field instead:
              $access = $formFieldObj->getAccess();
              $result_hidden .= $builder->makeHiddenField($formFieldObj->getId(), $formFieldObj->getValue());
            }
            else {
              // formFieldObj is on a visible tab, so render it:
              $currentFFnum++;

              //
              // -> ===============================================
              // -> Assign the correct Labels to FormField Objects:
              // -> ===============================================
              //
              // And here is the magic. Oh, the joys of object oriented PHP programming!
              //
              // We have the FormField Objects and we have the separate LabelObjects that 
              // say which label goes into which FormField. This is one of the places
              // where we (at the latest!) need to merge them. But we can't do this somewhere 
              // in the other classes, because they have no access to the LabelObjects or 
              // only see the last LabelObject we just processed. 
              //
              // So we take a little round about here:
              //
              // Around line 175 of this class we passed the LabelObject data to BxPage, so
              // that it can keep track of it for us. Now we fetch that info back and stuff 
              // it manually back into the FormFiel Objects:

              // Get the ID of the current FormFiel Object:
              $formFieldObj_id = $formFieldObj->id;

              // With $this->BxPage->getLabel($formFieldObj_id) we ask BXPage to pass us the
              // label information of the corresponding label back to us. However, we need to
              // make sure the info we get back from BxPage is an array. If not, this FF has
              // no label:
              if (is_array($this->BxPage->getLabel($formFieldObj_id))) {
                foreach ($this->BxPage->getLabel($formFieldObj_id) as $label => $description) {
                  // Stuff the label and the description into our FormField Object:
                  if (!isset($formFieldObj->page)) { $formFieldObj->page = new stdClass(); }
                  $formFieldObj->page->Label = array($label => $description);
                  // Also manually set the current Object ID into that FormField Object, because
                  // at this time that information might be incorrect:
                  $formFieldObj->page->ID = array("id" => $formFieldObj_id);
                }
              }
              else {
                // We have no label for this FormField:
                $formFieldObj->page->Label = "";
                // Also manually set the current Object ID into that FormField Object, because
                // at this time that information might be incorrect:
                $formFieldObj->page->ID = array("id" => $formFieldObj_id);
              }

              // Now there is one small catch, which is more of an imperfection: All FormField
              // Objects now carry the field page->BXLabel() which contains an array with ALL
              // labels that are on this page. But we will have to live with that.
              $formField =& $formFieldObj->toHtml();
              $result .= $formField . "\n";
            }
          }
        }
        $result .= '                  </div>' . "\n";
        $result .= $vertical_stretcher_close;
      }

    }

    $result .= $result_hidden;
    $result .= $result_foot;
    $result .= $result_errors;

    return $result;

  }
}

/*
Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
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