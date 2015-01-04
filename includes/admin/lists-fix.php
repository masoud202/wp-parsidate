<?php
/**
 * Fixes admin lists for dates
 *
 * @author              Mobin Ghasempoor
 * @package             WP-Parsidate
 * @subpackage          Admin/Lists
 */


if(is_admin()) {
    function update_plugin_link_fix($buffer)
    {

        if (get_locale() == 'fa_IR') {
            $regexp = "(صورت <a.*?>خودکار به‌روز کنید<\/a>)";

            if(preg_match_all($regexp, $buffer, $matches))
            {
                $matches = $matches[0];

                foreach($matches as $match) {
                    preg_match_all('!https?://\S+!', $match, $url_matches);
                    $url_s = ' صورت '.'<a href="'.$url_matches[0][0].'">خودکار به‌روز کنید</a>';
                    $buffer = str_replace($match, $url_s, $buffer);
                }
            }
        }

        return $buffer;
    }

    add_action('init', 'buffer_start');
    add_action('admin_footer', 'buffer_end');
    function buffer_start()
    {
        ob_start("update_plugin_link_fix");
    }

    function buffer_end()
    {
        ob_end_flush();
    }
}

/**
 * Enqueues admin scripts
 *
 * @author              Ehsaan
 * @return              void
 */
function wpp_enqueue_admin_scripts() {
    wp_register_script( 'wpp_admin', WP_PARSI_URL . 'assets/js/admin.js', false, WP_PARSI_VER );
    wp_enqueue_script( 'wpp_admin' );
}
add_action( 'admin_enqueue_scripts', 'wpp_enqueue_admin_scripts' );

/**
 * Hooks admin functions for restrict posts in edit pages
 *
 * @return              void
 */
function wpp_backend_init() {
    add_action( 'restrict_manage_posts', 'wpp_restrict_posts' );
    add_filter( 'posts_where', 'wpp_admin_posts_where' );
}
add_action( 'load-edit.php', 'wpp_backend_init' );

/**
 * Limits posts to a certain date, if date setted
 *
 * @param               string $where Query pointer
 * @return              string New Pointer
 */
function wpp_admin_posts_where( $where ) {
    global $wp_query;
    if ( isset( $_GET['mfa'] ) && $_GET['mfa'] != '0' ) {
        $wp_query->query_vars['m'] = $_GET['mfa'];
        $where = wpp_posts_where( $where );
    }

    return $where;
}

/**
 * Restrict posts to given date
 * @author            Mobin Ghasempoor
 * @author            Parsa Kafi
 * @return            void
 */
function wpp_restrict_posts() {
    global $post_type, $post_status, $wpdb, $persian_month_names;

    $post_status_w = "AND post_status <> 'auto-draft'";
    if($post_status != "") {
        if(is_string($post_status))
            $post_status_w .= " AND post_status = '$post_status'";
    }else
        $post_status_w .= " AND post_status <> 'trash'";

    $sql = "SELECT DISTINCT date( post_date ) AS date
        FROM $wpdb->posts
        WHERE post_type='$post_type' {$post_status_w}  AND date( post_date ) <> '0000-00-00'
        ORDER BY post_date";
    $list = $wpdb->get_col( $sql );
    if ( empty( $list ) )
        return;

    $m = isset( $_GET['mfa'] ) ? (int) $_GET['mfa'] : 0;
    echo '<select name="mfa">';
    echo '<option ' . selected( $m, 0, false ) . ' value="0">' . __( 'Show All Dates' , 'wp-parsidate' ) . '</option>' . PHP_EOL;
    foreach( $list as $date ) {
        $date       =   parsidate( 'Ym', $date, 'eng' );
        $year       =   substr( $date, 0, 4 );
        $month      =   substr( $date, 4, 2 );
        $month      =   $persian_month_names[intval( $month )];

        if ( $predate != $date )
            echo sprintf( '<option %s value="%s">%s</option>', selected( $m, $date, false ), $date, $month . ' ' . fixnumber( $year ) );
        $predate = $date;
    }
    echo '</select>';
}