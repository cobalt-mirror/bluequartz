/* $Id: sessionmgr.h 3 2003-07-17 15:19:15Z will $
 *
 * sessionmgr.h -- session manager functions for keeping track
 * of active sessions, creating new sessions, expiring expired
 * sessions, and deleting sessions when a logout occurs.
 *
 * jmayer,thockin (c) 2000 Cobalt Networks.
 */

#ifndef __SESSIONMGR_H__
#define __SESSIONMGR_H__

#define SESSION_TIMEOUT		(60 * 60)
extern int session_timeout;

typedef struct cce_session_struct cce_session;

/* constructor - make an object and a session */
cce_session *cce_session_new(char *username); 

/* destructor - destroy object, not session */
void cce_session_destroy(cce_session *s);

/* resume an existing sesison, make a new object */
cce_session *cce_session_resume(char *username, char *session_id);

/* close session, not object */
int cce_session_expire(cce_session *s); 

/* restart timestamp on session */
void cce_session_refresh(cce_session *s);

/* query object fields */
char *cce_session_getid(cce_session *s);
char *cce_session_getuser(cce_session *s);

/* cleanup old sessions */
void cce_session_cleanup(void);

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
