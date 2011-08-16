/*
 * $Id: mh-wrapper.c 3 2003-07-17 15:19:15Z will $
 *
 * a suid wrapper for MHonArc
 *
 * does not: catch any signals or write to any log files.  
 *
 */

#define RCFILE "/usr/sausalito/handlers/base/maillist/mhonarc.rc"
#define MHONARC "/home/mhonarc/bin/mhonarc"
#define PATH "PATH=/home/mhonarc/bin:/bin:/usr/bin:/usr/ucb"
#define HOME "HOME=/home/mhonarc"
#define SHELL "SHELL=/bin/sh"

#include <sys/stat.h>
#include <sys/types.h>
#include <pwd.h>
#include <stdio.h>
#include <stdlib.h>
#include <ctype.h>
#include <unistd.h>
#include <fcntl.h>
#include <signal.h>
#include <syslog.h>
#include <string.h>

#ifndef DEBUG
#define DEBUG	0
#endif

void chmkdir(char *dir);
int run_command( char *listname, char *expire);

int
main (int argc, char *argv[], char *env[])
{
  int status;
  uid_t uid;
  gid_t gid;
  char *listname = NULL;
  char *expire = NULL;
  char *s;

  /* I'm me, really. */
  uid = geteuid(); 
  setreuid(uid, uid); 
  gid = getegid();
  setregid(gid, gid);

  if (argc < 2) {
	syslog(LOG_ERR,"mh-wrapper invoked with wrong # of args: %d", argc);
	exit(0);
  }
  listname = argv[1];
  expire = argv[2];
  

  syslog(LOG_ERR,"mh-wrapper invoked: %s", listname);
  
  // munge listname
  s = listname;
  while (*s) {
    if (*s == '/') { *s = '_'; }
    s++;
  }
  
  // go there
  if (chdir("/home/mhonarc") < 0) {
    syslog(LOG_ERR, "mh-wrapper: couldn't chdir to /home/mhonarc");
    exit(127);
  }
  
  chmkdir("data");

  // check listname.enabled file:  
  {
    char fname[80];
    struct stat buf;
    snprintf(fname, 79, "%s.enabled", listname);
    if (stat(fname, &buf) < 0) {
      // archive is not enabled.  don't worry, be happy.
      syslog(LOG_ERR, "mh-wrapper: not enabled: %s", fname);
      exit(0);
    }
  }
  
  chmkdir(listname);
  
  status = run_command(listname, expire);
 
  return(status);
}

void
chmkdir(char *dir) {
  struct passwd *user, *group;
  if (chdir(dir) < 0) {
	/* changed directory perms to allow 'other' to read archives */
    mkdir(dir, 0775);
    if (chdir(dir) < 0) {
      syslog(LOG_ERR, 
      	"mh-wrapper: dir does not exist and could not be created: %s",
	dir);
    }
    user = getpwnam("mail");
    group = getpwnam("daemon");
    if (! chown(dir, user->pw_uid, group->pw_gid) ) {
      syslog(LOG_ERR,
        "mh-wrapper: could not chown mail.daemon on %s",
        dir);
    }
  }
}

int
run_command( char *listname, char *expire)
{
  static char mlname[256];
  char *filename;
  char *argv[10];
  char *env[10];
  int i,j;
 
  filename = MHONARC;
  
  argv[i=0] = MHONARC;
  argv[++i] = "-add";
  argv[++i] = "-main";
  argv[++i] = "-rcfile";
  argv[++i] = RCFILE;
  if (expire && expire[0]) {
	  argv[++i] = "-expireage";
	  argv[++i] = expire;
  }
  argv[++i] = NULL;
  
  env[i=0] = HOME;
  env[++i] = PATH;
  env[++i] = SHELL;
  sprintf(mlname, "MLNAME=%240s", listname);
  env[++i] = mlname;
  env[++i] = NULL;
  //env[++i] = "MLNAME=LISTNAMEHERE";

  execve(filename, argv, env);
  syslog(LOG_ERR, "mh-wrapper failed to execute %s", filename);
  exit(127);
}
  
/* Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * -Redistribution of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 * 
 * -Redistribution in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution. 
 *
 * Neither the name of Sun Microsystems, Inc. or the names of contributors may
 * be used to endorse or promote products derived from this software without 
 * specific prior written permission.

 * This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
 * 
 * You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
 */
