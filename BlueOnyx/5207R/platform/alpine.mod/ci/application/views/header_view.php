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
	<script src="/gui/pluginsmin?update"></script>
	<script src="/.adm/scripts/adminica/adminica_all-min.js"></script>
	<script src="/.adm/scripts/adminica/adminica_mobile-min.js"></script>
	<script src="/.adm/scripts/overlay/jquery.popupoverlay-min.js"></script>

	<!-- Start: Overrides for Adminica functions:-->
	<script src="/gui/validation?update"></script>
	<!-- End: Overrides for Adminica functions:-->	

<!-- Start: Stylesheet for custom modifications by server owner -->
    <link rel="stylesheet" href="/.adm/styles/customer/customer.css" >
<!-- Stop: Stylesheet for custom modifications by server owner -->

<!-- Extra headers: Start -->
<?php echo $extra_headers; ?>

<!-- Extra headers: End -->

	</head>

<?php echo $body_open_tag; ?>

<!-- Start: Wait overlay -->
<?php echo $overlay; ?>
<!-- End: Wait overlay -->

<!-- End: framework_head.php -->
        <div id="pjax">
		<!-- The next line specifies the active menu elements in the top and side menu: -->
                <div id="wrapper" data-adminica-nav-top="<?php echo $active_top_menu; ?>"<?php echo $active_nav_inner_entry; ?>data-adminica-side-top="<?php echo $active_side_menu; ?>"<?php echo $active_side_menu_entry; ?>>

<!-- Start: Topbar Code (pulled in): -->
<div id="topbar" class="clearfix">

        <a href="/gui" class="logo"><span>BlueOnyx</span></a>

        <div class="user_box dark_box clearfix">
                <img src="/.adm/images/interface/contact.png" width="55" alt="Profile Pic" />
                <h3><?php echo gethostname(); ?></h3>
                <h2><?php echo $loginName; ?></h2>
                <h3><a class="text_shadow" href="<?php echo $profile_link; ?>"><?php echo $fullName; ?></a></h3>
        </div><!-- #user_box -->
</div><!-- #topbar -->

<!-- End: Topbar Code (pulled in): -->
<!-- Start: Sidebar Code (pulled in): -->
<div id="sidebar" class="sidebar pjax_links">
        <div class="cog">+</div>

        <a href="/gui" class="logo"><span>BlueOnyx</span></a>

        <div class="user_box dark_box clearfix">
                <img src="/.adm/images/interface/contact.png" width="55" alt="Profile Pic" />
        		<h3><?php echo gethostname(); ?></h3>
                <h2><?php echo $loginName; ?></h2>
                <h3><a href="<?php echo $profile_link; ?>"><?php echo $fullName; ?></a></h3>
        </div><!-- #user_box -->

        <ul class="side_accordion" id="nav_side"> <!-- add class 'open_multiple' to change to from accordion to toggles -->
<!-- Start: Insert vertical menu here-->
<?php echo $side_html_menu; ?>
<!-- End: Insert vertical menu here-->
        </ul>
</div><!-- #sidebar -->
<!-- End: Sidebar Code (pulled in): -->
<!-- Start: Stackbar Code (pulled in): -->
<div id="stackbar" class="stackbar">    <div class="user_box dark_box clearfix">
                <img src="/.adm/images/interface/contact.png" width="55" alt="Profile Pic" />
                <h3><?php echo gethostname(); ?></h3>
                <h2><?php echo $loginName; ?></h2>
                <h3><a href="<?php echo $profile_link; ?>"><?php echo $profile_text; ?></a></h3>
        </div>
        <ul class="">
<!-- Start: Insert vertical menu here-->
<?php echo $side_html_menu; ?>
<!-- End: Insert vertical menu here-->
        </ul>
</div><!-- Closing Div for Stack Nav, you can boxes under the stack before this -->
<!-- End: Stackbar Code (pulled in): -->

                        <div id="main_container" class="main_container container_16 clearfix">
<!-- Start: Navigation Code (pulled in): -->
<div id="nav_top" class="dropdown_menu clearfix round_top">
        <ul class="clearfix">
<!-- Start: Insert horizontal menu here-->
<?php echo $nav_html_menu; ?>
<!-- End: Insert horizontal menu here-->
        </ul>

        <div id="mobile_nav">
                <div class="main"></div>
                <div class="side"></div>
        </div>

</div><!-- #nav_top -->
<!-- New -->
				<div class="flat_area grid_16">
					<h2><?php echo $active_page_title; ?><br>
						<small>(<?php echo $active_page_help; ?>)</small>
					</h2>
				</div>
<!-- New -->

<!-- End: Navigation Code (pulled in): -->
<!-- End: header_view.php --> 
<?php echo $debug; ?>
