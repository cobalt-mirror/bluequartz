#!/usr/bin/suidperl

$ENV{'PATH'} = '/usr/bin';

use File::ReadBackwards;

$log = "/var/log/messages";

$bw = File::ReadBackwards->new($log) or die "can't read $log $!" ;

$num = '0';
while( defined( $log_line = $bw->readline ) ) {
		# We sure do NOT want the SSH related stuff visible in the GUI:
		unless (($log_line =~ /(.*)SSHauthKeys(.*)/) || ($log_line =~ /(.*)\. SSH (.*)/)) {
        	print $log_line ;
        	$num++;
        	if ($num >= '200') {
        		exit;
        	}
        }
}
