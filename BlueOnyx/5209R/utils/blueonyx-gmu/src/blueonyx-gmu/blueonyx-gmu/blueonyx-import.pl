#!/usr/bin/perl -I/usr/sausalito/perl

# Debugging switch (0|1|2):
# 0 = off
# 1 = log to syslog
# 2 = log to screen
#
$DEBUG = "2";
if ($DEBUG) {
    if ($DEBUG eq "1") {
        use Sys::Syslog qw( :DEFAULT setlogsock);
    }
}

# Note: Required dependencies:
#
# yum install perl-XML-Simple pv perl-Apache-ConfigFile perl-List-Flatten perl-Mail-Sendmail
# These are in the CentOS 7 repository and/or the BlueOnyx 5209R repositories.

#
### CCE:
#

use CCE;
$cce = new CCE;
$cce->connectuds();

#
### Load required Perl modules:
#

use Getopt::Std;
use XML::Simple;
use Data::Dumper;
use Apache::ConfigFile;
use List::Flatten;
use Unix::PasswdFile;
use Unix::GroupFile;
use Mail::Sendmail;
use Fcntl qw( :flock );
use DB_File;
use List::MoreUtils qw(uniq);
use Quota;
use POSIX;

#
### Check if we are 'root':
#
&root_check;

#
### Command line option handling
#

%options = ();
getopts("avhcepxqd:i:n:r:", \%options);

# Handle display of help text:
if ($options{h}) {
    &help;
}

# Apply PHP settings:
if ($options{p}) {
    &header;
    &apply_php_settings;
    print "### Done! \n";
    $cce->bye("SUCCESS");
    exit(0);
}

# Skip reseller import:
$no_resellers = "0";
if ($options{a}) {
    $no_resellers = "1";
}

# Just examine dump:
$just_examine = "0";
if ($options{e}) {
    $just_examine = "1";
}

# Import the configurations of Vsites and User only, but don't import Tarballs:
$config_only = "0";
if ($options{c}) {
    $config_only = "1";
    $just_examine = "0";
}

# Skip Vsite import:
$no_vsites = "0";
if ($options{v}) {
    $no_vsites = "1";
}

# Skip MD5-Check of Tarballs:
$skip_md5_verify = "0";
if ($options{x}) {
    $skip_md5_verify = "1";
}

# Fix Vsite and User quota:
if ($options{q}) {
    &header;
    &fix_overquotas;
    print "### Done! \n";
    $cce->bye("SUCCESS");
    exit(0);
}

# Import all Vsites IP address specified on CLI:
$ip_override = "0";
if ($options{i}) {
    $ip_override = "1";
    $override_ipAddr = $options{i};
}

# Import all Vsites IP address specified on CLI:
$import_all = "1";
@import_Vsites = ();
if ($options{n}) {
    $import_all = "0";
    if ($options{n} =~ /,/) {
        @import_Vsites = split(',', $options{n});
        @import_Vsites = uniq(@import_Vsites);
    }
    else {
        push @import_Vsites, $options{n};
        @import_Vsites = uniq(@import_Vsites);
    }
}

# Remove Resellers, Vsites, Users - and we omit the trailing 's' in our check:
if ($options{r}) {
    if ($options{r} =~ /reseller/i) {
        &delete_resellers;
    }
    elsif ($options{r} =~ /vsite/i) {
        &delete_vsites;
        $cce->bye("SUCCESS");
        exit(0);

    }
    elsif ($options{r} =~ /user/i) {
        &delete_users;
        $cce->bye("SUCCESS");
        exit(0);
    }
    else {
        &help("-r needs one option. Either 'resellers', 'vsites' or 'users'.")
    }
}

# Import directory:
if ($options{d}) {
    $path_to_import = $options{d} . "/";
    $path_to_import =~ s#//#/#;
    $xml_path = $path_to_import . "BlueOnyx-Migrate.xml";
}
else {
    $path_to_import = `pwd`;
    chomp($path_to_import);
    $path_to_import .= "/";
    $path_to_import =~ s#//#/#;
    $xml_path = $path_to_import . "BlueOnyx-Migrate.xml";
}

# Check presence of XML:
unless (-f $xml_path) {
    &help("Invalid path. No 'BlueOnyx-Migrate.xml' in the specified target directory.")
}

#
### Start:
#

&debug_msg("########################################################## \n");
&debug_msg("# blueonyx-import.pl: BlueOnyx Generic Migration Utility #\n");
&debug_msg("##########################################################\n\n");
&debug_msg("This utility allows to import Vsites and Users that were exported according\nto the BlueOnyx API on another BlueOnyx or non-BlueOnyx server.\n");

# Create XML object:
$xml = new XML::Simple;

# Read XML file:
$data = $xml->XMLin($xml_path, 'ForceArray' => '1');

unless ($data->{Vsite} && $data->{User} && $data->{Resellers}) {
    &help("The XML file does not seem to contain valid data.");
}

# Print output:
&debug_msg("\n################################## \n");
&debug_msg("XML Config Contends: \n");
&debug_msg("################################## \n\n");

&debug_msg("This export was generated on " . $data->{baseHost} . " at " . $data->{DateTime} . "\n");

$Vsite = $data->{Vsite};
$User = $data->{User};
$Vsite_Extra = $data->{Vsite_Extra};
$User_Extra = $data->{User_Extra};
$Resellers = $data->{Resellers};
$User_Tarballs = $data->{User_Tarballs};
$Vsite_Tarballs = $data->{Vsite_Tarballs};

$numVsites = "0";
@Vsites_in_XML = ();
foreach $v ( keys %{ $Vsite } ) {
    $numVsites++;
    if ($import_all eq "1") {
        push @import_Vsites, $v;
        @import_Vsites = uniq(@import_Vsites);
    }
    push @Vsites_in_XML, $v;
    @Vsites_in_XML = uniq(@Vsites_in_XML);
}

# If we only import selected Vsites, then make sure that these Vsites exist in XML:
if ($import_all eq "0") {
    foreach $v (@import_Vsites) {
        unless (in_array(\@Vsites_in_XML, $v)) {
            &help("The Vsite '$v' could not be found in the export!");
        }
    }
}

$numUsers = "0";
foreach $u ( keys %{ $User } ) {
    $numUsers++;
}

&debug_msg("The data of this export contains $numVsites Vsites and $numUsers Users.\n\n");

#
### Verifiy integrity of dump tarballs:
#

if (($config_only eq "0") && ($skip_md5_verify eq "0")) {
    &verify_tarball_integrity;
}

if ($just_examine eq "1") {
    $cce->bye("SUCCESS");
    exit(0);
}

#
### Check if the Vsites we want to import belong to a Reseller. If they do, we need to create the Reseller first:
#

@Resellers_to_Import = ();
if (is_array($Resellers)) {
    &debug_msg("No Resellers to import.\n");
    $no_resellers = "1";
    @Resellers_to_Import = ();
}
else {
    foreach $v (@import_Vsites) {
        foreach $u ( keys %{ $Resellers } ) {
            @known_reseller_domains = scalar_to_array($Resellers->{$u}->{domains});
            $num = scalar(@known_reseller_domains);
            if (in_array(\@known_reseller_domains, $v)) {
                if ($no_resellers eq "1") {
                    &debug_msg("Reseller '$u' owns Vsite '$v' - are you sure you don't want to import him?\n");
                }
                else {
                    &debug_msg("Reseller '$u' owns Vsite '$v'\n");
                }
                push @Resellers_to_Import, $Resellers->{$u}->{newName};
                @Resellers_to_Import = uniq(@Resellers_to_Import);
            }
        }
    }
}

#
### Create Resellers:
#

&debug_msg("\n");

if ($no_resellers eq "0") {

    print "########################################################## \n";
    print "# Creating Reseller Accounts:                            #\n";
    print "##########################################################\n\n";

    foreach $u (@Resellers_to_Import) {
        &debug_msg("Attempting to create Reseller-Account: $u\n");
        if (&cce_find_user($u)) {
            &help("Creation of User '$u' failed! User '$u' already exists! (OID: " . &cce_find_user($u) .")");
        }
        else {
            $ok = &cce_create_user($u);
            if ($ok) {
                &debug_msg("User '$u' was created successfully.\n\n");
            }
            else {
                &help("Creation of User '$u' failed!");
            }
        }
    }
    print "### Done! \n";
}

#
### Create Vsites:
#

if ($no_vsites eq "0") {

    &debug_msg("\n");

    print "########################################################## \n";
    print "# Creating Vsites:                                       #\n";
    print "##########################################################\n\n";

    foreach $v (@import_Vsites) {
        &debug_msg("Attempting to create Vsite: $v\n");
        if (&cce_find_vsite($v)) {
            &help("Creation of Vsite '$v' failed! Vsite '$v' already exists! (OID: " . &cce_find_vsite($v) .")");
        }
        else {
            $ok = &cce_create_vsite($v);
            if ($ok) {
                if ($ok eq "1") {
                    &debug_msg("Vsite '$v' was created successfully.\n\n");
                }
                else {
                    &debug_msg("Skipping Vsite '$v' as the XML didn't contain all the required info for it.\n\n");
                }
            }
            else {
                &help("Creation of Vsite '$v' failed!");
            }
        }
    }

    print "### Done! \n";
    &debug_msg("\n");

}

#
### Create Vsite Users:
#

print "########################################################## \n";
print "# Creating Users of Vsites:                              #\n";
print "##########################################################\n\n";

@Userss_to_Import = ();
foreach $v (@import_Vsites) {
    foreach $u ( keys %{ $User } ) {
        if (in_array(\@import_Vsites, $User->{$u}->{site})) {
            &debug_msg("Attempting to import User '$u' of Vsite '$User->{$u}->{site}'\n");
            $tmpvs = $User->{$u}->{site};
            $ok = &cce_create_user($u);
            if ($ok) {
                if ($ok eq "1") {
                    &debug_msg("User '$u' was created successfully.\n\n");
                }
                else {
                    &debug_msg("Skipping User '$u' as the XML didn't contain all the required info for his Vsite ('$tmpvs') it.\n\n");
                }
            }
            else {
                &help("Creation of User '$u' failed!");
            }
        }
        else {
            delete $User->{$u};
        }
    }
}

#
### Fix Vsite Web-Owners:
#

if ($config_only eq "0") {
    if (scalar($Vsite_Chowns) gt "0") {
        print "\n########################################################## \n";
        print "# Fix Web-Owners of Vsites:                              #\n";
        print "##########################################################\n\n";

        foreach $cv ( keys %{ $Vsite_Chowns } ) {
            if (($Vsite_Chowns->{$cv}->{uid} ne "") && ($Vsite_Chowns->{$cv}->{gid} ne "") && ($Vsite_Chowns->{$cv}->{target} ne "")) {
                &debug_msg("Running 'chown -R $Vsite_Chowns->{$cv}->{uid}:$Vsite_Chowns->{$cv}->{gid} $Vsite_Chowns->{$cv}->{target}'\n");
                system("chown -R $Vsite_Chowns->{$cv}->{uid}:$Vsite_Chowns->{$cv}->{gid} $Vsite_Chowns->{$cv}->{target}");
            }
        }
        &debug_msg("\n");
    }
}

#
### Raise Vsite and User quota if they are over-quota:
#

if ($config_only eq "0") {
    &fix_overquotas;
}

#
### Apply PHP Settings:
#

&apply_php_settings;

print "### Done! \n";

#
### Debugging:
#

#print Dumper(\@import_Vsites);
#print Dumper($data);
#print Dumper($Vsite);
#print Dumper($User);
#print Dumper($Vsite_Extra);
#print Dumper($User_Extra);
#print Dumper($Resellers);

#
### Subroutines:
#

sub debug_msg {
    if ($DEBUG eq "1") {
        $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
    if ($DEBUG eq "2") {
        my $msg = shift;
        print $msg;
    }
}

sub is_array {
    my ($ref) = @_;
    # Firstly arrays need to be references, throw
    #  out non-references early.
    return 0 unless ref $ref;

    # Now try and eval a bit of code to treat the
    #  reference as an array.  If it complains
    #  in the 'Not an ARRAY reference' then we're
    #  sure it's not an array, otherwise it was.
    eval {
        my $a = @$ref;
    };
    if ($@=~/^Not an ARRAY reference/) {
        return 0;
    }
    elsif ($@) {
        die "Unexpected error in eval: $@\n";
    }
    else {
        return 1;
    }
}

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

# pack and unpack arrays
sub array_to_scalar {
    my $scalar = "&";
    if ($_ eq "undef") {
        next;
    }
    while (defined($_ = shift)) {
        $scalar .= $_ . '&';
    }
    if ($scalar eq "&") { 
        $scalar = ""; # special case
    }
    return $scalar;
}

sub scalar_to_array {
    my $scalar = shift || "";
    $scalar =~ s/^&//;
    $scalar =~ s/&$//;
    my @data = split(/&/, $scalar);
    for ($i = 0; $i <= $#data; $i++) {
        $data[$i] =~ s/\+/ /g;
        $data[$i] =~ s/%([0-9a-fA-F]{2})/chr(hex($1))/ge;
    }
    return @data;
}

sub header {
    print "########################################################## \n";
    print "# blueonyx-import.pl: BlueOnyx Generic Migration Utility #\n";
    print "##########################################################\n\n";
}

sub help {
    $error = shift || "";
    &header;
    if ($error) {
        print "ERROR: $error\n\n";
    }
    print "usage:   blueonyx-import.pl [OPTION]\n";
    print "         -c import configuration only\n";
    print "         -d directory of that contains the exported files\n";
    print "         -i import all virtual sites with this IP address\n";
    print "         -n import only these sites ie -n \"ftp.foo.com,www.bar.com\"\n";
    print "         -r remove items ie \"-r (vsites|users|resellers)\"\n";
    print "         -a skip import of all Reseller accounts\n";
    print "         -e just examine and verify dump\n";
    print "         -q Fix Vsite and User quota to OK limits\n";
    print "         -p Set all Vsites PHP settings to server defaults\n";
    print "         -h help, this help text\n\n";
    $cce->bye("SUCCESS");
    exit(0);
}

#sub uniq {
#    my %seen;
#    grep !$seen{$_}++, @_;
#}

sub cce_find_vsite {
    my $v = shift || "";
    @oids = $cce->find("Vsite", {"fqdn" => $v});
    @oids_www = $cce->find("Vsite", {"fqdn" => "www.".$v});
    $num = scalar(@oids) + scalar(@oids_www);
    if ($oids[0] gt "1") {
        $retOid = $oids[0];
    }
    if ($oids_www[0] gt "1") {
        $retOid = $oids_www[0];
    }
    if ($num gt "0") {
        return $retOid;
    }
    else {
        return;
    }
}

sub cce_find_user {
    my $u = shift || "";
    @oids = $cce->find("User", {"name" => $u});
    if (scalar(@oids) eq "1") {
        return $oids[0];
    }
}

sub cce_get_user {
    my $u = shift || "";
    @oids = $cce->find("User", {"name" => $u});
    if (scalar(@oids) eq "1") {
        ($ok, $obj) = $cce->get($oids[1]);
        return $obj;
    }
}

sub cryptpw {
    my $pw = shift;
    my @saltchars = ('a'..'z','A'..'Z',0..9);
    srand();
    my $salt = sel(@saltchars) . sel(@saltchars);
    my $crypt_pw = crypt($pw, $salt);
    $salt = '$1$';
    for (my $i = 0; $i < 8; $i++) { $salt .= sel(@saltchars); }
    $salt .= '$';
    my $md5_pw = crypt($pw, $salt);
    return ($crypt_pw, $md5_pw);
}

sub delete_resellers {
    &header;
    @oids = $cce->find("User", {"capLevels" => 'manageSite'});
    foreach $delObj (@oids) {
        ($ok, $obj) = $cce->get($delObj);
        &debug_msg("Deleting Reseller '$obj->{name}'\n");
        ($ok, $obj) = $cce->destroy($delObj);
    }
    &debug_msg("\nDone!\n");
    $cce->bye("SUCCESS");
    exit(0);
}

sub delete_vsites {
    &header;
    @oids = $cce->find("Vsite");
    foreach $delObj (@oids) {
        ($ok, $obj) = $cce->get($delObj);
        &debug_msg("Deleting Vsite '$obj->{fqdn}'\n");
        #($ok, $obj) = $cce->destroy($delObj);
        system("/usr/sausalito/sbin/vsite_destroy.pl $obj->{name}");
    }
    &debug_msg("\nDone!\n");
}

sub delete_users {
    &header;
    @ResellerOids = $cce->find("User", {"capLevels" => 'manageSite'});
    @UserOids = $cce->find("User");
    foreach $oid (@UserOids) {
        ($ok, $obj) = $cce->get($oid);
        if (in_array(\@ResellerOids, $oid)) {
            # Ignore Resellers:
            next;
        }
        elsif ($obj->{systemAdministrator} eq "1") {
            # Ignore all systemAdministrator accounts:
            next;
        }
        else {
            &debug_msg("Deleting User Account '$obj->{name}'\n");
            # Turn filecheck off and unsuspend email before deleting:
            $cce->set($oid, '', { 'noFileCheck' => 1 , 'emailDisabled' => '0'});
            ($ok, $obj) = $cce->destroy($oid);
        }
    }
    &debug_msg("\nDone!\n");
}

sub cce_create_user {
        my $cu = shift;

        # Keep track of Errors during CREATE and SET:
        my $errors = '0';

        # Create new Object for User:
        $do_user = $User->{$cu};

        foreach $uok ( keys %{ $do_user } ) {
            # If a User-Object contains a hash-ref, then this is a NameSpace within the User Object.
            # Move the NameSpace over to a separate Object:
            if (ref $do_user->{$uok} eq ref {}) {
               $do_user_extra->{$uok} = $do_user->{$uok};
               delete $do_user->{$uok};
            }
            if (is_array($do_user->{$uok})) {
                $do_user_extra->{$uok} = $do_user->{$uok}[0];
                delete $do_user->{$uok};
            }
        }

        # Add/remove anything we need:
        $do_user->{name} = $cu;
        $do_user_reserve->{password} = $do_user->{crypt_password};
        delete $do_user->{crypt_password};
        delete $do_user->{md5_password};

        #
        ### Handle Users Vsite association:
        #

        if (defined $Skipped_Vsites->{$do_user->{site}}) {
            delete $User->{$cu};
            $retval = "2";
            return $retval;
        }

        if ($do_user->{site} ne "") {
            $u_Vsite_oid = cce_find_vsite($do_user->{site});
            if ($u_Vsite_oid gt "1") {
                ($ok, $users_site) = $cce->get($u_Vsite_oid);
                &debug_msg("User '$cu' belongs to Vsite -> '$users_site->{fqdn}' ($users_site->{name})\n");
                # Update the site info before attempting to create the user:
                $do_user->{site} = $users_site->{name};
            }
            else {
                &help("Cannot determine which Vsite user '$cu' belongs to!");
            }
        }

        #
        ### Make sure the username is available:
        #

        @oids_s_alias = $cce->find("EmailAlias", {'alias' => $cu, 'site' => $users_site->{name}});
        if (scalar(@oids_s_alias)) {
            # Find out the User who owns that bloody alias!
            ($ok, $EmailAlias) = $cce->get($oids_s_alias[0]);
            &debug_msg("Email alias '$cu' is already in use by user '$EmailAlias->{action}'. Removing it before importing user '$cu'.\n");
            # Find OID of offender:
            $alias_swatter_oid = cce_find_user($EmailAlias->{action});
            # Get his Object and Email Namespace:
            ($ok, $alias_swatter) = $cce->get($alias_swatter_oid);
            ($ok, $alias_swatter_Email) = $cce->get($alias_swatter_oid, 'Email');
            # Get his aliases:
            @Swatter_Aliases = scalar_to_array($alias_swatter_Email->{aliases});
            # Remove our username from his aliases:
            @cleaned_Swatter_Aliases = ();
            foreach $asSwA (@Swatter_Aliases) {
                if ($asSwA ne $cu) {
                    push @cleaned_Swatter_Aliases, $asSwA;
                }
            }
            # Update Swatter-Aliases in CODB:
            ($ok) = $cce->set($alias_swatter_oid, "Email", {'aliases' => array_to_scalar(@cleaned_Swatter_Aliases)});
        }

        #
        ### CREATE the main User Object:
        #

        ($ok) = $cce->create("User", $do_user, '');

        # Check result:
        if ($ok ne "1") {
            # Increment error counter:
            $errors++;
        }

        # Set Password-Hash:
        if ($errors eq '0') {
            &debug_msg("Setting password via: /usr/sbin/usermod $cu -p '" . $do_user_reserve->{password} . "'\n");
            system("/usr/sbin/usermod $cu -p '" . $do_user_reserve->{password} . "'");
        }

        #
        ### Set all NameSpaces of the newly create User Object:
        #

        # Get User-OID:
        $user_OID = &cce_find_user($cu);

        if (($errors eq '0') && ($user_OID)) {

            # Some cleanup of NameSpace key/value pairs:
            if ($do_user_extra->{Email}->{vacationMsgStart} eq "") {
                delete $do_user_extra->{Email}->{vacationMsgStart};
            }
            if ($do_user_extra->{Email}->{vacationMsgStop} eq "") {
                delete $do_user_extra->{Email}->{vacationMsgStop};
            }

            # Disk Quota adjustments (reduce by factor 1024):
            $do_user_extra->{Disk}->{quota} = $do_user_extra->{Disk}->{quota}/1024;

            # Loop through all NameSpaces:
            foreach $uon ( keys %{ $do_user_extra } ) {

                # Make sure the Email aliases are available:                
                if ($uon eq "Email") {
                    @new_userAliases = ();
                    @used_user_aliases = (scalar_to_array($do_user_extra->{$uon}->{aliases}));
                    foreach $s_alias (@used_user_aliases) {
                        # Check if this Vsite already has an email alias with that name:
                        @oids_s_alias = $cce->find("EmailAlias", {'alias' => $s_alias, 'site' => $users_site->{name}});
                        if (scalar(@oids_s_alias)) {
                            &debug_msg("Email alias '$s_alias' for user '$cu' is already in use. Removing it before import.\n");
                        }
                        else {
                            push @new_userAliases, $s_alias;
                        }
                    }
                    $do_user_extra->{$uon}->{aliases} = array_to_scalar(@new_userAliases);
                }

                # Perform SET transaction:
                ($ok) = $cce->set($user_OID, "$uon", $do_user_extra->{$uon});
                delete $do_user_extra->{$uon};
                # Check result:
                if ($ok ne "1") {
                    # Increment error counter:
                    $errors++;
                }
            }
        }

        #
        # Import Tarballs (if any):
        #

        if ($config_only eq "0") {
            if (defined $User_Tarballs->{$cu}) {

                @balls = (keys %{$User_Tarballs->{$cu}});
                foreach $ball (@balls) {
                    $fileName = $User_Tarballs->{$cu}->{$ball}[0]->{fileName};
                    $md5sum = $User_Tarballs->{$cu}->{$ball}[0]->{md5sum};

                    # Get User basedir:
                    $u_data = get_current_user_sysdata($cu);

                    # Get Group:
                    ($ok, $obj_user) = $cce->get($user_OID);
                    $u_group = $obj_user->{site};

                    # Path and Filename of tarball to import:
                    $unpack_this_file = $path_to_import . $fileName;
                    $unpack_target = $u_data->{homedir} . "/";

                    # Unpack and chown to correct group:
                    unless ($unpack_target eq "/") {
                        &debug_msg("Importing Tarball ($fileName) for User '$cu' to $unpack_target\n");
                        system("pv $unpack_this_file | tar xzf - -C $unpack_target");
                        if ($ball eq "mbox") {
                            # Give mbox file the right name:
                            system("mv $unpack_target/$cu $unpack_target/mbox");
                            system("chmod 600 $unpack_target/mbox");
                        }
                        system("chown -R $cu:$u_group $unpack_target");
                        &debug_msg("\n");
                    }
                }
                if (-d "$unpack_target/Maildir") {
                    &debug_msg("Converting Maildir to Mbox format ... (this will take a moment) ...\n");
                    $target_mbox = $unpack_target . "mbox";
                    $target_mbox_convert = $unpack_target . "mbox-convert";
                    system("cd $unpack_target; /usr/sausalito/sbin/dw-maildirtombox.pl . $target_mbox_convert");
                    system("touch  $target_mbox");
                    if (-f "$target_mbox_convert") {
                        system("cat $target_mbox_convert >> $target_mbox");
                        system("rm -f $target_mbox_convert");
                    }
                    system("chmod 600 $target_mbox");
                    system("chown -R $cu:$u_group $unpack_target");
                }
            }
        }

        $retval = '';
        if ($errors eq "0") {
            $retval = "1";
            # As we're processing this User now, we remove him from $Users right away
            # to spare us confusion about who we did already and whom not:
            delete $User->{$cu};
            undef $retryValue;
        }
        return $retval;
}

sub cce_create_vsite {
        my $cv = shift;

        # Keep track of Errors during CREATE and SET:
        my $errors = '0';

        # Create new Object for Vsite:
        $do_vsite = $Vsite->{$cv};

        if (($do_vsite->{domain} eq "") || ($do_vsite->{fqdn} eq "") || ($do_vsite->{hostname} eq "") || ($do_vsite->{ipaddr} eq "")) {
            $Skipped_Vsites->{$cv} = $cv;
            delete $Vsite->{$cv};
            $retval = '2';
            return $retval;
        }

        # As we're processing this Vsite now, we remove him from $Vsite right away
        # to spare us confusion about who we did already and whom not:
        delete $Vsite->{$cv};

        foreach $vok ( keys %{ $do_vsite } ) {
            # If a User-Object contains a hash-ref, then this is a NameSpace within the User Object.
            # Move the NameSpace over to a separate Object:
            if (ref $do_vsite->{$vok} eq ref {}) {
               $do_vsite_extra->{$vok} = $do_vsite->{$vok};
               delete $do_vsite->{$vok};
            }
            if (is_array($do_vsite->{$vok})) {
                $do_vsite_extra->{$vok} = $do_vsite->{$vok}[0];
                delete $do_vsite->{$vok};
            }
        }

        if ($ip_override eq "1" && $override_ipAddr) {
            $do_vsite->{ipaddr} = $override_ipAddr;
        }

        #
        ### Idiotic Alias Search to make *really* sure no alias is already in use. 
        ### If an alias is already in use, we remove it from the Vsite we want to
        ### import before attempting the import. Silly stuff, but rather it imports
        ### than that it barfs half way through.
        #

        @new_webAliases = ();
        @new_mailAliases = ();

        foreach $alias (scalar_to_array($do_vsite->{webAliases})) {
            @oids_WA = $cce->find("Vsite", {'webAliases' => $alias});
            if (scalar(@oids_WA)) {
                print "webAlias '$alias' is already in use as webAlias on another Vsite. Removing it before import.\n";
            }
            else {
                push @new_webAliases, $alias;
            }
        }            
        foreach $alias (scalar_to_array($do_vsite->{mailAliases})) {
            @oids_MA = $cce->find("Vsite", {'mailAliases' => $alias});
            if (scalar(@oids_MA)) {
                print "mailAlias '$alias' is already in use as mailAlias on another Vsite. Removing it before import.\n";
            }
            else {
                push @new_mailAliases, $alias;
            }
        }
        $do_vsite->{webAliases} = array_to_scalar(@new_webAliases);
        $do_vsite->{mailAliases} = array_to_scalar(@new_mailAliases);

        #
        ### CREATE the main Vsite Object:
        #

        ($ok) = $cce->create("Vsite", $do_vsite, '');

        # Check result:
        if ($ok ne "1") {
            # Increment error counter:
            $errors++;
        }

        #
        ### Set all NameSpaces of the newly create User Object:
        #

        # Get Vsite-OID:
        $vsite_OID = &cce_find_vsite($cv);

        if (($errors eq '0') && ($vsite_OID)) {

            # Disk Quota adjustments (reduce by factor 1024):
            $do_vsite_extra->{Disk}->{quota} = $do_vsite_extra->{Disk}->{quota}/1024;

            # Loop through all NameSpaces:
            foreach $von ( keys %{ $do_vsite_extra } ) {
                # Perform SET transaction:
                ($ok) = $cce->set($vsite_OID, "$von", $do_vsite_extra->{$von});
                delete $do_vsite_extra->{$von};
                # Check result:
                if ($ok ne "1") {
                    # Increment error counter:
                    $errors++;
                }
            }
        }

        #
        # Import Tarballs (if any):
        #

        if ($config_only eq "0") {
            if (defined $Vsite_Tarballs->{$cv}) {

                @balls = (keys %{$Vsite_Tarballs->{$cv}});
                foreach $ball (@balls) {
                    $fileName = $Vsite_Tarballs->{$cv}->{$ball}[0]->{fileName};
                    $md5sum = $Vsite_Tarballs->{$cv}->{$ball}[0]->{md5sum};

                    # Get Vsite basedir:
                    ($ok, $obj_vsite) = $cce->get($vsite_OID);

                    # Path and Filename of tarball to import:
                    $unpack_this_file = $path_to_import . $fileName;

                    if ($ball eq "DocumentRoot") {
                        $unpack_target = $obj_vsite->{basedir} . "/web/";
                        $unpack_target_dir = $obj_vsite->{basedir} . "/web";
                    }
                    if ($ball eq "CGI_BIN") {
                        $unpack_target = $obj_vsite->{basedir} . "/web/cgi-bin/";
                        $unpack_target_dir = $obj_vsite->{basedir} . "/web/cgi-bin";
                    }
                    if ($ball eq "SSL") {
                        $unpack_target = $obj_vsite->{basedir} . "/certs/";
                        $unpack_target_dir = $obj_vsite->{basedir} . "/certs";
                    }

                    # Get 'prefered_siteAdmin' from 'Vsite' . 'PHP' Object:
                    ($ok, $obj_vsite_php) = $cce->get($vsite_OID, 'PHP');
                    $vsite_webOwner = $obj_vsite_php->{prefered_siteAdmin};
                    $vsite_grp = $obj_vsite->{name};
                    if ($vsite_webOwner eq "") {
                        $vsite_webOwner = 'nobody';
                    }
                    else {
                        if ($ball eq "DocumentRoot") {
                            # Keep track of whom we need to chown to later:
                            $Vsite_Chowns->{$cv}->{target} = $unpack_target;
                            $Vsite_Chowns->{$cv}->{uid} = $vsite_webOwner;
                            $Vsite_Chowns->{$cv}->{gid} = $vsite_grp;
                        }
                    }

                    # Unpack and chown to correct group:
                    unless ($unpack_target eq "/") {
                        &debug_msg("Importing Tarball ($fileName) for Vsite '$cv' to $unpack_target\n\n");
                        if ($ball eq "DocumentRoot") {
                            # If we're unpacking the 'DocumentRoot', then we remove our skelleton index.html first:
                            $indexHtml = $unpack_target . "index.html";
                            system("rm -f $indexHtml");
                        }
                        if ($ball eq "CGI_BIN") {
                            # Create /web/cgi-bin:
                            system("mkdir $unpack_target_dir");
                            system("chmod 755 $unpack_target_dir");
                            system("chown -R nobody:$vsite_grp $unpack_target_dir");
                        }

                        # Actual unpack:
                        system("pv $unpack_this_file | tar xzf - -C $unpack_target");
                        if ($ball ne "SSL") {
                            # Not dealing with an SSL tarball:
                            system("chown -R nobody:$vsite_grp $unpack_target");
                        }
                        else {
                            # Fix names of the respective certificate files:
                            if (-f "$unpack_target_dir/$Vsite_Extra->{$cv}->{SSL}->{SSLCertificateFile}") {
                                system("mv $unpack_target_dir/$Vsite_Extra->{$cv}->{SSL}->{SSLCertificateFile} $unpack_target_dir/certificate");
                            }
                            if (-f "$unpack_target_dir/$Vsite_Extra->{$cv}->{SSL}->{SSLCertificateKeyFile}") {
                                system("mv $unpack_target_dir/$Vsite_Extra->{$cv}->{SSL}->{SSLCertificateKeyFile} $unpack_target_dir/key");
                            }
                            if (-f "$unpack_target_dir/$Vsite_Extra->{$cv}->{SSL}->{SSLCACertificateFile}") {
                                system("mv $unpack_target_dir/$Vsite_Extra->{$cv}->{SSL}->{SSLCACertificateFile} $unpack_target_dir/ca-certs");
                            }
                            # Special perms for cert directory when dealing with SSL tarballs:
                            system("chown -R root:$vsite_grp $unpack_target");
                            system("chmod -R 640 $unpack_target");
                            system("chmod 2770 $unpack_target_dir");
                        }
                        &debug_msg("\n");
                    }
                }
            }
        }

        $retval = '';
        if ($errors eq "0") {
            $retval = "1";
        }
        return $retval;
}

sub verify_tarball_integrity {

    $num_Vsite_Tarballs = scalar(keys %{$Vsite_Tarballs});
    $num_User_Tarballs = scalar(keys %{$User_Tarballs});

    if ($num_Vsite_Tarballs eq "0") {
        &debug_msg("No Vsite Tarballs found!\n");
    }
    if ($num_User_Tarballs eq "0") {
        &debug_msg("No User Tarballs found!\n");
    }
    &debug_msg("Found Tarballs for $num_Vsite_Tarballs Vsites.\n\n");

    &debug_msg("##############################################\n");
    &debug_msg("Verifying MD5 Checksums for Vsite Tarballs:\n");
    &debug_msg("##############################################\n\n");

    foreach $vb ( keys %{ $Vsite_Tarballs } ) {
        @balls = (keys %{$Vsite_Tarballs->{$vb}});
        foreach $ball (@balls) {
            $fileName = $Vsite_Tarballs->{$vb}->{$ball}[0]->{fileName};
            $md5sum = $Vsite_Tarballs->{$vb}->{$ball}[0]->{md5sum};

            # Path and Filename of tarball to check:
            $check_this_file = $path_to_import . $fileName;

            unless (-f $check_this_file) {
                &debug_msg("WARNING: The Tarball $fileName is NOT present in $path_to_import!!\n");
            }
            # Perform check:
            $md5sumCheck = `md5sum $check_this_file`;
            chomp($md5sumCheck);
            $md5sumCheck =~ s/\s+/:/;
            @md5_clean = split ':', $md5sumCheck;
            # Handle results:
            if ($md5sum ne $md5_clean[0]) {
                &debug_msg("WARNING: The Tarball $fileName failed the MD5 Checksum test and is probably corrupted!\n");
            }
            else {
                &debug_msg("INFO: $fileName MD5 Checksum: OK\n");
            }
        }
    }
    &debug_msg("\n");

    &debug_msg("Found Tarballs for $num_User_Tarballs Users.\n\n");

    &debug_msg("##############################################\n");
    &debug_msg("Verifying MD5 Checksums for User Tarballs:\n");
    &debug_msg("##############################################\n\n");

    foreach $vb ( keys %{ $User_Tarballs } ) {
        @balls = (keys %{$User_Tarballs->{$vb}});
        foreach $ball (@balls) {
            $fileName = $User_Tarballs->{$vb}->{$ball}[0]->{fileName};
            $md5sum = $User_Tarballs->{$vb}->{$ball}[0]->{md5sum};

            # Path and Filename of tarball to check:
            $check_this_file = $path_to_import . $fileName;

            unless (-f $check_this_file) {
                &debug_msg("WARNING: The Tarball $fileName is NOT present in $path_to_import!!\n");
            }
            # Perform check:
            $md5sumCheck = `md5sum $check_this_file`;
            chomp($md5sumCheck);
            $md5sumCheck =~ s/\s+/:/;
            @md5_clean = split ':', $md5sumCheck;
            # Handle results:
            if ($md5sum ne $md5_clean[0]) {
                &debug_msg("WARNING: The Tarball $fileName failed the MD5 Checksum test and is probably corrupted!\n");
            }
            else {
                &debug_msg("INFO: $fileName MD5 Checksum: OK\n");
            }
        }
    }
    &debug_msg("\n");
}

sub get_current_user_sysdata {
    my $username = shift;
    my @user_info = getpwnam($username);
    my ($name,$passwd,$uid,$gid,$quota,$comment,$gcos,$dir,$shell) = getpwnam $username;
    return {
            'name' => $user_info[0],
            'uid' => $user_info[2],
            'group' => $user_info[3],
            'password' => $user_info[1],
            'comment' => $user_info[6],
            'homedir' => $user_info[7],
            'shell' => $user_info[8]
            };
}

sub fix_overquotas {

    &debug_msg("\n");
    &debug_msg("####################################################\n");
    &debug_msg("Making sure all Vsites and Users are OK on Quota:\n");
    &debug_msg("####################################################\n\n");
    @sites_over_quota = sites_over_quota();
    &debug_msg("\n");
    @users_over_quota = users_over_quota();
    &debug_msg("\n");
}

sub users_over_quota {
    my ($name, $null, $uid, $user_gid, $all_gid, $dir);
    my (@users_over_quota) = ();
    my @cceusers;

    # fetch all CCE users
    my @alluseroids = $cce->find('User', '');
    foreach my $entry (@alluseroids) {
        (my $ok, my $user) = $cce->get($entry);
        push(@cceusers,$user->{name});
        $User_OID->{$user->{name}}  = $user->{OID};
    }

    # now we do getpwent() and only lookup users who are CCE users
    setpwent();
    while (($name, $null, $uid, $user_gid, $null, $null, $null, $dir) = getpwent()) {
        my $userfound = 0;
        if (grep {$_ eq $name} @cceusers) {
            $userfound = 1;
        }

        if ($userfound==0) {
            next;
        }

        my $dev = Quota::getqcarg($dir);

        if (!$dev) {
            # $dev may not always be set. Has problems on extra-admins, which then causes the error:
            # Use of uninitialized value in subroutine entry at /usr/sausalito/swatch/bin/am_disk.pl line 348, <GEN1> line 27.
            # So if $dev is not defined, we set a safe default:
            $dev = "/home";
        }
        my ($used, $quota) = Quota::query($dev, $uid);

        if (! defined $quota || $quota == 0) {
            #&debug_msg ("No quota set on '$user', skipping.\n");
            next;
        }

        if ($used >= ($quota * 90/100)) { # 90 percent used
            $newQuota_disp = ceil((($used*'1.25')));
            $newQuota = ceil((($used*'1.25')/1000));
            &debug_msg ("User '$name' is over quota. Used '$used' of $quota. Raising quota to '$newQuota_disp'.\n");
            $cce->set($User_OID->{$name}, 'Disk', { 'quota' => $newQuota});
            push @users_over_quota, $name;
        }
    }
    endpwent();
    return @users_over_quota;
}

sub sites_over_quota {
    my @sites_over_quota = ();
    my %hash = ();

    # find all disks
    my $cce = new CCE;
    $cce->connectuds();
    my (@disks) = $cce->find('Disk', { 'isHomePartition' => 1 });

    # find all mountpoints
    my @mounts = ();
    foreach my $disk (@disks) {
        my ($ok, $obj) = $cce->get($disk);
        push @mounts, $obj->{mountPoint};
    }

    # this relies on Alpine's hashing scheme. if the hashing scheme changes
    # or this is installed on another product, then this will need to change.
    # find all numeric hashes
    my @hashdirs = ();
    foreach my $mount (@mounts) {
        if (-d "$mount/.sites") {
            opendir(SITEDIR, "$mount/.sites");
            my @dirs = map { "$mount/.sites/$_" } grep /^\d+$/, readdir(SITEDIR);
            push @hashdirs, @dirs;
            close(SITEDIR);
        }
    }

    # find all dirs in all hashes
    my @some_sites = ();
    foreach my $hash (@hashdirs) {
        opendir(HASH, $hash);
        @some_sites = grep !/^\./, readdir(HASH);
        close(HASH);

        # lookup dev
        my $dev = Quota::getqcarg($hash);
        my $is_group = 1;

        foreach my $site (@some_sites) {
            # lookup gid
            my ($name, $null, $gid) = getgrnam($site);

            # do query
            if ($gid) {
                my ($used, $quota) = Quota::query($dev, $gid, $is_group);

                if (! defined $quota || $quota == 0) {
                    # no quota set
                    next;
                }

                if ($used > $quota) { 
                    @Voids = $cce->find("Vsite", {"name" => $name});
                    $newQuota_disp = ceil((($used*'1.25')));
                    $newQuota = ceil((($used*'1.25')/1000));
                    &debug_msg("Vsite '$name' is over quota. Used '$used' of '$quota.' Raising quota to '$newQuota_disp'.\n");
                    $cce->set($Voids[0], 'Disk', { 'quota' => $newQuota});
                    push @sites_over_quota, $name;
                }
            }
        }
    }
    return @sites_over_quota;
}

sub root_check {
    my $id = `id -u`;
    chomp($id);
    if ($id ne "0") {
        #print "$0 must be run by user 'root'!\n\n";
        &help("$0 must be run by user 'root'!");
    }
}

sub apply_php_settings {

    my @vhosts = ();
    my (@vhosts) = $cce->findx('Vsite');

    &debug_msg("\n");
    &debug_msg("##############################################################################\n");
    &debug_msg("# Going through all Vsites to set the PHP settings to server wide defaults:\n");
    &debug_msg("##############################################################################\n\n");

    # Get PHP settings:
    @PHPoid = $cce->find("PHP");
    if (scalar(@PHPoid) eq "1") {
        ($ok, $PHP) = $cce->get($PHPoid[0]);
    }

    # Walk through all Vsites:
    for my $vsite (@vhosts) {
        ($ok, my $my_vsite) = $cce->get($vsite);

        &debug_msg("Processing Site: $my_vsite->{fqdn}\n");

        ($ok) = $cce->set($vsite, 'PHPVsite',{
            'max_execution_time' => $PHP->{max_execution_time},
            'safe_mode_exec_dir' => $PHP->{safe_mode_exec_dir},
            'upload_max_filesize' => $PHP->{upload_max_filesize},
            'max_input_time' => $PHP->{max_input_time},
            'safe_mode_gid' => $PHP->{safe_mode_gid},
            'safe_mode_protected_env_vars' => $PHP->{safe_mode_protected_env_vars},
            'allow_url_fopen' => $PHP->{allow_url_fopen},
            'memory_limit' => $PHP->{memory_limit},
            'safe_mode_include_dir' => $PHP->{safe_mode_include_dir},
            'safe_mode_allowed_env_vars' => $PHP->{safe_mode_allowed_env_vars},
            'allow_url_include' => $PHP->{allow_url_include},
            'register_globals' => $PHP->{register_globals},
            'safe_mode' => $PHP->{safe_mode},
            'post_max_size' => $PHP->{post_max_size},
            'force_update' => time()
           });
    }
    &debug_msg("\n");
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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