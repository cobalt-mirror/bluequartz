<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class News extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /swupdate/news.
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

	    // Get $sessionId and $loginName from Cookie (if they are set):
	    $sessionId = $CI->input->cookie('sessionId');
	    $loginName = $CI->input->cookie('loginName');
	    $locale = $CI->input->cookie('locale');

	    // Line up the ducks for CCE-Connection:
	    include_once('ServerScriptHelper.php');
		$serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
		$cceClient = $serverScriptHelper->getCceClient();
		$user = $cceClient->getObject("User", array("name" => $loginName));
		$i18n = new I18n("base-yum", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// Required array setup:
		$errors = array();
		$extra_headers = array();

		// -- Actual page logic start:

		// Not 'managePackage'? Bye, bye!
		//print_rp($Capabilities->getAllowed('managePackage'));
		if (!$Capabilities->getAllowed('managePackage')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}
		else {

	    //-- Generate page:

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-yum", "/swupdate/news");
		$BxPage = $factory->getPage();
		$i18n = $factory->getI18n();

		$BxPage->setExtraHeaders('
				<script>
					$(document).ready(function() {
						$(".various").fancybox({
							overlayColor: "#000",
							fitToView	: false,
							width		: "80%",
							height		: "80%",
							autoSize	: false,
							fixed		: false,
							closeClick	: false,
							openEffect	: "none",
							closeEffect	: "none"
						});
					});
				</script>');

		$BxPage->setVerticalMenu('base_swupdate');
		$page_module = 'base_sysmanage';

			// Location (URL) of the RSS feed:
			$rsslocation = 'http://www.blueonyx.it/index.php?mact=CGFeedMaker,cntnt01,default,0&cntnt01feed=BlueOnyx-News&cntnt01showtemplate=false';

			// Check if we are online:
			if (areWeOnline($rsslocation)) {
			    $online = "1";
			}
			else {
			   $online = "0";
			   $errors[] = '<div class="alert alert_light"><img width="40" height="36" src="/.adm/images/icons/small/white/alert_2.png"><strong>' . $i18n->getHtml("[[base-yum.ErrorMSGdesc]]") . '</strong></div>';
			}

			if ($online == "1") {

			    // Process the RSS feed:
			    $news = getRssfeed($rsslocation,"BlueOnyx News","auto",50,3);

			    // News are now stored in this format:
			    //
			    // $news["_bx_title"] : Titles
			    // $news["_bx_date"]  : Date
			    // $news["_bx_desc"]  : Short description
			    // $news["_bx_link"]  : Link

			    // Can't get News for whatever reason:
			    if ($news["_bx_title"] == "n/a") {
					// Although we can establish a connection to www.blueonyx.it, the RSS feed did not return expected results:
	 				$errors[] = '<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->getHtml("[[base-yum.ErrorMSGdesc]]") . '</strong></div>';
			    }
			    else {

					// General parameters for the scroll list:

				    // Count number of news-entries:
				    $bx_num = count($news["_bx_title"]);

				    // Build multidimensional array of our news:
				    $news = array($news["_bx_title"], $news["_bx_desc"], $news["_bx_date"], $news["_bx_link"]);

				    // Loop through array $news and extract the news to populate the scroll list rows:
				    $num = "0";
				    while ($num < $bx_num) {
						// Create the image link button for the external news article URL:
						preg_match_all("/articleid=(.*)&(.*)/Uism", $news[3][$num], $article_id);
						$article = $article_id[1][0];
						$exturl = $news[3][$num];
						$news[3][$num] = '<a class="various" target="_blank" href="' . $exturl . '" data-fancybox-type="iframe">' . '<button class="fancybox tiny icon_only img_icon tooltip hover" title="' . $i18n->getWrapped("[[base-yum.openURL_help]]") .'"><img src="/.adm/images/icons/small/white/magnifying_glass.png"></button>' . '</a>';
						$linkButton = $factory->getUrlButton($exturl);
						$linkButton->setButtonSite("tiny");
						$news[4][$num] = $linkButton->toHtml();
						$num++;
				    }
				}
			}
		}

		if (!isset($news)) {
			$news = array();
		}

		$scrollList = $factory->getScrollList("TheNews", array("title", "desc", "date", "internal", 'link'), $news); 
	    $scrollList->setAlignments(array("left", "left", "center", "right", "right"));
	    $scrollList->setDefaultSortedIndex('2');
	    $scrollList->setSortOrder('descending');
	    $scrollList->setSortDisabled(array('3', '4'));
	    $scrollList->setPaginateDisabled(FALSE);
	    $scrollList->setSearchDisabled(FALSE);
	    $scrollList->setSelectorDisabled(FALSE);
	    $scrollList->enableAutoWidth(FALSE);
	    $scrollList->setInfoDisabled(FALSE);
	    $scrollList->setColumnWidths(array("150", "75%", "100", "35", "35"));

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		$page_body[] = $scrollList->toHtml();

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