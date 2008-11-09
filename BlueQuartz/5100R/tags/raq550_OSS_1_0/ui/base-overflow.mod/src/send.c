/* 
#
# Name: send.c 
# Author: Ge Weijers
# Description: Sample code for sending messages to sgalertd
# Copyright 2001 Sun Microsystems, Inc. All rights reserved.
# $Id: send.c,v 1.2 2001/09/28 21:42:17 jthrowe Exp $
*/

#include <stdio.h>
#include <string.h>
#include <sys/types.h>
#include <stdarg.h>
#include <sys/ipc.h>
#include <sys/msg.h>

static char alert_file[] = "/var/tmp/sgalertd.channel";

static void SendAlert (const char *message, ...)
{
     FILE *f = fopen(alert_file, "r");
     int mq, len;
     enum { MAXMSG = 256 };
     va_list vl;
     struct { long type; char text[MAXMSG]; } msg;
     if(f == NULL){
	  return;
     };
     if(fscanf(f, "%d", &mq) != 1 || mq < 0){
	  fclose(f);
	  return;
     };
     fclose(f);
     va_start(vl, message);
     len = vsnprintf(msg.text, MAXMSG, message, vl);
     va_end(vl);
     msg.type = 1;
     if(len > MAXMSG || len < 0)
	  len = MAXMSG;
     msgsnd(mq, (void *)&msg, len, IPC_NOWAIT);
}

int main ()
{
  SendAlert("Hello, world %d", 4711);
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
