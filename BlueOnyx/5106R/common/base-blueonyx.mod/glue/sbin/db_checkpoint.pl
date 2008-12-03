#!/usr/bin/perl
# $Id: db_checkpoint.pl 448 2005-01-03 11:38:06Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# Try to run db_checkpoint on the user and group db environment, so dbrecover
# doesn't take so long to check the database consistency on system
# boot.  Also, use db_archive to find out which log files are no longer needed
# and remove old log files from the system to save disk space.

use lib qw(/usr/sausalito/perl);
use I18n;

my $DB_CHECKPOINT = '/usr/sausalito/db4/bin/db_checkpoint';
my $DB_ARCHIVE = '/usr/sausalito/db4/bin/db_archive';
my $DB_HOME = '/var/db';
my $DB_LOGFILE = '/var/log/dbrecover.log';

# try to checkpoint the db twice.
# Checkpoint it twice so that db_recover uses the first checkpoint to
# start recovery, so it will finish relatively quickly.
for (my $i = 0; $i < 2; $i++)
{
	system("$DB_CHECKPOINT -1 -v -h $DB_HOME >>$DB_LOGFILE 2>&1");

	# check if it succeeded
	if ($?)
	{
		# failed for some reason
		my $locale = I18n::i18n_getSystemLocale();
		my $i18n = new I18n;
		$i18n->setLocale($locale);
		print $i18n->get('[[base-sys.checkPointFailed]]');
		exit(1);
	}
}

# no run db_archive to see what we can clean up
my @old_logs = `$DB_ARCHIVE -h $DB_HOME 2>/dev/null`;
chomp(@old_logs);

for my $file (@old_logs)
{
	unlink("$DB_HOME/$file");
}

exit(0);
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
