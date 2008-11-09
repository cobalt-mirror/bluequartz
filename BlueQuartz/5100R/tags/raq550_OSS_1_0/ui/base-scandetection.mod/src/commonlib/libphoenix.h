/*

$Header: /home/cvs/base-scandetection.mod/src/commonlib/libphoenix.h,v 1.1 2001/09/18 16:59:37 jthrowe Exp $

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/libphoenix.h,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  Wed Feb 11 10:06:07 EST 1998
    Originating Author :  MJ Hullhorst

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:37 $

   **********************************************************************

   Copyright (c) 1997-1998 Progressive Systems Inc.
   All rights reserved.

   This code is confidential property of Progressive Systems Inc.  The
   algorithms, methods and software used herein may not be duplicated or
   disclosed to any party without the express written consent from
   Progressive Systems Inc.

   Progressive Systems Inc. makes no representations concerning either
   the merchantability of this software or the suitability of this
   software for any particular purpose.

   These notices must be retained in any copies of any part of this
   documentation and/or software.

   **********************************************************************

*/

# ifndef _LIBPHOENIX_H
# define _LIBPHOENIX_H

# include "pack.h" 
#include "ArrayList.h"

# include <fcntl.h>
# include <stdio.h>
# include <stdarg.h>
# include <strings.h>
# include <syslog.h>
# include <sys/types.h>
# include <sys/stat.h>


/* ********************************************************************** */

# ifndef MASTER_CONFIG_FILE
# define MASTER_CONFIG_FILE "/etc/progsys.conf"
# endif

# ifndef MASTER_CONFIG_FILE_PROT
# define MASTER_CONFIG_FILE_PROT S_IRUSR|S_IWUSR|S_IRGRP|S_IROTH
# endif

/* ********************************************************************** */

# ifndef TELNETFLAGFILE
# define TELNETFLAGFILE "/tmp/telnet.flag"
# endif

/* ********************************************************************** */

// useful global definitions 

# ifndef OSEXITERROR
# define OSEXITERROR	1
# endif

# ifndef OSEXITOKAY
# define OSEXITOKAY	0
# endif

# ifndef FALSE
# define FALSE	0
# endif

# ifndef TRUE
# define TRUE	!FALSE
# endif

/* ********************************************************************** */

#ifdef __cplusplus
extern "C" {
#endif

extern void Sys_Logger( int debuglvl, const char *fmt, ... ) ;

# define  LOG		Sys_Logger

extern void Sys_Logger_v( int debuglvl, const char *fmt, va_list vl ) ;

# define  VLOG		Sys_Logger_v


# define  LOG_STDERR    0       /* Log these messages to stderr */
# define  LOG_BASE      1       /* Mesgs that should always be logged */
# define  LOG_ERROR     1       /* Mesgs that should always be logged */
# define  LOG_HTML      3       /* Web Server information */
# define  LOG_BUTTON    3       /* Front panel */
# define  LOG_TIMING    4       /* Front panel */
# define  LOG_SRVR      4       /* Server Specific */
# define  LOG_COMM      6       /* Communication debugging */
# define  LOG_CRYPT     8       /* Cryptographic info */
# define  LOG_PROCTOL   9       /* This is where we're getting in real deep */


/* ********************************************************************** */

ArrayList * getDirectoryList (char *);
int checkfilename( char *filename ) ;
int CompressPath (const char *p, char *q, unsigned qlen) ;
int ValidPath(const char *path, const char *prefix) ;
int compairandparse( char* linein, char* compair, char* result, int resultsize ) ;
int fgetline( char* line, int linemax, FILE *infile ) ;
int element( char *returnarg, int returnsize, char *inputarg, char *delimiter, int elementnbr )  ;

void compressIP( char *arg, int size ) ;
int findmaskbits( char* maskarg ) ;

char* getrandomword( char* word ) ;
void stringDump( char *desc, BYTE *string, int stringlen ) ;
void bufferDump( char *desc, BYTE *string, int strlen ) ;

/* read OS release number as a sequence of 3 integers */
int GetOSRelease (int release[3]);

/* access to IP forwarding flag */
int GetIPForwardingFlag (int *pforward);
int SetIPForwardingFlag (int forward);


/* ********************************************************************** */

#ifdef __cplusplus
}
#endif

# endif
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
