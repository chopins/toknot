dnl $Id$
dnl config.m4 for extension epoll

PHP_ARG_ENABLE(epoll, whether to enable epoll support,
 --enable-epoll           Enable epoll support])

if test "$PHP_EPOLL" != "no"; then
    AC_CHECK_FUNCS(epoll_create, [AC_DEFINE(HAVE_EPOLL_CREATE, 1, [])],[AC_MSG_ERROR(epoll:epoll_create not supported by this platform)])
    PHP_NEW_EXTENSION(epoll, epoll.c, $ext_shared, cli)
fi
