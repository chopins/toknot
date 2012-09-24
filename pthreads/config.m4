dnl $Id$
dnl config.m4 for extension pthreads

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

dnl PHP_ARG_WITH(pthreads, for pthreads support,
dnl Make sure that the comment is aligned:
dnl [  --with-pthreads             Include pthreads support])

dnl Otherwise use enable:

dnl PHP_ARG_ENABLE(pthreads, whether to enable pthreads support,
dnl Make sure that the comment is aligned:
dnl [  --enable-pthreads           Enable pthreads support])

if test "$PHP_PTHREADS" != "no"; then
  dnl Write more examples of tests here...

  dnl # --with-pthreads -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/pthreads.h"  # you most likely want to change this
  dnl if test -r $PHP_PTHREADS/$SEARCH_FOR; then # path given as parameter
  dnl   PTHREADS_DIR=$PHP_PTHREADS
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for pthreads files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       PTHREADS_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$PTHREADS_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the pthreads distribution])
  dnl fi

  dnl # --with-pthreads -> add include path
  dnl PHP_ADD_INCLUDE($PTHREADS_DIR/include)

  dnl # --with-pthreads -> check for lib and symbol presence
  dnl LIBNAME=pthreads # you may want to change this
  dnl LIBSYMBOL=pthreads # you most likely want to change this 

  dnl PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  dnl [
  dnl   PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $PTHREADS_DIR/lib, PTHREADS_SHARED_LIBADD)
  dnl   AC_DEFINE(HAVE_PTHREADSLIB,1,[ ])
  dnl ],[
  dnl   AC_MSG_ERROR([wrong pthreads lib version or lib not found])
  dnl ],[
  dnl   -L$PTHREADS_DIR/lib -lm
  dnl ])
  dnl
  dnl PHP_SUBST(PTHREADS_SHARED_LIBADD)

  PHP_NEW_EXTENSION(pthreads, pthreads.c, $ext_shared)
fi
