/*
 * SUN PROPRIETARY/CONFIDENTIAL:  INTERNAL USE ONLY.
 *
 * Copyright 2001 Sun Microsystems, Inc. All rights reserved.
 * Use is subject to license terms.
 */

#include <stdio.h>
#include <stddef.h>
#include <stdlib.h>
#include <stdarg.h>
#include <string.h>
#include <getopt.h>
#include <errno.h>
#include <unistd.h>
#include <syslog.h>

#include <sys/types.h>
#include <sys/stat.h>
#include <sys/file.h>
#include <sys/time.h>
#include <sys/resource.h>
#include <signal.h>
#include <sys/wait.h>
#include <time.h>
#include <fcntl.h>

#include "Exception.h"
#include "utility.h"
#include "processutil.h"

/*
 * calculate the file name without the path
 */
const char *BaseName (const char *p)
{
     char c;
     const char *base = p;
     while((c = *p++) != '\0')
	  if(c == '/')
	       base = p;
     return base;
}

static void LimitCore ()
{
     struct rlimit lim;
     if(getrlimit(RLIMIT_CORE, &lim) != -1){
	  lim.rlim_cur = 0;
	  setrlimit(RLIMIT_CORE, &lim);
     }
}

/*
 * Create a pid-file. This file is kept locked to prevent double
 * invocations of this program. Use different pidfiles for different daemons
 */
int CreatePidfile (const char *path)
{
     int fd = open(path, O_RDWR | O_CREAT, S_IRUSR | S_IWUSR);
     int len;
     int pid_handle;
     char tmp[64];
     if(fd == -1){
	  throw IOException (formatString("can't create/open %s: %s", 
					  path, strerror(errno)));
     };
     if(flock(fd, LOCK_EX | LOCK_NB) == -1){
	  if(errno == EWOULDBLOCK){
	       long opid;
	       len = read(fd, tmp, sizeof(tmp)-1);
	       if(len < 0){
		    int e = errno;
		    close(fd);
		    throw IOException(formatString("error reading file %s: %s",
						   path, strerror(e)));
	       };
	       if(sscanf(tmp, "%ld", &opid) != 1){
		    close(fd);
		    throw Exception(formatString("corrupt pidfile: %s", path));
	       };
	       close(fd);
	       throw Exception(
		    formatString("another process is already active: "
				 "pid = %ld", opid));
	  };
	  int e = errno;
	  close(fd);
	  throw IOException(formatString("could not lock file %s: %s", 
				       path, strerror(e)));
     };
     len = (size_t)sprintf(tmp, "%ld\n", (long)getpid());
     if(write(fd, tmp, len) == -1){
	  int e = errno;
	  close(fd);
	  throw IOException(formatString("Could not write to file %s: %s", 
					 path, strerror(e)));
     };
     if(ftruncate(fd, len) == -1){
	  int e = errno;
	  close(fd);
	  throw IOException(formatString("could not truncate file %s: %s", 
					 path, strerror(e)));
     };
     if(fd < 3){
	  pid_handle = dup2(fd, 3);
	  close(fd);
     }else{
	  pid_handle = fd;
     };
     return pid_handle;
}

/*
 * Run this program as a daemon
 */
void Daemonize (int save_handle)
{
     /* if the process is managed by pafmgr don't do anything */
     if(getenv("PAFMGR") != NULL)
	  return;
     pid_t pid = fork();
     int i, fd, dt = getdtablesize();
     if(pid == -1)
	  throw Exception(formatString("fork(): %s", strerror(errno)));
     if(pid != 0)
	  exit(0);
     setsid();
     signal(SIGHUP, SIG_IGN);
     umask(0);
     LimitCore();
     if(dt > 64 || dt < 0)
	  dt = 64;
     for(i = 0; i < dt; i++)
	  if(i != save_handle)
	       close(i);
     //StartSyslog();
     fd = open("/dev/null", O_RDWR);
     if(fd == -1)
	  throw IOException(formatString("can't open /dev/null: %s", strerror(errno)));
     for(i = 0; i <= 2; i++)
	  if(fd != i)
	       dup2(fd, i);
     if(fd > 2)
	  close(fd);
}
