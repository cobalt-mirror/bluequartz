#!/usr/bin/perl -w
# $Id: Util.pm 1050 2008-01-23 11:45:43Z mstauber $
# author: jmayer@cobalt.com

package Sauce::Util;
use FileHandle;

use strict;
use vars qw( $DEFAULT_MODE );

$DEFAULT_MODE = 0640;
my $TXNFILE = '/usr/sausalito/codb/txn/current.handlerlog';

my $DEBUG = 0;
if ($DEBUG)
{   
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

sub SAFEMODE { O_WRONLY | O_CREAT | O_EXCL };

sub escape_perl_metachars
{
    $_ =~ s/([\\\|\(\)\[\{\^\$\*\+\?\.])/\\$1/g;
    return $_;
}

sub txnaddline
{
	my $marker = shift;
	my $string = shift;
	$DEBUG && warn "$marker $string\n";
	unless (open (HANDTXNLOG, ">>$TXNFILE")) {
		$DEBUG && warn "Could not open $TXNFILE: $!\n";
		 return 0;
	}
	print HANDTXNLOG "$marker $string\n";
	close (HANDTXNLOG);
	return 1;
}

sub addrollbackcommand
{
	my $comm = join(' ', @_);
	return txnaddline('R', $comm);
}

sub addcleanupcommand
{
	my $comm = shift;
	return txnaddline('C', $comm);
}

sub addcomment
{
	my $comm = shift;
	return txnaddline('#', $comm);
}

sub findfreefilename
{
	my $filename = shift;
	$filename .= '.backup.';
	my $extension = 1;
	while (-e ($filename . $extension)) {
		$extension++;
		if ($extension > 1000) {
			$DEBUG && warn 'Can\'t find free $filename';
			$filename .= 'backup.';
			$extension = 1;
		}
	}
	return $filename . $extension;
}



#
# Description:
#	Creates a copy of a file before and adds appropriate rollback commands
# to retrieve the copy in the case of a transaction abort.
#
# Inputs:
#
#	Parameter               I/O     Purpose
#	----------------------  ---     ------------------------------------
# 	filename		I	The file being modified by the txn
#
# Outputs:
#
#	Return Codes            Purpose
#	----------------------  --------------------------------------------
#	0			Failure.  The system could not make a copy
#				of the file.
#	1			Success.  The file was copied and the
#				appropriate commands added to provide rollback
#
# Notes:
#
#
sub modifyfile
{
	my $filename = shift;
	my $newfilename;

	$newfilename = findfreefilename($filename);

	addcomment("modifying $filename using $newfilename as backup");

	if (-e $filename) {
		# copy to newfilename
		my $rc = system("cp -p \"$filename\" \"$newfilename\"");
		if ($rc == 0) {
			# set up for rollback
			addrollbackcommand("rm \"$newfilename\"");
			addrollbackcommand("cp -p \"$newfilename\" \"$filename\"");
			# and for cleanup
			addcleanupcommand("rm \"$newfilename\"");
		} else {
			#
			# Anything but 0 is failure.  Ignore translating the
			# result and return failure.
			#
			return 0;
		}
	} else {
		addrollbackcommand("rm \"$filename\"");
	}

	return 1;
}


#
# Description:
#	Unlink a file in a rollback-compatible manner.
#
# Inputs:
#
#	Parameter               I/O     Purpose
#	----------------------  ---     ------------------------------------
#	filename		I	The file to unlink
#
# Outputs:
#
#	Return Codes            Purpose
#	----------------------  --------------------------------------------
#	0			Failure.  The file could either not be modifed
#				for rollback or the unlink failed.
#	1			Success.  The file was modified for rollback
#				and the original unlinked.
#
# Notes:
#
#
sub unlinkfile
{
	my $filename = shift;
	my $result;

	addcomment("deleting $filename");
	$result = Sauce::Util::modifyfile($filename);
	if ($result == 0) {
		# Could not make a backup copy of the file.  Fail.
		return $result;
	}

	# Return the number of files successfully unlinked.
	return unlink($filename);
}


#
# Description:
#	Changes the mode of a file in a rollback-safe manner.
#
# Inputs:
#
#	Parameter               I/O     Purpose
#	----------------------  ---     ------------------------------------
#	mode			I	The new mode
#	filename		I	The file to modify
#
# Outputs:
#
#	Return Codes            Purpose
#	----------------------  --------------------------------------------
#	0			Failure.  The mode of the file could not be
#				changed.
#	1			Success.  The mode of the file was changed
#				and appropriate rollback commands added.
# Notes:
#	$! contains the system error on failure.
#
sub chmodfile
{
	my $mode = shift;
	my $filename = shift;
	my $oldmode;
	my $result;

	my @stats = stat($filename);
	$oldmode = sprintf("%04o", $stats[2] & 07777);

	$result = chmod $mode, $filename;
	if ($result == 1) {
		# Success.  Add a rollback command
		addrollbackcommand("chmod $oldmode \"$filename\"");

		# Fall through and return the result
	}

	return $result;
}

sub chownfile
{
	my $owner = shift;
	my $group = shift;
	my $filename = shift;
	my @stats = stat($filename);
	my $result;

	#
	# The result of chown is the number of files modified.  Since
	# we are modifying 1 file, 1 == success and 0 == failure.  Excellent.
	#
	$result = chown $owner, $group, $filename;
	if ($result) {
		# Success
		addcomment("chown $owner $group $filename");
		addrollbackcommand("chown $stats[4]:$stats[5] \"$filename\"");

		# Fall through and return the result
	}

	return 1;
}


sub makedirectory
{
	my $filename = shift;
	my $mode = shift;
	my $result = 1;
	if (! -e $filename) {
		$result = mkdir($filename, $mode);
		if ($result == 1) {
			# Success.  Add rollback commands
			addcomment("making directory $filename");
			addrollbackcommand("rmdir \"$filename\"");

			# Fall through and return result
		}
	} elsif (-d $filename) {
		# The directory already exists
		$result = 1;
	} else {
		# A file with the requested name exists, but is not a directory
		$result = 0;
	}

	return $result;
}

sub linkfile
{
	my $filename1 = shift;
	my $filename2 = shift;

	if (! -e $filename2) {
		symlink ($filename1, $filename2);
		addcomment("linking $filename1 and $filename2");
		addrollbackcommand("rm \"$filename2\"");
	}
	return 1;
}

sub renamefile
{
	my ($filename, $newfilename) = (shift, shift);

	rename($filename, $newfilename);
	addrollbackcommand("mv \"$newfilename\" \"$filename\"");

	return 1;
}

sub copyfile
{
	my ($filename, $newfilename) = (shift, shift);

	system("cp \"$filename\" \"$newfilename\"");
	addrollbackcommand("rm \"$newfilename\"");

	return 1;
}

sub modifytree
{
	my $filename = shift;
	my $newfile = findfreefilename($filename);

	mkdir($newfile, 0700);
	system("tar --one-file-system -cf - \"$filename\" | tar -C \"$newfile\" -xpf -");
	addrollbackcommand("rm -rf \"$newfile\"");
	addrollbackcommand("tar -C \"$newfile\" -clf - . | tar -C \"$filename\" -xpf -");
	addrollbackcommand("rm -rf \"$filename\"");
	addcleanupcommand("rm -rf \"$newfile\"");

	return 1;
}

sub keyvalue_edit_fcn
# a convenient routine for key=value editing
# pass in ($input, $output, $comments, $separator, $options, %settings)
# options: noappend,nosave
{
    my ($input, $output, $comments, $sep, $options, %settings) = @_;
    my $keys = ':' . join(':', keys %settings) . ':';
    my ($spaces,$key);
    
    while (<$input>) {
        # skip comments
        if ($comments and /^\s*[$comments]/o) {
            print $output $_;
            next;
        }

	# find a key=value, strip away pre- and post-spaces
	if (/^(\s*)(.+?)\s*$sep/ and not ($options =~ /\bnosave\b/)) {
	    ($spaces, $key) = ($1, $2);
	    if ($keys =~ /:$key:/) {
		print $output "$spaces$key$sep$settings{$key}\n";
		delete $settings{$key};
		next;
	    }
	}
	print $output $_;
    }
	
    # append anything that's missing
    unless ($options =~ /\bnoappend\b/) {
	foreach $key (keys %settings) {
	    print $output "$key$sep$settings{$key}\n";
	}
    }

    return 1;
}

sub hash_edit_function
# inspired by keyvalue_edit_fcn, but gives greater control over how the file is
# edited
# arguments are 
# ($input, $output, $comments, \%seperator , \%modifications_hash, $delete_not_found)
# $input and $ouput are the already opened input and output file handles
# $comments is a string with characters that when found at the beginning of a line
# 			should be skipped as comment lines (eg $comments = "#" skips lines that
#			begin with "#")
# \%seperator is a hash reference to a hash structered as follows
#			$seperator = {
#				# regular expression used to seperate a line into a
#				# key and a value 
#				# (eg. ($key, $value) = split(/$seperator->{re}/, $line))
#				're' => 'seperator regex',
#				
#				# actual text to put in when writing a key value
#				# pair to $output
#				# (eq print $output "$key$seperator->{val}$value\n"
#				'val' => 'seperator'
#			 };
# \%modifications_hash is a hash reference to a hash describing which pairs to edit
# 			in the input file
#			for example:
# 			$modifications_hash = {
#				'foo' => 'bar',
#				'bar' => ''
#			  };
#			If there was a key-value pair with key=foo, the value would be changed
#			to bar, or if the key foo doesn't exist it would be added to the file
#			with value bar.
#			If there is a key-value pair with key=bar, that line would be removed
#			from the file.
# $delete_not_found indicates whether a key found in the input file that is not found
#			in the modification hash should be removed from the file.  This defaults
#			to false, so pairs not in the modification hash are untouched.
{
	my $in = shift;
	my $out = shift;
	my $comments = shift;
	my $sep = shift;
	my $mod_hash = shift;
	my $delete_not_found = shift;

	$delete_not_found ||= 0;

	while (<$in>)
	{
		# skip comments
		if (/^\s*[$comments]/) 
		{
			print $out $_;
			next;
		}
		# skip section blocks like [Main Config File]
		if (/^\s*\[(.*)\]/) 
		{
			print $out $_;
			next;
		}

		# need to split up the line, so we can know exactly what to look for
		my @parts = split(' ');

		# save leading whitespace, although it probably doesn't matter
		/^(\s*)$parts[0]/;
		my $space = $1;

		if (join(' ', @parts) =~ /^(\S+) $sep->{re}/)
		{
			my $key = $1;

			if (exists $mod_hash->{$key})
			{
				# indicate key deletion by an existing key with no value
				if ($mod_hash->{$key} ne "")
				{
					print $out "$space$key$sep->{val}$mod_hash->{$key}\n";
				}
				delete $mod_hash->{$key};
			}
			elsif (not $delete_not_found)
			{
				print $out $_;
			}
		}
		else
		{
			print $out $_;
		}
	}

	# add any values that didn't have a previous entry in the file
	for my $key (keys %$mod_hash)
	{
		if ($mod_hash->{$key} ne "")
		{
			print $out "$key$sep->{val}$mod_hash->{$key}\n";
		}
	}

	return 1;
}

# $ok = lockfile ($filename)
#
# we're not using flock because flock seems to be broken in Perl on mips.
sub lockfile
{
  my $filename = shift;

  my $lockfile = $filename;
  $lockfile =~ s/[^a-zA-Z0-9\-\.]+/_/g;
  $lockfile = "/var/lock/editlock_" . $lockfile;
  
  # lock the file
  for (my $i = 0; $i < 5; $i++) {
  	last if (!-e $lockfile);
    sleep(1);
  }
  unlink($lockfile);
  my $fh = new FileHandle("$lockfile", SAFEMODE);
  if ($fh) {
  	print $fh $$,"\n";
    $fh->close();
  }
  
  return 1; # ok
}

# $ok = unlockfile ($filename)
#
# we're not using flock because flock seems to be broken in Perl on mips.
sub unlockfile
{
	my $filename = shift;

  my $lockfile = $filename;
  $lockfile =~ s/[^a-zA-Z0-9\-\.]+/_/g;
  $lockfile = "/var/lock/editlock_" . $lockfile;
  
  # unlock the file
  unlink($lockfile);

  return 1; # ok
}


# $ok = editfile ($filename, $function, ...)
#
# Used to edit $filename in a safe, consistent way.
#
# $function is executed such that $_[0] is the input filehandle,
# $_[1] is the output filehandle, and $_[2...] contains any additional
# data passed into editfile.  The function provided must return 1 on success
# and 0 on failure; failed edit fuctions will prevent the modification of a
# file from taking place.
#
sub editfile
{
  my $filename = shift;
  my $function = shift;

  Sauce::Util::lockfile($filename);
  Sauce::Util::modifyfile($filename);
  
  # attempt to handle the case where the file is missing:
  if (!-e $filename) {
      print STDERR "Warning: File $filename was missing.\n";
      my $fh = new FileHandle("$filename", SAFEMODE);
    if ($fh) {
      $fh->close();
    } else {
      Sauce::Util::unlockfile($filename);
      print STDERR "Could not create $filename: $!\n";
      return 0; # fail
    }
  }

  # move file to a backup location
  my $newfile = $filename . "~";

  # open for reading  
  my $fin = new FileHandle("<$filename");
  if (!$fin) {
    Sauce::Util::unlockfile($filename);
    print STDERR "Could not open $filename for reading: $!\n";
    return 0;
  }
  
  # open for writing
  my @statbuf = stat($filename);
  unlink($newfile);
  my $fout = new FileHandle("$newfile", SAFEMODE, $statbuf[2]);
  if (!$fout) {
    Sauce::Util::unlockfile($filename);
    print STDERR "Could not open $newfile for writing: $!\n";
    return 0;
  }
  
  # process file
  my $oldfh = select(); # save stdout file handle
  my $ret = &$function($fin, $fout, @_);
  
  my $commz = join(' ', @_);
  
  &debug_msg("Util.pm:editfile editing $filename - function: $function payload: $commz");
  select($oldfh); # restore stdout file handle
  
  # close file handles
  $fout->close();
  $fin->close();

  # set up permissions:
  copy_access_bits($filename, $newfile);
  # defined($fmode) && chmod($fmode, $newfile);

  #
  # Replace the old file with the new file ony if the editing
  # function returned 1 (success) and does not contain the
  # string 'FAIL' for historical reasons.
  #
  if ($ret =~ m/^FAIL/) {
    # The edit function used a deprecated failure mode.
    $ret = 0;
  }
  if ($ret) {
    # The edit function succeeded.  Swap the files now.
    switch_files($filename, $newfile);
  }

  # unlock the file
  Sauce::Util::unlockfile($filename);

  # remove the backup
  unlink($newfile);
  
  return $ret;
}

# my $ok = process_template($templatefile, $hashref)
sub process_template
{
  my $filename = shift;
  my $hashref = shift;
  my $fmode = shift || $DEFAULT_MODE;

  my $outfile = $filename;
  $outfile =~ s/\.te?mpla?t?e?$//;
  if ($outfile eq $filename) { $outfile .= ".out"; }
  
  my $tmpfile = $outfile . "~";
    
  # lock
  Sauce::Util::lockfile($outfile);  
  Sauce::Util::modifyfile($filename);

  # attempt to handle the case where the file is missing:
  if (!-e $filename) {
    Sauce::Util::unlockfile($outfile);
    print STDERR "Missing file: $filename\n";
    return 0;
  }

  # open for reading  
  my $fin = new FileHandle("<$filename");
  if (!$fin) {
    Sauce::Util::unlockfile($outfile);
    print STDERR "Could not open $filename for reading: $!\n";
    return 0;
  }
  
  # open for writing
  my @statbuf = stat($filename);
  unlink($tmpfile);
  my $fout = new FileHandle($tmpfile, SAFEMODE, $statbuf[2]);
  if (!$fout) {
    Sauce::Util::unlockfile($outfile);
  	print STDERR "Could not open $tmpfile for writing: $!\n";
    return 0;
  }
      
  while (defined($_ = <$fin>)) {
    s/\$\{(\S+?)\}/$hashref->{$1}/g;
    print $fout $_;
  } 

  # close file handles
  $fout->close();
  $fin->close();
  
  # set up permissions:
  if (-e $outfile) {
    copy_access_bits($outfile, $tmpfile);
  }
  defined($fmode) && chmod($fmode, $tmpfile);

  # pull the old file switcheroo:
  if (-e $outfile) {
    switch_files($outfile, $tmpfile);
  } else {
    link($tmpfile, $outfile);
  }

  # unlock
  Sauce::Util::unlockfile($outfile);  

  # remove the backup
  unlink($tmpfile);
  
  return 1;
}

# editblock( $filename, $fun, $start_line, $end_line, @args );
#
# Caveat: start_line and end_line must start at the beginning of a new line.
#
# FIXME: there is no way to add an optional "mode" arguement to this
# function w/o breaking legacy code.  :-(  All code using this function
# should be fixed.
sub editblock
{
  my $filename = shift;
  my $fun = shift;
  my $start_line = shift;
  my $end_line = shift;
  my @args = @_;
  
  my $ret = 1;

  Sauce::Util::lockfile($filename);
  Sauce::Util::modifyfile($filename);
  
  # attempt to handle the case where the file is missing:
  if (!-e $filename) {
  	print STDERR "Warning: File $filename was missing.\n";
  	my $fh = new FileHandle($filename, SAFEMODE);
    if ($fh) {
    	$fh->close();
    } else {
    	print STDERR "Could not create $filename: $!\n";
      Sauce::Util::unlockfile($filename);
      return 0; # fail
    }
  }

  # open for reading  
  my $fin = new FileHandle("<$filename");
  if (!$fin) {
    print STDERR "Could not open $filename for reading: $!\n";
    Sauce::Util::unlockfile($filename);
    return 0;
  }

  # Open the three temporary files (Start, insection and endof.)
  unlink( $filename . ".start");
  my $fstart = new FileHandle("$filename.start", SAFEMODE);
  if (!$fstart) {
	  print STDERR "Could not open $filename.start for writing: $!\n";
    Sauce::Util::unlockfile($filename);
	  return 0;
  }

  unlink( $filename . ".middle" );
  my $fmiddle = new FileHandle("$filename.middle", SAFEMODE);
  if (!$fmiddle) {
	  print STDERR "Could not open $filename.middle for writing: $!\n";
    Sauce::Util::unlockfile($filename);
	  return 0;
  }

  unlink ( $filename . ".end" );
  my $fend = new FileHandle("$filename.end", SAFEMODE);
  if (!$fend) {
	  print STDERR "Could not open $filename.end for writing: $!\n";
    Sauce::Util::unlockfile($filename);
	  return 0;
  }

  # precompile regexi:
  my $start_re = qr/^$start_line\s*$/;
  my $end_re = qr/^$end_line\s*$/;
  
  # split file into three sections
  my $mode = 'start';
  while (defined($_ = <$fin>)) {
  	if ($mode eq 'start') {
    	# initial state
    	if ($_ =~ $start_re) {
      	# encountered start tag
      	$mode = 'middle';
	# We don;t print it so step on.
	next;
      }
      print $fstart $_;
    } elsif ($mode eq 'middle') {
      # inside block state 
      if ($_ =~ $end_re) {
      	# encountered an end tag
      	$mode = 'end';
	next;
      }
      print $fmiddle $_;
    } else {
      print $fend $_;
    }
  } # end of while loop

  # close input handle;
  $fin->close();
  # Close our three section handler;
  $fstart->close();
  $fmiddle->close();
  $fend->close();

  #
  # Now we call editfile on just the section that the user wanted.
  # FIXME: if the edit function fails, we need to clean up the temporary files
  # before returning.
  #
  $ret = editfile("$filename.middle",$fun,@args);
  if (! $ret) {
    # The edit function has failed.
    Sauce::Util::unlockfile($filename);
    return $ret;
  }

  unless( $fstart->open("<$filename.start") &&
	  $fmiddle->open("<$filename.middle") &&
	  $fend->open("<$filename.end") ) 
  {
    print STDERR "Could not reopen all files for $filename: $!\n";
    Sauce::Util::unlockfile($filename);
    return 0;
  }

  # backup file name
  my $backupfile = $filename . '~';

  # copy to a backup file now
  unlink($backupfile);
  my $fout = new FileHandle($backupfile, SAFEMODE, 0600);
  $fout->print( join('',<$fstart> ) );
  $fout->print( $start_line . "\n" );
  $fout->print( join('',<$fmiddle> ) );
  $fout->print( $end_line . "\n" );
  $fout->print( join('',<$fend> ) );

  # Close out main file.
  $fout->close;

  # now, swap backup and new files
  switch_files($filename, $backupfile);

  # Close all out sub files.
  $fstart->close();
  $fmiddle->close();
  $fend->close();

  # Clean up out temp files.

  unlink("$filename.start");
  unlink("$filename.middle");
  unlink("$filename.end");

  # unlock the file
  Sauce::Util::unlockfile($filename);

  # remove the backup
  unlink("$backupfile");
  
  # return success
  return $ret;
}

# replaceblock( $filename, $start_line, $data, $end_line, $filemode );
#
# replaces the section of $filename denoted by the $start_line and
# $end_line with the contents of $data.
#
# Caveat: if $data is an empty string, the (empty) block is left in
# place in the file as a placeholder.  OTOH, if $data is undefined,
# the block (including start and stop tags) are completely removed
# from the file.
#
# Caveat: start_line and end_line must start at the beginning of a new line.
sub replaceblock
{
  my $filename = shift;
  my $start_line = shift;
  my $data = shift;
  my $end_line = shift;
  my $fmode = shift || undef;
  
  my $ret = 1;

  Sauce::Util::lockfile($filename);
  Sauce::Util::modifyfile($filename);
  
  # attempt to handle the case where the file is missing:
  if (!-e $filename) {
    print STDERR "Warning: File $filename was missing.\n";
    unlink($filename);
    my $fh = new FileHandle("$filename", SAFEMODE);
    if ($fh) {
    	$fh->close();
    } else {
      print STDERR "Could not create $filename: $!\n";
      Sauce::Util::unlockfile($filename);
      return 0; # fail
    }
  }

  # make a place for the new edition:
  my $newfile = $filename . '~';
  unlink($newfile) if (-e $newfile);

  # open for reading  
  my $fin = new FileHandle("<$filename");
  if (!$fin) {
    print STDERR "Could not open $filename for reading: $!\n";
    Sauce::Util::unlockfile($filename);
    return 0;
  }


  # open for writing
  my @statbuf = stat($filename);
  unlink($newfile);
  my $fout = new FileHandle("$newfile", SAFEMODE, $statbuf[2]);
  if (!$fout) {
    print STDERR "Could not open $newfile for writing: $!\n";
    Sauce::Util::unlockfile($filename);
    return 0;
  }

   # precompile regexi:
  my $start_re = qr/^$start_line\s*$/;
  my $end_re = qr/^$end_line\s*$/;
  
 
  # process file:
  my $mode = 'init';
  while (defined($_ = <$fin>)) {
    if ($mode eq 'init') {
      # initial state (print anything but a start tag)
      if ($_ =~ $start_re) {
        # encountered start tag
        $mode = 'inside';
        if (defined($data)) {
          print $fout $start_line,"\n",$data,"\n",$end_line,"\n";
        }
        next;
      }
      print $fout $_;
      next;
    }
    if ($mode eq 'inside') {
      # inside block state (don't print anything)
      if ($_ =~ $end_re) {
        # encountered an end tag
        $mode = 'outside';
      }
      next;
    }
    if ($mode eq 'outside') {
      # past block state (print anything)
      print $fout $_;
      next;
    }
  } # end of while loop
  if ($mode eq 'init' && defined($data)) {
    # tag was never found, so let's make one:
    print $fout $start_line,"\n",$data,"\n",$end_line,"\n";
  }
    
  # close file handles
  $fout->close();
  $fin->close();
  
  # set up permissions:
  copy_access_bits($filename, $newfile);
  defined($fmode) && chmod($fmode, $newfile);

  # pull the old file switcheroo:
  switch_files($filename, $newfile);

  # unlock the file
  Sauce::Util::unlockfile($filename);

  # remove the backup
  unlink($newfile);
  
  # return success
  return $ret;
}

sub switch_files
{
  my ($f1, $f2) = (shift, shift);

  if (!-e $f1) { return -9; }
  if (!-e $f2) {
    link $f1, $f2;
    return 1;
  }
  
  my $tmp = $f1 . ".$$.tmp";

  do { link ($f1, $tmp) } || return -1;
  unlink($f1) || do {
    # undo
    unlink $tmp;
    return -2;
  };
  
  link ($f2, $f1) && unlink($f2) || do {
    # undo
    unlink($f1);
    link ($tmp, $f1) && unlink($tmp);
    return -3;
  };
  
  link ($tmp, $f2) || do {
    # undo
    link($f1, $f2);
    unlink($f1);
    link ($tmp, $f1) && unlink($tmp);
    return -4;
  };
  unlink($tmp);
  return 1;
} 
  

sub copy_access_bits
{
  my ($srcfile, $destfile) = (shift, shift);

  my @buf;
  @buf = stat($srcfile);
  if (!@buf) {
    print STDERR "Couldn't stat $srcfile\n";
    return 0;
  }
  
  my $mode = $buf[2] & 07777;
  chmod($mode, $destfile) || do {
    print STDERR "Couldn't chmod $mode, $destfile\n";
    return 0;
  };
  
  my $uid = $buf[4];
  my $gid = $buf[5];
  chown ($uid, $gid, $destfile) || do {
    print STDERR "Couldn't chown $destfile\n";
    return 0;
  };
  
  return 1;
}

sub force_access_permissions
{
  my $file = shift;
  my $uid = shift;
  my $gid = shift;
  my $mode = shift;
  
  my @buf = stat($file);
  if (!@buf) {
    print STDERR "$0: file \"$file\" unexpectedly missing.\n";
    return 0;    
  }
  
  if (!int($uid)) {
    my @pwent = getpwnam($uid);
    if (!@pwent) {
      print STDERR "$0: user \"$uid\" does not exist.\n";
      return 0;
    }
    $uid = $pwent[2];
  }

  if (!int($gid)) {
    my @ent = getgrnam($gid);
    if (!@ent) {
      print STDERR "$0: group \"$gid\" does not exist.\n";
      return 0;
    }
    $gid = $ent[2];
  }
  
  {
    my $flag = 0;
    if ($buf[4] != $uid) {
      print STDERR "$0: file \"$file\" has wrong uid($buf[4]),",
      	" correcting to uid($uid).\n";
      $flag = 1;
    }
    if ($buf[5] != $gid) {
      print STDERR "$0: file \"$file\" has wrong gid($buf[5]),",
      	" correcting to gid($gid).\n";
      $flag = 1;
    }
    if ($flag) {
      if (!(chown $uid, $gid, $file)) {
      	print STDERR "$0: Could not chown file \"$file\".\n";
      }
    }
  };
  
  if ($buf[2] != $mode) {
    print STDERR "$0: file \"$file\" had incorrect mode 0",oct($buf[2]),"\n";
  }
}

sub replace_unique_entries
{
  my ($fin, $fout) = (shift, shift);
  my $oid = shift;
  my $hash = shift;
  my $inside = 0;
  my $startline = "# Object ID $oid owns these lines:";
  my $stopline ="# End of Object ID $oid section";
  
  my $errs = 0;
  
  while (defined($_ = <$fin>)) {
    if ($inside != 1) {
      print $fout $_;
      if ((!m/^\s*#/) && (m/^\s*(\S+?):\s+(.*)/)) {
      	# check uniqueness
	if (defined($hash->{$1})) {
	  $errs++;
	}
      }
      if (m/^$startline/) { 
      	# dump contents
	foreach my $k (keys %$hash) {
	  print $fout "$k:\t",$hash->{$k},"\n";
	}
      	$inside = 1; 
      }
      next;
    } else {
      if (m/^$stopline/) { print $fout $_; $inside = 3; }
      next;
    }
  }
  
  if ($inside == 0) {
    print $fout $startline,"\n";
    foreach my $k (keys %$hash) {
      print $fout "$k:\t",$hash->{$k},"\n";
    }
    print $fout $stopline,"\n";
  }
  
  if ($errs) { return "FAIL"; }
  return 1;
}

sub debug_msg { 
    if ($DEBUG) { 
        my $msg = shift;
        my $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
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
