<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fullcalendar extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     *      http://example.com/index.php/fullcalendar
     *  - or -  
     *      http://example.com/index.php/fullcalendar/index
     *  - or -
     *      http://example.com/fullcalendar/
     *
     * This dynamically creates the fullcalendar.js code needed for
     * the full calendar plugin. 
     *
     * We just substitute our locale string for the English days and 
     * months. The rest is stock.
     *
     */

    public function index() {

        $CI =& get_instance();
        
        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Start sane:
        $locale = "en_US";
        $charset = "UTF-8";

        // Get $sessionId and $loginName from Cookie (if they are set):
        $sessionId = $CI->input->cookie('sessionId');
        $loginName = $CI->input->cookie('loginName');
        $locale = $CI->input->cookie('locale');

        if (!isset($locale)) {
            $locale = 'en_US';
        }
        if ($locale == '') {
            $locale = 'en_US';
        }

        $data = array();
        $i18n = new I18n("palette", $locale);

        // Set headers (And yes, we don't force a cache reload):
        $CI->output->set_header("Content-language: $locale");
        $CI->output->set_header("Content-type: text/html; charset=$charset");

        // Assemble the data:
        $january = $i18n->getHtml("[[palette.01month]]");
        $february = $i18n->getHtml("[[palette.02month]]");
        $march = $i18n->getHtml("[[palette.03month]]");
        $april = $i18n->getHtml("[[palette.04month]]");
        $may = $i18n->getHtml("[[palette.05month]]");
        $june = $i18n->getHtml("[[palette.06month]]");
        $july = $i18n->getHtml("[[palette.07month]]");
        $august = $i18n->getHtml("[[palette.08month]]");
        $september = $i18n->getHtml("[[palette.09month]]");
        $october = $i18n->getHtml("[[palette.10month]]");               
        $november = $i18n->getHtml("[[palette.11month]]");
        $december = $i18n->getHtml("[[palette.12month]]");

        $data['monthNames'] = "monthNames: ['$january','$february','$march','$april','$may','$june','$july','$august','$september','$october','$november','$december'],\n";

        $jan = $i18n->getHtml("[[palette.01month_short]]");
        $feb = $i18n->getHtml("[[palette.02month_short]]");
        $mar = $i18n->getHtml("[[palette.03month_short]]");
        $apr = $i18n->getHtml("[[palette.04month_short]]");
        $may_short = $i18n->getHtml("[[palette.05month_short]]");
        $jun = $i18n->getHtml("[[palette.06month_short]]");
        $jul = $i18n->getHtml("[[palette.07month_short]]");
        $aug = $i18n->getHtml("[[palette.08month_short]]");
        $sep = $i18n->getHtml("[[palette.09month_short]]");
        $oct = $i18n->getHtml("[[palette.10month_short]]");             
        $nov = $i18n->getHtml("[[palette.11month_short]]");
        $dec = $i18n->getHtml("[[palette.12month_short]]");

        $data['monthNamesShort'] = "monthNamesShort: ['$jan','$feb','$mar','$apr','$may_short','$jun','$jul','$aug','$sep','$oct','$nov','$dec'],\n";

        $monday = $i18n->getHtml("[[palette.monday]]");
        $tuesday = $i18n->getHtml("[[palette.tuesday]]");
        $wednesday = $i18n->getHtml("[[palette.wednesday]]");
        $thursday = $i18n->getHtml("[[palette.thursday]]");
        $friday = $i18n->getHtml("[[palette.friday]]");
        $saturday = $i18n->getHtml("[[palette.saturday]]");
        $sunday = $i18n->getHtml("[[palette.sunday]]");

        $data['dayNames'] = "dayNames: ['$sunday','$monday','$tuesday','$wednesday','$thursday','$friday','$saturday'],\n";

        $mon = $i18n->getHtml("[[palette.monday_short]]");
        $tue = $i18n->getHtml("[[palette.tuesday_short]]");
        $wed = $i18n->getHtml("[[palette.wednesday_short]]");
        $thu = $i18n->getHtml("[[palette.thursday_short]]");
        $fri = $i18n->getHtml("[[palette.friday_short]]");
        $sat = $i18n->getHtml("[[palette.saturday_short]]");
        $sun = $i18n->getHtml("[[palette.sunday_short]]");

        $data['dayNamesShort'] = "dayNamesShort: ['$sun','$mon','$tue','$wed','$thu','$fri','$sat'],\n";

        // Show the data:
        $this->load->view('fullcalendar_view', $data);

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