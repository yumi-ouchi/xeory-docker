<?php
/* Plugin Name: WP-PostViews
   Plugin URI: http://lesterchan.net/portfolio/programming/php/
   Description: Enables you to display how many times a post/page had been viewed.
   Version: 1.67
   Author: Lester 'GaMerZ' Chan
   Author URI: http://lesterchan.net
   Text Domain: wp-postviews
* ---------------------------------------- */


/*Copyright 2014  Lester Chan  (email : lesterchan@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
* ---------------------------------------- */


/* Create Text Domain For Translations
* ---------------------------------------- */
add_action( 'plugins_loaded', 'bzb_postviews_textdomain' );
function bzb_postviews_textdomain() {
  load_plugin_textdomain( 'wp-postviews', false, dirname( plugin_basename( __FILE__ ) ) );
}


// ### Function: Post Views Option Menu
// add_action('admin_menu', 'postviews_menu');
// function postviews_menu() {
//   if (function_exists('add_options_page')) {
//     add_options_page(__('PostViews', 'wp-postviews'), __('PostViews', 'wp-postviews'), 'manage_options', 'wp-postviews/postviews-options.php') ;
//   }
// }


/* Function: Calculate Post Views
* ---------------------------------------- */
add_action( 'wp_head', 'bzb_process_postviews' );
function bzb_process_postviews() {
  global $user_ID, $post;
  if( is_int( $post ) ) {
    $post = get_post( $post );
  }
  if( !wp_is_post_revision( $post ) ) {
    if( is_single() || is_page() ) {
      $id = intval( $post->ID );
      $views_options = get_option( 'views_options' );
      if ( !$post_views = get_post_meta( $post->ID, 'views', true ) ) {
        $post_views = 0;
      }
      $should_count = false;

      if(isset($views_options['count']))
      switch( intval( $views_options['count'] ) ) {
        case 0:
          $should_count = true;
          break;
        case 1:
          if(empty( $_COOKIE[USER_COOKIE] ) && intval( $user_ID ) === 0) {
            $should_count = true;
          }
          break;
        case 2:
          if( intval( $user_ID ) > 0 ) {
            $should_count = true;
          }
          break;
      }

      if(isset($views_options['exclude_bots']))
      if( intval( $views_options['exclude_bots'] ) === 1 ) {
        $bots = array
        (
            'Google Bot' => 'googlebot'
          , 'Google Bot' => 'google'
          , 'MSN' => 'msnbot'
          , 'Alex' => 'ia_archiver'
          , 'Lycos' => 'lycos'
          , 'Ask Jeeves' => 'jeeves'
          , 'Altavista' => 'scooter'
          , 'AllTheWeb' => 'fast-webcrawler'
          , 'Inktomi' => 'slurp@inktomi'
          , 'Turnitin.com' => 'turnitinbot'
          , 'Technorati' => 'technorati'
          , 'Yahoo' => 'yahoo'
          , 'Findexa' => 'findexa'
          , 'NextLinks' => 'findlinks'
          , 'Gais' => 'gaisbo'
          , 'WiseNut' => 'zyborg'
          , 'WhoisSource' => 'surveybot'
          , 'Bloglines' => 'bloglines'
          , 'BlogSearch' => 'blogsearch'
          , 'PubSub' => 'pubsub'
          , 'Syndic8' => 'syndic8'
          , 'RadioUserland' => 'userland'
          , 'Gigabot' => 'gigabot'
          , 'Become.com' => 'become.com'
          , 'Baidu' => 'baiduspider'
          , 'so.com' => '360spider'
          , 'Sogou' => 'spider'
          , 'soso.com' => 'sosospider'
          , 'Yandex' => 'yandex'
        );
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        foreach ( $bots as $name => $lookfor ) {
          if ( stristr( $useragent, $lookfor ) !== false ) {
            $should_count = false;
            break;
          }
        }
      }
      if( $should_count && ( ( isset( $views_options['use_ajax'] ) && intval( $views_options['use_ajax'] ) === 0 ) || ( !defined( 'WP_CACHE' ) || !WP_CACHE ) ) ) {
        update_post_meta( $id, 'views', ( $post_views + 1 ) );
      }
    }
  }
}


/* Function: Calculate Post Views With WP_CACHE Enabled
* ---------------------------------------- */
add_action('wp_enqueue_scripts', 'bzb_wp_postview_cache_count_enqueue');
function bzb_wp_postview_cache_count_enqueue() {
  global $user_ID, $post;

  if( !defined( 'WP_CACHE' ) || !WP_CACHE ){
    return;
  }

  $views_options = get_option( 'views_options' );

  if( isset( $views_options['use_ajax'] ) && intval( $views_options['use_ajax'] ) === 0 )
    return;

  if ( !wp_is_post_revision( $post ) && ( is_single() || is_page() ) ) {
    $should_count = false;
    switch( intval( $views_options['count'] ) ) {
      case 0:
        $should_count = true;
        break;
      case 1:
        if ( empty( $_COOKIE[USER_COOKIE] ) && intval( $user_ID ) === 0 ) {
          $should_count = true;
        }
        break;
      case 2:
        if ( intval( $user_ID ) > 0 ) {
          $should_count = true;
        }
        break;
    }
    if ( $should_count ) {
      wp_enqueue_script( 'wp-postviews-cache', get_template_directory_uri() . '/lib/js/postviews-cache.js', array( 'jquery' ), '1.67', true );
      wp_localize_script( 'wp-postviews-cache', 'viewsCacheL10n', array( 'admin_ajax_url' => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ), 'post_id' => intval( $post->ID ) ) );
    }
  }
}


/* Function: Determine If Post Views Should Be Displayed (By: David Potter)
* ---------------------------------------- */
function bzb_should_views_be_displayed($views_options = null) {
  if ( $views_options == null ) {
    $views_options = get_option('views_options');
  }
  $display_option = 0;
  if ( is_home() ) {
    if ( array_key_exists('display_home', $views_options) ) {
      $display_option = $views_options['display_home'];
    }
  } elseif ( is_single() ) {
    if ( array_key_exists('display_single', $views_options) ) {
      $display_option = $views_options['display_single'];
    }
  } elseif ( is_page() ) {
    if ( array_key_exists('display_page', $views_options) ) {
      $display_option = $views_options['display_page'];
    }
  } elseif ( is_archive() ) {
    if ( array_key_exists('display_archive', $views_options) ) {
      $display_option = $views_options['display_archive'];
    }
  } elseif ( is_search() ) {
    if ( array_key_exists('display_search', $views_options) ) {
      $display_option = $views_options['display_search'];
    }
  } else {
    if ( array_key_exists('display_other', $views_options) ) {
      $display_option = $views_options['display_other'];
    }
  }
  return (($display_option == 0) || (($display_option == 1) && is_user_logged_in()));
}


/* Function: Display The Post Views
* ---------------------------------------- */
function bzb_the_views($display = true, $prefix = '', $postfix = '', $always = false) {
  $post_views = intval(post_custom('views'));
  $views_options = get_option('views_options');
  if ( $always || bzb_should_views_be_displayed($views_options) ) {
    $output = $prefix.str_replace('%VIEW_COUNT%', number_format_i18n($post_views), $views_options['template']).$postfix;
    //$output = $prefix.str_replace('%VIEW_COUNT%', number_format_i18n($post_views), $views_options['template']).$postfix;
    if( $display ) {
      echo apply_filters('bzb_the_views', number_format_i18n($post_views));
//      echo apply_filters('bzb_the_views', $output);
    } else {
      return apply_filters('bzb_the_views', $output);
      //return apply_filters('the_views', $output);
    }
  }elseif ( !$display ) {
    return '';
  }
}


/* Function: Display Most Viewed Page/Post Views
* ---------------------------------------- */
if( !function_exists('bzb_get_most_viewed') ) {
  function bzb_get_most_viewed($mode = '', $limit = 10, $chars = 0, $display = true, $disp_views = 0) {
    global $wpdb;
    $views_options = get_option('views_options');
    $where = '';
    $temp = '';
    $output = '';
    if( !empty($mode) && $mode != 'both' ) {
      if( is_array($mode) ) {
        $mode = implode("','",$mode);
        $where = "post_type IN ('".$mode."')";
      } else {
        $where = "post_type = '$mode'";
      }
    } else {
      $where = '1=1';
    }
    $most_viewed = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.*, (meta_value+0) AS views FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID WHERE post_date < '".current_time('mysql')."' AND $where AND post_status = 'publish' AND meta_key = 'views' AND post_password = '' ORDER BY views DESC LIMIT $limit");
    if( $most_viewed ) {
      foreach ( $most_viewed as $post ) {
        $post_views = intval($post->views);
        $post_title = get_the_title($post);
        if( $chars > 0 ) {
          $post_title = bzb_snippet_text($post_title, $chars);
        }
        $post_excerpt = bzb_views_post_excerpt($post->post_excerpt, $post->post_content, $post->post_password, $chars);
        if( $disp_views ){
          $temp = stripslashes($views_options['most_viewed_template']);
        }else{
          $temp = '<li><a href="%POST_URL%"  title="%POST_TITLE%"　>%POST_TITLE%</a></li>';
        }
        //jump
        $temp = str_replace("%VIEW_COUNT%", number_format_i18n($post_views) , $temp);
        //echo $temp;
        $temp = str_replace("%POST_TITLE%", $post_title, $temp);
        $temp = str_replace("%POST_EXCERPT%", $post_excerpt, $temp);
        $temp = str_replace("%POST_CONTENT%", $post->post_content, $temp);
        $temp = str_replace("%POST_URL%", get_permalink($post), $temp);
        $temp = str_replace("%POST_DATE%", get_the_time(get_option('date_format'), $post), $temp);
        $temp = str_replace("%POST_TIME%", get_the_time(get_option('time_format'), $post), $temp);
        $output .= $temp;
      }
    } else {
      $output = '<li>'.__('N/A', 'wp-postviews').'</li>'."\n";
    }
    if( $display ) {
      echo $output;
    } else {
      return $output;
    }
  }
}


/* Function: Display Most Viewed Page/Post By Category ID
* ---------------------------------------- */
if( !function_exists('bzb_get_most_viewed_category') ) {
  function bzb_get_most_viewed_category($category_id = 0, $mode = '', $limit = 10, $chars = 0, $display = true, $disp_views = false) {
    global $wpdb;
    $views_options = get_option('views_options');
    $where = '';
    $temp = '';
    $output = '';
    if( is_array($category_id) ) {
      $category_sql = "$wpdb->term_taxonomy.term_id IN (".join(',', $category_id).')';
    } else {
      $category_sql = "$wpdb->term_taxonomy.term_id = $category_id";
    }
    if( !empty($mode) && $mode != 'both' ) {
      if( is_array($mode) ) {
        $mode = implode("','",$mode);
        $where = "post_type IN ('".$mode."')";
      } else {
        $where = "post_type = '$mode'";
      }
    } else {
      $where = '1=1';
    }
    $most_viewed = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.*, (meta_value+0) AS views FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) WHERE post_date < '".current_time('mysql')."' AND $wpdb->term_taxonomy.taxonomy = 'category' AND $category_sql AND $where AND post_status = 'publish' AND meta_key = 'views' AND post_password = '' ORDER BY views DESC LIMIT $limit");
    if( $most_viewed ) {
      foreach ( $most_viewed as $post ) {
        $post_views = intval($post->views);
        $post_title = get_the_title($post);
        if( $chars > 0 ) {
          $post_title = bzb_snippet_text($post_title, $chars);
        }
        $post_excerpt =bzb_views_post_excerpt($post->post_excerpt, $post->post_content, $post->post_password, $chars);
        if( $disp_views ){
          $temp = stripslashes($views_options['most_viewed_template']);
        }else{
          $temp = '<li><a href="%POST_URL%"  title="%POST_TITLE%"　>%POST_TITLE%</a></li>';
        }
        //$temp = stripslashes($views_options['most_viewed_template']);
        $temp = str_replace("%VIEW_COUNT%", number_format_i18n($post_views), $temp);
        $temp = str_replace("%POST_TITLE%", $post_title, $temp);
        $temp = str_replace("%POST_EXCERPT%", $post_excerpt, $temp);
        $temp = str_replace("%POST_CONTENT%", $post->post_content, $temp);
        $temp = str_replace("%POST_URL%", get_permalink($post), $temp);
        $temp = str_replace("%POST_DATE%", get_the_time(get_option('date_format'), $post), $temp);
        $temp = str_replace("%POST_TIME%", get_the_time(get_option('time_format'), $post), $temp);
        $output .= $temp;
      }
    } else {
      $output = '<li>'.__('N/A', 'wp-postviews').'</li>'."\n";
    }
    if( $display ) {
      echo $output;
    } else {
      return $output;
    }
  }
}


/* Function: Display Total Views
* ---------------------------------------- */
if( !function_exists('bzb_get_totalviews') ) {
  function bzb_get_totalviews($display = true) {
    global $wpdb;
    $total_views = intval($wpdb->get_var("SELECT SUM(meta_value+0) FROM $wpdb->postmeta WHERE meta_key = 'views'"));
    if( $display ) {
      echo number_format_i18n($total_views);
    } else {
      return $total_views;
    }
  }
}


/* Function: Snippet Text
* ---------------------------------------- */
if( !function_exists('bzb_snippet_text') ) {
  function bzb_snippet_text($text, $length = 0) {
    if ( defined('MB_OVERLOAD_STRING') ) {
      $text = @html_entity_decode($text, ENT_QUOTES, get_option('blog_charset'));
      if ( mb_strlen($text) > $length ) {
        return htmlentities(mb_substr($text,0,$length), ENT_COMPAT, get_option('blog_charset')).'...';
      } else {
        return htmlentities($text, ENT_COMPAT, get_option('blog_charset'));
      }
    } else {
      $text = @html_entity_decode($text, ENT_QUOTES, get_option('blog_charset'));
      if ( strlen($text) > $length ) {
        return htmlentities(substr($text,0,$length), ENT_COMPAT, get_option('blog_charset')).'...';
      } else {
        return htmlentities($text, ENT_COMPAT, get_option('blog_charset'));
      }
    }
  }
}


/* Function: Process Post Excerpt, For Some Reasons, The Default get_post_excerpt() Does Not Work As Expected
* ---------------------------------------- */
function bzb_views_post_excerpt($post_excerpt, $post_content, $post_password, $chars = 200) {
  if( !empty($post_password) ) {
    if( !isset($_COOKIE['wp-postpass_'.COOKIEHASH]) || $_COOKIE['wp-postpass_'.COOKIEHASH] != $post_password ) {
      return __('There is no excerpt because this is a protected post.', 'wp-postviews');
    }
  }
  if( empty($post_excerpt) ) {
    return bzb_snippet_text(strip_tags($post_content), $chars);
  } else {
    return $post_excerpt;
  }
}


/* Function: Modify Default WordPress Listing To Make It Sorted By Post Views
* ---------------------------------------- */
function bzb_views_fields($content) {
  global $wpdb;
  $content .= ", ($wpdb->postmeta.meta_value+0) AS views";
  return $content;
}
function bzb_views_join($content) {
  global $wpdb;
  $content .= " LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID";
  return $content;
}
function bzb_views_where($content) {
  global $wpdb;
  $content .= " AND $wpdb->postmeta.meta_key = 'views'";
  return $content;
}
function bzb_views_orderby($content) {
  $orderby = trim(addslashes(get_query_var('v_orderby')));
  if( empty($orderby) || ($orderby != 'asc' && $orderby != 'desc') ) {
    $orderby = 'desc';
  }
  $content = " views $orderby";
  return $content;
}


/* Function: Add Views Custom Fields
* ---------------------------------------- */
add_action('publish_post', 'bzb_add_views_fields');
add_action('publish_page', 'bzb_add_views_fields');
function bzb_add_views_fields($post_ID) {
  global $wpdb;
  if( !wp_is_post_revision($post_ID) ) {
    add_post_meta($post_ID, 'views', 0, true);
  }
}


/* Function: Delete Views Custom Fields
* ---------------------------------------- */
add_action('delete_post', 'bzb_delete_views_fields');
function bzb_delete_views_fields($post_ID) {
  global $wpdb;
  if( !wp_is_post_revision($post_ID) ) {
    delete_post_meta($post_ID, 'views');
  }
}


/* Function: Views Public Variables
* ---------------------------------------- */
add_filter('query_vars', 'bzb_views_variables');
function bzb_views_variables($public_query_vars) {
  $public_query_vars[] = 'v_sortby';
  $public_query_vars[] = 'v_orderby';
  return $public_query_vars;
}


/* Function: Sort Views Posts
* ---------------------------------------- */
add_action('pre_get_posts', 'bzb_views_sorting');
function bzb_views_sorting($local_wp_query) {
  if( $local_wp_query->get('v_sortby') == 'views' ) {
    add_filter('posts_fields', 'bzb_views_fields');
    add_filter('posts_join', 'bzb_views_join');
    add_filter('posts_where', 'bzb_views_where');
    add_filter('posts_orderby', 'bzb_views_orderby');
  } else {
    remove_filter('posts_fields', 'bzb_views_fields');
    remove_filter('posts_join', 'bzb_views_join');
    remove_filter('posts_where', 'bzb_views_where');
    remove_filter('posts_orderby', 'bzb_views_orderby');
  }
}


/* Function: Plug Into WP-Stats
* ---------------------------------------- */
add_action('wp','bzb_postviews_wp_stats');
function bzb_postviews_wp_stats() {
  if( function_exists('stats_page') ) {
    if( strpos(get_option('stats_url'), $_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], 'stats-options.php') || strpos($_SERVER['REQUEST_URI'], 'wp-stats/wp-stats.php') ) {
      add_filter('wp_stats_page_admin_plugins', 'bzb_postviews_page_admin_general_stats');
      add_filter('wp_stats_page_admin_most', 'bzb_postviews_page_admin_most_stats');
      add_filter('wp_stats_page_plugins', 'bzb_postviews_page_general_stats');
      add_filter('wp_stats_page_most', 'bzb_postviews_page_most_stats');
    }
  }
}


/* Function: Add WP-PostViews General Stats To WP-Stats Page Options
* ---------------------------------------- */
function bzb_postviews_page_admin_general_stats($content) {
  $stats_display = get_option('stats_display');
  if( $stats_display['views'] == 1 ) {
    $content .= '<input type="checkbox" name="stats_display[]" id="wpstats_views" value="views" checked="checked" />&nbsp;&nbsp;<label for="wpstats_views">'.__('WP-PostViews', 'wp-postviews').'</label><br />'."\n";
  } else {
    $content .= '<input type="checkbox" name="stats_display[]" id="wpstats_views" value="views" />&nbsp;&nbsp;<label for="wpstats_views">'.__('WP-PostViews', 'wp-postviews').'</label><br />'."\n";
  }
  return $content;
}


/* Function: Add WP-PostViews Top Most/Highest Stats To WP-Stats Page Options
* ---------------------------------------- */
function bzb_postviews_page_admin_most_stats($content) {
  $stats_display = get_option('stats_display');
  $stats_mostlimit = intval(get_option('stats_mostlimit'));
  if( $stats_display['viewed_most_post'] == 1 ) {
    $content .= '<input type="checkbox" name="stats_display[]" id="wpstats_viewed_most_post" value="viewed_most_post" checked="checked" />&nbsp;&nbsp;<label for="wpstats_viewed_most_post">'.sprintf(_n('%s Most Viewed Post', '%s Most Viewed Posts', $stats_mostlimit, 'wp-postviews'), number_format_i18n($stats_mostlimit)).'</label><br />'."\n";
  } else {
    $content .= '<input type="checkbox" name="stats_display[]" id="wpstats_viewed_most_post" value="viewed_most_post" />&nbsp;&nbsp;<label for="wpstats_viewed_most_post">'.sprintf(_n('%s Most Viewed Post', '%s Most Viewed Posts', $stats_mostlimit, 'wp-postviews'), number_format_i18n($stats_mostlimit)).'</label><br />'."\n";
  }
  if( $stats_display['viewed_most_page'] == 1 ) {
    $content .= '<input type="checkbox" name="stats_display[]" id="wpstats_viewed_most_page" value="viewed_most_page" checked="checked" />&nbsp;&nbsp;<label for="wpstats_viewed_most_page">'.sprintf(_n('%s Most Viewed Page', '%s Most Viewed Pages', $stats_mostlimit, 'wp-postviews'), number_format_i18n($stats_mostlimit)).'</label><br />'."\n";
  } else {
    $content .= '<input type="checkbox" name="stats_display[]" id="wpstats_viewed_most_page" value="viewed_most_page" />&nbsp;&nbsp;<label for="wpstats_viewed_most_page">'.sprintf(_n('%s Most Viewed Page', '%s Most Viewed Pages', $stats_mostlimit, 'wp-postviews'), number_format_i18n($stats_mostlimit)).'</label><br />'."\n";
  }
  return $content;
}


/* Function: Add WP-PostViews General Stats To WP-Stats Page
* ---------------------------------------- */
function bzb_postviews_page_general_stats($content) {
  $stats_display = get_option('stats_display');
  if( $stats_display['views'] == 1 ) {
    $content .= '<p><strong>'.__('WP-PostViews', 'wp-postviews').'</strong></p>'."\n";
    $content .= '<ul>'."\n";
    $content .= '<li>'.sprintf(_n('<strong>%s</strong> view was generated.', '<strong>%s</strong> views were generated.', get_totalviews(false), 'wp-postviews'), number_format_i18n(get_totalviews(false))).'</li>'."\n";
    $content .= '</ul>'."\n";
  }
  return $content;
}


/* Function: Add WP-PostViews Top Most/Highest Stats To WP-Stats Page
* ---------------------------------------- */
function bzb_postviews_page_most_stats($content) {
  $stats_display = get_option('stats_display');
  $stats_mostlimit = intval(get_option('stats_mostlimit'));
  if($stats_display['viewed_most_post'] == 1) {
    $content .= '<p><strong>'.sprintf(_n('%s Most Viewed Post', '%s Most Viewed Posts', $stats_mostlimit, 'wp-postviews'), number_format_i18n($stats_mostlimit)).'</strong></p>'."\n";
    $content .= '<ul>'."\n";
    $content .= bzb_get_most_viewed('post', $stats_mostlimit, 0, false);
    $content .= '</ul>'."\n";
  }
  if($stats_display['viewed_most_page'] == 1) {
    $content .= '<p><strong>'.sprintf(_n('%s Most Viewed Page', '%s Most Viewed Pages', $stats_mostlimit, 'wp-postviews'), number_format_i18n($stats_mostlimit)).'</strong></p>'."\n";
    $content .= '<ul>'."\n";
    $content .= bzb_get_most_viewed('page', $stats_mostlimit, 0, false);
    $content .= '</ul>'."\n";
  }
  return $content;
}


/* Function: Increment Post Views
* ---------------------------------------- */
add_action( 'wp_ajax_postviews', 'bzb_increment_views' );
add_action( 'wp_ajax_nopriv_postviews', 'bzb_increment_views' );
function bzb_increment_views() {
  if( empty( $_GET['postviews_id'] ) ){
    return;
  }

  if( !defined( 'WP_CACHE' ) || !WP_CACHE ){
    return;
  }

  $views_options = get_option( 'views_options' );

  if( isset( $views_options['use_ajax'] ) && intval( $views_options['use_ajax'] ) === 0 ){
    return;
  }

  $post_id = intval( $_GET['postviews_id'] );
  if( $post_id > 0 ) {
    $post_views = get_post_custom( $post_id );
    $post_views = intval( $post_views['views'][0] );
    update_post_meta( $post_id, 'views', ( $post_views + 1 ) );
    echo ( $post_views + 1 );
    exit();
  }
}

/* Function Show Post Views Column in WP-Admin
* ---------------------------------------- */
add_action('manage_posts_custom_column', 'bzb_add_postviews_column_content');
add_filter('manage_posts_columns', 'bzb_add_postviews_column');
add_action('manage_pages_custom_column', 'bzb_add_postviews_column_content');
add_filter('manage_pages_columns', 'bzb_add_postviews_column');
function bzb_add_postviews_column($defaults) {
  $defaults['views'] = '閲覧数';
  return $defaults;
}


/* Functions Fill In The Views Count
* ---------------------------------------- */
function bzb_add_postviews_column_content($column_name) {
  if($column_name == 'views') {
    if(function_exists('bzb_the_views')) { bzb_the_views(true, '', '', true); }
  }
}


/* Function Sort Columns
* ---------------------------------------- */
add_filter('manage_edit-post_sortable_columns', 'bzb_sort_postviews_column');
add_filter('manage_edit-page_sortable_columns', 'bzb_sort_postviews_column');
function bzb_sort_postviews_column($defaults){
  $defaults['views'] = 'views';
  return $defaults;
}
add_action('pre_get_posts', 'bzb_sort_postviews');
function bzb_sort_postviews($query) {
  if( !is_admin() ){
    return;
  }
  $orderby = $query->get('orderby');
  if( 'views' == $orderby ) {
    $query->set('meta_key', 'views');
    $query->set('orderby', 'meta_value_num');
  }
}


/* Class: WP-PostViews Widget
* ---------------------------------------- */
class WP_Widget_PostViews_bzb extends WP_Widget {
 // Constructor
 function __construct() {
   $widget_ops = array('description' => '人気記事をページビュー数付で表示します');
   parent::__construct('views', __('人気記事一覧', 'wp-postviews'), $widget_ops);
 }

 // Display Widget
 function widget($args, $instance) {
   extract($args);
   $title = apply_filters('widget_title', esc_attr($instance['title']));
   $type = esc_attr($instance['type']);
   $mode = esc_attr($instance['mode']);
   $limit = intval($instance['limit']);
   $chars = intval($instance['chars']);
   $cat_ids = explode(',', esc_attr($instance['cat_ids']));
   $disp_views = intval($instance['disp_views']);
   echo $before_widget.$before_title.$title.$after_title;
   echo '<ul class="postviews">'."\n";
   switch($type) {
     case 'most_viewed':
       bzb_get_most_viewed($mode, $limit, $chars,true, $disp_views);//jumps
       break;
     case 'most_viewed_category':
       bzb_get_most_viewed_category($cat_ids, $mode, $limit, $chars, true, $disp_views);
       break;
   }
   echo '</ul>'."\n";
   echo $after_widget;
 }

 // When Widget Control Form Is Posted
 function update($new_instance, $old_instance) {
   if ( !isset($new_instance['submit']) ) {
     return false;
   }
   $instance = $old_instance;
   $instance['title'] = strip_tags($new_instance['title']);
   $instance['type'] = strip_tags($new_instance['type']);
   $instance['mode'] = strip_tags($new_instance['mode']);
   $instance['limit'] = intval($new_instance['limit']);
   $instance['chars'] = intval($new_instance['chars']);
   $instance['cat_ids'] = strip_tags($new_instance['cat_ids']);
   $instance['disp_views'] = intval(($new_instance['disp_views']));
   return $instance;
 }

 // DIsplay Widget Control Form
 function form($instance) {
   $instance = wp_parse_args((array) $instance, array('title' => __('Views', 'wp-postviews'), 'type' => 'most_viewed', 'mode' => 'both', 'limit' => 10, 'chars' => 200, 'cat_ids' => '0', 'disp_views' => 0));
   $title = esc_attr($instance['title']);
   $type = esc_attr($instance['type']);
   $mode = esc_attr($instance['mode']);
   $limit = intval($instance['limit']);
   $chars = intval($instance['chars']);
   $cat_ids = esc_attr($instance['cat_ids']);
   $disp_views = intval($instance['disp_views']);
?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>">タイトル: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('type'); ?>">計測タイプ:
        <select name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>" class="widefat">
          <option value="most_viewed"<?php selected('most_viewed', $type); ?>>人気の記事順</option>
          <option value="most_viewed_category"<?php selected('most_viewed_category', $type); ?>>人気のカテゴリー順</option>
        </select>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('mode'); ?>">ページビューをカウントするページ:
        <select name="<?php echo $this->get_field_name('mode'); ?>" id="<?php echo $this->get_field_id('mode'); ?>" class="widefat">
          <option value="both"<?php selected('both', $mode); ?>>投稿ページと固定ページ</option>
          <option value="post"<?php selected('post', $mode); ?>>投稿ページのみ</option>
          <option value="page"<?php selected('page', $mode); ?>>固定ページのみ</option>
        </select>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('limit'); ?>">表示件数: <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" /></label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('chars'); ?>">タイトルの最大文字数: <input class="widefat" id="<?php echo $this->get_field_id('chars'); ?>" name="<?php echo $this->get_field_name('chars'); ?>" type="text" value="<?php echo $chars; ?>" /></label><br />
      <small><strong>0</strong> で制限なしとなります。</small>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('cat_ids'); ?>">表示したいカテゴリーID: <span style="color: red;">*</span> <input class="widefat" id="<?php echo $this->get_field_id('cat_ids'); ?>" name="<?php echo $this->get_field_name('cat_ids'); ?>" type="text" value="<?php echo $cat_ids; ?>" /></label><br />
      <small>複数ある場合はカンマで区切って入力</small>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('disp_views');?>"><input type="checkbox" name="<?php echo $this->get_field_name('disp_views');?>" value="1" <?php checked($disp_views, "1");?>>ページビュー数を表示する</label><br />
      <small></small>
    </p>

    <input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
<?php
  }
}


/* Function: Init WP-PostViews Widget
* ---------------------------------------- */
add_action( 'widgets_init', 'bzb_widget_views_init' );
function bzb_widget_views_init() {
  register_widget( 'WP_Widget_PostViews_bzb' );
}




// function testmsg($type=0) {
//     if ( $type == 1 ) {
//         $msg = 'テーマ切替直後のロードです。';
//     } else {
//         $msg = 'テーマへ切替後2回目以降のロードです。';
//     }
//     return '<div id="testmsg" class="error"><p>' . $msg . '</p></div>' . "\n";
// }

/* add comment..
* ---------------------------------------- */
add_action('load-themes.php', 'bzb_pre_views_activation');
function bzb_pre_views_activation() {
  if ( false === get_option('theme_activated') ) {
    //add_action('admin_notices', create_function(null, 'views_activation();'));
    $option_name = 'views_options';
    $option = array(
        'count'                   => 1
      , 'exclude_bots'            => 0
      , 'display_home'            => 0
      , 'display_single'          => 0
      , 'display_page'            => 0
      , 'display_archive'         => 0
      , 'display_search'          => 0
      , 'display_other'           => 0
      , 'use_ajax'                => 1
      , 'template'                => __('%VIEW_COUNT% views', 'wp-postviews')
      , 'most_viewed_template'    => '<li><a href="%POST_URL%"  title="%POST_TITLE%">%POST_TITLE%</a><span> %VIEW_COUNT% '.__('PV', 'wp-postviews').'</span></li>'
    );

    if ( is_multisite() && $network_wide ){
      $ms_sites = wp_get_sites();

      if( 0 < sizeof( $ms_sites ) ){
        foreach ( $ms_sites as $ms_site ){
          switch_to_blog( $ms_site['blog_id'] );
          add_option( $option_name, $option );
        }
      }

      restore_current_blog();
    }else{
      add_option( $option_name, $option );
    }
    add_option('theme_activated', true);
  } else {
    //add_action('admin_notices', create_function(null, 'echo testmsg(0);'));
    add_action('switch_theme', function(){'delete_option("theme_activated")';});
  }
}



?>