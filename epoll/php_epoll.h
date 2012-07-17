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

#ifndef PHP_EPOLL_H
#define PHP_EPOLL_H
#define PHP_LIBEVENT_VERSION "0.0.1"

extern zend_module_entry epoll_module_entry;
#define phpext_epoll_ptr &epoll_module_entry

#ifdef ZTS
#include "TSRM.h"
#endif

PHP_MINIT_FUNCTION(epoll);
PHP_MSHUTDOWN_FUNCTION(epoll);
PHP_RINIT_FUNCTION(epoll);
PHP_RSHUTDOWN_FUNCTION(epoll);
PHP_MINFO_FUNCTION(epoll);

PHP_FUNCTION(epoll_create);
PHP_FUNCTION(epoll_ctl);
PHP_FUNCTION(epoll_wait);



#define PHP_EPOLL_VERSION "0.0.1"

#define EPOLL_BUF_TOO_SMALL(ret,errno) \
	((ret) == 0 || ((ret) == -1 && (errno) == EINVAL))
#define EPOLL_FD(stream, fd) \
	php_stream_cast((stream), PHP_STREAM_AS_FD_FOR_SELECT, (void*)&(fd), 1);

/* Define some error messages for the error numbers set by inotify_*() functions, 
 as strerror() messages are not always usefull here */

#define EPOLL_CREATE_EMFILE \
	"The user limit on the total number of epoll instances has been reached"
#define EPOLL_CREATE_ENFILE \

	"The system limit on the total number of file descriptors has been reached"
#define EPOLL_CREATE_ENOMEM \
	"Insufficient kernel memory is available"
#define EPOLL_CREATE_EINVAL \
	"size is not positive"

#define EPOLL_CTL_EEXIST \
	"the supplied file descriptor already registered with the epoll instance"
#define EPOLL_CTL_EBADF \
	"The given epoll resource or file descriptor is not valid"
#define EPOLL_CTL_EINVAL \
	"The given epoll resource is not valid or  file descriptor is not supported"
#define EPOLL_CTL_ENOMEM \
	"Insufficient kernel memory was available"
#define EPOLL_CTL_ENOSPC \
	"The user limit on the total number of inotify watches was reached or the kernel failed to allocate a needed resource"
#define EPOLL_CTL_NOENT \
	"op was EPOLL_CTL_MOD or EPOLL_CTL_DEL and fd is not registered with this epoll instance"
#define EPOLL_CTL_EPERM \
	"The target file fd does not support epoll"

#define EPOLL_ERROR_CASE(func, errno) \
	case (errno): \
		php_error_docref(NULL TSRMLS_CC, E_WARNING, INOTIFY_##func##_##errno); \
		break;
#define EPOLL_DEFAULT_ERROR(errno) \
	default: \
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "%s", strerror(errno)); \
		break;


#endif	/* PHP_EPOLL_H */


