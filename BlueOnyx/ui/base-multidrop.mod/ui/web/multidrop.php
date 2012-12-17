<?php 

include_once("ServerScriptHelper.php");
 
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-multidrop", "/base/multidrop/multidropHandler.php");

$systemObj = $cceClient->getObject("System",array(),"Multidrop");
 
$page = $factory->getPage();
 
$block = $factory->getPagedBlock("multidrop");
 
$block->addFormField(
  $factory->getBoolean("enableField", $systemObj["enable"]),
  $factory->getLabel("enableField")
);

 
$server = $factory->getNetAddress("serverField",
$systemObj["server"]);
$block->addFormField(
  $server,
  $factory->getLabel("serverField") );

$userDomain = $factory->getDomainName("userDomainField",
$systemObj["userDomain"]);
$block->addFormField(
  $userDomain,
  $factory->getLabel("userDomainField") );


$userName = $factory->getTextField("userNameField",
$systemObj["userName"]); 
$block->addFormField(
  $userName,
  $factory->getLabel("userNameField") );

$password = $factory->getPassword("passwordField",
$systemObj["password"]);
$password->setOptional("silent");
$block->addFormField(
  $password,
  $factory->getLabel("passwordField") );



$protoMap=array("POP3" => "POP3", "IMAP" => "IMAP", "ETRN" => "ETRN");

$proto_select = $factory->getMultiChoice("protoField",array_values($protoMap));
$proto_select->setSelected($protoMap[$systemObj["proto"]], true);
$block->addFormField($proto_select,$factory->getLabel("protoField"));


$interval = $factory->getInteger("intervalField",
$systemObj["interval"]); 
$block->addFormField(
  $interval,
  $factory->getLabel("intervalField") );


$block->addButton($factory->getSaveButton($page->getSubmitAction()));
 
$serverScriptHelper->destructor(); 
?>
<?php print($page->toHeaderHtml()); ?>
 
<?php print($block->toHtml()); ?>
 
<?php print($page->toFooterHtml()); 

?>

