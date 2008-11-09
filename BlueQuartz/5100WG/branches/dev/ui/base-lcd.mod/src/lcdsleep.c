#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/param.h>
#include <netdb.h>
#include <signal.h>

#include "lcdutils.h"
#include "lcd.h"

#define TIMEOUTSECS 30

volatile int power_button;

void timeout_handler(int sig) 
{
  system("/sbin/lcdstop");
  system("/etc/rc.d/init.d/lcd-showip");
}

void sigusr1_handler( int signal )
{
    if( signal == SIGUSR1 )
	power_button = 1;
}

int main(int argc, char **argv) 
{

  char *storeline1 = NULL, *storeline2 = NULL;
  char *line1, *line2;
  void *lcd;
  struct sigaction sa;
  int 	sleeper=0;
  int 	dum=52;


  power_button = 0;

  lcd_setlocale();
  system("/sbin/lcdstop");
  
  if ((lcd = lcd_open(O_RDWR)) == NULL) {
    printf(_("LCD is not present\n"));
    exit(1);
  }

  memset( &sa, 0x0, sizeof( sa ) );
  sa.sa_handler = sigusr1_handler;
  
  sigaction( SIGUSR1, &sa, NULL );

  while (1) {

      if( power_button )
      {
	  system("/etc/lcd.d/power_off");
    	  system("/etc/rc.d/init.d/lcd-showip");
	  power_button = 0;
      }

    lcd_wait_no_button(lcd);
    lcd_timeout_start(timeout_handler, TIMEOUTSECS);
    if ((dum = lcd_getbutton(lcd)) < 0) 
      continue; /* try again */

    printf(_("LCD Awake\n"));
    lcd_timeout_stop();
    system("/sbin/lcdstop");
    lcd_lock();
      
    sleeper = 0;
      
    lcd_getdisplay(lcd, &line1, &line2, NULL); /* read in the display */

    /* free if necessary */
    if (storeline1)
      free(storeline1);
    if (storeline2)
      free(storeline2);

    storeline1 = line1 ? strdup(line1) : NULL;
    storeline2 = line2 ? strdup(line2) : NULL;

    /*  Press the Reset Password button */
    if (dum == 0xFC) {
      system("/etc/lcd.d/reset_password");
      sleeper = 1;
    }
    
    while (!sleeper) {
      lcd_reset(lcd);
      sleeper = system("/sbin/lcd-menu /etc/lcd.d");
      lcd_write(lcd, storeline1, storeline2);
      printf(_("LCD Sleeping\n"));
    }
    lcd_unlock();
    system("/etc/rc.d/init.d/lcd-showip");
  }

  lcd_close(lcd);
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
