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
#include <pthread.h>
typedef struct _php_pthread_callback_t {
    zval *func;
    zval *arg;
} php_pthread_callback_t;


ZEND_BEGIN_ARG_INFO(arginfo_pthread_gettid, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_pthread_create,0, 0,1)
    ZEND_ARG_INFO(1, tid)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO(arginfo_pthread_exit,0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_pthread_join,0,0,1)
    ZEND_ARG_INFO(0,tid)
ZEND_END_ARG_INFO()


/* {{{ pthreads_functions[]
 *
 * Every user visible function must have an entry in pthreads_functions[].
 */
const zend_function_entry pthreads_functions[] = {
	PHP_FE(pthread_gettid,	arginfo_pthread_gettid)
	PHP_FE(pthread_create,	arginfo_pthread_create)
	PHP_FE(pthread_exit,	arginfo_pthread_exit)
	PHP_FE(pthread_join,	arginfo_pthread_join)
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
	NULL,	/* Replace with NULL if there's nothing to do at request start */
	NULL,	/* Replace with NULL if there's nothing to do at request end */
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

void pthread_callback(void * call_param)
{
    zval *args[1];
    php_pthread_callback_t *callback = (php_pthread_callback_t *)call_param;
    zval retval;
    zend_op_array *orig_op_array = NULL;
    zend_op_array *new_op_array = NULL;
    zend_file_handle file_handle;
    
    args[0] = callback->arg;
    
    TSRMLS_FETCH();


    zend_execute(EG(active_op_array) TSRMLS_CC);
    if ( call_user_function(EG(function_table), NULL, callback->func, &retval, 1, args TSRMLS_CC) == SUCCESS)
    {
        zval_dtor(&retval);
    }
    zval_ptr_dtor(&(args[0]));
}

/* {{{ proto int pthread_gettid(void)
*/ 
PHP_FUNCTION(pthread_gettid) 
{
	pthread_t tid;
	tid = pthread_self();
	RETURN_LONG(tid);
}
/* }}} */
;
/* {{{ proto int pthread_create(int tid, callback start_route[, mixed arg])
 */
PHP_FUNCTION(pthread_create)
{
	zval *z_tid, *z_callback, *z_arg;
    char *func_name;
	pthread_t tid;
    php_pthread_callback_t *callback;
	int ret;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "zz|z", &z_tid, &z_callback, &z_arg) == FAILURE)
		return;

    if (!zend_is_callable(z_callback, 0, &func_name TSRMLS_CC))
    {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "%s is not a callable function name error", func_name);
		efree(func_name);
		RETURN_FALSE;
    }

    efree(func_name);

    zval_add_ref(&z_callback);
    if (z_arg) 
    {
        zval_add_ref(&z_arg);
    } else {
        ALLOC_INIT_ZVAL(z_arg);
    }

    callback = emalloc(sizeof(php_pthread_callback_t));
    callback->func = z_callback;
    callback->arg  = z_arg;

	convert_to_long_ex(&z_tid);
	tid = Z_LVAL_P(z_tid);

    ret = pthread_create(&tid, NULL, (void *) pthread_callback, callback);
	Z_LVAL_P(z_tid) = tid;

	RETURN_LONG(ret);
}
/* }}} */

/* {{{ proto void pthread_exit(void)
 */
PHP_FUNCTION(pthread_exit)
{
	pthread_exit(0);
}
/* }}} */

/* {{{ proto void pthread_join(int tid)
 */
PHP_FUNCTION(pthread_join)
{
	long tid;
	if(zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &tid) == FAILURE)
		return;

	pthread_join((pthread_t) tid, NULL);
}
/* }}} */
