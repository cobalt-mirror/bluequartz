<?php
    // Bit of a hack: If BASEPATH is not set, we can assume that the calling page is
    // old-Style BlueOnyx and not using CodeIgniter. So instead of showing that class
    // CceClient is unknown, we show the user a page which explains him to update his
    // stuff via YUM, SWUpdate and if that doesn't fit it, then to ask for help:
    if ( ! defined('BASEPATH')) {
      header('Location: /gui/NotYetDone');
    }
?>
