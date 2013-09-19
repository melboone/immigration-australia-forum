<?php
/**
 * Board Messages Plugin for MyBB
 * Copyright © 2010 MyBB Mods
 *
 * By: Alan Crisp
 * Website: http://mods.mybb.com/
 * Version: 2.0.1
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('admin_load', 'board_messages_admin');
$plugins->add_hook('admin_config_menu', 'board_messages_admin_config_menu');
$plugins->add_hook('admin_config_action_handler', 'board_messages_admin_config_action_handler');
$plugins->add_hook('admin_config_permissions', 'board_messages_admin_config_permissions');
$plugins->add_hook('global_start', 'board_messages');

function board_messages_info()
{
	global $lang;
	$lang->load('board_messages');

	return array(
		'name'=> $lang->board_messages,
		'description'   => $lang->board_messages_desc,
		'website'       => 'http://mods.mybb.com/',
		'author'        => 'Alan Crisp',
		'authorsite'    => 'http://musicalmidget.com/',
		'version'       => '2.0.1',
		'guid'          => '2759907b61afa0e03b67e658b6b569e3',
		'compatibility' => '14*, 16*'
	);
}

function board_messages_install()
{
	global $db;

	$db->write_query("
		CREATE TABLE ".TABLE_PREFIX."board_messages (
			`mid` int(10) unsigned NOT NULL auto_increment,
			`message` text NOT NULL,
			`class` varchar(255) NOT NULL,
			`global` tinyint(1) NOT NULL default '1',
			`enabled` tinyint(1) NOT NULL default '0',
			PRIMARY KEY (mid)
		) Type=MyISAM;
	");
}

function board_messages_is_installed()
{
	global $db;

	if($db->table_exists('board_messages'))
	{
		return true;
	}

	return false;
}

function board_messages_uninstall()
{
	global $db;
	$db->drop_table('board_messages');
}

function board_messages_activate()
{
	global $db;

	$stylesheet = '.board_message {
	background: #EFEFEF;
	color: #333333;
	border-top: 2px solid #D4D4D4;
	border-bottom: 2px solid #D4D4D4;
	padding: 5px;
	margin-top: 10px;
}';

	$new_stylesheet = array(
		'name'         => 'board_messages.css',
		'tid'          => 1,
		'attachedto'   => '',
		'stylesheet'   => $stylesheet,
		'lastmodified' => TIME_NOW
	);

	$sid = $db->insert_query('themestylesheets', $new_stylesheet);
	$db->update_query('themestylesheets', array('cachefile' => "css.php?stylesheet={$sid}"), "sid='{$sid}'", 1);

	$query = $db->simple_select('themes', 'tid');
	while($theme = $db->fetch_array($query))
	{
		require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
		update_theme_stylesheet_list($theme['tid']);
	}

	require MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('header', '#<navigation>#', "<navigation>\n\t\t\t{\$board_messages}");
	
	change_admin_permission('config', 'board_messages', 0);
}

function board_messages_deactivate()
{
	global $db;

	$db->delete_query('themestylesheets', "name='board_messages.css'");

	$query = $db->simple_select('themes', 'tid');
	while($theme = $db->fetch_array($query))
	{
		require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
		update_theme_stylesheet_list($theme['tid']);
	}

	require MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('header', '#\n\t\t\t{\$board_messages}#', '', 0);

	change_admin_permission('config', 'board_messages', -1);
}

function board_messages_admin_config_menu($sub_menu)
{
    global $lang;
    $lang->load('board_messages');

    $sub_menu[] = array('id' => 'board_messages', 'title' => $lang->board_messages, 'link' => 'index.php?module=config/board_messages');
    return $sub_menu;
}

function board_messages_admin_config_action_handler($actions)
{
    $actions['board_messages'] = array('active' => 'board_messages', 'file' => 'board_messages');
    return $actions;
} 

function board_messages_admin_config_permissions($admin_permissions)
{
    global $lang;
    $admin_permissions['board_messages'] = $lang->can_manage_board_messages;
    return $admin_permissions;
}

function board_messages_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file;

	if($run_module == 'config' && $action_file == 'board_messages')
	{
		$page->add_breadcrumb_item($lang->board_messages, 'index.php?module=config/board_messages');

		if($mybb->input['action'] == 'add')
		{
			if($mybb->request_method == 'post')
			{
				if(!trim($mybb->input['message']))
				{
					$errors[] = $lang->error_no_message;
				}

				if(!$errors)
				{
					$new_message = array(
						'message' => $db->escape_string($mybb->input['message']),
						'class'   => $db->escape_string($mybb->input['class']),
						'global'  => intval($mybb->input['global']),
						'enabled' => intval($mybb->input['enabled'])
					);

					$mid = $db->insert_query('board_messages', $new_message);

					log_admin_action($mid);

					flash_message($lang->success_message_saved, 'success');
					admin_redirect('index.php?module=config/board_messages');
				}
			}

			$page->add_breadcrumb_item($lang->add_message);
			$page->output_header($lang->board_messages.' - '.$lang->add_message);

			$sub_tabs['manage_messages'] = array(
				'title' => $lang->board_messages,
				'link'  => 'index.php?module=config/board_messages',
			);

			$sub_tabs['add_message'] = array(
				'title'       => $lang->add_message,
				'link'        => 'index.php?module=config/board_messages&amp;action=add',
				'description' => $lang->add_message_desc
			);

			$page->output_nav_tabs($sub_tabs, 'add_message');

			if($errors)
			{
				$page->output_inline_error($errors);
			}

			$form = new Form('index.php?module=config/board_messages&amp;action=add', 'post', 'add');
			$form_container = new FormContainer($lang->add_message);
			$form_container->output_row($lang->message.' <em>*</em>', $lang->message_desc, $form->generate_text_area('message', $mybb->input['message']));
			$form_container->output_row($lang->class, $lang->class_desc, $form->generate_text_box('class', $mybb->input['class']));
			$form_container->output_row($lang->display_global.' <em>*</em>', $lang->display_global_desc, $form->generate_yes_no_radio('global', $mybb->input['global'], true));
			$form_container->output_row($lang->enabled.' <em>*</em>', $lang->enabled_desc, $form->generate_yes_no_radio('enabled', $mybb->input['enabled'], true));
			$form_container->end();

			$buttons[] = $form->generate_submit_button($lang->save_message);

			$form->output_submit_wrapper($buttons);

			$form->end();

			$page->output_footer();
		}

		if($mybb->input['action'] == 'edit')
		{
			$query = $db->simple_select('board_messages', '*', "mid='".intval($mybb->input['mid'])."'");
			$message = $db->fetch_array($query);

			if(!$message['mid'])
			{
				flash_message($lang->error_invalid_message, 'error');
				admin_redirect('index.php?module=config/board_messages');
			}

			if($mybb->request_method == 'post')
			{
				if(!trim($mybb->input['message']))
				{
					$errors[] = $lang->error_no_message;
				}

				if(!$errors)
				{
					$message = array(
						'message' => $db->escape_string($mybb->input['message']),
						'class'   => $db->escape_string($mybb->input['class']),
						'global'  => intval($mybb->input['global']),
						'enabled' => intval($mybb->input['enabled'])
					);

					$db->update_query('board_messages', $message, "mid='".intval($mybb->input['mid'])."'");

					log_admin_action(intval($mybb->input['mid']));

					flash_message($lang->success_message_saved, 'success');
					admin_redirect('index.php?module=config/board_messages');
				}
			}

			$page->add_breadcrumb_item($lang->edit_message);
			$page->output_header($lang->board_messages.' - '.$lang->edit_message);

			$sub_tabs['edit_message'] = array(
				'title'       => $lang->edit_message,
				'link'        => 'index.php?module=config/board_messages',
				'description' => $lang->edit_message_desc
			);

			$page->output_nav_tabs($sub_tabs, 'edit_message');

			if($errors)
			{
				$page->output_inline_error($errors);
			}
			else
			{
				$mybb->input = $message;
			}

			$form = new Form('index.php?module=config/board_messages&amp;action=edit', 'post', 'edit');
			echo $form->generate_hidden_field('mid', $message['mid']);

			$form_container = new FormContainer($lang->edit_message);
			$form_container->output_row($lang->message.' <em>*</em>', $lang->message_desc, $form->generate_text_area('message', $mybb->input['message']));
			$form_container->output_row($lang->class, $lang->class_desc, $form->generate_text_box('class', $mybb->input['class']));
			$form_container->output_row($lang->display_global.' <em>*</em>', $lang->display_global_desc, $form->generate_yes_no_radio('global', $mybb->input['global'], true));
			$form_container->output_row($lang->enabled.' <em>*</em>', $lang->enabled_desc, $form->generate_yes_no_radio('enabled', $mybb->input['enabled'], true));
			$form_container->end();

			$buttons[] = $form->generate_submit_button($lang->save_message);
			$buttons[] = $form->generate_reset_button($lang->reset);

			$form->output_submit_wrapper($buttons);

			$form->end();

			$page->output_footer();
		}

		if($mybb->input['action'] == 'delete')
		{
			$query = $db->simple_select('board_messages', '*', "mid='".intval($mybb->input['mid'])."'");
			$message = $db->fetch_array($query);

			if(!$message['mid'])
			{
				flash_message($lang->error_invalid_message, 'error');
				admin_redirect('index.php?module=config/board_messages');
			}

			if($mybb->input['no'])
			{
				admin_redirect('index.php?module=config/board_messages');
			}

			if($mybb->request_method == 'post')
			{
				$db->delete_query('board_messages', "mid='{$message['mid']}'");

				log_admin_action($message['mid']);

				flash_message($lang->success_message_deleted, 'success');
				admin_redirect('index.php?module=config/board_messages');
			}
			else
			{
				$page->output_confirm_action("index.php?module=config/board_messages&amp;action=delete&amp;mid={$message['mid']}", $lang->confirm_message_deletion);
			}
		}

		if(!$mybb->input['action'])
		{
			$page->output_header($lang->board_messages);

			$sub_tabs['manage_messages'] = array(
				'title'       => $lang->board_messages,
				'link'        => 'index.php?module=config/board_messages',
				'description' => $lang->manage_messages_desc
			);

			$sub_tabs['add_message'] = array(
				'title' => $lang->add_message,
				'link'  => 'index.php?module=config/board_messages&amp;action=add'
			);

			$page->output_nav_tabs($sub_tabs, 'manage_messages');

			$table = new Table;
			$table->construct_header($lang->message, array('colspan' => 2));
			$table->construct_header($lang->location, array('class' => "align_center"));
			$table->construct_header($lang->controls, array('class' => "align_center", 'colspan' => 2));

			$query = $db->simple_select('board_messages', '*');
			while($message = $db->fetch_array($query))
			{
				if($message['enabled'] == 1)
				{
					$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_on.gif\" alt=\"(Enabled)\" title=\"Enabled\"  style=\"vertical-align: middle;\" />";
				}
				else
				{
					$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_off.gif\" alt=\"(Disabled)\" title=\"Disabled\"  style=\"vertical-align: middle;\" />";
				}

				if($message['global'] != 1)
				{
					$location = $lang->index_only;
				}
				else
				{
					$location = $lang->global;
				}

				$table->construct_cell($icon, array('width' => 1));
				$table->construct_cell($message['message'], array('width' => '65%'));
				$table->construct_cell($location, array('class' => "align_center"));
				$table->construct_cell("<a href=\"index.php?module=config/board_messages&amp;action=edit&amp;mid={$message['mid']}\">{$lang->edit}</a>", array("class" => "align_center"));
				$table->construct_cell("<a href=\"index.php?module=config/board_messages&amp;action=delete&amp;mid={$message['mid']}&amp;my_post_key={$mybb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_message_deletion}')\">{$lang->delete}</a>", array("class" => "align_center"));
				$table->construct_row();
			}

			if($table->num_rows() == 0)
			{
				$table->construct_cell($lang->no_board_messages, array('colspan' => 5));
				$table->construct_row();
			}

			$table->output($lang->board_messages);

			$page->output_footer();
		}

		exit;
	}
}

function board_messages()
{
	global $db, $templates, $board_messages, $current_page;

	$board_messages = '';
	$query = $db->simple_select('board_messages', '*', "enabled='1'");
	while($message = $db->fetch_array($query))
	{
		if($message['global'] != 0 || $current_page == 'index.php')
		{
			if(!$message['class'])
			{
				$message['class'] = 'board_message';
			}

			$board_messages .= '<div class="'.$message['class'].'">'.$message['message'].'</div>';
		}
	}
}
?>
