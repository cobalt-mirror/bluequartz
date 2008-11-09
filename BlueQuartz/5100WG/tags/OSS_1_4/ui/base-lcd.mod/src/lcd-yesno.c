#include <stdio.h>
#include <stdlib.h>
#include <sys/time.h>

#include "lcd.h"
#include "lcdutils.h"


void timeout_handler(int sig)
{
  exit(RET_TIMEOUT);
}


int main(int argc, char *argv[]) {
  void *lcd;
  int silent;
  char line1[40], line2[40];
  int button, address;

  int		 ctr=0;
  int            i, j;
  int            yes_adr = 0;
  int            no_adr = 0;
  int            spots[10];
  int            args = argc;
  char           **vp = &argv[0];
  
  lcd_setlocale();

  silent = (((argc > 1) && strcmp(argv[1], "-s") == 0)) ? 1 : 0; 

  if (!silent && (lcd_lock() < 0)) {
    /* some else is using the lcd, so quit */
    printf(_("LCD in use... try again later\n"));
    exit(-1);
  }

  system("/sbin/lcdstop");

  strcpy(line1, _("Is this Correct?"));
  strcpy(line2, _("yes_no"));

  if ((lcd = lcd_open(O_RDWR)) == NULL) {
    printf(_("LCD is not present\n"));
    exit(1);
  }
    
  while (--args > 0) {
    vp++;
    if ((**vp) != '-') break;
    switch (*((*vp)+1)) {
    case 'y':
      vp++; args--;
      sscanf(*vp,"%x",&yes_adr);
      break;
    case 'n':
      vp++; args--;
      sscanf(*vp,"%x",&no_adr);
      break;
    case '1':
      vp++; args--;
      strcpy(line1,*vp);
      break;
    case '2':
      vp++; args--;
      strcpy(line2,*vp);
      break;
    case 's':
      break;
    default:
      printf(_("Unknown switch %s\n"),*vp);
    case 'h':
      printf(_("usage-yesno"),argv[0]);
      exit(0);
    }
  }
    
  memset(spots, 0, sizeof(spots));

  j = 0;
  for (i=0; i<14; i++) {
    if (line1[i] == '[' && line1[i+2] == ']') {
      spots[j++] = i + kDD_R00 + 1;
    }
  }
  for (i=0; i<14; i++) {
    if (line2[i] == '[' && line2[i+2] == ']') {
      spots[j++] = i + kDD_R10 + 1;
    }
  }
  if (yes_adr == 0) {
    if (spots[0] != 0) {
      yes_adr = spots[0];
    } else {
      yes_adr = CURSOR_YES;
    }
  }
  if (no_adr == 0) {
    if (spots[1] != 0) {
      no_adr = spots[1];
    } else {
      no_adr = CURSOR_NO;
    }
  }
  
  lcd_reset(lcd);
  ctr=0;
    
  lcd_write(lcd, line1, line2);
  lcd_set(lcd, LCD_Cursor_On);
  lcd_setcursorpos(lcd, no_adr);
    
  while( 1 ) {
    lcd_timeout_start(timeout_handler, MAX_IDLE_TIME);
    button = lcd_readbutton(lcd) & 0x00FF;
    if ( (button != BUTTON_NONE) && 
	 (button != BUTTON_NONE_B) ) {
      ctr++;

      if ( (ctr==BUTTON_DEBOUNCE) ) {
	lcd_timeout_stop();

	switch (button) {
	case BUTTON_Next:
	case BUTTON_Next_B:
	case BUTTON_Up:
	case BUTTON_Up_B:
	case BUTTON_Down:
	case BUTTON_Down_B:
	case BUTTON_Left:
	case BUTTON_Left_B:
	case BUTTON_Right:
	case BUTTON_Right_B:
	  address = lcd_getcursorpos(lcd);
          lcd_setcursorpos(lcd, address == no_adr ? yes_adr : no_adr);
	  break;
	    
	case BUTTON_Enter:
	case BUTTON_Enter_B:
	  address = lcd_getcursorpos(lcd);
	  lcd_close(lcd);
	  if (!silent)
		  lcd_unlock();
	  return (address == yes_adr) ? RET_YES : RET_NO;
	  break;
	}
      }
      if (ctr==BUTTON_SENSE) {
	ctr=0;
      }
    } else {
      ctr=0;
    }
  }
  
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
