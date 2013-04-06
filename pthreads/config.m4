dnl $Id$
dnl config.m4 for extension pthreads


PHP_ARG_ENABLE(pthreads, for pthreads support,
[  --enable-pthreads             Include pthreads support])


if test "$PHP_PTHREADS" != "no"; then
    LIBNAME=pthread
    LIBSYMBOL=pthread
    PTHREAD_LIBS="-lpthread"
    PHP_ADD_LIBRARY_WITH_PATH(pthread, "", PTHREADS_SHARED_LIBADD)
    EXTRA_LIBS="$EXTRA_LIBS $PTHREAD_LIBS"
    PHP_NEW_EXTENSION(pthreads, pthreads.c, $ext_shared)
    PHP_SUBST(PTHREAD_LIBS)
fi
