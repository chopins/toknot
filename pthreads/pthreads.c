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
#include "php_pthreads.h"
#include <pthreads.h>

/* If you declare any globals in php_pthreads.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(pthreads)
*/

/* True global resources - no need for thread safety here */
static int le_pthreads;

/* {{{ pthreads_functions[]
 *
 * Every user visible function must have an entry in pthreads_functions[].
 */
const zend_function_entry pthreads_functions[] = {
	PHP_FE(pthread_gettid,	NULL)
	PHP_FE(pthread_create,	NULL)
	PHP_FE(pthread_exit,	NULL)
	PHP_FE(pthread_join,	NULL)
	PHP_FE_END	/* Must be the last line in pthreads_functions[] */
};
/* }}} */

/* {{{ pthreads_module_entry
 */
zend_module_entry pthreads_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"pthreads",
	pthreads_functions,
	PHP_MINIT(pthreads),
	PHP_MSHUTDOWN(pthreads),
	PHP_RINIT(pthreads),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(pthreads),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(pthreads),
#if ZEND_MODULE_API_NO >= 20010901
	"0.1", /* Replace with version number for your extension */
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_PTHREADS
ZEND_GET_MODULE(pthreads)
#endif


/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(pthreads)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(pthreads)
{
	return SUCCESS;
}
/* }}} */



/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(pthreads)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "pthreads support", "enabled");
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	DISPLAY_INI_ENTRIES();
	*/
}
/* }}} */

void thread_test(void) {
    printf("this is a pthread.%d \n", tid);
}


PHP_FUNCTION(pthread_gettid) 
{
	int tid;
	tid = pthread_gettid();
	RETURN_LONG(tid);
}

PHP_FUNCTION(pthread_create)
{
	zval *z_tid;
	pthread_t id;
	int ret;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &z_status) == FAILURE)
		return;

	convert_to_long_ex(&z_tid);
	id = Z_LVAL_P(z_tid);

    ret = pthread_create(&id, NULL, (void *) thread_test ,NULL);

	Z_LVAL_P(z_tid) = id;

	RETURN_LONG(ret);
}
PHP_FUNCTION(pthread_exit)
{
	pthread_exit(0);
}

PHP_FUNCTION(pthread_join)
{
	long id;
	if(zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &id) == FAILURE)
		return;
	pthread_join((pthread_t) id);
}
