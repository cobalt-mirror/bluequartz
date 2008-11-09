#include <cce.h>
#include <stdlib.h>
#include <stdio.h>

int main ( int argc, char *argv[] ) {
	cce_handle_t *handle;
	cscp_oid_t oid;
	
	handle = cce_handle_new();

	oid = cce_connect_handler_cmnd(handle);

	printf("Functioning on oid %lu\n", oid);
	
	cce_bye_handler_cmnd( handle, CCE_SUCCESS, NULL );
	cce_bye_handler_cmnd( handle, CCE_DEFER, NULL );
	cce_bye_handler_cmnd( handle, CCE_FAIL, "I was written by Matin B." );

	cce_bad_data_cmnd( handle, 12, NULL, "foo", "Foo sucks ass. Yeah!");
	cce_bad_data_cmnd( handle, 12, "namespace", "foo", "Foo sucks ass. Yeah!");
	cce_bad_data_cmnd( handle, 12, "namespace", "foo", NULL);

	return 1;
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
