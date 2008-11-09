/* $Id: test.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include "c6.h"
#include "cce.h"
#include <stdlib.h>
#include <cce_common.h>
#include <cce_scalar.h>
#include <glib.h>

#define PRINT_INT(a,b) fprintf(fp, a " returns %d\n", b)
#define PRINT_OID(a,b) fprintf(fp, a " returns %lu\n", (ulong) b)
#define PRINT_STR(a,b) fprintf(fp, a " returns %s\n", b)

void print_list_errors(FILE *, GSList *);
void print_list_ints(FILE *, GSList *);
void print_list_strings(FILE *, GSList *);
void print_list_oids(FILE *, GSList *);
void print_props(FILE *, cce_props_t *);

int
main(int argc, char *argv[]) {
	cce_props_t *props;
	cce_handle_t *handle;
	char *id;
	
	FILE *fp;

	fp = fopen( "./test/test.1.out", "w" );
	if( ! fp ) {
		fprintf(stderr,"Could not open test output file\n");
	}
	
	handle = cce_handle_new();

	PRINT_INT("Connect ", cce_connect_cmnd(handle,"/usr/sausalito/cced.socket"));
	/* First the auth cmnd */
	PRINT_STR("Auth foo bar",cce_auth_cmnd(handle, "foo", "bar"));
	id = cce_auth_cmnd(handle, "bar", "foo");
	PRINT_INT("Authkey bar foo",cce_authkey_cmnd(handle, "bar", id));
/*	PRINT_INT("Commit", cce_commit_cmnd(handle));

	print_list_errors(fp, cce_last_errors_cmnd(handle));
	
	props = cce_props_new();
	cce_props_set(props, "foo","bar");
	cce_props_set(props, "bar","foo");
	PRINT_OID("Create one", cce_create_cmnd(handle, "class", props ));
	cce_props_destroy(props);

	print_list_errors(fp, cce_last_errors_cmnd(handle));

	props = cce_props_new();
	cce_props_set(props, "phone", "555");
	PRINT_OID("Set one", cce_set_cmnd(handle, 12, NULL, props ));
	PRINT_OID("Set two",cce_set_cmnd(handle,5,"Modem",props));
	cce_props_destroy(props);

	print_list_errors(fp, cce_last_errors_cmnd(handle));
	
	fprintf(fp, "Props one\n");
	print_props( fp, cce_get_cmnd(handle, 12, NULL ) );
	print_list_errors(fp, cce_last_errors_cmnd(handle));

	fprintf(fp, "Props two\n");
	print_props( fp, cce_get_cmnd(handle, 4, "namespace") );
	print_list_errors(fp, cce_last_errors_cmnd(handle));

	fprintf(fp, "Names oid twelve\n");
	print_list_strings(fp, cce_names_oid_cmnd(handle, 12));
	fprintf(fp, "Names class \"class\"\n");
	print_list_strings(fp, cce_names_class_cmnd(handle, "class"));

	fprintf(fp, "Find one\n");
	props = cce_props_new();
	cce_props_set(props, "foo", "bar");
	print_list_oids(fp, cce_find_cmnd(handle, "class", props));
	cce_props_destroy(props);

	fprintf(fp, "Destroy one\n");
	cce_destroy_cmnd(handle, 12 );
	fprintf(fp, "Destroy  twenty two (Test of invalid line handling).\n");
	cce_destroy_cmnd(handle, 22 );

	cce_handle_destroy( handle ); */
	
	/* I know.. I know..
	 * It actually frees all memory in reality that glib hasn't got around
	 * to freeing yet. */

	g_blow_chunks();

	fclose(fp);
	
	return 0;
}

void
print_list_strings( FILE *fp, GSList *strings ) {
	while ( strings ) {
		fprintf(fp, "\tString: %s\n", (char *)strings->data);
		strings = g_slist_next(strings);
	}
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

void
print_props( FILE *fp, cce_props_t *props ) {
	char *prop_str;
	if( props ) {
		prop_str = cce_props_serialise( props );
		fprintf(fp, "\tProps: %s\n", prop_str);
		free(prop_str);
	} else {
		fprintf(fp, "\tProps: NULL\n");
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
