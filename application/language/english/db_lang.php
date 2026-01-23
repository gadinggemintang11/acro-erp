<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database language strings
 *
 * @package	CodeIgniter
 * @subpackage	Language
 * @category	Language
 * @author	EllisLab Dev Team
 * @link	https://codeigniter.com
 */

$lang['db_invalid_connection_str'] = 'Unable to determine the database settings based on the connection string you submitted.';
$lang['db_unable_to_select'] = 'Unable to select the specified database: %s';
$lang['db_unable_to_create'] = 'Unable to create the specified database: %s';
$lang['db_invalid_query'] = 'The query you submitted is not valid.';
$lang['db_halt_executed'] = 'The query execution has stopped.';
$lang['db_unable_to_load_utility'] = 'Unable to load the specified database utility.';
$lang['db_unable_to_load_driver'] = 'Unable to load the specified database driver: %s';
$lang['db_connection_failed'] = 'Unable to connect to your database server using the provided settings.';
$lang['db_unable_to_send_charset'] = 'Unable to send character set request: %s';
$lang['db_invalid_savepoint'] = 'Savepoint names must follow the naming conventions for savepoints.';
$lang['db_error_heading'] = 'A Database Error Occurred';
$lang['db_unknown_error'] = 'Unknown Database Error';
$lang['db_must_set_table'] = 'You must set the database table to be used with your query.';
$lang['db_must_use_set'] = 'You must use the "set" method to update an entry.';
$lang['db_must_use_where'] = 'Updates are not allowed unless they are accompanied by a "where" clause.';
$lang['db_must_use_like'] = 'DELETE queries are not allowed unless they are accompanied by a "like" clause.';
$lang['db_delete_limit'] = 'DELETE queries are not allowed to have LIMIT clauses.';
$lang['db_call_to_unconfigured_model'] = 'The model you are calling has not been configured properly or is not a Model.';
$lang['db_invalid_call_default_table'] = 'Call to a model method is not valid: model does not have a default table.';
$lang['db_invalid_calls'] = 'The query method used is invalid.';
$lang['db_query_notallowed'] = 'That type of query is not permitted.';
