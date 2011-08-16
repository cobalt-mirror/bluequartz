#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: set_logrotate.pl,v 1.1 2001/11/30 02:44:33 pbaltz Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# turn on/off log file rotation for sites, and adjust size if Vsite quota
# changes

use CCE;
use Sauce::Util;
use Base::HomeDir qw(homedir_get_group_dir);

my $LOGROTATE_DIR = '/etc/logrotate.d';
my $LOG_DIR = 'logs';
my $DEFAULT_SIZE = 25; # default size to rotate logs at (in MB))

my $cce = new CCE;
$cce->connectfd();

my $vsite = {};
my ($ok, $disk);
if ($cce->event_is_destroy())
{
	$vsite = $cce->event_old();
}
else
{
	$vsite = $cce->event_object();

	if ($cce->event_is_create() && !$vsite->{name})
	{
		$cce->bye('DEFER');
		exit(0);
	}

	($ok, $disk) = $cce->get($cce->event_oid(), 'Disk');

	if (!$ok)
	{
		$cce->bye('FAIL', '[[base-sitestats.systemError]]');
		exit(1);
	}
}

my $logrotate_file = "$LOGROTATE_DIR/$vsite->{name}";

# on destroy just get rid of the file
if ($cce->event_is_destroy())
{
	Sauce::Util::unlinkfile($logrotate_file);
}
else # create or quota change
{
	my $log_dir = homedir_get_group_dir($vsite->{name}, $vsite->{volume}) .
					"/$LOG_DIR";
	my $size = int($disk->{quota} / 10) || 1;

	# disk quota can be -1 to specify unlimited, so deal with it
	if ($disk->{quota} == -1) { $size = $DEFAULT_SIZE; }

	if (!Sauce::Util::editfile($logrotate_file, *edit_logrotate, 
				$log_dir, $size))
	{
		$cce->bye('FAIL', '[[base-sitestats.cantEnableLogrotate]]');
		exit(1);
	}
}

$cce->bye('SUCCESS');
exit(0);

sub edit_logrotate
{
	my($in, $out, $log_dir, $size) = @_;

	$size .= 'M';
	my($rotate) = <<EOF;
$log_dir/mail.log {
   missingok
   compress
   size $size
}

$log_dir/ftp.log {
   missingok
   compress
   size $size
}

$log_dir/web.log {
   missingok
   compress
   size $size
}
EOF

	print $out $rotate;

	return 1;
}
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
