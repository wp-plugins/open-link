<?php
/*
Plugin Name: Open Link
Version: 1.0.1
Plugin URI: http://www.xiaomac.com/201312193.html
Description: Outputs your Blogroll links to a Page or Post. use <code>[wp-openlink]</code> then you can get all your Wordpress links/Blogrolls. 
Author: Afly
Author URI: http://www.xiaomac.com/
Stable tag: 1.0.2
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Text Domain: open-link
Domain Path: /lang
*/

//init
add_action('admin_init', 'open_link_init', 1);
function open_link_init() {
	load_plugin_textdomain( 'open-link', '', dirname( plugin_basename( __FILE__ ) ) . '/lang' );
}

//tracker
add_action('init', 'open_link_track_init', 1);
function open_link_track_init() {
	if( isset($_GET['open_link_id']) ){
		$link_id = intval($_GET['open_link_id']);
		if($link_id<1) exit();
	    global $wpdb;
	    $sql = "update ".$wpdb->links." set link_rating = link_rating + 1 where link_id = " . $link_id . ";";
	    $wpdb->query($sql);
		exit();
	}
}

//enable link manager and shortcode: [wp-openlink]
add_filter( 'pre_option_link_manager_enabled', '__return_true' );
add_shortcode('wp-openlink', 'open_list_bookmarks');

function open_list_bookmarks($args=''){
	$defaults = array(
		'orderby' => 'rating','order' => 'DESC','limit' => -1,'category' => '','exclude_category' => '',
		'category_name' => '','category_orderby' => 'id','category_order' => 'ASC'
	);
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	$output = '<style>.link_thumbs {font-size:12px;text-align:center;line-height:175px;color:#333;height:68px;width:116px;margin-right: 25px;margin-bottom:45px;padding:2px;border-radius:3px;box-shadow: 0 1px 4px rgba(0,0,0, 0.2);display:inline-block;display:-moz-inline-stack;zoom:1;*display:inline;}</style>';
	$output .= '<script>function _clickTrack(id){new Image().src="?open_link_id="+id;}</script>';
	$cats = get_terms('link_category', array('name__like' => $category_name, 'include' => $category, 'exclude' => $exclude_category, 'orderby' => $category_orderby, 'order' => $category_order, 'hierarchical' => 0));
	foreach ( (array) $cats as $cat ) {
		$params = array_merge($r, array('category'=>$cat->term_id));
		$bookmarks = get_bookmarks($params);
		if ( empty($bookmarks) )
			continue;
		$output .= '<h4 class="link_cate_title">'.$cat->name.'</h4>';
		$output .= '<div id="link_cate_'.$cat->term_id.'">'. open_walk_bookmarks($bookmarks, $r) . '</div>';
	}
	return $output;
}

function open_walk_bookmarks($bookmarks, $args=''){
	$output = '';
	foreach ( (array) $bookmarks as $bookmark ) {
		if ( !isset($bookmark->recently_updated) ) $bookmark->recently_updated = false;
		$output .= "<span>";
		$the_link = "#";
		if ( !empty($bookmark->link_url) ) $the_link = esc_url($bookmark->link_url);
		$desc = '['.$bookmark->link_rating.'] '.esc_attr(sanitize_bookmark_field('link_description', $bookmark->link_description, $bookmark->link_id, 'display'));
		$name = esc_attr(sanitize_bookmark_field('link_name', $bookmark->link_name, $bookmark->link_id, 'display'));	
		$output .= "<a onclick=\"_clickTrack('$bookmark->link_id');return true;\" class=\"link_thumbs\" target=\"_blank\" style=\"background:url(http://free.pagepeeker.com/v2/thumbs.php?size=s&url=$the_link)\" href=\"$the_link\" title=\"$desc\">$name</a>";
		$output .= "</span>";
	}
	return $output;
}

//you may want orderby: updated
add_action('edit_link', 'open_link_edit');
add_action('add_link', 'open_link_edit');
function open_link_edit($link_ID) {
    global $wpdb;
    $sql = "update ".$wpdb->links." set link_updated = NOW() where link_id = " . $link_ID . ";";
    $wpdb->query($sql);
}

//disabled the default rating select, use it for click_count
add_action('admin_menu', 'open_link_meta_box');
function open_link_meta_box() {
	remove_meta_box('linkadvanceddiv', 'link', 'normal');
    add_meta_box('open_link_box',__('Open Link Box','open-link'),'open_link_meta_box_info','link','side');
}
function open_link_meta_box_info($link) {
    if (!empty($link->link_id)){
    	echo sprintf(__('Last updated: %s'),$link->link_updated)."<br>".sprintf(__('Clicked: %s','open-link'),$link->link_rating);
	}else{
		echo __('None');
	}
}

?>