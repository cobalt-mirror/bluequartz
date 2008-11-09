#!/usr/bin/perl -w
# $Id: Config.pm 201 2003-07-18 19:11:07Z will $


package Sauce::Config;

use File::Path;

sub VERSION { 
	my $id = q$Revision: 201 $;
  my ($vernum) = ($id =~ m/([\d\.]+)/);
  return $vernum;
}

# configurable parameters
sub WWW_group_id { 11; }
sub WWW_user { return 'httpd'; }
sub groupdir_owner { return 'admin'; }
sub admin_user { 'admin'; }
sub homedir_base { '/home/users'; }
sub groupdir_base { '/home/groups'; }
sub default_shell { '/bin/usersh'; }
sub bad_shell { return '/bin/badsh'; }
sub webdir { return 'web'; }
sub site_adm_group { return 'site-adm'; };
sub first_uid { return 110; }
sub first_gid { return 110; }
sub bytes_per_block { return 1024; }

# directories where I expect to find certain utilities
sub bin_htpasswd { '/usr/bin/htpasswd'; }
sub bin_sendmail { '/usr/sbin/sendmail'; }

# permissions
sub perm_UserBaseDir { 0755; }
sub perm_UserDir { 02751; }
sub perm_GroupDir { 02775; }
sub perm_UserPrivDir { 02700; }

# Global file locations. NOT for location used in only one handler or module.

sub dir_Cron_basedir { return "/etc/cron."; }
sub dir_Cron_intervals { return ('quarter-hourly','half-hourly','hourly','quarter-daily','daily','weekly','monthly','never'); }

# okay
1;
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
