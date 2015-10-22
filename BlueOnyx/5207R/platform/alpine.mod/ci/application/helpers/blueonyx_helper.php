<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * BlueOnyx Helper Library
 *
 * BlueOnyx Helper for Codeigniter
 *
 * @package   CI Blueonyx
 * @author    Michael Stauber
 * @copyright Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
 * @link      http://www.solarspeed.net
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */

// This function is used to log 403 errors to /var/log/admserv/adm_error with the username,
// the IP of the offender, the page where it happened and the browser that was used.
// If an URL string is supplied, we will redirect to that and exit. Do NOT call this function
// with an URL unless you have said good bye to CCE first!
// 
// Example Log Entry: 
//
// User admin (IP: 186.116.135.82) triggered a 403 on page /vsite/manageAdmin?MODIFY=1&_oid=2605555 with user agent Firefox 25.0
// 
function Log403Error($url = "") {
    $CI =& get_instance();

    $loginName = $CI->input->cookie('loginName');
    $userip = $CI->input->ip_address();

    if ($loginName == "") {
        $loginName = "-unknown or not logged in-";
    }

    $CI->load->library('user_agent');
    if ($CI->agent->is_browser()) {
        $agent = $CI->agent->browser().' '.$CI->agent->version();
    }
    elseif ($CI->agent->is_robot()) {
        $agent = $CI->agent->robot();
    }
    elseif ($CI->agent->is_mobile()) {
        $agent = $CI->agent->mobile();
    }
    else {
        $agent = 'Unidentified User Agent';
    }
    $source = "unknown";
    if (isset($_SERVER['REQUEST_URI'])) {
        if ($_SERVER['REQUEST_URI']) {
            $source = $_SERVER['REQUEST_URI'];
        }
    }
    error_log("User $loginName (IP: $userip) triggered a 403 on page $source with user agent $agent");
    if ($url != "") {
        header("location: $url");
        exit;
    }
}

// Function GetFormAttributes() walks through the $form_data and returns us the $parameters we want to
// submit to CCE. It intelligently handles checkboxes, which only have "on" set when they are ticked.
// In that case it pulls the unticked status from the hidden checkboxes and addes them to $parameters.
// It also transforms the value of the ticked checkboxes from "on" to "1". 
//
// Furthermore it intelligently handles textareas and turns their multiline strings into an urldecoded 
// and ampersand packed array in string format ready for submitting them to CODB. 
//
// Additionally it generates the form_validation rules for CodeIgniter.
//
// params: $i18n                i18n Object of the error messages
// params: $form_data           array with form_data array from CI
// params: $required_keys       array with keys that must have data in it. Needed for CodeIgniter's error checks
// params: $ignore_attributes   array with items we want to ignore. Such as Labels.
// return:                      array with keys and values ready to submit to CCE.

function GetFormAttributes ($i18n, $form_data, $required_keys=array(), $ignore_attributes=array()) {
    // Get $CI instance:
    $CI =& get_instance();

    // Required array setup:
    $attributes = array();
    $seen_checkboxes = array();
    $seen_textareas = array();
    $seen_radios = array();
    $checkbox_data_before_submit = array();
    $textarea_data_before_submit = array();
    $radio_data_before_submit = array();

    // Let the games begin:
    foreach ($form_data as $key => $value) {
        if (is_object($i18n)) {
            if (in_array($key, $required_keys)) {
                // This key is required. Create a CI form_validation rule that takes that into account:
                if (!is_array($value)) {
                    $CI->form_validation->set_rules($key, $i18n->get($key), 'trim|required|xss_clean');
                }
            }
            else {
                // This key is not required. Just do a form_validation rule with trim and xss_clean:
                if (!is_array($value)) {
                    $CI->form_validation->set_rules($key, $i18n->get($key), 'trim|xss_clean');
                }
            }
        }
        else {
            // This key is not required. Just do a form_validation rule with trim and xss_clean:
            if (!is_array($value)) {
                $CI->form_validation->set_rules($key, "N/A", 'trim|xss_clean');
            }
        }

        // Certain fields (like getSetSelector()) have arrays as values. We want to immediately
        // join them into a CODB-friendly storage format to make things a little easier:
        if (is_array($value)) {
            $value = array_to_scalar(array_values($value));
        }

        // Generate an array with the key => values we want to submit to CCE:
        if (!in_array($key, $ignore_attributes)) {
            // Key is not a key that we want to ignore.
            if (preg_match('/^checkbox-/', $key, $matches, PREG_OFFSET_CAPTURE)) {
                // This key is a hidden key from a checkbox. Extract the real key name:
                $new_key = preg_split('/^checkbox-/', $key);
                if (isset($new_key[1])) {
                    $the_new_key = $new_key[1];
                    // Add the real key name and the corresponding (old) value to $attributes:
                    $attributes[$the_new_key] = $value;
                    // Note down that we have seen this checkbox:
                    $seen_checkboxes[] = $the_new_key;
                    $checkbox_data_before_submit[$the_new_key] = $value;
                }
            }
            elseif (preg_match('/^textarea-/', $key, $matches, PREG_OFFSET_CAPTURE)) {
                // This key is a hidden key from a textarea. Extract the real key name:
                $new_ta_key = preg_split('/^textarea-/', $key);
                if (isset($new_ta_key[1])) {
                    $the_ta_new_key = $new_ta_key[1];
                    // Add the real key name and the corresponding (old) value to $attributes:
                    $attributes[$the_ta_new_key] = $value;
                    // Note down that we have seen this textarea:
                    $seen_textareas[] = $the_ta_new_key;
                    $textarea_data_before_submit[$the_ta_new_key] = $value;
                }
            }
            elseif (preg_match('/^radio-/', $key, $matches, PREG_OFFSET_CAPTURE)) {
                // This key is a hidden key from a radio selector. Extract the real key name:
                $new_radio_key = preg_split('/^radio-/', $key);
                if (isset($new_radio_key[1])) {
                    $the_radio_new_key = $new_radio_key[1];
                    // Add the real key name and the corresponding (old) value to $attributes:
                    $attributes[$the_radio_new_key] = $value;
                    // Note down that we have seen this textarea:
                    $seen_radios[] = $the_radio_new_key;
                    $radio_data_before_submit[$the_radio_new_key] = $value;
                }
            }           
            else {
                // This is not the hidden key and (old) value of a checkbox:
                if (in_array($key, $seen_checkboxes)) {
                    // This is a "real" checkbox with new data. If it's ticked, the value will be "on".
                    // We need to change the value to "1" instead:
                    if ($value == "on") {
                        $attributes[$key] = "1";
                    }
                    else {
                        $attributes[$key] = "0";
                    }
                }
                elseif (in_array($key, $seen_textareas)) {
                    // This is a "real" textarea with new data. 
                    // We need to make its payload CODB-friendly.
                    $attributes[$key] = urldecode(arrayToString(stringNToArray($value)));
                }
                elseif (in_array($key, $seen_radios)) {
                    // This is a "real" radio with new data. 
                    // We need to make its payload CODB-friendly.
                    $attributes[$key] = urldecode(arrayToString(stringNToArray($value)));
                }
                else {
                    // This is not a hidden or real checkbox, nor is it a textarea. We can add it right away:
                    $attributes[$key] = $value;
                }
            }
        }
    }
    // Finally a correctional run to handle checkboxes which were "on", but have been unticked:
    foreach ($seen_checkboxes as $key => $value) {
        if (isset($checkbox_data_before_submit[$value])) {
            if ((isset($checkbox_data_before_submit[$value])) && (!isset($form_data[$value]))) {
                $attributes[$value] = "0";
            }
        }
    }
    // Finally a correctional run to handle radio selectors which were "on", but have been unticked:
    foreach ($seen_radios as $key => $value) {
        if (isset($radio_data_before_submit[$value])) {
            if ((isset($radio_data_before_submit[$value])) && (!isset($form_data[$value]))) {
                $attributes[$value] = "0";
            }
        }
    }   
    return $attributes;
}

// Private function that takes current ItemID and returns the URL of the first menu child
// that the current user has access rights for. Example: User has access to "Active Monitor".
// Which is under "Server Management". In that case we only want him to have "Active Monitor"
// listed under "Server Management" and need to hide "Network Services", "Security" and 
// "Maintenance". So we return just the URL for /am/amStatus. If he also had the privileges
// to mess with "Network Services", we'd get the first URL of the first item of "Network Services"
// that he is privileged to see. Sounds simple, but is a tiny weeny itzi bitzy complicated:
function getURLofFirstChild($val, $ignore_items, $_SiteMap_items, $access=array()) {

    // Our first itemID can be an array of IDs or a single ID.
    // We will only process ONE item ID, so we pick the first
    // item off the array and ignore the rest for now:
    if (is_array($val)) {
        $first_item = array_shift(array_keys($val));
    }
    else {
        $first_item = $val;
    }

    // Find out which children this item ID has:
    $first_items_children = MenuChildren($first_item, $ignore_items, $_SiteMap_items, $access);

    // Sort the children based on their "order", so that the lowest order comes first:
    asort($first_items_children);

    // Go through the children one by one:
    foreach (array_keys($first_items_children) as $key => $itemID) {
        // Check if that menu child itself has other children:
        if (isset($_SiteMap_items[$itemID]["children"])) {
            // It does. So we extract the very first child from that:
            ksort($_SiteMap_items[$itemID]["children"]);
            $first_item = array_shift(array_values($_SiteMap_items[$itemID]["children"]));
            // Check if that grandchild has an URL set. It should, as our menus are at the
            // worst three levels deep ("root" / category header / actual menu entry):
            if (isset($_SiteMap_items[$first_item]["url"])) {
                // Ok, it has an URL. We return that and be done with this charade:
                return $_SiteMap_items[$first_item]["url"];
            }
        }
        else {
            // This child has no childs of its own. So we check if it has an URL set:
            if ((isset($_SiteMap_items[$itemID]["url"])) && (!isset($_SiteMap_items[$itemID]["children"]))) {
                // It has an URL set. So we return that and be done here:
                return $_SiteMap_items[$itemID]["url"];
            }
        }
    }

    // After all this trouble we still don't have a return URL? In that case we 
    // return the URL of the parent passed to us. Which might contain an URL.
    // Or it not, it returns NULL:
    return $_SiteMap_items[$first_item]["url"];
}

// Function to clean URLs:
// The Menu XML files have some [[variables]] in them that need to be replaced with
// the actual intended content. Such as the group ID or the FQDN. We do that here.
function fixInternalURLs($url, $substitute=array()) {

    // Start sane:
    $numCount = "0";

    if ((isset($substitute['group'])) && (isset($substitute['fqdn']))) {
        // Check if the URL has a [[variable]] that needs replacing:
        $pattern = '/\[\[[a-zA-Z0-9\-\_\.]{1,99}\]\]/';
        preg_match_all($pattern, $url, $matches);
        $numCount = count($matches[0], COUNT_RECURSIVE);

        if ($numCount > 0) {

            // Do the actual replacing:
            foreach ($matches[0] as $key => $value) {
                $patterns = array();
                $patterns[0] = '/\[\[/';
                $patterns[1] = '/\]\]/';
                $value = preg_replace($patterns, "", $value);
                $xpatterns = array();
                // Found [[VAR.group]]:
                if ($value == "VAR.group") {
                    // Replace with the group name:
                    $replacement = $substitute['group']; 
                }
                // Found [[VAR.hostname]]:
                if ($value == "VAR.hostname") {
                    // Replace with the FQDN of the Vsite the user belongs to:
                    $replacement = $substitute['fqdn'];
                }
                //if ($value == "VAR.title") { // <-- Not sure where this is used!
                //  $replacement = ... no idea!
                //} 
                $xpatterns[0] = "/\[\[$value\]\]/";
                // Actual replacement:
                if (isset($replacement)) {
                    $url = preg_replace($xpatterns, "" . $replacement . "", $url);
                }
            }
        }
    }
    // Return cleaned URL:
    return $url;
}

/**
 * initialize_languages($browserdetect)
 *
 * A helper function that defines which languages we support and which activates them for
 * usage. Depending on the language locale and the charset the generated pages will be 
 * rendered slightly different. 
 *
 * A cookie set locale always overrides anything that was gathered by browser detect. If
 * all fails we hail Mary (who sould have confessed to cheating instead) and fail back to 
 * English.
 *
 * @param VAR   $browserdetect  : TRUE or empty. Defines if we use browser detect or not.
 * @return ARR  array("locale" => $locale, "localization" => $localization, "charset" => $charset);
 */

function initialize_languages($browserdetect) {

    // Include BXBrowserLocale:
    include_once("BXBrowserLocale.php");

    // Start sane:
    $locale = 'en_US';
    $charset = 'UTF-8';

    $CI =& get_instance();
    $cookie_locale = $CI->input->cookie('locale');

    if ($browserdetect == "TRUE") {

        // Detect the browser locale to see if it is supported.
        // If not, fall back to 'en_US':
        $detected_locale = BXBrowserLocale::prefered_language();

        if ($detected_locale == 'en_US') {
            $locale = 'en_US';
            $localization = 'en-US';
            $loc = 'en';
        }
        elseif ($detected_locale == 'de_DE') {
            $locale = 'de_DE';
            $localization = 'de-DE';
            $loc = 'de';
        }
        elseif ($detected_locale == 'da_DK') {
            $locale = 'da_DK';
            $localization = 'da-DK';
            $loc = 'da';
        }
        elseif ($detected_locale == 'es_ES') {
            $locale = 'es_ES';
            $localization = 'es-ES';
            $loc = 'es';
        }
        elseif ($detected_locale == 'fr_FR') {
            $locale = 'fr_FR';
            $localization = 'fr-FR';
            $loc = 'fr';
        }
        elseif ($detected_locale == 'it_IT') {
            $locale = 'it_IT';
            $localization = 'it-IT';
            $loc = 'it';
        }
        elseif ($detected_locale == 'pt_PT') {
            $locale = 'pt_PT';
            $localization = 'pt-PT';
            $loc = 'pt';
        }
        elseif ($detected_locale == 'nl_NL') {
            $locale = 'nl_NL';
            $localization = 'nl-NL';
            $loc = 'nl';
        }
        elseif ($detected_locale == 'ja_JP') {
            $locale = 'ja_JP';
            $localization = 'ja-JP';
            $loc = 'ja';
        }
    }
    elseif ($cookie_locale == "en_US") {
        $locale = 'en_US';
        $localization = 'en-US';
        $loc = 'en';
    }
    elseif ($cookie_locale == "de_DE") {
        $locale = 'de_DE';
        $localization = 'de-DE';
        $loc = 'de';
    }
    elseif ($cookie_locale == "da_DK") {
        $locale = 'da_DK';
        $localization = 'da-DK';
        $loc = 'da';
    }
    elseif ($cookie_locale == "es_ES") {
        $locale = 'es_ES';
        $localization = 'es-ES';
        $loc = 'es';
    }
    elseif ($cookie_locale == "fr_FR") {
        $locale = 'fr_FR';
        $localization = 'fr-FR';
        $loc = 'fr';
    }
    elseif ($cookie_locale == "it_IT") {
        $locale = 'it_IT';
        $localization = 'it-IT';
        $loc = 'it';
    }
    elseif ($cookie_locale == "pt_PT") {
        $locale = 'pt_PT';
        $localization = 'pt-PT';
        $loc = 'pt';
    }
    elseif ($cookie_locale == "nl_NL") {
        $locale = 'nl_NL';
        $localization = 'nl-NL';
        $loc = 'nl';
    }
    elseif ($cookie_locale == "ja_JP") {
        $locale = 'ja_JP';
        $localization = 'ja-JP';
        $loc = 'ja';
    }
    else {
        $locale = 'en_US';
        $localization = 'en-US';
        $loc = 'en';
    }

    $localecharset = 'UTF-8';

    return array("locale" => $locale, "localization" => $localization, "charset" => $charset, "localecharset" => $localecharset, "loc" => $loc);
}

// This function is used to get the Newsfeed off www.blueonyx.it:
function getRssfeed($rssfeed, $cssclass="", $encode="auto", $howmany=10, $mode=0) {
    // $encode e[".*"; "no"; "auto"]

    // $mode e[0; 1; 2; 3]:
    // 0 = only titel and link of the items
    // 1 = Titel and link
    // 2 = Titel, link and description
    // 3 = 1 & 2
    
    $bx_title = array();
    $bx_date = array();
    $bx_desc = array();
    $bx_link = array();
    
    // Pull the RSS feed:
    $data = get_data($rssfeed);
    if(strpos($data,"</item>") > 0) {
        preg_match_all("/<item.*>(.+)<\/item>/Uism", $data, $items);
        $atom = 0;
    }
    elseif(strpos($data,"</entry>") > 0) {
        preg_match_all("/<entry.*>(.+)<\/entry>/Uism", $data, $items);
        $atom = 1;
    }

    if (!isset($atom)) {
        return NULL;
    }
    
    // Encoding:
    if($encode == "auto") {
        preg_match("/<?xml.*encoding=\"(.+)\".*?>/Uism", $data, $encodingarray);
        if (isset($encodingarray[1])) {
            $encoding = $encodingarray[1];
        }
    }
    else {
        $encoding = $encode;
    }
    
    // Titel and link:
    if ($mode == 1 || $mode == 3) {
        if(strpos($data,"</item>") > 0) {
            $data = preg_replace("/<item.*>(.+)<\/item>/Uism", '', $data);
        }
        else {
            $data = preg_replace("/<entry.*>(.+)<\/entry>/Uism", '', $data);
        }
        preg_match("/<title.*>(.+)<\/title>/Uism", $data, $channeltitle);
        if($atom == 0) {
            preg_match("/<link>(.+)<\/link>/Uism", $data, $channellink);
        }
        elseif($atom == 1) {
            preg_match("/<link.*alternate.*text\/html.*href=[\"\'](.+)[\"\'].*\/>/Uism", $data, $channellink);
        }

        $channeltitle = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $channeltitle);
        $channellink = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $channellink);
    }
    // Check if we get multiple news items back. If not, a proxy or a badly configured router may be interfering:
    $counter = count ($items);
    if ($counter) {
        // Titel, link and description of the news items:
        foreach ($items[1] as $item) {
        preg_match("/<title.*>(.+)<\/title>/Uism", $item, $title);
        if($atom == 0) {
            preg_match("/<link>(.+)<\/link>/Uism", $item, $link);
        }
        elseif($atom == 1) {
            preg_match("/<link.*alternate.*text\/html.*href=[\"\'](.+)[\"\'].*\/>/Uism", $item, $link);
        }
        
        if($atom == 0) {
            preg_match("/<description>(.*)<\/description>/Uism", $item, $description);
        }
        elseif($atom == 1) {
            preg_match("/<summary.*>(.*)<\/summary>/Uism", $item, $description);
        }

        preg_match("/<pubDate>(.*)-(.*)<\/pubDate>/Uism", $item, $pubDate);

        $bx_title[] = $title[1];
        $bx_date[] = $pubDate[1];
        $bx_desc[] = $description[1];
        $bx_link[] = $link[1];

        if ($howmany-- <= 1) break; }
        $payload["_bx_title"] = $bx_title;
        $payload["_bx_date"] = $bx_date;
        $payload["_bx_desc"] = $bx_desc;
        $payload["_bx_link"] = $bx_link;
    }
    else {
        // Did not receive expected results. Set bx_title to something we can catch and process:
        $payload["_bx_title"] = "n/a";
    }
    return $payload;
}

function areWeOnline($domain, $awo_timeout = "10") {
    // Check to see if we're online and if the desired URL is reachable.
    // Returns true, if URL is reachable, false if not

    if (!isset($awo_timeout)) {
        $awo_timeout = "10";
    }

    // Initialize curl:
    $curlInit = curl_init($domain);
    curl_setopt($curlInit,CURLOPT_TIMEOUT, $awo_timeout);
    curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT, $awo_timeout);
    curl_setopt($curlInit,CURLOPT_HEADER,true);
    curl_setopt($curlInit,CURLOPT_NOBODY,true);
    curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

    // Get answer
    $response = curl_exec($curlInit);

    // Close curl:
    curl_close($curlInit);

    // Generate response:
    if ($response) return true;
    return false;
}

function get_data($url) {
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    curl_setopt($ch, CURLOPT_USERAGENT, 'BlueLinQ/1.0');
    $error = curl_error($ch);
    $data = curl_exec($ch);
    if($data === false) {
        $data = $error;
    }
    curl_close($ch);
    return $data;
}

/**
 * PoorMansBabelFish($text, $language, $domain)
 *
 * A helper function that translates text via PHP's i18n support. We use this on
 * pages where we can't use Sausalito's i18n support, which needs CceClient.
 *
 * @param VAR   $text           : msgid of the string we need to translate
 * @param VAR   $language       : language identifier. Like de_DE for German.
 * @param VAR   $domain         : name of the gettext file without extension
 * @return VAR translated string
 */

function PoorMansBabelFish ($text, $language, $domain) {

    putenv("LANG=$language"); 
    setlocale(LC_ALL, $language);

    $directory = "/usr/share/locale";

    setlocale( LC_MESSAGES, $language);
    bindtextdomain($domain, $directory);
    textdomain($domain);
    bind_textdomain_codeset($domain, 'UTF-8');

    return gettext($text);
}

function is_HTTPS () {
    if (isset($_SERVER['HTTPS'])) {
        return TRUE;
    }
    else {
        return FALSE;
    }
    return FALSE;
}

function PageURLReferer () {
    if(isset($_SERVER['HTTP_REFERER'])) {
        $ng = $_SERVER['HTTP_HOST'];
        $match = "/^http:(.*)$ng/";
        $baseline = preg_replace($match, '', $_SERVER['HTTP_REFERER']);
    }
    else {
        $baseline = "";
    }
    return $baseline;
}

/**
 * MenuChildren($root, $ignore_items, $_SiteMap_items)
 *
 * A helper function that parses the Menu XML files and returns
 * the $_SiteMap_items object with all menu entries.
 *
 * @param VAR   $root       : ItemID of the menu entry whose childs we're looking for
 * @param ARR   $ignore_items   : array of menu items we ignore in this search
 * @param ARR   $_SiteMap_items : Our array with the complete SiteMap
 * @return ARR $root_children_sort_order
 */

function MenuChildren($root, $bx_ignore_items, $SiteMap_items, $access=array()) {

    // Build an array that contains all children of a given menu entry from our $SiteMap_items:
    $root_children = elements($SiteMap_items[$root]['children'], $SiteMap_items);

    $parent_we_search_for = $root;

    // Build the array $root_children_sort_order which will eventually contain
    // just the 'id' of the child as keys and the 'order' as values.
    // That array will be sorted with the item with the lowest ID comming first.
    //
    // However, there may be some menu items that we don't want to show. They can 
    // be listed in the array $bx_ignore_items. If we have these, we simply ignore
    // them here.

    $root_children_sort_order = array();

    foreach ($root_children as $itemID => $val) {
      if (!in_array($itemID, $bx_ignore_items)) { // See, here we ignore the ignore items:
        if (isset($val['parents'])) {
            $temp_arr = elements(array('id', 'order', 'access', 'requiresChildren', 'children'), $val['parents'], NULL);
            // Catches items which only have a single parent:
            if ($temp_arr['id'] == $parent_we_search_for) {
                // Is this an item that anyone can access? Or one that we specifically have access rights for?
                if (($temp_arr['access'] == NULL) || (in_array($temp_arr['access'], $access))) {
                    // We have access. However, we don't know if this is a 2nd level menu child
                    // or a third level menu entry. So we use getURLofFirstChild() again and see
                    // if it returns an URL or NULL. If it has no URL as return, then this is either
                    // a 2nd level menu with entries to which we don't have access. Or it's a
                    // 3rd level menu entry to which we don't have access. We only add this
                    // entry as child to the parent if we have access and need access:
                    if (getURLofFirstChild($itemID, array(), $SiteMap_items, $access) != NULL) {
                        // We have access, so we add it. But only if we have access to the item 
                        // itself, too:
//                      print_rp($itemID);
//                      print_rp($SiteMap_items[$itemID]['parents']);
//                      print_rp($access);
//                      print_rp($temp_arr['access']);
                        if (isset($SiteMap_items[$itemID]['parents'])) {
                            if (in_array($temp_arr['access'], $SiteMap_items[$itemID]['parents'])) {
                                $root_children_sort_order[$itemID] = $temp_arr['order'];
                            }
                        }
                        else {
                            $root_children_sort_order[$itemID] = $temp_arr['order'];
                        }
                    }
                }
            }
            // Catches items which have multiple parents:
            if (isset($val['parents'][0])) {
                foreach ($val['parents'] as $par_key => $par_val) {
                    $temp_arr = elements(array('id', 'order', 'access', 'requiresChildren', 'children'), $par_val, NULL);
                    if ($temp_arr['id'] == $parent_we_search_for) {
                        // Is this an item that anyone can access? Or one that we specifically have access rights for?
                        if (($temp_arr['access'] == NULL) || (in_array($temp_arr['access'], $access))) {
                            // We have access. However, we don't know if this is a 2nd level menu child
                            // or a third level menu entry. So we use getURLofFirstChild() again and see
                            // if it returns an URL or NULL. If it has no URL as return, then this is either
                            // a 2nd level menu with entries to which we don't have access. Or it's a
                            // 3rd level menu entry to which we don't have access. We only add this
                            // entry as child to the parent if we have access and need access:
                            if (getURLofFirstChild($itemID, array(), $SiteMap_items, $access) != NULL) {
                                // We have access, so we add it:
                                $root_children_sort_order[$itemID] = $temp_arr['order'];
                            }
                        }
                    }
                }
            }
        }
      }
    }
    // Sort $root_children_sort_order by numeric value 'order', lowest first:
    asort($root_children_sort_order);
    return $root_children_sort_order;
}

/**
 * generateSiteMap()
 *
 * Menu related:
 *
 * A helper function that returns an unsorted array of all menu items with all
 * information that is required to build the menu. However, this also contains
 * information that the current user may not be privileged to see.
 *
 * @param  ARR      $debug              : If set to TRUE, it dumps the array with print_rp()
 * @param  ARR      $access             : Access rights of the current users
 * @param  ARR      $CceClient          : Current CceClient Object that this user is using.
 * @return ARR      $_SiteMap_items
 */

function generateSiteMap($debug = FALSE, $access, $CceClient, $substitutes) {

    // Location of the directory with the BX Menus:
    $menu_XML_dir = '/usr/sausalito/ui/chorizo/menu/';

    // Get a fileMap of /usr/sausalito/ui/chorizo/menu/:
    $map = directory_map($menu_XML_dir, FALSE, FALSE);

    // Pre-define array for our XML files:
    $xml_files = array();

    // The fileMap $map is pretty detailed. Let us build an array that has all
    // paths to XML files in it and contains them in an easily accessible way:
    foreach($map as $key => $val) {
        foreach($map[$key] as $key_zwo => $val_zwo) {
            // This handles 'base' and 'vendor' dirs:
            if (is_array($map[$key][$key_zwo])) {
                foreach($map[$key][$key_zwo] as $key_drei => $val_drei) {
                    // We're only interested in XML files:
                    if (preg_match('/\.xml$/', $val_drei)) {
                        $xml_files[] = $menu_XML_dir . "$key" . '/' .  $key_zwo . '/' . $val_drei;
                    }
                }
            }
            else {
                // This handles 'palette' and other short pathed XML locations:
                // We're only interested in XML files:
                if (preg_match('/\.xml$/', $map[$key][$key_zwo])) {
                    $xml_files[] = $menu_XML_dir . "$key" . '/' .  $map[$key][$key_zwo];
                }
            }
        }
    }

    // Set up an empty $_SiteMap_items array:
    $_SiteMap_items = array();

    for($i = 0; $i < count($xml_files); $i++) {
        // Read in each XML file:
        $xml_data = read_file($xml_files[$i]);

        // For debugging - print the path and filename:
        //echo "$xml_files[$i]<pre>";

        // Convert the raw XML data into an array:
        //
        // This is mightily fucking brilliant and fast! Thanks to Eric Potvin for this amazing idea!
        // See: http://www.bookofzeus.com/articles/convert-simplexml-object-into-php-array/
        $xml = json_decode(json_encode((array) simplexml_load_string($xml_data)), 1);

        // Array preparation (we want to start fresh during each iteration):
        $item = array();

        // Start the extraction procedure to get all menu items we need:
        // First create items of the easily extractable information:
        $k = elements(array('id', 'description', 'label', 'type', 'url', 'window', 'imageOff', 'imageOn', 'requiresChildren', 'children', 'module', 'icon', 'icononly'), $xml['@attributes'], NULL);
        $itemId = $item['id'] = $k['id'];
        $item['description'] = $k['description'];
        $item['label'] = $k['label'];
        $item['type'] = $k['type'];
        $item['url'] = fixInternalURLs($k['url'], $substitutes);
        $item['window'] = $k['window'];
        $item['imageOff'] = $k['imageOff'];
        $item['imageOn'] = $k['imageOn'];
        $item['requiresChildren'] = $k['requiresChildren'];
        $item['children'] = $k['children'];
        $item['module'] = $k['module'];
        $item['icon'] = $k['icon'];
        $item['icononly'] = $k['icononly'];

        // Now the complicated stuff:
        //
        // We need to extract the 'parent id' of this object. To make matters worse: It may have multiple parents!
        // And as if it ain't enought, each of these 'parent id' entries may have an optional access restriction.
        // But CodeIgniter's elements() function is a time saviour, so this is mean and clean:

        // We check if there is a 'parent' field in the array to begin with:
        if (isset($xml['parent'])) {
            // We loop through the results:
            $l = "";
            foreach($xml['parent'] as $key => $val) {
                // If there is directly an '@attributes' element, then this object only has one 'parent':
                if (isset($xml['parent']['@attributes'])) {
                  // Get Id of the single parent, the sort order and the access:
                  $item["parents"] = elements(array('id', 'order', 'access'), $xml['parent']['@attributes'], NULL);
                  // Extract 'access require' correctly as well:
                  if (isset($xml['parent']['access'])) {
                    $l = elements(array('require'), $xml['parent']['access']['@attributes'], NULL);
                  }
                }
                // If the $key is an integer and $val is an array, then we have multiple parents:
                if ((is_int($key) === true) && (is_array($val))) {
                  $item["parents"][] = elements(array('id', 'order', 'access'), $val['@attributes'], NULL);
                  // Extract 'access require' correctly as well:
                  if (isset($xml['parent'][$key]['access']['@attributes']['require'])) {
                    $l['require'][] = $xml['parent'][$key]['access']['@attributes']['require'];
                  }
                }

                // Stuff 'access require' into $item during this post processing:
                if (isset($l['require'])) {
                    // But only do so, if the current user has access to it!
                    if (($l['require'] == NULL) || (in_array($l['require'], $access))) {
                        $item["parents"]['access'] = $l['require'];
                    }
                    else {
                        // This user does not have access to this item.
                        // Remove the item: 
                        unset($item);
                    }
                }
            }
        }
        if (isset($item)) {
            // We still do have an item, so we add it to the $_SiteMap_items:
            $_SiteMap_items["$itemId"] = $item;
        }
    }

    // Now we need to populate the $_SiteMap_items['children'] fields.
    // This makes sure that our siteMap contains not only the entries 
    // to let us know who the parents are, but it also tells us which 
    // children (if any) an item has.
    $itemIds = array_keys($_SiteMap_items);
    foreach ($itemIds as $itemId) {
        $item = $_SiteMap_items[$itemId];
        // Create a list of children for this item
        if (isset($_SiteMap_items[$itemId]["parents"])) {
            $h = array();
            foreach($_SiteMap_items[$itemId]["parents"] as $parentkey => $parentval) {
                // Multiple parents found:
                if ((is_int($parentkey) === true) && (is_array($parentval))) {
                    $h = $parentval['id'];
                    $order = "";
                    // Loop through the various parents:
                    foreach ($item['parents'] as $key => $value) {
                        if ($value['id'] == $parentval['id']) {
                            // Find out the sort order:
                            $order = $value['order'];
                        }
                    }
                    // Make sure the sort order isn't already taken by another menu item:
                    if (isset($_SiteMap_items[$h]['children'][$order])) {
                        print_rp("ERROR: Menu item with the ID '$itemId' has the same sort order as item " . $_SiteMap_items[$h]['children'][$order]);
                        exit;
                    }

                    // Store the sort order of the children:
                    $_SiteMap_items[$h]['children'][$order] = $itemId;
                }
                else {
                    // Single parent found:
                    if ($parentkey == "id") {
                        // Get the sort order:
                        $order = $_SiteMap_items[$itemId]['parents']['order'];
                        // Make sure the sort order isn't already taken by another menu item:
                        if (isset($_SiteMap_items[$parentval]['children'][$order])) {
                            print_rp("ERROR: Menu item with the ID '$itemId' has the same sort order as item " . $_SiteMap_items[$parentval]['children'][$order]);
                            exit;
                        }
                        // Store the sort order of the children:
                        $_SiteMap_items[$parentval]['children'][$order] = $itemId;
                    }
                }
            }
        }
    }

    // At this point our $_SiteMap_items is complete and every item is populated
    // with all info. Like which children it has. What parents it has. And so on.
    // This is the full sitemap and it contains items that the user might not be
    // privileged to see. We handle the actual access rights in the function
    // MenuChildren(), which is called via getURLofFirstChild(). Which in turn
    // is used to populate menu entries with the correct URL of the first item
    // that the user has rights to see.

    if ($debug == TRUE) {
        echo "----_SiteMap_items:----<br>";
        print_rp($_SiteMap_items);
    }

  return $_SiteMap_items;
}

/**
 *
 *  Simple function to detect if a string is UTF-8 or not.
 *
 */


function detectUTF8($string) {
        return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]                  # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]             # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]             # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}          # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}              # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}          # plane 16
        )+%xs', $string);
}

function Utf8Encode($text) {
  if (mb_detect_encoding($text, "JIS, UTF-8, EUC-JP, ISO-8859-1, ISO-8859-15, windows-1252") == "EUC-JP") {
    $text = mb_convert_encoding($text, "UTF-8", "EUC-JP");
  }
  if (detectUTF8($text) == "1" ) {
    return $text;
  }
  return BXEncoding::toUTF8($text);
}

/**
 * bx_charsetsafe()
 *
 * This is bloody anoying: Say someone with a Japanese locale sets a username in
 * Japanese. An English serverAdmin or siteAdmin then sees the username garbled.
 * Likewise: German umlauts, or foreign acutes which work fine in 'UTF-8' will look
 * garbled in 'EUC-JP'. So we use this function to sanitize the fullName of users
 * (and other things). 
 *
 * The function takes the string as argument, checks if it is UTF-8 and if not, 
 * converts it. If it is already UTF-8, it gets returned outright. 
 *
 * @param  VAR  $string     : string we want to convert to a safe charset for display
 * @return VAR  $string     : sanitized string or original string
 */

function bx_charsetsafe($string) {

    if (detectUTF8($string) == "1") {
        return BXEncoding::toUTF8($string);
    }
    return $string;
}

/**
 * bx_profiler()
 *
 * A helper function for profiling
 *
 * @param TRUE or FALSE
 * @return TRUE
 */


function bx_profiler($enabled = FALSE) {
  $CI =& get_instance();
  // Profiling and Benchmarking:
  $sections = array(
      'config'  => TRUE,
      'queries' => FALSE,
      'get' => TRUE,
      'http_headers' => TRUE,
      'memory_usage' => TRUE,
      'post' => TRUE,
      'uri_string' => TRUE,
      'controller_info' => TRUE,
      'benchmarks' => TRUE
      );
  $CI->output->set_profiler_sections($sections);
  $CI->output->enable_profiler($enabled);
  return $enabled;
}

/**
 * print_rp()
 *
 * A helper function that mimicks print_r, but encapsulates the results in '<pre></pre>' tags.
 *
 * @param ARR   $prp        : array we want to print
 * @return NONE
 */

function print_rp($prp) {
  echo "<pre>";
  print_r($prp);
  echo "/<pre>";
}

/**
 * init_libraries()
 *
 * A helper function for loading all our usual libraries and helpers.
 * Loading them all with two lines of code is more comforting than 
 * having a dozen load->... lines in every controller.
 *
 * @param none
 * @return TRUE
 */

function init_libraries() {

  $CI =& get_instance();

  // Need to load 'user_agent' as we need to access the browser info:
  $CI->load->library('user_agent');
  // Need to load 'parser' to load our template parser:
  $CI->load->library('parser');
  // Need to load the 'cookie' helper:
  $CI->load->helper('cookie');
  // Load the array helper:
  $CI->load->helper('array');
  // Load the string helper:
  $CI->load->helper('string');
  // Load URL helper:
  $CI->load->helper('url');
  // Load the text helper:
  $CI->load->helper('text');

  // Load CI helper and libraries for form validation and handling:
  $CI->load->helper(array('form', 'url'));
  $CI->load->library('form_validation');

  // Load Directory helper:
  $CI->load->helper('directory');
  // Load File helper:
  $CI->load->helper('file');

  // Need to load 'I18n' for localization and 'CceClient' for access to CCE:
  $CI->load->library('I18n');
  $CI->load->library('CceClient');
  $CI->load->library('BXEncoding');

  // Load UIFC NG library:
  $CI->load->helper('uifc_ng');

  // Load ArrayPacker:
  include_once("ArrayPacker.php");

  return TRUE;

}

/**
 * bx_pw_check()
 *
 * A helper function for checking if passwords are good enough.
 *
 * @param TRUE or FALSE
 * @return TRUE
 */


function bx_pw_check($i18n, $password = "", $pass_repeat = "") {

        // Start sane:
        $my_errors = array();

        $CI =& get_instance();

        // Get loginName:
        $loginName = $CI->input->cookie('loginName');

        if ((!isset($loginName)) || ($loginName == "")) {
            // This handles pw-checks in Wizard. We might not yet have a cookie:
            $loginName = 'admin';
        }

        // We do have a pass_repeat, but it's not identical to the password:
        if (($pass_repeat != "") && ($password != $pass_repeat)) {
            $my_errors[] = ErrorMessage($i18n->interpolate("[[palette.pw_not_identical]]"));
        }
        elseif (strcasecmp($loginName, $password) == 0) {
            // Username == Password? Baaaad idea!
            $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-password-equals-username]]"));
        }
        elseif (($pass_repeat == "") || ($pass_repeat == "")) {
            // Either password or repeat password are empty:
            $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-password-invalid]]") . " ". $i18n->get("[[base-user.error-invalid-password]]"));
        }
        elseif ($password) {

            if (function_exists('crack_opendict')) {

                // Open CrackLib Dictionary for usage:
                @$dictionary = crack_opendict('/usr/share/dict/pw_dict');

                // Perform password check with cracklib:
                $check = crack_check($dictionary, $password);

                // Retrieve messages from cracklib:
                $diag = crack_getlastmessage();

                if ($diag == 'strong password') {
                    // Nothing to do. Cracklib thinks it's a good password.
                }
                else {

                    // Parse the return strings from cracklib and localize them:
                    if (preg_match('/^it\'s WAY too short$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_way_too_short]]");
                    }
                    elseif (preg_match('/^it is too short$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_too_short]]");
                    }
                    elseif (preg_match('/^it does not contain enough DIFFERENT characters$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_not_nuff_different]]");
                    }
                    elseif (preg_match('/^it is all whitespace$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_all_whitespace]]");
                    }
                    elseif (preg_match('/^it is too simplistic\/systematic$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_too_simple]]");
                    }
                    elseif (preg_match('/^it looks like a National Insurance (.*)$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_insurance_number]]");
                    }
                    elseif (preg_match('/^it is based on a dictionary word$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_dictionary_word]]");
                    }
                    elseif (preg_match('/^it is based on a \(reversed\) dictionary word$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_reversed_dictionary_word]]");
                    }
                    elseif (preg_match('/^strong password$/', $diag)) {
                        $diag_result = $i18n->getHtml("[[palette.pw_strong_password]]");
                    }
                    else {
                        // In case the localization fails, return the cracklib output directly:
                        $diag_result = $diag;
                    }

                    $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-password-invalid]]") . '<br>' . $diag_result);
                    $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-invalid-password]]"));
                }

                // Close cracklib dictionary:
                crack_closedict($dictionary);
            }
            else {
                // No Cracklib support available. We have alternatives, though:

                $CI =& get_instance();
                $CI->load->library('StupidPass');
                // Override the default errors messages
                $hardlang = array(
                'length' => $i18n->getHtml("[[palette.pw_way_too_short]]"),
                'upper'  => $i18n->getHtml("[[palette.pw_not_nuff_different]]"),
                'lower'  => $i18n->getHtml("[[palette.pw_not_nuff_different]]"),
                'numeric'=> $i18n->getHtml("[[palette.pw_too_simple]]"),
                'special'=> $i18n->getHtml("[[palette.pw_too_simple]]"),
                'common' => $i18n->getHtml("[[palette.pw_dictionary_word]]"),
                'environ'=> $i18n->getHtml("[[palette.pw_too_simple]]"));

                // Supply reference of the environment (company, hostname, username, etc)
                $environmental = array('blueonyx', 'admin');
                $sp = new StupidPass(40, $environmental, '/usr/sausalito/ui/chorizo/ci/application/libraries/stupid-pass/StupidPass.default.dict', $hardlang);
                if ($sp->validate($password) === false) {
                    $PWerrors = $sp->get_errors();
                    $diag_result = $PWerrors[0];
                    $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-password-invalid]]") . '<br>' . $diag_result);
                    $my_errors[] = ErrorMessage($i18n->get("[[base-user.error-invalid-password]]"));
                }
                else {
                    $diag_result = $i18n->getHtml("[[palette.pw_strong_password]]");
                }
            }
        }

        if (is_array($my_errors)) {
            if (count($my_errors) >= "1") {
                return $my_errors;
            }
        }
}

/**
 * format_bytes()
 *
 * A helper function used by /mysql/mysqlserver/
 *
 * @param SIZE
 * @return SIZE
 */

function format_bytes ( $size ) {
    switch ( $size ) {
        case $size > 1000000:
            return number_format(ceil($size / 1000000)) . "mb";
            break;
        case $size > 1000:
            return number_format(ceil($size / 1000)) . "k";
            break;
        default:
            return number_format($size) . "b";
            break;
    }
}

function br2nl($str) {
   $str = preg_replace("/(\r\n|\n|\r)/", "", $str);
   return preg_replace("=<br */?>=i", "\n", $str);
}

/**
 * str_split_php4()
 *
 * A helper function used by /console/consolelogins
 *
 * @param SIZE
 * @return SIZE
 */

// str_split_php4
function str_split_php4( $text, $min, $max ) {
    // place each character of the string into and array
    $array = array();
    for ( $i=0; $i < strlen( $text ); ){
        $key = NULL;
        for ( $j = 0; $j < $max; $j++, $i++ ) {
            if ($j >= $min) {
                $key .= $text[$i];
            }
        }
        array_push( $array, $key );
    }
    return $array;
}

/**
 * formspecialchars()
 *
 * A helper function used to clean up output to make it HTML-Safe.
 * Can be run on arrays AND strings, but will always return strings.
 * Does safe encoding based on user charset, too.
 *
 * @param $input
 * @return $output
 */


function formspecialchars($var) {
        $pattern = '/&(#)?[a-zA-Z0-9]{0,};/';

        if (is_array($var)) {    // If variable is an array
            $out = array();      // Set output as an array - for now
            foreach ($var as $key => $v) {     
                $out[$key] = formspecialchars($v);         // Run formspecialchars on every element of the array and return the result. Also maintains the keys.
            }
            // Now that we're done with the array, we turn it back into a string:
            $out = implode("", $out);
        } else {
            $out = $var;
            $lang_helper = initialize_languages(FALSE); // will return $lang_helper['charset'] which contains the client used charset
            $out = htmlspecialchars(stripslashes(trim($out)), ENT_QUOTES, $lang_helper['charset']);     // Trim the variable, strip all slashes, and encode it
        }
        return $out;
}

/**
  * 
  * This gets the timezone offset based on the olson code.
  * In this code it is used to find the offset between the given olson code and UTC, but can be used to convert other differences
  * 
  * @param string $remote_tz TZ string
  * @param string $origin_tz TZ string, defaults to UTC
  * @return int offset in seconds
  */

function ln_get_timezone_offset($remote_tz, $origin_tz = 'UTC') {
        $origin_dtz = new DateTimeZone($origin_tz);
        $remote_dtz = new DateTimeZone($remote_tz);
        $origin_dt = new DateTime("now", $origin_dtz);
        $remote_dt = new DateTime("now", $remote_dtz);
        $offset = $remote_dtz->getOffset($remote_dt) - $origin_dtz->getOffset($origin_dt);
        return $offset;
}

/**
 * Converts a timezone difference to be displayed as GMT +/-
 * 
 * @param string $timezone TZ time
 * @return string text with GMT
 */

function ln_get_timezone_offset_text($timezone){
        $time = ln_get_timezone_offset($timezone);

        $minutesOffset = $time/60;
        $hours = floor(($minutesOffset)/60);
        $minutes = abs($minutesOffset%60);
        $minutesFormatted = sprintf('%02d', $minutes);
        $plus = '';
        if($time >= 0){
            $plus = '+';
        }
        $GMToff = 'GMT '.$plus.$hours.':'.$minutesFormatted;
        return $GMToff;
}

/**
 * This is for formatting how the timezone option displays.
 * It can be converted to include current time, not include gmt or anything like that.
 * 
 * @param string $timezone TZ time
 * @param string $text format select box option
 */
function ln_display_timezone_option($timezone, $text, $value) {
        $selectedTZ = '';
        if ($value == $timezone) {
            $selectedTZ = " SELECTED ";
        }
        $out = '<option ' . $selectedTZ . 'value="' . $timezone .'">' . '(' . ln_get_timezone_offset_text($timezone) .') ' . $text . '</option>' . "\n";
        return $out;
}

/**
 *  The concise list of timezones.  This generates the html wherever it is called
 */

function ln_display_timezone_selector($value = "") {
        $out = '<select name="timezoneSelectDropdown">' . "\n"
        . ln_display_timezone_option('Pacific/Auckland', 'International Date Line West', $value)
        . ln_display_timezone_option('Pacific/Midway', 'Midway Island, Samoa', $value)
        . ln_display_timezone_option('US/Hawaii', 'Hawaii', $value)
        . ln_display_timezone_option('US/Alaska', 'Alaska', $value)
        . ln_display_timezone_option('US/Pacific', 'Pacific Time (US & Canada)', $value)
        . ln_display_timezone_option('America/Tijuana', 'Tijuana, Baja California', $value)
        . ln_display_timezone_option('America/Phoenix', 'Arizona', $value)
        . ln_display_timezone_option('America/Chihuahua', 'Chihuahua, La Paz, Mazatlan', $value)
        . ln_display_timezone_option('US/Mountain', 'Mountain Time (US & Canada)', $value)
        . ln_display_timezone_option('America/Cancun', 'Central America', $value)
        . ln_display_timezone_option('US/Central', 'Central Time (US & Canada)', $value)
        . ln_display_timezone_option('America/Mexico_City', 'Guadalajara, Mexico City, Monterrey', $value)
        . ln_display_timezone_option('Canada/Saskatchewan', 'Saskatchewan', $value)
        . ln_display_timezone_option('America/Lima', 'Bogota, Lima, Quito, Rio Branco', $value)
        . ln_display_timezone_option('US/Eastern', 'Eastern Time (US & Canada)', $value)
        . ln_display_timezone_option('US/East-Indiana', 'Indiana (East)', $value)
        . ln_display_timezone_option('Canada/Atlantic', 'Atlantic Time (Canada)', $value)
        . ln_display_timezone_option('America/Caracas', 'Caracas, La Paz', $value)
        . ln_display_timezone_option('America/Manaus', 'Manaus', $value)
        . ln_display_timezone_option('America/Santiago', 'Santiago', $value)
        . ln_display_timezone_option('Canada/Newfoundland', 'Newfoundland', $value)
        . ln_display_timezone_option('America/Sao_Paulo', 'Brasilia', $value)
        . ln_display_timezone_option('America/Argentina/Buenos_Aires', 'Buenos Aires, Georgetown', $value)
        . ln_display_timezone_option('America/Godthab', 'Greenland', $value)
        . ln_display_timezone_option('America/Montevideo', 'Montevideo', $value)
        . ln_display_timezone_option('Atlantic/South_Georgia', 'Mid-Atlantic', $value)
        . ln_display_timezone_option('Atlantic/Cape_Verde', 'Cape Verde Is.', $value)
        . ln_display_timezone_option('Atlantic/Azores', 'Azores', $value)
        . ln_display_timezone_option('Africa/Casablanca', 'Casablanca, Monrovia, Reykjavik', $value)
        . ln_display_timezone_option('UTC', 'Greenwich Mean Time : Dublin, Edinburgh, Lisbon, London', $value)
        . ln_display_timezone_option('Europe/Amsterdam', 'Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna', $value)
        . ln_display_timezone_option('Europe/Belgrade', 'Belgrade, Bratislava, Budapest, Ljubljana, Prague', $value)
        . ln_display_timezone_option('Europe/Brussels', 'Brussels, Copenhagen, Madrid, Paris', $value)
        . ln_display_timezone_option('Europe/Sarajevo', 'Sarajevo, Skopje, Warsaw, Zagreb', $value)
        . ln_display_timezone_option('Africa/Windhoek', 'West Central Africa', $value)
        . ln_display_timezone_option('Asia/Amman', 'Amman', $value)
        . ln_display_timezone_option('Europe/Athens', 'Athens, Bucharest, Istanbul', $value)
        . ln_display_timezone_option('Asia/Beirut', 'Beirut', $value)
        . ln_display_timezone_option('Africa/Cairo', 'Cairo', $value)
        . ln_display_timezone_option('Africa/Harare', 'Harare, Pretoria', $value)
        . ln_display_timezone_option('Europe/Helsinki', 'Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius', $value)
        . ln_display_timezone_option('Asia/Jerusalem', 'Jerusalem', $value)
        . ln_display_timezone_option('Europe/Minsk', 'Minsk', $value)
        . ln_display_timezone_option('Africa/Windhoek', 'Windhoek', $value)
        . ln_display_timezone_option('Asia/Kuwait', 'Kuwait, Riyadh, Baghdad', $value)
        . ln_display_timezone_option('Europe/Moscow', 'Moscow, St. Petersburg, Volgograd', $value)
        . ln_display_timezone_option('Africa/Nairobi', 'Nairobi', $value)
        . ln_display_timezone_option('Asia/Tbilisi', 'Tbilisi', $value)
        . ln_display_timezone_option('Asia/Tehran', 'Tehran', $value)
        . ln_display_timezone_option('Asia/Muscat', 'Abu Dhabi, Muscat', $value)
        . ln_display_timezone_option('Asia/Baku', 'Baku', $value)
        . ln_display_timezone_option('Asia/Yerevan', 'Yerevan', $value)
        . ln_display_timezone_option('Asia/Kabul', 'Kabul', $value)
        . ln_display_timezone_option('Asia/Yekaterinburg', 'Yekaterinburg', $value)
        . ln_display_timezone_option('Asia/Karachi', 'Islamabad, Karachi, Tashkent', $value)
        . ln_display_timezone_option('Asia/Kolkata', 'Sri Jayawardenepura', $value)
        . ln_display_timezone_option('Asia/Kolkata', 'Chennai, Kolkata, Mumbai, New Delhi', $value)
        . ln_display_timezone_option('Asia/Kathmandu', 'Kathmandu', $value)
        . ln_display_timezone_option('Asia/Almaty', 'Almaty, Novosibirsk', $value)
        . ln_display_timezone_option('Asia/Dhaka', 'Astana, Dhaka', $value)
        . ln_display_timezone_option('Asia/Rangoon', 'Yangon (Rangoon)', $value)
        . ln_display_timezone_option('Asia/Bangkok', 'Bangkok, Hanoi, Jakarta', $value)
        . ln_display_timezone_option('Asia/Krasnoyarsk', 'Krasnoyarsk', $value)
        . ln_display_timezone_option('Asia/Shanghai', 'Beijing, Chongqing, Hong Kong, Urumqi', $value)
        . ln_display_timezone_option('Asia/Singapore', 'Kuala Lumpur, Singapore', $value)
        . ln_display_timezone_option('Asia/Irkutsk', 'Irkutsk, Ulaan Bataar', $value)
        . ln_display_timezone_option('Australia/Perth', 'Perth', $value)
        . ln_display_timezone_option('Asia/Taipei', 'Taipei', $value)
        . ln_display_timezone_option('Asia/Tokyo', 'Osaka, Sapporo, Tokyo', $value)
        . ln_display_timezone_option('Asia/Seoul', 'Seoul', $value)
        . ln_display_timezone_option('Asia/Yakutsk', 'Yakutsk', $value)
        . ln_display_timezone_option('Australia/Adelaide', 'Adelaide', $value)
        . ln_display_timezone_option('Australia/Darwin', 'Darwin', $value)
        . ln_display_timezone_option('Australia/Brisbane', 'Brisbane', $value)
        . ln_display_timezone_option('Australia/Sydney', 'Canberra, Melbourne, Sydney', $value)
        . ln_display_timezone_option('Australia/Hobart', 'Hobart', $value)
        . ln_display_timezone_option('Pacific/Guam', 'Guam, Port Moresby', $value)
        . ln_display_timezone_option('Asia/Vladivostok', 'Vladivostok', $value)
        . ln_display_timezone_option('Asia/Magadan', 'Magadan, Solomon Is., New Caledonia', $value)
        . ln_display_timezone_option('Pacific/Auckland', 'Auckland, Wellington', $value)
        . ln_display_timezone_option('Pacific/Fiji', 'Fiji, Kamchatka, Marshall Is.', $value)
        . ln_display_timezone_option('Pacific/Tongatapu', 'Nuku\'alofa', $value)
        . '</select>';
    return $out;
}

// description: converts a array into a CCE-encoded scalar
function array_to_scalar( $array ) {
$result = "&";
    if (is_array($array)) {
        $result = "&";
        foreach($array as $value) {
                $value = preg_replace("/([^A-Za-z0-9_\. -])/e",
                          "sprintf('%%%02X', ord('\\1'))", $value);
                $value = preg_replace("/ /", "+", $value);

                $result .= $value . "&";
        }
    }
    if ($result == "&") $result = "";
    return $result;
}

// description: converts a CCE-encoded scalar into an array
function scalar_to_array( $scalar ) {
    // just in case trim off whitespace
    $scalar = trim($scalar);

    $scalar = preg_replace("/^&/", "", $scalar);
    $scalar = preg_replace("/&$/", "", $scalar);
    $array = explode("&", $scalar);
    for($i = 0; $i < count($array); $i++) {
      $array[$i] = preg_replace("/\+/", " ", $array[$i]);
      $array[$i] = preg_replace("/%([0-9a-fA-F]{2})/e",
                                "chr(hexdec('\\1'))", $array[$i]);
    }

    return $array;
}

// description: converts a string to a CCE-encoded scalar. 
// This is new as of 5200R and is a necessity due to CodeIgniters
// XSS cleaning of our form data:
function string_to_scalar ($string) {
  // Just in case trim off whitespace:
  $string = trim($string);

  // Strip leading and trailing "&" - just in case as well:
  $string = preg_replace("/^&/", "", $string);
  $string = preg_replace("/&$/", "", $string);

  // Strip excess whitespaces:
  $string = preg_replace("/\s\s+/", " ", $string);

  // Replace ", " with "&":
  $string = preg_replace("/,[\s+]{0,999}/i", "&", $string);

  // Replace "\n" with "&":
  $string = preg_replace("/\n/i", "&", $string);

  // Build scalar:
  if ($string) {
    $scalar = "&" . $string . "&";
  }
  else {
    $scalar = "";
  }

  return $scalar;
}

// description: converts a CCE-encoded scalar into a string:
function scalar_to_string($scalar, $delimiter='\n') {
  if (preg_match("/^\&(.*)\&$/", $scalar, $regs)) {
    $value = implode($delimiter, stringToArray($scalar));
  }
  else {
    $value = $scalar;
  }
  return $value;
}

function array_merge_alt($a, $b) {
    $new = array();
    $new = $a;
    foreach ( $b as $line ) {
        $key = array_search($line, $a);
        if ( $key === FALSE ) {
            if ( $line ) {
                $new[] = $line;
            }
        }
    }
    return $new;
}

function removeElementWithValue($array, $key, $value){
     foreach($array as $subKey => $subArray){
          if($subArray[$key] == $value){
               unset($array[$subKey]);
          }
     }
     return $array;
}

function createRandomPassword($length='7', $type='alpha') {

    // Get CI instance and load library uifc/PasswordGenerator.php:
    $CI =& get_instance();
    $CI->load->library('PasswordGenerator');

    // Can return random passwords of varius length and type.
    // Supported types:
    //
    // - 'ascii'
    // - 'hex'
    // - 'alpha' (alphanumeric)
    // - 'custom' (not supported by us at this time)

    if ($type == 'ascii') {
        return PasswordGenerator::getASCIIPassword($length);
    }
    elseif ($type == 'hex') {
        return PasswordGenerator::getHexPassword($length);
    }
    else {
        return PasswordGenerator::getAlphaNumericPassword($length);
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