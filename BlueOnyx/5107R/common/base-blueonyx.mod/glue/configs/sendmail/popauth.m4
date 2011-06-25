divert(0)

# Pop-before-smtp secondary access hash
Kpopauth hash -a<MATCH> /etc/mail/popip.db

LOCAL_RULESETS

SLocal_check_rcpt
# Put the address into cannonical form (even if it doesn't resolve to an MX).
R$*			$: $>Parse0 03 $1
R$* < $* > $*		$: $1 < $2 . > $3
R$* < $* . . > $*	$1 < $2 . > $3
# Test against pop-before-relay hash
R$*			$: < $&{client_addr} >
R< $* >			$(popauth $1 $)
R$* < MATCH >		$#OK

