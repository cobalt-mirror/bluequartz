#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/stat.h>
#include <sys/ioctl.h>
#include <unistd.h>
#include <getopt.h>

#include "lcd.h"
#include "lcdutils.h"

/* FIXME - timeout needed */
#define OPTIONS "sh1:i:"

int main(int argc, char **argv) {
  void *lcd;
  int  j, i, c, silent = 0;
  char *line, numstr[17], num[17];

  extern char *optarg;
  extern int opterr;

  opterr = 0; /* make getopt quiet */ 

  lcd_setlocale();

  strcpy(num, "                ");
  strcpy(numstr, _("ENTER PASSWORD:"));

  /* we silently ignore bad options. so, only -h returns usage. */
  while ((c = getopt(argc, argv, OPTIONS)) != EOF) { 
    switch (c) {
    case 's':
      silent++;
      break;

    case '1':
	strncpy(numstr, optarg, sizeof(numstr) - 1);
      break;

    case 'i':
	strncpy(num, optarg, sizeof(num) - 1);
      break;

    case 'h':
      printf(_("usage-getpass"),argv[0]);
      exit(0);
      break;

    default:
      break;
    }
  }

  if (!silent && (lcd_lock() < 0)) {
    printf(_("LCD in use... try again later\n"));
    exit(0);
  }

  if ((lcd = lcd_open(O_RDWR)) == NULL) {
    printf(_("LCD is not present\n"));
    exit(1);
  }

  lcd_reset(lcd);
  lcd_set(lcd, LCD_Blink_Off);
  lcd_wait_no_button(lcd);
  lcd_write(lcd, numstr, num);
  lcd_setcursorpos(lcd, 0x40);  //to the left
  lcd_wait_no_button(lcd);
  lcd_passbutton(lcd);

  lcd_getdisplay(lcd, NULL, &line, NULL);
  strncpy( num, line, 15 );
  lcd_close(lcd);
  if (!silent)
	  lcd_unlock();
  
  /* find the end of the string  */  
  /* (end being defined as the first non-
      space character encountered while
      looping from the end of the array to
      the beginning.) */
  for(i=15; i>=0 && num[i] == ' '; i--);
  printf("%d\n", i);
  /* print out the string  */
  if(i != 0)
     for(j=0; j <= i; j++)
	printf("%c", num[j]);

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
