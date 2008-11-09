/* display utilities for the panel utils 
 *
 * NOTE: as we want to make sure that all of the lcd utils know
 *       about each other, this is almost intentionally 
 *       single-threaded.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/time.h>
#include <sys/types.h>
#include <sys/ioctl.h>

#include "lcdutils.h"
#include "lcd_private.h"

#define INVALIDUSECS 300000 /* .3 seconds */
#define SPINUSECS    500000 /* .5 secs */

#ifndef MIN
#define MIN(a, b)  ((a) < (b) ? (a) : (b))
#endif


void lcd_set(void *lcd_private, const int value) 
{
  struct lcd_private *lcd = lcd_private;

  if (!lcd || lcd->fd < 0)
    return;

  ioctl(lcd->fd, value);
}


/* this depends upon the lcd driver not changing bits that
 * it doesn't use. */
void lcd_blink_invalid(void *lcd_private) {
  struct lcd_private *save = lcd_private;
  struct lcd_display lcd, lcdblank;
  struct timeval tv, savetv;

  if (!save || save->fd < 0)
    return;

  ioctl(save->fd, LCD_Read, &lcd);

  /* initialize the blinky bits */
  memcpy(&lcdblank, &lcd, sizeof(lcd));
  memset(lcdblank.line2, ' ', sizeof(lcdblank.line2));
  lcd.size1 = lcd.size2 = 16;
  lcdblank.size1 = lcdblank.size2 = 16;

  ioctl(save->fd, BUTTON_Read, &save->display);
  save->display.buttons = save->display.buttons & 0x00FF;

  memset(&savetv, 0, sizeof(savetv));
  savetv.tv_usec = INVALIDUSECS;
  while ((save->display.buttons == BUTTON_NONE) || 
	 (save->display.buttons == BUTTON_NONE_B))  {
    ioctl(save->fd, LCD_Write, &lcdblank); 
    memcpy(&tv, &savetv, sizeof(tv));
    select(1, NULL, NULL, NULL, &tv);

    ioctl(save->fd, LCD_Write, &lcd);
    memcpy(&tv, &savetv, sizeof(tv));
    select(1, NULL, NULL, NULL, &tv);

    ioctl(save->fd, BUTTON_Read, &save->display);
    save->display.buttons = save->display.buttons & 0x00FF;
  }

  ioctl(save->fd, LCD_Write, &lcd);
}


/* blinky asterisk */
void lcd_spin(void *lcd, const char *line1, const char *line2)
{
  char star[17];
  struct timeval tv, save;

  memset(star, ' ', sizeof(star));
  star[16] = '\0';
  if (line2) 
    memcpy(star, line2, MIN(strlen(line2), sizeof(star)));

  memset(&save, 0, sizeof(save));
  save.tv_usec = SPINUSECS;
  while (1) {
    star[15] = '*';
    lcd_write(lcd, line1, star);
    memcpy(&tv, &save, sizeof(tv));
    select(1, NULL, NULL, NULL, &tv);

    star[15] = ' ';
    lcd_write(lcd, line1, star);
    memcpy(&tv, &save, sizeof(tv));
    select(1, NULL, NULL, NULL, &tv);
  }
}


void *lcd_open(const int flags) 
{
  struct lcd_private *lcd;
  int lcdtype = 0;

  if ((lcd = calloc(1, sizeof(struct lcd_private))) == NULL)
    return NULL;

  if ((lcd->fd = open(DEVLCD, flags)) < 0) {
    free(lcd);
    return NULL;
  }

  if (ioctl(lcd->fd, BUTTON_Read, &lcd->display) < 0) {
    close(lcd->fd);
    free(lcd);
    return NULL;
  }
	
  lcd->button_debounce = PARALLEL_BUTTON_DEBOUNCE;
  lcd->button_sense = PARALLEL_BUTTON_SENSE;
  if ((ioctl(lcd->fd, LCD_Type, &lcdtype) == 0) &&
      (lcdtype == LCD_TYPE_I2C)) {
	  lcd->button_debounce = I2C_BUTTON_DEBOUNCE;
	  lcd->button_sense = I2C_BUTTON_SENSE;
  }
	
  return lcd;
}

void lcd_close(void *lcd_private)
{
  struct lcd_private *lcd = lcd_private;

  if (!lcd)
    return;

  if (lcd->fd < 0)
    close(lcd->fd);
  
  free(lcd);
}


void lcd_reset(void *lcd_private)
{
  struct lcd_private *lcd = lcd_private;

  if (!lcd || (lcd->fd < 0))
    return;

  ioctl(lcd->fd,LCD_Reset);
  ioctl(lcd->fd,LCD_Clear);
  ioctl(lcd->fd,LCD_On);	
  ioctl(lcd->fd,LCD_Cursor_Off);
}

int lcd_write(void *lcd_private, const char *line1, const char *line2)
{
  struct lcd_private *lcd = lcd_private;

  if (!lcd || (lcd->fd < 0))
    return -1;

  if (line1) {
    memset(lcd->display.line1, ' ', sizeof(lcd->display.line1));
    lcd->display.line1[sizeof(lcd->display.line1)-1] = '\0';
    lcd->display.size1 = MIN(strlen(line1), sizeof(lcd->display.line1)-1);
    memcpy(lcd->display.line1, line1, lcd->display.size1);
  }

  if (line2) {
    memset(lcd->display.line2, ' ', sizeof(lcd->display.line2));
    lcd->display.line2[sizeof(lcd->display.line2)-1] = '\0';
    lcd->display.size2 = MIN(strlen(line2), sizeof(lcd->display.line2)-1);
    memcpy(lcd->display.line2, line2, lcd->display.size2);
  }

  ioctl(lcd->fd, LCD_Write, &lcd->display);
  return 0;
}


int lcd_setleds(void *lcd_private, const int pattern) 
{
  struct lcd_private *lcd = lcd_private;

  if (ioctl(lcd->fd, LED32_Set, pattern) == 0)
	  return 0;

  if (!lcd || (lcd->fd < 0) || ((pattern & 0x0F) == 0x0F))
    return -1;

  lcd->display.leds = pattern;
  ioctl(lcd->fd,LED_Set,&lcd->display);
  return 0;
}

int lcd_led_set(void *lcd_private, const unsigned int pattern) 
{
  struct lcd_private *lcd = lcd_private;

  if (ioctl(lcd->fd, LED32_Bit_Set, pattern) == 0)
	  return 0;

  if (!lcd || (lcd->fd < 0) || ((pattern & 0x0F) == 0x0F))
    return -1;

  lcd->display.leds = pattern;
  ioctl(lcd->fd,LED_Set,&lcd->display);
  return 0;
}

int lcd_led_clear(void *lcd_private, const unsigned int pattern) 
{
  struct lcd_private *lcd = lcd_private;

  if (ioctl(lcd->fd, LED32_Bit_Clear, pattern) == 0)
	  return 0;

  if (!lcd || (lcd->fd < 0) || ((pattern & 0x0F) == 0x0F))
    return -1;

  lcd->display.leds = pattern;
  ioctl(lcd->fd,LED_Set,&lcd->display);
  return 0;
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
