<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SummaryEmail extends MX_Controller {

    private $i18n;
    private $group;
    private $domain;

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /sitestats/summaryEmail.
     *
     */

    // This is mainly a parser and presenter for Sendmail Analyzer data files.
    //
    // For now it provides stats for the most common email parameters encountered 
    // on BlueOnyx. But it is missing some advanced and exotic stats that I have 
    // no data for at the present time. Such as for Amavis, Postgrey, j-ChkMail 
    // and others.
    //
    // All things considered this is the largest and most complex GUI page so far.
    //
    // Many thanks to Gilles Darold from http://sendmailanalyzer.darold.net/ 
    // for the splendid groundwork, coding examples and naturally for the 
    // underlying Sendmail Analyzer which we use for the generation of the 
    // statistics.
    //
    //
    // Gilles Darold: You're my kind of Perl-God. Mad props to you!

    private function setI18n($i18n) {
        $this->i18n = $i18n;
    }

    private function getI18n() {
        return $this->i18n;
    }

    private function setGroup($group) {
        $this->group = $group;
    }

    private function getGroup() {
        if (!isset($this->group)) {
            $this->group = 'server';
        }
        if ($this->group == '') {
            $this->group = 'server';
        }
        return $this->group;
    }

    private function setDomain($domain) {
        $this->domain = $domain;
    }

    private function getDomain() {
        if (!isset($this->domain)) {
            $this->domain = '';
        }
        return $this->domain;
    }

    private function setHour($hour) {
        $this->hour = $hour;
    }

    private function getHour() {
        if (!isset($this->hour)) {
            $this->setHour(date("H"));
        }
        return $this->hour;
    }

    private function setDay($day) {
        $this->day = $day;
    }

    private function getDay() {
        if (!isset($this->day)) {
            $this->day = date('d', strtotime("now"));
        }
        return $this->day;
    }

    private function setWeek($week) {
        $this->week = $week;
    }

    private function calcWeek($startDate) {
        $week = date('W', strtotime($startDate . 'T00:00:01'));
        return $week;
    }

    private function getWeek() {
        if (!isset($this->week)) {
            $this->week = date('W', strtotime("now"));
        }
        return $this->week;
    }
    private function setMonth($month) {
        $this->month = $month;
    }

    private function getMonth() {
        if (!isset($this->month)) {
            $this->month = date('m', strtotime("now"));
        }
        return $this->month;
    }

    private function setYear($year) {
        $this->year = $year;
    }

    private function getYear() {
        if (!isset($this->year)) {
            $this->year = date('Y', strtotime("now"));
        }
        return $this->year;
    }

    private function setStats($stats) {
        $this->stats = $stats;
    }

    private function getStats() {
        return $this->stats;
    }

    private function setPeriod($period) {
        $this->period = $period;
    }

    private function getPeriod() {
        return $this->period;
    }

    private function cleanup_stats(&$item, &$key) {
        if (is_array($item)) {
            array_filter($item);
        }
        if (is_array($key)) {
            array_filter($key);
        }
        if ((!preg_match('/^cache\.pm$/', $item)) && (!preg_match('/^(\d+)cache\.pm$/', $item))) {
            $item = "";
            unset($key);
        }
    }

    private function array_remove_empty($haystack) {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = SummaryEmail::array_remove_empty($haystack[$key]);
            }
            if (empty($haystack[$key])) {
                unset($haystack[$key]);
            }
        }
        return $haystack;
    }

    private function is_month($calMonth) {
        $selectedMonth = $this->getMonth();
        $period = $this->getPeriod();
        $group = summaryEmail::getGroup();
        $domain = summaryEmail::getDomain();
        $month_locales = array(
                                    '01' => "01month_short",
                                    '02' => "02month_short",
                                    '03' => "03month_short",
                                    '04' => "04month_short",
                                    '05' => "05month_short",
                                    '06' => "06month_short",
                                    '07' => "07month_short",
                                    '08' => "08month_short",
                                    '09' => "09month_short",
                                    '10' => "10month_short",
                                    '11' => "11month_short",
                                    '12' => "12month_short"
                                    );
        $mnt = $month_locales[$calMonth];
        $STATS = $this->getStats();
        $haveYears = array_keys($STATS);
        if (!in_array($this->getYear(), $haveYears)) {
            // Someone tried to set an invalid year. Reset it to this year:
            if (isset($haveYears[0])) {
                $this->setYear($haveYears[0]);
            }
            else {
                $this->setYear(date("Y"));
                $out = '';
                return $out;
            }
        }
        $haveMonths = array_keys($STATS[$this->getYear()]);
        if (!in_array($selectedMonth, $haveMonths)) {
            // Someone tried to set a month for that we don't have stats
            // in the given year. Reset the date to todays month and year:
            $selectedMonth = date("m");
            $this->setMonth($selectedMonth);
            $this->setYear(date("Y"));
        }

        if ($domain == '') {
            $domain_parm = '';
        }
        else {
            $domain_parm = "&domain=$domain";
        }

        if (($calMonth == $selectedMonth) && ($period == "month")) {
            // Highlight selected month, but only make months links if we have stats for them:
            if ((in_array($this->getYear(), $haveYears)) && (in_array($calMonth, $haveMonths))) {
                $out = '<TH><a href="/sitestats/summaryEmail?group=' . $group . '&period=month&month=' . $calMonth  . '&year=' . $this->getYear() . $domain_parm . '">' .  $this->i18n->get("[[palette.$mnt]]") . '</a></TH>';
            }
            else {
                $out = '<TH>' .  $this->i18n->get("[[palette.$mnt]]") . '</TH>';
            }
        }
        else {
            // Only make months links if we have stats for them:
            if ((in_array($this->getYear(), $haveYears)) && (in_array($calMonth, $haveMonths))) {
                $out = '<TD><a href="/sitestats/summaryEmail?group=' . $group . '&period=month&month=' . $calMonth  . '&year=' . $this->getYear() . $domain_parm . '">' .  $this->i18n->get("[[palette.$mnt]]") . '</a></TD>';
            }
            else {
                $out = '<TD>' .  $this->i18n->get("[[palette.$mnt]]") . '</TD>';
            }
        }
        return $out;
    }

    private function is_hour($calHour) {
        $selectedMonth = $this->getMonth();
        $selectedDay = $this->getDay();
        $selectedHour = $this->getHour();
        $period = $this->getPeriod();
        $group = $this->group;
        $domain = $this->domain;

        if ($domain == '') {
            $domain_parm = '';
        }
        else {
            $domain_parm = "&domain=$domain";
        }

        $STATS = $this->getStats();
        $haveYears = array_keys($STATS);

        if (!in_array($this->getYear(), $haveYears)) {
            $out = '';
            return $out;
        }

        if (!in_array($this->getYear(), $haveYears)) {
            // Someone tried to set an invalid year. Reset it to this year:
            $this->setYear(date("Y"));
        }
        $haveMonths = array_keys($STATS[$this->getYear()]);
        if (!in_array($selectedMonth, $haveMonths)) {
            // Someone tried to set a month for that we don't have stats
            // in the given year. Reset the date to todays month and year:
            $selectedMonth = date("m");
            $this->setMonth($selectedMonth);
            $this->setYear(date("Y"));
        }
        $tmpStats = $STATS;
        if (isset($tmpStats[$this->getYear()][$this->getMonth()]['summary'])) {
            unset($tmpStats[$this->getYear()][$this->getMonth()]['summary']);
        }

        if (isset($tmpStats[$this->getYear()][$this->getMonth()])) {
            $haveDays = array_keys($tmpStats[$this->getYear()][$this->getMonth()]);
        }
        else {
            $haveDays = array();
        }

        if (!in_array($selectedDay, $haveDays)) {
            // We don't have this day. Set to today:
            $this->setMonth(date("m"));
            $this->setYear(date("Y"));
            $this->setDay(date("d"));
        }

        // Now check if we have stats for the individual hours of this day (Yay, finally!):
        if (isset($tmpStats[$this->getYear()][$this->getMonth()][$this->getDay()])) {
            $haveHours = array_keys($tmpStats[$this->getYear()][$this->getMonth()][$this->getDay()]);
        }
        elseif (isset($tmpStats[$this->getYear()][$this->getMonth()][$this->getDay()-1])) {
            // No? How about from one hour ago?
            $haveHours = array_keys($tmpStats[$this->getYear()][$this->getMonth()][$this->getDay()-1]);
        }
        else {
            // Still nothing? Okay, I give up. Must be early in the morning. Like before 1 a.m.:
            $haveHours = array();
        }
        foreach ($haveHours as $key => $hour) {
            // Remove daily summary from the hours:
            if ($hour == "summary") {
                unset($haveHours[$key]);
            }
        }
        if (!in_array($selectedHour, $haveHours)) {
            // The last hour we have stats for is the last hour we present links for:
            $selectedHour = array_shift(array_values(array_reverse($haveHours)));
        }
        if (($period == "hour") && ($calHour == $selectedHour)) {
            // Highlight selected hour when we're in hourly mode and this hour is selected:
            $out = '<TH><a href="/sitestats/summaryEmail?group=' . $group . '&period=hour&month=' . $this->getMonth()  . '&year=' . $this->getYear() . '&day=' . $this->getDay() . '&hour=' . $calHour . $domain_parm . '">' .  $calHour . '</a></TH>';
        }
        else {
            // Only make Hour links if we have stats for the given hour:
            if ((in_array($this->getYear(), $haveYears)) && (in_array($selectedMonth, $haveMonths)) && (in_array($this->getDay(), $haveDays)) && (in_array($calHour, $haveHours))) {
                $out = '<TD><a href="/sitestats/summaryEmail?group=' . $group . '&period=hour&month=' . $this->getMonth()  . '&year=' . $this->getYear() . '&day=' . $this->getDay() . '&hour=' . $calHour . $domain_parm . '">' .  $calHour . '</a></TD>';
            }
            else {
                $out = '<TD>' .  $calHour . '</TD>';
            }
        }
        return $out;
    }
    
    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $CI->BX_SESSION['sessionId'] and $CI->BX_SESSION['loginName'] from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection and store them for re-usability in $CI:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();

        $i18n = new I18n("base-vsite", $CI->BX_SESSION['loginUser']['localePreference']);
        $this->setI18n($i18n);
        $system = $CI->getSystem();
        $user = $CI->BX_SESSION['loginUser'];

        // Access Rules:
        if ((!$CI->serverScriptHelper->getAllowed('adminUser')) && 
            (!$CI->serverScriptHelper->getAllowed('siteAdmin')) && 
            (!$CI->serverScriptHelper->getAllowed('manageSite')) && 
            (($user['site'] != $CI->serverScriptHelper->loginUser['site']) && $CI->serverScriptHelper->getAllowed('siteAdmin')) &&
            (($vsiteObj['createdUser'] != $CI->BX_SESSION['loginName']) && $CI->serverScriptHelper->getAllowed('manageSite'))
            ) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // Load pagination library:
        $this->load->library('pagination');

        // -- Actual page logic start:

        // We start without any active errors:
        $errors = array();
        $extra_headers =array();
        $ci_errors = array();
        $my_errors = array();

        //
        //--- URL String parsing:
        //
        $type = '';
        $group = '';
        summaryEmail::setGroup('');
        $period = 'day';

        // Shove submitted POST data into $form_data after passing it through the XSS filter:
        $form_data = $CI->input->post(NULL, TRUE);

        // Shove submitted GET data into $get_form_data:
        $get_form_data = $CI->input->get(NULL, TRUE);
        if (isset($get_form_data['type'])) {
            $type = $CI->security->xss_clean($get_form_data['type']);
            $type = $CI->security->sanitize_filename($type);
        }
        if (isset($get_form_data['group'])) {
            $group = $CI->security->xss_clean($get_form_data['group']);
            $group = $CI->security->sanitize_filename($group);
            summaryEmail::setGroup($group);
        }
        if (isset($get_form_data['period'])) {
            $period = $get_form_data['period'];
        }

        if (isset($get_form_data['domain'])) {
            $domain = $get_form_data['domain'];
            summaryEmail::setDomain($domain);
        }

        if (isset($domain)) {
            $domain_parm = "&domain=$domain";
        }
        else {
            $domain_parm = '';
        }

        $maxDate = "'" . date("Y-m-d") . "'";

        // Only menuServerServerStats, manageSite and siteAdmin should be here:
        if (!$Capabilities->getAllowed('menuServerServerStats') &&
            !$Capabilities->getAllowed('manageSite') &&
            !($Capabilities->getAllowed('siteAdmin') &&
              $group == $Capabilities->loginUser['site'])) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        if ((!isset($group)) || ($group == '')) {
            $group = "server";
            summaryEmail::setGroup($group);
        }
        if (!isset($type)) {
            $type = "mail";
        }

        // Form fields that are required to have input:
        $required_keys = array("");

        // Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

        // Empty array for key => values we want to submit to CCE:
        $attributes = array();
        // Items we do NOT want to submit to CCE:
        $ignore_attributes = array();
        if (is_array($form_data)) {
            // Function GetFormAttributes() walks through the $form_data and returns us the $parameters we want to
            // submit to CCE. It intelligently handles checkboxes, which only have "on" set when they are ticked.
            // In that case it pulls the unticked status from the hidden checkboxes and addes them to $parameters.
            // It also transformes the value of the ticked checkboxes from "on" to "1". 
            //
            // Additionally it generates the form_validation rules for CodeIgniter.
            //
            // params: $i18n                i18n Object of the error messages
            // params: $form_data           array with form_data array from CI
            // params: $required_keys       array with keys that must have data in it. Needed for CodeIgniter's error checks
            // params: $ignore_attributes   array with items we want to ignore. Such as Labels.
            // return:                      array with keys and values ready to submit to CCE.
            $attributes = GetFormAttributes($i18n, $form_data, $required_keys, $ignore_attributes, $i18n);
        }
        //Setting up error messages:
        $CI->form_validation->set_message('required', $i18n->get("[[palette.val_is_required]]", false, array("field" => "\"%s\"")));        

        // Do we have validation related errors?
        if ($CI->form_validation->run() == FALSE) {

            if (validation_errors()) {
                // Set CI related errors:
                $ci_errors = array(validation_errors('<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>', '</strong></div>'));
            }           
            else {
                // No errors. Pass empty array along:
                $ci_errors = array();
            }
        }

        //
        //--- Own error checks:
        //

        $is_month_view = TRUE;

        if ($CI->input->post(NULL, TRUE)) {
            if (isset($form_data['dateSelected'])) {
                if ($form_data['dateSelected'] != "") {
                    $form_data['dateSelected'] = preg_replace('/\s+/', '', $form_data['dateSelected']);
                    $formdate = explode('-', $form_data['dateSelected']);
                    if (count($formdate) == '6') {
                        $from_date = array('Y' => $formdate[0], 'M' => $formdate[1], 'D' => $formdate[2]);
                        $to_date = array('Y' => $formdate[3], 'M' => $formdate[4], 'D' => $formdate[5]);
                        $this->setYear($formdate[0]);
                        $this->setMonth($formdate[1]);
                        $this->setDay($formdate[2]);
                        if ($from_date == $to_date) {
                            $period = "day";
                        }
                        else {
                            $period = "week";
                            $this->setWeek($this->calcWeek($from_date['Y']."-".$from_date['M']."-".$from_date['D']));
                        }
                    }
                }
            }
            else {
                // Safe fallback:
                $period = "week";
                $this->setWeek($this->calcWeek(date("Y")."-".date("m")."-".date("d")));
            }
        }
        else {
            // No date realated POST data, so get date from URL string:
            if (isset($get_form_data['hour'])) {
                $this->setHour($get_form_data['hour']);
            }
            else {
                $this->setHour(date("H"));
            }
            if (isset($get_form_data['day'])) {
                $this->setDay($get_form_data['day']);
            }
            else {
                $this->setDay(date("d"));
            }
            if (isset($get_form_data['month'])) {
                $this->setMonth($get_form_data['month']);
            }
            else {
                $this->setMonth(date("m"));
            }
            if (isset($get_form_data['year'])) {
                $this->setYear($get_form_data['year']);
            }
            else {
                $this->setYear(date("Y"));
            }
        }

        // Store the period:
        $this->setPeriod($period);

        if (!isset($from_date)) {
            $from_date = array('Y' => $this->getYear(), 'M' => $this->getMonth(), 'D' => $this->getDay());
        }

        if (!isset($to_date)) {
            $to_date = array('Y' => $this->getYear(), 'M' => $this->getMonth(), 'D' => $this->getDay());
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        //
        //--- Get Items of Interest:
        //

        // Update SA Cache:
        //$ret = $CI->serverScriptHelper->shell("/usr/bin/sa_cache -a", $sareport, 'root', $CI->BX_SESSION['sessionId']);

        // Location of the directory with statistics:
        $Stats_dir = '/home/.sendmailanalyzer';

        if (!is_dir($Stats_dir)) {
            // If we don't have stats we don't go any further and throw an error.
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // Get a fileMap of the stats directory:
        $map = directory_map($Stats_dir, FALSE, FALSE);

        // Pre-define array for our XML files:
        $xml_files = array();

        // Find the name of the server stats directory:
        $firstlevel_keys = array_keys($map);
        $hn = $system['hostname'];
        foreach ($firstlevel_keys as $key => $value) {
            if (preg_match("/^$hn$/", $value)) {
                $server_statsDir = $system['hostname'];
            }
        }
        // OK, that yield a result. We do it the hard way then:
        if (!isset($server_statsDir)) {
            foreach ($map as $key => $value) {
                if (is_array($value)) {
                    $server_statsDir = $key;
                }
            }
        }

        // Cleanup topdir:
        foreach ($map as $key => $value) {
            if (!is_array($value)) {
                unset($map[$key]);
            }
        }

        // Now we still have a lot of junk that we're not interested in.
        // We *only* want the cache.pm's at this time and nothing else.

        // This function will zero out everything but 'cache.pm's:
        array_walk_recursive($map, 'SummaryEmail::cleanup_stats');

        // This function will remove all empty bits and pieces:
        $baremetalStats = SummaryEmail::array_remove_empty($map);

        // Array setup:
        $STATS = array();
        $good_hours = array('00', '01', '02', '03', '04', '05', '06', '07', '08', '09',
                    '10', '11', '12', '13', '14', '15', '16', '17', '18', '19',
                    '20', '21', '22', '23');

        $Full_Month_Locales = array(
                                '01' => "01month",
                                '02' => "02month",
                                '03' => "03month",
                                '04' => "04month",
                                '05' => "05month",
                                '06' => "06month",
                                '07' => "07month",
                                '08' => "08month",
                                '09' => "09month",
                                '10' => "10month",
                                '11' => "11month",
                                '12' => "12month"
                            );

        // Do we have stats to display? If not, stop here:
        if (!isset($server_statsDir)) {
            $defaultPage = "basicSettingsTab";
            $factory =& $CI->serverScriptHelper->getHtmlComponentFactory('base-mailsitestats', "");

            // Prepare Page:
            $BxPage = $factory->getPage();
            $BxPage->setErrors($errors);
            $i18n = $factory->getI18n();

            $block = $factory->getPagedBlock("summaryStats", array($defaultPage));
            $block->setToggle("#");
            $block->setSideTabs(FALSE);
            $block->setDefaultPage($defaultPage);

            // Out with the message_delivery_flows_table:
            $no_data = $this->i18n->get("[[base-mailsitestats.sa_nodata]]");
            $block->addFormField(
                $factory->getRawHTML("no_data", $no_data),
                $factory->getLabel("no_data"),
                $defaultPage
            );

            $page_body[] = $block->toHtml();

            // Set Menu items:
            if ($group == "server") {
                $BxPage->setVerticalMenu('base_serverusage');
                $page_module = 'base_sysmanage';
                $BxPage->setVerticalMenuChild('base_server_mailusage');
            }
            else {
                $BxPage->setVerticalMenu('base_siteusage');
                $BxPage->setVerticalMenuChild('base_webusage');
                $page_module = 'base_sitemanage';
                $BxPage->setVerticalMenuChild('base_mailusage');
            }
                        
            // Out with the page:
            $BxPage->render($page_module, $page_body);
            return;
        }

        // Now map it out nicely:
        if (isset($baremetalStats[$server_statsDir])) {
            foreach ($baremetalStats[$server_statsDir] as $year => $y_value) {
                $STATS[$year] = array();
                foreach ($baremetalStats[$server_statsDir][$year] as $month => $m_value) {
                    if (!is_array($m_value)) {
                        if (preg_match('/^cache.pm$/', $m_value)) {
                            $STATS[$year]['summary'] = $m_value;
                        }
                    }
                    if (preg_match('/^weeks$/', $month)) {
                        $STATS[$year]['weeks'] = array();
                    }
                    elseif (is_array($m_value)) {
                        $STATS[$year][$month] = array();
                    }

                    if (is_array($m_value)) {
                        foreach ($baremetalStats[$server_statsDir][$year][$month] as $day => $d_value) {
                            if (!is_array($d_value)) {
                                // Month Summary:
                                if (preg_match('/^cache.pm$/', $d_value)) {
                                    $STATS[$year][$month]['summary'] = $d_value;
                                }
                            }
                            if ($month == 'weeks') {
                                $day_key = array_keys($d_value);
                                $STATS[$year]['weeks'][$day] = $d_value[$day_key['0']];
                                if (is_array($STATS[$year]['weeks'])) {
                                    ksort($STATS[$year]['weeks'], SORT_NUMERIC);
                                }
                            }
                            else {
                                // Day Summary:
                                if (is_array($d_value)) {
                                    foreach ($d_value as $dkey => $h_value) {
                                        // Create copy of $h_value:
                                        $full_h_value = $h_value;
                                        // Daily summary:
                                        if (preg_match('/^cache.pm$/', $h_value)) {
                                            $STATS[$year][$month][$day]['summary'] = $h_value;
                                        }
                                        else {
                                            // Hourly statistics for the given day:
                                            unset($h_matches);
                                            if (preg_match('/^(\d{2})cache\.pm$/', $full_h_value, $h_matches)) {
                                                if ((isset($h_matches[1])) && (in_array($h_matches[1], $good_hours))) {
                                                    $hour = $h_matches[1];
                                                    $STATS[$year][$month][$day][$hour] = $h_value;
                                                }
                                            }
                                        }
                                    }
                                    ksort($STATS[$year][$month][$day], SORT_NUMERIC);
                                }
                            }
                        }
                        if (is_array($STATS[$year][$month])) {
                            ksort($STATS[$year][$month], SORT_NUMERIC);
                        }
                    }
                    if (is_array($STATS)) {
                        ksort($STATS, SORT_NUMERIC);
                    }
                }
            }
        }
        else {
            // If we don't have stats we don't go any further and throw an error.
            // We can get at this point if the server is freshly set up and sa_cache
            // hasn't finished its initial run. In that case we just throw a 403.
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // Find out which years we have:
        $YEARS = array_keys($STATS);
        $this->setStats($STATS);

        // Get the oldest date that we have stats for.
        $tmpStats = $STATS;
        $oldestYear = array_shift(array_values($YEARS));

        foreach ($tmpStats[$oldestYear] as $key => $value) {
            if ($key == 'summary') {
                unset($tmpStats[$oldestYear][$key]);
            }
        }
        
        if (isset($tmpStats[$oldestYear]['weeks'])) {
            unset($tmpStats[$oldestYear]['weeks']);
        }
        
        $oldestMonth = array_shift(array_keys($tmpStats[$oldestYear]));

        if (isset($tmpStats[$oldestYear][$oldestMonth]))  {
            if (is_array($tmpStats[$oldestYear][$oldestMonth])) {
                foreach ($tmpStats[$oldestYear][$oldestMonth] as $key => $value) {
                    if ($key == 'summary') {
                        unset($tmpStats[$oldestYear][$oldestMonth][$key]);
                    }
                }
            }
        }
        $oldestDay = array_shift(array_keys($tmpStats[$oldestYear][$oldestMonth]));
        unset($tmpStats);
        $minDate = "'" . $oldestYear . '-' . $oldestMonth . '-' . $oldestDay . "'";

        // Construct the URL parameters based on the currently selected date and group:
        if ($period == "day") {
            $formTargetUrl = "/sitestats/summaryEmail?group=$group&period=$period&year=" . $this->getYear() . "&month=" . $this->getMonth() . "&day=" . $this->getDay();
        }
        elseif ($period == "week") {
            $formTargetUrl = "/sitestats/summaryEmail?group=$group&period=$period&year=" . $this->getYear() . "&week=" . $this->getWeek();
        }
        elseif ($period == "month") {
            $formTargetUrl = "/sitestats/summaryEmail?group=$group&period=$period&year=" . $this->getYear() . "&month=" . $this->getMonth();
        }
        elseif ($period == "year") {
            $formTargetUrl = "/sitestats/summaryEmail?group=$group&period=$period&year=" . $this->getYear();
        }
        else {
            // Default back to current day:
            $formTargetUrl = "/sitestats/summaryEmail?group=$group&period=$period&year=" . $this->getYear() . "&month=" . $this->getMonth() . "&day=" . $this->getDay();
        }

        // Append the domain if one is specified:
        $mainFormTargetUrl = $formTargetUrl . $domain_parm;

        $defaultPage = "basicSettingsTab";
        $factory =& $CI->serverScriptHelper->getHtmlComponentFactory('base-sitestats', $mainFormTargetUrl);

        // Prepare Page:
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        //
        //--- Configure $type Reporting Options:
        //

        $block = $factory->getPagedBlock("generateSettings", array($defaultPage));
        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        $typestring = $i18n->interpolate("[[base-sitestats." . $type . "usage]]");
        $i18nvars['type'] = $typestring;
        $mailAliases = array();

        if (isset($group) && $group != 'server') {
            $vsite = $CI->cceClient->find('Vsite', array('name' => $group));
            if (isset($vsite[0])) {
                $vsiteObj = $CI->cceClient->get($vsite[0]);
                $mailAliases = scalar_to_array($vsiteObj['mailAliases']);
            }
            else {
                // Vsite Object doesn't exist.
                // Nice people say goodbye, or CCEd waits forever:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                Log403Error("/gui/Forbidden403");
            }
        }
        $dsp_mnt = $Full_Month_Locales[$this->getMonth()];
        if ($period == "month") {
            $display_period = $this->i18n->get("[[palette.$dsp_mnt]]") . " " . $this->getYear();
        }
        elseif ($period == "hour") {
            $dsp_mnt = $Full_Month_Locales[$this->getMonth()];
            $hourNext = $this->getHour()+1;
            if ($hourNext > '24') {
                $hourNext = "01";
            }
            $display_period = $this->getDay() . ". " . $this->i18n->get("[[palette.$dsp_mnt]]") . " " . $this->getYear() . " -  " . $this->getHour() . ':00-' . $hourNext . ':00';
        }
        elseif ($period == "week") {
            $dsp_mnt = $Full_Month_Locales[$this->getMonth()];
            $dsp_mnt_to = $Full_Month_Locales[$to_date['M']];
            $display_period = $this->getDay() . ". " . $this->i18n->get("[[palette.$dsp_mnt]]") . " " . $this->getYear() . " - " . $to_date['D'] . ". " . $this->i18n->get("[[palette.$dsp_mnt_to]]") . " " . $to_date['Y'];
        }
        elseif ($period == "year") {
            $display_period = $this->getYear();
        }
        else {
            // Default: day
            $dsp_mnt = $Full_Month_Locales[$this->getMonth()];
            $display_period = $this->getDay() . ". " . $this->i18n->get("[[palette.$dsp_mnt]]") . " " . $this->getYear();
        }

        if (isset($domain)) {
            $lbl_prefix = $domain . ': ';
        }
        else {
            $lbl_prefix = '';
        }
        
        $block->setLabel($factory->getLabel($lbl_prefix . $this->i18n->get("[[base-mailsitestats.sa_stats_label]]") . " $display_period"));

        // Set Menu items:
        if ($group == "server") {
            $BxPage->setVerticalMenu('base_serverusage');
            $page_module = 'base_sysmanage';
            $BxPage->setVerticalMenuChild('base_server_mailusage');
        }
        else {
            $BxPage->setVerticalMenu('base_siteusage');
            $BxPage->setVerticalMenuChild('base_webusage');
            $page_module = 'base_sitemanage';
            $BxPage->setVerticalMenuChild('base_mailusage');
        }

        // Explanation: If you run datepicker or datepick on a DIV and not a formfield, then you get
        // no form data back on submit. Hence we use datepick's "altField" to populate the chosen date
        // range into the hidden formfield "dateSelected" below. And can you believe that I needed two
        // fucking days to figure this out and solve it? Incredible. Oh, and the $datepicker variable 
        // has to be populated before we set the extra-headers below. Because in summaryEmail::is_month()
        // we have a check that resets the date to todays date if someone selects a month and year for
        // which we have no statistics.

        // Paginate Years:
        $Ypages = '';
        $numYears = count($YEARS);
        $tmpYears = array_reverse($YEARS);
        // Show 3 years max per pagination:
        $pages = array_chunk($tmpYears, 3);
        $i = 0;
        $foundkey = 0;
        foreach ($pages as $key => $pyear) {
            if (in_array($this->getYear(), $pyear)) {
                $pyear = array_reverse($pyear);
                $foundkey = $key;
                foreach ($pyear as $key => $actualYear) {
                    if (($this->getYear() == $actualYear) && (($period == "year") || ($period == "month") || ($period == "week"))) {
                        $Ypages .= '<b><a href="/sitestats/summaryEmail?group=' . $group . '&year=' . $actualYear  . '&period=year' . $domain_parm . '">' .  $actualYear . '</a></b>&nbsp';
                    }
                    else {
                        $Ypages .= '<a href="/sitestats/summaryEmail?group=' . $group . '&year=' . $actualYear  . '&period=year' . $domain_parm . '">' .  $actualYear . '</a>&nbsp';
                    }
                }
            }
            $i++;
        }

        // Add << - >> to thumb through pages:
        if (isset($pages[$foundkey+1])) {
            $uFlowYears = array_values($pages[$foundkey+1]);
            $Ypages = '<a href="/sitestats/summaryEmail?group=' . $group . '&period=year&year=' . $uFlowYears[0] . $domain_parm . '">&lt;&lt;</a>&nbsp;' . $Ypages;
        }
        if (isset($pages[$foundkey-1])) {
            $oFlowYears = array_reverse(array_values($pages[$foundkey-1]));
            $Ypages .= "&nbsp;" . '<a href="/sitestats/summaryEmail?group=' . $group . '&period=year&year=' . $oFlowYears[0] . $domain_parm . '">&gt;&gt;</a>';
        }

        $datepicker = '
            <div class="columns clearfix">
                <input type="hidden" value="" name="dateSelected" id="dateSelected" class="dateSelected"></input>
                <div class="col_50">
                    <fieldset class="label_top label_small top bottom">
                        <label>' . $this->i18n->get("[[base-mailsitestats.daily_weekly_summary]]") . '</label>
                        <div class="multiShowPicker">
                            <div id="multiShowPicker"></div>
                        </div>
                    </fieldset>
                </div>

                <div class="col_50">';

        summaryEmail::setGroup($group);
        if (($period == 'day') || ($period == 'hour')) {
            $datepicker .= '
                        <fieldset class="label_top top right no_lines">
                            <label>' . $this->i18n->get("[[base-mailsitestats.hourly_summary]]") . '</label>
                            <div class="clearfix">
                                <TABLE class="calborder">
                                    <TR><TH colspan="12" align="center"><H2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.hours]]") . '</H2></TH></TR>
                                    <TR align=center>' . 
                                        summaryEmail::is_hour("00") . summaryEmail::is_hour("01") . summaryEmail::is_hour("02") . summaryEmail::is_hour("03") . 
                                        summaryEmail::is_hour("04") . summaryEmail::is_hour("05") . summaryEmail::is_hour("06") . summaryEmail::is_hour("07") . 
                                        summaryEmail::is_hour("08") . summaryEmail::is_hour("09") . summaryEmail::is_hour("10") . summaryEmail::is_hour("11") . '</TR>' .
                                    '<TR align=center>' . 
                                        summaryEmail::is_hour("12") . summaryEmail::is_hour("13") . summaryEmail::is_hour("14") . summaryEmail::is_hour("15") . 
                                        summaryEmail::is_hour("16") . summaryEmail::is_hour("17") . summaryEmail::is_hour("18") . summaryEmail::is_hour("19") . 
                                        summaryEmail::is_hour("20") . summaryEmail::is_hour("21") . summaryEmail::is_hour("22") . summaryEmail::is_hour("23") . 
                                    '</TR>
                                </TABLE>
                            </div>
                        </fieldset>';
        }

        $datepicker .= '
                    <fieldset class="label_top top right no_lines">
                        <label>' . $this->i18n->get("[[base-mailsitestats.yearly_summary]]") . '</label>
                        <div class="clearfix"> 
                            ' . $Ypages . '
                        </div>
                    </fieldset>
                    <fieldset class="label_top top right no_lines">
                        <label>' . $this->i18n->get("[[base-mailsitestats.monthly_summary]]") . '</label>
                        <div class="clearfix">
                            <TABLE class="calborder">
                                <TR><TH colspan="4" align="center"><H2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.months]]") . '</H2></TH></TR>
                                <TR align=center>' . summaryEmail::is_month("01") . summaryEmail::is_month("02") . summaryEmail::is_month("03") . summaryEmail::is_month("04") . '</TR>
                                <TR align=center>' . summaryEmail::is_month("05") . summaryEmail::is_month("06") . summaryEmail::is_month("07") . summaryEmail::is_month("08") . '</TR>
                                <TR align=center>' . summaryEmail::is_month("09") . summaryEmail::is_month("10") . summaryEmail::is_month("11") . summaryEmail::is_month("12") . '</TR>
                            </TABLE>
                        </div>
                    </fieldset>     
                </div>
            ';

        $locale_info = initialize_languages(FALSE);
        $shortlocale = $locale_info['loc'];

        $BxPage->setExtraHeaders(
                            '<link rel="stylesheet" type="text/css" href="/.adm/scripts/datepick/ui.datepick.css"> 
                            <script type="text/javascript" src="/.adm/scripts/datepick/jquery.plugin.js"></script>
                            <script type="text/javascript" src="/.adm/scripts/datepick/jquery.datepick.js?update"></script>
                            <script type="text/javascript" src="/.adm/scripts/datepick/jquery.datepick.ext.js"></script>
                            ');

        if ($shortlocale != 'en') {
            $BxPage->setExtraHeaders('<script type="text/javascript" src="/.adm/scripts/datepick/jquery.datepick-' . $shortlocale . '.js"></script>');
        }

        $BxPage->setExtraHeaders("<script>
                                    $(function() {
                                        $('#multiShowPicker').datepick({
                                            renderer: $.datepick.weekOfYearRenderer,
                                            firstDay: 1, showOtherMonths: true, rangeSelect: true, 
                                            onShow: $.datepick.multipleEvents( 
                                                $.datepick.selectWeek, $.datepick.showStatus), 
                                            showTrigger: '#calImg',
                                            dateFormat: 'yyyy-mm-dd',
                                            defaultDate: '" . $this->getYear() . "-" . $this->getMonth() . "-" . $this->getDay() . "',
                                            minDate: " . $minDate . ",
                                            maxDate: " . $maxDate . ",
                                            altField: '#dateSelected', altFormat: 'yyyy-mm-dd',
                                            onSelect: function(dates) { $('form').submit(); }
                                        });
                                    });
                                </script>
                                <style>
                                .ui-datepicker-calendar {
                                    display: none;
                                    }
                                </style>
                                ");

        // Out with the date-selectors:
        $block->addFormField(
            $factory->getRawHTML("datepicker", $datepicker),
            $factory->getLabel("datepicker"),
            $defaultPage
        );

        // Add the mailAliases selector if we're not in 'server' mode:
        if ($group != 'server') {
            $num = '0';
            foreach ($mailAliases as $key => $alias) {
                $mailAliasesList[$alias] =  $formTargetUrl . '&domain=' . $alias;
                $aliasIndex[$alias] = $num;
                $num++;
            }
            $addButton = $factory->getMultiButton("mailAliases",
                          array_values($mailAliasesList),
                          array_keys($mailAliasesList));

            $block->addFormField(
                $factory->getRawHTML("filler", "&nbsp;"),
                $factory->getLabel(" "),
                $defaultPage
            );

            if ((isset($domain)) && ($domain != "") && (in_array($domain, $mailAliases))) {
                $addButton->setSelectedIndex($aliasIndex[$domain]);
            }
            else {
                // No domain set via URL param. Pick the first element from the array $mailAliases instead:
                $domain = array_shift($mailAliases);
                if ($domain != "") {
                    summaryEmail::setDomain($domain);
                    $addButton->setSelectedIndex($aliasIndex[$domain]);
                    $addButton->setText($this->i18n->get("[[base-mailsitestats.select_an_alias]]"));
                    $block->addFormField(
                        $addButton,
                        $factory->getLabel(" "),
                        $defaultPage
                    );
                }
                else {
                    // If we get here, the Vsite doesn't have an email server alias set.
                    // To not present the whole server statistics or an empty pulldown,
                    // we hardwire the domain to the fqdn of the Vsite instead:
                    $this->setDomain($vsiteObj['fqdn']);
                    $domain = $vsiteObj['fqdn'];
                }
            }
        }

        // Parameters to pass to /var/lib/sendmailanalyzer/sa_to_php.pl:
        //
        //  $hn                     = hostname
        //  $this->getYear()        = current year
        //  $this->getWeek()        = current week  (optional)
        //  $this->getMonth()       = current month (optional)
        //  $this->getDay()         = current day   (optional)
        //  $this->getHour()        = current hour  (optional)
        //  $domain                 = Domain name (all in upper case!) for a Vsite

        $sa_params = "--host " . $hn;
        //sol --year=2014 --month=05 --day=22 --domain=SOLARSPEED.NET

        if ($period == "year") {
            $sa_params .= " --year=" . $this->getYear();
        }
        elseif ($period == "week") {
            $sa_params .= " --year=" . $this->getYear() . " --week=" . $this->getWeek();
        }
        elseif ($period == "month") {
            $sa_params .= " --year=" . $this->getYear() . " --month=" . $this->getMonth();
        }
        elseif ($period == "day") {
            $sa_params .= " --year=" . $this->getYear() . " --month=" . $this->getMonth() . " --day=" . $this->getDay();
        }
        elseif ($period == "hour") {
            $sa_params .= " --year=" . $this->getYear() . " --month=" . $this->getMonth() . " --day=" . $this->getDay() . " --hour=" . $this->getHour();
        }
        else {
            // Default to day:
            $sa_params .= " --year=" . $this->getYear() . " --month=" . $this->getMonth() . " --day=" . $this->getDay();
        }

        $Global_Default = $this->i18n->get("[[base-mailsitestats.global_cat_messaging]]");
        $Global_Spamming = $this->i18n->get("[[base-mailsitestats.global_cat_spamming]]");
        $Global_Virus = $this->i18n->get("[[base-mailsitestats.global_cat_virus]]");
        $Global_Notification = $this->i18n->get("[[base-mailsitestats.global_cat_notification]]");
        $Global_Rejections = $this->i18n->get("[[base-mailsitestats.global_cat_rejection]]");
        $Global_Status = $this->i18n->get("[[base-mailsitestats.global_cat_status]]");
        $Global_SMTPAuth = $this->i18n->get("[[base-mailsitestats.global_cat_smtpauth]]");
        
        $Top_Senders = $this->i18n->get("[[base-mailsitestats.top_cat_senders]]");
        $Top_Recipients = $this->i18n->get("[[base-mailsitestats.top_cat_recipients]]");
        $Top_Spamming = $this->i18n->get("[[base-mailsitestats.top_cat_spamming]]");
        $Top_Virus = $this->i18n->get("[[base-mailsitestats.top_cat_virus]]");
        $Top_Notification = $this->i18n->get("[[base-mailsitestats.top_cat_notification]]");
        $Top_Rejection = $this->i18n->get("[[base-mailsitestats.top_cat_rejection]]");
        $Top_SMTPAuth = $this->i18n->get("[[base-mailsitestats.top_cat_smtpauth]]");

        $AV_SPAMdMilter = $this->i18n->get("[[base-mailsitestats.av_spamassassin]]");

        $no_data = $this->i18n->get("[[base-mailsitestats.sa_nodata]]");

        // Handle stats for individual domains or the whole server:
        $SAR = array();
        if (($group != 'server') && (isset($domain))) {
            // Individual domain:
            $domain = strtoupper($domain);
            $sa_params .= ' --domain=' . $domain;
            $output = '';
            $ret = $CI->serverScriptHelper->shell("/var/lib/sendmailanalyzer/sa_to_php.pl $sa_params", $output, 'root', $CI->BX_SESSION['sessionId']);
            $statsObject = json_decode($output); // Returns Object
            $SAR = json_decode(json_encode($statsObject), true); // Returns and Array instead
        }
        else {
            // Whole server:
            $ret = $CI->serverScriptHelper->shell("/var/lib/sendmailanalyzer/sa_to_php.pl $sa_params", $output, 'root', $CI->BX_SESSION['sessionId']);
            $statsObject = json_decode($output); // Returns Object
            $SAR = json_decode(json_encode($statsObject), true); // Returns and Array instead           
        }

        // Here is some shit we need down the road:
        $dummyGLOBAL_STATUS['Please try again later_bytes'] = '0';
        $dummyGLOBAL_STATUS['User unknown'] = '0';
        $dummyGLOBAL_STATUS['Blocked'] = '0';
        $dummyGLOBAL_STATUS['SysErr'] = '0';
        $dummyGLOBAL_STATUS['No such user here_bytes'] = '0';
        $dummyGLOBAL_STATUS['User unknown_bytes'] = '0';
        $dummyGLOBAL_STATUS['Spam'] = '0';
        $dummyGLOBAL_STATUS['No such user here'] = '0';
        $dummyGLOBAL_STATUS['Blocked_bytes'] = '0';
        $dummyGLOBAL_STATUS['Can\'t create output_bytes'] = '0';
        $dummyGLOBAL_STATUS['Spam_bytes'] = '0';
        $dummyGLOBAL_STATUS['Sent'] = '0';
        $dummyGLOBAL_STATUS['Deferred_bytes'] = '0';
        $dummyGLOBAL_STATUS['Deferred'] = '0';
        $dummyGLOBAL_STATUS['Rejected'] = '0';
        $dummyGLOBAL_STATUS['SysErr_bytes'] = '0';
        $dummyGLOBAL_STATUS['Rejected_bytes'] = '0';
        $dummyGLOBAL_STATUS['Can\'t create output'] = '0';
        $dummyGLOBAL_STATUS['Sent_bytes'] = '0';
        $dummyGLOBAL_STATUS['Please try again later'] = '0';

        if (isset($SAR['GLOBAL_STATUS'])) {
            // Make sure our imported GLOBAL_STATUS has the basic defaults. If not,
            // then set them from the above $dummyGLOBAL_STATUS array:
            foreach ($dummyGLOBAL_STATUS as $key => $value) {
                if (!isset($SAR['GLOBAL_STATUS'][$key])) {
                    $SAR['GLOBAL_STATUS'][$key] = $value;
                }
            }

            //
            //-- Statistic Output:
            //

            $statsTabs = array($Global_Default, $Global_Spamming, $Global_Virus, $Global_Notification, $Global_Rejections, $Global_Status, $Global_SMTPAuth,
                           $Top_Senders, $Top_Recipients, $Top_Spamming, $Top_Virus, $Top_Notification, $Top_Rejection, $Top_SMTPAuth, $AV_SPAMdMilter);

            $statsBlock = $factory->getPagedBlock("EmailStats", $statsTabs);
            $statsBlock->setFormDisabled(TRUE);
            $statsBlock->setToggle("#");
            $statsBlock->setSideTabs(TRUE);
            $statsBlock->setDivHeight('600');
            $statsBlock->setDefaultPage($Global_Default);

            //@//
            //@//-- Messaging Tab:
            //@//

            //
            //-- Global Defaults:
            //

            $SAR['messaging']['inbound_mean']           = Meaner($SAR['messaging']['inbound_bytes'], $SAR['messaging']['inbound']);
            $SAR['messaging']['local_inbound_mean']     = Meaner($SAR['messaging']['local_inbound_bytes'], $SAR['messaging']['local_inbound']);
            $SAR['messaging']['total_inbound_mean']     = Meaner($SAR['messaging']['total_inbound_bytes'], $SAR['messaging']['total_inbound']);
            $SAR['messaging']['outbound_mean']          = Meaner($SAR['messaging']['outbound_bytes'], $SAR['messaging']['outbound']);
            $SAR['messaging']['local_outbound_mean']    = Meaner($SAR['messaging']['local_outbound_bytes'], $SAR['messaging']['local_outbound']);
            $SAR['messaging']['total_outbound_mean']    = Meaner($SAR['messaging']['total_outbound_bytes'], $SAR['messaging']['total_outbound']);

            //
            //-- Messaging flows table:
            //
            $messaging_flows_table = '
                <div class="box grid_16">
                    <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.messaging_flows]]") . '</h2>
                    <table class="static">
                        <thead>
                            <tr>
                                <th><b>&nbsp;</b></th>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.messages]]") . '</b></th>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.size]]") . '</b></th>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.mean]]") . '</b></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.incoming]]") . '</td>
                                <td>' . $SAR['messaging']['inbound'] . '</td>
                                <td>' . SimNum($SAR['messaging']['inbound_bytes']) . '</td>
                                <td>' . $SAR['messaging']['inbound_mean'] . '</td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.local_incomming]]") . '</td>
                                <td>' . $SAR['messaging']['local_inbound'] . '</td>
                                <td>' . SimNum($SAR['messaging']['local_inbound_bytes']) . '</td>
                                <td>' . $SAR['messaging']['local_inbound_mean'] . '</td>
                            </tr>
                            <tr>
                                <td><b>' . $this->i18n->get("[[base-mailsitestats.total_incomming]]") . '</td>
                                <td><b>' . $SAR['messaging']['total_inbound'] . '</b></td>
                                <td><b>' . SimNum($SAR['messaging']['total_inbound_bytes']) . '</b></td>
                                <td><b>' . $SAR['messaging']['total_inbound_mean'] . '</b></td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.outgoing]]") . '</td>
                                <td>' . $SAR['messaging']['outbound'] . '</td>
                                <td>' . SimNum($SAR['messaging']['outbound_bytes']) . '</td>
                                <td>' . $SAR['messaging']['outbound_mean'] . '</td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.local_delivery]]") . '</td>
                                <td>' . $SAR['messaging']['local_outbound'] . '</td>
                                <td>' . SimNum($SAR['messaging']['local_outbound_bytes']) . '</td>
                                <td>' . $SAR['messaging']['local_outbound_mean'] . '</td>
                            </tr>
                            <tr>
                                <td><b>' . $this->i18n->get("[[base-mailsitestats.total_outgoing]]") . '</td>
                                <td><b>' . $SAR['messaging']['total_outbound'] . '</b></td>
                                <td><b>' . SimNum($SAR['messaging']['total_outbound_bytes']) . '</b></td>
                                <td><b>' . $SAR['messaging']['total_outbound_mean'] . '</b></td>
                            </tr>
                        </tbody>
                    </table>
                </div>';
            // Out with the messaging_flows_table:
            $statsBlock->addFormField(
                $factory->getRawHTML("messaging_flows_table", $messaging_flows_table),
                $factory->getLabel("messaging_flows_table"),
                $Global_Default
            );

            //
            //-- Graph for 'Messaging Flow':
            //

            // Items of interest:
            $mfg_inbound = explode(":", $SAR['messaging']['values']);
            $mfg_outbound = explode(":", $SAR['messaging']['values1']);
            $messaging_flow_seenTimes = explode(":", $SAR['messaging']['lbls']);

            foreach ($mfg_inbound as $key => $value) {
                $messaging_flow_Data[$this->i18n->get("[[base-mailsitestats.inbound]]")][$key] = $value;
            }

            foreach ($mfg_outbound as $key => $value) {
                $messaging_flow_Data[$this->i18n->get("[[base-mailsitestats.outbound]]")][$key] = $value;
            }

            $messaging_flow_graph = $factory->getBarGraph("messaging_flow", $messaging_flow_Data, $messaging_flow_seenTimes);
            $messaging_flow_graph->setPoints($this->i18n->get("[[base-mailsitestats.inbound]]"), FALSE);
            $messaging_flow_graph->setPoints($this->i18n->get("[[base-mailsitestats.outbound]]"), FALSE);
            $messaging_flow_graph->setSize("590", "450");
            $messaging_flow_graph->setXLabel($this->i18n->get("[[base-mailsitestats.number_of_messages]]"));
            $statsBlock->addFormField(
                $messaging_flow_graph,
                "",
                $Global_Default);

            //
            //-- Graph for 'Messaging Size Flow':
            //

            // Items of interest:
            $msg_inbound = explode(":", $SAR['messaging']['values_bytes']);
            $msg_outbound = explode(":", $SAR['messaging']['values1_bytes']);
            $message_size_flow_seenTimes = explode(":", $SAR['messaging']['lbls']);

            foreach ($msg_inbound as $key => $value) {
                $message_size_flow_Data[$this->i18n->get("[[base-mailsitestats.inbound]]")][$key] = "'" . sprintf("%.2f", $value/1000000) . "'";
            }

            foreach ($msg_outbound as $key => $value) {
                $message_size_flow_Data[$this->i18n->get("[[base-mailsitestats.outbound]]")][$key] = "'" . sprintf("%.2f", $value/1000000) . "'";
            }

            $message_size_flow_graph = $factory->getBarGraph("Messaging_Size_Flow", $message_size_flow_Data, $message_size_flow_seenTimes);
            $message_size_flow_graph->setPoints($this->i18n->get("[[base-mailsitestats.inbound]]"), FALSE);
            $message_size_flow_graph->setPoints($this->i18n->get("[[base-mailsitestats.outbound]]"), FALSE);
            $message_size_flow_graph->setSize("590", "450");
            $message_size_flow_graph->setXLabel($this->i18n->get("[[base-mailsitestats.size_mb]]"));
            $statsBlock->addFormField(
                $message_size_flow_graph,
                "",
                $Global_Default);

            //
            //-- Message delivery flows (table):
            //

            // Prepare data:
            $SAR['delivery']['total'] = $SAR['GLOBAL_STATUS']['Sent'];
            if (($SAR['delivery']['total'] == "") || ($SAR['delivery']['total'] == "0")) { 
                $SAR['delivery']['total'] = "1"; 
            }
            $SAR['delivery']['total_bytes'] = $SAR['GLOBAL_STATUS']['Sent_bytes'];
            $SAR['delivery']['Ext_Int_percent'] = sprintf("%.2f", ($SAR['delivery']['Ext_Int']*100) / $SAR['delivery']['total']);
            $SAR['delivery']['Ext_Ext_percent'] = sprintf("%.2f", ($SAR['delivery']['Ext_Ext']*100) / $SAR['delivery']['total']);
            $SAR['delivery']['Int_Int_percent'] = sprintf("%.2f", ($SAR['delivery']['Int_Int']*100) / $SAR['delivery']['total']);
            $SAR['delivery']['Int_Ext_percent'] = sprintf("%.2f", ($SAR['delivery']['Int_Ext']*100) / $SAR['delivery']['total']);
            $nbsender = 0;
            $nbrcpt = 0;

            $message_delivery_flows_table = '
                <div class="box grid_16">
                    <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.messaging_flows]]") . '</h2>
                    <table class="static">
                        <thead>
                            <tr>
                                <th><b>&nbsp;</b></th>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.messages]]") . '</b></th>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.size]]") . '</b></th>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.percentage]]") . '</b></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.external_to_internal]]") . '</td>
                                <td>' . $SAR['delivery']['Ext_Int'] . '</td>
                                <td>' . SimNum($SAR['delivery']['Ext_Int_bytes']) . '</td>
                                <td>' . $SAR['delivery']['Ext_Int_percent'] . '</td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.external_to_external]]") . '</td>
                                <td>' . $SAR['delivery']['Ext_Ext'] . '</td>
                                <td>' . SimNum($SAR['delivery']['Ext_Ext_bytes']) . '</td>
                                <td>' . $SAR['delivery']['Ext_Ext_percent'] . '</td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.internal_to_internal]]") . '</td>
                                <td>' . $SAR['delivery']['Int_Int'] . '</td>
                                <td>' . SimNum($SAR['delivery']['Int_Int_bytes']) . '</td>
                                <td>' . $SAR['delivery']['Int_Int_percent'] . '</td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.internal_to_external]]") . '</td>
                                <td>' . $SAR['delivery']['Int_Ext'] . '</td>
                                <td>' . SimNum($SAR['delivery']['Int_Ext_bytes']) . '</td>
                                <td>' . $SAR['delivery']['Int_Ext_percent'] . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>';

            // Out with the message_delivery_flows_table:
            $statsBlock->addFormField(
                $factory->getRawHTML("message_delivery_flows_table", $message_delivery_flows_table),
                $factory->getLabel("message_delivery_flows_table"),
                $Global_Default
            );

            //
            //-- 'Delivery Direction' Pie Chart:
            //

            if ($SAR['GLOBAL_STATUS']['Sent'] != '0') {
                // Setup data:
                $delivery_direction_Data['Ext -> Int'] = $SAR['delivery']['Ext_Int_percent'];
                $delivery_direction_Data['Ext -> Ext'] = $SAR['delivery']['Ext_Ext_percent'];
                $delivery_direction_Data['Int -> Int'] = $SAR['delivery']['Int_Int_percent'];
                $delivery_direction_Data['Int -> Ext'] = $SAR['delivery']['Int_Ext_percent'];

                // Generate Pie Chart:
                $delivery_direction_pieChart = $factory->getPieChart("delivery_direction_pieChart", $delivery_direction_Data);
                $delivery_direction_pieChart->setSize("590", "450");
                $delivery_direction_pieChart->setXLabel($this->i18n->get("[[base-mailsitestats.delivery_direction]]"));
                $statsBlock->addFormField(
                    $delivery_direction_pieChart,
                    "",
                    $Global_Default);
            }

            //
            //-- 'Different senders/recipients' (table):
            //

            // Prepare data:
            $nbsender = "0";
            $nbsenderArr = explode(":", $SAR['messaging']['nbsender']);
            foreach ($nbsenderArr as $value) {
                $nbsender += $value;
            }

            $nbrcpt = "0";
            $nbrcptArr = explode(":", $SAR['messaging']['nbrcpt']);
            foreach ($nbrcptArr as $value) {
                $nbrcpt += $value;
            }

            // Prepare table:
            $diff_senders_recipients_table = '
                <div class="box grid_16">
                    <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.different_senders_recipients]]") . '</h2>
                    <table class="static">
                        <thead>
                            <tr>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.senders]]") . '</b></th>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.recipients]]") . '</b></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>' . $nbsender . '</td>
                                <td>' . $nbrcpt . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>';

            // Out with the diff_senders_recipients_table:
            $statsBlock->addFormField(
                $factory->getRawHTML("diff_senders_recipients_table", $diff_senders_recipients_table),
                $factory->getLabel("diff_senders_recipients_table"),
                $Global_Default
            );

            //
            //-- Graph for 'Different senders/recipients':
            //

            if ($period != "hour") {
                // Items of interest:
                $diff_sender_reciepient_seenTimes = explode(":", $SAR['messaging']['lbls']);
                foreach ($nbsenderArr as $key => $value) {
                    $diff_sender_reciepient_Data[$this->i18n->get("[[base-mailsitestats.senders]]")][$key] = "'" . sprintf("%.2f", $value) . "'";
                }

                foreach ($nbrcptArr as $key => $value) {
                    $diff_sender_reciepient_Data[$this->i18n->get("[[base-mailsitestats.recipients]]")][$key] = "'" . sprintf("%.2f", $value) . "'";
                }

                $diff_sender_reciepient_graph = $factory->getBarGraph("diff_sender_reciepient", $diff_sender_reciepient_Data, $diff_sender_reciepient_seenTimes);
                $diff_sender_reciepient_graph->setPoints($this->i18n->get("[[base-mailsitestats.senders]]"), FALSE);
                $diff_sender_reciepient_graph->setPoints($this->i18n->get("[[base-mailsitestats.recipients]]"), FALSE);
                $diff_sender_reciepient_graph->setSize("590", "450");
                $diff_sender_reciepient_graph->setXLabel($this->i18n->get("[[base-mailsitestats.different_senders_recipients]]"));
                $statsBlock->addFormField(
                    $diff_sender_reciepient_graph,
                    "",
                    $Global_Default);
            }

            //@//
            //@//-- Spamming Tab:
            //@//

            //
            //--- 'Spamming flows' table:
            //

            // Prepare data for Spamming flows + Spam delivery flows:
            $SAR['spam']['inbound_mean']            = Meaner($SAR['spam']['inbound_bytes'],         $SAR['spam']['inbound']);
            $SAR['spam']['local_inbound_mean']      = Meaner($SAR['spam']['local_inbound_bytes'],   $SAR['spam']['local_inbound']);
            $SAR['spam']['total_inbound_mean']      = Meaner($SAR['spam']['total_inbound_bytes'],   $SAR['spam']['total_inbound']);
            $SAR['spam']['outbound_mean']           = Meaner($SAR['spam']['outbound_bytes'],        $SAR['spam']['outbound']);
            $SAR['spam']['local_outbound_mean']     = Meaner($SAR['spam']['local_outbound_bytes'],  $SAR['spam']['local_outbound']);
            $SAR['spam']['total_outbound_mean']     = Meaner($SAR['spam']['total_outbound_bytes'],  $SAR['spam']['total_outbound']);

            $SAR['spam']['Ext_Int_mean']            = Meaner($SAR['spam']['Ext_Int'],   $SAR['spam']['Ext_Int_bytes']);
            $SAR['spam']['Int_Int_mean']            = Meaner($SAR['spam']['Int_Int'],   $SAR['spam']['Int_Int_bytes']);
            $SAR['spam']['Int_Ext_mean']            = Meaner($SAR['spam']['Int_Ext'],   $SAR['spam']['Int_Ext_bytes']);
            $SAR['spam']['Ext_Ext_mean']            = Meaner($SAR['spam']['Ext_Ext'],   $SAR['spam']['Ext_Ext_bytes']);

            $SAR['spam']['Int_Int_bytes']           = SimNum($SAR['spam']['Int_Int_bytes']);
            $SAR['spam']['Int_Ext_bytes']           = SimNum($SAR['spam']['Int_Ext_bytes']);
            $SAR['spam']['Ext_Ext_bytes']           = SimNum($SAR['spam']['Ext_Ext_bytes']);
            $SAR['spam']['Ext_Int_bytes']           = SimNum($SAR['spam']['Ext_Int_bytes']);

            // Prepare table:
            $spamming_flows_table = '
                <div class="box grid_16">
                    <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.spamming_flows]]") . '</h2>
                    <table class="static">
                        <thead>
                            <tr>
                                <th><b>&nbsp;</b></th>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.messages]]") . '</b></th>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.size]]") . '</b></th>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.size_per_msg]]") . '</b></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.incoming]]") . '</td>
                                <td>' . $SAR['GLOBAL_STATUS']['Spam'] . '</td>
                                <td>' . SimNum($SAR['spam']['inbound_bytes']) . '</td>
                                <td>' . $SAR['spam']['inbound_mean'] . '</td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.local_incomming]]") . '</td>
                                <td>' . $SAR['spam']['local_inbound'] . '</td>
                                <td>' . SimNum($SAR['spam']['local_inbound_bytes']) . '</td>
                                <td>' . $SAR['spam']['local_inbound_mean'] . '</td>
                            </tr>
                            <tr>
                                <td><b>' . $this->i18n->get("[[base-mailsitestats.total_incomming]]") . '</b></td>
                                <td><b>' . $SAR['spam']['total_inbound'] . '</b></td>
                                <td><b>' . SimNum($SAR['spam']['total_inbound_bytes']) . '</b></td>
                                <td><b>' . $SAR['spam']['total_inbound_mean'] . '</b></td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.outgoing]]") . '</td>
                                <td>' . $SAR['spam']['outbound'] . '</td>
                                <td>' . SimNum($SAR['spam']['outbound_bytes']) . '</td>
                                <td>' . $SAR['spam']['outbound_mean'] . '</td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.local_delivery]]") . '</td>
                                <td>' . $SAR['spam']['local_outbound'] . '</td>
                                <td>' . SimNum($SAR['spam']['local_outbound_bytes']) . '</td>
                                <td>' . $SAR['spam']['local_outbound_mean'] . '</td>
                            </tr>
                            <tr>
                                <td><b>' . $this->i18n->get("[[base-mailsitestats.total_outgoing]]") . '</b></td>
                                <td><b>' . $SAR['spam']['total_outbound'] . '</b></td>
                                <td><b>' . SimNum($SAR['spam']['total_outbound_bytes']) . '</b></td>
                                <td><b>' . $SAR['spam']['total_outbound_mean'] . '</b></td>
                            </tr>
                        </tbody>
                    </table>
                </div>';

            //
            //--- 'Spam delivery flows' table:
            //

            $spam_delivery_flows_table = '
                <div class="box grid_16">
                    <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.spam_delivery_flows]]") . '</h2>
                    <table class="static">
                        <thead>
                            <tr>
                                <th><b>&nbsp;</b></th>
                                <td><b>' . $this->i18n->get("[[base-mailsitestats.messages]]") . '</b></td>
                                <td><b>' . $this->i18n->get("[[base-mailsitestats.size]]") . '</b></td>
                                <td><b>' . $this->i18n->get("[[base-mailsitestats.size_per_msg]]") . '</b></td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.external_to_internal]]") . '</td>
                                <td>' . $SAR['spam']['Ext_Int'] . '</td>
                                <td>' . $SAR['spam']['Ext_Int_bytes'] . '</td>
                                <td>' . $SAR['spam']['Ext_Int_mean'] . '</td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.external_to_external]]") . '</td>
                                <td>' . $SAR['spam']['Ext_Ext'] . '</td>
                                <td>' . $SAR['spam']['Ext_Ext_bytes'] . '</td>
                                <td>' . $SAR['spam']['Ext_Ext_mean'] . '</td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.internal_to_internal]]") . '</td>
                                <td>' . $SAR['spam']['Int_Int'] . '</td>
                                <td>' . $SAR['spam']['Int_Int_bytes'] . '</td>
                                <td>' . $SAR['spam']['Int_Int_mean'] . '</td>
                            </tr>
                            <tr>
                                <td>' . $this->i18n->get("[[base-mailsitestats.internal_to_external]]") . '</td>
                                <td>' . $SAR['spam']['Int_Ext'] . '</td>
                                <td>' . $SAR['spam']['Int_Ext_bytes'] . '</td>
                                <td>' . $SAR['spam']['Int_Ext_mean'] . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>';

            if (($SAR['spam']['total_inbound'] == "0") && ($SAR['spam']['total_inbound'] == "0")) {

                // No data:
                $global_spam_nodata = $factory->getTextField('global_spam_nodata', $no_data, 'r');
                $global_spam_nodata->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $global_spam_nodata,
                        $factory->getLabel("global_spam_nodata"),
                        $Global_Spamming
                        );
            }
            else {

                // Out with the spamming_flows_table:
                $statsBlock->addFormField(
                    $factory->getRawHTML("spamming_flows_table", $spamming_flows_table),
                    $factory->getLabel("spamming_flows_table"),
                    $Global_Spamming
                );

                // Out with the spam_delivery_flows_table:
                $statsBlock->addFormField(
                    $factory->getRawHTML("spam_delivery_flows_table", $spam_delivery_flows_table),
                    $factory->getLabel("spam_delivery_flows_table"),
                    $Global_Spamming
                );

                //
                //-- Graph for 'Spamming Flow':
                //

                // Items of interest:
                $msg_spam_array = explode(":", $SAR['spam']['values']);
                $spamming_flow_seenTimes = explode(":", $SAR['spam']['lbls']);

                foreach ($msg_spam_array as $key => $value) {
                    $spamming_flow_Data['#SPAMs'][$key] = "'" . sprintf("%.2f", $value) . "'";
                }

                $spamming_flow_graph = $factory->getBarGraph("Spamming_Flow", $spamming_flow_Data, $spamming_flow_seenTimes);
                $spamming_flow_graph->setPoints('#SPAMs', FALSE);
                $spamming_flow_graph->setSize("590", "450");
                $spamming_flow_graph->setXLabel($this->i18n->get("[[base-mailsitestats.spamming_flow]]"));
                $statsBlock->addFormField(
                    $spamming_flow_graph,
                    "",
                    $Global_Spamming);

            }

            //@//
            //@//-- Virus Tab:
            //@//

            //
            //--- Note: Effectively disabled for now as I don't have any sample data to play with.
            //

            // Start sane:
            $SAR['virus']['inbound'] = defaulter($SAR['virus']['inbound']);
            $SAR['virus']['local_inbound'] = defaulter($SAR['virus']['local_inbound']);
            $SAR['virus']['outbound'] = defaulter($SAR['virus']['outbound']);
            $SAR['virus']['local_outbound'] = defaulter($SAR['virus']['local_outbound']);

            $SAR['virus']['Int_Int'] = defaulter($SAR['virus']['Int_Int']);
            $SAR['virus']['Int_Ext'] = defaulter($SAR['virus']['Int_Ext']);
            $SAR['virus']['Ext_Ext'] = defaulter($SAR['virus']['Ext_Ext']);
            $SAR['virus']['Ext_Int'] = defaulter($SAR['virus']['Ext_Int']);

            // Prepare data for virus statistics:
            $SAR['virus']['total_inbound']          = $SAR['virus']['inbound']          + $SAR['virus']['local_inbound'];
            $SAR['virus']['total_inbound_bytes']    = $SAR['virus']['inbound_bytes']    + $SAR['virus']['local_inbound_bytes'];
            $SAR['virus']['total_outbound']         = $SAR['virus']['outbound']         + $SAR['virus']['local_outbound'];
            $SAR['virus']['total_outbound_bytes']   = $SAR['virus']['outbound_bytes']   + $SAR['virus']['local_outbound'];

            $SAR['virus']['total_inbound_bytes']    = SimNum($SAR['virus']['total_inbound_bytes']/1000000);
            $SAR['virus']['inbound_bytes']          = SimNum($SAR['virus']['inbound_bytes']/1000000);
            $SAR['virus']['local_inbound_bytes']    = SimNum($SAR['virus']['local_inbound_bytes']/1000000);
            $SAR['virus']['total_outbound_bytes']   = SimNum($SAR['virus']['total_outbound_bytes']/1000000);
            $SAR['virus']['outbound_bytes']         = SimNum($SAR['virus']['outbound_bytes']/1000000);
            $SAR['virus']['local_outbound_bytes']   = SimNum($SAR['virus']['local_outbound_bytes']/1000000);

            $SAR['virus']['Int_Int']                = SimNum($SAR['virus']['Int_Int']);
            $SAR['virus']['Int_Ext']                = SimNum($SAR['virus']['Int_Ext']);
            $SAR['virus']['Ext_Ext']                = SimNum($SAR['virus']['Ext_Ext']);
            $SAR['virus']['Ext_Int']                = SimNum($SAR['virus']['Ext_Int']);

            // Viruses flows / Viruses delivery flows / syserr flows

            $SAR['virus']['inbound_mean']           = Meaner($SAR['virus']['inbound_bytes'],    $SAR['virus']['inbound']);
            $SAR['virus']['local_inbound_mean']     = Meaner($SAR['virus']['local_inbound_bytes'],  $SAR['virus']['local_inbound']);
            $SAR['virus']['total_inbound_mean']     = Meaner($SAR['virus']['total_inbound_bytes'],  $SAR['virus']['total_inbound']);
            $SAR['virus']['outbound_mean']          = Meaner($SAR['virus']['outbound_bytes'],   $SAR['virus']['outbound']);
            $SAR['virus']['local_outbound_mean']    = Meaner($SAR['virus']['local_outbound_bytes'],     $SAR['virus']['local_outbound']);
            $SAR['virus']['total_outbound_mean']    = Meaner($SAR['virus']['total_outbound_bytes'],     $SAR['virus']['total_outbound']);

            $SAR['virus']['Ext_Int_mean']           = Meaner($SAR['virus']['Ext_Int'],  $SAR['virus']['Ext_Int_bytes']);
            $SAR['virus']['Int_Int_mean']           = Meaner($SAR['virus']['Int_Int'],  $SAR['virus']['Int_Int_bytes']);
            $SAR['virus']['Int_Ext_mean']           = Meaner($SAR['virus']['Int_Ext'],  $SAR['virus']['Int_Ext_bytes']);
            $SAR['virus']['Ext_Ext_mean']           = Meaner($SAR['virus']['Ext_Ext'],  $SAR['virus']['Ext_Ext_bytes']);

            $SAR['virus']['Int_Int_bytes']          = SimNum($SAR['virus']['Int_Int_bytes']/1000000);
            $SAR['virus']['Int_Ext_bytes']          = SimNum($SAR['virus']['Int_Ext_bytes']/1000000);
            $SAR['virus']['Ext_Ext_bytes']          = SimNum($SAR['virus']['Ext_Ext_bytes']/1000000);
            $SAR['virus']['Ext_Int_bytes']          = SimNum($SAR['virus']['Ext_Int_bytes']/1000000);

            if (($SAR['virus']['total_inbound'] == "0") && ($SAR['virus']['total_inbound'] == "0")) {
                $global_virus_nodata = $factory->getTextField('global_virus_nodata', $no_data, 'r');
                $global_virus_nodata->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $global_virus_nodata,
                        $factory->getLabel("global_virus_nodata"),
                        $Global_Virus
                        );
            }

            //@//
            //@//-- Notification Tab:
            //@//

            // Prep data:

            if ((!isset($SAR['dsn']['outbound']))       || ($SAR['dsn']['outbound'] == ""))         { $SAR['dsn']['outbound'] = '0'; }
            if ((!isset($SAR['dsn']['local_outbound']))     || ($SAR['dsn']['local_outbound'] == ""))   { $SAR['dsn']['local_outbound'] = '0'; }
            if ((!isset($SAR['dsn']['error']))          || ($SAR['dsn']['error'] == ""))            { $SAR['dsn']['error'] = '0'; }

            $SAR['dsn']['total_outbound'] = $SAR['dsn']['outbound'] + $SAR['dsn']['local_outbound'];

            if ((!isset($SAR['dsn']['Int_Int']))        || ($SAR['dsn']['Int_Int'] == ""))          { $SAR['dsn']['Int_Int'] = '0'; }
            if ((!isset($SAR['dsn']['Int_Ext']))        || ($SAR['dsn']['Int_Ext'] == ""))          { $SAR['dsn']['Int_Ext'] = '0'; }

            $total_dsn = $SAR['dsn']['total_outbound'] + $SAR['dsn']['error'];

            if ($total_dsn == '0') {
                // Got no sensible data:
                $dsn_nodata = $factory->getTextField('dsn_nodata', $no_data, 'r');
                $dsn_nodata->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $dsn_nodata,
                        $factory->getLabel("dsn_nodata"),
                        $Global_Notification
                        );
            }
            else {

                // We have data:

                $delivery_status_notification_table = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.delivery_status_notification]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>&nbsp;</b></th>
                                    <td><b>' . $this->i18n->get("[[base-mailsitestats.messages]]") . '</b></td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>' . $this->i18n->get("[[base-mailsitestats.outgoing]]") . '</td>
                                    <td>' . $SAR['dsn']['total_outbound'] . '</td>
                                </tr>
                                <tr>
                                    <td>' . $this->i18n->get("[[base-mailsitestats.in_error]]") . '</td>
                                    <td>' . $SAR['dsn']['error'] . '</td>
                                </tr>
                                <tr>
                                    <td><b>' . $this->i18n->get("[[base-mailsitestats.total]]") . '</b></td>
                                    <td><b>' . $total_dsn . '</b></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>';

                // Out with the delivery_status_notification_table:
                $statsBlock->addFormField(
                    $factory->getRawHTML("delivery_status_notification_table", $delivery_status_notification_table),
                    $factory->getLabel("delivery_status_notification_table"),
                    $Global_Notification
                );

                $dsn_delivery_flows_table = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.dsn_delivery_flows]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>&nbsp;</b></th>
                                    <td><b>' . $this->i18n->get("[[base-mailsitestats.messages]]") . '</b></td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>' . $this->i18n->get("[[base-mailsitestats.internal_to_internal]]") . '</td>
                                    <td>' . $SAR['dsn']['Int_Int'] . '</td>
                                </tr>
                                <tr>
                                    <td>' . $this->i18n->get("[[base-mailsitestats.internal_to_external]]") . '</td>
                                    <td>' . $SAR['dsn']['Int_Ext'] . '</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>';

                // Out with the dsn_delivery_flows_table:
                $statsBlock->addFormField(
                    $factory->getRawHTML("dsn_delivery_flows_table", $dsn_delivery_flows_table),
                    $factory->getLabel("dsn_delivery_flows_table"),
                    $Global_Notification
                );

                //
                //-- DSN Flow (graph):
                //

                // Items of interest:
                $diff_dsn_seenTimes = explode(":", $SAR['dsn']['lbls']);
                $dsn_flow_Data = explode(":", $SAR['dsn']['values']);
                foreach ($dsn_flow_Data as $key => $value) {
                    $diff_dsn_flow_Data['#dsn'][$key] = "'" . $value . "'";
                }

                $dsn_flow_Data_graph = $factory->getBarGraph("dsn_flow_Data", $diff_dsn_flow_Data, $diff_dsn_seenTimes);
                $dsn_flow_Data_graph->setPoints('#dsn', FALSE);
                $dsn_flow_Data_graph->setSize("590", "450");
                $dsn_flow_Data_graph->setXLabel($this->i18n->get("[[base-mailsitestats.dsn_flow]]"));
                $statsBlock->addFormField(
                    $dsn_flow_Data_graph,
                    "",
                    $Global_Notification);

            }

            //@//
            //@//-- Rejection Tab:
            //@//

            // Prep data:

            if ((!isset($SAR['reject']['inbound']))         || ($SAR['reject']['inbound'] == ""))           { $SAR['reject']['inbound'] = '0'; }
            if ((!isset($SAR['reject']['local_inbound']))   || ($SAR['reject']['local_inbound'] == ""))     { $SAR['reject']['local_inbound'] = '0'; }

            $SAR['reject']['total_inbound'] = $SAR['reject']['inbound'] + $SAR['reject']['local_inbound'];
            $SAR['reject']['total_inbound_bytes'] = $SAR['reject']['inbound_bytes'] + $SAR['reject']['local_inbound_bytes'];

            if ((!isset($SAR['err']['inbound']))        || ($SAR['err']['inbound'] == ""))                  { $SAR['err']['inbound'] = '0'; }
            if ((!isset($SAR['err']['local_inbound']))  || ($SAR['err']['local_inbound'] == ""))            { $SAR['err']['local_inbound'] = '0'; }

            $SAR['err']['total_inbound'] = $SAR['err']['inbound'] + $SAR['err']['local_inbound'];
            $SAR['err']['total_inbound_bytes'] = $SAR['err']['inbound_bytes'] + $SAR['err']['local_inbound_bytes'];

            $SAR['reject']['total_inbound_bytes']   = SimNum($SAR['reject']['total_inbound_bytes']);
            $SAR['reject']['inbound_bytes']         = SimNum($SAR['reject']['inbound_bytes']);
            $SAR['reject']['local_inbound_bytes']   = SimNum($SAR['reject']['local_inbound_bytes']);

            $SAR['err']['total_inbound_bytes']      = SimNum($SAR['err']['total_inbound_bytes']);
            $SAR['err']['inbound_bytes']            = SimNum($SAR['err']['inbound_bytes']);
            $SAR['err']['local_inbound_bytes']      = SimNum($SAR['err']['local_inbound_bytes']);

            $SAR['reject']['inbound_mean']          = Meaner($SAR['reject']['inbound_bytes'],   $SAR['reject']['inbound']);
            $SAR['reject']['local_inbound_mean']    = Meaner($SAR['reject']['local_inbound_bytes'],     $SAR['reject']['local_inbound']);
            $SAR['reject']['total_inbound_mean']    = Meaner($SAR['reject']['total_inbound_bytes'],     $SAR['reject']['total_inbound']);

            $SAR['err']['inbound_mean']             = Meaner($SAR['err']['inbound_bytes'],          $SAR['err']['inbound']);
            $SAR['err']['local_inbound_mean']       = Meaner($SAR['err']['local_inbound_bytes'],    $SAR['err']['local_inbound']);
            $SAR['err']['total_inbound_mean']       = Meaner($SAR['err']['total_inbound_bytes'],    $SAR['err']['total_inbound']);

            if (($SAR['reject']['total_inbound'] == '0') && ($SAR['err']['total_inbound'] == '0')) {
                // Got no sensible data:
                $reject_nodata = $factory->getTextField('reject_nodata', $no_data, 'r');
                $reject_nodata->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $reject_nodata,
                        $factory->getLabel("reject_nodata"),
                        $Global_Rejections
                        );
            }
            else {

                // We have data:
                $rejection_flows_table = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.rejection_flows]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>&nbsp;</b></th>
                                    <td><b>' . $this->i18n->get("[[base-mailsitestats.messages]]") . '</b></td>
                                    <td><b>' . $this->i18n->get("[[base-mailsitestats.size]]") . '</b></td>
                                    <td><b>' . $this->i18n->get("[[base-mailsitestats.size_per_msg]]") . '</b></td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>' . $this->i18n->get("[[base-mailsitestats.incoming]]") . '</td>
                                    <td>' . $SAR['reject']['inbound'] . '</td>
                                    <td>' . $SAR['reject']['inbound_bytes'] . '</td>
                                    <td>' . $SAR['reject']['inbound_mean'] . '</td>
                                </tr>
                                <tr>
                                    <td>' . $this->i18n->get("[[base-mailsitestats.local_incomming]]") . '</td>
                                    <td>' . $SAR['reject']['local_inbound'] . '</td>
                                    <td>' . $SAR['reject']['local_inbound_bytes'] . '</td>
                                    <td>' . $SAR['reject']['local_inbound_mean'] . '</td>
                                </tr>
                                <tr>
                                    <td><b>' . $this->i18n->get("[[base-mailsitestats.total_incomming]]") . '</b></td>
                                    <td><b>' . $SAR['reject']['total_inbound'] . '</b></td>
                                    <td><b>' . $SAR['reject']['local_inbound_bytes'] . '</b></td>
                                    <td><b>' . $SAR['reject']['total_inbound_mean'] . '</b></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>';

                // Out with the rejection_flows_table:
                $statsBlock->addFormField(
                    $factory->getRawHTML("rejection_flows_table", $rejection_flows_table),
                    $factory->getLabel("rejection_flows_table"),
                    $Global_Rejections
                );

                // Syserr flows
                $syserr_flows_table = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.rejection_flows]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>&nbsp;</b></th>
                                    <td><b>' . $this->i18n->get("[[base-mailsitestats.messages]]") . '</b></td>
                                    <td><b>' . $this->i18n->get("[[base-mailsitestats.size]]") . '</b></td>
                                    <td><b>' . $this->i18n->get("[[base-mailsitestats.size_per_msg]]") . '</b></td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>' . $this->i18n->get("[[base-mailsitestats.incoming]]") . '</td>
                                    <td>' . $SAR['err']['inbound'] . '</td>
                                    <td>' . $SAR['err']['inbound_bytes'] . '</td>
                                    <td>' . $SAR['err']['inbound_mean'] . '</td>
                                </tr>
                                <tr>
                                    <td>' . $this->i18n->get("[[base-mailsitestats.local_incomming]]") . '</td>
                                    <td>' . $SAR['err']['local_inbound'] . '</td>
                                    <td>' . $SAR['err']['local_inbound_bytes'] . '</td>
                                    <td>' . $SAR['err']['local_inbound_mean'] . '</td>
                                </tr>
                                <tr>
                                    <td><b>' . $this->i18n->get("[[base-mailsitestats.total_incomming]]") . '</b></td>
                                    <td><b>' . $SAR['err']['total_inbound'] . '</b></td>
                                    <td><b>' . $SAR['err']['local_inbound_bytes'] . '</b></td>
                                    <td><b>' . $SAR['err']['total_inbound_mean'] . '</b></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>';

                // Out with the syserr_flows_table:
                $statsBlock->addFormField(
                    $factory->getRawHTML("syserr_flows_table", $syserr_flows_table),
                    $factory->getLabel("syserr_flows_table"),
                    $Global_Rejections
                );

            }

            //@//
            //@//-- Status Tab:
            //@//

            // Prep data:

            $delivery_global_total = 1;
            $total_percent = 0;
            $new_GLOBAL_STATUS = array();
            foreach ($SAR['GLOBAL_STATUS'] as $key => $value) {
                if (!preg_match('/_bytes/', $key)) {
                    $delivery_global_total += $value;
                    $new_GLOBAL_STATUS[$key] = $value;
                }
            }

            $delivery_total = $SAR['GLOBAL_STATUS']['Sent'];
            $delivery_total_bytes = $SAR['GLOBAL_STATUS']['Sent_bytes'];
            $total_percent = 0;
            $piecount = 0;

            $messaging_status_table = '
                <div class="box grid_16">
                    <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.messaging_status]]") . '</h2>
                    <table class="static">
                        <thead>
                            <tr>
                                <th><b>&nbsp;</b></th>
                                <td><b>' . $this->i18n->get("[[base-mailsitestats.messages]]") . '</b></td>
                                <td><b>' . $this->i18n->get("[[base-mailsitestats.size]]") . '</b></td>
                                <td><b>' . $this->i18n->get("[[base-mailsitestats.percentage]]") . '</b></td>
                            </tr>
                        </thead>
                        <tbody>' . "\n";

            arsort($new_GLOBAL_STATUS);
            $messaging_status_pieChart = array();
            $messaging_status_pieChart_Data_unlock = '0';
            foreach ($new_GLOBAL_STATUS as $key => $value) {
                if ((!preg_match('/_bytes/', $key)) && (!preg_match('/_bytes/', $key)) && 
                    (!preg_match('/Virus/', $key)) && (!preg_match('/Spam/', $key)) && 
                    (!preg_match('/Command rejected/', $key))) {

                    $percent = sprintf("%.2f", ($value/$delivery_global_total * 100));

                    // Prep pieChart while we're at it:
                    $messaging_status_pieChart_Data[$key] = $percent;
                    if ($percent != '0.00') {
                        $messaging_status_pieChart_Data_unlock = '1';
                    }

                    $messaging_status_table .= '                            <tr>' . "\n";
                    $messaging_status_table .= '                                <td>' . $key . '</td>' . "\n";
                    $messaging_status_table .= '                                <td>' . $value . '</td>' . "\n";
                    $messaging_status_table .= '                                <td>' . SimNum($SAR['GLOBAL_STATUS'][$key . '_bytes']) . '</td>' . "\n";
                    $messaging_status_table .= '                                <td>' . $percent . '%</td>' . "\n";
                    $messaging_status_table .= '                            </tr>' . "\n";

                }
            }

            $messaging_status_table .= '
                        </tbody>
                    </table>
                </div>';

            // Out with the messaging_status_table:
            $statsBlock->addFormField(
                $factory->getRawHTML("messaging_status_table", $messaging_status_table),
                $factory->getLabel("messaging_status_table"),
                $Global_Status
            );

            // Generate Pie Chart:
            if ((count($messaging_status_pieChart_Data) > 1) && ($messaging_status_pieChart_Data_unlock == '1')) {
                $messaging_status_pieChart = $factory->getPieChart("messaging_status_pieChart_Data", $messaging_status_pieChart_Data);
                $messaging_status_pieChart->setSize("590", "450");
                $messaging_status_pieChart->setXLabel($this->i18n->get("[[base-mailsitestats.messaging_status]]"));
                $statsBlock->addFormField(
                    $messaging_status_pieChart,
                    "",
                    $Global_Status
                );
            }

            //@//
            //@//-- SMTP Auth Tab:
            //@//

            // Prepare data:
            $authkeys_unwanted = array('x_label', 'lbls', 'values');
            $auth_mechanisms = array();
            $total_auth = '0';

            if (isset($SAR['auth']['server'])) {
                foreach ($SAR['auth']['server'] as $key => $value) {
                    if (!in_array($key, $authkeys_unwanted)) {
                        $auth_mechanisms[$key] = $value;
                        $total_auth += $value;
                    }
                }
            }

            //
            //--- 'SMTP Auth: server' table:
            //

            $smtp_auth_server_table = '
                <div class="box grid_16">
                    <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.smtpauth_server]]") . '</h2>
                    <table class="static">
                        <thead>
                            <tr>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.mechanism]]") . '</b></th>
                                <th><b>' . $this->i18n->get("[[base-mailsitestats.count]]") . '</b></th>
                            </tr>
                        </thead>
                        <tbody>' . "\n";

            arsort($auth_mechanisms);
            foreach ($auth_mechanisms as $key => $value) {

                    // Prep pieChart while we're at it:
                    $messaging_status_pieChart_Data[$key] = $percent;

                    $smtp_auth_server_table .= '                            <tr>' . "\n";
                    $smtp_auth_server_table .= '                                <td>' . $key . '</td>' . "\n";
                    $smtp_auth_server_table .= '                                <td>' . $value . '</td>' . "\n";
                    $smtp_auth_server_table .= '                            </tr>' . "\n";

            }

            $smtp_auth_server_table .= '
                            <tr>
                                <td><b>' . $this->i18n->get("[[base-mailsitestats.total]]") . '</b></td>
                                <td><b>' . $total_auth . '</b></td>
                            </tr>
                        </tbody>
                    </table>
                </div>';

            if ((count($auth_mechanisms) == '0') && (!isset($SAR['auth']['server']))) {

                // No data:
                $smtp_auth_server_nodata = $factory->getTextField('smtp_auth_server_nodata', $no_data, 'r');
                $smtp_auth_server_nodata->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $smtp_auth_server_nodata,
                        $factory->getLabel("smtp_auth_server_nodata"),
                        $Global_SMTPAuth
                        );
            }
            else {

                // Out with the smtp_auth_server_table:
                $statsBlock->addFormField(
                    $factory->getRawHTML("smtp_auth_server_table", $smtp_auth_server_table),
                    $factory->getLabel("smtp_auth_server_table"),
                    $Global_SMTPAuth
                );

                $smtp_auth_server_seenTimes = explode(":", $SAR['auth']['server']['lbls']);
                $smtp_auth_server_valArr = explode(":", $SAR['auth']['server']['values']);
                foreach ($smtp_auth_server_valArr as $key => $value) {
                    $smtp_auth_server_Data['auth'][$key] = "'" . sprintf("%.2f", $value) . "'";
                }

                // Out with the barGraph:
                $smtp_auth_server_Data_graph = $factory->getBarGraph("smtp_auth_server_Data", $smtp_auth_server_Data, $smtp_auth_server_seenTimes);
                $smtp_auth_server_Data_graph->setPoints('#auth', FALSE);
                $smtp_auth_server_Data_graph->setSize("590", "450");
                $smtp_auth_server_Data_graph->setXLabel($this->i18n->get("[[base-mailsitestats.authentication_flow_server]]"));
                $statsBlock->addFormField(
                    $smtp_auth_server_Data_graph,
                    "",
                    $Global_SMTPAuth);
            }

            //@//
            //@//-- 'Top Senders' Tab:
            //@//

            if ((isset($SAR['topsender'])) && (isset($SAR['topsender']['domain'])) && (isset($SAR['topsender']['relay']))  && (isset($SAR['topsender']['email']))) {

                $topdomain = '';
                $top = 0;

                arsort($SAR['topsender']['domain']);
                arsort($SAR['topsender']['relay']);
                arsort($SAR['topsender']['email']);

                $i = '0';
                $top_sender_relay_Data[$this->i18n->get("[[base-mailsitestats.other]]")] = '0';
                foreach ($SAR['topsender']['relay'] as $key => $value) {
                    if (preg_match('/_empty_/', $key)) {
                        $key = '<>';
                        $SAR['topsender']['relay']['<>'] = $value;
                        unset($SAR['topsender']['relay'][$key]);
                    }
                    $t_relay[$key] = $value;
                    // Collect data for pieChart, but only the first three are of interest.
                    // Rest goes into 'Other' anyway:
                    if ($i < 3) {
                        $top_sender_relay_Data[$key] = $value;
                    }
                    else {
                        $top_sender_relay_Data[$this->i18n->get("[[base-mailsitestats.other]]")] += $value;
                    }
                    $i++;
                }
                arsort($top_sender_relay_Data);

                if (count($top_sender_relay_Data) > 1) {
                    // Generate Pie Chart:
                    $top_sender_relay_pieChart = $factory->getPieChart("top_sender_relay_pieChart", $top_sender_relay_Data);
                    $top_sender_relay_pieChart->setSize("590", "450");
                    $top_sender_relay_pieChart->setXLabel($this->i18n->get("[[base-mailsitestats.top_sender_relay]]"));
                    $statsBlock->addFormField(
                        $top_sender_relay_pieChart,
                        "",
                        $Top_Senders);
                }

                //
                //--- 'Senders Statistics (top 100)' tables:
                //
                $sender_statistics_table_one = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.top_sender_domains]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>' . $this->i18n->get("[[base-mailsitestats.top_sender_domain]]") . '</b></th>
                                    <th><b>#</b></th>
                                </tr>
                            </thead>
                            <tbody>' . "\n";
                foreach ($SAR['topsender']['domain'] as $key => $value) {
                    if (preg_match('/_empty_/', $key)) {
                        $key = '<>';
                    }
                    $sender_statistics_table_one .= '                           <tr>' . "\n";
                    $sender_statistics_table_one .= '                               <td>' . $key . '</td>' . "\n";
                    $sender_statistics_table_one .= '                               <td>' . $value . '</td>' . "\n";
                    $sender_statistics_table_one .= '                           </tr>' . "\n";
                }
                $sender_statistics_table_one .= '
                            </tbody>
                        </table>
                    </div>';

                // Out with the sender_statistics_table_one:
                $statsBlock->addFormField(
                    $factory->getRawHTML("sender_statistics_table_one", $sender_statistics_table_one),
                    $factory->getLabel("sender_statistics_table_one"),
                    $Top_Senders
                );

                $sender_statistics_table_two = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.top_sender_relays]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>' . $this->i18n->get("[[base-mailsitestats.top_sender_relay]]") .'</b></th>
                                    <th><b>#</b></th>
                                </tr>
                            </thead>
                            <tbody>' . "\n";
                foreach ($SAR['topsender']['relay'] as $key => $value) {
                    if (preg_match('/_empty_/', $key)) {
                        $key = '<>';
                    }
                    $sender_statistics_table_two .= '                           <tr>' . "\n";
                    $sender_statistics_table_two .= '                               <td>' . $key . '</td>' . "\n";
                    $sender_statistics_table_two .= '                               <td>' . $value . '</td>' . "\n";
                    $sender_statistics_table_two .= '                           </tr>' . "\n";
                }
                $sender_statistics_table_two .= '
                            </tbody>
                        </table>
                    </div>';

                // Out with the sender_statistics_table_two:
                $statsBlock->addFormField(
                    $factory->getRawHTML("sender_statistics_table_two", $sender_statistics_table_two),
                    $factory->getLabel("sender_statistics_table_two"),
                    $Top_Senders
                );

                $sender_statistics_table_three = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.top_sender_addresses]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>' . $this->i18n->get("[[base-mailsitestats.top_sender_address]]") . '</b></th>
                                    <th><b>#</b></th>
                                </tr>
                            </thead>
                            <tbody>' . "\n";
                foreach ($SAR['topsender']['email'] as $key => $value) {
                    $sender_statistics_table_three .= '                         <tr>' . "\n";
                    $sender_statistics_table_three .= '                             <td>' . $key . '</td>' . "\n";
                    $sender_statistics_table_three .= '                             <td>' . $value . '</td>' . "\n";
                    $sender_statistics_table_three .= '                         </tr>' . "\n";
                }
                $sender_statistics_table_three .= '
                            </tbody>
                        </table>
                    </div>';

                // Out with the sender_statistics_table_three:
                $statsBlock->addFormField(
                    $factory->getRawHTML("sender_statistics_table_three", $sender_statistics_table_three),
                    $factory->getLabel("sender_statistics_table_three"),
                    $Top_Senders
                );

            }
            else {
                // No data:
                $top_senders_nodata = $factory->getTextField('top_senders_nodata', $no_data, 'r');
                $top_senders_nodata->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $top_senders_nodata,
                        $factory->getLabel("top_senders_nodata"),
                        $Top_Senders
                );
            }

            //@//
            //@//-- 'Top Recipients' Tab:
            //@//

            if ((isset($SAR['toprcpt'])) && (isset($SAR['toprcpt']['domain'])) && (isset($SAR['toprcpt']['relay']))  && (isset($SAR['toprcpt']['email']))) {

                $topdomain = '';
                $top = 0;

                arsort($SAR['toprcpt']['domain']);
                arsort($SAR['toprcpt']['relay']);
                arsort($SAR['toprcpt']['email']);

                $i = '0';
                $top_recipient_relay_Data[$this->i18n->get("[[base-mailsitestats.other]]")] = '0';
                foreach ($SAR['toprcpt']['relay'] as $key => $value) {
                    if (preg_match('/_empty_/', $key)) {
                        $key = '<>';
                        $SAR['toprcpt']['relay']['<>'] = $value;
                        unset($SAR['toprcpt']['relay'][$key]);
                    }
                    $t_relay[$key] = $value;
                    // Collect data for pieChart, but only the first three are of interest.
                    // Rest goes into 'Other' anyway:
                    if ($i < 3) {
                        $top_recipient_relay_Data[$key] = $value;
                    }
                    else {
                        $top_recipient_relay_Data[$this->i18n->get("[[base-mailsitestats.other]]")] += $value;
                    }
                    $i++;
                }
                arsort($top_recipient_relay_Data);

                // Generate Pie Chart:
                if (count($top_recipient_relay_Data) > 1) {
                    $top_recipient_relay_pieChart = $factory->getPieChart("top_recipient_relay_pieChart", $top_recipient_relay_Data);
                    $top_recipient_relay_pieChart->setSize("590", "450");
                    $top_recipient_relay_pieChart->setXLabel($this->i18n->get("[[base-mailsitestats.top_recipient relay]]"));
                    $statsBlock->addFormField(
                        $top_recipient_relay_pieChart,
                        "",
                        $Top_Recipients);
                }

                //
                //--- 'Senders Statistics (top 100)' tables:
                //
                $recipient_statistics_table_one = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.top_recipient_domains]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>' . $this->i18n->get("[[base-mailsitestats.top_recipient_domain]]") .'</b></th>
                                    <th><b>#</b></th>
                                </tr>
                            </thead>
                            <tbody>' . "\n";
                foreach ($SAR['toprcpt']['domain'] as $key => $value) {
                    if (preg_match('/_empty_/', $key)) {
                        $key = '<>';
                    }
                    $recipient_statistics_table_one .= '                            <tr>' . "\n";
                    $recipient_statistics_table_one .= '                                <td>' . $key . '</td>' . "\n";
                    $recipient_statistics_table_one .= '                                <td>' . $value . '</td>' . "\n";
                    $recipient_statistics_table_one .= '                            </tr>' . "\n";
                }
                $recipient_statistics_table_one .= '
                            </tbody>
                        </table>
                    </div>';

                // Out with the recipient_statistics_table_one:
                $statsBlock->addFormField(
                    $factory->getRawHTML("recipient_statistics_table_one", $recipient_statistics_table_one),
                    $factory->getLabel("recipient_statistics_table_one"),
                    $Top_Recipients
                );

                $recipient_statistics_table_two = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.top_recipient_relays]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>' . $this->i18n->get("[[base-mailsitestats.top_recipient_relay]]") . '</b></th>
                                    <th><b>#</b></th>
                                </tr>
                            </thead>
                            <tbody>' . "\n";
                foreach ($SAR['toprcpt']['relay'] as $key => $value) {
                    if (preg_match('/_empty_/', $key)) {
                        $key = '<>';
                    }
                    $recipient_statistics_table_two .= '                            <tr>' . "\n";
                    $recipient_statistics_table_two .= '                                <td>' . $key . '</td>' . "\n";
                    $recipient_statistics_table_two .= '                                <td>' . $value . '</td>' . "\n";
                    $recipient_statistics_table_two .= '                            </tr>' . "\n";
                }
                $recipient_statistics_table_two .= '
                            </tbody>
                        </table>
                    </div>';

                // Out with the recipient_statistics_table_two:
                $statsBlock->addFormField(
                    $factory->getRawHTML("recipient_statistics_table_two", $recipient_statistics_table_two),
                    $factory->getLabel("recipient_statistics_table_two"),
                    $Top_Recipients
                );

                $recipient_statistics_table_three = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.top_recipient_addresses]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>' . $this->i18n->get("[[base-mailsitestats.top_recipient_address]]") . '</b></th>
                                    <th><b>#</b></th>
                                </tr>
                            </thead>
                            <tbody>' . "\n";
                foreach ($SAR['toprcpt']['email'] as $key => $value) {
                    $recipient_statistics_table_three .= '                          <tr>' . "\n";
                    $recipient_statistics_table_three .= '                              <td>' . $key . '</td>' . "\n";
                    $recipient_statistics_table_three .= '                              <td>' . $value . '</td>' . "\n";
                    $recipient_statistics_table_three .= '                          </tr>' . "\n";
                }
                $recipient_statistics_table_three .= '
                            </tbody>
                        </table>
                    </div>';

                // Out with the recipient_statistics_table_three:
                $statsBlock->addFormField(
                    $factory->getRawHTML("recipient_statistics_table_three", $recipient_statistics_table_three),
                    $factory->getLabel("recipient_statistics_table_three"),
                    $Top_Recipients
                );

            }
            else {
                // No data:
                $top_recipients_nodata = $factory->getTextField('top_recipients_nodata', $no_data, 'r');
                $top_recipients_nodata->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $top_recipients_nodata,
                        $factory->getLabel("top_recipients_nodata"),
                        $Top_Recipients
                );
            }

            //@//
            //@//-- 'Top Spamming' Tab:
            //@//

            $spam_ids = array(
                    'rule' => $this->i18n->get("[[base-mailsitestats.lbl_top_spam_rules]]"),
                    'domain' => $this->i18n->get("[[base-mailsitestats.lbl_top_spammers_domain]]"), 
                    'sender_relay' => $this->i18n->get("[[base-mailsitestats.lbl_top_spammers_relay]]"), 
                    'sender' => $this->i18n->get("[[base-mailsitestats.lbl_top_spammers_address]]"), 
                    'rcpt' => $this->i18n->get("[[base-mailsitestats.lbl_top_recipients_address]]"), 
                    );

            if ((isset($SAR['topspam'])) && (count($SAR['topspam'] > 0))) {
                foreach ($SAR['topspam'] as $key => $value) {
                    arsort($SAR['topspam'][$key]);
                }
                foreach ($spam_ids as $key => $label) {

                    //
                    //--- Tables:
                    //
                    $spam_statistics_table_[$key] = '
                        <div class="box grid_16">
                            <h2 class="box_head">' . $label . '</h2>
                            <table class="static">
                                <thead>
                                    <tr>
                                        <th><b>' . $label . '</b></th>
                                        <th><b>#</b></th>
                                    </tr>
                                </thead>
                                <tbody>' . "\n";
                    foreach ($SAR['topspam'][$key] as $tkey => $tvalue) {
                        if (preg_match('/_empty_/', $tkey)) {
                            $tkey = '<>';
                        }
                        $spam_statistics_table_[$key] .= '                          <tr>' . "\n";
                        $spam_statistics_table_[$key] .= '                              <td>' . $tkey . '</td>' . "\n";
                        $spam_statistics_table_[$key] .= '                              <td>' . $tvalue . '</td>' . "\n";
                        $spam_statistics_table_[$key] .= '                          </tr>' . "\n";
                    }
                    $spam_statistics_table_[$key] .= '
                                </tbody>
                            </table>
                        </div>';

                    // Out with the recipient_statistics_table_one:
                    $statsBlock->addFormField(
                        $factory->getRawHTML("spam_statistics_table_$key", $spam_statistics_table_[$key]),
                        $factory->getLabel("spam_statistics_table_$key"),
                        $Top_Spamming
                    );
                }
            }
            else {
                // No data:
                $top_spam_nodata = $factory->getTextField('top_spam_nodata', $no_data, 'r');
                $top_spam_nodata->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $top_spam_nodata,
                        $factory->getLabel("top_spam_nodata"),
                        $Top_Spamming
                );
            }

            //@//
            //@//-- 'Top Virus' Tab:
            //@//

            $tvirus_nodata = $factory->getTextField('tvirus_nodata', $no_data, 'r');
            $tvirus_nodata->setLabelType("nolabel no_lines");
            $statsBlock->addFormField(
                    $tvirus_nodata,
                    $factory->getLabel("tvirus_nodata"),
                    $Top_Virus
                    );

            //@//
            //@//-- 'Top Notification' Tab:
            //@//

            $dsn_ids = array(
                    'dsnstatus' => $this->i18n->get("[[base-mailsitestats.lbl_top_dsn_status]]"),
                    'sender' => $this->i18n->get("[[base-mailsitestats.lbl_top_dsn_senders]]"), 
                    'relay' => $this->i18n->get("[[base-mailsitestats.lbl_top_dsn_relays]]"), 
                    'rcpt' => $this->i18n->get("[[base-mailsitestats.lbl_top_dsn_recipients]]")
                    );

            if ((isset($SAR['topdsn'])) && (count($SAR['topdsn'] > 0))) {
                foreach ($SAR['topdsn'] as $key => $value) {
                    arsort($SAR['topdsn'][$key]);
                }
                foreach ($dsn_ids as $key => $label) {

                    //
                    //--- Tables:
                    //
                    $dsn_statistics_table_[$key] = '
                        <div class="box grid_16">
                            <h2 class="box_head">' . $label . '</h2>
                            <table class="static">
                                <thead>
                                    <tr>
                                        <th><b>' . $label . '</b></th>
                                        <th><b>#</b></th>
                                    </tr>
                                </thead>
                                <tbody>' . "\n";
                    foreach ($SAR['topdsn'][$key] as $tkey => $tvalue) {
                        if (preg_match('/_empty_/', $tkey)) {
                            $tkey = '<>';
                        }
                        $dsn_statistics_table_[$key] .= '                           <tr>' . "\n";
                        $dsn_statistics_table_[$key] .= '                               <td>' . $tkey . '</td>' . "\n";
                        $dsn_statistics_table_[$key] .= '                               <td>' . $tvalue . '</td>' . "\n";
                        $dsn_statistics_table_[$key] .= '                           </tr>' . "\n";
                    }
                    $dsn_statistics_table_[$key] .= '
                                </tbody>
                            </table>
                        </div>';

                    // Out with the recipient_statistics_table_one:
                    $statsBlock->addFormField(
                        $factory->getRawHTML("dsn_statistics_table_$key", $dsn_statistics_table_[$key]),
                        $factory->getLabel("dsn_statistics_table_$key"),
                        $Top_Notification
                    );
                }
            }
            else {
                // No data:
                $top_dsn_nodata = $factory->getTextField('top_dsn_nodata', $no_data, 'r');
                $top_dsn_nodata->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $top_dsn_nodata,
                        $factory->getLabel("top_dsn_nodata"),
                        $Top_Notification
                );
            }

            //@//
            //@//-- 'Top Rejection' Tab:
            //@//

            $reject_ids = array(
                    'rule' => $this->i18n->get("[[base-mailsitestats.lbl_top_rules]]"),
                    'domain' => $this->i18n->get("[[base-mailsitestats.lbl_top_domains]]"), 
                    'relay' => $this->i18n->get("[[base-mailsitestats.lbl_top_relays]]"), 
                    'sender' => $this->i18n->get("[[base-mailsitestats.lbl_top_senders]]"),
                    'chck_status' => $this->i18n->get("[[base-mailsitestats.lbl_top_status]]")
                    );

            $top_dsn_nodata_shown = '0';

            if ((isset($SAR['topreject'])) && (count($SAR['topreject'] > 0))) {
                $top_dsn_nodata_shown = '1';
                foreach ($SAR['topreject'] as $key => $value) {
                    arsort($SAR['topreject'][$key]);
                }
                foreach ($reject_ids as $key => $label) {

                    //
                    //--- Tables:
                    //
                    $reject_statistics_table_[$key] = '
                        <div class="box grid_16">
                            <h2 class="box_head">' . $label . '</h2>
                            <table class="static">
                                <thead>
                                    <tr>
                                        <th><b>' . $label . '</b></th>
                                        <th><b>#</b></th>
                                    </tr>
                                </thead>
                                <tbody>' . "\n";
                    foreach ($SAR['topreject'][$key] as $tkey => $tvalue) {
                        if (preg_match('/_empty_/', $tkey)) {
                            $tkey = '<>';
                        }
                        $reject_statistics_table_[$key] .= '                            <tr>' . "\n";
                        $reject_statistics_table_[$key] .= '                                <td>' . $tkey . '</td>' . "\n";
                        $reject_statistics_table_[$key] .= '                                <td>' . $tvalue . '</td>' . "\n";
                        $reject_statistics_table_[$key] .= '                            </tr>' . "\n";
                    }
                    $reject_statistics_table_[$key] .= '
                                </tbody>
                            </table>
                        </div>';

                    // Out with the recipient_statistics_table_one:
                    $statsBlock->addFormField(
                        $factory->getRawHTML("reject_statistics_table_$key", $reject_statistics_table_[$key]),
                        $factory->getLabel("reject_statistics_table_$key"),
                        $Top_Rejection
                    );
                }
            }

            // Now add 'toperr' as well:
            if ((isset($SAR['toperr'])) && (count($SAR['toperr'] > 0))) {
                arsort($SAR['toperr']);
                $top_dsn_nodata_shown = '1';

                //
                //--- Table:
                //
                $toperr_statistics_table = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.system_messages]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>' . $this->i18n->get("[[base-mailsitestats.system_messages]]") . '</b></th>
                                    <th><b>#</b></th>
                                </tr>
                            </thead>
                            <tbody>' . "\n";
                foreach ($SAR['toperr'] as $tkey => $tvalue) {
                    if (preg_match('/_empty_/', $tkey)) {
                        $tkey = '<>';
                    }
                    $toperr_statistics_table .= '                           <tr>' . "\n";
                    $toperr_statistics_table .= '                               <td>' . $tkey . '</td>' . "\n";
                    $toperr_statistics_table .= '                               <td>' . $tvalue . '</td>' . "\n";
                    $toperr_statistics_table .= '                           </tr>' . "\n";
                }
                $toperr_statistics_table .= '
                            </tbody>
                        </table>
                    </div>';

                // Out with the toperr_statistics_table:
                $statsBlock->addFormField(
                    $factory->getRawHTML("toperr_statistics_table", $toperr_statistics_table),
                    $factory->getLabel("toperr_statistics_table"),
                    $Top_Rejection
                );
            }
            if ($top_dsn_nodata_shown != '1') {
                // No data:
                $top_reject_nodata_system = $factory->getTextField('top_reject_nodata_system', $no_data, 'r');
                $top_reject_nodata_system->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $top_reject_nodata_system,
                        $factory->getLabel("top_reject_nodata_system"),
                        $Top_Rejection
                );
            }

            //@//
            //@//-- 'Top SMTP-Auth' Tab:
            //@//

            $top_smtpAuth_ids = array(
                    'mech' => $this->i18n->get("[[base-mailsitestats.lbl_top_mechanism]]"),
                    'relay' => $this->i18n->get("[[base-mailsitestats.lbl_top_relay]]"), 
                    'authid' => $this->i18n->get("[[base-mailsitestats.lbl_top_AUTHID]]") 
                    );

            if ((isset($SAR['topauth'])) && (count($SAR['topauth'] > 0))) {
                foreach ($SAR['topauth'] as $key => $value) {
                    arsort($SAR['topauth'][$key]);
                }
                foreach ($top_smtpAuth_ids as $key => $label) {

                    //
                    //--- Tables:
                    //
                    $topauth_statistics_table_[$key] = '
                        <div class="box grid_16">
                            <h2 class="box_head">' . $label . '</h2>
                            <table class="static">
                                <thead>
                                    <tr>
                                        <th><b>' . $label . '</b></th>
                                        <th><b>#</b></th>
                                    </tr>
                                </thead>
                                <tbody>' . "\n";
                    foreach ($SAR['topauth'][$key] as $tkey => $tvalue) {
                        if (preg_match('/_empty_/', $tkey)) {
                            $tkey = '<>';
                        }
                        $topauth_statistics_table_[$key] .= '                           <tr>' . "\n";
                        $topauth_statistics_table_[$key] .= '                               <td>' . $tkey . '</td>' . "\n";
                        $topauth_statistics_table_[$key] .= '                               <td>' . $tvalue . '</td>' . "\n";
                        $topauth_statistics_table_[$key] .= '                           </tr>' . "\n";
                    }
                    $topauth_statistics_table_[$key] .= '
                                </tbody>
                            </table>
                        </div>';

                    // Out with the recipient_statistics_table_one:
                    $statsBlock->addFormField(
                        $factory->getRawHTML("topauth_statistics_table_$key", $topauth_statistics_table_[$key]),
                        $factory->getLabel("topauth_statistics_table_$key"),
                        $Top_SMTPAuth
                    );
                }
            }
            else {
                // No data:
                $topauth_nodata_system = $factory->getTextField('topauth_nodata_system', $no_data, 'r');
                $topauth_nodata_system->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $topauth_nodata_system,
                        $factory->getLabel("topauth_nodata_system"),
                        $Top_SMTPAuth
                );
            }

            //@//
            //@//-- 'SpamAssassin' Tab:
            //@//

            $top_smtpAuth_ids = array(
                    'rule' => $this->i18n->get("[[base-mailsitestats.lbl_topspam_spams]]"),
                    'score' => $this->i18n->get("[[base-mailsitestats.lbl_topspam_scores]]"), 
                    'autolearn' => $this->i18n->get("[[base-mailsitestats.lbl_topspam_autolearnstats]]")
                    );

            $individual_rules = array();

            if ((isset($SAR['topspamdetail'])) && (isset($SAR['topspamdetail']['spamdmilter'])) 
                && (count($SAR['topspamdetail'] > 0)) && (count($SAR['topspamdetail']['spamdmilter'] > 0))) {
                foreach ($SAR['topspamdetail']['spamdmilter'] as $key => $value) {
                    if ($key == 'score') {
                        krsort($SAR['topspamdetail']['spamdmilter'][$key]);
                    }
                    else {
                        arsort($SAR['topspamdetail']['spamdmilter'][$key]);
                    }
                }
                foreach ($top_smtpAuth_ids as $key => $label) {

                    //
                    //--- Tables:
                    //
                    $topspam_statistics_table_[$key] = '
                        <div class="box grid_16">
                            <h2 class="box_head">' . $label . '</h2>
                            <table class="static">
                                <thead>
                                    <tr>
                                        <th><b>' . $label . '</b></th>
                                        <th><b>#</b></th>
                                    </tr>
                                </thead>
                                <tbody>' . "\n";
                    foreach ($SAR['topspamdetail']['spamdmilter'][$key] as $tkey => $tvalue) {
                        if (preg_match('/_empty_/', $tkey)) {
                            $tkey = '<>';
                        }
                        $topspam_statistics_table_[$key] .= '                           <tr>' . "\n";
                        $tkey = preg_replace('/\\\n\\\t/', '', $tkey);
                        $tkey = preg_replace('/autolearn=[no|spam|yes]/', '', $tkey);
                        if ($key == 'rule') {
                            $tkeywrapped = word_wrap($tkey, 75);
                            $tkeywrappedbred = str_replace("\n","<br>", $tkeywrapped);
                            $topspam_statistics_table_[$key] .= '                               <td><a href="javascript:void(0)" class="tooltip hover" title="' . $tkeywrappedbred . '">' . stringshortener($tkey, '60') . '</a></td>' . "\n";

                            // While we are here, we also break it down into statistics for individual rules
                            // that got fired. This needs some computing, but will be worthwhile:
                            $tmprule = explode(',', $tkey);
                            foreach ($tmprule as $tmpkey => $tmpvalue) {
                                $tmpvalue = preg_replace('/\\\n\\\t/', '', $tmpvalue);
                                $tmpvalue = preg_replace('/autolearn=[no|spam|yes]/', '', $tmpvalue);
                                if ($tmpvalue != '') {
                                    if (!isset($individual_rules[$tmpvalue])) {
                                        $individual_rules[$tmpvalue] = '';
                                    }
                                    $individual_rules[$tmpvalue] += $tvalue;
                                }
                            }
                        }
                        else {
                            $topspam_statistics_table_[$key] .= '                               <td>' . $tkey . '</td>' . "\n";
                        }
                        $topspam_statistics_table_[$key] .= '                               <td>' . $tvalue . '</td>' . "\n";
                        $topspam_statistics_table_[$key] .= '                           </tr>' . "\n";
                    }
                    $topspam_statistics_table_[$key] .= '
                                </tbody>
                            </table>
                        </div>';

                    // Out with the recipient_statistics_table_one:
                    $statsBlock->addFormField(
                        $factory->getRawHTML("topspam_statistics_table_$key", $topspam_statistics_table_[$key]),
                        $factory->getLabel("topspam_statistics_table_$key"),
                        $AV_SPAMdMilter
                    );
                }

                //
                //-- Detailed SPAM rule table:
                //

                arsort($individual_rules);
                $topspamrule_statistics_table = '
                    <div class="box grid_16">
                        <h2 class="box_head">' . $this->i18n->get("[[base-mailsitestats.top_individual_spam_rules]]") . '</h2>
                        <table class="static">
                            <thead>
                                <tr>
                                    <th><b>' . $this->i18n->get("[[base-mailsitestats.top_individual_spam_rules]]") . '</b></th>
                                    <th><b>#</b></th>
                                </tr>
                            </thead>
                            <tbody>' . "\n";
                foreach ($individual_rules as $tkey => $tvalue) {
                    if (preg_match('/_empty_/', $tkey)) {
                        $tkey = '<>';
                    }
                    $topspamrule_statistics_table .= '                          <tr>' . "\n";
                    $topspamrule_statistics_table .= '                              <td>' . $tkey . '</td>' . "\n";
                    $topspamrule_statistics_table .= '                              <td>' . $tvalue . '</td>' . "\n";
                    $topspamrule_statistics_table .= '                          </tr>' . "\n";
                }
                $topspamrule_statistics_table .= '
                            </tbody>
                        </table>
                    </div>';

                // Out with the recipient_statistics_table_one:
                $statsBlock->addFormField(
                    $factory->getRawHTML("topspamrule_statistics_table", $topspamrule_statistics_table),
                    $factory->getLabel("topspamrule_statistics_table"),
                    $AV_SPAMdMilter
                );

            }
            else {
                // No data:
                $topspam_nodata_system = $factory->getTextField('topspam_nodata_system', $no_data, 'r');
                $topspam_nodata_system->setLabelType("nolabel no_lines");
                $statsBlock->addFormField(
                        $topspam_nodata_system,
                        $factory->getLabel("topspam_nodata_system"),
                        $AV_SPAMdMilter
                );
            }
        }

        //--- Finalize the page:
        $page_body[] = $block->toHtml();

        //
        //--- AutoFeatures (via Extensions 'Email.Stats'): 
        //

        // Figure out which services are available
        list($vsiteServices) = $CI->cceClient->find('VsiteServices');
        $autoFeatures = new AutoFeatures($CI->serverScriptHelper);
        $EmailStats_ID = 'EmailStats';
        $EmailStats =& $factory->getPagedBlock("EmailStats", array($EmailStats_ID));

        // add all generic enabled/disabled type services detected above
        $autoFeatures->display($EmailStats, 'Email.Stats', 
                array(
                    'CCE_SERVICES_OID' => $vsiteServices,
                    'PAGE_ID' => $EmailStats_ID,
                    'GROUP' => $group,
                    'YEAR' => $this->getYear(),
                    'MONTH' => $this->getMonth(),
                    'DAY' => $this->getDay(),
                    'CAN_ADD_PAGE' => TRUE
                    ));

        // Only print anything if AutoFeatures has added FormFields to $EmailStats.
        // Because if there are no FormFields in it, then there were no AutoFeatures.
        if (isset($EmailStats->formFields)) {
            if (count($EmailStats->formFields) > "0") {
                $page_body[] = "<p>&nbsp;</p>\n";
                $page_body[] = $EmailStats->toHtml();
            }
        }

        //
        //--- End AutoFeatures
        //

        $page_body[] = "<p>&nbsp;</p>\n";
        if (isset($SAR['GLOBAL_STATUS'])) {
            $page_body[] = $statsBlock->toHtml(). "\n";
        }
        else {
            $page_body[] = "<p>&nbsp;&nbsp;&nbsp;$no_data</p>\n";
        }
                    
        // Out with the page:
        $BxPage->render($page_module, $page_body);

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