/* implement time-related functions for panel utils. it's done this way
 * because select() doesn't always work with the lcd driver. bleah.
 *
 * NOTE: as we want to make sure that all of the lcd utils know
 *       about each other, this is almost intentionally 
 *       single-threaded.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <signal.h>

#include <sys/time.h>
#include <sys/types.h>

#include "lcdutils.h"

static struct itimerval timeout;

/* start up an alarm handler for timeouts. we have to do it
 * this way because we can't do a select/poll on /dev/lcd
 * and have it be meaningful. */
int lcd_timeout_start(void (*fcn)(int), const int seconds)
{
  struct sigaction act;
  
  memset(&act, 0, sizeof(act));
  act.sa_handler = fcn;
  act.sa_flags = SA_RESTART;
  if (sigaction(SIGALRM, &act, NULL) < 0)
    return -1;
  
  memset(&timeout, 0, sizeof(timeout));
  timeout.it_interval.tv_sec = seconds;
  timeout.it_value.tv_sec = seconds;
  if (setitimer(ITIMER_REAL, &timeout, NULL) < 0) {
    sigaction(SIGALRM, NULL, NULL);
    return -1;
  }

  return 0;
}


/* stop and reset everything */
void lcd_timeout_stop(void)
{
  struct itimerval zero;
  struct sigaction act;

  memset(&zero, 0, sizeof(zero));
  memset(&act, 0, sizeof(act));
  setitimer(ITIMER_REAL, &zero, NULL);
  act.sa_handler = SIG_DFL;
  sigaction(SIGALRM, &act, NULL);
}


/* just do a reset */
int lcd_timeout_reset(void)
{
  return setitimer(ITIMER_REAL, &timeout, NULL); 
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
