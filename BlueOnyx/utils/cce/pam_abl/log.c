/* log.c */
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
#include "pam_abl.h"

void log_out(const abl_args *args, int pri, const char *format, ...) {
    va_list ap;

    (void) args;

    va_start(ap, format);
#if defined(TEST) || defined(TOOLS)
    vfprintf(stderr, format, ap);
    fprintf(stderr, "\n");
#else
    openlog(MODULE_NAME, LOG_CONS | LOG_PID, LOG_AUTHPRIV);
    vsyslog(pri, format, ap);
    closelog();
#endif
    va_end(ap);
}

#if !defined(TOOLS)
void log_pam_error(const abl_args *args, int err, const char *what) {
    log_out(args, LOG_ERR, "%s (%d) while %s", pam_strerror(args->pamh, err), err, what);
}
#endif

void log_sys_error(const abl_args *args, int err, const char *what) {
    log_out(args, LOG_ERR, "%s (%d) while %s", strerror(err), err, what);
}

void log_info(const abl_args *args, const char *format, ...) {
    va_list ap;

    va_start(ap, format);
#if defined(TEST) || defined(TOOLS)
    fprintf(stderr, "INFO: ");
    vfprintf(stderr, format, ap);
    fprintf(stderr, "\n");
#else
    openlog(MODULE_NAME, LOG_CONS | LOG_PID, LOG_AUTHPRIV);
    vsyslog(LOG_INFO, format, ap);
    closelog();
#endif
    va_end(ap);
}

void log_warning(const abl_args *args, const char *format, ...) {
    va_list ap;

    va_start(ap, format);
    if (!args->no_warn) {
#if defined(TEST) || defined(TOOLS)
        fprintf(stderr, "WARNING: ");
        vfprintf(stderr, format, ap);
        fprintf(stderr, "\n");
#else
        openlog(MODULE_NAME, LOG_CONS | LOG_PID, LOG_AUTHPRIV);
        vsyslog(LOG_WARNING, format, ap);
        closelog();
#endif
    }
    va_end(ap);
}

void log_debug(const abl_args *args, const char *format, ...) {
    va_list ap;

    va_start(ap, format);
    /* Nasty bodge: we can force debug output by passing
     * NULL for args which is useful in cases where we
     * don't have a valid args structure.
     */
    if (NULL == args || args->debug) {
#if defined(TEST) || defined(TOOLS)
        fprintf(stderr, "DEBUG: ");
        vfprintf(stderr, format, ap);
        fprintf(stderr, "\n");
#else
        openlog(MODULE_NAME, LOG_CONS | LOG_PID, LOG_AUTHPRIV);
        vsyslog(LOG_DEBUG, format, ap);
        closelog();
#endif
    }
    va_end(ap);
}

