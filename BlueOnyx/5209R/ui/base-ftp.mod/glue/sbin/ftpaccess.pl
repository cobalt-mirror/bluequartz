#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: ftpaccess.pl Tue 13 May 2008 07:53:44 PM CEST mstauber $
#
# This script takes one switch (-u) and one argument (a valid username) as parameters.
# Upon execution it removes or creates the .ftpaccess file in the user's home directory, 
# which prevents or allows FTP access. 
#
# The .ftpaccess file which prevents FTP access will only be created if ...
#
# - The user is not a siteAdmin
# - FTP access for non-Admin users is disabled for that site
#
# ... otherwise it will remove the .ftpaccess file from that users home directory.
#
#

use CCE;
use Base::HomeDir qw(homedir_get_user_dir);

my $cce = new CCE;
$cce->connectuds();

if (defined($ARGV[1])) {
    if ($ARGV[0] = "-u") {
        $username = $ARGV[1];
	&find_site_of_user;
    }
}
else {
    print "Wrong command line usage!\n\n";
    print "This script takes one switch (-u) and one argument (a valid username) as parameters.\n\n";
    print "Upon execution it removes or creates the .ftpaccess file in the user's home directory, \n";
    print "which prevents or allows FTP access.\n\n";
    print "The .ftpaccess file which prevents FTP access will only be created if ...\n";
    print "\n";
    print "- The user is not a siteAdmin\n";
    print "- FTP access for non-Admin users is disabled for that site\n";
    print "\n";
    print "... otherwise it will remove the .ftpaccess file from that users home directory.\n";
    print "\n";
}

$cce->bye('SUCCESS');
exit(0);

#
## Start sub
#

sub find_site_of_user {
	
    # Find out to which site the user belongs to:

    ($vsoid) = $cce->find('User', { 'name' => $username });
    if ($vsoid) {
        ($ok, $user) = $cce->get($vsoid);
	$usite = $user->{site};

	# Is the user a siteAdmin?
	$isSiteAdmin = "0";
	if ($user->{capabilities} =~ /siteAdmin/) {
	    $isSiteAdmin = "1";
	    $explanation = " because he is siteAdmin";
	}
    }
    else {
	print "ERROR: Cannot find that user.\n";
	exit(1);
    }
    if (!$usite) {
	print "ERROR: Unable to determine to which site user $username belongs.\n";
	exit(1);
    }
    else {
    	# Check if FTPNONADMIN is enabled or disabled for that site:
	&is_ftpnonadmin_set_for_site;
	&manage_ftpaccess;
    }
}

sub is_ftpnonadmin_set_for_site {
	
    # Check if FTPNONADMIN is enabled or disabled for that site:
    @soids = $cce->find('Vsite', {'name' => $usite});
    ($ok, $flag) = $cce->get($soids[0], 'FTPNONADMIN');
    $ftpnonadmin = $flag->{enabled};
    print "FTP access for non admin users for site $usite is set to $ftpnonadmin \n";

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
		print "FTP access for user $username has been disabled.\n";
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
        print "FTP access for user $username has been enabled" . $explanation . ".\n";
    }
}

