<?php

/**
 * Plugin Name: Multisite Cloner
 * Plugin URI: https://wordpress.org/plugins/multisite-cloner
 * Description: When creating a new blog on WordPress Multisite, copies all the posts, settings and files, from a selected blog into the new one.
 * Version: 0.2.0
 * Author: Manuel Razzari, Patricio Tarantino
 * Author URI: http://tipit.net
 * License: License: GPL2+
**/

/*  Copyright 2014 Tipit.net  (email: manuel@tipit.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

!defined( 'ABSPATH' ) AND exit("Oops!");

add_action('plugins_loaded', array( MultiSiteCloner::get_instance(), 'plugin_setup' ));

class MultiSiteCloner {

    protected static $instance = NULL;
    
    function __construct() {
        register_activation_hook( __FILE__, array( &$this, 'install_multisite_cloner' ) );
        add_action( 'admin_init', array( &$this, 'init_multisite_cloner' ) );
    }

    private function get_main_blog_id() {
        global $current_site;
        global $wpdb;
        return $wpdb->get_var ( $wpdb->prepare ( "SELECT `blog_id` FROM `$wpdb->blogs` WHERE `domain` = '%s' AND `path` = '%s' ORDER BY `blog_id` ASC LIMIT 1", $current_site->domain, $current_site->path ) );
    }

    private function get_first_blog_id() {
        global $wpdb;
        return $wpdb->get_var ( "SELECT `blog_id` FROM `$wpdb->blogs` ORDER BY `blog_id` ASC LIMIT 1,1" );
    }

    
    public static function get_instance() {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }
  
    function install_multisite_cloner() {
        if( !is_multisite() )
            wp_die(
               'The whole point of this plugin is to clone blogs within a Multisite network. It can\'t be installed on a single site',
               'Error',
               array(
                   'response' => 500,
                   'back_link' => true
               )
           );
    }
    public function plugin_setup() {     
        add_action( 'network_admin_menu', array($this, 'wp_mu_clone_page_link') );
        add_action( 'wpmu_new_blog', array($this, 'set_new_blog'), 1, 1);
        add_action( 'admin_footer', array($this, 'clone_input_admin') );
        add_filter( 'manage_sites_action_links', array($this, 'add_clone_link'), null, 2 );
        $plugin = plugin_basename(__FILE__);
        add_filter( "network_admin_plugin_action_links_$plugin", array(&$this, 'cloner_settings_link'), 4, 4 );
    }
  
    function init_multisite_cloner() {
        add_option('wpmuclone_default_blog', $this->get_first_blog_id() );
        register_setting( 'default', 'wpmuclone_default_blog' ); 
    }

    function cloner_settings_link($links, $plugin_file, $plugin_data, $context) {
      $settings_link = sprintf( '<a href="settings.php?page=wp_mu_clone_settings">%s</a>', __('Settings'));
      array_unshift($links, $settings_link); 
      return $links; 
    }

    function wp_mu_clone_settings() {
        if ( !empty( $_POST[ 'action' ] ) ) {
            update_option('wpmuclone_default_blog', $_POST['wpmuclone_default_blog']);
            update_option('wpmuclone_copy_users', isset($_POST['wpmuclone_copy_users']) );
        }

        $main_blog_id = $this->get_main_blog_id();

        ?>
        <div class="wrap">
            <style>
                .settings_page_wp_mu_clone_settings .wrap {
                    max-width: 600px;
                }
                #wpbody {
                    background: url(<?php echo plugin_dir_url( __FILE__) ?>/flying-dolly.png) 96% 5% no-repeat;
                    background-size: 200px;
                }
                @media screen and (max-width: 600px) {
                    #wpbody {
                        background-image: none;
                    }
                }
            </style>
            <h2>Multisite Cloner Settings</h2>
            <form method="post">
                <?php settings_fields( 'default' ); ?>
                <h3>Default blog to be cloned</h3>
                <p>All the data from this blog will be copied into new blogs.</p>
                <p>This includes settings, posts and other content, theme options, and uploaded files.</p>
                <p>Note: the main site in your network (id = <?php echo $main_blog_id ?>) can't be cloned, as it contains many DB tables, assets and sensitive information that shouldn't be replicated to other blogs.</p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="wpmuclone_default_blog">Default Blog</label></th>
                        <td>
                        <select name="wpmuclone_default_blog" id="wpmuclone_default_blog">
                            <option value="0">No default blog</option>
                        <?php
                        $blog_list = wp_get_sites(array('limit' => 0 ));
                        $blog_list_counter = 0;
                        foreach ($blog_list as $blog) { 
                            if($blog['blog_id'] != $main_blog_id ){
                                $blog_list_counter++;
                                ?>
                                <option value="<?php echo $blog['blog_id'];?>" <?php if (get_option('wpmuclone_default_blog') == $blog['blog_id'] ){ ?> selected <?php } ?>><?php echo get_blog_details( $blog['blog_id'], 'blogname' )->blogname ; ?> ( <?php echo $blog['domain']; ?>)</option>
                                <?php
                            }
                        }
                        ?>                   
                        </select>
                        <?php if (!$blog_list_counter){ ?>
                            <div class="error">
                                <p>The plugin won&rsquo;t work until you have created a site in your network. (The main site should never be cloned.) </p>
                            </div>
                        <?php } ?>
                        </td>
                    </tr>
                </table>
                <table class="form-table">
                    <h3>Global clone settings</h3>
                    <p>These settings apply to the default blog selected above, and to blogs copied using the &ldquo;Clone&rdquo; link on the &ldquo;All Sites&rdquo; network admin page.</p>
                    <tr valign="top">
                        <th scope="row"><label for="wpmuclone_copy_users">Copy users too</label></th>
                        <td>
                        <input type="checkbox" name="wpmuclone_copy_users" id="wpmuclone_copy_users" <?php if (get_option('wpmuclone_copy_users') ){ ?> checked <?php } ?> >
                        <p class="description">Check this if you want to copy all users from the source blog into the new cloned blog.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    function wp_mu_clone_page_link() {
        add_submenu_page('settings.php', 'Multisite Cloner', 'Multisite Cloner', 'manage_options', 'wp_mu_clone_settings', array($this, 'wp_mu_clone_settings') );
    }

    function set_new_blog( $blog_id=false ) {
        global $wpdb;

        if ( isset($_POST['wpmuclone_default_blog']) ) {
            $id_default_blog = intval($_POST['wpmuclone_default_blog']); 
        } else {
            $id_default_blog = get_option('wpmuclone_default_blog'); 
        }
        $copy_users = get_option('wpmuclone_copy_users');

        if (!$id_default_blog) { return false; }

        $old_url = get_site_url($id_default_blog);
        
        switch_to_blog( $blog_id );

        $new_url = get_site_url();
        $new_name = get_bloginfo('title','raw');
        $admin_email = get_bloginfo('admin_email','raw');

        $prefix = $wpdb->base_prefix;
        $prefix_escaped = str_replace('_','\_',$prefix);

        // List all tables for the default blog,
        $tables_q = $wpdb->get_results("SHOW TABLES LIKE '" . $prefix_escaped . $id_default_blog . "\_%'");

        foreach($tables_q as $table){
            $in_array = get_object_vars($table);
            $old_table_name = current($in_array);
            $tables[] = str_replace($prefix . $id_default_blog . '_', '', $old_table_name);
            unset($in_array);
        }

        // Replace tables from the new blog with the ones from the default blog
        foreach($tables as $table){
            $new_table = $prefix . $blog_id . '_' . $table;
            $old_table = $prefix . $id_default_blog . '_' . $table;

            unset($queries);
            $queries = array();

            $queries[] = "DROP TABLE IF EXISTS " . $new_table ;
            $queries[] = "CREATE TABLE " . $new_table . " LIKE " . $old_table;
            $queries[] = "INSERT INTO " . $new_table . " SELECT * FROM " . $old_table;

            foreach($queries as $query){
                $wpdb->query($query);
            }

            $new_tables[] = $new_table;
        }

        $wp_uploads_dir = wp_upload_dir();
        $base_dir = $wp_uploads_dir['basedir'];
        $relative_base_dir = str_ireplace(get_home_path(), '', $base_dir);

        // I need to get the previous folder before the id, just in case this is different to 'sites'
        $dirs_relative_base_dirs = explode('/',$relative_base_dir);
        $sites_dir = $dirs_relative_base_dirs[count($dirs_relative_base_dirs)-2];

        $old_uploads = str_ireplace('/'.$sites_dir.'/'.$blog_id, '/'.$sites_dir.'/'.$id_default_blog, $relative_base_dir);
        $new_uploads = $relative_base_dir;

        // Replace URLs and paths in the DB
        
        $old_url = str_ireplace(array('http://', 'https://'), '://', $old_url);
        $new_url = str_ireplace(array('http://', 'https://'), '://', $new_url);


        cloner_db_replacer( array($old_url,$old_uploads), array($new_url,$new_uploads), $new_tables);

        // Update Title
        update_option('blogname',$new_name);

        // Update Email
        update_option('admin_email',$admin_email);

        // Copy Files
        $old_uploads = str_ireplace('/'.$sites_dir.'/'.$blog_id, '/'.$sites_dir.'/'.$id_default_blog, $base_dir);
        $new_uploads = $base_dir;;

        cloner_recurse_copy($old_uploads, $new_uploads);

        // User Roles
        $user_roles_sql = "UPDATE $prefix" . $blog_id . "_options SET option_name = '$prefix" . $blog_id . "_user_roles' WHERE option_name = '$prefix" . $id_default_blog . "_user_roles';";
        $wpdb->query($user_roles_sql);

        // Copy users
        if ( $copy_users ){
            $users = get_users('blog_id='.$id_default_blog);

            function user_array_map( $a ){ return $a[0]; }

            foreach($users as $user){
                   
                $all_meta = array_map( 'user_array_map', get_user_meta( $user->ID ) );

                foreach ($all_meta as $metakey => $metavalue) {
                    $prefix_len = strlen($prefix . $id_default_blog);

                    $metakey_prefix = substr($metakey, 0, $prefix_len);
                    if($metakey_prefix == $prefix . $id_default_blog) {
                        $raw_meta_name = substr($metakey,$prefix_len);
                        update_user_meta( $user->ID, $prefix . $blog_id . $raw_meta_name, maybe_unserialize($metavalue) );
                    }
                }

            }
        }

        // Restores main blog
        switch_to_blog( get_option('wpmuclone_default_blog') );
    }

    function clone_input_admin() {
        if( 'site-new-network' != get_current_screen()->base ){
            return;
        }

        $dropdown = '<input name="wpmuclone_default_blog" id="wpmuclone_default_blog" type="hidden" value="';

        if ( isset($_GET['clone_from']) ) {
            $dropdown .= intval($_GET['clone_from']);
        } else {
            $dropdown .= get_option('wpmuclone_default_blog') ;
        }

        $dropdown .= '">';

echo <<<HTML
    <script type="text/javascript">
    jQuery(document).ready( function($) {
        $('$dropdown').appendTo('.form-table');
    });
    </script>
HTML;

    }

    function add_clone_link( $actions, $blog_id ) {     
        $main_blog_id = $this->get_main_blog_id();
        if($main_blog_id != $blog_id):
            $actions['clone'] = '<a href="'. network_admin_url( 'site-new.php' ).'?clone_from=' . $blog_id . '">Clone</a>';     
        endif;
        return $actions;
    }


  
} // end class
new MultiSiteCloner();



/* 
SEARCH AND REPLACE for WP DBs, taking into account serialized arrays commonly used by plugin options.
Adapted from the excellent "Search Replace DB" tool by Robert O'Rourke and David Coveney.
https://github.com/interconnectit/Search-Replace-DB/
*/
function cloner_recursive_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false ) {

    // some unseriliased data cannot be re-serialised eg. SimpleXMLElements
    try {

        if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {
            $data = cloner_recursive_unserialize_replace( $from, $to, $unserialized, true );
        }

        elseif ( is_array( $data ) ) {
            $_tmp = array( );
            foreach ( $data as $key => $value ) {
                $_tmp[ $key ] = cloner_recursive_unserialize_replace( $from, $to, $value, false );
            }

            $data = $_tmp;
            unset( $_tmp );
        }

        // Submitted by Tina Matter
        elseif ( is_object( $data ) ) {
            $dataClass = get_class( $data );
            $_tmp = new $dataClass( );
            foreach ( $data as $key => $value ) {
                $_tmp->$key = cloner_recursive_unserialize_replace( $from, $to, $value, false );
            }

            $data = $_tmp;
            unset( $_tmp );
        }

        else {
            if ( is_string( $data ) )
                $data = str_replace( $from, $to, $data );
        }

        if ( $serialised )
            return serialize( $data );

    } catch( Exception $error ) {

    }

    return $data;
}


function cloner_db_replacer( $search = '', $replace = '', $tables = array( ) ) {

    global $wpdb;

    $guid = 1;
    $exclude_cols = array();

    if ( is_array( $tables ) && ! empty( $tables ) ) {
        foreach( $tables as $table ) {

            $columns = array( );

            // Get a list of columns in this table
            $fields = $wpdb->query( 'DESCRIBE ' . $table );
            if ( ! $fields ) {
                continue;
            }
            
            $columns_gr = $wpdb->get_results( 'DESCRIBE ' . $table );

            foreach($columns_gr as $column){
                $columns[ $column->Field ] = $column->Key == 'PRI' ? true : false;
            }

            // Count the number of rows we have in the table if large we'll split into blocks, This is a mod from Simon Wheatley
            $row_count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $table );          
            if ( $row_count == 0 )
                continue;

            $page_size = 50000;
            $pages = ceil( $row_count / $page_size );

            for( $page = 0; $page < $pages; $page++ ) {

                $current_row = 0;
                $start = $page * $page_size;
                $end = $start + $page_size;
                // Grab the content of the table
                $data = $wpdb->query( sprintf( 'SELECT * FROM %s LIMIT %d, %d', $table, $start, $end ) );

                $rows_gr = $wpdb->get_results( sprintf( 'SELECT * FROM %s LIMIT %d, %d', $table, $start, $end ) );

                foreach($rows_gr as $row) {

                    $current_row++;

                    $update_sql = array( );
                    $where_sql = array( );
                    $upd = false;

                    foreach( $columns as $column => $primary_key ) {
                        if ( $guid == 1 && in_array( $column, $exclude_cols ) )
                            continue;

                        $edited_data = $data_to_fix = $row->$column;

                        // Run a search replace on the data that'll respect the serialisation.
                        $edited_data = cloner_recursive_unserialize_replace( $search, $replace, $data_to_fix );

                        // Something was changed
                        if ( $edited_data != $data_to_fix) {
                            $update_sql[] = $column . ' = "' . esc_sql( $edited_data ) . '"';
                            $upd = true;
                        }
                        if ( $primary_key )
                            $where_sql[] = $column . ' = "' . esc_sql( $data_to_fix ) . '"';
                    }

                    if ( $upd && ! empty( $where_sql )) {
                        $sql = 'UPDATE ' . $table . ' SET ' . implode( ', ', $update_sql ) . ' WHERE ' . implode( ' AND ', array_filter( $where_sql ) );
                        $result = $wpdb->query( $sql );   
                    }


                }

            }

        }

    }

}

/* RECURSIVELY COPY a directory.
   By gimmicklessgpt at http://php.net/manual/en/function.copy.php#91010 
   Edited to work with empty dirs: https://wordpress.org/support/topic/pull-request-error-while-copying-a-dir-while-cloning
*/
function cloner_recurse_copy($src, $dst) {
    $dir = opendir($src); 

    // maybe I am not a dir after all.
    if(!$dir || !is_dir($src)) {
        mkdir($src);
    }

    if (!file_exists($dst)) {
        mkdir($dst);
    }

    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                cloner_recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    } 

    closedir($dir);
}


/*
Dejare mi tierra por ti,
dejare mis campos y me ire,
lejos de aqui.

Cruzare llorando el jardin,
y con tus recuerdos partire,
lejos de aqui.
*/
