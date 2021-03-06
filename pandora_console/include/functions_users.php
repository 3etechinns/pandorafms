<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Users
 */

require_once($config['homedir'] . "/include/functions_groups.php");

function users_is_strict_acl($id_user = null) {
	global $config;
	
	if (empty($id_user)) {
		$id_user = $config['id_user'];
	}
	
	$strict_acl = (bool)db_get_value('strict_acl', 'tusuario',
		'id_user', $id_user);
	
	return $strict_acl;
}

/**
 * Get a list of all users in an array [username] => (info)
 *
 * @param string Field to order by (id_usuario, nombre_real or fecha_registro)
 * @param string Which info to get (defaults to nombre_real)
 *
 * @return array An array of users
 */
function users_get_info ($order = "fullname", $info = "fullname") {
	$users = get_users ($order);
	
	$ret = array ();
	foreach ($users as $user_id => $user_info) {
		$ret[$user_id] = $user_info[$info];
	}
	
	return $ret;
}

/**
 * Enable/Disable a user
 *
 * @param int user id
 * @param int new disabled value (0 when enable, 1 when disable)
 *
 * @return int sucess return
 */
function users_disable ($user_id, $new_disabled_value) {
	return db_process_sql_update('tusuario',
		array('disabled' => $new_disabled_value), array('id_user' => $user_id));
}

/**
 * Get all the Model groups a user has reading privileges.
 *
 * @param string User id
 * @param string The privilege to evaluate
 *
 * @return array A list of the groups the user has certain privileges.
 */
function users_get_all_model_groups () {
	$groups = db_get_all_rows_in_table ('tmodule_group');
	if ($groups === false) {
		$groups = array();
	}
	
	$returnGroups = array();
	foreach ($groups as $group)
		$returnGroups[$group['id_mg']] = $group['name'];
	
	$returnGroups[0] = "Not assigned"; //Module group external to DB but it exist
	
	
	return $returnGroups;
}

/**
 * Get all the groups a user has reading privileges with the special format to use it on select.
 *
 * @param string User id
 * @param string The privilege to evaluate, and it is false then no check ACL.
 * @param boolean $returnAllGroup Flag the return group, by default true.
 * @param boolean $returnAllColumns Flag to return all columns of groups.
 * @param array $id_groups The id of node that must do not show the children and own.
 * @param string $keys_field The field of the group used in the array keys. By default ID
 *
 * @return array A list of the groups the user has certain privileges.
 */
function users_get_groups_for_select($id_user,  $privilege = "AR", $returnAllGroup = true,  $returnAllColumns = false, $id_groups = null, $keys_field = 'id_grupo') {
	if ($id_groups === false) {
		$id_groups = null;
	}
	
	$user_groups = users_get_groups ($id_user, $privilege, $returnAllGroup, $returnAllColumns, null);
	
	if ($id_groups !== null) {
		$childrens = groups_get_childrens($id_groups);
		foreach ($childrens as $child) {
			unset($user_groups[$child['id_grupo']]);
		}
		unset($user_groups[$id_groups]);
	}
	
	if (empty($user_groups)) {
		$user_groups_tree = array();
	}
	else {
		// First group it's needed to retrieve its parent group
		$first_group = array_slice($user_groups, 0, 1);
		$first_group = reset($first_group);
		$parent_group = $first_group['parent'];
		
		$user_groups_tree = groups_get_groups_tree_recursive($user_groups, $parent_group);
	}
	$fields = array();
	
	foreach ($user_groups_tree as $group) {
		$groupName = ui_print_truncate_text($group['nombre'], GENERIC_SIZE_TEXT, false, true, false);
		
		$fields[$group[$keys_field]] = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $group['deep']) . $groupName;
	}
	
	return $fields;
}

function get_group_ancestors($group_id, $groups) {
	if($group_id == 0) {
		return 0;
	}

	if (!isset($groups[$group_id])) {
		return null;
	}

	$parent = $groups[$group_id]["parent"];

	if ($groups[$group_id]["propagate"] == 0){
		return $group_id;
	}

	$r = get_group_ancestors($parent, $groups);

	if (is_array($r)) {
		$r = array_merge(array($group_id), $r);
	}
	else {
		$r = array($group_id, $r);
	}

	return $r;
}

function groups_combine_acl($acl_group_a, $acl_group_b){
	if(!is_array($acl_group_a)){
		if(is_array($acl_group_b)){
			return $acl_group_b;
		}
		else{
			return null;
		}
	}
	else{
		if(!is_array($acl_group_b)){
			return $acl_group_a;
		}
	}

	$acl_list = array (
		"incident_view" => 1,
		"incident_edit" => 1,
		"incident_management" => 1,
		"agent_view" => 1,
		"agent_edit" => 1,
		"agent_disable" => 1,
		"alert_edit" => 1,
		"alert_management" => 1,
		"pandora_management" => 1,
		"db_management" => 1,
		"user_management" => 1,
		"report_view" => 1,
		"report_edit" => 1,
		"report_management" => 1,
		"event_view" => 1,
		"event_edit" => 1,
		"event_management" => 1,
		"map_view" => 1,
		"map_edit" => 1,
		"map_management" => 1,
		"vconsole_view" => 1,
		"vconsole_edit" => 1,
		"vconsole_management" => 1,
		"tags" => 1,
	);

	foreach ($acl_list as $acl => $aux) {

		if($acl == "tags") {
			// Mix tags
			
			if (isset($acl_group_a[$acl]) && ($acl_group_a[$acl] != "")) {
				if (isset($acl_group_b[$acl]) && ($acl_group_b[$acl] != "")) {
					if ($acl_group_b[$acl] != ($acl_group_a[$acl])) {
						$acl_group_b[$acl] = $acl_group_a[$acl] . "," . $acl_group_b[$acl];
					}
				}
				else {
					$acl_group_b[$acl] = $acl_group_a[$acl];
				}
			}
			continue;
		}
		// propagate ACL
		$acl_group_b[$acl] = $acl_group_a[$acl] || $acl_group_b[$acl];
	}

	return $acl_group_b;

}

/**
 * Get all the groups a user has reading privileges.
 *
 * @param string User id
 * @param string The privilege to evaluate, and it is false then no check ACL.
 * @param boolean $returnAllGroup Flag the return group, by default true.
 * @param boolean $returnAllColumns Flag to return all columns of groups.
 * @param array $id_groups The list of group to scan to bottom child. By default null.
 * @param string $keys_field The field of the group used in the array keys. By default ID
 *
 * @return array A list of the groups the user has certain privileges.
 */
function users_get_groups ($id_user = false, $privilege = "AR", $returnAllGroup = true, $returnAllColumns = false, 
							$id_groups = null, $keys_field = 'id_grupo', $cache = true) {
	static $group_cache = array();

	// Added users_group_cache to avoid unnecessary proccess on massive calls...
	static $users_group_cache = array();
	$users_group_cache_key = $id_user . "|" . $privilege . "|" . $returnAllGroup . "|" . $returnAllColumns;

	if (empty ($id_user)) {
		global $config;
	
		$id_user = null;
		if (isset($config['id_user'])) {
			$id_user = $config['id_user'];
		}
	}

	// Check the group cache first.
	if (array_key_exists($id_user, $group_cache) && $cache) {
		$forest_acl = $group_cache[$id_user];
	}
	else {
		// Admin.
		if (is_user_admin($id_user)) {
			$forest_acl = db_get_all_rows_sql ("SELECT * FROM tgrupo ORDER BY nombre");
		}
		// Per-group permissions.
		else {
			$query  = "SELECT * FROM tgrupo ORDER BY parent,id_grupo DESC";
			$raw_groups = db_get_all_rows_sql($query);

			$query = sprintf("SELECT tgrupo.*, tperfil.*, tusuario_perfil.tags FROM tgrupo, tusuario_perfil, tperfil
						WHERE (tgrupo.id_grupo = tusuario_perfil.id_grupo OR tusuario_perfil.id_grupo = 0)
						AND tusuario_perfil.id_perfil = tperfil.id_perfil
						AND tusuario_perfil.id_usuario = '%s' ORDER BY nombre", $id_user);
			$raw_forest = db_get_all_rows_sql ($query);

			foreach ($raw_forest as $g) {
				// XXX, following code must be remade (TAG)
				if (!isset($forest_acl[$g["id_grupo"]] )) {
					$forest_acl[$g["id_grupo"]] = $g;
				}
				else {
					$forest_acl[$g["id_grupo"]] = groups_combine_acl($forest_acl[$g["id_grupo"]], $g);
				}
				
			}

			$groups = array();
			foreach ($raw_groups as $g) {
				$groups[$g["id_grupo"]] = $g;
			}

			foreach ($groups as $group) {
				$parents = get_group_ancestors($group["parent"],$groups);
				if (is_array($parents)) {
					foreach ($parents as $parent) {
						if ( (isset($forest_acl[$parent])) && ($groups[$parent]["propagate"] == 1)) {
							if (isset($forest_acl[$group["id_grupo"]])) {
								// update ACL propagation
								$tmp = groups_combine_acl($forest_acl[$parent], $forest_acl[$group["id_grupo"]]);
							}
							else {
								// add group to user ACL forest
								$tmp = groups_combine_acl($forest_acl[$parent], $group);
							}
							if ($tmp !== null) {
								// add only if valid
								$forest_acl[$group["id_grupo"]] = $tmp;
							}
						}
					}
				}
				else {
					// no parents, direct assignment already done
				}
			}		
		}
		// Update the group cache.
		$group_cache[$id_user] = $forest_acl;
	}

	$user_groups = array ();
	if (!$forest_acl) {
		return $user_groups;
	}
	
	if ($returnAllGroup) { //All group
		$groupall = array('id_grupo' => 0, 'nombre' => __('All'),
			'icon' => 'world', 'parent' => 0, 'disabled' => 0,
			'custom_id' => null, 'description' => '', 'propagate' => 0); 
		
		// Add the All group to the beginning to be always the first
		array_unshift($forest_acl, $groupall);
	}
	
	$acl_column = get_acl_column($privilege);

	if (array_key_exists($users_group_cache_key, $users_group_cache)) {
		return $users_group_cache[$users_group_cache_key];
	}


	foreach ($forest_acl as $group) {

		# Check the specific permission column. acl_column is undefined for admins.
		if (isset($group[$acl_column]) && $group[$acl_column] != '1') {
			continue;
		}

		if ($returnAllColumns) {
			$user_groups[$group[$keys_field]] = $group;
		}
		else {
			$user_groups[$group[$keys_field]] = $group['nombre'];
		}
	}

	$users_group_cache[$users_group_cache_key] = $user_groups;

	return $user_groups;
}

/**
 * Get all the groups a user has reading privileges. Version for tree groups.
 *
 * @param string User id
 * @param string The privilege to evaluate
 * @param boolean $returnAllGroup Flag the return group, by default true.
 * @param boolean $returnAllColumns Flag to return all columns of groups.
 *
 * @return array A treefield list of the groups the user has certain privileges.
 */
function users_get_groups_tree($id_user = false, $privilege = "AR", $returnAllGroup = true) {
	$user_groups = users_get_groups ($id_user, $privilege, $returnAllGroup, true);
	
	$user_groups_tree = groups_get_groups_tree_recursive($user_groups);
	
	return $user_groups_tree;
}

/**
 * Get the first group of an user.
 *
 * Useful function when you need a default group for a user.
 *
 * @param string User id
 * @param string The privilege to evaluate
 * @param bool $all_group Flag to return all group, by default true;
 *
 * @return array The first group where the user has certain privileges.
 */
function users_get_first_group ($id_user = false, $privilege = "AR", $all_group = true) {
	$groups = array_keys (users_get_groups ($id_user, $privilege));
	
	$return = array_shift($groups);
	
	if ((!$all_group) && ($return == 0)) {
		$return = array_shift($groups);
	}
	
	return $return;
}

/**
 * Return access to a specific agent by a specific user
 *
 * @param int Agent id.
 * @param string Access mode to be checked. Default AR (Agent reading)
 * @param string User id. Current user by default
 *
 * @return bool Access to that agent (false not, true yes)
 */
function users_access_to_agent ($id_agent, $mode = "AR", $id_user = false) {
	if (empty ($id_agent))
		return false;
	
	if ($id_user == false) {
		global $config;
		$id_user = $config['id_user'];
	}
	
	$id_group = (int) db_get_value ('id_grupo', 'tagente', 'id_agente', (int) $id_agent);
	
	return (bool) check_acl ($id_user, $id_group, $mode);
}

/**
 * Return user by id (user name)
 *
 * @param string User id.
 *
 * @return mixed User row or false if something goes wrong
 */
function users_get_user_by_id ($id_user) {
	$result_user = db_get_row('tusuario', 'id_user', $id_user);
	
	return $result_user;
}

define("MAX_TIMES", 10);

////////////////////////////////////////////////////////////////////////
//////////////////////WEBCHAT FUNCTIONS/////////////////////////////////
////////////////////////////////////////////////////////////////////////
function users_get_last_messages($last_time = false) {
	$file_global_counter_chat = $config["attachment_store"] . '/pandora_chat.global_counter.txt';
	
	//First lock the file
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter === false) {
		echo json_encode($return);
		
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_global_counter, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			echo json_encode($return);
			
			return;
		}
		
		sleep(1);
	}
	fscanf($fp_global_counter, "%d", $global_counter_file);
	if (empty($global_counter_file)) {
		$global_counter_file = 0;
	}
	
	$timestamp = time();
	if ($last_time === false)
		$last_time = 24 * 60 * 60;
	$from = $timestamp - $last_time;
	
	$log_chat_file = $config["attachment_store"] . '/pandora_chat.log.json.txt';
	
	$return = array('correct' => false, 'log' => array());
	
	if (!file_exists($log_chat_file)) {
		touch($log_chat_file);
	}
	
	$text_encode = @file_get_contents($log_chat_file);
	$log = json_decode($text_encode, true);
	
	if ($log !== false) {
		if ($log === null)
			$log = array();
		
		$log_last_time = array();
		foreach ($log as $message) {
			if ($message['timestamp'] >= $from) {
				$log_last_time[] = $message;
			}
		}
		
		$return['correct'] = true;
		$return['log'] = $log_last_time;
		$return['global_counter'] = $global_counter_file;
	}
	
	echo json_encode($return);
	
	fclose($fp_global_counter);
	
	return;
}

function users_save_login() {
	global $config;
	
	$file_global_user_list = $config["attachment_store"] . '/pandora_chat.user_list.json.txt';
	
	$user = db_get_row_filter('tusuario',
		array('id_user' => $config['id_user']));
	
	$message = sprintf(__('User %s login at %s'), $user['fullname'],
		date($config['date_format']));
	users_save_text_message($message, 'notification');
	
	//First lock the file
	$fp_user_list = @fopen($file_global_user_list, "a+");
	if ($fp_user_list === false) {
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_user_list, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			return;
		}
		
		sleep(1);
	}
	@fscanf($fp_user_list, "%[^\n]", $user_list_json);
	
	$user_list = json_decode($user_list_json, true);
	if (empty($user_list))
		$user_list = array();
	
	if (isset($user_list[$config['id_user']])) {
		$user_list[$config['id_user']]['count']++;
	}
	else {
		$user_list[$config['id_user']] = array('name' => $user['fullname'],
			'count' => 1);
	}
	
	//Clean the file
	ftruncate($fp_user_list, 0);
	
	$status = fwrite($fp_user_list, json_encode($user_list));
	
	if ($status === false) {
		fclose($fp_user_list);
		
		return;
	}
	
	fclose($fp_user_list);
}

function users_save_logout($user = false, $delete = false) {
	global $config;
	
	$return = array('correct' => false, 'users' => array());
	
	$file_global_user_list = $config["attachment_store"] . '/pandora_chat.user_list.json.txt';
	
	if (empty($user)) {
		$user = db_get_row_filter('tusuario',
			array('id_user' => $config['id_user']));
	}
	
	if ($delete) {
		$no_json_output = true;
		$message = sprintf(__('User %s was deleted in the DB at %s'),
			$user['fullname'], date($config['date_format']));
	}
	else {
		$no_json_output = false;
		$message = sprintf(__('User %s logout at %s'), $user['fullname'],
			date($config['date_format']));
	}
	
	users_save_text_message($message, 'notification', $no_json_output);
	
	//First lock the file
	$fp_user_list = @fopen($file_global_user_list, "a+");
	if ($fp_user_list === false) {
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_user_list, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			return;
		}
		
		sleep(1);
	}
	@fscanf($fp_user_list, "%[^\n]", $user_list_json);
	
	$user_list = json_decode($user_list_json, true);
	if (empty($user_list))
		$user_list = array();
	
	if ($delete) {
		unset($user_list[$user['id_user']]);
	}
	else {
		if (isset($user_list[$config['id_user']])) {
			$user_list[$config['id_user']]['count']--;
		}
		
		if ($user_list[$config['id_user']]['count'] <= 0) {
			unset($user_list[$user['id_user']]);
		}
	}
	
	//Clean the file
	ftruncate($fp_user_list, 0);
	
	$status = fwrite($fp_user_list, json_encode($user_list));
	
	if ($status === false) {
		fclose($fp_user_list);
		
		return;
	}
	
	fclose($fp_user_list);
}

function users_save_text_message($message = false, $type = 'message', $no_json_output = false) {
	global $config;
	
	$file_global_counter_chat = $config["attachment_store"] . '/pandora_chat.global_counter.txt';
	$log_chat_file = $config["attachment_store"] . '/pandora_chat.log.json.txt';
	
	$return = array('correct' => false);
	
	$id_user = $config['id_user'];
	$user = db_get_row_filter('tusuario',
		array('id_user' => $id_user));
	
	$message_data = array();
	$message_data['type'] = $type;
	$message_data['id_user'] = $id_user;
	$message_data['user_name'] = $user['fullname'];
	$message_data['text'] = io_safe_input_html($message);
	//The $message_data['timestamp'] set when adquire the files to save.
	
	
	
	//First lock the file
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter === false) {
		if (!$no_json_output)
			echo json_encode($return);
		
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_global_counter, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			if (!$no_json_output)
				echo json_encode($return);
			
			return;
		}
		
		sleep(1);
	}
	@fscanf($fp_global_counter, "%d", $global_counter_file);
	if (empty($global_counter_file)) {
		$global_counter_file = 0;
	}
	
	//Clean the file
	ftruncate($fp_global_counter, 0);
	
	$message_data['timestamp'] = time();
	$message_data['human_time'] = date($config['date_format'], $message_data['timestamp']);
	
	$global_counter = $global_counter_file + 1;
	
	$status = fwrite($fp_global_counter, $global_counter);
	
	if ($status === false) {
		fclose($fp_global_counter);
		
		if (!$no_json_output)
			echo json_encode($return);
		
		return;
	}
	else {
		$text_encode = @file_get_contents($log_chat_file);
		$log = json_decode($text_encode, true);
		$log[$global_counter] = $message_data;
		$status = file_put_contents($log_chat_file, json_encode($log));
		
		fclose($fp_global_counter);
		
		$return['correct'] = true;
		if (!$no_json_output)
			echo json_encode($return);
	}
	
	return;
}

function users_long_polling_check_messages($global_counter) {
	global $config;
	
	$file_global_counter_chat = $config["attachment_store"] . '/pandora_chat.global_counter.txt';
	$log_chat_file = $config["attachment_store"] . '/pandora_chat.log.json.txt';
	
	$changes = false;
	
	$tries_general = 0;
	
	$error = false;
	
	while (!$changes) {
		//First lock the file
		$fp_global_counter = @fopen($file_global_counter_chat, "a+");
		if ($fp_global_counter) {
			//Try to look MAX_TIMES times
			$tries = 0;
			$lock = true;
			while (!flock($fp_global_counter, LOCK_EX)) {
				$tries++;
				if ($tries > MAX_TIMES) {
					$lock = false;
					$error = true;
					break;
				}
				
				sleep(1);
			}
			
			if ($lock) {
				@fscanf($fp_global_counter, "%d", $global_counter_file);
				if (empty($global_counter_file)) {
					$global_counter_file = 0;
				}
				
				if ($global_counter_file > $global_counter) {
					//TODO Optimize slice the array.
					
					$text_encode = @file_get_contents($log_chat_file);
					$log = json_decode($text_encode, true);
					
					$return_log = array();
					foreach ($log as $key => $message) {
						if ($key <= $global_counter) continue;
						
						$return_log[] = $message;
					}
					
					$return = array(
						'correct' => true,
						'global_counter' => $global_counter_file,
						'log' => $return_log);
					
					echo json_encode($return);
					
					fclose($fp_global_counter);
					
					return;
				}
			}
			fclose($fp_global_counter);
		}
		
		sleep(3);
		$tries_general = $tries_general + 3;
		
		if ($tries_general > MAX_TIMES) {
			break;
		}
	}
	
	//Because maybe the exit of loop for exaust.
	echo json_encode(array('correct' => false, 'error' => $error));
	
	return;
}

/**
 * Get the last global counter for chat.
 * 
 * @param string $mode There are two modes 'json', 'return' and 'session'. And json is by default.
 */
function users_get_last_global_counter($mode = 'json') {
	global $config;
	
	$file_global_counter_chat = $config["attachment_store"] . '/pandora_chat.global_counter.txt';
	
	$global_counter_file = 0;
	
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter) {
		$tries = 0;
		$lock = true;
		while (!flock($fp_global_counter, LOCK_EX)) {
			$tries++;
			if ($tries > MAX_TIMES) {
				$lock = false;
				break;
			}
			
			sleep(1);
		}
		
		if ($lock) {
			@fscanf($fp_global_counter, "%d", $global_counter_file);
			if (empty($global_counter_file)) {
				$global_counter_file = 0;
			}
			
			fclose($fp_global_counter);
		}
	}
	
	switch ($mode) {
		case 'json':
			echo json_encode(array('correct' => true, 'global_counter' => $global_counter_file));
			break;
		case 'return':
			return $global_counter_file;
			break;
		case 'session':
			$_SESSION['global_counter_chat'] = $global_counter_file;
			break;
	}
}

/**
 * Get the last global counter for chat.
 * 
 * @param string $mode There are two modes 'json', 'return' and 'session'. And json is by default.
 */
function users_get_last_type_message() {
	global $config;
	
	$return = 'false';
	
	$file_global_counter_chat = $config["attachment_store"] . '/pandora_chat.global_counter.txt';
	$log_chat_file = $config["attachment_store"] . '/pandora_chat.log.json.txt';
	
	$global_counter_file = 0;
	
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter) {
		$tries = 0;
		$lock = true;
		while (!flock($fp_global_counter, LOCK_EX)) {
			$tries++;
			if ($tries > MAX_TIMES) {
				$lock = false;
				break;
			}
			
			sleep(1);
		}
		
		if ($lock) {
			$text_encode = @file_get_contents($log_chat_file);
			$log = json_decode($text_encode, true);
			
			// Prevent from error when chat file log doesn't exists 
			if (empty($log))
				$return = false;
			else {
				$last = end($log);
				$return = $last['type'];
			}
			
			fclose($fp_global_counter);
		}
	}
	
	return $return;
}

function users_is_admin($id_user = false) {
	global $config;

	if (!isset($config["is_admin"])) {
		$config["is_admin"] = array();
	}

	if ($id_user === false) {
		$id_user = $config['id_user'];
	}
	
	if (isset($config["is_admin"][$id_user])) {
		return $config["is_admin"][$id_user];
	}
	
	$config["is_admin"][$id_user] = (bool)db_get_value('is_admin',
		'tusuario', 'id_user', $id_user);
	
	return $config["is_admin"][$id_user];
}

function users_is_last_system_message() {
	$type = users_get_last_type_message();
	
	if ($type != 'message')
		return true;
	else
		return false;
}

function users_check_users() {
	global $config;
	
	$return = array('correct' => false, 'users' => '');
	
	$file_global_user_list = $config["attachment_store"] . '/pandora_chat.user_list.json.txt';
	
	//First lock the file
	$fp_user_list = @fopen($file_global_user_list, "a+");
	if ($fp_user_list === false) {
		echo json_encode($return);
		
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_user_list, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			echo json_encode($return);
			
			return;
		}
		
		sleep(1);
	}
	@fscanf($fp_user_list, "%[^\n]", $user_list_json);
	
	$user_list = json_decode($user_list_json, true);
	if (empty($user_list))
		$user_list = array();
	
	fclose($fp_user_list);
	
	$user_name_list = array();
	foreach ($user_list as $user) {
		$user_name_list[] = $user['name'];
	}
	
	$return['correct'] = true;
	$return['users'] = implode('<br />', $user_name_list);
	echo json_encode($return);
	
	return;
}

// Check if a user can manage a group when group is all
// This function dont check acls of the group, only if the 
// user is admin or pandora manager and the group is all
function users_can_manage_group_all($access = "PM") {
	global $config;

	$access = get_acl_column($access);

	$sql = sprintf ('SELECT COUNT(*) FROM tusuario_perfil
		INNER JOIN tperfil
			ON tperfil.id_perfil = tusuario_perfil.id_perfil
		WHERE tusuario_perfil.id_grupo=0
			AND tusuario_perfil.id_usuario="%s"
			AND %s=1
		', $config['id_user'], $access
	);
	
	if (users_is_admin($config['id_user']) || (int)db_get_value_sql($sql) !== 0) {
		return true;
	}
	
	return false;
}

/**
 * Get the users that belongs to the same groups of the current user
 * 
 * @param string User id
 * @param string The privilege to evaluate, and it is false then no check ACL.
 * @param boolean $returnAllGroup Flag the return group, by default true.
 * 
 * @return mixed Array with id_user as index and value
 */
function users_get_user_users($id_user = false, $privilege = "AR",
	$returnAllGroup = true, $fields = null) {
	
	global $config;
	
	$user_groups = users_get_groups($id_user, $privilege, $returnAllGroup);

	$user_users = array();
	$array_user_group = array();
	
	foreach ($user_groups as $id_user_group => $name_user_group) {
		$array_user_group[] = $id_user_group;
	}

	$group_users = groups_get_users($array_user_group, false, $returnAllGroup);
		
	foreach ($group_users as $gu) {
		if (empty($fields)) {
			$user_users[$gu['id_user']] = $gu['id_user'];
		}
		else {
			$fields = (array)$fields;
			foreach ($fields as $field) {
				$user_users[$gu['id_user']][$field] = $gu[$field];
			}
		}
	}
	
	return $user_users;
}

function users_get_strict_mode_groups($id_user, $return_group_all) {
	
	global $config;
	
	$sql = "SELECT * FROM tusuario_perfil WHERE id_usuario = '".$id_user."' AND tags = ''";
	$user_groups = db_get_all_rows_sql ($sql);
	
	if ($user_groups == false) {
		$user_groups = array();
	}
	
	$return_user_groups = array();
	if ($return_group_all) {
		$return_user_groups[0] = __('All');
	}
	foreach ($user_groups as $group) {
		$return_user_groups[$group['id_grupo']] = groups_get_name ($group['id_grupo']);
	}
	
	return $return_user_groups;
}

?>
