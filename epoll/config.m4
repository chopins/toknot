dnl $Id$
dnl config.m4 for extension epoll

PHP_ARG_ENABLE(epoll, whether to enable epoll support,
 --enable-epoll           Enable epoll support])

if test "$PHP_EPOLL" != "no"; then

    AC_TRY_RUN([
        #include <sys/epoll.h>
        void testfunc(int (*passedfunc)()) {
        }
        int main() {
            testfunc(epoll_create);
            return 0;
        }
        ],[],[
        AC_MSG_ERROR(Your system does not support inotify)
    ])



  PHP_NEW_EXTENSION(epoll, epoll.c, $ext_shared)
fi
