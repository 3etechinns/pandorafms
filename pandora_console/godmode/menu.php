<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

require_once ('include/config.php');

check_login ();

enterprise_include ('godmode/menu.php');
require_once ('include/functions_menu.php');

$menu_godmode = array ();
$menu_godmode['class'] = 'godmode';

if (check_acl ($config['id_user'], 0, "AW") || check_acl ($config['id_user'], 0, "AD")) {
	$menu_godmode["gagente"]["text"] = __('Resources');
	$menu_godmode["gagente"]["sec2"] = "godmode/agentes/modificar_agente";
	$menu_godmode["gagente"]["id"] = "god-resources";

	if (check_acl ($config['id_user'], 0, "AW")) {
		$sub = array ();
		$sub['godmode/agentes/modificar_agente']['text'] = __('Manage agents');
		$sub["godmode/agentes/modificar_agente"]["subsecs"] = array(
			"godmode/agentes/configurar_agente");

		if (check_acl ($config["id_user"], 0, "PM")) {
			$sub["godmode/agentes/fields_manager"]["text"] = __('Custom fields');
		}

		$sub["godmode/modules/manage_nc_groups"]["text"] = __('Component groups');
		// Category
		$sub["godmode/category/category"]["text"] = __('Module categories');
		$sub["godmode/category/category"]["subsecs"] = "godmode/category/edit_category";
		$sub["godmode/modules/module_list"]["text"] = __('Module types');

		if (check_acl ($config["id_user"], 0, "PM")) {
			$sub["godmode/groups/modu_group_list"]["text"] = __('Module groups');
		}
		// GIS
		if (check_acl ($config['id_user'], 0, "IW")) {
			if ($config['activate_gis']) {
				$sub["godmode/gis_maps/index"]["text"] = __('GIS Maps builder');
				$sub["godmode/gis_maps/index"]["refr"] = (int)get_parameter('refr', 60);
			}
		}

		if ($config['activate_netflow']) {
			//Netflow
			$sub["godmode/netflow/nf_edit"]["text"] = __('Netflow filters');
		}

		$menu_godmode["gagente"]["sub"] = $sub;
	}

}

if (check_acl ($config['id_user'], 0, "UM")) {
	$menu_godmode["gusuarios"]["text"] = __('Profiles');
	$menu_godmode["gusuarios"]["sec2"] = "godmode/users/user_list";
	$menu_godmode["gusuarios"]["id"] = "god-users";

	$sub = array ();
	$sub['godmode/users/user_list']['text'] = __('Users management');
	$sub['godmode/users/profile_list']['text'] = __('Profile management');
	$sub["godmode/groups/group_list"]["text"] = __('Manage agents groups');
	// Tag
	$sub["godmode/tag/tag"]["text"] = __('Module tags');
	$sub["godmode/tag/tag"]["subsecs"] = "godmode/tag/edit_tag";

	enterprise_hook ('enterprise_acl_submenu');
	$menu_godmode["gusuarios"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "PM")) {
	$menu_godmode["gmodules"]["text"] = __('Configuration');
	$menu_godmode["gmodules"]["sec2"] = "godmode/modules/manage_network_templates";
	$menu_godmode["gmodules"]["id"] = "god-configuration";

	$sub = array ();

	$sub["godmode/modules/manage_network_components"]["text"] = __('Network components');
	enterprise_hook ('components_submenu');
	$sub["godmode/modules/manage_network_templates"]["text"] = __('Module templates');
	enterprise_hook ('inventory_submenu');
	if (check_acl ($config['id_user'], 0, "AW")) {
		enterprise_hook ('policies_menu');
	}
	enterprise_hook('agents_submenu');
	if (check_acl ($config['id_user'], 0, "AW")) {
		$sub["gmassive"]["text"] = __('Bulk operations');
		$sub["gmassive"]["type"] = "direct";
		$sub["gmassive"]["subtype"] = "nolink";
		$sub2 = array ();
		$sub2["godmode/massive/massive_operations&amp;tab=massive_agents"]["text"] = __('Agents operations');
		$sub2["godmode/massive/massive_operations&amp;tab=massive_modules"]["text"] = __('Modules operations');
		$sub2["godmode/massive/massive_operations&amp;tab=massive_plugins"]["text"] = __('Plugins operations');
		if (check_acl ($config['id_user'], 0, "PM")) {
			$sub2["godmode/massive/massive_operations&amp;tab=massive_users"]["text"] = __('Users operations');
		}
		$sub2["godmode/massive/massive_operations&amp;tab=massive_alerts"]["text"] = __('Alerts operations');
		enterprise_hook('massivepolicies_submenu');
		enterprise_hook('massivesnmp_submenu');
		enterprise_hook('massivesatellite_submenu');

		$sub["gmassive"]["sub2"] = $sub2;
	}


	$menu_godmode["gmodules"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "LM") || check_acl ($config['id_user'], 0, "AD")) {
	$menu_godmode["galertas"]["text"] = __('Alerts');
	$menu_godmode["galertas"]["sec2"] = "godmode/alerts/alert_list";
	$menu_godmode["galertas"]["id"] = "god-alerts";

	if (check_acl ($config['id_user'], 0, "LM")) {
		$sub = array ();
		$sub["godmode/alerts/alert_list"]["text"] = __('List of Alerts');
		$sub["godmode/alerts/alert_templates"]["text"] = __('Templates');
		$sub["godmode/alerts/alert_actions"]["text"] = __('Actions');

		if (check_acl ($config['id_user'], 0, "PM")) {
			$sub["godmode/alerts/alert_commands"]["text"] = __('Commands');
		}
		$sub["godmode/alerts/alert_special_days"]["text"] = __('Special days list');
		enterprise_hook('eventalerts_submenu');
		$sub["godmode/snmpconsole/snmp_alert"]["text"] = __("SNMP alerts");

		$menu_godmode["galertas"]["sub"] = $sub;
	}
}

if (check_acl ($config['id_user'], 0, "EW")) {
	// Manage events
	$menu_godmode["geventos"]["text"] = __('Events');
	$menu_godmode["geventos"]["sec2"] = "godmode/events/events&amp;section=filter";
	$menu_godmode["geventos"]["id"] = "god-events";

	// Custom event fields
	$sub = array ();
	$sub["godmode/events/events&amp;section=filter"]["text"] = __('Event filters');

	if (check_acl ($config['id_user'], 0, "PM")) {
		$sub["godmode/events/events&amp;section=fields"]["text"] = __('Custom events');
		$sub["godmode/events/events&amp;section=responses"]["text"] = __('Event responses');
	}

	$menu_godmode["geventos"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "AW")) {
	// Servers
	$menu_godmode["gservers"]["text"] = __('Servers');
	$menu_godmode["gservers"]["sec2"] = "godmode/servers/modificar_server";
	$menu_godmode["gservers"]["id"] = "god-servers";

	$sub = array ();
	$sub["godmode/servers/modificar_server"]["text"] = __('Manage servers');
	$sub["godmode/servers/manage_recontask"]["text"] = __('Recon task');

	//This subtabs are only for Pandora Admin
	if (check_acl ($config['id_user'], 0, "PM")) {
		$sub["godmode/servers/plugin"]["text"] = __('Plugins');

		$sub["godmode/servers/recon_script"]["text"] = __('Recon script');

		enterprise_hook('export_target_submenu');
	}

	$menu_godmode["gservers"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "PM")) {
	// Setup
	$menu_godmode["gsetup"]["text"] = __('Setup');
	$menu_godmode["gsetup"]["sec2"] = "godmode/setup/setup&section=general";
	$menu_godmode["gsetup"]["id"] = "god-setup";

	$sub = array ();

	// Options Setup
	$sub["general"]["text"] = __('Setup');
	$sub["general"]["type"] = "direct";
	$sub["general"]["subtype"] = "nolink";
	$sub2 = array ();
	
	$sub2["godmode/setup/setup&amp;section=general"]["text"] = __('General Setup');
	$sub2["godmode/setup/setup&amp;section=general"]["refr"] = 0;

	enterprise_hook ('password_submenu');
	enterprise_hook ('enterprise_submenu');
	enterprise_hook ('historydb_submenu');
	enterprise_hook ('log_collector_submenu');

	$sub2["godmode/setup/setup&amp;section=auth"]["text"] =  __('Authentication');
	$sub2["godmode/setup/setup&amp;section=auth"]["refr"] = 0;

	$sub2["godmode/setup/setup&amp;section=perf"]["text"] = __('Performance');
	$sub2["godmode/setup/setup&amp;section=perf"]["refr"] = 0;

	$sub2["godmode/setup/setup&amp;section=vis"]["text"] = __('Visual styles');
	$sub2["godmode/setup/setup&amp;section=vis"]["refr"] = 0;

	if (check_acl ($config['id_user'], 0, "AW")) {
		if ($config['activate_netflow']) {
			$sub2["godmode/setup/setup&amp;section=net"]["text"] = __('Netflow');
			$sub2["godmode/setup/setup&amp;section=net"]["refr"] = 0;
		}
	}



	if ($config['activate_gis'])
		$sub2["godmode/setup/gis"]["text"] = __('Map conections GIS');
	
	$sub["general"]["sub2"] = $sub2;
	$sub["godmode/setup/os"]["text"] = __('Edit OS');
	$sub["godmode/setup/license"]["text"] = __('License');

	enterprise_hook ('skins_submenu');

	$menu_godmode["gsetup"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "PM")) {
	$menu_godmode["gextensions"]["text"] = __('Admin tools');
	$menu_godmode["gextensions"]["sec2"] = "godmode/extensions";
	$menu_godmode["gextensions"]["id"] = "god-extensions";

	$sub = array ();
	// Audit //meter en extensiones
	$sub["godmode/admin_access_logs"]["text"] = __('System audit log');
	$sub["godmode/setup/links"]["text"] = __('Links');
	$sub["godmode/update_manager/update_manager"]["text"] = __('Update manager');
	$sub["gextmaneger"]["sub2"] = $sub2;
	if (check_acl ($config['id_user'], 0, "DM")) {
		$sub["gdbman"]["text"] = __('DB maintenance');
		$sub["gdbman"]["type"] = "direct";
		$sub["gdbman"]["subtype"] = "nolink";
		$sub2 = array ();
		$sub2["godmode/db/db_info"]["text"] = __('DB information');
		$sub2["godmode/db/db_purge"]["text"] = __('Database purge');
		$sub2["godmode/db/db_refine"]["text"] = __('Database debug');
		$sub2["godmode/db/db_audit"]["text"] = __('Database audit');
		$sub2["godmode/db/db_event"]["text"] = __('Database event');

		$sub["gdbman"]["sub2"] = $sub2;
	}
	$sub["extras/pandora_diag"]["text"] = __('Diagnostic info');
	$sub["godmode/setup/news"]["text"] = __('Site news');
	$sub["godmode/setup/file_manager"]["text"] = __('File manager');
	$menu_godmode["gextensions"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "PM")) {
	
	if (is_array ($config['extensions'])) {
		
		$sub = array ();
		$sub["gextmaneger"]["text"] = __('Extension manager');
		$sub["gextmaneger"]["type"] = "direct";
		$sub["gextmaneger"]["subtype"] = "nolink";
		$sub2 = array ();

		foreach ($config['extensions'] as $extension) {
			//If no godmode_menu is a operation extension
			if ($extension['godmode_menu'] == '') {
				continue;
			}

			$extmenu = $extension['godmode_menu'];

			if ($extmenu["name"] == 'DB interface' && !check_acl ($config['id_user'], 0, "DM")) {
				continue;
			}

			//Check the ACL for this user
			if (! check_acl ($config['id_user'], 0, $extmenu['acl'])) {
				continue;
			}
			
			//Check if was displayed inside other menu
			if ($extension['godmode_menu']["fatherId"] == '') {
				$sub2[$extmenu["sec2"]]["text"] = __($extmenu["name"]);
				$sub2[$extmenu["sec2"]]["refr"] = 0;
			}
			else {
				if (array_key_exists('fatherId',$extmenu)) {
					if (strlen($extmenu['fatherId']) > 0) {
						if (array_key_exists('subfatherId',$extmenu)) {
							if (strlen($extmenu['subfatherId']) > 0) {
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]["text"] = __($extmenu['name']);
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]["refr"] = 0;
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]["icon"] = $extmenu['icon'];
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]["sec"] = 'extensions';
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]["extension"] = true;
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]["enterprise"] = $extension['enterprise'];
								$menu_godmode[$extmenu['fatherId']]['hasExtensions'] = true;
							}
							else {
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["text"] = __($extmenu['name']);
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["refr"] = 0;
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["icon"] = $extmenu['icon'];
								if ($extmenu["name"] == 'Cron jobs') 
									$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["sec"] = 'extensions';
								else
									$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["sec"] = 'gextensions';
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["extension"] = true;
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["enterprise"] = $extension['enterprise'];
								$menu_godmode[$extmenu['fatherId']]['hasExtensions'] = true;
							}
						}
						else {
							$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["text"] = __($extmenu['name']);
							$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["refr"] = 0;
							$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["icon"] = $extmenu['icon'];
							$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["sec"] = 'gextensions';
							$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["extension"] = true;
							$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["enterprise"] = $extension['enterprise'];
							$menu_godmode[$extmenu['fatherId']]['hasExtensions'] = true;
						}
					}
				}
			}
		}
		$sub["gextmaneger"]["sub2"] = $sub2;
		$submenu = array_merge($menu_godmode["gextensions"]["sub"],$sub);
		$menu_godmode["gextensions"]["sub"] = $submenu;
	}
}

if (!$config['pure']) {
	menu_print_menu ($menu_godmode);
}
?>
