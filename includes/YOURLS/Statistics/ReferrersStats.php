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
class ReferrersStats implements Statistics {

    public function __toString() {
        yourls_do_action( 'pre_yourls_info_sources', $keyword ); ?>
        <div class="row">
        <div class="col-lg-6">
<?php
        if ( $referrers ) {
            yourls_html_htag( yourls__( 'Referrer shares' ), 3 );
            if ( $number_of_sites > 1 )
                $referrer_sort[ yourls__( 'Others' ) ] = count( $referrers );
            yourls_stats_pie( $referrer_sort, 5, '332x220', 'stat_tab_source_ref' );
            unset( $referrer_sort['Others'] );
            yourls_html_htag( yourls__( 'Referrers' ), 3 ); ?>
            <ul class="no_bullet">
                <?php
                $i = 0;
                foreach( $referrer_sort as $site => $count ) {
                    $i++;
                    $favicon = yourls_get_favicon_url( $site );
                    echo "<li class='sites_list'><img src='$favicon' class='fix_images'/> $site: <strong>$count</strong> <a href='' class='details hide-if-no-js' id='more_url$i'>" . yourls__( '(details)' ) . "</a></li>";
                    echo "<ul id='details_url$i' style='display:none'>";
                    foreach( $referrers[$site] as $url => $count ) {
                        echo "<li>"; yourls_html_link($url); echo ": <strong>$count</strong></li>";
                    }
                    echo "</ul>";
                    unset( $referrers[$site] );
                }
                // Any referrer left? Group in "various"
                if ( $referrers ) {
                    echo "<li id='sites_various'>" . yourls__( 'Various:' ) . " <strong>". count( $referrers ). "</strong> <a href='' class='details hide-if-no-js' id='more_various'>" . yourls__( '(details)' ) . "</a></li>";
                    echo "<ul id='details_various' style='display:none'>";
                    foreach( $referrers as $url ) {
                        echo "<li>"; yourls_html_link(key($url)); echo ": 1</li>";	
                    }
                    echo "</ul>";
                }
                ?>
            </ul>
                </div><div class="col-lg-6">
            <?php
            yourls_html_htag( yourls__( 'Direct vs Referrer Traffic' ), 3 );
            yourls_stats_pie( array( yourls__( 'Direct' ) => $direct, yourls__( 'Referrers' ) => $notdirect ), 5, '332x220', 'stat_tab_source_direct' );
            ?>
            <p><?php yourls_e( 'Direct traffic:' ); echo ' ' . sprintf( yourls_n( '<strong>%s</strong> hit', '<strong>%s</strong> hits', $direct ), $direct ); ?></p>
            <p><?php yourls_e( 'Referrer traffic:' ); echo ' ' . sprintf( yourls_n( '<strong>%s</strong> hit', '<strong>%s</strong> hits', $notdirect ), $direct ); ?></p>
</div></div>
            <?php yourls_do_action( 'post_yourls_info_sources', $keyword );
            
        } else {
            echo '<p>' . yourls__( 'No referrer data.' ) . '</p>';
        } ?>
            
    </div>
    <?php
    }

}
