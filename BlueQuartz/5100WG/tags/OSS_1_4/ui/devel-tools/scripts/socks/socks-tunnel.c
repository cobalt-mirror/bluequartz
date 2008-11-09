/***********************************************************************
 * socks-tunnel.c
 * Make socket connection using SOCKS4/5 and HTTP tunnel.
 *
 * Copyright (c) 2000, 2001 Shun-ichi Goto
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * ---------------------------------------------------------
 * PROJECT:  My Test Program
 * AUTHOR:   Shun-ichi GOTO <gotoh@taiyo.co.jp>
 * CREATE:   Wed Jun 21, 2000
 * REVISION: $Revision: 3 $
 * ---------------------------------------------------------
 *
 * How To Compile:
 *  On UNIX environment:
 *      $ gcc socks-tunnel.c -o socks-tunnel
 *    or
 *      $ cc socks-tunnel.c -o socks-tunnel
 * 
 *  on Win32 environment:
 *      $ cl socks-tunnel.c wsock32.lib
 *    or
 *      $ bcc32 socks-tunnel.c wsock32.lib
 *
 * How To Use:
 *   You can specify proxy method by environment variable or command
 *   line option.
 * 
 *   usage:  socks-tunnel [-dnhs45] [-R resolve]
 *                   [-H proxy-server-sepc] [-S [user@]socks-server[:port]]
 *                   host port
 *
 *   "host" and "port" is for target hostname and port-number to connect.
 *   
 *   -H option specify hostname and port number of http proxy server to
 *   relay. If port is omitted, 80 is used. You can specify this value by
 *   environment variable HTTP_PROXY and give -h option to use it.
 *   
 *   -S option specify hostname and port number of SOCKS server to relay.
 *   Like -H, port number can be omit and default is 1080. You can also
 *   specify this value pair by environment variable SOCKS5_SERVER and
 *   give -s option to use it.
 *   
 *   -4 and -5 is for specifying SOCKS protocol version. It is valid only
 *   using with -s or -S. Default is -5 (protocol version 5)
 *
 *   -R is for specifying method to resolve hostname. 3 keywords are
 *   available: "local", "remote", "both". Keyword "both" means, "Try
 *   local first, then remote". Default is "remote" for SOCKS5 or
 *   "local" for others. On SOCKS4 protocol, remote resolving method
 *   ("remote" and "both") requires protocol 4a supported server.
 *   
 *   -d option is used for debug. If you fail to connect, use this and
 *   check request to and response from server.
 *
 *   You can omit "port" argument when program name is special format
 *   containing port number itself. For example, 
 *     $ ln -s socks-tunnel socks-tunnel-25
 *   means this socks-tunnel-25 command is spcifying port number 25 already
 *   so you need not 2nd argument (and ignored if specified).
 *
 *   To use proxy, this example is for SOCKS5 connection to connect to
 *   'host' at port 25 via SOCKS5 server on 'firewall' host.
 *     $ socks-tunnel -S firewall  host 25
 *   or
 *     $ SOCKS5_SERVER=firewall; export SOCKS5_SERVER
 *     $ socks-tunnel -s host 25
 *
 *   And this is for HTTP-PROXY connection:
 *     $ socks-tunnel -H proxy-server:8080  host 25
 *   or
 *     $ HTTP_PROXY=proxy-server:8080; export HTTP_PROXY
 *     $ socks-tunnel -h host 25
 *
 * For Your Information
 *
 *   SOCKS5 -- RFC 1928, RFC 1929, RFC 1961
 *             NEC SOCKS Reference Implementation is available from:
 *               http://www.socks.nec.com
 *             DeleGate version 5 or earlier can be SOCKS4 server,
 *             and version 6 canbe SOCKS5 and SOCKS4 server.
 *               http://www.delegate.org/delegate/
 *
 *   HTTP-Proxy --
 *             Many http proxy servers supports this, but https should
 *             be allowed as configuration on your host.
 *             For example on DeleGate, you should add "https" to 
 *             "REMITTABLE" parameter to allow HTTP-Proxy like this:
 *               delegated -Pxxxx ...... REMITTABLE="+,https" ...
 *
 ***********************************************************************/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include <memory.h>
#include <errno.h>
#include <assert.h>
#include <sys/types.h>
#include <stdarg.h>

#ifdef __CYGWIN32__
#undef _WIN32
#endif

#ifdef _WIN32
#include <windows.h>
#include <winsock.h>
#include <sys/stat.h>
#include <io.h>
#include <conio.h>
#include <fcntl.h>
#else /* !_WIN32 */
#include <unistd.h>
#include <sys/time.h>
#include <sys/select.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>
#ifndef __CYGWIN32__
#include <arpa/nameser.h>
#include <resolv.h>
#endif /* __CYGWIN32__ */
#endif /* !_WIN32 */

#ifndef LINT
static char *vcid = "$Id: socks-tunnel.c 3 2003-07-17 15:19:15Z will $";
#endif


/* available authentication types */
#define SOCKS_ALLOW_NO_AUTH
#undef SOCKS_ALLOW_USERPASS_AUTH

/* consider Borland C */
#ifdef __BORLANDC__
#define _kbhit kbhit
#endif

/* help message */
static char *usage =
"usage: %s [-dnhs45] [-R resolve] \n"
"          [-H proxy-server[:port]] [-S [user@]socks-server[:port]] \n"
"          host port\n";

/* name of this program */
char *progname = NULL;

/* options */
int f_debug = 0;

/* relay method, server and port */
#define METHOD_UNDECIDED 0
#define METHOD_DIRECT    1
#define METHOD_SOCKS     2
#define METHOD_HTTP      3
char *method_names[] = { "UNDICIDED", "DIRECT", "SOCKS", "HTTP" };

int   relay_method = METHOD_UNDECIDED;		/* relaying method */
char *relay_host = NULL;			/* hostname of relay server */
short relay_port = 0;				/* port of relay server */
char *relay_user = NULL;			/* user name for auth */

/* destination target host and port */
char *dest_host = NULL;
struct in_addr dest_addr;
unsigned short dest_port = 0;

/* informations for SOCKS */
#define SOCKS5_REP_SUCCEEDED	0x00	/* succeeded */
#define SOCKS5_REP_FAIL		0x01	/* general SOCKS serer failure */
#define SOCKS5_REP_NALLOWED	0x02	/* connection not allowed by ruleset */
#define SOCKS5_REP_NUNREACH	0x03	/* Network unreachable */
#define SOCKS5_REP_HUNREACH	0x04	/* Host unreachable */
#define SOCKS5_REP_REFUSED	0x05	/* conenction refused */
#define SOCKS5_REP_EXPIRED	0x06	/* TTL expired */
#define SOCKS5_REP_CNOTSUP	0x07	/* Command not supported */
#define SOCKS5_REP_ANOTSUP	0x08	/* Address not supported */
#define SOCKS5_REP_INVADDR	0x09	/* Inalid address */
/* SOCKS5 authentication methods */
#define SOCKS5_AUTH_REJECT	0xFF	/* No acceptable auth method */
#define SOCKS5_AUTH_NOAUTH	0x00	/* without authentication */
#define SOCKS5_AUTH_GSSAPI	0x01	/* GSSAPI */
#define SOCKS5_AUTH_USERPASS	0x02	/* User/Password */
#define SOCKS5_AUTH_CHAP	0x03	/* Challenge-Handshake Auth Proto. */
#define SOCKS5_AUTH_EAP		0x05	/* Extensible Authentication Proto. */
#define SOCKS5_AUTH_MAF		0x08	/* Multi-Authentication Framework */

#define SOCKS4_REP_SUCCEEDED	90	/* rquest granted (succeeded) */
#define SOCKS4_REP_REJECTED	91	/* request rejected or failed */
#define SOCKS4_REP_IDENT_FAIL	92	/* cannot connect identd */
#define SOCKS4_REP_USERID	93	/* user id not matched */

#define RESOLVE_UNKNOWN 0
#define RESOLVE_LOCAL   1
#define RESOLVE_REMOTE  2
#define RESOLVE_BOTH    3
#define RESOLVE_OTHER   4
char *resolve_names[] = { "UNKNOWN", "LOCAL", "REMOTE", "BOTH", "OTHER" };

int socks_version = 5;				/* SOCKS protocol version */
int socks_resolve = RESOLVE_UNKNOWN;
int socks_ns;

/* Environment variable names */
#define ENV_SOCKS_SERVER  "SOCKS_SERVER"	/* SOCKS server */
#define ENV_SOCKS5_SERVER "SOCKS5_SERVER"
#define ENV_SOCKS4_SERVER "SOCKS4_SERVER"

#define ENV_SOCKS_RESOLVE  "SOCKS_RESOLVE"	/* resolve method */
#define ENV_SOCKS5_RESOLVE "SOCKS5_RESOLVE"
#define ENV_SOCKS4_RESOLVE "SOCKS4_RESOLVE"

#define ENV_SOCKS5_PASSWORD "SOCKS5_PASSWORD"	/* password */

#define ENV_HTTP_PROXY    "HTTP_PROXY"		/* commonly used */

/* Prefix string of HTTP_PROXY */
#define HTTP_PROXY_PREFIX "http://"

/* socket related definitions */
#ifndef _WIN32
#define SOCKET int
#endif
#ifndef SOCKET_ERROR
#define SOCKET_ERROR -1
#endif

#ifndef FD_ALLOC
#define FD_ALLOC(nfds) ((fd_set*)malloc((nfds+7)/8))
#endif /* !FD_ALLOC */

#ifdef _WIN32
#define socket_errno() WSAGetLastError()
#else /* !_WIN32 */
#define closesocket close
#define socket_errno() (errno)
#endif /* !_WIN32 */

/* packet operation macro */
#define PUT_BYTE(ptr,data) (*(unsigned char*)ptr = data)
#define PUT_WORD(ptr,data) (*(unsigned short*)ptr = htons(data))

/* debug message output */
void
debug( const char *fmt, ... )
{
    va_list args;
    if ( f_debug ) {
	va_start( args, fmt );
	fprintf(stderr, "DEBUG: ");
	vfprintf( stderr, fmt, args );
	va_end( args );
    }
}

/* error message output */
void
error( const char *fmt, ... )
{
    va_list args;
    va_start( args, fmt );
    fprintf(stderr, "ERROR: ");
    vfprintf( stderr, fmt, args );
    va_end( args );
}

downcase( char *buf )
{
    while ( *buf ) {
	if ( isupper(*buf) )
	    *buf -= 'a'-'A';
	buf++;
    }
}

int
lookup_resolve( const char *str )
{
    char *buf = strdup( str );
    int ret;

    downcase( buf );
    if ( strcmp( buf, "both" ) == 0 )
	ret = RESOLVE_BOTH;
    else if ( strcmp( buf, "remote" ) == 0 )
	ret = RESOLVE_REMOTE;
    else if ( strcmp( buf, "local" ) == 0 )
	ret = RESOLVE_LOCAL;
    else if ( strspn(buf, "0123456789.") == strlen(buf) ) {
#if defined(_WIN32) || defined(__CYGWIN32__)
	error("Sorry, you can't specify name resolve host with -R option on Win32 environment.");
	exit(1);
#endif /* _WIN32 || __CYGWIN32 */
	ret = RESOLVE_OTHER;
	socks_ns = inet_addr(buf);
    }
    else
	ret = RESOLVE_UNKNOWN;
    free(buf);
    return ret;
}

/* set_relay()
   Determine relay informations:
   method, host, port, and username.
   1st arg, METHOD should be METHOD_xxx.
   2nd arg, SPEC is hostname or hostname:port or user@hostame:port.
   hostname is domain name or dot notation.
   If port is omitted, use 80 for METHOD_HTTP method,
   use 1080 for METHOD_SOCKS method.
   Username is also able to given by 3rd. format.
   2nd argument SPEC can be NULL. if NULL, use environment variable.
 */
int
set_relay( int method, char *spec )
{
    char *buf, *sep, *resolve;
    
    relay_method = method;

    switch ( method ) {
    case METHOD_DIRECT:
	return -1;				/* nothing to do */
	
    case METHOD_SOCKS:
	if ( spec == NULL ) {
	    switch ( socks_version ) {
	    case 5:
		spec = getenv(ENV_SOCKS5_SERVER);
		break;
	    case 4:
		spec = getenv(ENV_SOCKS4_SERVER);
		break;
	    }
	}
	if ( spec == NULL )
	    spec = getenv(ENV_SOCKS_SERVER);
	
	if ( spec == NULL ) {
	    error("Failed to determine SOCKS server.\n");
	    exit(1);
	}
	relay_port = 1080;			/* set default first */

	/* determine resolve method */
	if ( socks_resolve == RESOLVE_UNKNOWN ) {
	    if ( ((socks_version == 5) &&
		  ((resolve = getenv(ENV_SOCKS5_RESOLVE)) != NULL)) ||
		 ((socks_version == 4) &&
		  ((resolve = getenv(ENV_SOCKS4_RESOLVE)) != NULL)) ||
		 ((resolve = getenv(ENV_SOCKS_RESOLVE)) != NULL) ) {
		socks_resolve = lookup_resolve( resolve );
		if ( socks_resolve == RESOLVE_UNKNOWN ) {
		    error("Invalid resolve method: %s\n", resolve);
		    exit(3);
		}
	    } else {
		/* default */
		if ( socks_version == 5 )
		    socks_resolve = RESOLVE_REMOTE;
		else
		    socks_resolve = RESOLVE_LOCAL;
	    }
	}
	break;
	
    case METHOD_HTTP:
	if ( spec == NULL )
	    spec = getenv(ENV_HTTP_PROXY);
	if ( spec == NULL ) {
	    error("You must specify http proxy server\n");
	    exit(1);
	}
	relay_port = 80;			/* set default first */
	break;
    }
    
    /* consider "http://server:port/" format */
    if ( strncmp( spec, HTTP_PROXY_PREFIX, strlen(HTTP_PROXY_PREFIX) ) == 0 ) {
	/* convert to URL format */
	int len;
	buf = strdup( spec + strlen(HTTP_PROXY_PREFIX));
	len = strcspn( buf, "/" );
	buf[len] = '\0';
    } else {
	buf = strdup( spec );
    }
    spec = buf;
    
    /* check username in spec */ 
    sep = strchr( spec, '@' );
    if ( sep != NULL ) {
	*sep = '\0';
	relay_user = strdup( spec );
	spec = sep +1;
    }
    if ( (relay_user == NULL) &&
	 ((relay_user = getenv("LOGNAME")) == NULL) &&
	 ((relay_user = getenv("USER")) == NULL) ) {
	/* get username from system */
#ifdef _WIN32
	char buf[255];
	DWORD size = sizeof(buf);
	buf[0] = '\0';
	GetUserName( buf, &size);
	relay_user = strdup(buf);
#endif
    }
    
    /* split out hostname and port number from spec */
    sep = strchr(spec,':');
    if ( sep == NULL ) {
	/* hostname only, port is already set as default */
	relay_host = strdup( spec );
    } else {
	/* hostname and port */
	relay_port = atoi(sep+1);
	*sep = '\0';
	relay_host = strdup( spec );
    }
    free(buf);
    return 0;
}


int
getarg( int argc, char **argv )
{
    int err = 0;
    char *ptr, *server;
    int port, method = METHOD_DIRECT;
    
    progname = *argv;
    argc--, argv++;

    /* check optinos */
    while ( (0 < argc) && (**argv == '-') ) {
	ptr = *argv + 1;
	while ( *ptr ) {
	    switch ( *ptr ) {
	    case 's':				/* use SOCKS */
		method = METHOD_SOCKS;
		break;

	    case 'n':				/* no proxy */
		method = METHOD_DIRECT;
		break;
		
	    case 'h':				/* use http-proxy */
		method = METHOD_HTTP;
		break;
	    
	    case 'S':				/* specify SOCKS server */
		if ( 0 < argc ) {
		    argv++, argc--;
		    method = METHOD_SOCKS;
		    server = *argv;
		} else {
		    error("option '-%c' needs argument.\n", *ptr);
		    err++;
		}
		break;
	    
	    case 'H':				/* specify http-proxy server */
		if ( 0 < argc ) {
		    argv++, argc--;
		    method = METHOD_HTTP;
		    server = *argv;
		} else {
		    error("option '-%c' needs argument.\n", *ptr);
		    err++;
		}
		break;
		
	    case 'P':
		/* destination port number */
		if ( 0 < argc ) {
		    argv++, argc--;
		    dest_port = (unsigned short)atoi(*argv);
		} else {
		    error("option '-%c' needs argument.\n", *ptr);
		    err++;
		}
		break;
		
	    case '4':
		socks_version = 4;
		break;
		
	    case '5':
		socks_version = 5;
		break;

	    case 'R':				/* specify resolve method */
		if ( 0 < argc ) {
		    argv++, argc--;
		    socks_resolve = lookup_resolve( *argv );
		} else {
		    error("option '-%c' needs argument.\n", *ptr);
		    err++;
		}
		break;
		
	    case 'd':				/* debug mode */
		f_debug = 1;
		break;
	    
	    default:
		error("unknown option '-%c'\n", *ptr);
		err++;
	    }
	    ptr++;
	}
	argc--, argv++;
    }
    
    /* check error */
    if ( 0 < err ) {
	fprintf(stderr, usage, progname);
	err++;
	goto quit;
    }
	
    set_relay( method, server );

    /* check destination HOST and PORT argument */
    if ( argc == 0  ) {
	error( "You must specify hostname.\n");
	err++;
	goto quit;
    } else {
	dest_host = argv[0];
	/* decide port number from program name */
	if ( ((ptr=strrchr( progname, '/' )) != NULL) ||
	     ((ptr=strchr( progname, '\\')) != NULL) )
	    ptr++;
	else
	    ptr = progname;
	if ( sscanf( ptr, "socks-tunnel-%d", &port) == 1 ) {
	    /* ignore port number argument and extras */
	    dest_port = (unsigned short)port;
	} else if ( (dest_port == 0) && (1 < argc) ) {
	    /** NOTE: This way is for cvs ext method. **/
	    /** accept only if -P is not specified. **/
	    dest_port = atoi(argv[1]);
	}
    }
    /* check port number */
    if ( dest_port <= 0 ) {
	error( "You must specify destination port correctly.\n");
	err++;
	goto quit;
    }
    if ( (relay_method != METHOD_DIRECT) && (relay_port <= 0) ) {
	error("Invalid relay port: %d\n", dest_port);
	err++;
	goto quit;
    }

quit:
    /* report for debugging */
    debug("progname = %s\n", progname);
    debug("relay_method = %s (%d)\n",
	  method_names[relay_method], relay_method);
    if ( relay_method != METHOD_DIRECT ) {
	debug("relay_host=%s\n", relay_host);
	debug("relay_port=%d\n", relay_port);
	debug("relay_user=%s\n", relay_user);
    }
    if ( relay_method == METHOD_SOCKS ) {
	debug("socks_version=%d\n", socks_version);
	debug("socks_resolve=%s (%d)\n",
	      resolve_names[socks_resolve], socks_resolve);
    }
    debug("dest_host=%s\n", dest_host);
    debug("dest_port=%d\n", dest_port);
    if ( 0 < err )
	exit(1);
    return 0;
}


/* TODO: IPv6 */
SOCKET
open_connection(void)
{
    SOCKET s;
    char *host;
    short port;
    struct hostent *ent;
    struct in_addr iaddr;
    struct sockaddr_in saddr;

    if ( relay_method == METHOD_DIRECT ) {
	host = dest_host;
	port = dest_port;
    } else {
	host = relay_host;
	port = relay_port;
    }
    
    debug("resolving hostname: %s\n", host);
    ent = gethostbyname( host );
    if ( ent == NULL ) {
        error("can't resolve hostname: %s\n", host);
        return SOCKET_ERROR;
    }
    memcpy( &saddr.sin_addr, ent->h_addr, ent->h_length );
    saddr.sin_family = ent->h_addrtype;
    saddr.sin_port = htons(port);

    debug("connect to %s:%d\n", inet_ntoa(saddr.sin_addr), port);

    s = socket( AF_INET, SOCK_STREAM, 0 );
    if ( connect( s, (struct sockaddr *)&saddr, sizeof(saddr))
	 == SOCKET_ERROR) {
	debug( "connect() failed.\n");
	return SOCKET_ERROR;
    }
    return s;
}

void
report_text( char *prefix, char *buf )
{
    static char work[1024];
    char *tmp = work;

    if ( !f_debug )
	return;
    memset( work, 0, sizeof(work));
    while ( *buf ) {
	switch ( *buf ) {
	case '\t': *tmp++ = '\\'; *tmp++ = 't'; break;
	case '\r': *tmp++ = '\\'; *tmp++ = 'r'; break;
	case '\n': *tmp++ = '\\'; *tmp++ = 'n'; break;
	case '\\': *tmp++ = '\\'; *tmp++ = '\\'; break;
	default:
	    if ( isprint(*buf) ) {
		*tmp++ = *buf;
	    } else {
		sprintf( tmp, "\\x%02X", (unsigned char)*buf);
		tmp += strlen(tmp);
	    }
	}
	buf++;
	*tmp = '\0';
    }
    debug("%s \"%s\"\n", prefix, work);
}


void
report_bytes( char *prefix, char *buf, int len )
{
    if ( ! f_debug )
	return;
    debug( "%s", prefix );
    while ( 0 < len ) {
	fprintf( stderr, " %02x", *(unsigned char *)buf);
	buf++;
	len--;
    }
    fprintf(stderr, "\n");
    return;
}

int
atomic_out( SOCKET s, char *buf, int size )
{
    int ret, len;

    assert( buf != NULL );
    assert( 0<=size );
    /* do atomic out */
    ret = 0;
    while ( 0 < size ) {
	len = send( s, buf+ret, size, 0 );
	if ( len == -1 ) {
	    error("Fail to send(), %d\n", socket_errno());
	    return -1;
	}
	ret += len;
	size -= len;
    }
    debug("atomic_out()  [%d bytes]\n", ret);
    report_bytes(">>>", buf, ret);
    return ret;
}

int
atomic_in( SOCKET s, char *buf, int size )
{
    int ret, len;

    assert( buf != NULL );
    assert( 0<=size );
    
    /* do atomic out */
    ret = 0;
    while ( 0 < size ) {
	len = recv( s, buf+ret, size, 0 );
	if ( len == -1 ) {
	    error("Fail to send(), %d\n", socket_errno());
	    return -1;
	} else if ( len == 0 ) {
	    /* closed by peer */
	    error( "Connection closed by peer.\n");
	    return -1;				/* can't complete atomic in */
	}
	ret += len;
	size -= len;
    }
    debug("atomic_in() [%d bytes]\n", ret);
    report_bytes("<<<", buf, ret);
    return ret;
}

int
line_input( SOCKET s, char *buf, int size )
{
    int len = 0;
    if ( 0 < size ) {
	do {
	    buf += len;
	    len = recv( s, buf, 1, 0);
	    if ( len == SOCKET_ERROR )
		return -1;			/* error */
	    /* continue reading until last 1 char is EOL? */
	} while ( *buf != '\n' );		
	buf[len] = '\0';
    }
    return 0;
}

#ifdef SOCKS_ALLOW_USERPASS_AUTH
static int
socks5_do_auth_userpass( int s )
{
    unsigned char buf[1024], *ptr;
    char *pass;
    int len;
    
    /* do User/Password authentication. */
    /* This feature requires username and password from 
       command line argument or environment variable,
       or terminal. */
    if ( relay_user == NULL ) {
	error("user name was undecided.\n");
	return -1;
    }
    /* get password from environment variable if exists. */
    pass = getenv(ENV_SOCKS5_PASSWORD);
    if ( pass == NULL ) {
	error("Can't get password for user: %s\n", relay_user);
	return -1;
    }

    /* make authentication packet */
    ptr = buf;
    PUT_BYTE( ptr++, 5 );			/* subnegotiation ver.: 1 */
    len = strlen( relay_user );			/* ULEN and UNAME */
    PUT_BYTE( ptr++, len );
    strcpy( ptr, relay_user );
    ptr += len;
    len = strlen( pass );			/* PLEN and PASSWD */
    PUT_BYTE( ptr++, strlen(pass));
    strcpy( ptr, pass );
    ptr += len;
    
    /* send it and get answer */
    if ( (atomic_out( s, buf, ptr-buf ) != ptr-buf)
	 || (atomic_in( s, buf, 2 ) != 2) ) {
	error("I/O error\n");
	return -1;
    }

    /* check status */
    if ( buf[1] == 0 )
	return 0;				/* success */
    else
	return -1;				/* fail */
}
#endif /* SOCKS_ALLOW_USERPASS_AUTH */

static const char *
socks5_getauthname( int auth )
{
    switch ( auth ) {
    case SOCKS5_AUTH_REJECT: return "REJECTED";
    case SOCKS5_AUTH_NOAUTH: return "NO-AUTH";
    case SOCKS5_AUTH_GSSAPI: return "GSSAPI";
    case SOCKS5_AUTH_USERPASS: return "USERPASS";
    case SOCKS5_AUTH_CHAP: return "CHAP";
    case SOCKS5_AUTH_EAP: return "EAP";
    case SOCKS5_AUTH_MAF: return "MAF";
    default: return "(unknown)";
    }
}

/* begin SOCKS5 relaying
   And no authentication is supported.
 */
int
begin_socks5_relay( SOCKET s )
{
    unsigned char buf[256], *ptr;
    unsigned char n_auth = 0; unsigned char auth_list[10], auth_method;
    int len, auth_result;    

    debug( "begin_socks_relay()\n");
    
    /* request authentication */
    ptr = buf;
    PUT_BYTE( ptr++, 5);			/* SOCKS version (5) */
#ifdef SOCKS_ALLOW_NO_AUTH
    /* add no-auth authentication */
    auth_list[n_auth++] = 0;
#endif /* SOCKS_ALLOW_NO_AUTH */
#ifdef SOCKS_ALLOW_USERPASS_AUTH
    /* add user/pass authentication */
    auth_list[n_auth++] = 2;
#endif /* SOCKS_ALLOW_USERPASS_AUTH */
    PUT_BYTE( ptr++, n_auth);			/* num auth */
    while (0 < n_auth--)
	PUT_BYTE( ptr++, auth_list[n_auth]);	/* authentications */
    if ( (atomic_out( s, buf, ptr-buf ) < 0 ) || /* send requst */
	 (atomic_in( s, buf, 2 ) < 0) ||	/* recv response */
	 (buf[0] != 5) ||			/* ver5 response */
	 (buf[1] == 0xFF) )			/* check auth method */
	return -1;
    auth_method = buf[1];

    debug("auth method: %s\n", socks5_getauthname(auth_method));
    auth_result = -1;
    switch ( auth_method ) {
    case SOCKS5_AUTH_REJECT:
	error("No acceptable authentication method\n");
	return -1;				/* fail */
	
    case SOCKS5_AUTH_NOAUTH:
	/* nothing to do */
	auth_result = 0;
	break;
	
    case SOCKS5_AUTH_USERPASS:
#ifdef SOCKS_ALLOW_USERPASS_AUTH
	auth_result = socks5_do_auth_userpass(s);
	break;
#endif
	
    default:
	error("Unsupported authentication method: %s\n",
	      socks5_getauthname( auth_method ));
	return -1;				/* fail */
    }
    if ( auth_result != 0 ) {
	error("Authentication faield.\n");
	return -1;
    }
    /* request to connect */
    ptr = buf;
    PUT_BYTE( ptr++, 5);			/* SOCKS version (5) */
    PUT_BYTE( ptr++, 1);			/* CMD: CONNECT */
    PUT_BYTE( ptr++, 0);			/* FLG: 0 */
    if ( dest_addr.s_addr == 0 ) {
	/* resolved by SOCKS server */
	PUT_BYTE( ptr++, 3);			/* ATYP: DOMAINNAME */
	len = strlen(dest_host);
	PUT_BYTE( ptr++, len);			/* DST.ADDR (len) */
	memcpy( ptr, dest_host, len );		/* (hostname) */
	ptr += len;
    } else {
	/* resolved localy */
	PUT_BYTE( ptr++, 1 );			/* ATYP: IPv4 */
	memcpy( ptr, &dest_addr.s_addr, sizeof(dest_addr.s_addr));
	ptr += sizeof(dest_addr.s_addr);
    }
    PUT_WORD( ptr, dest_port);			/* DST.PORT */
    ptr += 2;
    if ( (atomic_out( s, buf, ptr-buf) < 0) ||	/* send request */
	 (atomic_in( s, buf, 4 ) < 0) ||	/* recv response */
	 (buf[1] != SOCKS5_REP_SUCCEEDED) )	/* check reply code */
	return -1;
    ptr = buf + 4;
    switch ( buf[3] ) {				/* case by ATYP */
    case 1:					/* IP v4 ADDR*/
	atomic_in( s, ptr, 4+2 );		/* recv IPv4 addr and port */
	break;
    case 3:					/* DOMAINNAME */
	atomic_in( s, ptr, 1 );			/* recv name and port */
	atomic_in( s, ptr+1, *(unsigned char*)ptr + 2);
	break;
    case 4:					/* IP v6 ADDR */
	atomic_in( s, ptr, 16+2 );		/* recv IPv6 addr and port */
	break;
    }
    
    /* Conguraturation, connected via http proxy server! */
    debug("connected.\n");
    return 0;
}

/* begin SOCKS protocol 4 relaying
   And no authentication is supported.

   There's SOCKS protocol version 4 and 4a. Protocol version
   4a has capability to resolve hostname by SOCKS server, so
   we don't need resolving IP address of destination host on
   local machine.

   Environment variable SOCKS_RESOLVE directs how to resolve
   IP addess. There's 3 keywords allowed; "local", "remote"
   and "both" (case insensitive). Keyword "local" means taht
   target host name is resolved by localhost resolver
   (usualy with gethostbyname()), "remote" means by remote
   SOCKS server, "both" means to try resolving by localhost
   then remote.

   SOCKS4 protocol and authentication of SOCKS5 protocol
   requires user name on connect request.
   User name is determined by following method.
   
   1. If server spec has user@hostname:port format then
      user part is used for this SOCKS server.
      
   2. Get user name from environment variable LOGNAME, USER
      (in this order).

*/
int
begin_socks4_relay( SOCKET s )
{
    unsigned char buf[256], *ptr;
    unsigned char n_auth = 0; unsigned char auth_list[10], auth_method;
    struct in_addr addr;
    int len;

    debug( "begin_socks_relay()\n");
    
    /* make connect request packet 
       protocol v4:
         VN:1, CD:1, PORT:2, ADDR:4, USER:n, NULL:1
       protocol v4a:
         VN:1, CD:1, PORT:2, DUMMY:4, USER:n, NULL:1, HOSTNAME:n, NULL:1
    */
    ptr = buf;
    PUT_BYTE( ptr++, 4);			/* protocol version (4) */
    PUT_BYTE( ptr++, 1);			/* CONNECT command */
    PUT_WORD( ptr, dest_port);			/* destination Port */
    ptr += 2;
    /* destination IP */
    memcpy(ptr, &dest_addr.s_addr, sizeof(dest_addr.s_addr));
    ptr += sizeof(dest_addr.s_addr);
    if ( dest_addr.s_addr == 0 )
	*(ptr-1) = 1;				/* fake, protocol 4a */
    /* username */
    strcpy( ptr, relay_user );
    ptr += strlen( relay_user ) +1;
    /* destination host name (for protocol 4a) */
    if ( (socks_version == 4) && (dest_addr.s_addr == 0)) {
	strcpy( ptr, dest_host );
	ptr += strlen( dest_host ) +1;
    }
    /* send command and get response
       response is: VN:1, CD:1, PORT:2, ADDR:4 */
    if ( (atomic_out( s, buf, ptr-buf) < 0) ||	/* send request */
	 (atomic_in( s, buf, 8 ) < 0) ||	/* recv response */
	 (buf[1] != SOCKS4_REP_SUCCEEDED) )	/* check reply code */
	return -1;				/* failed */
    
    /* Conguraturation, connected via http proxy server! */
    debug("connected.\n");
    return 0;
}

/* begin relaying via HTTP proxy
   Directs CONNECT method to proxy server to connect to
   destination host (and port). It may not be allowed on your
   proxy server.
 */
int
begin_http_relay( SOCKET s )
{
    char buf[1024];

    debug("begin_http_relay()\n");
    
    sprintf(buf, "CONNECT %s:%d HTTP/1.0\r\n\r\n", dest_host, dest_port);
    report_text(">>>", buf);
    if ( send(s, buf, strlen(buf), 0) == SOCKET_ERROR ) {
	debug("failed to send http requst.\n");
	return -1;
    }
    /* get response */
    if ( line_input(s, buf, sizeof(buf)) < 0 ) {
	debug("failed to read http response.\n");
	return -1;
    }
    report_text( "<<<", buf);
    /* check status */
    if ( atoi(strchr(buf,' '))/100 != 2 ) {
	/* error */
	debug("http proxy is not allowed.\n");
	return -1;
    }
    /* skip to end of response header */
    do {
	if ( line_input(s, buf, sizeof(buf) ) ) {
	    debug("Can't skip response headers\n");
	    return -1;
	}
    } while ( strcmp(buf,"\r\n") != 0 );
    
    /* Conguraturation, connected via http proxy server! */
    debug("connected, start user session.\n");
    return 0;
}


/* relay byte from stdin to socket and fro socket to stdout */
int
do_repeater( SOCKET s )
{
    char to_buf[1024], from_buf[1024];
    int nfds, len, to_buf_len, from_buf_len;
    int f_stdin, f_socket;
    fd_set *ifds, *ofds;
    struct timeval *tmo;
#ifdef _WIN32
    struct timeval win32_tmo;
#endif /* _WIN32 */

    /* repeater between stdin/out and socket  */
    nfds = s + 1;
    ifds = FD_ALLOC(nfds);
    ofds = FD_ALLOC(nfds);
    f_stdin = 1;				/* yes, read from stdin */
    f_socket = 1;				/* yes, read from socket */
    to_buf_len = 0;
    from_buf_len = 0;

    while ( f_stdin || f_socket ) {
	FD_ZERO( ifds );
	FD_ZERO( ofds );

	tmo = NULL;
#ifndef _WIN32
	debug("selecting: ");
#endif
	if ( f_stdin && (to_buf_len < sizeof(to_buf)) ) {
#ifdef _WIN32
	    win32_tmo.tv_sec = 0;
	    win32_tmo.tv_usec = 10*1000;	/* 10 ms */
	    tmo = &win32_tmo;
#else /* !_WIN32 */
	    if ( f_debug ) fprintf(stderr, " stdin" );
	    FD_SET( 0, ifds );
#endif /* !_WIN32 */
	}
	if ( f_socket && (from_buf_len < sizeof(from_buf)) ) {
#ifndef _WIN32
	    if ( f_debug ) fprintf(stderr, " socket" );
#endif /* !_WIN32 */
	    FD_SET( s, ifds );
	}
#ifndef _WIN32
	if ( f_debug ) fprintf(stderr, "\n");
#endif
	
	/* FD_SET( 1, ofds ); */
	/* FD_SET( s, ofds ); */
	
	if ( select( nfds, ifds, ofds, NULL, tmo ) == -1 ) {
	    /* some error */
	    error( "select() failed, %d\n", socket_errno());
	    return -1;
	}
#ifdef _WIN32
	/* fake ifds */
	if ( f_stdin ) {
	    DWORD len = 0;
	    struct stat st;
	    HANDLE hStdin = GetStdHandle(STD_INPUT_HANDLE);
	    fstat( 0, &st );
	    if ( st.st_mode & _S_IFIFO ) { 
		/* in case of PIPE */
		if ( !PeekNamedPipe( hStdin, NULL, 0, NULL, &len, NULL) ) {
		    if ( GetLastError() == ERROR_BROKEN_PIPE ) {
			/* PIPE source is closed */
			/* read() will detects EOF */
			len = 1;
		    } else {
			error("PeekNamedPipe(), %d\n",
				GetLastError());
			exit(3);
		    }
		}
	    } else if ( st.st_mode & _S_IFREG ) {
		/* in case of regular file (redirected) */
		len = 1;			/* always data ready */
	    } else {
		/* in case of console */
		if ( _kbhit() )
		    len = 1;
	    }
	    if ( 0 < len )
		FD_SET(0,ifds);
	}
#endif

	/* from socket : socket => stdout */
	if ( FD_ISSET(s, ifds) ) {
	    len = recv( s, from_buf + from_buf_len,
			sizeof(from_buf)-from_buf_len, 0);
	    if ( len == 0 ) {
		/* connection closed by peer */
		debug("connection closed by peer\n");
		f_socket = 0;			/* no more read from socket */
		f_stdin = 0;
	    } else if ( len == -1 ) {
		/* error */
		error("recv() faield, %d\n", socket_errno());
		exit(3);			/* I/O error */
	    } else {
		from_buf_len += len;
		len = write( 1, from_buf, from_buf_len );
		if ( len == -1 ) {
		    error("write() failed (stdout), %d\n",
			    errno);
		    exit(3);			/* I/O error */
		} 
		debug("recv %d bytes\n", len);
		assert( len == from_buf_len );
		from_buf_len = 0;
	    }
	}
	/* to socket : stdin => socket */
	if ( FD_ISSET(0, ifds) ) {
	    len = read( 0, to_buf+to_buf_len, sizeof(to_buf)-to_buf_len );
	    if ( len == 0 ) {
		/* stdin is EOF */
		debug("stdin is EOF\n");
		shutdown(s, 1);			/* no-more writing */
		f_stdin = 0;
	    } else if ( len == -1 ) {
		/* error on reading from stdin */
		error("read() failed (stdin), %d\n", errno);
		exit(3);			/* I/O error */
	    } else {
		/* repeat */
		to_buf_len += len;
		len = send( s, to_buf, to_buf_len, 0);
		to_buf[to_buf_len] = '\0';
		report_text( ">>>", to_buf);
		debug("send %d bytes\n", len);
		if ( len == -1 ) {
		    error("send() failed, %d\n",
			    socket_errno());
		    exit(3);			/* I/O error */
		} else if ( 0 < len ) {
		    /* move data on to top of buffer */
		    to_buf_len -= len;
		    if ( 0 < to_buf_len )
			memcpy( to_buf, to_buf+len, to_buf_len );
		}
	    }
	}
    }
    
    return 0;
}


/** Main of program **/
int
main( int argc, char **argv )
{
#ifdef _WIN32
    WSADATA wsadata;
#endif /* _WIN32 */
    SOCKET  s;					/* socket */

    /* initialization */
    getarg( argc, argv );

#ifdef _WIN32
    WSAStartup( 0x101, &wsadata);
#endif /* _WIN32 */
    
    /* make connection */
    s = open_connection();
    if ( s == SOCKET_ERROR ) {
	error( "failed to connect to %s:%d\n", relay_host, relay_port);
	exit(2);
    }

    /** resolve destination host **/
    if ( relay_method != METHOD_DIRECT ) {
	if ( strspn(dest_host, "0123456789.") == strlen(dest_host) ) {
	    /* given by IP address */
	    dest_addr.s_addr = inet_addr( dest_host );
	} else if ( (relay_method == METHOD_SOCKS) &&
		    ((socks_resolve == RESOLVE_LOCAL) ||
		     (socks_resolve == RESOLVE_BOTH) ||
		     (socks_resolve == RESOLVE_OTHER))) {
	    /* try to resolve on localhost */
	    struct hostent *ent;
	    debug("resolving host by name: %s\n", dest_host);
	    if (socks_resolve == RESOLVE_OTHER) {
#if defined(_WIN32) || defined(__CYGWIN32__)
		error("Why this code has run?");
		exit(1);
#else /* !_WIN32 && !__CYGWIN32__ */
		res_init();
		_res.nsaddr_list[0].sin_addr.s_addr = socks_ns;
		_res.nscount = 1;
#endif /* !_WIN32 && !__CYGWIN32__ */
	    }
	    ent = gethostbyname( dest_host );
	    if ( ent ) {
		memcpy( &(dest_addr.s_addr), ent->h_addr, sizeof(dest_addr));
		debug("hostname resolved: %s (%s)\n",
		      dest_host, inet_ntoa(dest_addr));
	    } else if ( socks_resolve == RESOLVE_LOCAL ) {
		error("Failed to resolve destination host: %s\n", dest_host);
		exit(3);
	    } else {
		debug("resolve failed, try remote resolving.\n");
	    }
	}
    }
    
    /** relay negociation **/
    switch ( relay_method ) {
    case METHOD_SOCKS:
	if ( ((socks_version == 5) && (begin_socks5_relay(s) < 0)) ||
	     ((socks_version == 4) && (begin_socks4_relay(s) < 0)) ) {
	    error( "failed to begin relaying via SOCKS.\n");
	    exit(3);
	}
	break;
	
    case METHOD_HTTP:
	if ( begin_http_relay(s) < 0 ) {
	    error("failed to begin relaying via HTTP.\n");
	    exit(3);
	}
	break;
    }
#ifdef _WIN32
    _setmode(0, O_BINARY);
    _setmode(1, O_BINARY);
#endif

    /* main loop */
    do_repeater(s);
    closesocket(s);
#ifdef _WIN32
    WSACleanup();
#endif /* _WIN32 */
    
    return 0;
}

/* ------------------------------------------------------------
   Local Variables:
   compile-command: "cc socks-tunnel.c -o socks-tunnel"
   fill-column: 60
   comment-column: 48
   End:
   ------------------------------------------------------------ */

/*** end of connect.c ***/
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
