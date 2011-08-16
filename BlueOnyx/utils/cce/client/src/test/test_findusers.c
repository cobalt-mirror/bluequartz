/* $Id: test_findusers.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include "c6.h"
#include "cce.h"
#include <stdlib.h>
#include <cce_common.h>
#include <cce_scalar.h>
#include <glib.h>

#define PRINT_INT(a,b) fprintf(stderr, a " returns %d\n", b)
#define PRINT_OID(a,b) fprintf(stderr, a " returns %lu\n", (ulong) b)

void print_list_errors(FILE *, GSList *);
void print_list_ints(FILE *, GSList *);
void print_list_strings(FILE *, GSList *);
void print_list_oids(FILE *, GSList *);
void print_props(FILE *, cce_props_t *);

int
main(int argc, char *argv[]) {
	cce_props_t *props;
	cce_handle_t *handle;
	GSList *oids;
	char *classname;
	
	handle = cce_handle_new();

	PRINT_INT("Connect ", 
		cce_connect_cmnd(handle,"/usr/sausalito/cced.socket"));

	/* get all users */
	classname = "User";
	props = cce_props_new();
	oids = cce_find_cmnd(handle, classname, props);
	cce_props_destroy(props);

	fprintf(stderr, "All users:\n");
	print_list_oids(stderr, oids);

	/* get users named fubar */
	classname = "User";
	props = cce_props_new();
	cce_props_set(props,"name","fubar");
	oids = cce_find_cmnd(handle, classname, props);
	cce_props_destroy(props);

	fprintf(stderr, "Users named fubar:\n");
	print_list_oids(stderr, oids);

	cce_handle_destroy( handle );
	
	g_blow_chunks();

	return 0;
}

void
print_list_oids( FILE *fp, GSList *oids ) {
	while ( oids ) {
		fprintf(fp, "\tOID: %u\n", GPOINTER_TO_INT(oids->data));
		oids = g_slist_next(oids);
	}
}

void
print_list_errors( FILE *fp, GSList *errors ) {
	char *error_str;
	if( ! errors ) {
		fprintf(fp, "\tNo errors\n");
	}
	while ( errors ) {
		error_str = cce_error_serialise(errors->data);
		fprintf(fp, "\tError: %s\n", error_str);
		free(error_str);
		errors = g_slist_next(errors);
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
