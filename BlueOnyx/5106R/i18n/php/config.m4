shared=no
AC_MSG_CHECKING(whether to include i18n support)
AC_ARG_WITH(i18n,
[  --without-i18n          Disable i18n support.
  --with-i18n             Include i18n support.],
[
  PHP_WITH_SHARED
  case "$withval" in
    no)
      AC_MSG_RESULT(no)
    ;;
    *)
      PHP_EXTENSION(i18n, yes)
      AC_ADD_INCLUDE(/usr/sausalito/include)
      AC_ADD_LIBRARY_WITH_PATH(i18n, /usr/sausalito/lib)
      PHP_FAST_OUTPUT(${ext_base}i18n/Makefile)
    ;;
  esac
dnl A whole whack of possible places where this might be
])
