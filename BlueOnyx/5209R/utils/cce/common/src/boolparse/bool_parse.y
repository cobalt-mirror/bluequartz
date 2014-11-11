/* $Id: bool_parse.y,v 1.2 2001/08/10 22:23:16 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
%{

#include <ctype.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include "bool_parse.h"

static void yyerror(char *);
static struct bool_node *bool_node_alloc(void);

#define BOOL_DBG_DEFAULT 0
static struct bool_node *root = NULL;
static int booldbg = BOOL_DBG_DEFAULT;

#define YYERROR_VERBOSE 1
#define BPRINTF(f, a...) do { if (booldbg) fprintf(stderr, f, ##a); } while(0)
%}

%union {
	char *str;
	struct bool_node *bool;
}

%token LPAREN
%token RPAREN
%token NOT
%token <str> RULE

%type <bool> BooleanString
%type <bool> BoolExpr
%type <bool> PrimExpr
%type <bool> UnaryExpr
%type <bool> Expr
%type <bool> BinaryExpr

%left OR AND
%nonassoc NOT

%%
BooleanString:
	BoolExpr { root = $1; $$ = $1; }
	| { $$ = NULL;} /* Empty */
	;

BoolExpr:
	BinaryExpr
	| UnaryExpr
	;

BinaryExpr:
	BoolExpr AND BoolExpr {
		struct bool_node *p;
		
		BPRINTF("AND: assigning %s to left, and %s to right\n", 
		  $1->data, $3->data);
		p = bool_node_alloc();
		if (!p) {
			fprintf(stderr, "bool_node_alloc() failed\n");
			exit(1);
		}
		p->left = $1;
		p->op = BOOL_AND;
		p->right = $3;
		$$ = p;
	}
	| BoolExpr OR BoolExpr {
		struct bool_node *p;
		
		BPRINTF("OR: assigning %s to left, and %s to right\n", 
		  $1->data, $3->data);
		p = bool_node_alloc();
		if (!p) {
			fprintf(stderr, "bool_node_alloc() failed\n");
			exit(1);
		}
		p->left = $1;
		p->op = BOOL_OR;
		p->right = $3;
		$$ = p;
	}
	;

UnaryExpr:
	NOT UnaryExpr { $$ = $2; $$->not ^= 1; BPRINTF("NOT'ing it\n"); }
	| PrimExpr
	;

PrimExpr:
	LPAREN BoolExpr RPAREN { $$ = $2; }
	| Expr 
	;

Expr:
	RULE {
		struct bool_node *p;
		
		BPRINTF("got an expression\n");
		p = bool_node_alloc();
		if (!p) {
			fprintf(stderr, "bool_node_alloc() failed\n");
			exit(1);
		}
		p->data = strdup($1);
		p->op = BOOL_EXPR;
		$$ = p;
	}
	;

%%

static void
yyerror(char *s)
{
}

static struct bool_node *
bool_node_alloc(void)
{
	struct bool_node *node;

	node = malloc(sizeof(*node));
	if (!node) {
		return NULL;
	}
	node->left = node->right = NULL;
	node->op = BOOL_NONE;
	node->data = NULL;
	node->not = 0;

	return node;
}
	
/* exported */
struct bool_node *
bool_parse(void)
{
	yyparse();
	return root;
}

/* exported */
char *
bool_op(enum bool_op op)
{
	static char *opstr[] = {
		"<none>",
		"<expr>",
		"OR",
		"AND"
	};

	if (op < 0 || op > BOOL_OP_MAX) {
		return NULL;
	} else {
		return opstr[op];
	}
}

