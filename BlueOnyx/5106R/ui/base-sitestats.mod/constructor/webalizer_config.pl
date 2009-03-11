#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: webalizer_config.pl, v1.0.0.0 Wed 11 Mar 2009 11:16:19 AM EDT mstauber Exp $
# Copyright 2006-2009 Team BlueOnyx. All rights reserved.

# This script edits /etc/webalizer.conf and sets the switch "ReallyQuiet" to "yes".
# Otherwise we get the "Warning: Truncating oversized request field" messages to cron.
# This will also suppress errors and warnings.

use CCE;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectfd();

# Check for presence of /etc/webalizer.conf:
if (-f "/etc/webalizer.conf") {
    # Fix /etc/webalizer.conf:
    system("/bin/sed -i -e 's@^#ReallyQuiet.*\@ReallyQuiet    yes\@' /etc/webalizer.conf");
}

$cce->bye('SUCCESS');
exit(0);

