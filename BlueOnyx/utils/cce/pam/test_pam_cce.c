/* $Id: test_pam_cce.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * CSCP Authentication
 */

#include <cce_common.h>
#include <stdio.h>
#include <security/pam_appl.h>
#include <security/pam_misc.h>

static int test_conv(int nmsg, const struct pam_message **msg, 
	struct pam_response **resp, void *appdata);

static int
test_conv(int nmsg, const struct pam_message **msg, 
	struct pam_response **resp, void *appdata)
{
	struct pam_response *reply;
	int count;

	reply = (struct pam_response *)calloc(nmsg, sizeof(struct pam_response));
	if (!reply) {
		return PAM_CONV_ERR;
	}

	for (count = 0; count < nmsg; count++) {
		switch (msg[count]->msg_style) {
			case PAM_PROMPT_ECHO_OFF:
			case PAM_PROMPT_ECHO_ON:
				reply[count].resp_retcode = 0;
				reply[count].resp = strdup((char *)appdata);
				break;
			case PAM_ERROR_MSG:
			case PAM_TEXT_INFO:
				break;
		}
	}
	
	*resp = reply;

	return PAM_SUCCESS;
}


int
test_auth(char *name, char *passwd)
{
	pam_handle_t *pamh = NULL;
	int retval;
	struct pam_conv conv = {
		test_conv,
		NULL,
	};

	conv.appdata_ptr = passwd;

	retval = pam_start("test", name, &conv, &pamh);

	if (retval == PAM_SUCCESS) {
		retval = pam_authenticate(pamh, 0);
	}

	if (retval == PAM_SUCCESS) {
		retval = pam_acct_mgmt(pamh, 0);
	}

	pam_end(pamh, retval);
	
	if (retval == PAM_SUCCESS) {
		return 0;
	} else {
		return -1;
	}

	return 0;
}

int
main(int argc, char *argv[])
{
	char *user;
	char *pass;

	if (argc != 3) {
		fprintf(stderr, "usage: %s user pass\n", argv[0]);
		exit(1);
	}

	user = argv[1];
	pass = argv[2];

	printf("CCE authentication %s\n", !test_auth(user, pass) ?
		"succeeded" : "failed");

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
