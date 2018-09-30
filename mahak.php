<?php
/*
Plugin Name: Mahak
Plugin URI: http://winax.ir
Description: افزونه به روز رسانی اطلاعات از سیستم محک Telegram:@Amirsoltanik
Version: 1.0
Author: Amir soltani
Author URI: http://winax.ir
License: A "Slug" license name e.g. GPL2
*/
require_once ("Soltani.php");


add_action( 'admin_menu','mahakmenu');
function mahakmenu(){
    add_menu_page( 'Mahak', 'Mahak', 'manage_options', 'Mahak', 'form_function' );
}

function form_function(){
    wp_enqueue_style( 'bootstap', plugins_url('/css/style.css', __FILE__) );
    wp_enqueue_style( 'mahakcss', plugins_url('/style/boot.css', __FILE__) );
 $mahak = new Soltani();
 $mahak->Getform();
 $mahak->Setform();

 
}
add_action('init', 'your_login_function',3600);
function your_login_function()
{
    $mahak = new Soltani();
    $mahak->run();
}
register_activation_hook( __FILE__, 'activate' );
function activate()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'mahak';
    $sql = "CREATE TABLE $table_name ( `id` INT(6) NOT NULL AUTO_INCREMENT,
`time` INT(6) NOT NULL,
`syncid` INT(6) NOT NULL
,  `username` VARCHAR(50) NOT NULL 
,  `password` VARCHAR(50) NOT NULL 
,  `userid` INT(6) NOT NULL 
,  `lastime` DECIMAL NOT NULL 
,  `data` LONGTEXT CHARACTER SET utf8 COLLATE utf8_persian_ci  NULL 
,    PRIMARY KEY  (`id`)) ENGINE = InnoDB CHARSET=utf8 COLLATE utf8_persian_ci;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
        $wpdb->insert($table_name, ["id"=>1 , "time"=>1 , "syncid"=>0,"username"=>'soltani', "password"=>123, "userid"=>1, "lastime"=>time()]);
}