<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rsscron extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /swupdate/rsscron.
     *
     */

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');
        $MX =& get_instance();

        // Load CodeIgniter file helper:
        $CI->load->helper('file');

        // Load CodeIgniter directory helper:
        $CI->load->helper('directory');

        // locale and charset setup:
        $ini_langs = initialize_languages(FALSE);
        $locale = $ini_langs['locale'];
        $localization = $ini_langs['localization'];
        $charset = $ini_langs['charset'];
        $domain = 'palette';

        // Set headers:
        $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
        $this->output->set_header("Cache-Control: post-check=0, pre-check=0");
        $this->output->set_header("Pragma: no-cache"); 
        $this->output->set_header("Content-language: $localization");
        $this->output->set_header("Content-type: text/html; charset=$charset");

        $title = "BlueOnyx";

        // Prepare page:
        $data_head = array(
            'charset' => $charset,
            'page_title' => $title,
            'layout' => "layout_fixed.css",
            'extra_headers' => "",
            'overlay' => ""
        );

        //
        // Pull RSS:
        //

        // Location (URL) of the RSS feed:
        $rsslocation = 'http://www.blueonyx.it/index.php?mact=CGFeedMaker,cntnt01,default,0&cntnt01feed=BlueOnyx-News&cntnt01showtemplate=false';

        // Check if we are online:
        if (areWeOnline($rsslocation, "5")) {
            $online = "1";
        }
        else {
           $online = "0";
        }

        if ($online == "1") {
            // Process the RSS feed:
            $news = getRssfeed($rsslocation,"BlueOnyx News","auto",50,3);
        }

        if (!isset($news)) {
            $news = array();
            $text = "Unable to update the RSS news feed chache.";
        }
        else {
            $cache_dir = '/usr/sausalito/license';
            if (is_dir($cache_dir)) {
                $rss_cache_file = $cache_dir . "/rss-news.cache";

                // Create an Array with the cache content:
                $cache_data['time'] = time();
                $cache_data['rss'] = $news;

                // Json encode the array:
                $cache_content = json_encode($cache_data);

                // Write the new cache file out to disk:
                if (write_file($rss_cache_file, $cache_content)) {
                    $text = "RSS News Feed Cache updated.";
                }
                else {
                    $text = "Unable to update the RSS news feed chache.";
                }
            }
        }

        $page_body = '
            <div id="pjax">
                    <div id="wrapper">
                        <div class="isolate">
                            <div class="center">
                                <div class="main_container full_size container_16 clearfix">
                                    <div class="box grid_16 tabs">
                                        <ul class="tab_header clearfix">
                                            <li><a href="#tabs-1">' . $title . '</a></li>
                                        </ul>
                                        <div class="controls">
                                            <a href="#" class="toggle"></a>
                                        </div>
                                        <div class="toggle_container">
                                            <div id="tabs-1" class="block">
                                                <div class="section">
                                                    
                                                    <h1>' . $title . '</h1>
                                                    <p>' . $text . '</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a id="login_logo" href="/gui/"><span>
                                    BlueOnyx
                                </span></a>
                            </div>
                        </div>
                    <div class="display_none">
            </div>';

        $data_body = array(
            'page_body' => $page_body
        );

        $data_foot = array(
        );

        // Set Localization:
        $data_head['localization'] = $localization;
        $data_head['bx_css'] = '';

        // Show the HTML Page:
        $this->load->view('neutral_header_view', $data_head);
        $this->load->view('gui_view', $data_body);
        $this->load->view('neutral_footer_view', $data_foot);
    }

}
/*
Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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