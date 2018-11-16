<?php

putenv("_HTTP_HOST=".@$_SERVER["HTTP_HOST"]);
putenv("_SCRIPT_NAME=".@$_SERVER["SCRIPT_NAME"]);
putenv("_SCRIPT_FILENAME=".@$_SERVER["SCRIPT_FILENAME"]);
putenv("_DOCUMENT_ROOT=".@$_SERVER["DOCUMENT_ROOT"]);
putenv("_REMOTE_ADDR=".@$_SERVER["REMOTE_ADDR"]);
putenv("_SOWNER=".@get_current_user());

?>
