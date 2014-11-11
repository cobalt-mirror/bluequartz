/* $Id: cce_common.h 623 2005-10-30 15:53:49Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#ifndef __CCE_COMMON_H__
#define __CCE_COMMON_H__

#include <stdlib.h>
#include <stdio.h>
#include <errno.h>
#include <syslog.h>
#include <time.h>
#include <sys/time.h>
#include <sys/types.h>
#include <unistd.h>

#include <cce_paths.h>

/*****
 * flags associated with debug print/syslog/error printing.
 *****/

/* the global debug mask - 0 = no debugging */
extern unsigned long cce_debug_mask;

/* the 'verbose' flag.  send syslog's to stderr */
extern int vflag;

/* the 'no logging' flag.  never send anything to syslog */
extern int nologflag;

/* "fail everything" flag. */
extern int txnfailflag;

/* don't run transaction support library */
extern int txnstopflag;

/*****
 * some macros to manipulate them
 *****/
#define set_debug_mask(m)	do { cce_debug_mask = (m); } while (0)
#define add_debug_mask(m)	do { cce_debug_mask |= (m); } while (0)
#define rm_debug_mask(m)	do { cce_debug_mask &= ~(m); } while (0)


/*****
 * the functions/macros to output stuff
 *****/
/*
 * cce_syslog
 * 	if (!nl) syslog
 * 	if (-V) stderr
 *
 * DPRINTF
 * 	if (-d <DBG_X>) stderr
 */

/* 
 * use DPRINTF()/DPERROR() when there is data to print, but it is not
 * fatal.  We can then enable the debugging flag for each subsystem to get
 * more info on it.
 */
#define DPRINTF(m, ...) 	\
	do { \
		struct timeval ts; \
		struct tm *tm; \
		gettimeofday(&ts, NULL); \
		if (cce_debug_mask & (m)) { \
			fprintf(stderr, "%02d:%02d:%02d.%ld [%li] (%s:%d): ", \
				ts.tv_usec , \
				(long)getpid() , __FILE__ , __LINE__); \
			fprintf(stderr, __VA_ARGS__); \
		} \
	} while (0)

#define DPRINTF_CORE(m, ...) \
	do { \
		if (cce_debug_mask & (m)) { \
			fprintf(stderr, __VA_ARGS__); \
		} \
	} while (0)

#define DPERROR(m, ...) \
	do { \
		DPRINTF(m, __VA_ARGS__); \
		DPRINTF_CORE(m, ": %s\n" , strerror(errno)); \
	} while (0)

#define CCE_SYSLOG(...) \
	do { \
		if (vflag) { \
			fprintf(stderr, __VA_ARGS__); \
			fprintf(stderr, "\n"); \
		} \
		if (!nologflag) \
			syslog(LOG_INFO, __VA_ARGS__); \
	} while (0)

#define DPROFILE_START(m, tvp, ...) \
	do { \
		if (cce_debug_mask & m) { \
			gettimeofday((tvp), NULL); \
			DPRINTF(m, "PROFILING: "); \
			DPRINTF_CORE(m, __VA_ARGS__); \
			DPRINTF_CORE(m, "\n"); \
		} \
	} while (0)	

#define DPROFILE(m, tv, ...) \
	do { \
		if (cce_debug_mask & m) { \
			struct timeval tv2; \
			long unsigned duration; \
			gettimeofday(&tv2, NULL); \
			duration = (tv2.tv_sec - (tv).tv_sec) * (1000000) \
				+ (tv2.tv_usec - (tv).tv_usec); \
			DPRINTF(m, "-- TIME: %ld.%06ld: " , \
				(long)(duration / 1000000) , \
				(long)(duration % 1000000)); \
			DPRINTF_CORE(m, __VA_ARGS__); \
			DPRINTF_CORE(m, "\n"); \
		} \
	} while (0)

/* debugging values for cce_debug_mask, used throughout cce */
#define DBG_NONE    		0x0000
#define DBG_CCED    		0x0001
#define DBG_CODB    		0x0002
#define DBG_ED      		0x0004
#define DBG_CONF    		0x0008
#define DBG_SCHEMA  		0x0010
#define DBG_SESSION 		0x0020
#define DBG_CSCP    		0x0040
#define DBG_SCALAR   		0x0080
#define DBG_COMMON   		0x0100
#define DBG_CSCP_XTRA		0x0200
#define DBG_TXN      		0x0400
#define DBG_EXCESSIVE		0x8000

/* profiling values */
#define PROF_NONE    		0x00000000
#define PROF_CCED    		0x00010000
#define PROF_CODB    		0x00020000
#define PROF_ED      		0x00040000
#define PROF_CONF    		0x00080000
#define PROF_SCHEMA  		0x00100000
#define PROF_SESSION 		0x00200000
#define PROF_CSCP    		0x00400000
#define PROF_SCALAR   		0x00800000
#define PROF_COMMON   		0x01000000
#define PROF_CSCP_XTRA		0x02000000
#define PROF_TXN      		0x04000000

#endif /* __CCE_COMMON_H__ */

#ifdef USE_LIBDEBUG
#define USE_MEMDEBUG 2
#include <libdebug.h>
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
