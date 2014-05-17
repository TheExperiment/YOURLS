<?php

/**
 * Info Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Statistics;

/**
 * Summary of Info
 */
class AboutStats implements Statistics {

    /**
     * Build a list of all daily values between d1/m1/y1 to d2/m2/y2.
     *
     */
    public function build_list_of_days( $dates ) {
    ?>
        </div>
            <table class="table table-striped table-bordered g-stats">
                <tbody>
                    <tr>
                        <td><?php yourls_e( 'Short URL' ); ?></td>
                        <td><img src="<?php echo yourls_favicon(); ?>"/></td>
                        <td><?php if( $aggregate ) {
                                $i = 0;
                                foreach( $keyword_list as $k ) {
                                    $i++;
                                    if ( $i == 1 ) {
                                        yourls_html_link( yourls_link($k) );
                                    } else {
                                        yourls_html_link( yourls_link($k), "/$k" );
                                    }
                                    if ( $i < count( $keyword_list ) )
                                        echo ' + ';
                                }
                            } else {
                                yourls_html_link( yourls_link( $keyword ) );
                                if( isset( $keyword_list ) && count( $keyword_list ) > 1 )
                                    echo '<a href="'. yourls_link($keyword).'+all" title="' . yourls_esc_attr__( 'Aggregate stats for duplicate short URLs' ) . '"></a>';
                            } ?></td>
                    </tr>
                    <tr>
                        <td><?php yourls_e( 'Long URL' ); ?></td>
                        <td><img class="fix_images" src="<?php echo yourls_get_favicon_url( $longurl ); ?>" /></td>
                        <td><?php yourls_html_link( $longurl, yourls_trim_long_string( $longurl ), 'longurl' ); ?></td>
                    </tr>
                        <td><?php yourls_e( 'Stats URL' ); ?></td>
                        <td><i class="icon-info-sign"></i></td>
                        <td><?php yourls_html_link( yourls_link( $keyword ) . '+', yourls_trim_long_string( yourls_link( $keyword ) . '+' ), 'stats' ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

}
