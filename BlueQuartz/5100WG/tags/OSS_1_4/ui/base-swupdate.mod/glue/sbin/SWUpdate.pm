#!/usr/bin/perl -I/usr/sausalito/perl

package SWUpdate;

require Exporter;
@ISA    = qw(Exporter);
@EXPORT = qw(swupdate_getsettings swupdate_unpack
	     swupdate_download swupdate_verifyuntar
	     swupdate_notify swupdate_version
	     swupdate_parselist swupdate_add_pkginfo 
	     swupdate_tmpdir swupdate_tmpfile
	     swupdate_checkdepend swupdate_compare
	     swupdate_install_pkginfo  swupdate_runscripts
	     swupdate_scriptdir swupdate_splashdir swupdate_localename
	     swupdate_fromccevers swupdate_rmunpacked);

use POSIX;
use URI::Escape;
use SendEmail;
use Sauce::Config;
use CCE;

# constants
my $appName = 'BlueLinQ/1.0';
my $wgetBin = "/usr/bin/wget -A $appName";
my $tar_cmd = '/bin/tar';

my $pkgprefix = '.swupdate';
my $localedir = '/usr/share/locale';

# cron, ui, and everything else
my @emailaddrs;

1;

# --- useful mappings

# to/from cce versions
sub toccevers
{
	my $ver = shift;
	return 'v' . $ver unless $ver =~ /^v/;
	return $ver;
}

sub swupdate_fromccevers
{
	my $ver = shift;
	$ver =~ s/^v//;
	return $ver;
}

# get file magic
sub get_magic
{
    my $file = shift;
    return `/usr/bin/file "$file"`;
}


sub get_product
{
    my ($build, $product);
    if (open(BUILD, '/etc/build')) {
	while (<BUILD>) {
	    if (/build\s+(\S+)/) {
		$build = $1;
	    }
	    if (/for\s+a\s+(\S+)\sin/) {
		$product = $1;
	    }
	}
	close(BUILD);
    }
    return ($product, $build);
}

sub swupdate_version
{
    return '1.0';
}

sub swupdate_name
{
    return 'swupdate';
}

sub swupdate_localename
{
    my ($vendor, $name, $version) = @_;
    my $string = "$pkgprefix:$vendor-$name-$version";
    $string =~ s/\./_/g;
    return $string;
}

sub swupdate_splashdir
{
    my ($vendor, $name, $version) = @_;
    my $webdir = '/usr/sausalito/ui/web';
    return "$webdir/$pkgprefix/$vendor/$name-$version";
}

sub swupdate_scriptdir
{
    my ($vendor, $name, $version, $stage) = @_;
    my $scriptdir = "/usr/sausalito/$pkgprefix";
    return "$scriptdir/$vendor/$name-$version/$stage";
}

sub swupdate_tmpdir
{
    my ($name) = @_;
    my $dir = '/home/tmp/' . $pkgprefix;
    return $name ? "$dir/$name" : $dir;
}

sub swupdate_tmpfile
{
    my ($name) = @_;
    $name = 'pkgtemp' unless $name;

    my $tmp = swupdate_tmpdir;
    my $path = $tmp . '/' . $name . '.';
    my $i = $$;
    while (-e $path . $i) {
	++$i;
    }

    $path .= $i;
    `mkdir -p $tmp`;
    `chmod -R 0700 $tmp`;
    unlink($path);
    sysopen(PATH, $path, O_WRONLY | O_CREAT | O_EXCL, 0600) or die;
    close(PATH);
    return $path;
}

sub swupdate_rmunpacked
{
    my $location = shift;

    return unless $location =~ /^file:\s*(\S+)/;
    my $dir = $1;
    return if $dir =~ /\.\./;

    my $tmp = swupdate_tmpdir;
    return unless $dir =~ /^$tmp/;
    `rm -rf $dir`;
}

sub set_proxy
{
    my $server = shift;
    my ($cce, $sysoid, $swobj, $oid, $ok);

    if (not $server) {
	$cce = new CCE;
	$cce->connectuds();
	($sysoid) = $cce->find('System');
	($ok, $swobj) = $cce->get($sysoid, 'SWUpdate');
	$cce->bye('SUCCESS');
    }

    # Get http and ftp proxy if they exist. If so, set correct environment
    # variables for wget
    $ENV{http_proxy} = $swobj->{httpProxy} if $swobj->{httpProxy};
    $ENV{ftp_proxy} = $swobj->{ftpProxy} if $swobj->{ftpProxy};
}

sub swupdate_getsettings
# get initial info. this stuff everything into the passed in
# object. if there's an error, it determines whether or not
# there was a previous error and returns an array with more information.
{
    my ($obj, $cceref) = @_;
    my (@serverOids, $sysobj, $swobj, $ok, $server);
    my @ret;

    my $cce;

    if ($cceref) {
	$cce = $cceref;
    } else {
	$cce = new CCE;
	$cce->connectuds();
    }

    ($sysoid) = $cce->find('System');
    ($ok, $sysobj) = $cce->get($sysoid, '');
    ($ok, $swobj) = $cce->get($sysoid, 'SWUpdate');
    if($obj->{autoupdate} == 1){
	(@serverOids) = $cce->findNSorted('SWUpdateServer', 'orderPreference', {autoUpdate=>1});
    }else{
	(@serverOids) = $cce->findNSorted('SWUpdateServer', 'orderPreference');
    }

    $obj->{serialNumber} = $sysobj->{serialNumber};

    # get swupdate server locations.
    $obj->{location_oid} = ();
    $obj->{location_status} = ();
    $obj->{location} = ();
    my $serverOid;
    foreach $serverOid (@serverOids) {
	($ok, $server) = $cce->get($serverOid, '');
	push @{$obj->{location_oid}}, $serverOid;
	push @{$obj->{location_status}}, $server->{status};
	push @{$obj->{location}}, $server->{location};
	# Get notificationMode from the SWUpdate servers.
	# All servers will share the same setting for this, so we
	# we just take the first one.
	if (!$obj->{notifymode}) {
	    $obj->{notifymode} = $server->{notificationMode} eq 'UpdatesOnly' ? 'updates' : 'all';
	}
    }

    # figure out email addresses if necessary
    @emailaddrs = $cce->scalar_to_array($swobj->{updateEmailNotification});

    # Find all installed packages to send to server
    my @oids = $cce->find('Package', {'installState' => 'Installed'});
    my (@installed, $i);
    for ($i = 0; $i <= $#oids; $i++) {
	($ok, $j) = $cce->get($oids[$i], '');
	$installed[$i] = $j->{vendor} . ':' . $j->{name} . ':' . 
	    swupdate_fromccevers($j->{version});
    }

    $i = $cce->array_to_scalar(@installed);
    $i = uri_escape($i);
    $obj->{installed} = $i;

    ($obj->{product}, $obj->{build}) = get_product();

    # check for problems
    unless ($obj->{build} and $obj->{product} and
	    $obj->{location}) {
	my $info;
	$info = ($obj->{location}) ? 'nosysinfo' : 'nolocation';
	$cce->set($sysoid, 'SWUpdate', { 'status' => 'down' });
	$cce->bye('SUCCESS') unless $cceref;
	return (-1, $swobj->{status}, $info);

    } else {
	$cce->set($sysoid, 'SWUpdate', { 'status' => 'up' });
    }

    $cce->bye('SUCCESS') unless $cceref;
    return (0);
}


# verify and untar a tar/tar.gz file that's been optionally signed
# this will delete the original tarball if it's successful. 
sub swupdate_verifyuntar
{
    my ($file, $destdir, $sigp) = @_;
    my $gpg = 'HOME=/root /usr/bin/gpg --quiet --batch --no-tty';
    my $gunzip = '/bin/gunzip -q -c';
    my $bunzip2 = '/usr/bin/bunzip2 -q -c';
    my $err;

    # first, check to see what file type it is
    $type = get_magic($file);
    my $local = $file;
    if (($type =~ /PGP\s+armored\s+data\s+signed\s+message/) or
	($type =~ /data$/)) {

	# verify
	`$gpg --verify $file > /dev/null 2>&1`;
	return (-1, 'badsig') if $?;

	# convert the file
	`$gpg --output $local.tar $local > /dev/null 2>&1`;
	$local .= '.tar' if -e "$local.tar";
	$type = get_magic($local);
    } elsif ($sigp) {
	return (-1, 'nosig');
    }
	
    # now, see if it's compressed
    if ($type =~ /(\S+)\s+compressed/) {
	my $prog = ($1 eq 'bzip2') ? $bunzip2 : $gunzip;
	`$prog $local > $local.unzipped 2>/dev/null`;
	$err = $?;
	unlink($local);
	$local .= '.unzipped';
	if ($err) {
	    unlink($local);
	    return (-1, 'nounzip');
	}
	$type = get_magic($local);
    }

    # it has to be a tar file
    unless ($type =~ /tar/) {
	unlink($local);
	return (-1, 'notar');
    }

    # untar the file now 
    `mkdir -p $destdir`;
    `chmod -R 700 $destdir`;
    `$tar_cmd xf $local -C $destdir 2>/dev/null`;
    $err = $?;
    unlink($local);
    return (-1, 'cantuntar') if $err;
    unlink($file);
    return (0);
}


sub setProgress 
{
  my ($cce, $oid, $message, $progress) = @_;
  my %settings;

  $settings{message} = $message if defined($message);
  $settings{progress} = $progress if defined($progress);
  my ($ok) = $cce->set($oid, 'SWUpdate', \%settings);
  return $ok;
}

# this unpacks a package and stuffs information into cce
# it also moves any auxiliary files into the correct place.
sub swupdate_unpack
{
    my ($file, $cceref, $status, $sigp, $oid) = @_;
    my $path = swupdate_tmpdir . "/pkgunpack.$$";
    my (%settings, $cce);

    # don't allow relative paths in the file name.
    return (-1, 'badpath') if $file =~ /\.\./;

    # get rid of any existing file and remove the package as well.
    `rm -rf $path`;

    if ($cceref) {
	$cce = $cceref;
    } else {
	$cce = new CCE;
	$cce->connectuds;
    }

    # unpack the basic package.
    `mkdir -p $path`;
    setProgress($cce, $status, '[[base-swupdate.verifyingPackage]]') if $status;
    my $size = -s $file;
    my ($err, $info) = swupdate_verifyuntar($file, $path, $sigp);
    if ($err) {
	`rm -rf $path`;
	setProgress($cce, $status, '[[base-swupdate.badFormat]]', 100) if $status;
	$cce->bye('SUCCESS') unless $cceref;
	return ($err, $info);
    }

    # install the info bits and tell apache to restart itself
    unless (open(PL, "$path/packing_list") and 
	    (%settings = read_pkgheader(\*PL, undef))) {
	`rm -rf $path`;
	setProgress($cce, $status, '[[base-swupdate.badPackage]]', 100) if $status;
	$cce->bye('SUCCESS') unless $cceref;
	return (-1, 'nopackinglist');
    }
    close(PL);

    # now, attempt to add the package. always make it visible at this stage.
    # also clear out things that are only used by the update server.
    $settings{size} = $size;
    $settings{location} = 'file:' . $path;
    $settings{url} = '';

    ($oid, $info) = swupdate_add_pkginfo($cce, \%settings, 'visible', $oid);
    if ($oid lt 0) {
	`rm -rf $path`;
	my $string = ($info eq 'alreadyinstalled') ? '[[base-swupdate.packageAlreadyInstalled]]' : '[[base-swupdate.badFormat]]';
	setProgress($cce, $status, $string, 100) if $status;
        $cce->bye('SUCCESS') unless $cceref;
    	return (-1, $info); 
    }
    $cce->bye('SUCCESS') unless $cceref;
    
    # move files into the appropriate place
    swupdate_install_pkginfo("$path/pkginfo", $settings{vendor},
			     $settings{name}, $settings{version});
    return ($oid, \%settings);
}


sub swupdate_download
{
    my ($file, $location, $get, $status, $uname, $passwd) = @_;
    setProgress($status, "[[base-swupdate.downloading]]") if $status;

    set_proxy();
#
#wget handles https urls seamlessly.  no need to have a seprate
#case just for them...
#
    return swupdate_normal_download($file, $location, $get, $status, $uname, $passwd);
}

# depending upon the $info field, this does different things.
sub swupdate_normal_download
{
    my ($file, $location, $get, $status, $uname, $passwd) = @_;
    my (%error, $type);
    my $query = 1;
    my $percent;

    $error{location} = $location;
    unlink($file);

    my $auth = "";
    if(defined $uname && defined $passwd){
        $auth = "--http-user $uname --http-passwd $passwd";
    }


    return (-1, 'down', 'queryerror', \%error) unless
	open(WGET, "$wgetBin -t 2 $auth -T 60 \"$location$get\" -O $file 2>&1 |");

    while (<WGET>) {
	if (/Host\s+not\s+found/i) {
	    close(WGET);
	    return (-1, 'down', 'hostnotfound', \%error);
	}
	    
	if (/404\s+Not\s+Found/i) {
	    close(WGET);
	    return (-1, 'down', 'filenotfound', \%error);
	}
      
	if (/refused/i) {
	    close(WGET);
	    return (-1, 'down', 'refusedconnect', \%error);
	}
	
	if (/\[\s*(\d+)\%\]/) {
	    $percent = $1;
	    my $filePercent = ceil($1);
	    setProgress($status, "[[base-swupdate.dlPercent,percent=$filePercent,file=$location]]", ceil($percent)) if $status;
	}
    }

    # we didn't get a file. error out.
    unless (-f $file and -s $file) {
	return (-1, 'down', 'queryerror', \%error);
    }

    # we check for a couple strings if it's a text file
    $type = get_magic($file);    
    if ($type =~ /text/) {
	if (open(FILE, $file)) {
	    while (<FILE>) {
		if (/No packages available/i) {
			close(FILE);
			return (-1, 'up', 'nopkgavail', \%error);
		} elsif (/^ERROR/) {
			close(FILE);
			return (-1, 'down', 'queryerror', \%error);
		}
	    }
	    close(FILE);
	}
    }

    # we got the file. 
    return (0);
}


sub read_pkgheader
{
    my ($file, $line) = @_;
    my ($inpackage, @products, @visible, @depend, @obsolete, @rpms, @srpms);
    my %settings;
    
    if ($line =~ /^\[Package -- Version=1.0\]/i) {
	$inpackage = 1;
    } else {
	while (<$file>) {
	    next if /^\s*#/;
	    if (/^\[Package -- Version=1.0\]/i) {
		$inpackage = 1;
		last;
	    }
	}
    }

    return undef unless $inpackage;
    %settings = ();
    while (<$file>) {
	next if /^\s*#/;
	next unless /\S/;
	last if /^\[\/Package\]/;
	
	if (/^Category:\s*(\S+)/i) {
	    $settings{category} = $1;
	    next;
	}

	if (/^Splash:\s*(.+)/i) {
	    $settings{splashPages} = $1;
	    next;
	}

	if (/^EncryptionFormat:\s*(\S+)/i) {
	    $settings{encryptionFormat} = $1;
	    next;
	}

	if (/^FileName:\s*(\S+)/i) {
	    $settings{fileName} = $1;
	    next;
	}

	if (/^Location:\s*(\S+)/i) {
	    $settings{location} = $1; 
	    next;
	}

	if (/^Copyright:\s*(.+)/i) {
	    $settings{copyright} .= $1;
	    next;
	}
	
	if (/^ShortDesc:\s*(.+)/i) {
	    $settings{shortDesc} .= $1;
	    next;
	}
	
	if (/^LongDesc:\s*(.+)/i) {
	    $settings{longDesc} .= $1;
	    next;
	}

	if (/^License:\s*(.+)/i) {
	    $settings{licenseDesc} .= $1;
	    next;
	}

	if (/^Name:\s*(\S+)/i) {
	    $settings{name} = $1;
	    next;
	}

	if (/^NameTag:\s*(.+)\n/i) {
	    $settings{nameTag} = $1;
	    next;
	}

	if (/^Product:\s*(\S+)/i) {
	    push @products, $1;
	    next;
	}

	if (/^PackageType:\s*(\S+)/i) {
	    $settings{packageType} = $1 eq 'update' ? 'update' : 'complete';
	    next;
	}

	if (/^Options:\s*(.+)\n/i) {
	    $settings{options} = $1;
	    next;
	}
	
	if (/^Size:\s*(\d+)/i) {
	    $settings{size} = $1;
	    next;
	}

	if (/^InfoURL:\s*(\S+)/i) {
	    $settings{url} = $1;
	    next;
	}
	
	if (/^InfoURLOptions:\s*(.+)\n/i) {
	    $settings{urloptions} = $1;
	    next;
	}
	
	if (/^Vendor:\s*(\S+)/i) {
	    $settings{vendor} = $1;
	    next;
	}

	if (/^VendorTag:\s*(.+)\n/i) {
	    $settings{vendorTag} = $1;
	    next;
	}

	if (/^Version:\s*(\S+)/i) {
	    $settings{version} = toccevers($1); 
	    next;
	}

	if (/^VersionTag:\s*(.+)\n/i) {
	    $settings{versionTag} = $1; 
	    next;
	}

	#  vendor:package [><=] version
	if (/^Depend:\s*(\S+):(\S+)\s*(?:([!>=<]*)\s*(\S+))*/i) {
	    push @depend, "$1:$2:$3:$4";
	    next;
	}

	if (/^VisibleDepend:\s*(\S+):(\S+)\s*(?:([!>=<]*)\s*(\S+))*/i) {
	    push @visible, "$1:$2:$3:$4";
	    next;
	}

	# obsoletes
	if (/^Obsoletes:\s*(\S+):(\S+)\s*(?:([>=<]*)\s*(\S+))*/i) {
	    push @obsolete, "$1:$2:$3:$4";
	    next;
	}

	if (/^RPM:\s*(\S+)/i) {
	    push @rpms, $1;
	    next;
	} 

	if (/^SRPM:\s*(\S+)/i) {
	    push @srpms, $1;
	    next;
	} 

    }
    my $domain = swupdate_localename($settings{vendor}, $settings{name},
				     $settings{version});
    
    # prepend domains on a bunch of tags
    $settings{shortDesc} = prepend_domain($domain, $settings{shortDesc}) if $settings{shortDesc};
    $settings{longDesc} = prepend_domain($domain, $settings{longDesc}) if $settings{longDesc};
    $settings{nameTag} = prepend_domain($domain, $settings{nameTag}) if $settings{nameTag};
    $settings{vendorTag} = prepend_domain($domain, $settings{vendorTag}) if $settings{vendorTag};
    $settings{copyright} = prepend_domain($domain, $settings{copyright}) if $settings{copyright};
    $settings{versionTag} = prepend_domain($domain, $settings{versionTag}) if $settings{versionTag};
    $settings{licenseDesc} = prepend_domain($domain, $settings{licenseDesc}) if $settings{licenseDesc};

    $settings{product} = CCE->array_to_scalar(@products) if @products;
    $settings{visibleList} = CCE->array_to_scalar(@visible) if @visible;
    $settings{dependencyList} = CCE->array_to_scalar(@depend) if @depend;
    $settings{obsoleteList} = CCE->array_to_scalar(@obsolete) if @obsolete;
    $settings{RPMList} = CCE->array_to_scalar(@rpms) if @rpms;
    $settings{SRPMList} = CCE->array_to_scalar(@srpms) if @srpms;
    return %settings;
}


sub swupdate_parselist
{
    my ($file, $packages) = @_;
    my ($error, $ok);

    return -1 unless open(PL, $file);

    while (<PL>) {
	next if /^\s*#/;
	if (/\[PackageList -- Version=1.0\]/i) {
	    $ok = 1;
	    last;
	}
	next;
    }
	
    unless ($ok) {
	close(PL);
	return -1;
    }

    while (<PL>) {
	my %package;
	%package = read_pkgheader(\*PL, $_);
	# make sure crucial information exists
	if ($package{version} and $package{name} and $package{vendor} and 
	    ($package{location} or $package{url})) {
	    $package{installState} = 'Available';
	    push @$packages, \%package;
	}
    }
    close(PL);
    return scalar(@$packages);

}


sub swupdate_runscripts
{
    my ($path) = @_;
    my (@scripts, $err);

    if (opendir(DIR, $path)) {
	while ($_ = readdir(DIR)) {
	    next if /^\./;
	    push @scripts, "$path/$_";
	}
    }
    closedir(DIR);

    foreach $path (sort @scripts) {
	$err = system($path);
	return -1 if $err;
    }
    return 0;
}


# compare versions. we check for the following:
# blah
# blah >=< version
# blah != version
# blah !
sub swupdate_compare
{
    my ($deps, $pkg, $reverse) = @_;
    my @depends = @$deps;
    my ($i, $compare, $sign);
    my ($version, $eq, $lt, $gt);

    foreach $i (@depends) {
	$i =~ /^(\S+):(\S+):(\S*):(\S*)/;
	$version = $pkg->{$1}{$2};
	$compare = $3;

	# handle the following cases:
	# no package !         -- okay
	# package !            -- not okay
	# no package           -- not okay
	# package              -- okay
	next if ($compare =~ /!/) and not defined($version);
	return -1 if defined($version) and ($compare eq '!');
	return -1 unless defined($version);
	next unless $compare;

	# we have to do version checks. 
	if ($reverse) {
	    $depversion = $version;
	    $version = $4;
	} else {
	    $depversion = $4;
	}
	$eq = $lt = $gt = 0;
	$sign = compareVersion($version, $depversion);
	return $sign ? 0 : -1 if ($compare eq '!=');

	$eq = 1 if (($compare =~ /=/) and ($sign == 0));
	$lt = 1 if (($compare =~ /</) and ($sign < 0)); 
	$gt = 1 if (($compare =~ />/) and ($sign > 0));
	next if ($eq or $lt or $gt);
	return -1;
    }
    return 0;
}

# do a package dependency check. dependencies fail on the following 
# conditions:
#     packages don't match
#     dependencies don't match
#     the package has been obsoleted
# output:  0 -> okay
#         -2 -> bad because it's obsolete -- hide
#         -1 -> bad -- hide
#          1 -> bad -- show
sub swupdate_checkdepend
{
    my ($pkg, $cceref) = @_;
    my ($cce, $i, $ok, @depends);

    if ($cceref) {
	$cce = $cceref;
    } else {
	$cce = new CCE;
	$cce->connectuds;
    }
    
    # first, check against the product field if it's there.
    # we don't show up if the product field doesn't match. 
    if ($pkg->{product}) {
	my @product_array = $cce->scalar_to_array($pkg->{product});
	my ($product) = get_product();
	# note: we accept perl regexes for the product field
	foreach $i (@product_array) {
	    next unless $product =~ /^$i$/;
	    $ok = 1;
	    last;
	}
	
	unless ($ok) {
	    $cce->bye('SUCCESS') unless $cceref;
	    return -3;
	}
    }

    my @oids = $cce->find('Package', {installState => 'Installed'});
    my (%packages, $obj);

    # save version numbers. also check to see if the package has 
    # been obsoleted. if we get a match, we're an obsolete package.
    my %search;
    $search{$pkg->{vendor}}{$pkg->{name}} = $pkg->{version};
    foreach $i (@oids) {
	($ok, $obj) = $cce->get($i); 
	if ($obj->{obsoleteList}) {
	    my @list = $cce->scalar_to_array($obj->{obsoleteList});
	    foreach $ok (@list) {
		if (swupdate_compare([$ok], \%search) eq 0) {
		    $cce->bye('SUCCESS') unless $cceref;
		    return -2;
		}
	    }
	}
	$packages{$obj->{vendor}}{$obj->{name}} = $obj->{version};
    }
    
    # everything's okay. see if there are any package dependencies 
    unless ($pkg->{dependencyList} or $pkg->{visibleList}) {
	$cce->bye('SUCCESS') unless $cceref;
	return 0;
    }
    
    # need to do dependency checking
    # for visible dependencies, we return 1 on error.
    $ok = 0;
    if ($pkg->{visibleList}) {
	@depends = $cce->scalar_to_array($pkg->{visibleList});
	$ok = 1 if (swupdate_compare(\@depends, \%packages) lt 0);
    }

    # other dependencies return -1 on error.
    if ($pkg->{dependencyList}) {
	@depends = $cce->scalar_to_array($pkg->{dependencyList});
	$ok = -1 if (swupdate_compare(\@depends, \%packages) lt 0);
    }
    $cce->bye('SUCCESS') unless $cceref;
	
    return $ok;
}


################################################################
# swupdate_add_pkginfo
# Adds the specified package object in CCE if applicable
################################################################
sub swupdate_add_pkginfo {
    my ($cce, $obj, $visible, $oldoid) = @_;
    my ($OID, $success, $installed);

    # see if package is installed
    ($OID) = $cce->find("Package", {'name' => $obj->{name},
				    'vendor' => $obj->{vendor},
				    'installState' => 'Installed'
				    });
    if ($OID) {
	# installed - see if we have a newer version
	($success, $installed) = $cce->get($OID, '');
	return (-1, 'alreadyinstalled') if (compareVersion($obj->{version}, 
				     $installed->{version}) <= 0);
    }

    # see if the package already exists. if so, just overwrite it.
    ($OID) = $oldoid ? $oldoid : $cce->find('Package', {'name' => $obj->{name},
				    	    'vendor' => $obj->{vendor},
				    	    'version' => $obj->{version},
				    	    'installState' => 'Available' }
				            );
    
    # do a dependency check. set the isVisible flag if it fails.
    my $depend = swupdate_checkdepend($obj, $cce);
    $obj->{isVisible} = ($depend lt 0 and not $visible) ? '' : 'true';
    if ($OID gt 0) {
	# wipe out any temp files that might be lying around if
	# we successfully set the object.
	my $myobj;
	($success, $myobj) = $cce->get($OID, '');
	($success) = $cce->set($OID, '', $obj);
	swupdate_rmunpacked($myobj->{location}) if $success;
    } else { 
	($success) = $cce->create('Package', $obj);
	$OID = $cce->oid();
    }
    return $success ? $OID : (-1, 'badpkgfmt');
}


# notify admin of swupdate status. there are a bunch of messages
# that only get sent once per server state change.
sub swupdate_notify
{
    my ($invocation, $info, $error, $obj) = @_;
    my ($subject, $body);

    if ($error eq 'nosysinfo') {
	$subject = '[[base-swupdate.NoSystemInfoSubject]]';
	$body = '[[base-swupdate.NoSystemInfoBody]]';

    } elsif ($error eq 'nolocation') {
	$subject = '[[base-swupdate.NoSWUpdateServerSubject]]';
	$body = '[[base-swupdate.NoSWUpdateServerBody]]';

    } elsif ($error eq 'nopkgavail') {
	$subject = '[[base-swupdate.NoPackagesSubject]]';
	$body = '[[base-swupdate.NoPackagesBody]]';

    } elsif ($error =~ /(?:queryerror|filenotfound|hostnotfound|refusedconnect)/) {
	$subject = '[[base-swupdate.QueryErrorSubject]]';
	$body = '[[base-swupdate.NoPackageListBody,location=' . (ref $obj->{location} eq "ARRAY" ? $obj->{location}->[0] : $obj->{location}) . ']]';

    } elsif ($error eq 'badpkgfmt') {
	$subject = '[[base-swupdate.QueryErrorSubject]]';
	$body = '[[base-swupdate.BadPackageFmtBody,location=' . (ref $obj->{location} eq "ARRAY" ? $obj->{location}->[0] : $obj->{location}) . ']]';

    } elsif ($error) {
	$subject = '[[base-swupdate.QueryErrorSubject]]';
	$body = '[[base-swupdate.UnknownErrorBody,location=' . $obj->{location} . ',error='. $error . ']]';

    } else {
	# okay. 
	my @array = @$obj;
	my $ref;
	
	$subject = '[[base-swupdate.NewUpdatesSubject]]';
	$body = "[[base-swupdate.NewUpdatesBody]]\n\n";
	foreach $ref (@array) {
	    my $name = $ref->{nameTag} ? $ref->{nameTag} : $ref->{name};
	    my $vendor = $ref->{vendorTag} ? $ref->{vendorTag} : $ref->{vendor};
	    my $version = ($ref->{versionTag}) ? $ref->{versionTag} : swupdate_fromccevers($ref->{version});
	    $body .= "[[base-swupdate.nameField]]: $name\n";
	    $body .= "[[base-swupdate.versionField]]: $version\n";
	    $body .= "[[base-swupdate.vendorField]]: $vendor\n";
	    $body .= "[[base-swupdate.copyrightField]]: $ref->{copyright}\n";
	    $body .= "[[base-swupdate.typeField]]: [[base-swupdate.$ref->{packageType}]]\n";
	    $body .= "[[base-swupdate.descriptionField]]: $ref->{shortDesc}\n\n";
	}
    }

    if ($invocation eq 'ui' || $invocation eq 'autoupdate') {
	if ($error) {
	    # on errors, we want to spit out the body
	    print($body."\n");
	    exit 1;
	} else {
	    # otherwise, we want to spit out the subject
	    print($subject."\n");
	    exit 0;
	}
    } elsif ($invocation eq 'cron') {
	swupdate_email($subject, $body, @emailaddrs) unless $info eq 'INFO';
    } else {
	swupdate_print('subject,body', $subject, $body);
    }
}


sub swupdate_print
{
    my ($info, $subject, $body) = @_;
    my $i18n = new I18n;
 
    $i18n->setLocale(I18n::i18n_getSystemLocale());
    print $i18n->interpolate($subject) . "\n" if $info =~ /subject/;
    print $i18n->interpolate($body) . "\n" if $info =~ /body/;
}


################################################################
# swupdate_email
#
# Sends alert email to email addresses in @emailAlert
# Takes an Errorcode for a specific error.  0 if no error (in
# which case we just email about new Packages marked as Update)
################################################################
sub swupdate_email {
  my ($subject, $body, @addrs) = @_;
  my $address;
  
  foreach $address (@addrs) {
      SendEmail::sendEmail($address, Sauce::Config::groupdir_owner, 
			   $subject, $body);
  }      
}


# move the package info to the appropriate place
# this info gets removed when we destroy the package object
sub swupdate_install_pkginfo
{
    my ($dir, $vendor, $name, $version) = @_;
    my $localename = swupdate_localename($vendor, $name, $version);
    my ($lang, $dest);

    # move splash screens
    umask(022);
    my $splash = swupdate_splashdir($vendor, $name, $version);
    `mkdir -p $splash`;

    # make sure that we can reach the files. make sure that the permissions
    # are sane as well.
    if (-d "$dir/splash") {
	`cd $dir/splash; tar cBf -  . | (cd $splash; tar xBf -)`;
	`find $splash -type f | xargs chmod ug-s > /dev/null 2>&1`;
	`find $splash \\( -type d -o -name index.cgi \\) | xargs chmod 755 > /dev/null 2>&1`;
    }

     # now, move locales. we have to deal with the fact our conception
     # of version strings is slightly different than the developers.
     $version = swupdate_fromccevers($version);
     if (opendir(LOCALEDIR, "$dir/locale")) {
         while ($lang = readdir(LOCALEDIR)) {
	     next if $lang =~ /^\./;
             if (opendir(LOCALE, "$dir/locale/$lang")) {
                 my $locale;
                 while ($locale = readdir(LOCALE)) {
		     next if $locale =~ /^\./;
		     next unless $locale =~ /\.mo$/;
		     $dest = "$localedir/$lang/LC_MESSAGES/${localename}.mo";
		     `mkdir -p $localedir/$lang/LC_MESSAGES`;
		     `chmod -R 0755 $localedir/$lang/LC_MESSAGES`;
                     `cp $dir/locale/$lang/$locale $dest`;
		     chmod (0644, $dest);
		     last;
                 }
		 closedir(LOCALE);
             }
         }
	 closedir(LOCALEDIR);
     }
}

################################################################
# compareVersion()
#
# Takes two strings version info. returns the following:
#  1   first > second
#  0   first == second
# -1   first < second
################################################################
sub compareVersion {
  my($firstVer, $secondVer) = @_;
  $firstVer = swupdate_fromccevers($firstVer);
  $secondVer = swupdate_fromccevers($secondVer);
  
  my @firstVerArray = split(/\./, $firstVer);
  my @secondVerArray = split(/\./, $secondVer);
  my $index = 0;
  my ($size, $pad);

  # get largest size of array
  if ($#firstVerArray > $#secondVerArray) {
    $size = $#firstVerArray;
  } else {
    $size = $#secondVerArray;
  }
  
  while ($index <= $size) {
    # pad with 0's to catch compares with 2.0 & 2.0.0 (same) or 2.0 & 2.0.1 (2nd greater)
    if ($#firstVerArray < $index) {
      $firstVerArray[$index] = "0";
    } elsif ($#secondVerArray < $index) {
      $secondVerArray[$index] = "0";
    }
    return 1 if ($firstVerArray[$index] > $secondVerArray[$index]);
    return -1 if ($firstVerArray[$index] < $secondVerArray[$index]);
    $index++;
  }
  return 0;
}
  
sub prepend_domain 
{    
    my ($domain, $string) = @_;

    $string =~ s/\[\[([^\s\.]+)\]\]/\[\[${domain}\.$1\]\]/g;
    return $string;
}
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
