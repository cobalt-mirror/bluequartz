/* $Id: connect.c 3 2003-07-17 15:19:15Z will $ 
 */
 
#include <impl.h>
#include <odb_types.h>
#include <odb_errors.h>
#include <odb_headers.h>

/*
 * initiate a connection to the DB backend
 */
odb_impl_handle *
impl_handle_new(void)
{
	unsigned long val = 0;
	odb_impl_handle *h;

	/* get some mem before we write the file */
	h = malloc(sizeof(odb_impl_handle));
	if (!h) {
		DPERROR(DBG_CODB, "impl_connect: malloc()");
		return NULL;
	}

	/* make directories in case they don't exist */
	{
		mode_t mode = 0700;
		mkdir(ODB_DIR, mode);
		mkdir(ODB_DIR "/objects", mode);
	}

	val = read_and_incr_counter(ODB_DIR "/connectnum");
	if (val < 0) {
		DPERROR(DBG_CODB, "odb_incr_counter()");
		free(h);
		return NULL;
	}

	h->connectnum = val;
	h->db_path = strdup(ODB_DIR);

	return h;
}		


/*
 * shutdown a DB connection
 */
void
impl_handle_destroy(odb_impl_handle *h)
{
	if (!h) {
		return;
	}

	if (h->db_path) {
		free(h->db_path);
	}
	
	free(h);
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
