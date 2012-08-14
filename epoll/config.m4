dnl $Id$
dnl config.m4 for extension epoll

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

dnl PHP_ARG_WITH(epoll, for epoll support,
dnl Make sure that the comment is aligned:
dnl [  --with-epoll             Include epoll support])

dnl Otherwise use enable:

dnl PHP_ARG_ENABLE(epoll, whether to enable epoll support,
dnl Make sure that the comment is aligned:
dnl [  --enable-epoll           Enable epoll support])

if test "$PHP_EPOLL" != "no"; then
  dnl Write more examples of tests here...

  dnl # --with-epoll -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/epoll.h"  # you most likely want to change this
  dnl if test -r $PHP_EPOLL/$SEARCH_FOR; then # path given as parameter
  dnl   EPOLL_DIR=$PHP_EPOLL
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for epoll files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       EPOLL_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$EPOLL_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the epoll distribution])
  dnl fi

  dnl # --with-epoll -> add include path
  dnl PHP_ADD_INCLUDE($EPOLL_DIR/include)

  dnl # --with-epoll -> check for lib and symbol presence
  dnl LIBNAME=epoll # you may want to change this
  dnl LIBSYMBOL=epoll # you most likely want to change this 

  dnl PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  dnl [
  dnl   PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $EPOLL_DIR/lib, EPOLL_SHARED_LIBADD)
  dnl   AC_DEFINE(HAVE_EPOLLLIB,1,[ ])
  dnl ],[
  dnl   AC_MSG_ERROR([wrong epoll lib version or lib not found])
  dnl ],[
  dnl   -L$EPOLL_DIR/lib -lm
  dnl ])
  dnl
  dnl PHP_SUBST(EPOLL_SHARED_LIBADD)

  PHP_NEW_EXTENSION(epoll, epoll.c, $ext_shared)
fi
