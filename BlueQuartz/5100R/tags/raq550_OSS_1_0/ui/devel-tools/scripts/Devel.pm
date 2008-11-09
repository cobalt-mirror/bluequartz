# common functions used between devel scripts
# maybe other stuff too

package Devel;

use strict;
use FileHandle;
use Exporter;

use vars qw(@ISA @EXPORT);

@ISA = qw(Exporter);
@EXPORT = qw(make_cmd cvs_cmd check_out_modules get_module_tag);

 
sub make_cmd
{
  unlink("/tmp/make.log");
  system("make @_ &> /tmp/make.log");
  my $r = $?;

  if($r & 128){  #stupid random core dumps.  retry once if we get one of those.
    system("make @_ &> /tmp/make.log");
    $r = $?;
  }

  if ($r) {
    my $fh = new FileHandle("</tmp/make.log");
    if (defined($fh)) {
      print STDOUT <$fh>;
      $fh->close();
    }
    print STDERR "\nERROR: make @_ failed.\n";
  }
  return ($r == 0);
}
  
sub cvs_cmd
{
  unlink("/tmp/cvs.log");
  system ("cvs @_ &> /tmp/cvs.log");
  my $r = $?;
  if ($r) {
    my $fh = new FileHandle("</tmp/cvs.log");
    if (defined($fh)) {
      print STDOUT <$fh>;
      $fh->close();
    }
    print STDERR "ERROR: cvs @_ failed.\n";
  }
  return ($r == 0);
}

sub check_out_modules
{
    my ($packlist_file, $module_list, $quiet) = @_;
    if (!-e $packlist_file) { return 0; }

    my $fh = new FileHandle("< $packlist_file") || die;
    while (defined($_ = <$fh>)) 
    {
        if (m/^module: ([^:]+)(?::([^:]+)(?::(\S*))?)?/i) 
        {
            my $module = $1;
            my $tag = $2;
            my $buildSections = $3 || "makefile";
            chomp($module, $tag, $buildSections);
      
            # don't bother updating modules already in module_list
            next if $$module_list{$module};

            if (-d $module) 
            {
	            system("cvs update -PAd " . ($tag && $tag ne "HEAD" ? "-r $tag" : '') . " $module &> $module.co");
            } 
            else 
            {
    	        system("cvs co -PA " . ($tag && $tag ne "HEAD" ? "-r $tag" : '') . " $module &> $module.co");
            }
            if ($?) 
            { 
                print STDERR "FAILED: cvs co ".($tag && $tag ne "HEAD" ? "-r $tag" : '')." $module\n"; 
		return 0;
            }
            else 
            { 
	      check_out_modules("${module}/packing_list", $module_list, $quiet) 
		|| check_out_modules("${module}/templates/packing_list.tmpl", $module_list, $quiet);
	      $quiet ||  print STDERR "ok: $module\n"; 
	      $$module_list{$module} = $buildSections; 
            }
        }
    }
  
    return 1;
}

sub get_module_tag {
  my ($module) = @_;

  open(ENTRIES, "$module/CVS/Entries") || return 0;
  while (<ENTRIES>) {
    if (/\/\/T(.*)/) {
      return (1, $1);
    }
  }
  close(ENTRIES);
  return (1, "HEAD");
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
