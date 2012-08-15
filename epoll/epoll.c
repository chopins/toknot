/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2012 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author:                                                              |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_streams.h"
#include "php_network.h"
#include "php_epoll.h"

#ifndef FD_SETSIZE
#define FD_SETSIZE 512
#endif

#if PHP_VERSION_ID >= 50301 && (HAVE_SOCKETS || defined(COMPILE_DL_SOCKETS))
# include "ext/sockets/php_sockets.h"
# define LIBEVENT_SOCKETS_SUPPORT
#endif

#ifndef ZEND_FETCH_RESOURCE_NO_RETURN 
# define ZEND_FETCH_RESOURCE_NO_RETURN(rsrc, rsrc_type, passed_id, default_id, resource_type_name, resource_type) \
	(rsrc = (rsrc_type) zend_fetch_resource(passed_id TSRMLS_CC, default_id, resource_type_name, NULL, 1, resource_type))
#endif

#include <sys/epoll.h>

#ifndef DONT_HAVE_SYS_TYPES_H
#include <sys/types.h>
#endif

#ifdef COMPILE_DL_LIBEVENT
ZEND_GET_MODULE(epoll)
#endif


/* If you declare any globals in php_epoll.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(epoll)
*/

/* True global resources - no need for thread safety here */
static int le_epoll;
typedef union _php_epoll_data {
	zval  * func;
	int		fd;
	uint32_t u32;
	uint64_t u64;
} php_epoll_data_t;

typedef struct _php_epoll_event {
	uint32_t	events;
	php_epoll_data_t  data;
} php_epoll_event;


/* {{{ arginfo */
ZEND_BEGIN_ARG_INFO_EX(arginfo_epoll_create, 0, ZEND_RETURN_VALUE, 1)
	ZEND_ARG_INFO(0, size)
ZEND_END_ARG_INFO()


ZEND_BEGIN_ARG_INFO_EX(arginfo_epoll_ctl, 0, ZEND_RETURN_VALUE, 5)
	ZEND_ARG_INFO(0, epollfd)
	ZEND_ARG_INFO(0, op)
	ZEND_ARG_INFO(0, fd)
	ZEND_ARG_INFO(0, epoll_event)
	ZEND_ARG_INFO(0, callback)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_epoll_wait, 0, ZEND_RETURN_VALUE, 4)
	ZEND_ARG_INFO(0, epollfd)
	ZEND_ARG_INFO(0, epoll_event)
	ZEND_ARG_INFO(0, maxevents)
	ZEND_ARG_INFO(0, timeout)
ZEND_END_ARG_INFO()
/* }}} */


/* {{{ epoll_functions[]
 *
 * Every user visible function must have an entry in epoll_functions[].
 */
const zend_function_entry epoll_functions[] = {

	PHP_FE(epoll_create,   arginfo_epoll_create)
	PHP_FE(epoll_ctl,      arginfo_epoll_ctl)
	PHP_FE(epoll_wait,     arginfo_epoll_wait)
	{NULL, NULL, NULL}	/* Must be the last line in inotify_functions[] */
};
/* }}} */

/* {{{ epoll_module_entry
 */
zend_module_entry epoll_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"epoll",
	epoll_functions,
	PHP_MINIT(epoll),
	PHP_MSHUTDOWN(epoll),
	PHP_RINIT(epoll),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(epoll),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(epoll),
#if ZEND_MODULE_API_NO >= 20010901
	"0.1", /* Replace with version number for your extension */
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */




#ifdef COMPILE_DL_EPOLL
ZEND_GET_MODULE(epoll)
#endif
/* {{{proto resource epoll_create(int size) 
   open an epoll file descriptor*/
PHP_FUNCTION(epoll_create)
{
	int epollfd;
    long size;
    php_stream *stream;
	epollfd = epoll_create(size);
	if(epollfd == -1) {
		switch(errno) {
			EPOLL_ERROR_CASE(CREATE,EMFILE);
			EPOLL_ERROR_CASE(CREATE,ENFILE);
			EPOLL_ERROR_CASE(CREATE,ENOMEM);
			EPOLL_ERROR_CASE(CREATE,EINVAL);
			EPOLL_DEFAULT_ERROR(errno);
		}

		RETURN_FALSE;
	}
    stream = php_stream_fopen_from_fd(epollfd, "r", NULL);
	stream->flags |= PHP_STREAM_FLAG_NO_SEEK;

	php_stream_to_zval(stream, return_value);
}
/* }}} */

/* {{{proto epoll_add_event(resource fd, int events)
 */
PHP_FUNCTION(epoll_add_event) 
{
}

/* {{{proto epoll_ctl(resource epfd, int op,  resource events)
 */
PHP_FUNCTION(epoll_ctl)
{
	zval *fd, *zcallback, *zepollfd;
    long op, events;
    int ret, file_desc, epollfd;
    php_stream *stream, *epoll_stream;
    epoll_event  *epoll_event;
	char *func_name;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rlrz|z", &zepollfd, &op, &fd, &events,&zcallback) != SUCCESS) {
		return;
	}


	php_stream_from_zval(stream, &fd);
	EPOLL_FD(stream, file_desc);

    php_stream_from_zval(epoll_stream, &zepollfd);
    EPOLL_FD(epoll_stream, epollfd);

    if(zcallback) {
        if (!zend_is_callable(zcallback, 0, &func_name TSRMLS_CC)) {
            php_error_docref(NULL TSRMLS_CC, E_WARNING, "'%s' is not a valid callback", func_name);
            efree(func_name);
            RETURN_FALSE;
        }
        epoll_event.data.func = func_name;
        efree(func_name);
    }

    epoll_event.data.fd = file_desc;
    epoll_event.events = events;
    ret = epoll_ctl(epollfd, op, file_desc, epoll_event);
    if(ret == -1) {
		switch(errno) {
			EPOLL_ERROR_CASE(CREATE,EBADF);
			EPOLL_ERROR_CASE(CREATE,EEXIST);
			EPOLL_ERROR_CASE(CREATE,ENOENT);
			EPOLL_ERROR_CASE(CREATE,EINVAL);
			EPOLL_ERROR_CASE(CREATE,ENOMEM);
			EPOLL_ERROR_CASE(CREATE,ENOSPC);
			EPOLL_ERROR_CASE(CREATE,EPERM);
			EPOLL_DEFAULT_ERROR(errno);
		}

		RETURN_FALSE;

    }
    RETURN_LONG(ret);
}
/* }}} */

/* {{{proto epoll_wait(resource epollfd, mixed &epoll_event, int maxevents, int timeout)
 */
PHP_FUNCTION(epoll_wait)
{
	zval *z_events = NULL, **fd, zepollfd;
    long epollfd,maxevents, timeout;
    php_epoll_event  epoll_event;
    php_stream epoll_stream;
    php_event_callback_t *callback,
	char *func_name;

    int ret;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rZll", &epollfd, &events,&maxevents,&timeout) != SUCCESS) {
		return;
	}
    if(maxevents <0 ) {
        php_error_docref(NULL TSRMLS_CC, E_WARNING, "maxevents must be greater than zero");
		RETURN_FALSE;
    }
    php_stream_from_zval(epoll_stream, zepollfd);
    EPOLL_FD(epoll_stream, epollfd);

    ret = epoll_wait(epollfd, epoll_event, maxevents, timeout);
    ALLOC_INIT_ZVAL(z_events);
	array_init(z_events);
    add_assoc_resource(z_events, "fd", epoll_event.data.fd);
    add_assoc_long(z_events, "events", epoll_event.events);
    add_assoc_string(z_events,"callback", epoll_event.data.func);
    if(ret == -1) {
		switch(errno) {
			EPOLL_ERROR_CASE(CREATE,EBADF);
			EPOLL_ERROR_CASE(CREATE,EFAULT);
			EPOLL_ERROR_CASE(CREATE,EINTR);
			EPOLL_ERROR_CASE(CREATE,EINVAL);
			EPOLL_DEFAULT_ERROR(errno);
		}

		RETURN_FALSE;
	}
    RETURN_LONG(ret);
}
/* }}} */

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("epoll.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_epoll_globals, epoll_globals)
    STD_PHP_INI_ENTRY("epoll.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_epoll_globals, epoll_globals)
PHP_INI_END()
*/
/* }}} */

/* {{{ php_epoll_init_globals
 */
/* Uncomment this function if you have INI entries
static void php_epoll_init_globals(zend_epoll_globals *epoll_globals)
{
	epoll_globals->global_value = 0;
	epoll_globals->global_string = NULL;
}
*/
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(epoll)
{
	REGISTER_LONG_CONSTANT("EPOLL_CTL_ADD", EPOLL_CTL_ADD, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("EPOLL_CTL_MOD", EPOLL_CTL_MOD, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("EPOLL_CTL_DEL", EPOLL_CTL_DEL, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("EPOLL_IN", EPOLLIN, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("EPOLL_OUT", EPOLLOUT, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("EPOLL_ERR", EPOLLERR, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("EPOLL_HUP", EPOLLHUP, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("EPOLL_RDHUP", EPOLLRDHUP, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("EPOLL_PRI", EPOLLPRI, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("EPOLL_ET", EPOLLET, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("EPOLL_ONESHOT", EPOLLONESHOT, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("EPOLL_ONESHOT", EPOLLONESHOT, CONST_CS | CONST_PERSISTENT);
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(epoll)
{
	/* uncomment this line if you have INI entries
	UNREGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request start */
/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(epoll)
{
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(epoll)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(epoll)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "epoll support", "enabled");
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	DISPLAY_INI_ENTRIES();
	*/
}
/* }}} */


