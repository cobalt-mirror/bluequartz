/* button routines */

#include <stdio.h>
#include <stdlib.h>
#include <sys/ioctl.h>

#include "lcdutils.h"
#include "lcd_private.h"

int lcd_button_debounce(void *lcd_private)
{
  struct lcd_private *lcd = lcd_private;
  return lcd->button_debounce;
}

int lcd_button_sense(void *lcd_private)
{
  struct lcd_private *lcd = lcd_private;
  return lcd->button_sense;
}

/* wait until the user stops pressing a button */
void lcd_wait_no_button(void *lcd_private) {
  struct lcd_private *lcd = lcd_private;

  if (!lcd || lcd->fd < 0)
    return;

  ioctl(lcd->fd, BUTTON_Read, &lcd->display);
  lcd->display.buttons &= 0x00FF;
  while((lcd->display.buttons != BUTTON_NONE) && 
	(lcd->display.buttons != BUTTON_NONE_B)) {
    ioctl(lcd->fd, BUTTON_Read, &lcd->display);
    lcd->display.buttons &= 0x00FF;
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
