#include <stdio.h>

#include "lcd.h"
#include "lcdutils.h"

/* linkstatus.c
 *	Returns the link status of eth0 and eth1
 *	0 - both links down
 *	1 - eth0 UP, eth1 DOWN
 *	2 - eth0 DOWN, eth1 UP
 *	3 - eth0 UP, eth1 UP
 *
 */

int main (int argc, char **argv) {
  void *lcd;
  int button, err, status;
  
  lcd_setlocale();

  if ((lcd = lcd_open(O_RDWR)) == NULL)
    exit (69);

  status = 0;
  button = 0;
  if (lcd_checklink2(lcd, &button) < 0)
    exit(70);

  if (button) 
    status++;

//  printf(_("eth0: Link state is %d\n"), button);
  button = 1;
  err = lcd_checklink2(lcd, &button);
  if (err < 0)
    exit(71);

  if (button)
    status += 2;
//  printf(_("eth1: Link state is %d\n"), button);
  printf("%d",status);

  lcd_close(lcd);
  return(status);	
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
