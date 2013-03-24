dnl $Id$
dnl config.m4 for extension pthreads


PHP_ARG_WITH(pthreads, for pthreads support,
[  --with-pthreads             Include pthreads support])


if test "$PHP_PTHREADS" != "no"; then
    AC_CHECK_FUNCS(pthread_create, [AC_DEFINE(HAVE_PTHREAD_CREATE, 1, [])],[AC_MSG_ERROR(pthreads:epoll_create not supported by this platform)])
    PHP_NEW_EXTENSION(pthreads, pthreads.c, $ext_shared)
fi
