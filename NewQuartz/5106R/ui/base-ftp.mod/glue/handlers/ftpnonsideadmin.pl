#!/usr/bin/perl -I/usr/sausalito/handlers/base/ftp -I/usr/sausalito/perl
# $Id: ftpnonsiteadmin.pl Tue 13 May 2008 07:53:44 PM CEST mstauber $
#
# This is triggered by changes to the Vsite FTP settings when FTP is enabled or 
# disabled for non siteAdmins. Then it creates or removes the .ftpaccess file 
# in their home directories. 

use CCE;
use Base::HomeDir qw(homedir_get_user_dir);

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();

($ok, my $userwebs) = $cce->get($cce->event_oid(), 'FTPNONADMIN');

if (not $ok) {
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
			system("/bin/echo '<Limit RAW LOGIN READ WRITE DIRS ALL>\nDenyAll\n</Limit>\n' > $homedir/.ftpaccess");
		}
	    }
	}
	else {
	    # Remove the .ftpaccess file from this user:
	    system("/bin/rm -f $homedir/.ftpaccess");	
	}
    }
}

