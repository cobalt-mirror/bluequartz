package Backup;
$VERSION = 1.0;
use strict;
use vars qw( $AUTOLOAD );
use Carp;
use CCE;
use I18n;
use I18nMail;
use Net::FTP;
use POSIX;

# $Id: Backup.pm 201 2003-07-18 19:11:07Z will $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

## Backup
# Attribs which should be set at create:
#	create = 1
#	name
#	users
#	groups
#	fileset (optional)
#	backupconfig (optional)
#	ftp (optional)

## Restore
# Attribs which should be set at create:
#	create = 0
#	ftp (optional)

## General
# Attribs which will be set by mounting:
#	method
#	username (if applicable)
#	password (if applicable)
#	fsmount
#	fsdir
#	backupdir

{
# Encapsulated class data

	my %_attr_data = 
	( 			   # Default			# Access
		# control data and data used when backing up/restoring
		_backuparg	=> ['-pscf',			'read'],
		_backupcmd	=> ['/bin/tar',			'read'],
		_extractargs	=> ['-xf',			'read/write'],
		_tarscript	=> ['/usr/local/sbin/multi-volume.pl',	'read'],
		_label		=> ['???',			'read/write'],
		_pid		=> [$$,				'read'],
		_log		=> ['/var/log/backup.log',	'read'],
		_version	=> ["1.0",			'read'],

		# turn on debugging/testing
		_debug		=> [0,				'read/write'],
		_test		=> [0,				'read/write'],

		# flag for whether we need to re-enable CCE
		_enablecce	=> [1,				'read/write'],

		# CCE lockfile file handle
		_lockfile	=> [0,				'read/write'],

		# what type of backup target
		_method		=> ['???',			'read/write'],

		# network share name
		_fsmount	=> ['???',			'read/write'],

		# subdirectory on that share
		_fsdir		=> ['',				'read/write'],

		# local mountpoint, on which $fsmount goes (e.g.: /tmp/Xf92s/)
		_mount		=> ['',				'read/write'],
		# local/mounted directory to which the backup set is stored
		_backupdir	=> ['???',			'read/write'],

		# local directory to be treated as the base for the restore
		_restoredir	=> ['???',			'read/write'],

		# flag indicating whether to actually perform writes
		_create		=> ['1',			'read/write'],

		# flag indicating whether config info is backed up
		_backupconfig   => ['0',                        'read/write'],

		# the name of this backup
		_name           => ['???',                      'read/write'],

		# the fileset to backup (age in days, 0 = all files)
		_fileset        => ['???',                      'read/write'],

		# the maximum size of a file in the dataset (k)
		_tapelength	=> [256000,			'read'],
		# is a backup currently pending?
		_pending	=> [0,				'read/write'],
	
		# list of users/groups to backup
		_users		=> ['???',			'read/write'],
		_groups		=> ['???',			'read/write'],

		# usernamer/password used to access network mount
		_username	=> ['???',			'read/write'],
		_password	=> ['???',			'read/write'],

		# time at which we start/stop the backup
		_starttime	=> ['???',			'read/write'],
		_stoptime	=> ['???',			'read/write'],

		# flag identifying error cases
		_errors         => [0,                          'read/write'],
		_erroring	=> [0,				'read/write'],

		# flag and ftp object reference
		_ftp		=> ['0',			'read/write'],
		_ftpbase	=> ['/home/tmp/ftpbkup/',	'read/write'],
		_ncftpconfig	=> ['???',			'read/write']
	);

	# Is the variable accessable
	sub _accessible
	{
		my ($self, $attr, $mode) = @_;
		$_attr_data{$attr}[1] =~ /$mode/
	}

	# Return default value
	sub _default_for
	{
		my ($self, $attr) = @_;
		$_attr_data{$attr}[0];
	}

	# Get list of possible values
	sub _standard_keys
	{
		keys %_attr_data;
	}
}

##
##
## Exported methods: these are called from outside the object
##
##

sub new
# new - create new backup object
{
	my ($caller, %arg) = @_;
	my $caller_is_obj = ref($caller);
	my $class = $caller_is_obj || $caller;
	my $self = bless {}, $class;
	
	foreach my $attribute ( $self->_standard_keys() ) {
		my ($argument) = ($attribute =~ /^_(.*)/);
		if (exists $arg{$argument})
			{$self->{$attribute} = $arg{$argument}}
		elsif ($caller_is_obj)
			{$self->{$attribute} = $caller->{$attribute}}
		else 
			{$self->{$attribute} = $self->_default_for($attribute)}
	}
	return $self;
}

sub AUTOLOAD
# AUTOLOAD -- Fallback method
#
# Arguments: New value for encapsulated data
# Return:    Current value on get, undef on set
{
	no strict "refs";
	my ($self, $newval) = @_;

	# retrieve variables
	if ($AUTOLOAD =~ /.*::get(_\w+)/ && $self->_accessible($1,'read')) {
		my $attr_name = $1;
		*{$AUTOLOAD} = sub { return $_[0]->{$attr_name}; };
		return $self->{$attr_name};
	}

	# set variables
	if ($AUTOLOAD =~ /.*::set(_\w+)/ && $self->_accessible($1,'write')) {
		my $attr_name = $1;
		*{$AUTOLOAD} = sub { $_[0]->{$attr_name} = $_[1]; return};
		$self->{$1} = $newval;
		return;
	}

	# Method doesn't exist...
	die "No such method: $AUTOLOAD\n";
}

sub DESTROY
# DESTROY -- Object clean up method.
#
# o Unmount any mounts created during backup or restore
# o Remove temporary directories created for mounted filesystems
# o Clean up "cache" directory if FTP was used
# o Delete pending history
{
	my $self = shift;
	my $budir = $self->get_backupdir();
	my $method = $self->get_method();

	# exit if we did not NEW the object	
	if ($self->get_pid() != $$) {
		return;
	}

	# remove any mounts laying around in /tmp
	if ( $self->get_mount() ) {
		my $mounted = $self->get_mount();
		if ($mounted) {
			$self->dprint("unmounting $mounted\n");
			$self->_log_system("umount $mounted");
			$self->dprint("removing $mounted\n");
			$self->_log_system("rmdir $mounted");
		}
	}
	
	$self->delete_pending_history();

	# remove local files if using ftp
	if ( $self->get_ftp && -d $self->get_backupdir() ) { 
		$self->dprint("removing $budir\n");
		$self->_log_system("/bin/rm -rf $budir");

		my $ftp_base = $self->get_ftpbase();
		if(-d $ftp_base) {
			$self->dprint("removing $ftp_base\n");
			$self->_log_system("/bin/rm -rf $ftp_base");
		}
	}

	if ($self->get_enablecce()) {
		$self->cced_start();
	}
}

sub mount
# mount -- Generic mount method (determine method and dispatch)
#
# Arguments: Backup method, base mount point
# Returns:   Undef on failure, 1 on success
{
	my $self = shift || return; 
	my $method = shift || return;
	my $mountpt = shift || return;
	my $username = shift || "";
	my $password = shift || "";

	$self->dprint("*************************************************\n");
	$self->dprint("mount($method, $mountpt, $username, xxxxxx)\n");

	# Frayed ends of Sanity...
	if ( $method !~ /^(nfs|ftp|smb|local)$/i ) {
		$self->_error_handler("Invalid backup method: $method");
	}

	# Set method type
	$self->set_method($method);
	$self->dprint("setting _method=$method\n");

	# store user and password, if needed
	if ($username) {
		$self->set_username($username);
		$self->dprint("setting _username=$username\n");
	}
	if ($password) {
		$self->set_password($password);
		$self->dprint("setting _password=xxxxxx\n");
	}

	my $ret;

	# Make correct method call - in ALL cases these must set:
	#	_fsmount, _fsdir, _backupdir
	MOUNT: {
		($method =~ /^nfs$/i)	&& do {
			$ret = $self->_nfs_mount($mountpt);
			last MOUNT;
		};
		($method =~ /^ftp$/i)	&& do {
			$ret = $self->_ftp_mount($mountpt);
			last MOUNT;
		};
		($method =~ /^smb$/i)	&& do {
			$ret = $self->_smb_mount($mountpt);
			last MOUNT;
		};
		($method =~ /^local$/i)	&& do {
			$ret = $self->_local_mount($mountpt);
			last MOUNT;
		};
		$self->_error_handler("Failed to process mount method");
	}

	if (!$ret) {
		$self->_error_handler("Mount for method $method failed");
	}

	return 1;
}

sub umount
# umount -- unmount and remove mountpoint
#
# Arguments: None
# Returns:   Undef on failure, 1 on success
{
	my $self = shift || return;
	my $mounted;

	$self->dprint("*************************************************\n");
	$self->dprint("umount()\n");

	my $method = $self->get_method();
	if ($method =~ /^local$/i) {
		return 1;
	}

	if ( $self->get_mount() ) {
		$mounted = $self->get_mount();
	} else {
		return 1;
	}

	$self->dprint("running umount $mounted\n");
	$self->_log_system("umount $mounted") or 
		$self->_error_handler("umount($mounted): $!");
	rmdir($mounted) or 
		$self->_error_handler("umount($mounted): $!");
	
	return 1;
}

sub backup
# backup -- Generic backup method
#
# Arguments: None
# Returns:   Undef on failure, 1 on success
{
	my $self         = shift || return;
	my $name         = $self->get_name() || "Unknown";
	my $fileset	 = $self->get_fileset();
	my $backupconfig = $self->get_backupconfig();
	my $fsmount      = $self->get_fsmount();
	my $bdir         = $self->get_fsdir();
	my ($date, $after);

	$self->dprint("*************************************************\n");
	$self->dprint("backup()\n");

	# Note Begin time
	$date = time();
	$self->set_starttime($date);
	$self->_log_msg("Starting backup " . 
		"\\\'$name\\\' at $date to '$fsmount' in $bdir");
	$self->dprint("Backup is starting\n");

	# Mark us as pending
	( ! $self->enter_pending_history() )
		? $self->_error_handler("Failed to enter pending history")
		: 0;
	
	# Always backup header
	( ! $self->_create_backup_header() )
		? $self->_error_handler("Failed to create backup header")
		: 0;

	#-------------------------------------------------
	# Common intervals in seconds:
	#	1 hour = 3600
	#	1 day  = 86400
	#	1 week = 604800
	#-------------------------------------------------

	# Da Meat
	BKUP: {
		( $fileset == 0 )	&& do {
			$self->set_label("Full backup: $date");
			last BKUP;
		};
		( $fileset == 31 )	&& do {
			$after = time() - 2678400;
			$self->set_label("31 days or newer: $date");
			last BKUP;
		};
		( $fileset == 14 )	&& do {
			$after = time() - 1209600;
			$self->set_label("14 days or newer: $date");
			last BKUP;
		};
		( $fileset == 7 )	&& do {
			$after = time() - 604800;
			$self->set_label("7 days or newer: $date");
			last BKUP;
		};
		( $fileset == 2 )	&& do {
			$after = time() - 172800;
			$self->set_label("2 days or newer: $date");
			last BKUP;
		};
		( $fileset == 1 )	&& do {
			$after = time() - 86400;
			$self->set_label("1 day or newer: $date");
			last BKUP;
		};
		$self->_error_handler("Unknown backup interval: $fileset\n");
	}

	# do it
	if ( $backupconfig ) { 
		( ! $self->_backup_cce_db() )
			? $self->_error_handler(
				"Backup of CCE Failed: invalid reference")  
			: 0;
		( ! $self->_backup_root($after) )
			? $self->_error_handler(
				"Backup of / failed: invalid reference")  
			: 0;
		( ! $self->_backup_var($after) )
			? $self->_error_handler(
				"Backup of /var failed: invalid reference")  
			: 0;
	}
	( ! $self->_backup_home($after) )
		? $self->_error_handler(
			"Backup of users failed: invalid reference")
		: 0;
	( ! $self->_backup_groups($after) )
		? $self->_error_handler(
			"Backup of groups failed: invalid reference")
		: 0;

	# note ending time
	$date = time();
	$self->set_stoptime($date);
	$self->_log_msg("Ending backup " . "\\\'$name\\\' at $date");
	$self->dprint("Backup is done\n");

	# put in history
	( ! $self->delete_pending_history("admin") )
		? $self->_error_handler(
			"Failed to delete the pending history")
		: 0;
	( ! $self->enter_backup_history() )
		? $self->_error_handler( "Failed to enter backup history")
		: 0;

	( ! $self->_backup_history() )
		? $self->_error_handler(
			"Backup of history file failed: invalid reference")
		: 0;

	return 1;
}

sub restore
# restore -- Generic retore method
#
# Arguments: None
# Returns: Undef on failure, 1 on success
{
	my $self      = shift || return;
	my $backupdir = $self->get_backupdir();
	my $bdir      = $self->get_fsdir();
	my $fsmount   = $self->get_fsmount();
	my $rdir      = $self->get_restoredir();
	
	my $date;

	$self->dprint("*************************************************\n");
	$self->dprint("restore()\n");

	$date = time();
	$self->_log_msg("Starting restore of $bdir on '$fsmount' at $date");
	$self->dprint("Restore is starting\n");

	# Be sure the directory is there in the case of restoreTemp
	if ( ! -d $rdir ) {
		mkdir $rdir, 0755;
	}
	# Test for OS version match
	if( $rdir eq '/' ) {
		# Download; open header, find os version
		my $archive_version = $self->_restore_get_osversion($backupdir);
		# connect to CCE
		my $cce = new CCE;
		$cce->connectuds();
		# Find local os version
		my($ok, $system_ospkg) = $cce->get(
			($cce->find('Package', {
				'name'=>'OS',
				'vendor'=>'Cobalt',
				'installState' => 'Installed'
			} ))[0]
		);
		# fail on mismatch
		if ($system_ospkg->{version} ne $archive_version) {
			$self->_error_handler('[[base-backup.osversion_conflict]]');
			# ...And give up before user breaks Qube...
			return;
		}
	}

	# restore away
	( ! $self->_restore_cce_db($backupdir) )
		? $self->_error_handler(
			"Restore of cce failed: invalid reference")
		: 0;
	( ! $self->_restore_home($backupdir) )
		? $self->_error_handler(
			"Restore of users failed: invalid reference")
		: 0;
	( ! $self->_restore_groups($backupdir) )
		? $self->_error_handler(
			"Restore of groups failed: invalid reference")
		: 0;
	( ! $self->_restore_root($backupdir) )
		? $self->_error_handler(
			"Restore of / failed: invalid reference")
		: 0;
	( ! $self->_restore_var($backupdir) )
		? $self->_error_handler(
			"Restore of /var failed: invalid reference")
		: 0;

	# Restore recovers the pending history file.  Delete this 
	# file upon restore.
	my $f = $self->_home_of("admin") . "/.cbackup/pending";
	( ! defined($f) ) ? $self->_error_handler(
		"Failed to get home directory of admin") : 0;
	if ( -e $f ) { 
		$self->dprint("unlinking $f\n");
		unlink($f); 
	}

	$date = time();
	$self->_log_msg("Ending restore at $date");
	$self->dprint("Restore is done\n");

	if ( $rdir eq '/' ) {
		my $cceobj = new CCE;
		$cceobj->connectuds();
		my @sysoid = $cceobj->find("System");
		$cceobj->set($sysoid[0], "", {"reboot" => time()});
	}

	return 1;
}

sub _error_handler
# _error_handler -- Handle errors thrown by backup/restore
#
# Arguments: Error Message
# Returns:   Undef on failure, 1 on success
{
	my $self        = shift || return;
	my $err_msg     = shift || return;
	my $date;

	$self->dprint("_error_handler($err_msg)\n");

	if (!$self->get_test) {
		$self->set_errors(1) if $err_msg;
		if ( ! $self->get_erroring() ) {
			$self->set_erroring(1);
			if ($self->get_create()) {
				$self->enter_backup_history();				
			}
		}

		# Send out error mail
		$self->dprint("sending mail\n");
		( ! $self->_error_mail($err_msg) ) 
			? die "Failed to create error mail" : 0;
		$self->_log_msg($err_msg);

		# Note Ending time
		$date = time();
		$self->set_stoptime($date);
		$self->_log_msg("Ending failed procedure at $date");
	}

	# Don't use croak here, that causes calling script to fail to STDERR
	# This will cause silent failure, but return code is still '1'
	$self->dprint("exiting\n");
	exit 1;
}

##
## 
## Mount methods: these are specific to each backup method
##
##

sub _nfs_mount
# _nfs_mount -- Mount NFS shares
#
# Arguments: NFS share to mount
# Returns:   Undef on failue,  1 on success
{
	my $self      = shift || return;
	my $srv_mount = shift || return;
	my ($mountpoint, $mountcmd, $ret);

	$self->dprint("_nfs_mount($srv_mount)\n");
	
	# for NFS, all the subdirs count in the mount point
	$self->set_fsmount($srv_mount);
	$self->set_fsdir("");

	$self->dprint("setting _fsmount=$srv_mount\n");
	$self->dprint("setting _fsdir=\n");

	# Make temp mountpoint
	$mountpoint = $self->_mk_tmp_mount(); 
	( ! defined($mountpoint) )
		? $self->_error_handler(
			"Failed to create mount point: invalid reference")
		: 0;

	# Build up mount command
	$mountcmd  = "/bin/mount -t nfs";
	$mountcmd .= " -o rw";
	$mountcmd .= " $srv_mount \"$mountpoint\"";

	# do it
	$ret = $self->_mount_doit($mountcmd);
	( ! defined($ret) )
		? $self->_error_handler(
			"Failed to mount filesystem: invalid reference")
		: 0;
	

	return $ret;
}

sub _smb_mount
# _smb_mount -- Mount SMB shares
#
# Arguments: SMB share to mount
# Returns: Undef on failure, 1 on success
{
	my $self      = shift || return;
	my $srv_mount = shift || return;
	my ($mountpoint, $mountcmd, $dir, $username, $password, $ret);

	$self->dprint("_smb_mount($srv_mount)\n");

	# for SMB, the first subdir is the share, the rest are dirs
	$srv_mount =~ /^(\\\\[^\\]+\\[^\\]+)(.*)$/;
	$srv_mount = $1;
	$dir = $2 || "";
	$dir =~ s/\\/\//g;
	$self->set_fsmount($srv_mount);
	$self->set_fsdir($dir);

	$self->dprint("setting _fsmount=$srv_mount\n");
	$self->dprint("setting _fsdir=$dir\n");

	# Make temp mountpoint
	$mountpoint = $self->_mk_tmp_mount(); 
	( ! defined($mountpoint) )
		? $self->_error_handler(
			"Failed to create mount point: invalid reference")
		: 0;

	# escape slashes to run it
	$srv_mount =~ s/\\/\\\\/g;
	$username  = $self->get_username();
	$password  = $self->get_password();
	undef $username if ($username eq '???');
	undef $password if ($password eq '???');

	# Build up mount command
	$mountcmd  = "/bin/mount -t smbfs";			# Base command
	$mountcmd .= " -o rw";					# Read/Write
	$mountcmd .= ",username=$username" if $username;	# User
	$mountcmd .= ",password=$password" if $password;	# Password
	$mountcmd .= " \"$srv_mount\" $mountpoint";		# Mounts
	
	# do it
	$ret = $self->_mount_doit($mountcmd);
	( ! defined($ret) )
		? $self->_error_handler(
			"Failed to mount filesystem: invalid reference")
		: 0;

	return $ret;
}

sub _ftp_mount
# _ftp_mount -- Connect to ftp server and return reference to object
# 09.11.2000 -- Remove consistant ftp connection and use ncftpput for
#               transfers
#
# Arguments: FTP share to attach to 
# Returns:   Undef on failure, 1 on success
{
	my $self	= shift || return;
	my $srv_mount	= shift || return;
	my $create	= $self->get_create();
	my $ftpbase	= $self->get_ftpbase();
	my $restoredir	= $self->get_restoredir();
	my ($ftp, $username, $password, $server, $dir);

	$self->dprint("_ftp_mount($srv_mount)\n");

	# for FTP, all subdirs are dirs
	# ftp.host.com/dir1/dir2/
	$srv_mount =~ /^([^\/]+)(.*)$/;
	$server    = $1;
	$dir       = $2;
	$self->set_fsmount($server);
	$self->set_fsdir($dir);

	$self->dprint("setting _fsmount=$server\n");
	$self->dprint("setting _fsdir=$dir\n");

	$username  = $self->get_username();
	$password  = $self->get_password();

	# Get new FTP object
	$ftp = Net::FTP->new("$server"); 
	( ! defined($ftp) )
		? $self->_error_handler("FTP: failed to connect to $server: $@")
		: 0;
	if ($ftp && !$ftp->login("$username","$password")) {
		$self->_error_handler("FTP: failed to log into $server: $@");
	};
	$self->dprint("FTP: logged in\n");

	my $datefmt = `/bin/date "+%Y%m%d%H%M%S"`;
	chomp $datefmt;
	
	# Create the backup directory (like _create_backupdir, but for ftp)
	# FTP is special due to the fact is isn't mounted
	if ( $create ) {
		( (! $dir) || (! defined($dir)) ) ? $dir = $ftp->pwd() : 0;

		( ! $ftp->cwd("$dir") )
			? $self->_error_handler(
				"FTP: Invalid remote directory $dir")
			: 0;

		( ! $ftp->mkdir("$datefmt") )
			? $self->_error_handler(
				"FTP: Failed to create remote directory: $datefmt")
			: 0;

		# Tack on a trailing slash if missing
		if ($dir !~ /\/$/ ) {
			$dir .= "/";
		}

		# Server-side FTP directory
		$self->set_fsdir($dir . $datefmt);

		# Local FTP directory
		$self->set_backupdir($ftpbase . $datefmt);
		$self->dprint("setting _fsdir=$dir$datefmt\n");
	} else {
		# Local FTP directory
		$self->set_backupdir($ftpbase . $datefmt);
		$self->dprint("setting _fsdir=$dir\n");
	}
	$self->dprint("setting _backupdir=".$self->get_backupdir()."\n");

	if ( $create || $restoredir !~ /\?\?\?/ ) {
		# Create directory and set permissions
		umask 0027;
		$self->_log_system("mkdir -p " . $self->get_backupdir());

		# Create the ncftp config options
		my $ncftpconfig = $self->get_backupdir() . "/ncftp.cfg";
		open( NCF, "> $ncftpconfig" ) || $self->_error_handler( 
			"FTP: Failed to create host configuration file: $ncftpconfig");

		printf (NCF "host %s\n", $server);
		printf (NCF "user %s\n", $username);
		printf (NCF "password %s\n", $password);
	
		close(NCF);
		$self->set_ncftpconfig($ncftpconfig);
	}
	
	# Set ftp on.
	$self->set_ftp(1);

	return 1;
}

sub _local_mount
# _local_mount -- 'Mount' local volumes
#
# Arguments: dir share to 'mount'
# Returns: Undef on failure, 1 on success
{
	my $self      = shift || return;
	my $srv_mount = shift || return;
	my ($mountpoint, $mountcmd, $username, $password, $ret);

	$self->dprint("_local_mount($srv_mount)\n");

	if ( ! -d $srv_mount ) {
		return 0;
	}

	$self->set_fsmount($srv_mount);
	$self->set_fsdir("");
	$self->set_backupdir($srv_mount);

	$self->dprint("setting _fsmount=$srv_mount\n");
	$self->dprint("setting _fsdir=\n");
	$self->dprint("setting _backupdir=$srv_mount\n");

	return 1;
}


sub _mount_doit
# _mount_doit -- Handles the mounting of all filesystem types
#
# Arguments: Mount command
# Returns:   Undef on failure, 1 on success
{
	my $self       = shift || return;
	my $mountcmd   = shift || return;
	my $create     = $self->get_create();
	my $mountpoint = $self->get_mount();
	my $ret;

	# Saddle up
	# $self->dprint("running $mountcmd\n");
	$ret = `$mountcmd`;

	# Verify mount is there
	MOUNT: {
		( ! -d "$mountpoint" )	&& do {
			$self->_error_handler(
				"_mount_doit: $mountpoint doesn't exist");
			last MOUNT;
		};
		( `mount | grep -c "$mountpoint"` == 0 ) && do {
			$self->_error_handler(
				"_mount_doit: $mountpoint not mounted");
			last MOUNT;
		};
		( $ret )		&& do {
			$self->_error_handler(
				"_mount_doit: bad return from mount: $ret");
			last MOUNT;
		};
		undef $ret;
	}

	# Create the backup directory
	if ( $create ) { 
		( ! $self->_create_backupdir() )
			? $self->_error_handler(
				"_mount_doit: Create backup directory failed")
			: 0;
	} else {
		$self->set_backupdir($self->get_mount() . "/" 
			. $self->get_fsdir());
		$self->dprint("setting _backupdir="
			. $self->get_backupdir() . "\n");
	}

	return 1;
}

##
##
## Backup methods
##
##

sub _create_backup_header
# _create_backup_header -- Generates header
#
# Arguments: None
# Returns: Undef on failure, 1 on success
{
	my $self = shift || return;
	my ($cce, $header, $ret, $val);

	$self->dprint("_create_backup_header()\n");

	# connect to CCE
	$cce = new CCE;
	$cce->connectuds();

	my($ok, $system_ospkg) = $cce->get(
		($cce->find('Package', {
			'name'=>'OS',
			'vendor'=>'Cobalt',
			'installState' => 'Installed'
		} ))[0]
	);

	# Local header tags
	my $hdr_begin_tag	= '<cbackup header>';
	my $hdr_end_tag		= '</cbackup header>';

	$header = $self->get_backupdir() . "/header";

	# Open Backup Header.  This is where the header Info goes
	sysopen(BACKUP_HDR, $header, O_WRONLY|O_CREAT) or
		$self->_error_handler("Can't open header file \"$header\": $!");
    
	# Print out magic HEADER BEGIN Tag
	print BACKUP_HDR "$hdr_begin_tag\n";

	# Print out backup name / time
	$val = $self ->get_name() || "Unknown";
	print BACKUP_HDR "\t<backup_name>$val</backup_name>\n";
	$val = $self ->get_starttime();
	print BACKUP_HDR "\t<backup_time>$val</backup_time>\n";
	$val = $self->get_method();
	print BACKUP_HDR "\t<backup_method>$val</backup_method>\n";
	$val = $self->get_fsmount() . $self->get_fsdir();
	print BACKUP_HDR "\t<backup_destination>$val</backup_destination>\n";

	# Put Version of Backup.  
	$val = $self->get_version();
	print BACKUP_HDR 
		"\t<cbackup_version>$val<\/cbackup_version>\n"; 

	print BACKUP_HDR
	"\t<cbackup_osversion>".$system_ospkg->{version}.
		"<\/cbackup_osversion>\n";

	# Open /etc/build to get build information
	open(ETCBUILD, "< /etc/build");
	$val = <ETCBUILD>;
	chomp $val;
	close(ETCBUILD);
	print BACKUP_HDR "\t<product_version>$val<\/product_version>\n";

	# Machine Information
	open (UNAMECMD, "/bin/uname -n|");
	$val = <UNAMECMD>;
	chomp $val;
	print BACKUP_HDR "\t<backup_machine>$val<\/backup_machine>\n";
	close(UNAMECMD);

	# Owner Information
	print BACKUP_HDR "\t<backup_owner>admin<\/backup_owner>\n";

	# Currently, only dependency information is CSCP version
	print BACKUP_HDR "\t<backup_dependency> \n";
	$val = $cce->{version};
	print BACKUP_HDR "\t\t<cscp_version>$val<\/cscp_version>\n";
	print BACKUP_HDR "\t<\/backup_dependency>\n";

	# State information, currently get this from md5lsts
	print BACKUP_HDR "\t<backup_state> \n";
	opendir(DIRECTORY,"/var/lib/cobalt");
	my $file;

	while ($file = readdir(DIRECTORY)) {
		# Only want files ending in md5lst
		if ($file =~ /md5lst/i){
			print BACKUP_HDR "\t\t<md5lst>$file<\/md5lst>\n";
		}
	}

	closedir(DIRECTORY);
	print BACKUP_HDR "\t<\/backup_state> \n";

	# Finished dumping header
	print BACKUP_HDR "$hdr_end_tag\n";

	close(BACKUP_HDR);

	# Send the file to the backup server
	$self->ftp_send("$header") if ($self->get_ftp());

	# done with CCE
	$cce->bye();
	
	return 1;
}


sub _backup_cce_db
# _backup_cce_db Backup CCE database - /usr/sausalito
#
# Arguments: None
# Returns:   Undef on success, 1 on failure
{
	my $self      = shift || return;
	my $backupcfg = $self->get_backupconfig();
	my $backupcmd = $self->get_backupcmd();
	my $backuparg = $self->get_backuparg();
	my $backupdir = $self->get_backupdir();
	my $backuplbl = $self->get_label();
	my ($cmd, $ret, $excludearg);

	$self->dprint("_backup_cce_db()\n");

	# Files to ignore
	my @ignorefiles = qw( 
		/usr/sausalito/pperl.socket
	);

	# Add files to ignore list
	for (@ignorefiles) { $excludearg .= " --exclude=$_ "; }

	# Build up command-line
	$cmd .= "$backupcmd ";					# command
	$cmd .= "-V \"$backuplbl\" " if ($backuplbl ne '???');	# volume lbl
	$cmd .= "-b 16 ";					# bs
	$cmd .= "$excludearg ";					# exclude
	$cmd .= "$backuparg ";					# arguments
	$cmd .= "$backupdir/sausalito.tar ";			# where
	$cmd .= "/usr/sausalito/";				# what

	$self->dprint("stopping CCE\n");
	( ! $self->_cced_stop() )
		? $self->_error_handler(
			"Failed to stop cced: invalid reference")
		: 0;

	$self->dprint("$$: running $cmd\n");
	$self->_log_system($cmd);

	$self->dprint("starting CCE\n");
	( ! $self->_cced_start() )
		? $self->_error_handler(
			"Failed to start cced: invalid reference")
		: 0;

	# Send the file to the backup server
	$self->ftp_send("$backupdir/sausalito.tar") if ($self->get_ftp());

	return 1;
}

sub _backup_var
# _backup_var -- Backup /var partition
#
# Arguments: None
# Returns: Undef on failure, 1 on success
{
	my $self      = shift || return;
	my @args      = @_;
	my $backupcmd = $self->get_backupcmd();
	my $backuparg = $self->get_backuparg();
	my $backupdir = $self->get_backupdir();
	my $backuplbl = $self->get_label();
	my ($cmd, $fromdate, $ret);

	$self->dprint("_backup_var()\n");

	# Setup incremental date
	$args[0] ? $fromdate = localtime($args[0]) : undef $fromdate;

	# Build up command-line
	$cmd .= "$backupcmd ";					# command
	$cmd .= "-N \"$fromdate\" " if $fromdate;		# when
	$cmd .= "-V \"$backuplbl\" " if ($backuplbl ne '???');	# volume lbl
	$cmd .= "-b 16 ";					# bs
	$cmd .= "--exclude=/var/log/backup.log ";		# exclude
	$cmd .= "--exclude=/var/log/rpm/* ";			# exclude
	$cmd .= "$backuparg ";					# arguments
	$cmd .= "$backupdir/var.tar ";				# where
	$cmd .= "/var";						# what
	
	$self->dprint("$$: running $cmd\n");
	$self->_log_system($cmd);

#	if ( $? != 0 ) {
#		$self->_error_handler("Backup of /var failed: $!");
#	}

	# Send the file to the backup server
	$self->ftp_send("$backupdir/var.tar") if ($self->get_ftp());

	return 1;
}

sub _backup_root
# _backup_root --  Backup root filesystem except for explicit filesystems
#
# Arguments: after-date for tar (optional, assumes all be default)
# Returns:   Undef on failure, 1 on success
{
	my $self      = shift || return;
	my @args      = @_;
	my $backupcmd = $self->get_backupcmd();
	my $backuparg = $self->get_backuparg();
	my $backupdir = $self->get_backupdir();
	my $backuplbl = $self->get_label();
	my $tarscript = $self->get_tarscript();
	my $users     = $self->get_users();
	my ($cmd, $excludearg, $fromdate, $fs, $multi, $multicmd, $ret, $size);
	my ($tapelen, $kid, $pid);

	$self->dprint("_backup_root()\n");

	# Filesystems to not backup
	my @ignorefs = qw( 
		/boot
		/dev 
		/tmp 
		/usr/sausalito 
		/bin
		/lib
		/sbin
		/usr/lib
		/usr/libexec
		/usr/bin
		/usr/sbin
	);
	
	# Files to ignore
	my @ignorefiles = qw( 
		/bin/tar
		/etc/mtab
		/usr/sausalito/cced.socket 
		/usr/sausalito/pperl.socket
		/vmlinux.gz
	);
	
	# Push list of mounted filesystems on the ignorfs (except /)
	open( MNT, "/bin/mount |" ) or
		$self->_error_handler("Failed to get a pipe: $!");

	while( <MNT> ) {
		/on (\/.*) type/;
		$fs = $1;

		# make sure we aren't the root
		next if $fs eq "/";

		# Add to list
		push @ignorefs, $fs;
	}

	close MNT;

	# Add glob to filesystem ignores and build exclude list
	for (@ignorefs) { 
		$_ .= '/*';
		$excludearg .= " --exclude=$_ ";
	}

	# Add files to ignore list
	for (@ignorefiles) { $excludearg .= " --exclude=$_ "; }

	# get tapelength
	$tapelen = $self->get_tapelength() if $multi;

	# Setup incremental date
	$args[0] ? $fromdate = localtime($args[0]) : undef $fromdate;

	#
	# Always create the command that would be used in a multi-segment
	# archive.  It will always be used to send the "last" segment, which
	# is the only segment for some backups.
	#
	$multicmd = $tarscript . ' ';
	$multicmd .= '--create ';
	$multicmd .= '--ftpconfig ' . $self->get_ncftpconfig() . ' ';
	$multicmd .= '--oldarchive ' . "$backupdir/base.tar" . ' ';
	$multicmd .= '--newarchive ' . "$backupdir/base" . ' ';
	$multicmd .= '--location ' . $self->get_fsdir();

	# Build up command-line
	$cmd .= "$backupcmd ";					# command
	$cmd .= "--info-script=\"$multicmd\" " if $multi;		# multi
	$cmd .= "$excludearg ";					# excludes
	$cmd .= "--tape-length=$tapelen " if $multi;		# volume size
	$cmd .= "-M " if $multi;				# multi-volume
	$cmd .= "-N \"$fromdate\" " if $fromdate;		# when
	$cmd .= "-V \"$backuplbl\" " if ($backuplbl ne '???');	# volume lbl
	$cmd .= "-b 16 ";					# bs
	$cmd .= "$backuparg ";					# arguments
	$cmd .= "$backupdir/base.tar ";				# where
	$cmd .= '/*';						# what

	$self->dprint("$$: running $cmd\n");
	$self->_log_system($cmd);

	# Always transfer the last archive to the server
	$self->dprint("$$: running $multicmd\n");
	$self->_log_system($multicmd);

	return 1;
}

sub _backup_home
# _backup_home -- Backup home directory
#
# Arguments: Optional days to backup from
# Returns:   Undef on failure, 1 on success
{
	my $self      = shift || return;
	my @args      = @_;
	my $backupcmd = $self->get_backupcmd();
	my $backuparg = $self->get_backuparg();
	my $backupdir = $self->get_backupdir();
	my $backuplbl = $self->get_label();
	my $tarscript = $self->get_tarscript();
	my $users     = $self->get_users();
	my ($cmd, $excludearg, $fromdate, $multi, $multicmd, $ret, $size);
	my ($tapelen, $kid, $pid);

	return 1 if ( ! defined($users) || $users eq '???') ;

	$self->dprint("_backup_home()\n");

	# Determine if multi-volume is needed
	$size = `df -l |cut -b31- |grep /home`;
	$size =~ s/^\s+(\d+).*$/$1/;
	$size =~ s/\n//;
	if ($size >= 1536000) { $multi = 1 }

	# Filesystems to not backup
	my @ignorefs = qw( 
		/home/users/admin/.cbackup
	);
	
	# Add glob to filesystem ignores and build exclude list
	for (@ignorefs) { $_ .= '/*'; $excludearg .= " --exclude=$_ "; }

	# Setup incremental date
	$args[0] ? $fromdate = localtime($args[0]) : undef $fromdate;

	# get tapelength
	$tapelen = $self->get_tapelength() if $multi;

	#
	# Always create the command that would be used in a multi-segment
	# archive.  It will always be used to send the "last" segment, which
	# is the only segment for some backups.
	#
	$multicmd = $tarscript . ' ';
	$multicmd .= '--create' . ' ';
	$multicmd .= '--ftpconfig ' . $self->get_ncftpconfig() . ' ';
	$multicmd .= '--oldarchive ' . "$backupdir/home.tar" . ' ';
	$multicmd .= '--newarchive ' . "$backupdir/home" . ' ';
	$multicmd .= '--location ' . $self->get_fsdir();

	# Build up command-line
	$cmd .= "$backupcmd ";					# command
	$cmd .= "--info-script=\"$multicmd\" " if $multi;		# multi
	$cmd .= "$excludearg ";					# excludes
	$cmd .= "--tape-length=$tapelen " if $multi;		# volume size
	$cmd .= "-M " if $multi;				# multi-volume
	$cmd .= "-N \"$fromdate\" " if $fromdate;		# when
	$cmd .= "-V \"$backuplbl\" " if ($backuplbl ne '???');	# volume lbl
	$cmd .= "-b 16 ";					# bs
	$cmd .= "$backuparg ";					# arguments
	$cmd .= "$backupdir/home.tar ";				# where

	if ($users eq "all"){
#		$cmd .= "/var/spool/mail/* ";			# what
		$cmd .= "/home/users/* ";			# what
		$cmd .= "/home/profiles/*";
	} else { 
		my @userarray = split(/,/,  $users);
		my $user;

		foreach $user (@userarray) {
			# Incase mail spool of homedir is missing
			if ( -e "/home/users/$user" ) {
				$cmd .= "/home/users/$user ";	# what
			}

			if ( -e "/var/spool/mail/$user" ) {
				$cmd .= "/var/spool/mail/$user ";# what
			}

			if ( -e "/home/profiles/$user" ) {
				$cmd .= "/home/profiles/$user ";
			}
		}
	}

	$self->dprint("$$: running $cmd\n");
	$self->_log_system($cmd);

	# Always transfer the last archive to the server
	$self->dprint("$$: running $multicmd\n");
	$self->_log_system($multicmd);

	return 1;
}

sub _backup_groups
# _backup_groups -- Backup groups directory
#
# Arguments: Optional days to backup from
# Returns:   Undef on failure, 1 on success
{
	my $self      = shift || return;
	my @args      = @_;
	my $backupcmd = $self->get_backupcmd();
	my $backuparg = $self->get_backuparg();
	my $backupdir = $self->get_backupdir();
	my $backuplbl = $self->get_label();
	my $tarscript = $self->get_tarscript();
	my $groups    = $self->get_groups();
	my ($cmd, $fromdate, $multi, $multicmd, $ret, $size, $tapelen);

	return 1 if ( ! defined($groups) || $groups eq '???') ;

	$self->dprint("_backup_groups()\n");

	# Determine if multi-volume is needed
	$size = `df -l |cut -b31- |grep /home`;
	$size =~ s/^\s+(\d+).*$/$1/;
	$size =~ s/\n//;
	if ($size >= 1536000) { $multi = 1 }

	# Setup incremental date
	$args[0] ? $fromdate = localtime($args[0]) : undef $fromdate;

	# get tapelength
	$tapelen = $self->get_tapelength() if $multi;

	#
	# Always create the command that would be used in a multi-segment
	# archive.  It will always be used to send the "last" segment, which
	# is the only segment for some backups.
	#
	$multicmd = $tarscript . ' ';
	$multicmd .= '--create' . ' ';
	$multicmd .= '--ftpconfig ' . $self->get_ncftpconfig() . ' ';
	$multicmd .= '--oldarchive ' . "$backupdir/groups.tar" . ' ';
	$multicmd .= '--newarchive ' . "$backupdir/groups" . ' ';
	$multicmd .= '--location ' . $self->get_fsdir();

	# Build up command-line
	$cmd .= "$backupcmd ";					# command
	$cmd .= "--info-script=\"$multicmd\" " if $multi;		# multi
	$cmd .= "--tape-length=$tapelen " if $multi;		# volume size
	$cmd .= "-M " if $multi;				# multi-volume
	$cmd .= "-N \"$fromdate\" " if $fromdate;		# when
	$cmd .= "-V \"$backuplbl\" " if ($backuplbl ne '???');	# volume lbl
	$cmd .= "-b 16 ";					# bs
	$cmd .= "$backuparg ";					# arguments
	$cmd .= "$backupdir/groups.tar ";			# where

	if ($groups eq "all"){
		$cmd .= "/home/groups/* ";
	} else { 
		my @grouparray = split(/,/,  $groups);
		my $group;

		foreach $group (@grouparray) {
			if ( -e "/home/groups/$group" ) {
				$cmd .= "/home/groups/$group ";
			}
		}
	}

	$self->dprint("$$: running $cmd\n");
	$self->_log_system($cmd);

	# Always transfer the last archive to the server
	$self->dprint("$$: running $multicmd\n");
	$self->_log_system($multicmd);

	return 1;
}

sub ftp_send
{
	my $self = shift || return;
	my @files = @_;
	my $ret;

	$self->dprint("ftp_send");
	if (! $self->get_ftp()) {
		$self->dprint("not an ftp backup.");
	}

	$self->dprint("FTP: putting files...\n");

	# Call ncftp to put the file
	$ret = $self->_log_system(
		"/usr/bin/ncftpput ".
		"-f " . $self->get_ncftpconfig() . " " .
		" -t -1 " .
		" -V -DD " .
		$self->get_fsdir . " " .
		join ' ', @files
	);

	return $ret;
}



sub _backup_history
# _backup_history -- Copy histroy file over to backup location
#
# Arguments: None
# Returns:   Undef on failure, 1 on success
{
	my $self = shift || return;
	my @args      = @_;
	my $backupdir = $self->get_backupdir();
	my $history   = "/home/users/admin/.cbackup/history";
	my ($cmd, $fromdate, $multi, $ret, $size);

	$self->dprint("_backup_history()\n");

	# Upload it if we are using ftp
	if ($self->get_ftp()) {
		#hack, hack, hack.  
		$self->_log_system("/bin/cp $history $history.tmp");
		$self->ftp_send($history);
		$self->_log_system("/bin/cp $history.tmp $history");
		$self->_log_system("chgrp httpd $history");
		chmod(0640, $history);
	} else {
		$self->_log_system("/bin/cp $history $backupdir");
	}

	return 1;
}

##
##
## Restore methods
##
##

sub _restore_get_osversion
# _restore_get_osversion -- Obtain & parse the download header
#
# Arguments: Base directory where archives are located
# Returns:   version number, undef if none are specified in the header
{
        my $self        = shift || return;
        my $base        = shift || return;
        my $archive     = 'header';
        my $dir         = $self->get_restoredir();
        my $ret;

        $self->dprint("_restore_get_osversion($base)\n");

        # if FTP, retrieve the archive
        if ( $self->get_ftp() ) {
                $self->_log_msg("FTP: Retrieving header data");

                # Set the base dir
                $base   = $self->get_ftpbase();

                # Call ncftpget
                $ret = $self->_log_system(
                        "/usr/bin/ncftpget " .
                        "-f " . $self->get_ncftpconfig() .
                        " -t -1 " .
                        " -V " .
                        $base . "/ " .
                        $self->get_fsdir() . '/' . $archive
                );
        }

        if ( ! -e "$base/$archive" ) {
                $self->_log_msg("$archive not contained in the backup");
                return undef;
        }

        $self->_log_msg("Parsing header data");
        $self->dprint("parsing header\n");

        # Change to proper directory..
        chdir $dir;
        $self->dprint("Changing into directory: $dir for restore");
        open(ARCHIVE, "$base/$archive") || return undef;
        while(<ARCHIVE>) {
                if(/\<cbackup_osversion\>([^<]+)\<\/cbackup_osversion\>/) {
                        my $osversion = $1;

                        close(ARCHIVE);
                        unlink("$base/$archive");
                        return $osversion;
                }
        }
        close (ARCHIVE);
        unlink("$base/$archive");
        return undef;
}

sub _restore_var
# _restore_var -- Restore the var
#
# Arguments: Base directory where archives are located
# Returns:   Undef on failure, 1 on success
{
	my $self	= shift || return;
	my $base	= shift || return;
	my $archive     = 'var.tar';
	my $backupcfg	= $self->get_backupconfig();
	my $backupcmd   = $self->get_backupcmd();
	my $dir		= $self->get_restoredir();
	my $extractargs = $self->get_extractargs();
	my ($cmd, $ret);

	$self->dprint("_restore_var($base)\n");

        # if FTP, retrieve the archive
        if ( $self->get_ftp() ) {
		$self->_log_msg("FTP: Retrieving /var data");

		# Set the base dir
                $base   = $self->get_ftpbase();

		# Call ncftpget
		$ret = $self->_log_system(
			"/usr/bin/ncftpget " .
			"-f " . $self->get_ncftpconfig() .
			" -t -1 " .
			" -V " .
			$base . "/ " .
			$self->get_fsdir() . '/' . $archive
		);
        }

	if ( ! -e "$base/$archive" ) {
		$self->_log_msg("$archive not contained in the backup");
		return 1;
	}

	$self->_log_msg("Restoring /var data");
	$self->dprint("restoring /var\n");

	# Change to proper directory..
	chdir $dir;
	$self->dprint("Changing into directory: $dir for restore");

	# Build command
	$cmd .= "$backupcmd ";				# command
	$cmd .= "-b 16 ";				# bs
	$cmd .= "$extractargs ";			# args
	$cmd .= "$base/$archive";			# archive

	$self->dprint("$$: running $cmd\n");
	$self->_log_system($cmd);

#	if ( $? != 0 ) {
#		$self->_error_handler("Restore of /var failed: $!");
#	}

	# Remove local archive if using FTP
	if ( $self->get_ftp() ) {
		unlink "$base/$archive";
	}

	return 1;
}

sub _restore_root
# _restore_root -- Restore the root
#
# Arguments: Base directory where archives are located
# Returns:   Undef on failure, 1 on success
{
	my $self	= shift || return;
	my $base	= shift || return;
	my $archive     = 'base000.tar';
	my $backupcfg	= $self->get_backupconfig();
	my $backupcmd   = $self->get_backupcmd();
	my $dir		= $self->get_restoredir();
	my ($cmd, $extractargs, @files, $multi, $ret, $tmp, $vols);

	$self->dprint("_restore_root($base)\n");

        # if FTP, retrieve the archive
        if ( $self->get_ftp() ) {
		$self->_log_msg("FTP: Retrieving / data");

		# Set the base dir
		my $base = $self->get_ftpbase();

		# Call ncftpget
		$ret = $self->_log_system(
			"/usr/bin/ncftpget " .
			"-f " . $self->get_ncftpconfig() .
			" -t -1 " .
			" -V " .
			$self->get_backupdir . "/ " .
			$self->get_fsdir() . '/' . "base*"
		);
        }

	if ( ! -e "$base/$archive" ) {
		$self->_log_msg("$archive not contained in the backup");
		return 1;
	}

	$self->_log_msg("Restoring / data");
	$self->dprint("restoring /\n");

	# Change to proper directory..
	chdir $dir;
	$self->dprint("Changing into directory: $dir for restore");

	# Determine number of volumes.....
	# tar xM -f /archive1.tar -f /archive2.tar -f /archive3.tar

	# Determine number of volumes
	$tmp  = `/bin/ls -l $base/base* |/usr/bin/wc -l`;
	$tmp  =~ /(\d+).*/;
	$vols = $1;

	# Build args
	$extractargs = '-xM'; 	# extract multi-volume

	# Build command
	$cmd .= "$backupcmd ";				# command
	$cmd .= "-b 16 ";				# bs
	$cmd .= "$extractargs ";			# args

	# Build archive names
	for ( 1..$vols ) {
		$tmp  = sprintf("%03d", ( $_ - 1 ));
		$cmd .= " -f $base/base$tmp.tar";
	}

	$self->dprint("$$: running $cmd\n");
	$self->_log_system($cmd);

#	if ( $? != 0 ) {
#		$self->_error_handler("Restore of base filesystem failed: $!");
#	}

	# Remove local archive if using FTP
	if ( $self->get_ftp() ) {
		unlink "$base/$archive";
	}

	return 1;
}

sub _restore_home
# _restore_home -- Restore the users
#
# Arguments: Base directory where archives are located
# Returns:   Undef on failure, 1 on success
{
	my $self	= shift || return;
	my $base	= shift || return;
	my $archive	= 'home000.tar';
	my $backupcfg	= $self->get_backupconfig();
	my $backupcmd   = $self->get_backupcmd();
	my $dir		= $self->get_restoredir();
	my ($cmd, $extractargs, @files, $multi, $ret, $tmp, $vols);

	$self->dprint("_restore_home($base)\n");

        # if FTP, retrieve the archive
        if ( $self->get_ftp() ) {
		$self->_log_msg("FTP: Retrieving user data");

		# Set the base dir
		my $base = $self->get_ftpbase();

		# Call ncftpget
		$ret = $self->_log_system(
			"/usr/bin/ncftpget " .
			"-f " . $self->get_ncftpconfig() .
			" -t -1 " .
			" -V " .
			$self->get_backupdir . "/ " .
			$self->get_fsdir() . '/' . "home*"
		);
        }

	if ( ! -e "$base/$archive" ) {
		$self->_log_msg("$archive not contained in the backup");
		return 1;
	}

	$self->_log_msg("Restoring user data");
	$self->dprint("restoring users\n");

	# Change to proper directory..
	chdir $dir;
	$self->dprint("Changing into directory: $dir for restore");

	# Determine number of volumes.....
	# tar xM -f /archive1.tar -f /archive2.tar -f /archive3.tar

	# Determine number of volumes
	$tmp  = `/bin/ls -l $base/home* |/usr/bin/wc -l`;
	$tmp  =~ /(\d+).*/;
	$vols = $1;

	# Build args
	$extractargs = '-xM'; 	# extract multi-volume

	# Build command
	$cmd .= "$backupcmd ";				# command
	$cmd .= "-b 16 ";				# bs
	$cmd .= "$extractargs ";			# args

	# Build archive names
	for ( 1..$vols ) {
		$tmp  = sprintf("%03d", ( $_ - 1 ));
		$cmd .= " -f $base/home$tmp.tar";
	}

	$self->dprint("$$: running $cmd\n");
	$self->_log_system($cmd);

#	if ( $? != 0 ) {
#		$self->_error_handler("Restore of users failed: $!");
#	}

	# Remove local archive if using FTP
	if ( $self->get_ftp() ) {
		unlink "$base/home*";
	}

	return 1;
}

sub _restore_groups
# _restore_groups -- Restore the groups
#
# Arguments: Base directory where archives are located
# Returns:   Undef on failure, 1 on success
{
	my $self	= shift || return;
	my $base	= shift || return;
	my $archive	= 'groups000.tar';
	my $backupcmd   = $self->get_backupcmd();
	my $dir		= $self->get_restoredir();
	my ($cmd, $extractargs, @files, $multi, $ret, $tmp, $vols);

	$self->dprint("_restore_groups($base)\n");

        # if FTP, retrieve the archive
        if ( $self->get_ftp() ) {
		$self->_log_msg("FTP: Retrieving group data");

		# Get the object and set the base dir
		my $base = $self->get_ftpbase();

		# Call ncftpget
		$ret = $self->_log_system(
			"/usr/bin/ncftpget " .
			"-f " . $self->get_ncftpconfig() .
			" -t -1 " .
			" -V " .
			$self->get_backupdir . "/ " .
			$self->get_fsdir() . '/' . "group*"
		);
        }

	if ( ! -e "$base/$archive" ) {
		$self->_log_msg("$archive not contained in the backup");
		return 1;
	}

	$self->_log_msg("Restoring group data");
	$self->dprint("restoring groups\n");

	# Change to proper directory..
	chdir $dir;
	$self->dprint("Changing into directory: $dir for restore");

	# Determine number of volumes.....
	# tar xM -f /archive1.tar -f /archive2.tar -f /archive3.tar

	# Determine number of volumes
	$tmp  = `/bin/ls -l $base/groups* |/usr/bin/wc -l`;
	$tmp  =~ /(\d+).*/;
	$vols = $1;

	# Build args
	$extractargs = '-xM';

	# Build command
	$cmd .= "$backupcmd ";				# command
	$cmd .= "-b 16 ";				# bs
	$cmd .= "$extractargs ";			# args

	# Build archive names
	for ( 1..$vols ) {
		$tmp  = sprintf("%03d", ( $_ - 1 ));
		$cmd .= " -f $base/groups$tmp.tar";
	}

	$self->dprint("$$: running $cmd\n");
	$self->_log_system($cmd);

	if ( $? != 0 ) {
		$self->_error_handler("Restore of groups failed: $!");
	}

	# Remove local archive if using FTP
	if ( $self->get_ftp() ) {
		unlink "$base/group*";
	}

	return 1;
}

sub _restore_cce_db
# _restore_cce_db -- Restore the cce database
#
# Arguments: Base directory where archives are located
# Returns:   Undef on failure, 1 on success
{
	my $self 	= shift || return;
	my $base	= shift || return;
	my $archive	= 'sausalito.tar';
	my $backupcfg	= $self->get_backupconfig();
	my $backupcmd   = $self->get_backupcmd();
	my $subdir	= $self->get_fsdir();
	my $dir		= $self->get_restoredir();
	my $extractargs = $self->get_extractargs();
	my ($cmd, $ret, $codbdir);

	$self->dprint("_restore_cce_db($base)\n");

        # if FTP, retrieve the archive
        if ( $self->get_ftp() ) {
		$self->_log_msg("FTP: Retrieving CCE data");

		# Get the object and set the base dir
		my $base = $self->get_ftpbase();

		# Call ncftpget
		$ret = $self->_log_system(
			"/usr/bin/ncftpget " .
			"-f " . $self->get_ncftpconfig() .
			" -t -1 " .
			" -V " .
			$self->get_backupdir . "/ " .
			$self->get_fsdir() . '/' . $archive
		);
        }

	if ( ! -e "$base/$archive" ) {
		$self->_log_msg("$archive not contained in the backup");
		return 1;
	}

	$self->_log_msg("Restoring CCE data");
	$self->dprint("restoring $archive\n");

	# Change to proper directory..
	chdir $dir;
	$self->dprint("Changing into directory: $dir for restore");

	# Build command
	$cmd .= "$backupcmd ";				# command
	$cmd .= "-b 16 ";				# bs
	$cmd .= "$extractargs ";			# args
	$cmd .= "$base/$archive";			# archive

	$self->_cced_stop();

	# Remove old codb
	$codbdir = $dir . "usr/sausalito/codb";
	$codbdir =~ s/\/\//\//g;
	if (-d $codbdir) {
		$self->dprint("Removing codb directory: $codbdir");
		$self->_log_system("/bin/rm -fr $codbdir");
	}

	# Restore the archived version
	$self->dprint("$$: running $cmd\n");
	$self->_log_system($cmd);

	$self->_cced_start();

	if ( $? != 0 ) {
		$self->_error_handler("Restore of cce database failed: $!");
	}else{
		#make sure the routes are updated
		system("/usr/sausalito/handlers/base/sauce-basic/change_route.pl -c");
	}

	# Remove local archive if using FTP
	if ( $self->get_ftp() ) {
		unlink "$base/$archive";
	}

	return 1;
}


##
##
## History functions
##
##

sub _do_enter_history
{
	my $self  	= shift;
	my $historyfile = shift || return;
	my $user 	= shift || return;
	my $errors 	= shift || $self->get_errors();
	my $name 	= $self->get_name() || "Unknown";
	my $fileset 	= $self->get_fileset();
	my $volumelabel = $self->get_label();
	my $method 	= $self->get_method();
	my $location 	= $self->get_fsmount();
	my $username 	= $self->get_username();
	my $passwd 	= $self->get_password();
	my $backup_config = $self->get_backupconfig();
	my $backupdir 	= $self->get_fsdir();
	my $backups 	= "";
	my $time;

	my $userdir;
    
	# make sure the backup metadata dir exists
	$userdir = mk_user_backupdir($self, $user);

 	$historyfile = "$userdir/$historyfile";

	#open temp history file
	open(HISTORY, "> $historyfile.$$") ||
		$self->_error_handler("Can't open $historyfile.$$: $!");

	if (-f $historyfile) {
		# there is an original file - copy it in
		open(ORIGHIST, "< $historyfile") ||
			$self->_error_handler("Can't open $historyfile: $!");

		# print it all but the last </all_backups>
		while (<ORIGHIST>) {
			if ($_ ne "</all_backups>\n") {
				print HISTORY $_;
			}
		}
		close(ORIGHIST);
	} else {
		# start a new file
		print HISTORY "<all_backups>\n";
	}
		
	# append our new record
	print HISTORY "<backup>\n";
	print HISTORY "\t<name>$name</name>\n";

	$time = $self->get_starttime();
	print HISTORY "\t<start_time>$time</start_time>\n";

	$time = $self->get_stoptime();
	print HISTORY "\t<stop_time>$time</stop_time>\n";
	print HISTORY "\t<volume_label>$volumelabel</volume_label>\n";
	print HISTORY "\t<returncode>$errors</returncode>\n";
	print HISTORY "\t<fileset>$fileset</fileset>\n";
	print HISTORY "\t<method>$method</method>\n";
	print HISTORY "\t<location>$location$backupdir</location>\n";
	print HISTORY "\t<username>$username</username>\n";
	print HISTORY "\t<password>$passwd</password>\n";
	print HISTORY "\t<backup_config>$backup_config</backup_config>\n";

	# Add list of users backed up if applicable
	my $users = $self->get_users();
	if (defined($users)) {
		print HISTORY "\t<users>$users</users>\n";
	} else {
		print HISTORY "\t<users>none</users>\n";
	}

	# Add list of groups backed up if applicable
	my $groups = $self->get_groups();
	if (defined($groups)) {
		print HISTORY "\t<groups>$groups</groups>\n";
	} else {
		print HISTORY "\t<groups>none</groups>\n"
	}

	print HISTORY "\t<owner>$user</owner>\n";
	print HISTORY "</backup>\n";

	# finish and close it
	print HISTORY "</all_backups>\n";
	close HISTORY;

	# swap it in
	unlink("$historyfile.bak");
	rename($historyfile, "$historyfile.bak");
	rename("$historyfile.$$", $historyfile);
	$self->_log_system("chgrp httpd $historyfile");
	chmod(0640, $historyfile) ||
		$self->_error_handler( "Failed to chmod $historyfile");

	return 1;
}

sub enter_backup_history
# enter_backup_history -- Add this backup to the history list
#
# Arguments: None
# Returns:   Undef on failure, 1 on success
{
	my $self = shift || return;

	# log it
	_do_enter_history($self, "history", "admin");

	return 1;
}

sub enter_pending_history
# enter_pending_history -- Add this backup to the pending list
#
# Arguments: None
# Returns:   Undef on failure, 1 on success
{
	my $self = shift || return;

	$self->dprint("Entering pending history\n");

	# log it (flag errors as pending)
	_do_enter_history($self, "pending", "admin", -1);
	$self->dprint("setting _pending=1\n");
	$self->set_pending(1);

	return 1;
}

sub delete_pending_history
# delete_pending_history -- call backup_rm_hist.pl with args
#
# Arguments: None
# Returns:   Undef on failure, 1 on success
{
	my $self = shift || return;
	my $name = $self->get_name() || "Unknown";
	my $time = $self->get_starttime();
	my $file = $self->_home_of("admin") . "/.cbackup/pending";

	if ($self->get_pending()) {
		$self->dprint("$$: running /usr/local/sbin/backup_rm_hist.pl " 
			.  "$name $time $file\n");
		$self->_log_system("/usr/local/sbin/backup_rm_hist.pl $name $time $file");
		$self->dprint("setting _pending=0\n");
		$self->set_pending(0);
	}

	return 1;
}

sub _error_mail
# _error_mail -- Sends an email to admin with failure message
#
# Arguments: Error message
# Returns:   Undef on failure, 1 on success
{
        my $self = shift || return;
        my $errors = shift || return;
        my $backupName = $self->get_name();
        my $locale=I18n::i18n_getSystemLocale();
        my $i18n=new I18n;
        $i18n->setLocale($locale);
        my $mail=new I18nMail($locale);

        # localize errors if necessary
        if($errors =~ /^\[\[.+\]\]$/) {
                $errors = $i18n->get($errors);
        }

        my $check       = $i18n->get("[[base-backup.backupCheckSettings]]");
        my $backup      = $i18n->get("[[base-backup.backup]]");
        my $date        = $i18n->get("[[base-backup.backupDate]]")
                                . ": " . localtime() . "\n";
        my $failure     = $i18n->get("[[base-backup.statusFailure]]");
        my $reason      = $i18n->get("[[base-backup.backupReason]]");
        my $localhost   = `/bin/hostname`;
        my $subject     = "$backup $backupName: $failure\n";

        my $content     = "$backupName $failure: $check.";
        $content        .= "\n$date\n$reason: $errors\n";

        $mail->setBody($content);
        $mail->setSubject($subject);
        $mail->addTo("admin\@$localhost");
        $mail->setFrom("admin\@$localhost");

        my $message = $mail->toText();

        my $sendmail = "/usr/sbin/sendmail -t -i";

        open(MAIL, "|$sendmail") or die "Failed to get sendmail pipe: $!\n";
        print MAIL $message;
        close(MAIL);

        return 1;
}

sub _generate_error_message
# _generate_error_message -- Generates the failed backup error mail
#
# Arguments: Error message
# Returns:   Mail message on success, undef on failure
{
	my $self = shift || return;
	my $errors = shift || return;
	my $backupName = $self->get_name();
	my $mail;

	# Get Locale
	#my $language = $self->_get_locale();
	#( ! $language ) ? die "Failed to get locale\n" : 1;

	# Get i18n Object
	my $i18n= new I18n;
	$i18n->setLocale(I18n::i18n_getSystemLocale());

	my $check	= $i18n->get("[[base-backup.backupCheckSettings]]");
	my $backup	= $i18n->get("[[base-backup.backup]]");
	my $date	= $i18n->get("[[base-backup.backupDate]]")
				. ": " . localtime() . "\n";
	my $failure	= $i18n->get("[[base-backup.statusFailure]]");
	my $reason	= $i18n->get("[[base-backup.backupReason]]");
	my $localhost	= `/bin/hostname`;
	my $reply	= "Reply-To: admin\@$localhost";
	my $subject	= "Subject: $backup $backupName: $failure\n";
	my $to		= "To: admin\@$localhost";

	my $content	= "$backupName $failure: $check.";
	$content	.= "\n$date\n$reason: $errors\n";

	$mail = "$reply$subject$to\n\n$content";

	return $mail;
}

##
##
## Internal helper methods
##
##

sub _create_backupdir
# _create_backupdir -- Make directory within the mount for backup files
#
# Arguments: None
# Returns:   Complete directroy to backup location on success, undef on failure
{
	my $self   = shift || return;
	my $create = $self->get_create();
	my $mount  = $self->get_mount();
	my $backupdir;

	my @t = localtime(time);
	my $datefmt = ($t[5] + 1900) .			# year
			sprintf( "%02d", ($t[4] + 1)) . # month
			sprintf( "%02d", $t[3] ) . 	# date
			sprintf( "%02d", $t[2] ) . 	# hour
			sprintf( "%02d", $t[1] ) . 	# minute
			sprintf( "%02d", $t[0] );	# second

	$self->dprint("datebase for dirs=$datefmt\n");

	$self->set_fsdir($self->get_fsdir() . "/" . $datefmt);
	$backupdir = $mount . $self->get_fsdir();
	$self->set_backupdir($backupdir);

	$self->dprint("setting _fsdir=" . $self->get_fsdir() . "\n");
	$self->dprint("setting _backupdir=" . $self->get_backupdir() . "\n");

	# Be sure it isn't already there
	if ( -d "$backupdir" ) {
		return;
	}

	# Do it doug!
	if ( $create ) {
		$self->dprint("mkdir($backupdir)\n");
		mkdir($backupdir, oct("0700")) 
			or $self->_error_handler("mkdir($backupdir): $!");
	}

	return $backupdir;
}

sub _mk_tmp_mount
# _mk_tmp_mount -- Create temp directory to mount filesystem into
#
# Arguments: None
# Returns:   temp directory on success, undef on failure
{
	my $self = shift || return;
	my $cnt = 50;
	my $mountpoint;
	my $ret; 

	while ($cnt--) {
		# Make temp mountpoint
		$mountpoint = POSIX::tmpnam();
		$ret = mkdir("$mountpoint", oct("0700"));
		if ($ret) {
			# set the local mount point
			$self->set_mount($mountpoint);
			$self->dprint("setting _mount=$mountpoint\n");
			return $mountpoint;
		}
	}

	$self->_error_handler("Failed to create mountpoint");

	return;
}

# :: CCEd Control Methods :: #
sub _cced_stop
# _cced_stop -- Stop the cce deamon
#
# Arguments: None
# Returns:   Undef
{
	my $self = shift || return;

	# Set lock info
	$self->_lock_cced();

	$self->dprint("Set lock file\n");

	$self->set_enablecce(1);
	$self->_log_system ("/etc/rc.d/init.d/cced.init stop") or
		$self->_error_handler("Failed to stop the cce deamon");

	return 1;
}

sub _cced_start
# _cced_start -- Start the cce deamon
#
# Arguments: None
# Returns:   Undef
{
	my $self = shift || return;

	# Set lock info
	$self->_unlock_cced();

	$self->_log_system("/etc/rc.d/init.d/cced.init start") or
		$self->_error_handler("Failed to start the cce deamon");
	$self->set_enablecce(0);

	return 1;
}

sub _lock_cced
# _lock_cced -- Create message file for cced being down, and hold a cce lock
#
# Arguments: None
# Returns:   Undef
{
	my $self = shift || return;
	my $messagefile = '/usr/sausalito/.ccedmsg';
	my $lockfile = '/usr/sausalito/cced.lock';
	my $message	= '[[base-backup.backupCCEDmessage]]';

	#
	# first create a cced.message
	#

	# Get Locale
	#my $language = $self->_get_locale();
	#( ! $language ) ? die "Failed to get locale\n" : 1;

	# Get i18n Object
	my $i18n= new I18n;
	$i18n->setLocale(I18n::i18n_getSystemLocale());

	open( MSG, ">$messagefile" ) or
		$self->_error_handler("Failed to open cced lock file: $!");

	print MSG $message;

	close( MSG );

	my $mode = 0644;
	chmod $mode, $messagefile;

	#
	# create and hold the lock file (JIC something tries to start CCE)
	#

	open(LOCK, ">$lockfile");
	$self->set_lockfile(*LOCK);

	return 1;
}

sub _unlock_cced
# _unlock_cced 
#
# Arguments: None
# Returns:   Undef
{
	my $self = shift || return;
	my $messagefile = '/usr/sausalito/.ccedmsg';
	my $lockfile = '/usr/sausalito/cced.lock';
	my $fh = $self->get_lockfile();

	close($fh);
	unlink($messagefile);
	unlink($lockfile);
}

sub mk_user_backupdir
# mk_user_backupdir -- make a ~/.cbackup directory for a user
#
# Arguments: Username
# Returns:   Directory on success, undef on failure
# ----
# FIXME: should call this early to make sure it exists 
# ----
{
	my $self = shift || return;
	my $user = shift || return;
	my $dir;

	$dir = $self->_home_of($user) . "/.cbackup";

	# make the user dir
	if (! -d $dir){
		mkdir($dir, oct("0750")) ||
			$self->_error_handler("Failed to mkdir() $dir");
	}
		
	# Change ownership to $user/users
	my $uid = getpwnam($user);
	my $gid = getgrnam("httpd");
	chown($uid, $gid, $dir) 
		|| $self->_error_handler("Failed to chown() $dir");

	# Change ownership to root/wheel.
	#chown(0, 10, $dir) || $self->_error_handler("Failed to chown $dir");

	return $dir;
}

sub _get_locale
# _get_locale -- Get the locale of the admin user
#
# Arguments: None
# Returns:   Locale of admin, undef on failure
{
	my $self = shift || return;	
	my $language;

	my $cce = new CCE;
	$cce->connectuds();

	my (@oids) = $cce->find("User", { "name" => "admin" } );
	my ($ok, $object) = $cce->get( $oids[0] ) if (@oids > 0);
	$language = ${$object}{'localePreference'} if ($ok);
	$cce->bye("SUCCESS");

	if ( ! $language ) {
		die "Failed to get locale\n";
	}

	chomp $language;

	# success
	return $language if (length $language > 0 && $language ne "browser");

	# failure
	# Hard-coded for now... (mikew)
	return "en";
}

sub _home_of
# _home_of -- Return the specified users home directory
#
# Arguments: Username
# Returns:   User's home directory on success, Undef on failure
{
	my $self = shift || return;
	my $user = shift || return;
	my ($name, $dir);

	setpwent();

	# Step through the password file
	($name, $dir) = (getpwent())[0,7];
	while (defined($name) && $name) {
		if ($name eq $user) {
			return $dir;
		}
		($name, $dir) = (getpwent())[0,7];
	}

	endpwent();

	return;
}

sub _log_msg
# _log_msg
#
# Arguments: string to log
# Returns: 1
{
	my $self = shift || return;
	my $str = shift || return;
	my $timestamp = scalar localtime;
	if ($str !~ /\n$/) {
		# make sure the log message ends with at least 1 carriage return
		$str .= "\n";
	}
	$self->_log_system("/bin/echo -n $timestamp $$: \"$str\"");

	return 1;
}

sub _log_system
# _log_system
#
# Arguments: cmd to run
# Returns: result of system
{
	my $self = shift || return;
	my $cmd = shift || return;
	my $log = $self->get_log();

	system("$cmd >> $log 2>&1");

	if ( $? != 0 ) { 
		print STDERR "system error: $!, $?\n";
		return $?; 
	} 
	
	return 1;
}

sub _log_exec
# _log_exec
#
# Arguments: params to exec
# Returns: normally doesn't, undef on error
{
	my $self = shift || return;
	my @args = @_;
	my $log = $self->get_log();

	close(STDOUT);
	close(STDERR);
	open(STDOUT, ">>$log");
	open(STDERR, ">>$log");

	exec(@args) || die "exec(@args): $!\n";
}

sub dprint
# dprint -- Print out debugging information
#
# Arguments: Message String
# Returns:   Undef
{
	my $self = shift || return;
	my $string = shift || return;

	# Are we in debugging mode?
	if ($self->get_debug()) {
		print STDERR $string;
		$self->_log_msg("$string");
	}
	
	return 1;
}

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
