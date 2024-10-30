<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function mgnf_create_table()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'mgnf_widget';

    $sql = "CREATE TABLE $table_name (
        id int(10) NOT NULL AUTO_INCREMENT,
        wname varchar(255) DEFAULT '' NOT NULL,
        wkey varchar(255) DEFAULT '' NOT NULL,
        wcapabilities varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function mgnf_create_preset_table()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'mgnf_preset';

    $sql = "CREATE TABLE $table_name (
        id int(10) NOT NULL AUTO_INCREMENT,
        wname varchar(255) DEFAULT '' NOT NULL,
        wiframe varchar(1000) DEFAULT '' NOT NULL,
        wdesc varchar(1000) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
