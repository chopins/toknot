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

#if PHP_MAJOR_VERSION < 5
# ifdef PHP_WIN32
typedef SOCKET php_socket_t;
# else
typedef int php_socket_t;
# endif

#ifdef COMPILE_DL_LIBEVENT
ZEND_GET_MODULE(epoll)
#endif


/* If you declare any globals in php_epoll.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(epoll)
*/

/* True global resources - no need for thread safety here */
static int le_epoll;
typedef struct _php_epoll_data {
	zval  * func;
	int		fd;
	uint32_t u32;
	uint64_t u64;
} php_epoll_data_t

typedef struct _php_epoll_event {
	uint32_t	events;
	php_epoll_data_t  data;
} php_epoll_event

/* {{{ epoll_functions[]
 *
 * Every user visible function must have an entry in epoll_functions[].
 */
const zend_function_entry epoll_functions[] = {
	PHP_FE(epoll_create,   arginfo_epoll_create)
	PHP_FE(epoll_ctl,      arginfo_epoll_ctl)
	PHP_FE(epoll_wait,     arginfo_epoll_wait)
	{NULL, NULL, NULL}
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

static PHP_FUNCTION(epoll_create)
{
	zval **max_event;
	int epollfd;
	php_streams *stream;
	epollfd = epoll_create(10);
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
	stream = php_stream_fopen_from_fd(fd, "r", NULL);
	stream->flags |= PHP_STREAM_FLAG_NO_SEEK;
	php_stream_to_zval(stream, return_value);
}

static PHP_FUNCTION(epoll_ctl)
{
	zval *zstream, **fd, *zcallback,
	php_streams *stream;
}
static PHP_FUNCTION(epoll_wait)
{
	zval *zstream;
}

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


