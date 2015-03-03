<?php
/**
 * CustomRequest
 *
 * Copyright 2013 by Thomas Jakobi <thomas.jakobi@partout.info>
 *
 * @package customrequest
 * @subpackage lexicon
 *
 * Default English Lexicon Entries for CustomRequest
 */

$_lang['customrequest'] = 'CustomRequest';
$_lang['customrequest_desc'] = 'Use friendly URLs everywhere';

$_lang['customrequest.menu_home'] = 'CustomRequest';
$_lang['customrequest.menu_home_desc'] = 'Use friendly URLs everywhere';

$_lang['setting_customrequest.debug'] = 'Log debug information';
$_lang['setting_customrequest.debug_desc'] = 'Log debug information in MODX error log';

$_lang['customrequest.configs'] = 'Configurations';
$_lang['customrequest.configs_desc'] = 'Create and modify your CustomRequest configurations.';
$_lang['customrequest.configs_desc_extended'] = 'The configurations are executed in the order of the grid. If there are two configurations starting with the same alias path, the first configuration is used. You can change the order of the configurations by drag&amp;drop.';
$_lang['customrequest.configs_create'] = 'New Configuration';
$_lang['customrequest.configs_update'] = 'Update Configuration';
$_lang['customrequest.configs_remove'] = 'Remove Configuration';
$_lang['customrequest.configs_remove_confirm'] = 'Are you sure you want to delete this CustomRequest Configuration?';
$_lang['customrequest.configs_name'] = 'Configuration Name';
$_lang['customrequest.configs_alias'] = 'Alias Path';
$_lang['customrequest.configs_alias_generated'] = 'Generated';
$_lang['customrequest.configs_alias_desc'] = 'The first characters of a not found URI are compared with this string. If both paths are matching, this configuration is used. If the alias path field is not set, the alias path of the selected resource in this form is used.';
$_lang['customrequest.configs_resourceid'] = 'Resource';
$_lang['customrequest.configs_resourceid_desc'] = 'A not found URI is forwarded to this resource, if the current configuration is used.';
$_lang['customrequest.configs_urlparams'] = 'URI Parameter';
$_lang['customrequest.configs_urlparams_desc'] = 'The request/get/post parameter keys, the divided second part of the not found URI are assigned to. If the Regular Expression field not set, the second part is divided at the URI separators \'/\'';
$_lang['customrequest.configs_regex'] = 'Regular Expression';
$_lang['customrequest.configs_regex_desc'] = 'This optional regular expression is used to divide the second parts of the not found URI. The search results are assigned to the request parameters in the order of occurrence.';

$_lang['customrequest.error_msg'] = 'Error: [[+msg]]';

$_lang['customrequest.configs_err_invalid'] = 'Invalid config.';
$_lang['customrequest.configs_err_nf'] = 'Config not found.';
$_lang['customrequest.configs_err_ns'] = 'Config not specified.';
$_lang['customrequest.configs_err_save'] = 'An error occurred while trying to save the config.';
$_lang['customrequest.configs_err_ns_alias_resourceid'] = 'Please fill the alias and/or select a resource.';
