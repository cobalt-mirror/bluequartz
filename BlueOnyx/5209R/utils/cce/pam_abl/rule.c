/* rule.c */
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

static int parse_long(const char **sp, long *lp) {
    long l = 0;

    if (!isdigit(**sp)) {
        return EINVAL;
    }

    while (isdigit(**sp)) {
        l = l * 10 + *(*sp)++ - '0';
    }

    *lp = l;
    return 0;
}

/* Parse a time specification in the form
 * <digits>[s|m|h|d]
 */
static int parse_time(const char **sp, long *tp) {
    long t;
    int err;

    if (err = parse_long(sp, &t), 0 != err) {
        return err;
    }

    /* Handle the multiplier suffix */
    switch (**sp) {
    case 'd':
        t *= 24;
    case 'h':
        t *= 60;
    case 'm':
        t *= 60;
    case 's':
        (*sp)++;
    }

    *tp = t;

    return 0;
}

int rule_parse_time(const char *p, long *t, long min) {
    int err;

    if (err = parse_time(&p, t), 0 != err) {
        *t = min;
        return err;
    }

    if (*p != '\0') {
        *t = min;
        return EINVAL;
    }

    if (*t < min) {
        *t = min;
    }

    return 0;
}

static int wordlen(const char *rp) {
    int l = 0;
    while (*rp != '\0' &&
           *rp != '/'  &&
           *rp != '|'  &&
           *rp != '='  &&
           !isspace(*rp)) {
        rp++;
        l++;
    }
    return l;
}

static int match(const abl_args *args, const char *pattern, const char *target, int len) {
    log_debug(args, "match('%s', '%s', %d)", pattern, target, len);
    return (len == strlen(pattern)) && (memcmp(pattern, target, len) == 0);
}

static int matchname(const abl_args *args, const char *user, const char *service,
                     const char **rp) {
    int l = wordlen(*rp);
    int ok;

    log_debug(args, "Check %s/%s against %s(%d)", user, service, *rp, l);

    ok = (l != 0) && ((l == 1 && **rp == '*') || match(args, user, *rp, l));
    (*rp) += l;
    if (ok) {
        log_debug(args, "Name part matches, **rp = '%c'", **rp);
    }
    if (**rp == '/') {
        (*rp)++;
        l = wordlen(*rp);
        ok &= (l != 0) && ((l == 1 && **rp == '*') || match(args, service, *rp, l));
        (*rp) += l;
    }

    log_debug(args, "%satch!", ok ? "M" : "No m");

    return ok;
}

static int matchnames(const abl_args *args, const char *user, const char *service,
                      const char **rp) {
    int ok = matchname(args, user, service, rp);
    while (**rp == '|') {
        (*rp)++;
        ok |= matchname(args, user, service, rp);
    }
    return ok;
}

static long howmany(const abl_args *args, const time_t *history, int histsz,
                    time_t now, long limit) {
    int i = histsz - 1;
    while (i >= 0 && difftime(now, history[i]) < (double) limit) {
        i--;
    }

    log_debug(args, "howmany(%ld) = %d", limit, histsz - i - 1);

    return histsz - i - 1;
}

static int matchperiod(const abl_args *args, const time_t *history, int histsz,
                       time_t now, const char **rp) {
    int err;
    long count, period;

    log_debug(args, "matchperiod(%p, %d, '%s')", history, histsz, *rp);

    if (err = parse_long(rp, &count), 0 != err) {
        return 0;
    }
    log_debug(args, "count is %ld, **rp='%c'", count, **rp);
    if (**rp != '/') {
        return 0;
    }
    (*rp)++;
    if (err = parse_time(rp, &period), 0 != err) {
        return 0;
    }
    log_debug(args, "period is %ld, **rp='%c'", period, **rp);
    log_debug(args, "Checking %ld/%ld", count, period);
    return howmany(args, history, histsz, now, period) >= count;
}

int rule_matchperiods(const abl_args *args, const time_t *history, int histsz,
                        time_t now, const char **rp) {
    if (matchperiod(args, history, histsz, now, rp)) {
        return 1;
    }
    while (**rp == ',') {
        (*rp)++;
        if (matchperiod(args, history, histsz, now, rp)) {
            return 1;
        }
    }
    return 0;
}

static int check_clause(const abl_args *args, const char **rp,
                        const char *user, const char *service,
                        const time_t *history, int histsz, time_t now) {
    int inv = 0;

    if (**rp == '!') {
        inv = 1;
        (*rp)++;
    }

    if (!(inv ^ matchnames(args, user, service, rp))) {
        return 0;
    }

    log_debug(args, "Name matched, next char is '%c'", **rp);

    /* The name part matches so now check the trigger clauses */
    if (**rp != '=') {
        return 0;
    }

    (*rp)++;
    return rule_matchperiods(args, history, histsz, now, rp);
}

int rule_purge(DBT *rec, long maxage, time_t now) {
    size_t sz = rec->size / sizeof(time_t);
    time_t *tp = (time_t *) rec->data;
    unsigned int i;

    for (i = 0; i < sz; i++) {
        if (difftime(now, tp[i]) < (double) maxage) {
            break;
        }
    }

    rec->size = (sz - i) * sizeof(time_t);
    memmove(rec->data, &tp[i], rec->size);

    return i;
}

/* Apply a rule to a history record returning 1 if the rule matches, 0 if the
 * rule fails.
 *
 * Rule syntax is like:
 *
 * word         ::= /[^\s\|\/\*]+/
 * name         ::= word | '*'
 * username     ::= name
 * servicename  ::= name
 * userservice  ::= username
 *              |   username '/' servicename
 * namelist     ::= userservice
 *              |   userservice '|' namelist
 * userspec     ::= namelist
 *              |   '!' namelist
 * multiplier   ::= 's' | 'm' | 'h' | 'd'
 * number       ::= /\d+/
 * period       ::= number
 *              |   number multiplier
 * trigger      ::= number '/' period
 * triglist     ::= trigger
 *              |   trigger ',' triglist
 * userclause   ::= userspec '=' triglist
 * rule         ::= userclause
 *              |   userclause /\s+/ rule
 *
 * This gives rise to rules like
 *
 * !root|admin/sshd=10/1m,100/1d root=10/3m
 *
 * which means for accounts other than 'root' or 'admin' trigger if there were ten
 * or more events in the last minute or 100 or more events in the last day. For
 * 'root' trigger if there were ten or more events in the last three minutes.
 */
int rule_test(const abl_args *args, const char *rule,
              const char *user, const char *service,
              const time_t *history, int histsz, time_t now) {
    const char *rp = rule;

    while (*rp != '\0') {
        if (check_clause(args, &rp, user, service, history, histsz, now)) {
            return 1;
        }
        while (*rp != '\0' && !isspace(*rp)) {
            rp++;
        }
        while (isspace(*rp)) {
            rp++;
        }
    }

    return 0;
}

