#!/usr/bin/perl -w
# $Id: Config.pm

package Sauce::Config;

use File::Path;

sub VERSION { 
	my $id = q$Revision: 260 $;
  my ($vernum) = ($id =~ m/([\d\.]+)/);
  return $vernum;
}

# configurable parameters
sub WWW_group_id { 48; }
sub WWW_user { return 'apache'; }
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

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 