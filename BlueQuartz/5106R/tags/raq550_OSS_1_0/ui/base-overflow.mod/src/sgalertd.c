/*
#
# Name: sgalertd.c
# Author: Ge Weijers
# Description: This is the main c file for the sgalertd daemon. 
#  sgalertd handlers alerts for a buffer overflow on a cobalt appliance.
# Copyright 2001 Sun Microsystems, Inc. All rights reserved.
# $Id: sgalertd.c,v 1.13.2.2 2002/04/06 03:22:02 pbaltz Exp $
*/



#include <stdio.h>
#include <stddef.h>
#include <stdlib.h>
#include <errno.h>
#include <unistd.h>
#include <string.h>
#include <stdarg.h>
#include <syslog.h>
#include <signal.h>
#include <libintl.h>
#include <locale.h>
#include <sysexits.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <sys/ipc.h>
#include <sys/msg.h>
#include <sys/stat.h>

enum {
     MAX_ADDRESS_LEN = 2048
};

#define MAIL_CMD "/usr/sausalito/bin/i18nmail.pl"

#ifdef CCE_ENABLED
#include <cce/cce.h>

#else

#include "readconfig.h"
#include <stddef.h>
char email[MAX_ADDRESS_LEN];
int enabled;

#endif

/*
* Definitions for 'gettext' i18n interface
*/
#define _(STRING) gettext(STRING)
#define gettext_noop(STRING)  (STRING)
#define SGCONFIGFILE "/etc/overflow/overflow.conf"

int daemon_process = 0;		/* true if process runs as daemon */
int debug = 0;			/* > 0 if -d flag was used */

long mypid;

void Error (const char *fmt, ...)
{
     va_list vl;
     char message[1024];
     va_start(vl, fmt);
     (void)vsnprintf(message, sizeof(message), fmt, vl);
     message[1023] = '\0';
     va_end(vl);
     if(daemon_process)
	  syslog(LOG_NOTICE, "%s", message);
     else
	  fprintf(stderr, "%s\n", message);
}

/*
* Run this program in the background, and set up logging to use 'facility'.
*/
void Daemonize (const char *pname, int facility)
{
     if(getenv("PAFMGR") == NULL){
	  pid_t pid = fork();
	  int i, dt = getdtablesize();
	  if(pid == -1){
	       Error(_("fork(): %s"), strerror(errno));
	       exit(1);
	  };
	  if(pid != 0)
	       exit(0);
	  setsid();
	  signal(SIGHUP, SIG_IGN);
	  umask(0);
	  if(dt > 64 || dt < 0)
	       dt = 64;
	  for(i = 0; i < dt; i++)
	       close(i);
     };
     daemon_process = 1;
     openlog(pname, LOG_PID, facility);
     if(chdir("/") == -1){
	  Error(_("Can't change directory to %s"), "/");
	  exit(1);
     };
}

/*
* Strip path from file name
*/
const char * BaseName (const char *p)
{
     const char *q = p;
     char c;
     while((c = *p++) != '\0')
	  if(c == '/')
	       q = p;
     return q;
}

int ReadMessageQueueID (const char *fn, int quiet, long *ppid)
{
     FILE *f = fopen(fn, "r");
     int mq, rc;
     long pid;
     if(f == NULL){
	  if(!quiet)
	       Error(_("Can't open file %s: %s"), fn, strerror(errno));
	  return -1;
     };
     *ppid = -1;
     rc = fscanf(f, "%d %ld", &mq, &pid);
     switch(rc){
     case 2:
	  *ppid = pid;
     case 1:
	  if(mq < 0){
	       Error(_("File %s does not contain a (valid) number"), fn);
	       return -1;
	  };
     };
     fclose(f);
     return mq;
}

int DeleteMessageQueue (const char *fn)
{
     long pid;
     struct msqid_ds ds;
     int mq = ReadMessageQueueID(fn, 1, &pid);
     if(mq < 0)
	  return -1;
     if(msgctl(mq, IPC_STAT, &ds) != -1 || errno != EINVAL){
	 if(pid > 0 && pid != mypid && kill(pid, 0) == 0){
	      Error(_("Cannot delete message queue owned by process %ld"), pid);
	      return -1;
	 };
	 if(msgctl(mq, IPC_RMID, NULL) < 0){
	      Error(_("Cannot delete message queue: %s"), strerror(errno));
	      return -1;
	 }
     };
     if(unlink(fn) < 0 && errno != ENOENT){
	  Error(_("Can't delete file %s: %s"), fn, strerror(errno));
	  return -1;
     };
     return 0;
}


int SetupMessageQueue (const char *fn)
{
     FILE *f;
     mode_t oldmask;
     long pid;
     int mq;
     mq = ReadMessageQueueID(fn, 1, &pid);
     if(mq >= 0){
         if(pid > 0 && pid != mypid && kill(pid, 0) == 0){
             Error(_("Another alert daemon is already running: process %ld"),
	           pid);
             return -1;
         };
         if(DeleteMessageQueue(fn) < 0)
	     return -1;
     };
     mq = msgget(IPC_PRIVATE, IPC_CREAT | S_IRUSR | S_IWUSR | S_IWGRP | S_IWOTH);
     if(mq == -1){
	  Error(_("Cannot create a new private message queue: %s"), 
		strerror(errno));
	  return -1;
     };
     (void)unlink(fn);
     oldmask = umask(022);
     f = fopen(fn, "w");
     if(f == NULL){
	  Error(_("Can't create file %s: %s"), fn, strerror(errno));
	  return -1;
     };
     fprintf(f, "%d %ld\n", mq, mypid);
     fclose(f);
     umask(oldmask);
     return mq;
}

#define MAXMESSAGELEN 256

int ReceiveMessage (int mq, char *buffer, unsigned buflen)
{
     struct { long mtype; char text[MAXMESSAGELEN]; } msg;
     int len;
again:
     len = msgrcv(mq, (void *)&msg, MAXMESSAGELEN, 0, MSG_NOERROR);
     if(len  < 0){
	  switch(errno){
	  case EINTR:
	       goto again;
	  case EIDRM:
	       Error(_("Error queue was removed externally"));
	       return -1;
	  default:
	       Error(_("Receiving message failed: %s"), strerror(errno));
	       return -1;
	  }
     };
     if(len >= buflen)
	  len = buflen-1;
     memcpy(buffer, msg.text, len);
     buffer[len] = '\0';
     return 0;
}

static char alert_file[] = "/var/tmp/sgalertd.channel";

static void SigHandler (int sig)
{
     DeleteMessageQueue(alert_file);
     Error(_("Server terminated by signal %d"), sig);
     exit(1);
}

#ifdef CCE_ENABLED
int SGAlertEnabled() 
{
     // init variables
     cce_props_t *props = NULL;
     cce_handle_t *cce_handler = NULL;
     cscp_oid_t system_oid;
     char * IsEnabledChar;
     int IsEnabled, return_code;
     GSList *system_oids = NULL;

     // now to declare new handlers and connect to cce
     if (! (cce_handler = cce_handle_new())) { return 1; }
     if (! (props = cce_props_new())) { return 1; }
     return_code = cce_connect_cmnd(cce_handler, NULL);
     if (return_code == 0) { return 1; }

     // we are now connected, now to find the system object and get the 
     // portsentry namespace
     system_oids = cce_find_cmnd(cce_handler, "System", NULL);
     system_oid = (cscp_oid_t)system_oids->data;
     props = cce_get_cmnd(cce_handler, system_oid, "Overflow");

     // get our value for enabled, and convert it from a string
     // to a integer.  Yay for C for typecasting
     IsEnabledChar = cce_props_get(props, "enabled");
     IsEnabled = (int)IsEnabledChar[0] - 48;

     cce_bye_cmnd(cce_handler);
     cce_props_destroy(props);

     return IsEnabled;
}

#else 
void do_email (const char *keyword, const char *value)
{
     strncpy(email, value, sizeof(email));
}

void do_enable (const char *keyword, unsigned long number)
{
     enabled = number;
}

keyword_t kwtable[] = {
     KW_STRING("email", do_email),
     KW_NUMBER("enabled", do_enable),
};

int SGAlertEnabled() 
{

     FILE *config_file;
     char parse_error[128];
     if ((config_file = fopen(SGCONFIGFILE, "r")) == NULL) {
	  Error(_("Cannot open config file %s"), SGCONFIGFILE,  strerror(errno));
	  return 0;
     }

     if(ReadConfig(config_file, kwtable, sizeof(kwtable)/sizeof(kwtable[0]), parse_error, sizeof(parse_error)) == -1){
	  Error("Error: %s\n", parse_error);
	  return 0;
     };
     return enabled;
}
#endif


void GetFromAddress (char *fromEmail)
{
     strncpy(fromEmail, gettext("Buffer Overflow Protection Alert Daemon <root@localhost>"), 
	     MAX_ADDRESS_LEN);
}

#ifdef CCE_ENABLED
int GetToAddress (char *alertEmail)
{
     // init variables
     cce_props_t *props = NULL;
     cce_handle_t *cce_handler = NULL;
     cscp_oid_t system_oid;
     int return_code;
     GSList *system_oids = NULL;
     char *tempme;
     int res = -1;

     while (1) {
	  // now to declare new handlers and connect to cce

	  if (! (cce_handler = cce_handle_new())) { break; }
	  if (! (props = cce_props_new())) { break; }
	  return_code = cce_connect_cmnd(cce_handler, NULL);
	  if (return_code == 0) { break; }

	  // we are now connected, now to find the system object and get the
	  // portsentry namespace
	  system_oids = cce_find_cmnd(cce_handler, "System", NULL);
	  system_oid = (cscp_oid_t)system_oids->data;
	  props = cce_get_cmnd(cce_handler, system_oid, "Overflow");

	  // get our value for email
	  tempme = cce_props_get(props, "alertEmail");
	  snprintf(alertEmail,  MAX_ADDRESS_LEN, "%s", tempme);
	  res = 0;
	  break;
     }
     cce_bye_cmnd(cce_handler);
     cce_props_destroy(props);
     return res;
}

#else

int GetToAddress (char *alertEmail)
{
     FILE *config_file;
     char parse_error[128];
     if ((config_file = fopen(SGCONFIGFILE, "r")) == NULL) {
	  Error(_("Cannot open config file %s"), 
		SGCONFIGFILE,  strerror(errno));
	  return -1;
     }

     if(ReadConfig(config_file, kwtable, sizeof(kwtable)/sizeof(kwtable[0]), 
		   parse_error, sizeof(parse_error)) == -1){
	  Error("Error: %s\n", parse_error);
	  fclose(config_file);
	  return -1;
     };
     fclose(config_file);
     strncpy(alertEmail, email, sizeof(email));
     return 0;
}
#endif

char mail_message1[] =
gettext_noop(
     "Buffer Overflow Protection terminated a program because of a buffer overflow.");
char mail_message2[] =
gettext_noop(
     "The following information may help find the problem:");

void PutAddressList (FILE *f, const char *alist)
{
     char c;			/* the current character */
     unsigned ipos = 0;		/* the position of 'c' */
     unsigned opos = 0;		/* the position of the next char written */
     int newaddress = 0;	/* is this a new address (not the first one) */
     int quoted_char = 0;	/* is 'c' quoted using a backslash */
     int in_dquotes = 0;	/* is 'c' inside a double-quoted string */

     /* Write the To: string */
     fprintf(f, "To: ");	

     /* process each character in sequence */
     while((c = *alist++) != '\0'){
	  switch(c){
	  case '"':
	       /* a double quote is flagged using 'in_dquotes', unless
                  quoted using a backslash */
	       if(!quoted_char)
		    in_dquotes = !in_dquotes;
	       /* fallthru */
	  default:
	  normal:
	       /* normal character processing */

	       /* emit a comma and folding whitespace if this is the
                  start of the 2nd or later address */
	       if(newaddress){
		    fprintf(f, ",\n    ");
		    newaddress = 0;
	       };

	       /* output enough blanks */
	       if(opos > 0){
		    while(opos < ipos){
			 putc(' ', f);
			 opos++;
		    }
	       }else{
		    opos = ipos;
	       }

	       /* output the next character and update the positions */
	       putc(c, f);
	       ipos++;
	       opos++;
	       break;

	  case ' ':
	       ipos++;
	       break;
	  case '\t':
	       ipos = (ipos + 8)/8*8;
	       break;

	       /* handle the special characters */
	  case '&':
	  case ',':
	       if(quoted_char || in_dquotes)
		    goto normal;
	  case '\n':
	       newaddress = 1;
	       ipos = opos = 0;
	       break;
	  };
	  quoted_char = (quoted_char ? 0 : c == '\\');
     };
     putc('\n', f);
}

int DeliverMail (const char *message)
{
     int rc;
     int trycnt = 0;
     char FromAddress[MAX_ADDRESS_LEN];
     char ToAddress[MAX_ADDRESS_LEN];

     if(GetToAddress(ToAddress) < 0)
	  return -1;
     GetFromAddress(FromAddress);

     while(trycnt++ < 5){
	  FILE *ph;
	  int mail_cmd_len = 0, fd;
	  char *mail_cmd;
	  char *tmpfile = strdup("/tmp/.sgalertd-XXXXXX");
	  
	  /* check if strdup failed */
	  if (tmpfile == NULL) {
		Error(_("Could not start sendmail to deliver e-mail: %s"),
                     strerror(errno));
		return -1;
	  }

	  fd = mkstemp(tmpfile);
	  if (fd < 0) {
	       free(tmpfile);
	       Error(_("Could not start sendmail to deliver e-mail: %s"),
		     strerror(errno));
	       return -1;
	  };  
	  ph = fdopen(fd, "w");
	  if (ph == NULL) {
	       unlink(tmpfile);
	       free(tmpfile);
	       close(fd);
	       Error(_("Could not start sendmail to deliver e-mail: %s"),
		     strerror(errno));
	       return -1;
	  };

	  /* keep perl quiet */
	  setenv("PERL_BADLANG", "0", 1);

	  /*
	   * figure out the length of our mail command
	   * "MAIL_CMD -s "subj" -f "from" emails < tmpfile"
	   */
	  mail_cmd_len = strlen(MAIL_CMD) + 5 +
		strlen(_("Buffer Overflow Protection detected buffer overflow")) +
		6 + strlen(FromAddress) + 2 + strlen(ToAddress) + 3 +
		strlen(tmpfile) + 1;

	  mail_cmd = malloc(mail_cmd_len);
	  /* check if the malloc faile */
	  if (mail_cmd == NULL) {
		unlink(tmpfile);
		free(tmpfile);
		fclose(ph);
		close(fd);

		Error(_("Could not start sendmail to deliver e-mail: %s"),
                     strerror(errno));
		return -1;
	  }

	  /* comment this out for alpine, because to gets specified
	   * as part of the command
	   * PutAddressList(ph, ToAddress);
	   * fprintf(ph, "Subject: %s\n\n", _("Buffer Overflow Protection detected buffer overflow"));
	   */
	  fprintf(ph, "%s\n", gettext(mail_message1));
	  fprintf(ph, "%s\n", gettext(mail_message2));
	  fprintf(ph, "%s\n", message);
	  fclose(ph);

	  /* create the command string */
	  snprintf(mail_cmd, mail_cmd_len, "%s -s \"%s\" -f \"%s\" %s < %s",
		MAIL_CMD,
		_("Buffer Overflow Protection detected buffer overflow"),
		FromAddress, ToAddress, tmpfile);

	  rc = system(mail_cmd);
	  free(mail_cmd);
	  unlink(tmpfile);
	  free(tmpfile);
	  close(fd);
	  if (rc) {
		sleep(5 * trycnt);
	  } else {
		return 0;
	  }
     };
     Error(_("Tried %d times to deliver message, aborting"), trycnt);
     return -1;
}

int RunServer (const char *progname)
{
     int mq = SetupMessageQueue(alert_file);

     if(mq < 0)
	  return mq;
     (void)signal(SIGTERM, SigHandler);
     (void)signal(SIGINT, SigHandler);
     Error(_("Daemon %s started"), progname);
     for(;;){
	  char buffer[MAXMESSAGELEN];
	  if(ReceiveMessage(mq, buffer, MAXMESSAGELEN) < 0)
	       break;
          if(debug){
	       Error(_("message received"));
	  }
	  if (SGAlertEnabled()) { 
	       DeliverMail(buffer);
	  } 
     
     };
     DeleteMessageQueue(alert_file);
     return 0;
}

void SEND (const char *message)
{
     FILE *f = fopen(alert_file, "r");
     int mq, len = strlen(message);
     enum { MAXMSG = 256 };
     struct { long type; char text[MAXMSG]; } msg;
     if(f == NULL){
	  return;
     };
     if(fscanf(f, "%d", &mq) != 1 || mq < 0){
	  fclose(f);
	  return;
     };
     fclose(f);
     msg.type = 1;
     if(len > MAXMSG)
	  len = MAXMSG;
     memcpy(msg.text, message, len);
     msgsnd(mq, (void *)&msg, len, IPC_NOWAIT);
}

static char helptext[] = 
gettext_noop("Usage: %s [-d]"
	     "  -d (debug) will run the program in the foreground and"
	     "     send messages to standard error"
	     "  -T <text> .....   send a Buffer Overflow Protection alert containing <text>");

const char *FormatTestMessage (int argc, char *argv[])
{
     unsigned width = 0, l;
     int i;
     char *ptr;
     for(i = 0; i < argc; i++){
	  if(i > 0)width++;
          width += strlen(argv[i]);
     };
     if(width == 0)
          return "";
     ptr = malloc(width+1);
     if(ptr == NULL)
          return _("Out of memory in sgalertd test");
     for(i = 0; i < argc; i++){
          if(i > 0)*ptr++ = ' ';
          l = strlen(argv[i]);
          memcpy(ptr, argv[i], l);
          ptr += l;
     };
     *ptr = '\0';
     return ptr-width;
}

int main (int argc, char *argv[])
{
     int opt;
     long pid;
     unsigned i;
     const char *progname = BaseName(argv[0]);
     enum { CMD_DAEMON, CMD_TEST, CMD_KILL } cmd = CMD_DAEMON;
     /* i18n setup */

     setlocale(LC_ALL, "");
     bindtextdomain(PACKAGE, LOCALEDIR);
     textdomain(PACKAGE);

     /* save my process ID for later use */
     mypid = (long)getpid();

     while((opt = getopt(argc, argv, "dTk")) != -1){
	  switch(opt){
	  case 'd':
	       debug++;
	       break;
          case 'T':
               cmd = CMD_TEST;
	       break;
          case 'k':
	       cmd = CMD_KILL;
  	       break;
	  default:
	       goto help;
	  }
     };
     switch(cmd){
     case CMD_DAEMON:
	 if(optind >= argc){
	      if(debug == 0)
		   Daemonize(progname, LOG_DAEMON);
	      RunServer(progname);
         }else{
	      goto help;
         }
	 break;
     case CMD_TEST:
	  SEND(FormatTestMessage(argc-optind, &argv[optind]));
          break;
     case CMD_KILL:
          if(ReadMessageQueueID(alert_file, 1, &pid) < 0 || pid <= 0){
              Error(_("No sgalertd is running"));
	      return 1;
          };
          if(kill(pid, SIGTERM) < 0){
	      Error(_("Could not kill process %ld: %s"), pid, strerror(errno));
	      return 1;
          };
	  for(i = 0; i < 10; i++){
	      if(kill(pid, 0) < 0 && errno == ESRCH)
	          return 0;
              sleep(1);
          };
          Error(_("Process %ld did not terminate"), pid);
          return 1;
	  break;
     default:
	  goto help;
     };
     return 0;
help:
     fprintf(stderr, gettext(helptext), progname);
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
