/* pam_abl.h */
/* Unless otherwise *explicitly* stated the following text
 * describes the licensed conditions under which the
 * contents of this module release may be distributed:
 *
 * --------------------------------------------------------
 * Redistribution and use in source and binary forms of
 * this module, with or without modification, are permitted
 * provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain any
 *    existing copyright notice, and this entire permission
 *    notice in its entirety, including the disclaimer of
 *    warranties.
 *
 * 2. Redistributions in binary form must reproduce all
 *    prior and current copyright notices, this list of
 *    conditions, and the following disclaimer in the
 *    documentation and/or other materials provided with
 *    the distribution.
 *
 * 3. The name of any author may not be used to endorse or
 *    promote products derived from this software without
 *    their specific prior written permission.
 *
 * ALTERNATIVELY, this product may be distributed under the
 * terms of the GNU General Public License, in which case
 * the provisions of the GNU GPL are required INSTEAD OF
 * the above restrictions.  (This clause is necessary due
 * to a potential conflict between the GNU GPL and the
 * restrictions contained in a BSD-style copyright.)
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR(S) BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * --------------------------------------------------------
 *
 * Copyright Andy Armstrong, andy@hexten.net, 2005
 */
#ifndef __PAM_ABL_H
#define __PAM_ABL_H

#include <security/pam_modules.h>
#include <db.h>

#include <ctype.h>
#include <errno.h>
#include <stdarg.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <syslog.h>
#include <time.h>
#include <unistd.h>
#include <sys/types.h>


#define MODULE_NAME "pam_abl"
#define DATA_NAME   MODULE_NAME

#define HOURSECS    (60 * 60)
/* Host purge time in seconds */
#define HOST_PURGE  (HOURSECS * 24)
/* User purge time in seconds */
#define USER_PURGE  (HOURSECS * 24)

#define CONFIG "/etc/security/pam_abl.conf"

typedef struct abl_string {
    struct abl_string *link;
} abl_string;

typedef struct {
    /* Session handle */
    pam_handle_t    *pamh;

    /* Standard args */
    int             debug;
    int             no_warn;
    int             use_first_pass;
    int             try_first_pass;
    int             use_mapped_pass;
    int             expose_account;

    /* Our args */
    const char      *host_db;
    const char      *host_rule;
    long            host_purge;
    const char      *user_db;
    const char      *user_rule;
    long            user_purge;

    int		    record_attempt;
    int		    check_only;

    /* Storage */
    abl_string      *strs;
} abl_args;

/* functions from log.c */

void log_out(const abl_args *args, int pri, const char *format, ...);
#if !defined(TOOLS)
void log_pam_error(const abl_args *args, int err, const char *what);
#endif
void log_sys_error(const abl_args *args, int err, const char *what);
void log_info(const abl_args *args, const char *format, ...);
void log_warning(const abl_args *args, const char *format, ...);
void log_debug(const abl_args *args, const char *format, ...);

/* functions from config.c */

void config_clear(pam_handle_t *pamh, abl_args *args);
int config_parse_args(pam_handle_t *pamh, int argc, const char **argv, abl_args *args);
int config_parse_file(const char *name, abl_args *args);
void config_free(abl_args *args);

/* functions from rule.c */

int rule_purge(DBT *rec, long maxage, time_t now);
int rule_parse_time(const char *p, long *t, long min);
int rule_test(const abl_args *args, const char *rule,
              const char *user, const char *service,
              const time_t *history, int histsz, time_t now);
int rule_matchperiods(const abl_args *args, const time_t *history, int histsz,
                        time_t now, const char **rp);

#endif /* __PAM_ABL_H */


