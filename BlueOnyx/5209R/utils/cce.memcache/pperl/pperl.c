/* $Id: pperl.c */

#include <cce_common.h>
#include <cce_paths.h>
#include <stdio.h>
#include <stdlib.h>
#include <signal.h>
#include <unistd.h>
#include <errno.h>
#include <string.h>
#include <sys/time.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/wait.h>
#include <ud_socket.h>

#define SOCKETFILE 	"pperl.socket"
#define PPERLD		CCEBINDIR "pperld"

static void start_pperld(void);
static int safe_write(int fd, const char *buf, int len);

int
main(int argc, char *argv[])
{
	int fd;
	char buf[1024];
	int r;
	fd_set rfds;
	char *runfile;

	if (argc != 2) {
		fprintf(stderr, "usage: %s <filename>\n", argv[0]);
		exit(1);
	}
	if (argv[1][0] != '/') {
		char *pathbuf;

		/* build an absolute path */
		pathbuf = getcwd(NULL, 0);
		runfile = malloc(strlen(pathbuf) + strlen(argv[1]) + 2);
		sprintf(runfile, "%s/%s", pathbuf, argv[1]);
	} else {
		runfile = argv[1];
	}

	fd = ud_connect(SOCKETFILE);
	if (fd < 0) {
		int ntries = 100;

		start_pperld();
		while (ntries-- && (fd = ud_connect(SOCKETFILE)) < 0) {
			usleep(50000);
		}
		
		if (fd < 0) {
			fprintf(stderr, "can't connect to pperld\n");
			exit(2);
		}
	}

	safe_write(fd, runfile, strlen(runfile));
	safe_write(fd, "\n", 1);

	while (1) {
		FD_ZERO(&rfds);
		FD_SET(STDIN_FILENO, &rfds);
		FD_SET(fd, &rfds);

		select(fd+1, &rfds, NULL, NULL, NULL);

		if (FD_ISSET(fd, &rfds)) {
			r = read(fd, buf, sizeof(buf)-1);
			if (r == 0) {
				safe_write(STDOUT_FILENO, "\n", 1);
				break;
			}
			buf[r] = '\0';
			safe_write(STDOUT_FILENO, buf, strlen(buf));
		} else if (FD_ISSET(0, &rfds)) {
			r = read(STDIN_FILENO, buf, sizeof(buf)-1);
			if (r == 0) {
				sprintf(buf, "BYE\n");
				r = 4;
			}
			buf[r] = '\0';
			safe_write(fd, buf, strlen(buf));
		} else {
			break;
		}
	}

	close(fd);

	return 0;
}

static void 
start_pperld(void)
{
	if (!fork()) {
		int i;	
		/* close any fd > STDERR_FILENO */
		for (i = STDERR_FILENO+1; i < 255; i++) {
			close(i);
		}
		execl(PPERLD, PPERLD, NULL);
	}
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
		fprintf(stderr, "pperl ERROR: safe_write timed out (%s)\n", 
			strerror(errno));
		return r;
	}

	return ttl;
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/