/*
 * $Id: init.c 3 2003-07-17 15:19:15Z will $
 * Some init routines for libdebug
 */

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <ctype.h>
#include <glib.h>

#undef DEBUG_STR
#define DEBUG_STR			"LIBDEBUG"
#include <libdebug.h>


/* file info used globally for outputting debugging info */
FILE *debug_fp = NULL;

void
libdebug_init(int fd)
{
	libdebug_set_fd(fd);
	DPRINTF("libdebug initialized\n");
}

void
libdebug_set_fd(int fd)
{
	/* set up global file stuff */
	debug_fp = fdopen(fd, "rw");
}

void
libdebug_open(char *filename)
{
	FILE *tmp;
	tmp = fopen(filename, "w");
	if (tmp) {
  	debug_fp = tmp;
		DPRINTF("libdebug: now writing to %s\n", filename);
	} else {
		DPRINTF("libdebug: could not open %s\n", filename);
	}
}

void
libdebug_close(void)
{
	if (debug_fp) {
  	fclose(debug_fp);
    debug_fp = NULL;
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
