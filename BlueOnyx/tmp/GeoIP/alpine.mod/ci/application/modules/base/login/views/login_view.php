<!doctype html public "✰">
<!--[if lt IE 7]> <html lang="en-us" class="no-js ie6"> <![endif]-->
<!--[if IE 7]>    <html lang="en-us" class="no-js ie7"> <![endif]-->
<!--[if IE 8]>    <html lang="en-us" class="no-js ie8"> <![endif]-->
<!--[if IE 9]>    <html lang="en-us" class="no-js ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="en-us" class="no-js"> <!--<![endif]-->
<head>
	<meta charset="utf-8">

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

<!-- Style Switcher -->

	<link rel="stylesheet" href="/.adm/styles/themes/layout_switcher.php?default=layout_fixed.css" >
    <link rel="stylesheet" href="/.adm/styles/themes/nav_switcher.php?default=switcher.css" >
    <link rel="stylesheet" href="/.adm/styles/themes/skin_switcher.php?default=switcher.css" >
    <link rel="stylesheet" href="/.adm/styles/themes/theme_switcher.php?default=theme_blue.css" >
    <link rel="stylesheet" href="/.adm/styles/themes/bg_switcher.php?default=bg_silver.css" >

	<link rel="stylesheet" href="/.adm/styles/adminica/colours.css"> <!-- this file overrides the theme's default colour scheme, allowing more colour combinations (see layout example page) -->
	<script src="/.adm/scripts/plugins-min.js"></script>
	<script src="/.adm/scripts/adminica/adminica_all-min.js"></script>

<!-- Start: Stylesheet for custom modifications by server owner -->
    <link rel="stylesheet" href="/.adm/styles/customer/customer.css" >
<!-- Stop: Stylesheet for custom modifications by server owner -->

	<SCRIPT language="JavaScript" type="text/javascript"><!--
	//<![CDATA[
	function focuslogin() {
	document.form.username_field.focus();
	}
	function getKey(e) {    // WaveWeb 2012
	    var key;    // return code of key pressed; e.g.:
		    // onKeyPress="if(getKey(event)==13) ...
	    if(window.event) key = window.event.keyCode;    // IE
	    else key = e.which;    // Firefox and others
	    //alert("e='"+e+"' key='"+key+"'");
	}
	//]]>
	// -->
    </SCRIPT> 

	<!-- Extra headers: Start -->
	<!-- Extra headers: End -->

	</head>
<body>

<!-- End: framework_head.php -->
        <div id="pjax">
                <div id="wrapper">
                        <div class="isolate">
                                <div class="center narrow">
                                        <div class="main_container full_size container_16 clearfix">
                                                <div class="box">

                            <img src="/.adm/images/bx/images/BlueOnyxLoginImage-<?php echo $primaryColor; ?>.gif">

<h2 class="box_head"><center><strong><?php echo $WelcomeMsg; ?></strong></center></h2>

                                                        <div class="block">
                                                                <div class="section">
<?php 
if ((validation_errors()) || ($loginMessage == $loginFailed)) {

    echo '<div class="alert dismissible alert_red">';
    echo '<img width="24" height="24" src="/.adm/images/icons/small/white/alarm_bell.png">';
    echo "<strong>$loginFailed </strong>"; 
    echo '</div>';
}
else {
    // No errors present: Show the dismissible login info text:
    echo '<div class="alert dismissible alert_light">';
    echo '<img width="24" height="24" src="/.adm/images/icons/small/grey/locked.png">';
    echo "<strong>$loginMessage</strong> ";
    echo '</div>';
}
?>

                                                                    <noscript>
                                                                        <div class="alert dismissible alert_light">
                                                                        <img width="24" height="24" src="/.adm/images/icons/small/grey/locked.png">
                                                                        <strong><?php echo $noJS ?></strong> 
                                                                    </noscript>
                                                                </div>
                                                                <!-- <form action="index.php" class="validate_form"> -->
                                                                <form action="/login<?php echo $URLaddParams ?>" method="post" accept-charset="utf-8">

                                                                <fieldset class="label_side top">
                                                                        <label for="username_field"><?php echo $Username ?></label>
                                                                        <div>
                                                                                <input type="text" id="username_field" name="username_field" class="required" onKeyPress="if(document.layers && event.which == 13 && document.form.onsubmit()) document.form.submit()" value="<?php echo set_value('username_field');?>">
                                                                        </div>
                                                                </fieldset>
                                                                <fieldset class="label_side bottom">
                                                                        <label for="password_field"><?php echo $Password ?></label>
                                                                        <div>
                                                                                <input type="password" id="password_field" name="password_field" class="required" onKeyPress="if( getKey(event)==13 && document.form.onsubmit() ) document.form.submit()" value="<?php echo set_value('password_field');?>">
                                                                        </div>
                                                                </fieldset>

                                                                <fieldset class="label_side top">
                                                                        <label for="secureConnect"><?php echo $SecureConnect ?></label>
                                                                        <div class="jqui_radios">
									    <input type="radio" name="secureConnect" id="yes" value="1" <?php echo $sc_yes_selected; ?><?php echo $url; ?>>
									    <label for="yes"><?php echo $yes ?></label>
                                                                            <input type="radio" name="secureConnect" id="no" value="0" <?php echo $sc_no_selected; ?><?php echo $url; ?>>
									    <label for="no"><?php echo $no ?></label>

                                                                        </div>
                                                                </fieldset>


                                                                <div class="button_bar clearfix">
                                                                        <button class="wide" type="submit">
                                                                                <img src="/.adm/images/icons/small/white/key_2.png">
                                                                                <span><?php echo $login_text ?></span>
                                                                        </button>
                                                                </div>
                                                                            <input type="hidden" id="redirect_target" name="redirect_target" value="<?php echo $redirect_target;?>">
                                                                </form>
                                                        </div>
                                                </div>
                                        </div>
                                        <a href="index.php" id="login_logo"><span>BlueOnyx</span></a>
                                </div>
                        </div>
<!-- Start: Static footer -->
                <div id="loading_overlay">
                        <div class="loading_message round_bottom">
                                <img src="/.adm/images/interface/loading.gif" alt="loading" />
                        </div>
                </div>
        </body>
</html>
