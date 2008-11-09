/*

$Header: /home/cvs/base-scandetection.mod/src/commonlib/spawn.c,v 1.1 2001/09/18 16:59:37 jthrowe Exp $

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/spawn.c,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  Sat Aug 22 13:22:28 EDT 1998
    Originating Author :  Ge' Weijers, MJ Hullhorst

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:37 $

   **********************************************************************

   Copyright (c) 1997-1998 Progressive Systems Inc.
   All rights reserved.

   This code is confidential property of Progressive Systems Inc.  The
   algorithms, methods and software used herein may not be duplicated or
   disclosed to any party without the express written consent from
   Progressive Systems Inc.

   Progressive Systems Inc. makes no representations concerning either
   the merchantability of this software or the suitability of this
   software for any particular purpose.

   These notices must be retained in any copies of any part of this
   documentation and/or software.

   **********************************************************************

*/

#include <stdio.h>
#include <stddef.h>
#include <stdlib.h>
#include <signal.h>
#include <errno.h>
#include <string.h>


#include <unistd.h>
#include <sys/types.h>
#include <sys/wait.h>

#if 0
#include "pafserver.h"
#endif

/*
 * Run a subprocess and return the output in 'result'
 */
int spawnprocess (const char *command, char *argv[],
		  char *result, int maxreslen)
{
  /* reset SIGCHLD to default behavior */
  void (*old_sigcld)(int) = signal(SIGCHLD, SIG_DFL); 

  pid_t pid, wpid;
  int pfd[2];
  int status, reslen = 0;
  ssize_t n;
  char buff[200];

  //LOG( LOG_BASE, "Exec'ing command %s", command ) ;

  /* create a pipe */
  if(pipe(pfd) < 0){
    sprintf(result, "can't create pipe: %s\n", strerror(errno));
    signal(SIGCHLD, old_sigcld);
    return -1;
  };

  /* spawn a child process */
  pid = fork();
  if(pid == -1){
    sprintf(result, "can't fork: %s\n", strerror(errno));
    signal(SIGCHLD, old_sigcld);
    return -1;
  };

  if(pid == 0){
    /* 
     * The child: set standard output and standard error to the pipe 
     * write end
     */
    close(pfd[0]);
    if(pfd[1] != 1)
      dup2(pfd[1], 1);
    if(pfd[1] != 2)
      dup2(pfd[1], 2);

    //LOG( LOG_BASE, "Exec'ing %s", command ) ;

    /* run the command */
    //execv(command, argv);
    // explicit executable filenamem for security paranoia
    execvp(command, argv);
    fprintf(stderr, "ERROR: can't exec %s: %s\n", command, strerror(errno));
    exit(1);
  };

  /* the parent */
  close(pfd[1]);		/* close the write end */

  /* read the pipe until the buffer is full */
  maxreslen--;
  while(reslen < maxreslen
	&& (n = read(pfd[0], result+reslen, maxreslen-reslen)) > 0)
    reslen += n;
  result[reslen] = '\0';

  /* read the rest to prevent SIGPIPE */
  while(read(pfd[0], buff, sizeof(buff)) > 0);

  /* wait for the child to expire */
  while((wpid = waitpid(pid, &status, 0)) == -1 && errno == EINTR);
  if(wpid == -1){
    sprintf(result, "strange waitpid result: %d %s", wpid, strerror(errno));
    signal(SIGCHLD, old_sigcld);
    return -1;
  };

  /* close the pipe */
  close(pfd[0]);
  
  /* return the (positive) exit status */
  if(WIFEXITED(status)){
    signal(SIGCHLD, old_sigcld);
    return WEXITSTATUS(status);
  };

  /* signal death: oops! */
  if(WIFSIGNALED(status)){
    sprintf(result, "child died because of signal %d", (int) WTERMSIG(status));
    signal(SIGCHLD, old_sigcld);
    return -1;
  };

  /* can't happen? */
  signal(SIGCHLD, old_sigcld);
  return -1;
}
/*
int main (int argc, char *argv[])
{
  char bb[1000];
  if(argc > 1){
    int r = spawnprocess(argv[1], argv+1, bb, sizeof(bb));
    printf("%s\nExit=%d\n", bb, r);
  };
  return 0;
}
*/
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
