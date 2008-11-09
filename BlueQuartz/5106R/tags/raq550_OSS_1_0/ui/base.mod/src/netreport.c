#include <errno.h>
#include <fcntl.h>
#include <stdio.h>
#include <string.h>
#include <unistd.h>

/* this will be running setgid root, so be careful! */

void usage(void) {
    fprintf(stderr, "usage: netreport [-r]\n");
    exit(1);
}

#define ADD 1
#define DEL 0
int main(int argc, char ** argv) {
    int action = ADD;
    /* more than long enough for "/var/run/netreport/<pid>\0" */
    char netreport_name[64];
    int  netreport_file;

    if (argc > 2) usage();

    if ((argc > 1) && !strcmp(argv[1], "-r")) {
	action = DEL;
    }

    sprintf(netreport_name, "/var/run/netreport/%d", getppid());
    if (action == ADD) {
	netreport_file = creat(netreport_name, 0);
	if (netreport_file < 0) {
	    if (errno != EEXIST) {
		perror("Could not create netreport file");
		exit (1);
	    }
	} else {
	    close(netreport_file);
	}
    } else {
	/* ignore errors; not much we can do, won't hurt anything */
	unlink(netreport_name);
    }

    exit(0);
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
