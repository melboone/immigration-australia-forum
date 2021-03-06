<?php
/**
 * This file is part of Unanswered Posts plugin for MyBB.
 * Copyright (C) 2010-2013 Lukasz Tkacz <lukasamd@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */ 
 
/**
 * Disallow direct access to this file for security reasons
 * 
 */
if (!defined("IN_MYBB")) exit;

/**
 * Plugin Installator Class
 * 
 */
class unansweredPostsInstaller
{

    public static function install()
    {
        global $db, $lang, $mybb;
        self::uninstall();

        $result = $db->simple_select('settinggroups', 'MAX(disporder) AS max_disporder');
        $max_disporder = $db->fetch_field($result, 'max_disporder');
        $disporder = 1;

        $settings_group = array(
            'gid' => 'NULL',
            'name' => 'unansweredPosts',
            'title' => $db->escape_string($lang->unansweredPostsName),
            'description' => $db->escape_string($lang->unansweredPostsGroupDesc),
            'disporder' => $max_disporder + 1,
            'isdefault' => '0'
        );
        $db->insert_query('settinggroups', $settings_group);
        $gid = (int) $db->insert_id();

        $setting = array(
            'sid' => 'NULL',
            'name' => 'unansweredPostsExceptions',
            'title' => $db->escape_string($lang->unansweredPostsExceptions),
            'description' => $db->escape_string($lang->unansweredPostsExceptionsDesc),
            'optionscode' => 'text',
            'value' => '',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);

        $setting = array(
            'sid' => 'NULL',
            'name' => 'unansweredPostsStatusCounter',
            'title' => $db->escape_string($lang->unansweredPostsStatusCounter),
            'description' => $db->escape_string($lang->unansweredPostsStatusCounterDesc),
            'optionscode' => 'onoff',
            'value' => '1',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);

        $setting = array(
            'sid' => 'NULL',
            'name' => 'unansweredPostsStatusCounterHide',
            'title' => $db->escape_string($lang->unansweredPostsStatusCounterHide),
            'description' => $db->escape_string($lang->unansweredPostsStatusCounterHideDesc),
            'optionscode' => 'onoff',
            'value' => '0',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);

        $setting = array(
            'sid' => 'NULL',
            'name' => 'unansweredPostsCounterPages',
            'title' => $db->escape_string($lang->unansweredPostsCounterPages),
            'description' => $db->escape_string($lang->unansweredPostsCounterPagesDesc),
            'optionscode' => 'textarea',
            'value' => 'index.php',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);
    }

    public static function uninstall()
    {
        global $db;

        $result = $db->simple_select('settinggroups', 'gid', "name = 'unansweredPosts'");
        $gid = (int) $db->fetch_field($result, "gid");
        
        if ($gid > 0)
        {
            $db->delete_query('settings', "gid = '{$gid}'");
        }
        $db->delete_query('settinggroups', "gid = '{$gid}'");
    }

}
