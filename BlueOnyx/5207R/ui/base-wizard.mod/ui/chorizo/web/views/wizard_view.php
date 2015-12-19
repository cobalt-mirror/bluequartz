<!DOCTYPE html>
<!--[if lt IE 7]> <html lang=<?php echo '"' . $localization . '"';?> class="no-js ie6"> <![endif]-->
<!--[if IE 7]>    <html lang=<?php echo '"' . $localization . '"';?> class="no-js ie7"> <![endif]-->
<!--[if IE 8]>    <html lang=<?php echo '"' . $localization . '"';?> class="no-js ie8"> <![endif]-->
<!--[if IE 9]>    <html lang=<?php echo '"' . $localization . '"';?> class="no-js ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang=<?php echo '"' . $localization . '"';?> class="no-js"> <!--<![endif]-->
<head>
    <meta http-equiv="content-type" content="text/html; charset=<?php echo $charset;?>">
    <meta charset="<?php echo $charset; ?>">
    <title><?php echo $page_title;?></title>

<!-- iPhone, iPad and Android specific settings -->

    <meta name="viewport" content="width=device-width; initial-scale=1; maximum-scale=1;">
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

    <link href="/.adm/images/interface/iOS_icon.png" rel="apple-touch-icon">

<!-- Styles -->
    <!--
    <link rel="stylesheet" type="text/css" href="/.adm/styles/adminica/reset.css">
    <link rel="stylesheet" type="text/css" href="/.adm/fonts/fonts.css">
    <link rel="stylesheet" type="text/css" href="/.adm/styles/plugins/all/plugins.css">
    <link rel="stylesheet" type="text/css" href="/.adm/styles/adminica/all.css">
    -->
    <link rel="stylesheet" type="text/css" href="/.adm/styles/adminica/combined-common-mini.css">
<!-- Start: Style Switcher -->
    <link rel="stylesheet" type="text/css" href="/.adm/styles/themes/layout_switcher.php?default=<?php echo $layout; ?>" >
    <link rel="stylesheet" type="text/css" href="/.adm/styles/themes/nav_switcher.php?default=switcher.css" >
    <link rel="stylesheet" type="text/css" href="/.adm/styles/themes/skin_switcher.php?default=switcher.css" >
    <link rel="stylesheet" type="text/css" href="/.adm/styles/themes/theme_switcher.php?default=theme_blue.css" >
    <link rel="stylesheet" type="text/css" href="/.adm/styles/themes/bg_switcher.php?default=bg_silver.css" >
<!-- End: Style Switcher -->    
    <link rel="stylesheet" href="/.adm/styles/adminica/colours.css"> <!-- overrides the default colour scheme -->
    <script src="/.adm/scripts/plugins-min.js"></script>
    <script src="/.adm/scripts/adminica/adminica_all-min.js"></script>
    <script src="/.adm/scripts/overlay/jquery.popupoverlay-min.js"></script>

    <!-- Start: Overrides for Adminica functions:-->
    <script src="/gui/validation?update"></script>
    <!-- End: Overrides for Adminica functions:-->  

<!-- Start: Stylesheet for custom modifications by server owner -->
    <link rel="stylesheet" href="/.adm/styles/customer/customer.css" >
<!-- Stop: Stylesheet for custom modifications by server owner -->

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

<!-- Extra headers: Start -->
<?php echo $extra_headers; ?>
<!-- Extra headers: End -->

    </head>
<body>
<!-- End: framework_head.php -->

<!-- Start: Wait overlay -->
<?php echo $overlay; ?>
<!-- End: Wait overlay -->

<!-- End: header_view.php --> 

<div id="wrapper">
    <div class="isolate">
        <div class="center">
            <div class="main_container full_size container_16 clearfix">
                <div class="box light grid_16">
                    <h2 class="box_head"><?php echo $iso_wizard_title; ?></h2>
                    <?php echo $errors; ?>
                    <div class="controls">
                        <div class="wizard_progressbar"></div>
                    </div>
                    <div class="toggle_container">
                        <div class="wizard">

                            <div class="wizard_steps">
                                <ul class="clearfix">
                                    <li class="current">
                                        <a href="#step_1" id="step_1" class="clearfix">
                                            <span>1. <strong><?php echo $step_1_title; ?></strong></span>
                                            <small><?php echo $step_1_title_sub; ?></small>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#step_2" id="step_2" class="clearfix">
                                            <span>2. <strong><?php echo $step_2_title; ?></strong></span>
                                            <small><?php echo $step_2_title_sub; ?></small>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#step_3" id="step_3" class="clearfix">
                                            <span>3. <strong><?php echo $step_3_title; ?></strong></span>
                                            <small><?php echo $step_3_title_sub; ?></small>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#step_4" id="step_4" class="clearfix">
                                            <span>4. <strong><?php echo $step_4_title; ?></strong></span>
                                            <small><?php echo $step_4_title_sub; ?></small>
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <div class="wizard_content">

                                <form action="/wizard?action=post" method="post" class="validate_form" ENCTYPE="multipart/form-data">
                                <div id="step_1" class="step block" style="display:block;">
                                    <div class="section">
                                        <?php echo $step_1; ?>
                                    </div>
                                    <div class="button_bar clearfix">
                                        <button class="next_step forward send_right" data-goto="step_2" type="button">
                                            <img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/white/bended_arrow_right.png">
                                            <span><?php echo $next; ?></span>
                                        </button>
                                    </div>
                                </div>

                                <div id="step_2" class="step block">
                                    <div class="section">
                                        <?php echo $step_2; ?>
                                    </div>

                                    <div class="button_bar clearfix">
                                        <button class="next_step back light" data-goto="step_1" type="button">
                                            <img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/grey/bended_arrow_left.png">
                                            <span><?php echo $previous; ?></span>
                                        </button>
                                        <button class="next_step forward send_right" data-goto="step_3" type="button">
                                            <img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/white/bended_arrow_right.png">
                                            <span><?php echo $next; ?></span>
                                        </button>
                                    </div>
                                </div>

                                <div id="step_3" class="step block">
                                    <div class="section">
                                        <?php echo $step_3; ?>
                                    </div>

                                    <div class="button_bar clearfix">
                                        <button class="next_step back light" data-goto="step_2" type="button">
                                            <img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/grey/bended_arrow_left.png">
                                            <span><?php echo $previous; ?></span>
                                        </button>
                                        <button class="next_step forward send_right" data-goto="step_4" type="button">
                                            <img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/white/bended_arrow_right.png">
                                            <span><?php echo $next; ?></span>
                                        </button>
                                    </div>
                                </div>

                                <div id="step_4" class="step block">
                                    <div class="section">
                                        <?php echo $step_4; ?>
                                    </div>

                                    <div class="button_bar clearfix">
                                        <button class="next_step back light" data-goto="step_3" type="button">
                                            <img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/grey/bended_arrow_left.png">
                                            <span><?php echo $previous; ?></span>
                                        </button>
                                        <button class="next_step send_right submit_button" type="button">
                                            <img height="24" width="24" alt="Bended Arrow Right" src="/.adm/images/icons/small/white/bended_arrow_right.png">
                                            <span><?php echo $done; ?></span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <a class="logo" href="http://www.blueonyx.it" target="_blank">
            <span>BlueOnyx</span>
            </a>
        </div>
    </div>
</div>
<!-- Start: footer_view.php -->

    <!-- Start: Wait overlay -->
    <!-- End: Wait overlay -->

<!-- Piwik -->
<!--
    Register with BlueOnyx: No reason to get paranoid. We just track
    the usage of the Wizard and get to know your servers IP and which
    version of BlueOnyx you are using. The Serial Number is usually 
    empty at this point and is passed along, too. Beyond this no further
    tracking is done.
-->
<script type="text/javascript">
  var _paq = _paq || [];
  _paq.push(["setCustomVariable", 1, "platform", "<?php echo $productBuild; ?>", "visit"]);
  _paq.push(["setCustomVariable", 2, "serialNumber", "<?php echo $serialNumber; ?>", "visit"]);
  _paq.push(["setCustomVariable", 3, "ipaddr", "<?php echo $ipaddr; ?>", "visit"]);
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u=(("https:" == document.location.protocol) ? "https" : "http") + "://stats.blueonyx.it/";
    _paq.push(['setTrackerUrl', u+'piwik.php']);
    _paq.push(['setSiteId', 7]);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript';
    g.defer=true; g.async=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<noscript><p><img src="http://stats.blueonyx.it/piwik.php?idsite=7" style="border:0;" alt="" /></p></noscript>
<!-- Piwik Image Tracker-->
<img src="https://stats.blueonyx.it/piwik.php?idsite=7&rec=1&platform=<?php echo $productBuild; ?>&serialNumber=<?php echo $serialNumber; ?>&ipaddr=<?php echo $ipaddr; ?>" style="border:0" alt="" />
<!-- End Piwik -->
<!-- End Piwik Code -->

        </body>
</html>
