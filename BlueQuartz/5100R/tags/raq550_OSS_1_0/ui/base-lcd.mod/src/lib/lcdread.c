/* read functions for the lcd. we handle both buttons and 
 * reading from the panel. */
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include <sys/time.h>
#include <sys/types.h>
#include <sys/ioctl.h>
#include <unistd.h>

#include "lcdutils.h"
#include "lcd_private.h"


int lcd_getdisplay(void *lcd_private, char **line1, char **line2,
		   int *buttons)
{
  struct lcd_private *lcd = lcd_private;

  if (!lcd || (lcd->fd < 0))
    return -1;

  ioctl(lcd->fd, LCD_Read, &lcd->display);
  if (line1) 
    *line1 = lcd->display.line1;
  if (line2)
    *line2 = lcd->display.line2;
  if (buttons)
    *buttons = lcd->display.buttons;
  return 0;
}

int lcd_readbutton(void *lcd_private)
{
  struct lcd_private *lcd = lcd_private;

  if (!lcd || (lcd->fd < 0)) 
    return -1;

  ioctl(lcd->fd, BUTTON_Read, &lcd->display);
  return lcd->display.buttons;
}


int lcd_getbutton(void *lcd_private)
{
  struct lcd_private *lcd = lcd_private;
  int i;

  if (!lcd || (lcd->fd < 0)) 
    return -2;

  return read(lcd->fd, &i, 1);
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
