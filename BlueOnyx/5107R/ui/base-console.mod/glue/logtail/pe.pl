#!/usr/bin/suidperl

$ENV{'PATH'} = '/usr/bin';

system("/usr/bin/tail -40 /var/log/httpd/error_log");

