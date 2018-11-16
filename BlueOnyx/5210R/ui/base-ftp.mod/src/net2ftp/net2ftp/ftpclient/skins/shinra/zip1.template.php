<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/shinra/zip1.template.php begin -->
<div style="border-color: #000000; border-style: solid; border-width: 1px; padding: 10px; margin-<?php echo __("right"); ?>: 100px; margin-bottom: 10px;">
<input type="checkbox" name="zipactions[save]" value="yes" /> <?php echo __("Save the zip file on the FTP server as:"); ?> <input type=text class="input" name="zipactions[save_filename]" value="<?php echo $zipfilename; ?>" />
</div> &nbsp; 

<?php for ($i=1; $i<=sizeof($list["all"]); $i++) { ?>
<?php		printDirFileProperties($i, $list["all"][$i], "hidden", ""); ?>
<?php	} // end for ?>

<!-- Template /skins/shinra/zip1.template.php end -->
