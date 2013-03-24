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

#ifndef PHP_PTHREADS_H
#define PHP_PTHREADS_H
#define PHP_PTHREADS_VERSION "0.0.1"

#include "TSRM.h"

extern zend_module_entry pthreads_module_entry;
#define phpext_pthreads_ptr &pthreads_module_entry

PHP_MINIT_FUNCTION(pthreads);
PHP_MSHUTDOWN_FUNCTION(pthreads);
PHP_MINFO_FUNCTION(pthreads);

PHP_FUNCTION(pthread_gettid);
PHP_FUNCTION(pthread_create);
PHP_FUNCTION(pthread_exit);
PHP_FUNCTION(pthread_join);

ZEND_BEGIN_MODULE_GLOBALS(pthreads)
	int last_error;
ZEND_END_MODULE_GLOBALS(pcntl)

/* In every utility function you add that needs to use variables 
   in php_pthreads_globals, call TSRMLS_FETCH(); after declaring other 
   variables used by that function, or better yet, pass in TSRMLS_CC
   after the last function argument and declare your utility function
   with TSRMLS_DC after the last declared argument.  Always refer to
   the globals in your function as PTHREADS_G(variable).  You are 
   encouraged to rename these macros something shorter, see
   examples in any other php module directory.
*/

#ifdef ZTS
#define PTHREADS_G(v) TSRMG(pthreads_globals_id, zend_pthreads_globals *, v)
#else
#define PTHREADS_G(v) (pthreads_globals.v)
#endif

#endif	/* PHP_PTHREADS_H */


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
