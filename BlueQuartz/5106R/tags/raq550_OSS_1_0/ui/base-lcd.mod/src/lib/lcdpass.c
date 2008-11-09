/* read functions for the lcd. we handle both buttons and 
 * reading from the panel. */
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include <sys/time.h>
#include <sys/types.h>
#include <unistd.h>

#include "lcdutils.h"
#include "lcd_private.h"

int lcd_passbutton(void *lcd) {
  int   button, i, ctr=0;
   
  lcd_set(lcd, LCD_Cursor_On);
  while( 1 ) {
    button = lcd_readbutton(lcd) & 0x00FF;
    if ( (button != BUTTON_NONE) && (button != BUTTON_NONE_B) ) {
      ctr++;
      if ( (ctr==BUTTON_DEBOUNCE) ) {
	switch (button) {
	case BUTTON_Left:
	case BUTTON_Left_B:
	  i = lcd_getcursorpos(lcd);
	  if (i != 0x40) {
	    lcd_set(lcd, LCD_Cursor_Left);
	    i = lcd_getcursor(lcd);
	  }
	  break;
	  
	case BUTTON_Right:
	case BUTTON_Right_B:
	  i = lcd_getcursorpos(lcd);
	  if (i != 0x4E) {
	    lcd_set(lcd, LCD_Cursor_Right);
	    i = lcd_getcursor(lcd);
	  }
	  break;
	  
	case BUTTON_Up:
	case BUTTON_Up_B:
	  i = lcd_getcursor(lcd);
	  if (i == '9')
	   i = 'a';
	  else if (i == 'z')
	   i = ' ';
	  else if (i == ' ')
	   i = '0';
	  else
	   i++;
	  lcd_setcursor(lcd, i);
	  break;
	  
	case BUTTON_Down:
	case BUTTON_Down_B:
	  i = lcd_getcursor(lcd);
	  if (i == '0')
	    i = ' ';
	  else if (i == ' ')
	    i = 'z';
	  else if (i == 'a')
	    i = '9';
	  else
	    i--;
	  lcd_setcursor(lcd, i);
	  break;

	case BUTTON_Next:
	case BUTTON_Next_B:
	  break;
	  
	case BUTTON_Enter:
	case BUTTON_Enter_B:
	  lcd_getdisplay(lcd, NULL, NULL, &i);
	  return i;
	  break;
	  
	}
      }
      if (ctr==BUTTON_SENSE) {
	ctr=0;
      } 
      
    }
    else {
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
