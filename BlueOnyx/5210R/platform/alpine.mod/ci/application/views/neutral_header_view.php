<!DOCTYPE html>
<!--[if lt IE 7]> <html lang=<?php echo '"' . $localization . '"';?> class="no-js ie6"> <![endif]-->
<!--[if IE 7]>    <html lang=<?php echo '"' . $localization . '"';?> class="no-js ie7"> <![endif]-->
<!--[if IE 8]>    <html lang=<?php echo '"' . $localization . '"';?> class="no-js ie8"> <![endif]-->
<!--[if IE 9]>    <html lang=<?php echo '"' . $localization . '"';?> class="no-js ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang=<?php echo '"' . $localization . '"';?> class="no-js"> <!--<![endif]-->
<head>
	<meta charset="<?php echo $charset; ?>">
	<meta http-equiv="content-type" content="text/html; charset=<?php echo $charset;?>">
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
	<link rel="stylesheet" href="/.adm/styles/themes/layout_switcher.php?default=<?php echo $layout; ?>" >
	<link rel="stylesheet" href="/.adm/styles/themes/nav_switcher.php?default=switcher.css" >
	<link rel="stylesheet" href="/.adm/styles/themes/skin_switcher.php?default=switcher.css" >
	<link rel="stylesheet" href="/.adm/styles/themes/theme_switcher.php?default=theme_blue.css" >
	<link rel="stylesheet" href="/.adm/styles/themes/bg_switcher.php?default=bg_silver.css" >
<!-- End: Style Switcher -->	

	<link rel="stylesheet" href="/.adm/styles/adminica/colours.css"> <!-- overrides the default colour scheme -->
	<script src="/gui/pluginsmin?update"></script>

<?php echo $bx_css; ?>

	<!-- Start: Overrides for Adminica functions:-->
	<script src="/gui/validation"></script>
	<!-- End: Overrides for Adminica functions:-->	

<!-- Start: Stylesheet for custom modifications by server owner -->
    <link rel="stylesheet" href="/.adm/styles/customer/customer.css" >
<!-- Stop: Stylesheet for custom modifications by server owner -->

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
