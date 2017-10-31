<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * UIFC BlueOnyx Next Generation Helper Library
 *
 * UIFC helper for BlueOnyx NG on Codeigniter
 *
 * @package   CI UIFC NG
 * @author    Michael Stauber
 * @copyright Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
 * @link      http://www.solarspeed.net
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */

function addIframe ($url, $height, $BxPage) {
    // Generate an iframe from the passed on URL:
    //
    // $url:        URL of the iframe content.
    // $height:     Height. If "auto", it will be auto-calculated. 
    // $BxPage:     parents BXPage object

    if ($height == "auto") {
        $height = '';
    }
    else {
        $height = 'height="' . $height . '" ';
    }

    if (is_HTTPS() == TRUE) {
        $url = 'https://' . $_SERVER['SERVER_NAME'] . ':81' . $url;
    }
    else {
        $url = 'http://' . $_SERVER['SERVER_NAME'] . ':444' . $url; 
    }

    $BxPage->setExtraHeaders('<script type="text/javascript" src="/.adm/scripts/iframe-auto-height/jquery.iframe-auto-height.js"></script>');

    $out = '
        <iframe src="' . $url . '" class="column" scrolling="no" frameborder="0" width="100%" ' . $height . '></iframe>
        <script type="text/javascript">
         jQuery("iframe").iframeAutoHeight({debug: true, diagnostics: false});      
        </script>
    ';
    return $out;
}

function getBar ($palette, $name, $percentage, $bartext, $i18n) {
    // Generates a progressbar with a hovering helptext and a bartext below:
    // $palette:    which module related i18n locales we check against ('base-user', 'base-vsite').
    // $name:       name of the item
    // $percentage: percentage of the progress bars progress
    // $bartext:    text below progress bar
    // $i18n:       parents $i18n object

    $combined = $palette . "." . $name;
    $text = $i18n->getHtml("[[$combined]]");
    $h = $palette . "." . $name . '_help';
    $helptext = $i18n->getWrapped("[[$h]]");
    $percentage_helptext = $bartext;

    $out = '
                                                        <fieldset class="label_side">
                                                                <label title="' . $helptext . '" class="tooltip hover">' . $text . '</label>
                                                                <div>
                                                                        <div title="' . $percentage_helptext . '" id="progressbar" class="progressbar tooltip hover"></div>
                                                                            <p align="center">' . $percentage_helptext . '</p>
                                                                            <script>
                                                                                $( "#progressbar" ).progressbar({
                                                                                    value: ' . $percentage . '
                                                                                });
                                                                            </script>

                                                                </div>
                                                        </fieldset>';
    return $out;
}

function Label ($palette, $name, $i18n) {
    // Generates a Label with a hovering helptext:
    // $palette:    which module related i18n locales we check against ('base-user', 'base-vsite').
    // $name:       name of the item
    // $i18n:       parents $i18n object

    $tp1 = $palette . "." . $name;
    $text = $i18n->getHtml("[[$palette.$tp1]]");

    $h = $palette . "." . $name . '_help';
    $helptext = $i18n->getWrapped("[[$h]]");

    $out = '<label for="' . $text . '" title="' . $helptext . '" class="tooltip hover">'. $text .'</label>';

    return $out;

}

function addToggleAbleAutoGrowField ($name, $type="", $required = "required", $name_opt1 = "", $name_opt2 ="", $val_opt1 = "", $val_opt2 = "", $textarea_name="", $textarea_help="", $textarea_span="", $textarea_value="", $palette = "palette", $i18n, $cceClient) {
    // name:            main heading of the input field
    // type:            validation for the textarea. Like "email", "text", "fqdn" or similar.
    // required:        hass the textarea required input if enabled?
    // name_opt1:       name of the first checbox option
    // val_opt1:        value of the first checkbox option
    // name_opt2:       name of the second checbox option
    // val_opt2:        value of the second checkbox option 
    // checked_opt1:    Is that checkbox ticked?
    // checked_opt2:    Is that checkbox ticked?
    // textarea_name:   name of the textarea
    // textarea_help:   tooltip of the texarea heading
    // textarea_span:   Optional <span></span> text for the textarea heading.
    // textarea_value:  pre-filled text for textarea
    // palette:         which module related i18n locales we check against ('base-user', 'base-vsite'). Defaults to 'palette'.
    // $i18n:           parents $i18n object

    $h = $palette . "." . $name . '_help';
    $helptext = $i18n->getWrapped("[[$h]]");
    $ht = $palette . "." . $textarea_help;
    $area_helptext = $i18n->getWrapped("[[$ht]]");
    if ($textarea_span) {
        $my_textarea_span = $i18n->get("[[$palette.$textarea_span]]");
    }
    else {
        $my_textarea_span = "";
    }

    if ($required == "required") {
        $optional_text = '';
        $optional_class = 'required ';
        $optional_line = '<div class="required_tag tooltip hover left" title="' . get_i18n_error_for_inputvalidation($type, $i18n) . '"></div>';
    }
    else {
        //$optional_text = "(" . $i18n->get("[[palette.optional]]") . ")";
        $optional_text = "";
        $optional_class = ' ';
        $optional_line = '';
    }

    $htp1 = $palette . "." . $name . '_help';
    $htp2 = $palette . "." . $name_opt2 . '_help';

    $my_name_opt1 = $i18n->get("[[$palette.$name_opt1]]");
    $my_name_opt2 = $i18n->get("[[$palette.$name_opt2]]");

    $help_opt1 = $i18n->getWrapped("[[$palette.$htp1]]");
    $help_opt2 = $i18n->getWrapped("[[$palette.$htp2]]");

    if ($val_opt1 == "1") {
        $val_opt1 = " checked ";
    }
    else {
        $val_opt1 = "";
    }
    if ($val_opt2 == "1") {
        $val_opt2 = " checked ";
    }
    else {
        $val_opt2 = "";
    }


    $out = '
        <div class="columns">
                <fieldset class="label_side col_25 no_lines">
                    <div class="section">
                        <label for="' . $name . '" title="' . $helptext . '" class="tooltip hover">' . $i18n->get("[[$palette.$name]]") . '<span>' . $optional_text . '</span></label>
                    </div>
                </fieldset>
            <div class="col_25">
                <div class="section">
                    <fieldset class="label_top bottom no_lines">
                            <div class="uniform inline clearfix">
                                    <INPUT TYPE="HIDDEN" NAME="checkbox-' . $name_opt1 . '" VALUE="' . $val_opt1 . '">
                                    <label for="' . $name_opt1 . '" title="' . $help_opt1 . '" class="tooltip hover"><input type="checkbox" class="mcb-' . $name_opt1 . '" name="' . $name_opt1 . '" id="' . $name_opt1 . '"' . $val_opt1 . '/>'. $my_name_opt1 .'</label>
                                    <INPUT TYPE="HIDDEN" NAME="checkbox-' . $name_opt2 . '" VALUE="' . $val_opt2 . '">
                                    <label for="' . $name_opt2 . '" title="' . $help_opt2 . '" class="tooltip hover"><input type="checkbox" class="mcb-' . $name_opt2 . '" name="' . $name_opt2 . '" id="' . $name_opt2 . '"' . $val_opt2 . '/>'. $my_name_opt2 .'</label>
                            </div>
                    </fieldset>
                </div>
            </div>
            <div class="col_50">
                <div class="section">
                        <INPUT TYPE="HIDDEN" NAME="textarea-'. $textarea_name . '" VALUE="' . $textarea_value . '">
                        <fieldset class="label_top no_lines lesspadding">
                            <label for="' . $textarea_name . '" title="' . $area_helptext . '" class="tooltip">' . $i18n->get("[[$palette.$textarea_name]]") . '<span>' . $my_textarea_span . '</span></label>
                                <div class="clearfix' . $optional_class . '">
                                        <textarea name="'. $textarea_name . '" title="' . $i18n->get("[[palette.autogrow_expanding]]") . '" class="tooltip autogrow ' . $type . '" placeholder="' . $i18n->get("[[palette.autogrow_prefill]]") . '">' . $cceClient->scalar_to_string($textarea_value) . '</textarea>
                                        ' . $optional_line . '
                                </div>
                            </span>
                        </fieldset>
                </div>
            </div>                                          
        </div>';

  return $out;
}                                       

function addFreeButton ($label, $tooltip, $class = "no_margin_bottom div_icon has_text", $icon = "ui-icon ui-icon-check", $palette = "palette", $i18n) {
    // label:   label of the button
    // tooltip: button has tooltip
    // class:   defines the appearance of the button
    // icon:    defines if the button has an icon and if so, which.
    // palette: which module related i18n locales we check against ('base-user', 'base-vsite'). Defaults to 'palette'.
    // $i18n:   parents $i18n object

    $helptext = $palette . "." . $label . "_help";

    $out = "";
    if ($tooltip == "tooltip") {
        $out .= '                               <label title="' . $i18n->getWrapped("[[$helptext]]") . '" class="tooltip right">';
    }
    $out .= '
                                    <button class="' . $class . '" type="submit" formmethod="post">
                                        <div class="' . $icon . '"></div>
                                        <span>' . $i18n->get("[[$palette.$label]]") . '</span>
                                    </button>';

    if ($tooltip == "tooltip") {
        $out .= '                               </label>';
    }

  return $out;
}

function addSaveButton ($i18n) {
    // $i18n:   parents $i18n object
    $out = '
                                <label title="' . $i18n->getWrapped("[[palette.save_help]]") . '" class="tooltip right">
                                    <button class="no_margin_bottom div_icon has_text" type="submit" formmethod="post">
                                        <div class="ui-icon ui-icon-check"></div>
                                        <span>' . $i18n->get("[[palette.save]]") . '</span>
                                    </button>
                                </label>';
  return $out;
}

function addCancelButton ($i18n, $URL) {
    // $i18n:   parents $i18n object
    // $URL:    URL that the button points to

    if ($URL != "") {
        $data_link = ' data-link="' . $URL . '"';
        $linkable = ' link_button';
    }
    else {
        $data_link = '';
        $linkable = '';
    }

    $out = '
                                        <button title="' . $i18n->getWrapped("[[palette.cancel_help]]") . '" class="light send_right close_dialog tooltip right' . $linkable . '"' . $data_link . '>
                                            <div class="ui-icon ui-icon-closethick"></div>
                                                <span>' . $i18n->get("[[palette.cancel]]") . '</span>
                                        </button>';                                     
  return $out;
}

function addOldInputForm ($form_header, $grabber = "nograbber", $toggle = "notoggle", $form_body, $buttons = "", $i18n, $errors = "") {

    // form_header:     heading of the form
    // grabber:         defines if the form can be grabbed and moved.
    // toggle:          defines if the form can be toggled.
    // form_body:       HTML of the form body, or functions that define the output inside the form.
    // save_button:     defines if the form has a save button
    // cancel_button:   defines if the form has a cancel button
    // $i18n:           parents $i18n object
    // $errors:         form validation errors

    $out = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $form_header . '</h2>
                        <div class="controls">';
    if ($grabber == "grabber") {
        $out .= '                       <a href="#" class="grabber tooltip hover" title="' . $i18n->getWrapped("[[palette.icon_grabber]]") .'"></a>';
    }
    if ($toggle == "toggle") {
        $out .= '                       <a href="#" class="toggle tooltip hover" title="' . $i18n->getWrapped("[[palette.icon_toggle]]") .'"></a>';
    }
    $out .= '
                        </div>
                        <div class="toggle_container">
                            <div class="block">';

    if (is_array($errors)) {
        if (count($errors) > 0) { 
            foreach ($errors as $key => $value) {
                $out .= $value; 
            }           
        }
    }

    $out .= '                       <form class="validate_form" method="post">'
                                    . $form_body . '
                                    <div class="button_bar clearfix">';
    if ($buttons) {
        $out .= $buttons;
    }

    $out .= '                                           

                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </form>';

  return $out;

}

function addInputForm ($form_header, $elements = array("grabber" => "#", "toggle" => "#", "window" => "#"), $form_body, $buttons = "", $i18n, $BxPage, $errors = "") {

    // form_header:     heading of the form
    // elements:        Array that defines which header elements (buttons) the form has (grabber, toggle, window)
    // form_body:       HTML of the form body, or functions that define the output inside the form.
    // save_button:     defines if the form has a save button
    // cancel_button:   defines if the form has a cancel button
    // $i18n:           parents $i18n object
    // $errors:         form validation errors

    $out = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $form_header . '</h2>
                        <div class="controls">';

    if (isset($elements['grabber'])) {
        $out .= '                       <a href="#" class="grabber tooltip hover" title="' . $i18n->getWrapped("[[palette.icon_grabber]]") .'"></a>';
    }
    if (isset($elements['toggle'])) {
        $out .= '                       <a href="#" class="toggle tooltip hover" title="' . $i18n->getWrapped("[[palette.icon_toggle]]") .'"></a>';
    }
    if (isset($elements['window'])) {

        $BxPage->setExtraHeaders('<script>');
        $BxPage->setExtraHeaders('function open_win()');
        $BxPage->setExtraHeaders('{');
        $BxPage->setExtraHeaders('window.open("' . $elements['window'] . '","_blank","toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=1024, height=800");');
        $BxPage->setExtraHeaders('}');
        $BxPage->setExtraHeaders('</script>');

        $out .= '                       <a href="#" class="show_window tooltip hover" onclick="open_win()" title="' . $i18n->getWrapped("[[palette.icon_window]]") .'"></a>';

    }   
    $out .= '
                        </div>
                        <div class="toggle_container">
                            <div class="block">';

    if (is_array($errors)) {
        if (count($errors) > 0) { 
            foreach ($errors as $key => $value) {
                $out .= $value; 
            }           
        }
    }

    $out .= '                       <form class="validate_form" method="post">'
                                    . $form_body . '
                                    <div class="button_bar clearfix">';
    if ($buttons) {
        $out .= $buttons;
    }

    $out .= '                                           

                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </form>';

  return $out;

}


function addTextField($name, $type = "", $value = "", $palette = "palette", $required = "required", $read_write, $i18n) {

    // name:        name of the input field
    // type:        type of check that we validate against ('fqdn', 'email' or others). If empty, no validation.
    // value:       value to populate the input with.
    // palette:     which module related i18n locales we check against ('base-user', 'base-vsite'). Defaults to 'palette'.
    // required:    is this a required field? Usually 'required' or anything else for not required.
    // $read_write: Defines if this field is editable.  "hidden" = invisble form field. "rw" = editable form field, "r" = visible text + invisble form field.
    // $i18n:       parents $i18n object

    $h = $palette . "." . $name . '_help';
    $helptext = $i18n->getWrapped("[[$h]]");

    if ($required == "required") {
        $optional_text = '';
        $optional_class = 'required ';
        $optional_line = '<div class="required_tag tooltip hover left" title="' . get_i18n_error_for_inputvalidation($type, $i18n) . '"></div>';
    }
    else {
        $optional_text = "(" . $i18n->get("[[palette.optional]]") . ")";
        $optional_class = ' ';
        $optional_line = '';
    }
    if ($read_write == "hidden") {
        $input_type = "hidden";
        $show_only = '';
        // Need to reset any existing 'required' stuff:
        $optional_text = '';
        $optional_class = '';
        $optional_line = '';

    }
    elseif ($read_write == "rw") {
        if ($type == "password") {
            $input_type = "password";
        }
        else {
            $input_type = "text";   
        }
        $show_only = '';
    }
    else {
        // Covers 'r' and anything else:
        $input_type = "hidden";
        $show_only = '<p>' . $value . '</p>';
        // Need to reset any existing 'required' stuff:
        $optional_text = '';
        $optional_class = '';
        $optional_line = '';
    }

    $out = '';
    if ($read_write != "hidden") {
        $out .= '
                                    <fieldset class="label_side top">
                                            <label for="' . $name . '" title="' . $helptext . '" class="tooltip right">' . $i18n->get("[[$palette.$name]]") . '<span>' . $optional_text . '</span></label>
                                            <div>';
    }
    $out .= '
                                                <input type="' . $input_type . '" name="' . $name . '" VALUE="' . $value . '" id="' . $name . '" class="' . $optional_class . $type . ' error">
                                                ' . $show_only . $optional_line;
    if ($read_write != "hidden") {
        $out .= '
                                            </div>
                                    </fieldset>';
    }

  return $out;
}

function addTopTextField($name, $type = "", $value = "", $palette = "palette", $required = "required", $read_write, $i18n) {

    // name:        name of the input field
    // type:        type of check that we validate against ('fqdn', 'email' or others). If empty, no validation.
    // value:       value to populate the input with.
    // palette:     which module related i18n locales we check against ('base-user', 'base-vsite'). Defaults to 'palette'.
    // required:    is this a required field? Usually 'required' or anything else for not required.
    // $read_write: Defines if this field is editable.  "hidden" = invisble form field. "rw" = editable form field, "r" = visible text + invisble form field.
    // $i18n:       parents $i18n object

    $h = $palette . "." . $name . '_help';
    $helptext = $i18n->getWrapped("[[$h]]");

    if ($required == "required") {
        $optional_text = '';
        $optional_class = 'required ';
        $optional_line = '<div class="required_tag tooltip hover left" title="' . get_i18n_error_for_inputvalidation($type, $i18n) . '"></div>';
    }
    else {
        $optional_text = "(" . $i18n->get("[[palette.optional]]") . ")";
        $optional_class = ' ';
        $optional_line = '';
    }
    if ($read_write == "hidden") {
        $input_type = "hidden";
        $show_only = '';
        // Need to reset any existing 'required' stuff:
        $optional_text = '';
        $optional_class = '';
        $optional_line = '';

    }
    elseif ($read_write == "rw") {
        $input_type = "text";
        $show_only = '';
    }
    else {
        // Covers 'r' and anything else:
        $input_type = "hidden";
        $show_only = '<p>' . $value . '</p>';
        // Need to reset any existing 'required' stuff:
        $optional_text = '';
        $optional_class = '';
        $optional_line = '';
    }

    $out = '';
    if ($read_write != "hidden") {
        $out .= '
                                    <fieldset class="label_top top">
                                            <label for="' . $name . '" title="' . $helptext . '" class="tooltip right">' . $i18n->get("[[$palette.$name]]") . '<span>' . $optional_text . '</span></label>
                                            <div>';
    }
    $out .= '
                                                <input type="' . $input_type . '" name="' . $name . '" VALUE="' . $value . '" id="' . $name . '" class="' . $optional_class . $type . ' error">
                                                ' . $show_only . $optional_line;
    if ($read_write != "hidden") {
        $out .= '
                                            </div>
                                    </fieldset>';
    }

  return $out;
}

function addPasswordField($name, $palette = "palette", $required = "required", $i18n, $BxPage) {

    $h = $palette . "." . $name . '_help';
    $helptext = $i18n->getWrapped("[[$h]]");

    if ($required == "required") {
        $optional_text = "";
        $optional_line = '<div class="required_tag tooltip hover left" title="' . get_i18n_error_for_inputvalidation($type, $i18n) . '"></div>';
    }
    else {
        $optional_text = "(" . $i18n->get("[[palette.optional]]") . ")";
        $optional_line = '';
    }   

    $BxPage->setExtraHeaders('
          <script language="Javascript" type="text/javascript" src="/libJs/ajax_lib.js"></script>
          <script language="Javascript">
            <!--
              checkpassOBJ = function() {
                this.onFailure = function() {
                  alert("Unable to validate password");
                }
                this.OnSuccess = function() {
                  var response = this.GetResponseText();
                  document.getElementById("results").innerHTML = response;
                }
              }


              function validate_password ( word ) {
                checkpassOBJ.prototype = new ajax_lib();
                checkpass = new checkpassOBJ();
                var URL = "/gui/check_password";
                var PARAM = "password=" + word;
                checkpass.post(URL, PARAM);
              }

            //-->
          </script>
        ');
    
    $out = '
                                <fieldset class="label_side top">
                                    <label for="' . $name . '" title="' . $helptext . '" class="tooltip right">' . $i18n->get("[[$palette.$name]]") . '<span>' . $optional_text . '</span></label>
                                    <div>
                                        <INPUT id="pass" TYPE="PASSWORD" NAME="' . $name . '" VALUE="" SIZE="20" onKeyUp="validate_password(this.value)" >
                                        <div id="results">'. $i18n->get("pwCheckStr", "palette") . '</div>
                                        <INPUT id="pass" TYPE="PASSWORD" NAME="_' . $name . '_repeat" VALUE="" SIZE="20" onKeyUp="validate_password(this.value)" >' . $i18n->get("repeat", "palette") . '
                                    </div>
                                </fieldset>';
  return $out;
}

function addPullDown($name, $options = array(), $set_val = "", $palette = "palette", $i18n) {

    // name:        name of the dropdown
    // options:     array of select options
    // set_val:     if specified, the desired option will be pre-selected by default.
    // required:    is this a required field? '0' = No, '1' = Yes
    // $i18n:       parents $i18n object

    $h = $palette . "." . $name . '_help';
    $helptext = $i18n->getWrapped("[[$h]]");
    $key = array_search($set_val, $options);

    $out = '
                                <fieldset class="label_side top">
                                        <label for="' . $name . '" title="' . $helptext . '" class="tooltip left">' . $i18n->getHtml("[[$palette.$name]]") . '</label>
                                        <div class="clearfix">' .
                                                form_dropdown($name, $options, $key) . '
                                        </div>
                                </fieldset>';
  return $out;
}

/**
 * get_i18n_error_for_inputvalidation($checktype)
 *
 * Checks if a validation check is handled by jQuery's built in checks, or if we
 * use a native check of BlueOnyx for the validation.
 *
 * Returns the required i18n code to display the error message when the check fails.
 *
 * @param VAR   $checktype      : Short name of the check to be performed
 * @return VAR  i18n code to display the error message
 */

function get_i18n_error_for_inputvalidation($checktype, $i18n) {

    // Get Cookie-Locale to determine the currently used language:
    $CI =& get_instance();
    $cookie_locale = $CI->input->cookie('locale');

    $i18n = new I18n("palette", $cookie_locale);

    $internal_checks = array(
                    'required' => $i18n->getHtml("[[palette.val_required]]"),
                    'remote' => $i18n->getHtml("[[palette.val_remote]]"),
                    'email' => $i18n->getHtml("[[palette.val_email]]"),
                    'url' => $i18n->getHtml("[[palette.val_url]]"),
                    'date' => $i18n->getHtml("[[palette.val_date]]"),
                    'dateISO' => $i18n->getHtml("[[palette.val_dateISO]]"),
                    'number' => $i18n->getHtml("[[palette.val_number]]"),
                    'digits' => $i18n->getHtml("[[palette.val_digits]]"),
                    'creditcard' => $i18n->getHtml("[[palette.val_creditcard]]"),
                    'equalTo' => $i18n->getHtml("[[palette.val_equalTo]]"),
                    'accept' => $i18n->getHtml("[[palette.val_accept]]"),
                    'maxlength' => $i18n->getHtml("[[palette.val_maxlength]]"),
                    'minlength' => $i18n->getHtml("[[palette.val_minlength]]"),
                    'rangelength' => $i18n->getHtml("[[palette.val_rangelength]]"),
                    'range' => $i18n->getHtml("[[palette.val_range]]"),
                    'max' => $i18n->getHtml("[[palette.val_max]]"),
                    'min' => $i18n->getHtml("[[palette.val_min]]")
                );

    if ((in_array($checktype, $internal_checks)) && ($checktype != "")) {
        return $internal_checks[$checktype];
    }
    else {
        return  $i18n->get("[[palette.val_required]]");
    }

}

function showStyleSwitcher($i18n) {

/**
 * showStyleSwitcher($i18n)
 *
 * Shows the style switcher for the theme.
 *
 * Returns the required code to display the style switcher at the bottom of the page.
 *
 * @param VAR   $i18n       : parents $i18n object
 * @return VAR              : code to display the error message
 */

    $out = '
        <div id="template_options" class="clearfix">
            <div class="layout_size"><label>' . $i18n->get("[[base-product.productName]]") . ': ' . $i18n->get("[[base-user.styleField]]") . '</label></div>
            <div class="layout_size">
                <label>' . $i18n->get("[[palette.layout]]") . ':</label>
                <a href="/.adm/styles/themes/layout_switcher.php?style=switcher.css">' . $i18n->get("[[palette.fluid]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/layout_switcher.php?style=layout_fixed.css">' . $i18n->get("[[palette.fixed]]") . '</a>
            </div>
            <div class="layout_position">
                <label>' . $i18n->get("[[palette.menus]]") . ': </label>
                <a href="/.adm/styles/themes/nav_switcher.php?style=switcher.css">' . $i18n->get("[[palette.side]]") . '</a><span>|</span>
                <!-- <a href="/.adm/styles/themes/nav_switcher.php?style=nav_stacks.css">' . $i18n->get("[[palette.stacks]]") . '</a><span>|</span> -->
                <a href="/.adm/styles/themes/nav_switcher.php?style=nav_top.css">' . $i18n->get("[[palette.top]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/nav_switcher.php?style=nav_slideout.css">' . $i18n->get("[[palette.slide]]") . '</a>
            </div>
            <div class="layout_position">
                <label>' . $i18n->get("[[palette.theme]]") . ': </label>
                <a href="/.adm/styles/themes/skin_switcher.php?style=multiple&skin_switcher.php=switcher.css&bg_switcher.php=switcher.css">' . $i18n->get("[[palette.dark]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/skin_switcher.php?style=multiple&skin_switcher.php=skin_light.css&bg_switcher.php=switcher.css">' . $i18n->get("[[palette.light]]") . '</a>
            </div>
            <div class="theme_colour">
                <label class="display_none">Colour:</label>
                <a class="black" href="/.adm/styles/themes/theme_switcher.php?style=switcher.css"><span>Black</span></a>
                <a class="blue" href="/.adm/styles/themes/theme_switcher.php?style=theme_blue.css"><span>Blue</span></a>
                <a class="navy" href="/.adm/styles/themes/theme_switcher.php?style=theme_navy.css"><span>Navy</span></a>
                <a class="red" href="/.adm/styles/themes/theme_switcher.php?style=theme_red.css"><span>Red</span></a>
                <a class="green" href="/.adm/styles/themes/theme_switcher.php?style=theme_green.css"><span>Green</span></a>
                <a class="magenta" href="/.adm/styles/themes/theme_switcher.php?style=theme_magenta.css"><span>Magenta</span></a>
                <a class="orange" href="/.adm/styles/themes/theme_switcher.php?style=theme_brown.css"><span>Brown</span></a>
            </div>
            <div class="theme_background" id="bg_dark">
                <label>' . $i18n->get("[[palette.BGs]]") . ':</label>
                <a href="/.adm/styles/themes/bg_switcher.php?style=bg_wunder.css">' . $i18n->get("[[palette.metal]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/bg_switcher.php?style=switcher.css">' . $i18n->get("[[palette.boxes]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/bg_switcher.php?style=bg_punched.css">' . $i18n->get("[[palette.punched]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/bg_switcher.php?style=bg_honeycomb.css">' . $i18n->get("[[palette.honeycomb]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/bg_switcher.php?style=bg_wood.css">' . $i18n->get("[[palette.wood]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/bg_switcher.php?style=bg_dark_wood.css">' . $i18n->get("[[palette.timber]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/bg_switcher.php?style=bg_noise.css">' . $i18n->get("[[palette.noise]]") . '</a>
            </div>
            <div class="theme_background" id="bg_light">
                <label>' . $i18n->get("[[palette.BGs]]") . ':</label>
                <a href="/.adm/styles/themes/bg_switcher.php?style=switcher.css">' . $i18n->get("[[palette.silver]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/bg_switcher.php?style=bg_white_wood.css">' . $i18n->get("[[palette.wood]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/bg_switcher.php?style=bg_squares.css">' . $i18n->get("[[palette.squares]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/bg_switcher.php?style=bg_noise_zero.css">' . $i18n->get("[[palette.noise]]") . '</a><span>|</span>
                <a href="/.adm/styles/themes/bg_switcher.php?style=bg_stripes.css">' . $i18n->get("[[palette.stripes]]") . '</a>
            </div>
        </div>';
    return $out;
}

function minutes_round ($minutes = '03', $step = '15') {
    $rounded = round($minutes / ($step)) * ($step);
    return $rounded;
}

function simplify_number ($number, $literal, $cnt) {

    // Return our numbers nicely formatted.
    //
    // Arguments:
    //
    // $number:         The number we're formatting
    // $literal:        "K"  = one thousand = factor 1000
    //                  "KB" = one thousand = factor 1024
    // $cnt:            Number of digits after the dot
    //
    // Returns nicely formatted number including the factor.

    // Simple: If it's a '0' to begin with, we're done right here and now:
    if ($number == "0") {
        return $number;
    }

    if ($literal == "K") {
        $multi = "1000";
    }
    elseif ($literal == "KB") {
        $multi = "1024";
    }
    else {
        $multi = "1024";
    }    
    // Handle case where we don't have a number, but are set to 'unlimited':
    if ($number === "unlimited") {
        return "unlimited";
    }
    // Handle cases where '*_b' or '*_l' already have a unit assigned:
    $pattern = '/^(.*)(K|M|G|T)$/';
    if (preg_match($pattern, $number, $matches, PREG_OFFSET_CAPTURE)) {
        return $number;
    }
    if ((strlen($number)) > "16") {
        return "Unlimited";
    }
    $units = array('B', 'K', 'M', 'G', 'T');
    for ($i = 0; $number >= $multi && $i < count($units) - 1; $i++ ) {
        $number /= $multi;
    }
    $result = round($number, $cnt).''.$units[$i];
    return $result;
}

function unsimplify_number ($number, $literal, $cnt="") {

    // Return our numbers in machine readable format.
    //
    // Arguments:
    //
    // $number:         The number we're formatting
    // $literal:        "K"  = one thousand = factor 1000
    //                  "KB" = one thousand = factor 1024
    // $cnt:            Number of digits after the dot
    //
    // Returns numbers without factors or units in machine readable integers.

    if ($literal == "K") {
        $multi = "1000";
    }
    elseif ($literal == "KB") {
        $multi = "1024";
    }
    else {
        $multi = "1024";
    }    
    // Handle case where we don't have a number, but are set to 'unlimited':
    if ($number === "unlimited") {
        return "unlimited";
    }

    $number = preg_replace('/\,/', '.', $number);

    // Handle cases where '*_b' or '*_l' already have a unit assigned:
    $pattern = '/^(\d*[(\.)|(\,)]{0,1}\d+)(K|M|G|T)$/';
    if (preg_match($pattern, $number, $matches, PREG_OFFSET_CAPTURE)) {
        $split_numbers = preg_split("/(K|M|G|T)/", $number, 0, PREG_SPLIT_DELIM_CAPTURE);
        $number = $split_numbers[0];
        $format = $split_numbers[1];

        // Based on the unit multiply the number to get the integer back:
        if ($format == "M") {
            $mod = $multi;
            $number = $number*$mod;
        }
        if ($format == "G") {
            $mod = $multi*$multi;
            $number = $number*$mod;
        }
        if ($format == "T") {
            $mod = $multi*$multi*$multi;
            $number = $number*$mod;
        }
        if ($format == "P") {
            $mod = $multi*$multi*$multi*$multi;
            $number = $number*$mod;
        }

        // Return the recalculated integer without unit:
        return $number;
    }

    // Check for positive decimal number without unit
    $pattern = '/^(\d*\.{0,1}\d+)$/';
    if (preg_match($pattern, $number, $matches, PREG_OFFSET_CAPTURE)) {
        $integer = roundToNearest($number);
        // Return the recalculated and rounded integer:
        return $integer;
    }
}

function roundToNearest($number,$nearest=50) {
    $number = round($number);
    if ($nearest>$number || $nearest <= 0) {
        return $number;
    }
    else {
        $x = ($number%$nearest);
        return ($x<($nearest/2))?$number-$x:$number+($nearest-$x);
    }
}

function simplify_number_pages ($number, $literal, $cnt) {

    // NOTE: Slightly different from 'simplify_diskspace', as the parameters
    // 'physpages' and 'swappages' use a multiplicator of 4096, as OpenVZ
    // indeed handles them as pages. So when we get an integer, we need to
    // multiply it by 4096 to get the real amount of memory.

    // Return our numbers nicely formatted.
    //
    // Arguments:
    //
    // $number:         The number we're formatting
    // $literal:        "K"  = one thousand = factor 1000
    //                  "KB" = one thousand = factor 1024
    // $cnt:            Number of digits after the dot
    //
    // Returns nicely formatted number including the factor.

    if ($literal == "K") {
        $multi = "1000*1000";
    }
    elseif ($literal == "KB") {
        $multi = "1024";
    }
    else {
        $multi = "1024";
    }
    // Handle case where we don't have a number, but are set to 'unlimited':
    if ($number === "unlimited") {
        return "unlimited";
    }
    // Handle cases where '*_b' or '*_l' already have a unit assigned:
    $pattern = '/^(.*)(K|M|G|T)$/';
    if (preg_match($pattern, $number, $matches, PREG_OFFSET_CAPTURE)) {
        return $number;
    }

    // Check for positive decimal number without unit:
    $pattern = '/^(\d*\.{0,1}\d+)$/';
    if (preg_match($pattern, $number, $matches, PREG_OFFSET_CAPTURE)) {
        // We have an integer or number w/o unit. So this is in pages.
        // Multiply with the factor of the page size:
        $number = $number*4096;
        // Get the length of the string and set the unit accordingly:
        $len = strlen($number);
        if ($len <= "3") {
            //return sprintf("%.${cnt}f$format", "$number");
        }
        if (($len > "3") && ($len <= "6")) {
            $format = "K";
            $mod = $multi;
            $number = $number/$mod;
        }
        if (($len > "6") && ($len <= "9")) {
            $format = "M";
            $mod = $multi*$multi;
            $number = $number/$mod;
        }
        if (($len > "9") && ($len <= "12")) {
            $format = "G";
            $mod = $multi*$multi*$multi;
            $number = $number/$mod;
        }
        if ($len > "12") {
            $format = "E";
            $mod = $multi*$multi*$multi*$multi;
            $number = $number/$mod;
        }
        return sprintf("%.${cnt}f$format", "$number");
    }
}

function simplify_number_diskspace ($number, $literal, $cnt, $extra) {

    // NOTE: Slightly different from 'simplify_number', as the diskspace
    // needs to be multiplied with another factor of 1024 if it is an integer!

    // Return our numbers nicely formatted.
    //
    // Arguments:
    //
    // $number:         The number we're formatting
    // $literal:        "K"  = one thousand = factor 1000
    //                  "KB" = one thousand = factor 1024
    // $cnt:            Number of digits after the dot
    //
    // $extra:          Text to display at the end of the output.
    //
    // Returns nicely formatted number including the factor.

    if ($literal == "K") {
        $multi = "1000";
    }
    elseif ($literal == "KB") {
        $multi = "1024";
    }
    else {
        $multi = "1024";
    }
    // Handle case where we don't have a number, but are set to 'unlimited':
    if ($number === "unlimited") {
        return "unlimited";
    }
    // Handle cases where '*_b' or '*_l' already have a unit assigned:
    $pattern = '/^(.*)(K|M|G|T)$/';
    if (preg_match($pattern, $number, $matches, PREG_OFFSET_CAPTURE)) {
        return $number;
    }

    // Check for positive decimal number without unit:
    $pattern = '/^(\d*\.{0,1}\d+)$/';
    if (preg_match($pattern, $number, $matches, PREG_OFFSET_CAPTURE)) {
        // Get the length of the string and set the unit accordingly:
        $len = strlen($number);
        if ($len <= "3") {
            $format = "";
            return sprintf("%.${cnt}f$format$extra", "$number");
        }
        if (($len > "3") && ($len <= "6")) {
            $format = "M";
            $mod = $multi;
            $number = $number/$mod;
        }
        if (($len > "6") && ($len <= "9")) {
            $format = "G";
            $mod = $multi*$multi;
            $number = $number/$mod;
        }
        if (($len > "9") && ($len <= "12")) {
            $format = "T";
            $mod = $multi*$multi*$multi;
            $number = $number/$mod;
        }
        if ($len > "12") {
            $format = "E";
            $mod = $multi*$multi*$multi*$multi;
            $number = $number/$mod;
        }
        return sprintf("%.${cnt}f$format$extra", "$number");
    }
}

// Generate ErrorMessages:
function ErrorMessage ($errMsg, $type="alert_red", $icon="alarm_bell", $dismissible=TRUE) {
    $diss_fill = '';
    if ($dismissible == TRUE) {
        $diss_fill = 'dismissible ';
    }
    $out = '<div class="alert ' . $diss_fill . '' . $type . '"><img width="40" height="36" src="/.adm/images/icons/small/white/' . $icon . '.png"><strong>' . $errMsg . '<br>&nbsp;' . '</strong></div>';
    return $out;
}

// Meaner used by /sitestats/summaryEmail:
function Meaner ($size, $count="", $mult="K", $nachkomma="2", $suffix="B") {
    // Prevent division by zero:
    if (($count == "0") || ($count == "")) {
        $count = "1";
    }
    if ($size == "0") {
        return "0K" . $suffix;
    }
    $out = simplify_number(roundToNearest($size / $count), $mult, $nachkomma) . $suffix;
    return $out;
}

// simpler simplify_number used by /sitestats/summaryEmail:
function SimNum ($number, $mult="K", $nachkomma="2", $suffix="B") {
    $out = simplify_number($number, $mult, $nachkomma) . $suffix;
    return $out;
}

// If the passed value is empty, it will be set to a default:
function defaulter ($number="0", $default = "0") {
    if ($number == "") {
        $number = $default;
    }
    return $number;
}

function stringshortener ($string, $length='15') {
    $lngt = strlen($string);
    $lngt = $lngt+5;
    if ($lngt > $length) {
        $diff = ($lngt-$length)*('-1');
        $outstring = substr($string, 0, $diff);
        $string = $outstring . '(...)';
    }
    return $string;
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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