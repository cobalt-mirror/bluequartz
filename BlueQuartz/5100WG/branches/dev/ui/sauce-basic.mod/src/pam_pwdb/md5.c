/* $Id: md5.c 201 2003-07-18 19:11:07Z will $
 *
 * This code implements the MD5 message-digest algorithm.
 * The algorithm is due to Ron Rivest.  This code was
 * written by Colin Plumb in 1993, no copyright is claimed.
 * This code is in the public domain; do with it what you wish.
 *
 * Equivalent code is available from RSA Data Security, Inc.
 * This code has been tested against that, and is equivalent,
 * except that you don't need to include two pages of legalese
 * with every copy.
 *
 * To compute the message digest of a chunk of bytes, declare an
 * MD5Context structure, pass it to MD5Init, call MD5Update as
 * needed on buffers full of bytes, and then call MD5Final, which
 * will fill a supplied 16-byte array with the digest.
 *
 * $Log$
 * Revision 1.1.1.1.2.1  2003/07/18 19:11:06  will
 * dev "menlo" branch patch, it's huge
 *
 * Revision 1.1  2000/07/14 11:03:10  jmayer
 * Drat, pam does more than a simple MD5 hash.  In fact, the MD5 algorithm
 * implemented here doesn't even seem to be compatible with Perl's
 * Digest::MD5 implementation.
 *
 * Seems the only way to get the md5 hash correct is to use PAM's own code,
 * so here it is.  These source files can be used to build a "pw_to_md5"
 * utility, which perl can invoke.
 *
 * Revision 1.2  1999/07/04 23:22:38  morgan
 * Andrey's MD5 (bigendian) work around + cleanup to address problems with
 * applications that let an (ab)user kill them off without giving PAM the
 * opportunity to end. [Problem report from Tani Hosokawa on bugtraq.]
 *
 * Revision 1.1.1.1  1998/07/12 05:17:17  morgan
 * Linux PAM sources pre-0.66
 *
 * Revision 1.1  1996/09/05 06:43:31  morgan
 * Initial revision
 *
 */

#include <string.h>
#include "md5.h"

#ifndef HIGHFIRST
#define byteReverse(buf, len)	/* Nothing */
#else
static void byteReverse(unsigned char *buf, unsigned longs);

#ifndef ASM_MD5
/*
 * Note: this code is harmless on little-endian machines.
 */
static void byteReverse(unsigned char *buf, unsigned longs)
{
    uint32 t;
    do {
	t = (uint32) ((unsigned) buf[3] << 8 | buf[2]) << 16 |
	    ((unsigned) buf[1] << 8 | buf[0]);
	*(uint32 *) buf = t;
	buf += 4;
    } while (--longs);
}
#endif
#endif

/*
 * Start MD5 accumulation.  Set bit count to 0 and buffer to mysterious
 * initialization constants.
 */
void MD5Name(MD5Init)(struct MD5Context *ctx)
{
    ctx->buf[0] = 0x67452301U;
    ctx->buf[1] = 0xefcdab89U;
    ctx->buf[2] = 0x98badcfeU;
    ctx->buf[3] = 0x10325476U;

    ctx->bits[0] = 0;
    ctx->bits[1] = 0;
}

/*
 * Update context to reflect the concatenation of another buffer full
 * of bytes.
 */
void MD5Name(MD5Update)(struct MD5Context *ctx, unsigned const char *buf, unsigned len)
{
    uint32 t;

    /* Update bitcount */

    t = ctx->bits[0];
    if ((ctx->bits[0] = t + ((uint32) len << 3)) < t)
	ctx->bits[1]++;		/* Carry from low to high */
    ctx->bits[1] += len >> 29;

    t = (t >> 3) & 0x3f;	/* Bytes already in shsInfo->data */

    /* Handle any leading odd-sized chunks */

    if (t) {
	unsigned char *p = (unsigned char *) ctx->in + t;

	t = 64 - t;
	if (len < t) {
	    memcpy(p, buf, len);
	    return;
	}
	memcpy(p, buf, t);
	byteReverse(ctx->in, 16);
	MD5Name(MD5Transform)(ctx->buf, (uint32 *) ctx->in);
	buf += t;
	len -= t;
    }
    /* Process data in 64-byte chunks */

    while (len >= 64) {
	memcpy(ctx->in, buf, 64);
	byteReverse(ctx->in, 16);
	MD5Name(MD5Transform)(ctx->buf, (uint32 *) ctx->in);
	buf += 64;
	len -= 64;
    }

    /* Handle any remaining bytes of data. */

    memcpy(ctx->in, buf, len);
}

/*
 * Final wrapup - pad to 64-byte boundary with the bit pattern 
 * 1 0* (64-bit count of bits processed, MSB-first)
 */
void MD5Name(MD5Final)(unsigned char digest[16], struct MD5Context *ctx)
{
    unsigned count;
    unsigned char *p;

    /* Compute number of bytes mod 64 */
    count = (ctx->bits[0] >> 3) & 0x3F;

    /* Set the first char of padding to 0x80.  This is safe since there is
       always at least one byte free */
    p = ctx->in + count;
    *p++ = 0x80;

    /* Bytes of padding needed to make 64 bytes */
    count = 64 - 1 - count;

    /* Pad out to 56 mod 64 */
    if (count < 8) {
	/* Two lots of padding:  Pad the first block to 64 bytes */
	memset(p, 0, count);
	byteReverse(ctx->in, 16);
	MD5Name(MD5Transform)(ctx->buf, (uint32 *) ctx->in);

	/* Now fill the next block with 56 bytes */
	memset(ctx->in, 0, 56);
    } else {
	/* Pad block to 56 bytes */
	memset(p, 0, count - 8);
    }
    byteReverse(ctx->in, 14);

    /* Append length in bits and transform */
    ((uint32 *) ctx->in)[14] = ctx->bits[0];
    ((uint32 *) ctx->in)[15] = ctx->bits[1];

    MD5Name(MD5Transform)(ctx->buf, (uint32 *) ctx->in);
    byteReverse((unsigned char *) ctx->buf, 4);
    memcpy(digest, ctx->buf, 16);
    memset(ctx, 0, sizeof(ctx));	/* In case it's sensitive */
}

#ifndef ASM_MD5

/* The four core functions - F1 is optimized somewhat */

/* #define F1(x, y, z) (x & y | ~x & z) */
#define F1(x, y, z) (z ^ (x & (y ^ z)))
#define F2(x, y, z) F1(z, x, y)
#define F3(x, y, z) (x ^ y ^ z)
#define F4(x, y, z) (y ^ (x | ~z))

/* This is the central step in the MD5 algorithm. */
#define MD5STEP(f, w, x, y, z, data, s) \
	( w += f(x, y, z) + data,  w = w<<s | w>>(32-s),  w += x )

/*
 * The core of the MD5 algorithm, this alters an existing MD5 hash to
 * reflect the addition of 16 longwords of new data.  MD5Update blocks
 * the data and converts bytes into longwords for this routine.
 */
void MD5Name(MD5Transform)(uint32 buf[4], uint32 const in[16])
{
    register uint32 a, b, c, d;

    a = buf[0];
    b = buf[1];
    c = buf[2];
    d = buf[3];

    MD5STEP(F1, a, b, c, d,  in[0] + 0xd76aa478U,  7);
    MD5STEP(F1, d, a, b, c,  in[1] + 0xe8c7b756U, 12);
    MD5STEP(F1, c, d, a, b,  in[2] + 0x242070dbU, 17);
    MD5STEP(F1, b, c, d, a,  in[3] + 0xc1bdceeeU, 22);
    MD5STEP(F1, a, b, c, d,  in[4] + 0xf57c0fafU,  7);
    MD5STEP(F1, d, a, b, c,  in[5] + 0x4787c62aU, 12);
    MD5STEP(F1, c, d, a, b,  in[6] + 0xa8304613U, 17);
    MD5STEP(F1, b, c, d, a,  in[7] + 0xfd469501U, 22);
    MD5STEP(F1, a, b, c, d,  in[8] + 0x698098d8U,  7);
    MD5STEP(F1, d, a, b, c,  in[9] + 0x8b44f7afU, 12);
    MD5STEP(F1, c, d, a, b, in[10] + 0xffff5bb1U, 17);
    MD5STEP(F1, b, c, d, a, in[11] + 0x895cd7beU, 22);
    MD5STEP(F1, a, b, c, d, in[12] + 0x6b901122U,  7);
    MD5STEP(F1, d, a, b, c, in[13] + 0xfd987193U, 12);
    MD5STEP(F1, c, d, a, b, in[14] + 0xa679438eU, 17);
    MD5STEP(F1, b, c, d, a, in[15] + 0x49b40821U, 22);

    MD5STEP(F2, a, b, c, d,  in[1] + 0xf61e2562U,  5);
    MD5STEP(F2, d, a, b, c,  in[6] + 0xc040b340U,  9);
    MD5STEP(F2, c, d, a, b, in[11] + 0x265e5a51U, 14);
    MD5STEP(F2, b, c, d, a,  in[0] + 0xe9b6c7aaU, 20);
    MD5STEP(F2, a, b, c, d,  in[5] + 0xd62f105dU,  5);
    MD5STEP(F2, d, a, b, c, in[10] + 0x02441453U,  9);
    MD5STEP(F2, c, d, a, b, in[15] + 0xd8a1e681U, 14);
    MD5STEP(F2, b, c, d, a,  in[4] + 0xe7d3fbc8U, 20);
    MD5STEP(F2, a, b, c, d,  in[9] + 0x21e1cde6U,  5);
    MD5STEP(F2, d, a, b, c, in[14] + 0xc33707d6U,  9);
    MD5STEP(F2, c, d, a, b,  in[3] + 0xf4d50d87U, 14);
    MD5STEP(F2, b, c, d, a,  in[8] + 0x455a14edU, 20);
    MD5STEP(F2, a, b, c, d, in[13] + 0xa9e3e905U,  5);
    MD5STEP(F2, d, a, b, c,  in[2] + 0xfcefa3f8U,  9);
    MD5STEP(F2, c, d, a, b,  in[7] + 0x676f02d9U, 14);
    MD5STEP(F2, b, c, d, a, in[12] + 0x8d2a4c8aU, 20);

    MD5STEP(F3, a, b, c, d,  in[5] + 0xfffa3942U,  4);
    MD5STEP(F3, d, a, b, c,  in[8] + 0x8771f681U, 11);
    MD5STEP(F3, c, d, a, b, in[11] + 0x6d9d6122U, 16);
    MD5STEP(F3, b, c, d, a, in[14] + 0xfde5380cU, 23);
    MD5STEP(F3, a, b, c, d,  in[1] + 0xa4beea44U,  4);
    MD5STEP(F3, d, a, b, c,  in[4] + 0x4bdecfa9U, 11);
    MD5STEP(F3, c, d, a, b,  in[7] + 0xf6bb4b60U, 16);
    MD5STEP(F3, b, c, d, a, in[10] + 0xbebfbc70U, 23);
    MD5STEP(F3, a, b, c, d, in[13] + 0x289b7ec6U,  4);
    MD5STEP(F3, d, a, b, c,  in[0] + 0xeaa127faU, 11);
    MD5STEP(F3, c, d, a, b,  in[3] + 0xd4ef3085U, 16);
    MD5STEP(F3, b, c, d, a,  in[6] + 0x04881d05U, 23);
    MD5STEP(F3, a, b, c, d,  in[9] + 0xd9d4d039U,  4);
    MD5STEP(F3, d, a, b, c, in[12] + 0xe6db99e5U, 11);
    MD5STEP(F3, c, d, a, b, in[15] + 0x1fa27cf8U, 16);
    MD5STEP(F3, b, c, d, a,  in[2] + 0xc4ac5665U, 23);

    MD5STEP(F4, a, b, c, d,  in[0] + 0xf4292244U,  6);
    MD5STEP(F4, d, a, b, c,  in[7] + 0x432aff97U, 10);
    MD5STEP(F4, c, d, a, b, in[14] + 0xab9423a7U, 15);
    MD5STEP(F4, b, c, d, a,  in[5] + 0xfc93a039U, 21);
    MD5STEP(F4, a, b, c, d, in[12] + 0x655b59c3U,  6);
    MD5STEP(F4, d, a, b, c,  in[3] + 0x8f0ccc92U, 10);
    MD5STEP(F4, c, d, a, b, in[10] + 0xffeff47dU, 15);
    MD5STEP(F4, b, c, d, a,  in[1] + 0x85845dd1U, 21);
    MD5STEP(F4, a, b, c, d,  in[8] + 0x6fa87e4fU,  6);
    MD5STEP(F4, d, a, b, c, in[15] + 0xfe2ce6e0U, 10);
    MD5STEP(F4, c, d, a, b,  in[6] + 0xa3014314U, 15);
    MD5STEP(F4, b, c, d, a, in[13] + 0x4e0811a1U, 21);
    MD5STEP(F4, a, b, c, d,  in[4] + 0xf7537e82U,  6);
    MD5STEP(F4, d, a, b, c, in[11] + 0xbd3af235U, 10);
    MD5STEP(F4, c, d, a, b,  in[2] + 0x2ad7d2bbU, 15);
    MD5STEP(F4, b, c, d, a,  in[9] + 0xeb86d391U, 21);

    buf[0] += a;
    buf[1] += b;
    buf[2] += c;
    buf[3] += d;
}

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
