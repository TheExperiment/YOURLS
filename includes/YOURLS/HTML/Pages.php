<?php

/**
 * HTML Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\HTML;

/**
 * Here we prepare HTML output
 */
class Pages {

    /**
     * Display a page
     *
     */
    public function page( $page ) {
        $include = YOURLS_PAGEDIR . "/$page.php";
        if( !file_exists( $include ) ) {
            die( "Page '$page' not found"/*, 'Not found', 404 */);
        }
        do_action( 'pre_page', $page );
        include_once( $include );
        do_action( 'post_page', $page );
        die();
    }

    /**
     * Die die die
     *
     */
    public function y_die( $message = '', $title = '', $header_code = 200 ) {
        status_header( $header_code );

        if( !$head = did_action( 'html_head' ) ) {
            $this->head( 'die', _( 'Fatal error' ) );
            template_content( 'before', 'die' );
        }

        echo apply_filter( 'die_title', "<h2>$title</h2>" );
        echo apply_filter( 'die_message', "<p>$message</p>" );
        do_action( 'die' );

        if( !$head ) {
            template_content( 'after', 'die' );
        }
        die();
    }

    /**
     * Display the login screen. Nothing past this point.
     *
     */
    public function login_screen( $error_msg = '' ) {
        // Since the user is not authed, we don't disclose any kind of stats
        remove_from_template( 'html_global_stats' );

        $this->head( 'login' );

        $action = ( isset( $_GET['action'] ) && $_GET['action'] == 'logout' ? '?' : '' );

        template_content( 'before' );
        $this->htag( 'YOURLS', 1, 'Your Own URL Shortener' );

?>
            <div id="login">
                <form method="post" class="login-screen" action="<?php echo $action; // reset any QUERY parameters ?>">
                    <?php
        if( !empty( $error_msg ) ) {
            echo notice_box( $error_msg[0], $error_msg[1] );
        }
                    ?>
                    <div class="control-group">
                        <label class="control-label" for="username"><?php e( 'Username' ); ?></label>
                        <div class="controls">
                            <input type="text" id="username" name="username" class="text" autofocus="autofocus">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="password"><?php e( 'Password' ); ?></label>
                        <div class="controls">
                            <input type="password" id="password" name="password" class="text">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="submit" name="submit"><?php e( 'Login' ); ?></button>
                    </div>
                </form>
            </div>
<?php

        template_content( 'after' );

        die();
    }

}
