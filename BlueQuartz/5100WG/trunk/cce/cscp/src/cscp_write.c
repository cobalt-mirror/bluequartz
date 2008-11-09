#include <cce_debug.h>
#include <cscp_all.h>
#include <cscp_internal.h>
#include <cscp.h>
#include <cscp_fsm.h>
#include <cscp_msgs.h>
#include <glib.h>
#include <unistd.h>
#include <fcntl.h>
#include <stdio.h>

static int safe_write(int fd, const char *buf, int len);

int 
write_str(cscp_conn *cscp, const char *str)
{
	g_string_append(cscp->resp_buffer, str);

	return safe_write(cscp->client, str, strlen(str));
}
  
int
write_str_nl(cscp_conn *cscp, const char *str)
{
	int r;

	r = write_str(cscp, str); 
	if (r >= 0) {
		r += write_str(cscp, eol);	
	}
	if (r >= 0) {
		DPRINTF(DBG_CSCP, "%s\n", cscp->resp_buffer->str);
		g_string_assign(cscp->resp_buffer, "<< ");
	}

	return r;
}
  
int
write_err(cscp_conn *cscp, const char *str)
{
	int r;

	r = write_str(cscp, error_msg);
	if (r >= 0) {
	 	r += write_str_nl(cscp, str);
	}

	return r;
}

#define NTRIES		8192
#define PANIC_TRIES	100
static int
safe_write(int fd, const char *buf, int len)
{
	int r;
	int ttl = 0;
	int ntries = NTRIES;

	do {
		r = write(fd, buf+ttl, len-ttl);
		if (r < 0) {
			if (errno != EAGAIN && errno != EINTR) {
				/* a legit error */
				return r;
			}
			ntries--;
			/* we're really low on last chances */
			if (ntries < PANIC_TRIES) {
				/* try letting the target catch up */
				usleep(100000);
			}
		}
		if (r > 0) {
			/* as long as we make forward progress, reset ntries */
			ntries = NTRIES;
			ttl += r;
		}
	} while (ttl < len && ntries);

	if (!ntries) {
		/* crap - we timed out on this write */
		CCE_SYSLOG("safe_write timed out (%s)\n", strerror(errno));
		return r;
	}

	return ttl;
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
