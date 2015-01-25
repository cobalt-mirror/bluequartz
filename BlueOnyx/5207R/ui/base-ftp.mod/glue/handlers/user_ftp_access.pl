#!/usr/bin/perl -I/usr/sausalito/handlers/base/ftp -I/usr/sausalito/perl
# $Id: user_ftp_access.pl Wed 14 May 2008 01:25:22 PM CEST mstauber $
#
# This is triggered when a user is created or changed. Then it creates 
# or removes the .ftpaccess file in their home directories to allow or 
# prevent FTP access.

use CCE;
use Base::HomeDir qw(homedir_get_user_dir);

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
            # He is. Then we exit here:
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

