#!/usr/bin/perl -I/usr/sausalito/handlers/base/ftp -I/usr/sausalito/perl
# $Id: ftpnonsiteadmin.pl
#
# This is triggered by changes to the Vsite FTP settings when FTP is enabled or 
# disabled for non siteAdmins. Then it creates or removes the .ftpaccess file 
# in their home directories. 

use CCE;
use Base::HomeDir qw(homedir_get_user_dir);

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();

($ok, my $userwebs) = $cce->get($cce->event_oid(), 'FTPNONADMIN');

if (not $ok) {
	&debug_msg("Can't read the 'FTPNONADMIN' item.");
    $cce->bye('FAIL', '[[base-ftp.cantReadFTPNONADMIN]]');
    exit(1);
}
else {
    &main;
}

$cce->bye('SUCCESS');
exit(0);

#
## Main sub
#
sub main {

    # Check if FTPNONADMIN is enabled or disabled:
    $documentRoot = $vsite->{basedir};
    $siteNumber = $vsite->{name};
    @soids = $cce->find('Vsite', {'name' => $siteNumber});
    ($ok, $flag) = $cce->get($soids[0], 'FTPNONADMIN');
    $ftpnonadmin = $flag->{enabled};

	&debug_msg("Working on Vsite $siteNumber to set FTP access flag to $ftpnonadmin.");

    # Find all users who belong to the site in question:
    @alluseroids = $cce->find('User', {'site' => $siteNumber});

    # Walk through the OIDs and push the OIDs of all non-siteAdmin users to array @notsiteadmin:
    foreach $entry (@alluseroids) {
        ($ok, $user) = $cce->get($entry);
        unless ($user->{capabilities} =~ /siteAdmin/) {
            $func = push(@notsiteadmin, $entry);
        }
		# Delete all prviously set .ftpaccess files from ALL users of this site.
		# That way we cover the cases where the siteAdmin flag was previously not granted, but then got added.
		$alluserhomedir = '';
		if (!scalar(@pwent) || exists($new->{site})) {
	        	$alluserhomedir = homedir_get_user_dir($user->{name}, $user->{site}, $user->{volume});
			if ($alluserhomedir =~ /^\/.+/) {
			    if (-d "$alluserhomedir") {
				    system("/bin/rm -f $alluserhomedir/.ftpaccess");	
			    }
			}
		}
    }

    # Process all non-SiteAdmin users:
    foreach $entry (@notsiteadmin) {
		($ok, $user) = $cce->get($entry);

		# Find the home directories of the respective users:
		$homedir = '';
		if (!scalar(@pwent) || exists($new->{site})) {
	        	$homedir = homedir_get_user_dir($user->{name}, $user->{site}, $user->{volume});
		}

		# Do the file actions. Either remove or add the .ftpaccess files to/from the respective user directories: 
		if ($ftpnonadmin eq "0") {	
		    # Put the .ftpaccess files into the respective user directories: 
		    if ($homedir =~ /^\/.+/) {
				if (-d "$homedir") {
					&debug_msg("Working on Vsite $siteNumber to create $homedir/.ftpaccess");
					system("/bin/echo '<Limit RAW LOGIN READ WRITE DIRS ALL>\nDenyAll\n</Limit>\n' > $homedir/.ftpaccess");
				}
		    }
		}
		else {
			&debug_msg("Working on Vsite $siteNumber to delete $homedir/.ftpaccess");
		    # Remove the .ftpaccess file from this user:
		    system("/bin/rm -f $homedir/.ftpaccess");	
		}
    }
}

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

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
