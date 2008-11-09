# $Id: Workgroup.pm 3 2003-07-17 15:19:15Z will $
# Workgroup.pm
#
# Tools for dealing with workgroups.

package Workgroup;

use CCE;
use FileHandle;

# edit a file, line by line, in a safe way
sub editfile
{
  my $filename = shift;
  my $function = shift;
  
  my $lockfile = $filename;
  
  # save away the file permissions
  my $perms = "";
  if (-f $filename) {
	$perms = (stat($filename))[2] & 07777;
  }

  $lockfile =~ s/[^a-zA-Z0-9\-\.]+/_/g;
  $lockfile = "/var/lock/editlock_" . $lockfile;
  
  # lock the file
  for (my $i = 0; $i < 5; $i++) {
  	last if (!-e $lockfile);
    sleep(1);
  }
  my $fh = new FileHandle(">$lockfile");
  if ($fh) {
  	print $fh $$,"\n";
    $fh->close();
  }
  
  # move file to a backup location
  my $backupfile = $filename . '~';
  unlink($backupfile) if (-e $backupfile);
  if (!link($filename, $backupfile)) {
  	print STDERR "Could not create $backupfile: $!\n";
  	return 0; # fail
  }
  if (!unlink($filename)) {
  	print STDERR "Could not unlink $filename: $!\n";
    return 0; # fail
  }

	# open for reading  
  my $fin = new FileHandle("<$backupfile");
  if (!$fin) {
  	print STDERR "Could not open $backupfile for reading: $!\n";
    return 0;
  }
  
  # open for writing
  my $fout = new FileHandle(">$filename");
  if (!$fout) {
  	print STDERR "Could not open $filename for writing: $!\n";
    return 0;
  }
  
  # process file
  my $ret = &$function($fin, $fout);
  
  # close file handles
  $fout->close();
  $fin->close();
  
  # make sure file permissions are preserved
  if($perms) {
	chmod($perms, $filename);
  }

  # unlock the file
  unlink($lockfile);
  
  # return success
  return $ret;
}
  

# validate the current object
sub validate
{
	my $cce = shift;
  my $errors = 0;

	$errors += $cce->validate('name', qr/^[a-zA-Z0-9\.\-\_]{1,12}$/);
  $errors += $cce->validate('quota', qr/^\d*$/);
  $errors += $cce->validate('members', qr/^(:[a-zA-Z0-9\.\-\_:]*:)?$/);
 # $errors += $cce->validate('enabled', qr//);
  
  if ($errors) {
  	$cce->bye('FAIL');
    exit 1;
  }
	return 0;
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
