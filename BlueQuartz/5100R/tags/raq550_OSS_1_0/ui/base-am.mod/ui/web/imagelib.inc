<?
// $Id: imagelib.inc 259 2004-01-03 06:28:40Z shibuya $
include_once("I18n.php");

// renders a PNG image
// $background is the file that will server as the background image
// $info is an array of subimage properties
// $info = array( 'key1'  => array('locationX' => $x,
// 				   'locationY' => $y,
// 				   'imageFile' => $file),
// 	          'key2'  => array('locationX' => $x,
// 				   'locationY' => $y,
// 				   'imageFile' => $file));
// $locationX is the X coordinate to display the subimage.
// $locationY is the Y coordinate to display the subimage.
// $imageFile is the subimage to display
// All coordinates start at (0,0), which is the upper left corner of the image.
function render_image($background, $info) {
  $backgroundImg = createimage($background);
  if (!$info) {
    //render and exit;
    ImagePng($backgroundImg);
    ImageDestroy($backgroundImg);
    return;
  }
  
  // Alpha channel support needs GD 2.01
  // use transparency info when blending images
  ImageAlphaBlending($backgroundImg, true);

  foreach (array_keys($info) as $piece) {
    $imageInfo = $info[$piece];
    $imagefile = $imageInfo['imageFile'];
    $locationX = $imageInfo['locationX'];
    $locationY = $imageInfo['locationY'];

    $image = createimage($imagefile);

    ImageCopy($backgroundImg, $image, $locationX, $locationY, 
	      0, 0, ImageSX($image), ImageSY($image));
    ImageDestroy($image);
  }
  ImagePng($backgroundImg);
  ImageDestroy($backgroundImg);
  return;
}

// helper function so I don't need to pay attention to file types
function createimage($imageName) {
  if (eregi('.png$', $imageName)) {
    $image = ImageCreateFromPNG($imageName);
  } else if (eregi('.jpg$', $imageName)) {
    $image = ImageCreateFromJPEG($imageName);
  } 
  // Alpha channel support, needs GD 2.01
  // Copy image from old file to new file
  // This works around a bug in php when using images from certain files.
  $imagenew = ImageCreateTrueColor(ImageSX($image), ImageSY($image));
  // Literally copy transparency info, don't use it to blend anything.
  ImageAlphaBlending($imagenew, false);
  ImageCopy($imagenew, $image, 0, 0, 0, 0, ImageSX($image), ImageSY($image));
  ImageDestroy($image);
  return $imagenew;
} 

//info is structure
//      array ('key' => array('imageFile' => file,
//  			  'locationX' => x,
//  			  'locationY' => y,
//  			  'msgs' => array (strings)));

// creates an image map with mouseovers
// the image map will be called $name
// this image map is intended to be used with the image created by render_image();
// using the same $info
//
// $info is an array of subimage properties
// $info = array( 'key1'  => array('locationX' => $x,
// 				   'locationY' => $y,
// 				   'imageFile' => $file,
//                                 'msgs'      => array(strings)),
// 	          'key2'  => array('locationX' => $x,
// 				   'locationY' => $y,
// 				   'imageFile' => $file));
//                                 'msgs'      => array(strings)))
// All coordinates start at (0,0), which is the upper left corner of the image.
// $locationX is the X coordinate to display the subimage.
// $locationY is the Y coordinate to display the subimage.
// $imageFile is the subimage to display
// $msgs is an array of strings that will show when the user mouses over the subimage
// if $msgs is empty, then nothing will happen when you mouseover the subimage
function image_map($name, $info) {
  $result = "";
  $result = "<MAP name=\"$name\">\n";

  foreach (array_keys($info) as $piece) {
    $sectionInfo = $info[$piece];
    if ($sectionInfo['msgs']) {
      $text = implode(" ", $sectionInfo['msgs']);
      $image = createimage($sectionInfo['imageFile']);

      $result .= "<AREA ";
      $result .= "ALT=\"" . ($text) . "\" ";
      $result .= "ONMOUSEOVER=\"return top.code.info_mouseOver('" . ($text) . "')\" ";
      $result .= "HREF=\"#\"";
      $result .= "SHAPE=\"rect\" coords=\"";
      
      $result .= $sectionInfo['locationX'];
      $result .= ',' . $sectionInfo['locationY'];
      $result .= ',' . ($sectionInfo['locationX'] + ImageSX($image));
      $result .= ',' . ($sectionInfo['locationY'] + ImageSY($image));

      $result .= "\">\n";

    } 
  }
  $result .= "</MAP>\n";
  return $result;
}

// Takes two arrays, one with status information, the other with component information, and 
// generates the list of subimages to be rendered.
// The component information is a slice of the data structure that is returned by get_images_config()
// corresponding to one "image" object.
// $config = get_images_config();
// $imageconfig = $config[$an_image];

// The status array looks like this:
//  $status = array( $device1 => array( 'state' => $state,
//  				    'msgs'  => array(strings)),
//  		 $device2 => array( 'state' => $state,
//  				    'msgs'  => array(strings)));
// $state can be either a number or an AM statecode (NGYR)
// If $state is a number, the procedure is as follows:
//  1) Use the thresholds in the imageconfig to determine which state the component is in.
//  2) Use the imageFile and message string corresponding to the state.
//
// If the state is an AM statecode, the procedure is as follows:
// 1) Use the imageFile corresponding to the state.
// 2) Use the message strings passed in as the status information.
function get_render_info($status, $imageconfig) {
  $render_info = array();
  if (!$imageconfig) {
    return $render_info;
  }

  $i18n = new I18n();

  $subimages = $imageconfig['subimages'];

  foreach (array_keys($subimages) as $component) {
    $component_data = $subimages[$component];
    $component_status = $status[$component];

    // see if we're supposed to display the image
    // this is based off of the "show" property of the config file
    if ($component_data['show'] == "never") {
      continue;
    }

    // if we don't have status info
    if ($component_data['show'] == 'ifavailable' && !$component_status) {
      continue;
    }

    // parent sets the default location X and Y, or defaults to 0 if they are not present
    // these may be overridden by the states
    $locationX = $component_data['locationX']  ? $component_data['locationX'] : 0;
    $locationY = $component_data['locationY'] ? $component_data['locationY'] : 0;

    $component_state = $component_status['state'];
    // compare vs thresholds, or explicity choose state?
    if (is_numeric($component_state)) {
      foreach ($component_data['states'] as $state) {

	// display if min =< $state < max
	if (isset($state['threshold_max']) && $component_state >= $state['threshold_max']) {
	  continue;
	} 

	if (isset($state['threshold_min']) && $component_state < $state['threshold_min']) {
	  continue;
	} 

	// state "locationX" and "locationY" property override the parent subimage
	$locationX = $state['locationX'] ? $state['locationX'] : $locationX;
	$locationY = $state['locationY'] ? $state['locationY'] : $locationY;

	$data = array( 'imageFile' => $state['img'],
		       'locationX' => $locationX,
		       'locationY' => $locationY);

	if ($state['msg']) {
	  $msg = $i18n->interpolate($state['msg']);
	  $data['msgs'] = array($msg);
	}

	$render_info[$component] = $data;

	break;
      }

    } else {
      foreach ($component_data['states'] as $state) {
	if ($state['code'] == $component_state) {

	  // state "locationX" and "locationY" property override the parent subimage
	  $locationX = $state['locationX'] ? $state['locationX'] : $locationX;
	  $locationY = $state['locationY'] ? $state['locationY'] : $locationY;
	  
	  $data = array('imageFile' => $state['img'],
		       'locationX' => $locationX,
		       'locationY' => $locationY);

	  if ($component_status['msgs']) {
	    $msgs = array();
	    foreach ($component_status['msgs'] as $msg) {
	      $msgs[] = $i18n->interpolate($msg);
	    }
	    $data['msgs'] = $msgs;
	  }

	  $render_info[$component] = $data;

	  break;

	}
      }
    }
  }
  return $render_info;
}

// takes all .conf files in /usr/sausalito/swatch and parse them as XML
// see XML readme for more info
function get_images_config() {
  $files = array();
  $dirname = '/usr/sausalito/swatch';
  
  $handle = @opendir($dirname);
  if (!$handle) {
    error_log("can't open $dirname");
    exit;
  }

  while (false !== ($file = readdir($handle))) {
    if (ereg('.conf$', $file)) {
      $files[] = "$dirname/$file";
    }
  }
  closedir($handle);

  global $xml_parser_images;
  $xml_parser_images = array();

  foreach ($files as $file) {

    $xmlParser = xml_parser_create();
    xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, false);
    xml_set_element_handler($xmlParser, "start_parse", "end_parse");

    $handle = fopen($file, 'r');
    while ($data = fgets($handle, 4096)) {
      if (!xml_parse($xmlParser, $data, feof($handle))) {
	error_log(sprintf("get_images_config(): XML error in file $file: %s at line %d, col %d",
			  xml_error_string(xml_get_error_code($xmlParser)),
			  xml_get_current_line_number($xmlParser), 
			  xml_get_current_column_number($xmlParser)), 0);
	
	print "big ass error in $file!\n";
	print "on line " . xml_get_current_line_number($xmlParser) . ", col " .
	  xml_get_current_column_number($xmlParser) . "\n";
	print (xml_error_string(xml_get_error_code($xmlParser)));
      }
    }
    fclose($handle);

    xml_parser_free($xmlParser);
  }

  $ret = $xml_parser_images;
  // unset it so we don't get global namespace collisions
  unset($xml_parser_images);
  return $ret;
}


function start_parse($parser, $name, $attributes) {
  switch ($name) {
  case "image":
    image_parse($attributes);
    break;
    
  case "subimage":
    subimage_start($attributes);
    break;
    
  case "state":
    state_start($attributes);
    break;
  }
}

function end_parse($parser, $name) {
  switch ($name) {
  case "image":
    image_end();
    break;

  case "subimage":
    subimage_end();
    break;
    
  case "state":
    state_end();
    break;
  }
}



function image_parse($attributes) {
  global $thisimage;
  global $thisimagename;
  global $thisimagesystem;

  if (!isset($thisimage)) {
    $thisimage = array();
  }

  while (list($key, $val) = each($attributes)) {
    if ($key == "name") {
      $thisimagename = $val;
    } else if ($key == "system") {
      $thisimagesystem = $val;
    } else {
      $thisimage[$key] = $val;
    }
  }
}

function image_end() {
  global $xml_parser_images;
  global $thisimagename;
  global $thisimage;
  global $thisimagesystem;

  global $subimages;
  
  $systype = `cat /proc/cobalt/systype`;
  $systype = chop($systype);

  if (isset($subimages)) {
    $thisimage['subimages'] = $subimages;
    unset($GLOBALS['subimages']);
  }
  // only record it if it's for this systype
  if (strtolower($thisimagesystem) == strtolower($systype)) {
    $xml_parser_images[$thisimagename] = $thisimage;
  } 
  unset($GLOBALS['thisimage']);
  unset($GLOBALS['thisimagename']);
  unset($GLOBALS['thisimagesystem']);
}

function subimage_start($attributes) {
  global $thissubimagename;
  global $thissubimage;

  if (!isset($thissubimage)) {
    $thissubimage = array();
  }

  while (list($key, $val) = each($attributes)) {
    if ($key == "name") {
      $thissubimagename = $val;
    } else {
      $thissubimage[$key] = $val;
    }
  }
}

function subimage_end() {
  global $subimages;
  global $thissubimagename;
  global $thissubimage;
  global $states;

  if (isset($states)) {
    $thissubimage['states'] = $states;
    unset($GLOBALS['$states']);
  }

  $subimages[$thissubimagename] = $thissubimage;
  unset($GLOBALS['thissubimage']);
  unset($GLOBALS['thissubimagename']);
}

function state_start($attributes) {
  global $thisstatename;
  global $thisstate;

  if (!isset($thisstate)) {
    $thisstate = array();
  }

  while (list($key, $val) = each($attributes)) {
    if ($key == "name") {
      $thisstatename = $val;
    } else {
      $thisstate[$key] = $val;
    }
  }
}

function state_end() {
  global $states;
  global $thisstatename;
  global $thisstate;
  
  $states[$thisstatename] = $thisstate;
  unset($GLOBALS['thisstate']);
  unset($GLOBALS['thisstatename']);
}



?>
