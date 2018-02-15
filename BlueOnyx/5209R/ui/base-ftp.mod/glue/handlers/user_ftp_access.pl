#!/usr/bin/perl -I/usr/sausalito/handlers/base/ftp -I/usr/sausalito/perl
# $Id: user_ftp_access.pl
#
# This is triggered when a user is created or changed. Then it creates 
# or removes the .ftpaccess file in their home directories to allow or 
# prevent FTP access.

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

my $eventObj = $cce->event_object();

$username = $eventObj->{name};

&find_site_of_user;

$cce->bye('SUCCESS');
exit(0);

#
## Subs:
#

sub find_site_of_user {

    # Find out to which site the user belongs to:
    ($vsoid) = $cce->find('User', { 'name' => $username });
    if ($vsoid) {
        ($ok, $user) = $cce->get($vsoid);
        $usite = $user->{site};

        # Find out if the user is systemAdministrator:
        if ($user->{systemAdministrator} eq "1") {
            &debug_msg("capLevels say we're 'systemAdministrator'. No FTP-Access-handling needed.\n");
            # He is. Then we exit here:
            $cce->bye('SUCCESS');
            exit(0);
        }

        # If the user is an 'adminUser' we take an early exit, too:
        if ($eventObj->{capLevels} =~ /adminUser/) {
            &debug_msg("capLevels say we're 'adminUser'. No FTP-Access-handling needed.\n");
            $cce->bye('SUCCESS');
            exit(0);
        }

        # Is the user a siteAdmin?
        $isSiteAdmin = "0";
        if ($user->{capabilities} =~ /siteAdmin/) {
            $isSiteAdmin = "1";
        }
    }
    else {
        $cce->bye('FAIL', '[[base-ftp.cantFindThatUser]]');
        exit(1);
    }
    if (!$usite) {
        $cce->bye('FAIL', '[[base-ftp.cantFindOutWhichSiteUserBelongsTo]]');
        exit(1);
    }
    else {
        # Check if FTPNONADMIN is enabled or disabled for that site:
        &is_ftpnonadmin_set_for_site;
        # Manage the FTP access (adding/removal of .ftpaccess file):
        &manage_ftpaccess;
    }
}

sub is_ftpnonadmin_set_for_site {

    # Check if FTPNONADMIN is enabled or disabled for that site:
    @soids = $cce->find('Vsite', {'name' => $usite});
    ($ok, $flag) = $cce->get($soids[0], 'FTPNONADMIN');
    $ftpnonadmin = $flag->{enabled};

}

sub manage_ftpaccess {

    # Find the home directories of the respective users:
    $homedir = '';
    $homedir = homedir_get_user_dir($user->{name}, $user->{site}, $user->{volume});

    # Do the file actions. Add the .ftpaccess files to the user directory,
    # but only if FTP has been turned off for the site and the user is not
    # a siteAdmin:
    if (($ftpnonadmin eq "0") && ($isSiteAdmin eq "0")) {
        # Put the .ftpaccess files into the respective user directory:
        if ($homedir =~ /^\/.+/) {
            if (-d "$homedir") {
                system("/bin/echo '<Limit RAW LOGIN READ WRITE DIRS ALL>\nDenyAll\n</Limit>\n' > $homedir/.ftpaccess");

                # Update 'ftpDisabled' in CCE for that user:
                ($uvsoid) = $cce->find('User', { 'name' => $username });
                if ($uvsoid) {
                    ($ok, $user) = $cce->set($uvsoid, '', {'ftpDisabled' => '1'});
                }
            }
        }
    }
    else {
        # Remove the .ftpaccess file from this user's home directory:
        system("/bin/rm -f $homedir/.ftpaccess");

        # Update 'ftpDisabled' in CCE for that user:
        ($uvsoid) = $cce->find('User', { 'name' => $username });
        if ($uvsoid) {
            ($ok, $user) = $cce->set($uvsoid, '', {'ftpDisabled' => '0'});
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
# Copyright (c) 2015-2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2017 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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