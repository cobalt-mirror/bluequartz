/* $Id: pam_cce.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Pam CCE moduel written by Michael Waychison June 30, 2000 
 */

#include <cce_common.h>
#include <glib.h>
#include <cce.h>

static const char rcsid[] =
" - CCE Pluggable Authentication module. <mwaychison@cobalt.com>"
;

/* indicate the following groups are defined */

#define PAM_SM_AUTH
#define PAM_SM_ACCOUNT
#define PAM_SM_PASSWORD
#define PAM_SM_SESSION

#include <security/_pam_macros.h>
#include <security/pam_modules.h>

#ifndef LINUX_PAM 
#include <security/pam_appl.h>
#endif  /* LINUX_PAM */

static int _check_auth(char *user, char *pass) ;
static void _pam_log(int err, const char *format, ...);
static int _pam_parse(int argc, const char **argv);
static int _read_password(pam_handle_t *pamh, unsigned int ctrl, 
	const char *prompt, const char **pass);
static int converse(pam_handle_t *pamh, int ctrl, int nargs, 
	struct pam_message **message, struct pam_response **response);
static char *_pam_delete(register char *xx);
 
/*
 * PAM framework looks for these entry-points to pass control to the
 * authentication module.
 */

/* authenticate a user */
PAM_EXTERN int 
pam_sm_authenticate(pam_handle_t *pamh, int flags, int argc, const char **argv)
{
	int ctrl;
	int retval = PAM_AUTH_ERR;
	char *username;
	char *pass;

	ctrl = _pam_parse(argc, argv);

	/* grab the username */
	retval = pam_get_user(pamh, (const char **)&username, "username: ");

	/* grab the pass from PAM */
	_read_password(pamh, flags, "sessid/passwd: ", (const char **)&pass);

	/* check with CCE if sessionId (or maybe password) is cool */
	if (retval == PAM_SUCCESS) {
		retval = _check_auth(username, pass);
	}

	_pam_log(LOG_DEBUG, "authetication %s", 
		retval==PAM_SUCCESS ? "succeeded":"failed" );

	return retval;
}

/* set credentials (unimplemented) */
PAM_EXTERN int 
pam_sm_setcred(pam_handle_t *pamh, int flags, int argc, const char **argv)
{
	return PAM_SUCCESS;
}

/* change a password (unimplemented, but should be) */
PAM_EXTERN int 
pam_sm_chauthtok(pam_handle_t *pamh, int flags, int argc, const char **argv)
{
	return PAM_SUCCESS;
}

/* 
 * check that an account is currently valid for auth, after the token has been
 * accepted (unimplemented)
 */
PAM_EXTERN int 
pam_sm_acct_mgmt(pam_handle_t *pamh, int flags, int argc, const char **argv) 
{
	return PAM_SUCCESS;
} 

/* deal with opening/closing a 'session' (unimplemented, but should be) */
PAM_EXTERN int 
pam_sm_open_session(pam_handle_t *pamh, int flags, int argc, const char **argv) 
{
	return PAM_SUCCESS;
}

PAM_EXTERN int 
pam_sm_close_session(pam_handle_t *pamh, int flags, int argc, const char **argv)
{
	return PAM_SUCCESS;
}

/*
 * below here are just support routines
 */

static int 
_check_auth(char *user, char *pass) 
{
	int retval = PAM_AUTH_ERR;
	cce_handle_t *handle;

	handle = cce_handle_new();
	if (!handle) {
		return retval;
	}

	/* assume authkey, fall back on auth if we have to */
	if (cce_connect_cmnd(handle, CCESOCKET)) {
		/* connected to CCE */
		if (cce_authkey_cmnd(handle, user, pass) 
		 || cce_auth_cmnd(handle, user, pass)) {
			retval = PAM_SUCCESS;
		} else {
			retval = PAM_AUTH_ERR;
		}
		cce_bye_cmnd(handle);
	} else {
		retval = PAM_AUTHINFO_UNAVAIL;
	}
	cce_handle_destroy(handle);

	return retval;
}

static void 
_pam_log(int err, const char *format, ...)
{
	va_list args;

	va_start(args, format);
	openlog("PAM-cce", LOG_CONS|LOG_PID, LOG_AUTH);
	vsyslog(err, format, args);
	va_end(args);
	closelog();
}

/* this is here if we want to add parameters to pam_cce later */
static int 
_pam_parse(int argc, const char **argv)
{
	int ctrl=0;

	/* step through arguments */
	for (ctrl=0; argc-- > 0; ++argv) {
		/* generic options */
		_pam_log(LOG_AUTHPRIV, "pam_parse: unknown option; %s", *argv);
	}

	return ctrl;
}

static int 
_read_password( pam_handle_t *pamh, unsigned int ctrl, const char *prompt, 
	const char **pass )
{
    int retval;
    const char *item;
    char *token;

    /*
     * make sure nothing inappropriate gets returned
     */

    *pass = token = NULL;

    /*
     * should we obtain the password from a PAM item ?
     */

    retval = pam_get_item(pamh, PAM_AUTHTOK, (const void **) &item);
    if (retval != PAM_SUCCESS ) {
        /* very strange. */
        _pam_log(LOG_ALERT , "pam_get_item returned error to read-password");
        return retval;
    } else if (item != NULL) {    /* we have a password! */
        *pass = item;
        item = NULL;
        return PAM_SUCCESS;
    }

    /*
     * getting here implies we will have to get the password from the
     * user directly.
     */

    {
        struct pam_message msg, *pmsg[1];
        struct pam_response *resp;

        /* prepare to converse */

        pmsg[0] = &msg;
        msg.msg_style = PAM_PROMPT_ECHO_OFF;
        msg.msg = prompt;

        /* so call the conversation expecting i responses */
        resp = NULL;
        retval = converse(pamh, ctrl, 1, pmsg, &resp);

        if (resp != NULL) {
            /* interpret the response */
            if (retval == PAM_SUCCESS) {     /* a good conversation */
                token = x_strdup(resp[0].resp);
                if (!token) {
                    _pam_log(LOG_NOTICE, 
			  	"could not recover authentication token");
                }
            }

            _pam_drop_reply(resp, 1);

        } else {
            retval = (retval == PAM_SUCCESS)
                ? PAM_AUTHTOK_RECOVER_ERR:retval ;
        }
    }

    if (retval != PAM_SUCCESS) {
        _pam_log(LOG_DEBUG,"unable to obtain a password");
        return retval;
    }

    /* we store this password as an item */
    retval = pam_set_item(pamh, PAM_AUTHTOK, token);
    token = _pam_delete(token);   /* clean it up */
    if (retval != PAM_SUCCESS
     || (retval = 
     pam_get_item(pamh, PAM_AUTHTOK, (const void **)&item)) != PAM_SUCCESS ) {
            _pam_log(LOG_CRIT, "error manipulating password");
            return retval;
    }

    *pass = item;

    return PAM_SUCCESS;
}

static int 
converse(pam_handle_t *pamh, int ctrl, int nargs, 
	struct pam_message **message, struct pam_response **response)
{
    int retval;
    struct pam_conv *conv;

    retval = pam_get_item(pamh, PAM_CONV, (const void **) &conv) ;
    if (retval == PAM_SUCCESS) {
        retval = conv->conv(nargs, (const struct pam_message **)message, 
	  	response, conv->appdata_ptr);
    } else if (retval != PAM_CONV_AGAIN) {
        _pam_log(LOG_ERR, "couldn't obtain coversation function [%s]", 
	  	pam_strerror(pamh, retval));
    }

    return retval;                  /* propagate error status */
}

static char *
_pam_delete(register char *xx)
{
    _pam_overwrite(xx);
    _pam_drop(xx);
    return NULL;
}

/* static module data */

#ifdef PAM_STATIC
struct pam_module _pam_cce_modstruct = {
     "pam_cce",
     pam_sm_authenticate,
     pam_sm_setcred,
     pam_sm_acct_mgmt,
     pam_sm_open_session,
     pam_sm_close_session,
     pam_sm_chauthtok
};

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
