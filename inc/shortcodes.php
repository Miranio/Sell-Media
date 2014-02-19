<?php

/**
 * Lists all downloads using sell_media_thanks shortcode.
 * Added to Thanks page so buyers can download directly from Page after successful purchase.
 *
 * @return string
 * @since 0.1
 */
function sell_media_list_downloads_shortcode( $purchase_key=null, $email=null ) {

    if ( isset( $_GET['purchase_key'] ) && ! empty( $_GET['purchase_key'] ) ){
        $purchase_key = $_GET['purchase_key'];
    }

    if ( isset( $_GET['email'] ) && ! empty( $_GET['email'] ) ){
       	$email = $_GET['email'];
    }

    $message = null;
    if ( ! empty( $purchase_key ) && ! empty( $email ) ){

        $args = array(
            'post_type' => 'sell_media_payment',
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'AND',
                    array(
                        'key' => '_sell_media_payment_purchase_key',
                        'value' => $purchase_key
                    )
                )
            );

        $payments = new WP_Query( $args );
        foreach( $payments->posts as $payment ) {
            $payment_meta = get_post_meta( $payment->ID, '_sell_media_payment_meta', true );
            $downloads = maybe_unserialize( $payment_meta['products'] );
        }

        if ( empty( $downloads ) ) {
            $settings = sell_media_get_plugin_options();
            $message .= __( 'Your purchase is pending. This happens if you paid with an eCheck, if you opened a new account or if there is a problem with the checkout system. Please contact the seller if you have questions about this purchase: ') ;
            $message .= $settings->paypal_email;
        } else {

            $payment_id = sell_media_get_payment_id_by( 'key', $purchase_key );
            $links = sell_media_build_download_link( $payment_id, $email );

            foreach( $links as $link ){

               	$image_attributes = wp_get_attachment_image_src( get_post_meta( $link['item_id'], '_sell_media_attachment_id', true ), 'medium', false );

                // Currently there is no "type", i.e., download vs. physical print
                // so we use price groups to determine if the purchase was a download
                // and only show download links for downloads
                $term_obj = get_term_by( 'id', $link['price_id'], 'price-group' );

                $message .= '<div class="sell-media-aligncenter">';

                if ( $term_obj || $link['price_id'] == 'sell_media_original_file' ){
                    $message .= '<a href="' . $link['url']. '"><img src="' . $image_attributes[0] . '" width="' . $image_attributes[1] . '" height="' . $image_attributes[2] . '" class="sell-media-aligncenter" /></a>';
                    $message .= '<strong><a href="' . $link['url'] . '" class="sell-media-button">' . __( 'Download File', 'sell_media' ) . '</a></strong>';
                } else {
                    $message .= '<img src="' . $image_attributes[0] . '" width="' . $image_attributes[1] . '" height="' . $image_attributes[2] . '" class="sell-media-aligncenter" />';
                }

                $message .= '</div>';
            }
        }
    }
    return '<p class="sell-media-thanks-message">' . $message . '</p>';
}
add_shortcode( 'sell_media_thanks', 'sell_media_list_downloads_shortcode' );

/**
 * Search form shortcode [sell_media_searchform]
 *
 * @since 0.1
 */
function sell_media_search_shortcode( $atts, $content = null ) {
    return get_search_form();
}
add_shortcode('sell_media_searchform', 'sell_media_search_shortcode');

/**
 * Adds the 'sell_media' short code to the editor. [sell_media_item]
 *
 * @author Zane M. Kolnik
 * @since 0.1
 */
function sell_media_item_shortcode( $atts ) {

    extract( shortcode_atts( array(
        'style' => 'default',
        'color' => 'blue',
        'id' => 'none',
        'text' => 'BUY',
        'size' => 'medium',
        'align' => 'center'
        ), $atts )
    );

    $caption = null;
    $thumb_id = get_post_meta( $id, '_sell_media_attachment_id', true );
    $image = wp_get_attachment_image_src( $thumb_id, $size );
    $text = apply_filters('sell_media_purchase_text', __( $text,'sell_media' ), $id );
    if ( $image ) {
        $image = '<img src="' . $image[0] . '" alt="' . sell_media_image_caption( $id ) . '" title=" ' . sell_media_image_caption( $id ) . ' " class="sell-media-aligncenter" />';
    } else {
        sell_media_item_icon( get_post_thumbnail_id( $id ), $size );
    }

    $button = '<a href="#" data-sell_media-product-id="' . esc_attr( $id ) . '" data-sell_media-thumb-id="' . esc_attr( $thumb_id ) . '" class="sell-media-cart-trigger sell-media-buy-' . esc_attr( $style ) . '">' . $text . '</a>';

    return '<div class="sell-media-item-container sell-media-align' . $align . ' "><a href="' . get_permalink( $id ) . '">' . $image . '</a>' . $button . '</div>';
}
add_shortcode('sell_media_item', 'sell_media_item_shortcode');


/**
 * Adds template to display all items for sale.
 *
 * @author Zane M. Kolnik
 * @since 1.0.4
 */
function sell_media_all_items_shortcode( $atts ){

    extract( shortcode_atts( array(
        'collection' => null,
		'show' => -1
        ), $atts )
    );

    $args = array(
        'posts_per_page' => -1,
        'post_type' => 'sell_media_item'
        );

    if ( $collection ){
		$args = array(
				'posts_per_page' => $show,
				'taxonomy' => 'collection',
				'field' => 'slug',
				'term' => $collection
				);

    }

    $posts = New WP_Query( $args );
    ob_start(); ?>
    <div id="sell-media-shortcode-all" class="sell-media">
        <div class="sell-media-short-code-all">
            <div class="sell-media-grid-container">
                <?php $i = 0; ?>
                <?php foreach( $posts->posts as $post ) : $i++; ?>
                    <?php if ( $i %3 == 0) $end = ' end'; else $end = null; ?>
                    <div class="sell-media-grid<?php echo $end; ?>">
                        <a href="<?php print get_permalink( $post->ID ); ?>"><?php sell_media_item_icon( get_post_meta( $post->ID, '_sell_media_attachment_id', true ) ); ?></a>
                        <h3 class="sell-media-shortcode-all-item-title"><a href="<?php print get_permalink( $post->ID ); ?>"><?php print get_the_title( $post->ID ); ?></a></h3>
                        <?php sell_media_item_buy_button( $post->ID, 'text', __( 'Purchase', 'sell_media' ) ); ?>
                    </div>
                <?php endforeach; ?>
                <?php sell_media_pagination_filter(); ?>
            </div><!-- .sell-media-grid-container -->
        </div><!-- .sell-media-short-code-all -->
    </div><!-- #sell-media-shortcode-all .sell_media -->
    <?php return ob_get_clean();
}
add_shortcode('sell_media_all_items', 'sell_media_all_items_shortcode');


/**
 * Shows a list of everything user has downloaded.
 * Adds the 'sell_media_download_list' short code to the editor. [sell_media_download_list]
 *
 * @since 1.0.4
 */
function sell_media_download_shortcode( $atts ) {
	if ( is_user_logged_in() ) {
        global $current_user;
        global $wpdb;
        get_currentuserinfo();

	    $payment_lists = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value LIKE %s order by post_id DESC", '_sell_media_payment_user_email', $current_user->user_email ), ARRAY_A );
            $payment_obj = New Sell_Media_Payments;
            $html = null;

            foreach( $payment_lists as $payment ){
                if ( get_post_status( $payment['post_id'] ) != 'publish' ) {
                    $payment_meta = get_post_meta( $payment['post_id'], '_sell_media_payment_meta', true );
                    $html .= '<ul class="payment-meta">';
                    $html .= '<li><strong>'.__('Date', 'sell_media').'</strong> ' . $payment_meta['date'] . '</li>';
                    $html .= '<li><strong>'.__('Payment ID', 'sell_media').'</strong> ' . $payment_meta['payment_id'] . '</li>';
                    $html .= '</ul>';
                    $html .= $payment_obj->payment_table( $payment['post_id'] );
                }
            }

            return '<div id="purchase-history">'.$html.'</div>';

	} else {
            do_shortcode( '[sell_media_login_form]' );
	}
}
add_shortcode('sell_media_download_list', 'sell_media_download_shortcode');


/**
 * Displays all the price groups in a table
 *
 * @since 1.5.1
 */
function sell_media_price_group_shortcode(){
    ob_start(); ?>
    <table class="">
        <tbody>
        <?php foreach( get_terms('price-group', array( 'hide_empty' => false, 'parent' => 0 ) ) as $parent ) : ?>
            <tr>
                <th colspan="4"><?php echo $parent->name; ?></th>
            </tr>
            <tr class="sell-media-price-group-parent sell-media-price-group-parent-<?php echo $parent->name; ?>" id="sell-media-price-group-parent-<?php echo $parent->term_id; ?>">
                <th><?php _e('Description','sell_media'); ?></th>
                <th><?php _e('width (px)','sell_media'); ?></th>
                <th><?php _e('height (px)','sell_media'); ?></th>
                <th><?php _e('price','sell_media'); ?>(<span class="currency-symbol"><?php echo sell_media_get_currency_symbol(); ?></span>)</th>
            </tr>
            <?php $i=0; foreach( get_terms( 'price-group', array( 'hide_empty' => false, 'child_of' => $parent->term_id ) ) as $term ): ?>
                <tr class="sell-media-price-group-row-<?php echo ($i++%2==1) ? 'odd' : 'even'; ?> sell-media-price-group-child-<?php echo $term->name; ?>" id="sell-media-price-group-child-<?php echo $term->term_id; ?>">
                    <td>
                        <span class="sell-media-price-group-name"><?php echo $term->name; ?></span>
                    </td>
                    <td>
                        <span class="sell-media-price-group-width"><?php echo sell_media_get_term_meta( $term->term_id, 'width', true ); ?></span>
                    </td>
                    <td>
                        <span class="sell-media-price-group-height"><?php echo sell_media_get_term_meta( $term->term_id, 'height', true ); ?></span>
                    </td>
                    <td>
                        <span class="sell-media-price-group-height">
                            <span class="currency-symbol"><?php echo sell_media_get_currency_symbol(); ?></span>
                            <?php echo sprintf( '%0.2f', sell_media_get_term_meta( $term->term_id, 'price', true ) ); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php return ob_get_clean();
}
add_shortcode('sell_media_price_group', 'sell_media_price_group_shortcode');


/**
 * Displays all collections.
 * Adds the 'sell_media_list_all_collections' short code to the editor. [sell_media_list_all_collections]
 *
 * @since 1.5.3
 */
function sell_media_list_all_collections_shortcode( $atts ) {

	extract( shortcode_atts( array(
		'details' => 'false',
        'thumbs' => 'true'
        ), $atts )
    );

	if ( 'false' == $thumbs ) {

		$html = null;
		$html .= '<div class="sell-media-collections-shortcode">';

		$taxonomy = 'collection';
		$term_ids = array();
		foreach( get_terms( $taxonomy ) as $term_obj ){
		    $password = sell_media_get_term_meta( $term_obj->term_id, 'collection_password', true );
		    if ( $password ) $term_ids[] = $term_obj->term_id;
		}

		$args = array(
		    'orderby' => 'name',
			'hide_empty' => true,
			'parent' => 0,
			'exclude' => $term_ids
		);

		$terms = get_terms( $taxonomy, $args );

		if ( empty( $terms ) )
			return;

		$html .= '<ul class="sell-media-collections-shortcode-list">';
		foreach( $terms as $term ) :
			$html .= '<li class="sell-media-collections-shortcode-list-item">';
			$html .= '<a href="'. get_term_link( $term->slug, $taxonomy ) .'" class="sell-media-collections-shortcode-list-item-link">' . $term->name . '</a>';
			$html .= '</li>';
		endforeach;
		$html .= '</ul>';
		$html .= '</div>';
		return $html;

	} else {

		$html = null;
		$html .= '<div class="sell-media-collections-shortcode sell-media">';

		$taxonomy = 'collection';
		$term_ids = array();
		foreach( get_terms( $taxonomy ) as $term_obj ){
		    $password = sell_media_get_term_meta( $term_obj->term_id, 'collection_password', true );
		    if ( $password ) $term_ids[] = $term_obj->term_id;
		}

		$args = array(
		    'orderby' => 'name',
			'hide_empty' => true,
			'parent' => 0,
			'exclude' => $term_ids
		);

		$terms = get_terms( $taxonomy, $args );

		if ( empty( $terms ) )
			return;

		foreach( $terms as $term ) :
			$args = array(
					'post_status' => 'publish',
					'taxonomy' => 'collection',
					'field' => 'slug',
					'term' => $term->slug,
					'tax_query' => array(
						array(
							'taxonomy' => 'collection',
							'field' => 'id',
							'terms' => $term_ids,
							'operator' => 'NOT IN'
							)
						)
					);
			$posts = New WP_Query( $args );
			$post_count = $posts->found_posts;

			if ( $post_count != 0 ) : ?>
				<?php
				$html .= '<div class="sell-media-grid sell-media-grid-collection third">';
					$args = array(
							'posts_per_page' => 1,
							'taxonomy' => 'collection',
							'field' => 'slug',
							'term' => $term->slug
							);

					$posts = New WP_Query( $args );
					?>

					<?php foreach( $posts->posts as $post ) : ?>

						<?php
						//Get Post Attachment ID
						$sell_media_attachment_id = get_post_meta( $post->ID, '_sell_media_attachment_id', true );
						if ( $sell_media_attachment_id ){
							$attachment_id = $sell_media_attachment_id;
						} else {
							$attachment_id = get_post_thumbnail_id( $post->ID );
						}

						$html .= '<a href="'. get_term_link( $term->slug, $taxonomy ) .'" class="sell-media-collections-shortcode-item-link">';
						$collection_attachment_id = sell_media_get_term_meta( $term->term_id, 'collection_icon_id', true );
							if ( ! empty ( $collection_attachment_id ) ) {
								$html .= wp_get_attachment_image( $collection_attachment_id, 'sell_media_item' );
							} else {
								$html .= sell_media_item_icon( $attachment_id, 'sell_media_item', false );
							}
						$html .= '</a>';
					endforeach;

					$html .= '<div class="sell-media-collections-shortcode-item-title"><a href="'. get_term_link( $term->slug, $taxonomy ) .'">' . $term->name . '</a></div>';
					if ( 'true' == $details ) {
                        $settings = sell_media_get_plugin_options();
						$html .= '<div class="sell-media-collections-shortcode-item-details">';
						$html .= '<span class="sell-media-collections-shortcode-item-count">';
						$html .= '<span class="count">' . $post_count . '</span>' .  __( ' images in ', 'sell_media' ) . '<span class="collection">' . $term->name . '</span>' . __(' collection', 'sell_media');
						$html .= '</span>';
						$html .= '<span class="sell-media-collections-shortcode-item-price">';
						$html .=  __( 'Starting at ', 'sell_media' ) . '<span class="price">' . sell_media_get_currency_symbol() . $settings->default_price . '</span>';
						$html .= '</span>';
						$html .= '</div>';
					}
					$html .= '</div>';

			endif;
		endforeach;
		$html .= '</div>';

		return $html;

	}

}
add_shortcode('sell_media_list_all_collections', 'sell_media_list_all_collections_shortcode');


/**
 * Custom login form
 *
 * @since 1.5.5
 */
function sell_media_login_form_shortcode(){

    $settings = sell_media_get_plugin_options();

    if ( is_user_logged_in() ) {

        return sprintf( __( 'You are logged in. %1$s or %2$s.', 'sell_media'), '<a href="' . get_permalink( $settings->checkout_page ) . '">Checkout now</a>', '<a href="' . get_post_type_archive_link( 'sell_media_item' ) . '">continue shopping</a>' );

    } else {
        if( isset( $_GET['login'] ) && "failed" == $_GET['login'] ) {
            echo "<span class='error'>".__("Login Failed", "sell_media")."</span>";
        }

        $args = array(
            'redirect' => get_permalink( $settings->checkout_page ),
            'label_username' => __( 'Username', 'sell_media' ),
            'label_password' => __( 'Password', 'sell_media' ),
            'label_remember' => __( 'Remember Me', 'sell_media' ),
            'label_log_in' => __( 'Log In', 'sell_media' )        );

        wp_login_form( $args );

    }

}
add_shortcode( 'sell_media_login_form', 'sell_media_login_form_shortcode' );

/**
 * Redirect the failed login to the same page
 *
 * @since 1.6
 */
add_action( 'wp_login_failed', 'my_front_end_login_fail' );  // hook failed login

function my_front_end_login_fail( $username ) {
   $referrer = $_SERVER['HTTP_REFERER'];  // where did the post submission come from?
   // if there's a valid referrer, and it's not the default log-in screen
   if ( !empty($referrer) && !strstr($referrer,'wp-login') && !strstr($referrer,'wp-admin') ) {
        $redirect = add_query_arg( array( 'login' => 'failed' ), $referrer );
      wp_redirect( $redirect );
      exit;
   }
}