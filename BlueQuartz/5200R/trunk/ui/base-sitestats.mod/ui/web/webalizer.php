<?php

include_once('ServerScriptHelper.php');

$helper =& new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$helper->getAllowed('adminUser') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$factory =& $helper->getHtmlComponentFactory('base-ssl','/base/ssl/siteSSL.php');
$cce =& $helper->getCceClient();
$page = $factory->getPage();
$i18n =& $factory->getI18n();

if (!$file || ereg("./", $file) || (!ereg(".png$", $file) && !ereg(".html$", $file)) )
{
    $file = "index.html";
}

if ($group)
{
    if ($group != 'server') {
        list($oid) = $cce->find('Vsite', array('name' => $group));
        $vsite_info = $cce->get($oid);
        $fqdn = $vsite_info['fqdn'];
        $fullPath = "/home/sites/" . $vsite_info['fqdn'] . "/webalizer/" . $file;
    } else {
        if (is_dir("/var/www/html/usage")) {
            $fullPath = "/var/www/html/usage/" . $file;
        } else {
            $fullPath = "/var/www/usage/" . $file;
        }
    }
} else {
    print "Page not Found";
    exit;
}

if (preg_match('/html/', $file)) {
    print $page->toHeaderHtml();
}

if (file_exists($fullPath)){
        $fp = fopen ($fullPath, "r");

        $data = "";
        while(!feof($fp))
        {
                $string = fgets($fp, 4096);
                $string=str_replace("<A HREF=\"usage", "<A
HREF=\"webalizer.php?group=" . $group . "&file=usage", $string);
                $string=str_replace("<IMG SRC=\"", "<IMG
SRC=\"webalizer.php?group=" . $group . "&file=", $string);
                $data .= $string;
        }

        echo $data;

        @flose($fp);

}else{

        echo "<h4>Page not Found</h4>" . $fullPath;

}

if (preg_match('/html/', $file)){
print $page->toFooterHtml();
}

exit ();

?>
