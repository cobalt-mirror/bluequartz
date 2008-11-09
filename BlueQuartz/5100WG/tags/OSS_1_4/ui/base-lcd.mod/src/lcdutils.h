#ifndef LCDUTILS_H
#define LCDUTILS_H 1

#include <sys/types.h>
#include <fcntl.h>

#include <libintl.h>

/* locking bits */
extern int lcd_lock(void);
extern void lcd_unlock(void);

/* locale setting */
extern void lcd_setlocale(void);

/* timeout facilities */
extern int lcd_timeout_start(void (*)(const int), const int);
extern void lcd_timeout_stop(void);
extern int lcd_timeout_reset(void);

/* display and writing utils */
extern void *lcd_open(const int);
extern int lcd_write(void *, const char *, const char *);
extern void lcd_wait_no_button(void *);
extern void lcd_blink_invalid(void *);
extern void lcd_spin(void *, const char *, const char *);
extern void lcd_close(void *);
extern int lcd_setleds(void *, const int);
extern void lcd_reset(void *);
extern void lcd_set(void *, const int);

/* cursor utilities */
extern void lcd_setcursorpos(void *, unsigned char);
extern void lcd_setcursor(void *, unsigned char);
extern int lcd_getcursorpos(void *);
extern int lcd_getcursor(void *);
extern int lcd_movecursor(void *, const int);

/* link utilities */
extern int lcd_checklink(void *);
extern int lcd_checklink2(void *, int *);

/* reading utilities */
extern int lcd_getdisplay(void *, char **, char **, int *);
extern int lcd_readbutton(void *);
extern int lcd_getbutton(void *);


/* net utilities */
extern void lcd_netcorrect(void *);
extern int lcd_netbutton(void *);
extern void lcd_rev_format(char *);

/* password utilities */
extern int lcd_passbutton(void *);

#ifndef _
#define _(mesg)  gettext((mesg))
#endif

#endif
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
