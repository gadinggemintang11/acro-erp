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
 * Database language strings - 中文
 *
 * @package	CodeIgniter
 * @subpackage	Language
 * @category	Language
 * @author	EllisLab Dev Team
 * @link	https://codeigniter.com
 */

$lang['db_invalid_connection_str'] = '无法根据您提交的连接字符串确定数据库设置。';
$lang['db_unable_to_select'] = '无法选择指定的数据库: %s';
$lang['db_unable_to_create'] = '无法创建指定的数据库: %s';
$lang['db_invalid_query'] = '您提交的查询无效。';
$lang['db_halt_executed'] = '查询执行已停止。';
$lang['db_unable_to_load_utility'] = '无法加载指定的数据库实用程序。';
$lang['db_unable_to_load_driver'] = '无法加载指定的数据库驱动程序: %s';
$lang['db_connection_failed'] = '无法使用提供的设置连接到数据库服务器。';
$lang['db_unable_to_send_charset'] = '无法发送字符集请求: %s';
$lang['db_invalid_savepoint'] = '保存点名称必须遵循保存点的命名约定。';
$lang['db_error_heading'] = '发生数据库错误';
$lang['db_unknown_error'] = '未知数据库错误';
$lang['db_must_set_table'] = '您必须设置要与查询一起使用的数据库表。';
$lang['db_must_use_set'] = '您必须使用"set"方法来更新条目。';
$lang['db_must_use_where'] = '除非附带"where"子句，否则不允许更新。';
$lang['db_must_use_like'] = '除非附带"like"子句，否则不允许DELETE查询。';
$lang['db_delete_limit'] = 'DELETE查询不允许有LIMIT子句。';
$lang['db_call_to_unconfigured_model'] = '您调用的模型尚未正确配置或不是模型。';
$lang['db_invalid_call_default_table'] = '对模型方法的调用无效：模型没有默认表。';
$lang['db_invalid_calls'] = '使用的查询方法无效。';
$lang['db_query_notallowed'] = '不允许该类型的查询。';
