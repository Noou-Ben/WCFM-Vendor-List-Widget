<?php
/*
Plugin Name:  WCFM Vendor List Widget
Plugin URI:   https://wearenoou.com
Description:  Adds a dashboard widget that lists out all vendors who have zero products.
Version:      0.0.1
Author:       Ben Hussenet - Noou
Author URI:   https://wearenoou.com
*/


 function custom_wcfm_get_vendor_list( $all = false, $offset = '', $number = '', $search = '', $allow_vendors_list = '', $is_disabled_vendors = true, $vendor_search_data = array() ) {
  	global $WCFM;
  	
  	$is_marketplace = wcfm_is_marketplace();
  	$vendor_arr = array();
		if( $is_marketplace ) {
			if( !wcfm_is_vendor() || apply_filters( 'wcfm_is_allow_get_all_vendor_list_by_force', true ) ) {
				if( $all ) {
					$vendor_arr = array( 0 => __('All', 'wc-frontend-manager' ) );
				} else {
					$vendor_arr = array( '' => __('Choose Vendor ...', 'wc-frontend-manager' ) );
				}
				$wcfm_allow_vendors_list = apply_filters( 'wcfm_allow_vendors_list', $allow_vendors_list, $is_marketplace );
				if( $is_marketplace == 'wcpvendors' ) {
					$args = array(
						'hide_empty'   => false,
						'hierarchical' => false,
					);
					if( $number ) {
						$args['offset'] = $offset;
						$args['number'] = $number;
					}
					if( $search ) {
						$args['search'] = $search;
					}
					if( $wcfm_allow_vendors_list ) {
						$args['include']  = $wcfm_allow_vendors_list;
					}
					$vendors = get_terms( WC_PRODUCT_VENDORS_TAXONOMY, $args );
					
					if( !empty( $vendors ) ) {
						foreach ( $vendors as $vendor ) {
							$vendor_arr[$vendor->term_id] = esc_html( $vendor->name );
						}
					}
				} else {
					$vendor_user_roles = apply_filters( 'wcfm_allwoed_vendor_user_roles', array( 'dc_vendor', 'vendor', 'seller', 'wcfm_vendor' ) );
					if( $is_disabled_vendors ) {
						$vendor_user_roles = apply_filters( 'wcfm_allwoed_vendor_user_roles', array( 'dc_vendor', 'vendor', 'seller', 'wcfm_vendor', 'disable_vendor' ) );
					}
					$args = array(
						'role__in'     => $vendor_user_roles,
						'orderby'      => 'login',
						'order'        => 'ASC',
						'include'      => $wcfm_allow_vendors_list,
						'count_total'  => false,
						'fields'       => array( 'ID', 'display_name', 'user_login' )
					 ); 
					if( $number ) {
						$args['offset'] = $offset;
						$args['number'] = $number;
					}
					if( $search ) {
						//$args['search'] = $search;
						$args['meta_query'] = array(
																			 'relation' => 'OR',
																				array(
																						'key'     => 'first_name',
																						'value'   => $search,
																						'compare' => 'LIKE'
																				),
																				array(
																						'key'     => 'last_name',
																						'value'   => $search,
																						'compare' => 'LIKE'
																				),
																				array(
																						'key'     => 'nickname',
																						'value'   => $search,
																						'compare' => 'LIKE'
																				),
																				array(
																						'key'     => 'pv_shop_name',
																						'value'   => $search,
																						'compare' => 'LIKE'
																				),
																				array(
																						'key'     => '_vendor_page_title',
																						'value'   => $search,
																						'compare' => 'LIKE'
																				),
																				array(
																						'key'     => 'dokan_store_name',
																						'value'   => $search,
																						'compare' => 'LIKE'
																				),
																				array(
																						'key'     => 'wcfmmp_store_name',
																						'value'   => $search,
																						'compare' => 'LIKE'
																				),
																				array(
																						'key'     => 'store_name',
																						'value'   => $search,
																						'compare' => 'LIKE'
																				),
																		);
					}
					
					if( !empty( $vendor_search_data ) && is_array( $vendor_search_data ) ) {
						foreach( $vendor_search_data as $search_key => $search_value ) {
							if( !$search_value ) continue;
							if( in_array( $search_key, apply_filters( 'wcfmmp_vendor_list_exclude_search_keys', array( 'v', 'search_term', 'wcfmmp_store_search', 'wcfmmp_store_category', 'wcfmmp_radius_addr', 'wcfmmp_radius_lat', 'wcfmmp_radius_lng', 'wcfmmp_radius_range', 'pagination_base', 'wcfm_paged', 'paged', 'per_row', 'per_page', 'excludes', 'orderby', 'has_product', 'theme', 'nonce', 'lang' ) ) ) ) continue;
							if( $search ) $args['meta_query']['relation'] = 'AND';
							$args['meta_query'][] = array(
																						 'relation' => 'OR',
																						 array(
																								'key'     => str_replace( 'wcfmmp_store_', '', $search_key ),
																								'value'   => $search_value,
																								'compare' => 'LIKE'
																						),
																						array(
																								'key'     => str_replace( 'wcfmmp_store_', '_wcfm_', $search_key ),
																								'value'   => $search_value,
																								'compare' => 'LIKE'
																						)
																					);
						}
					}
					
					$all_users = get_users( $args );
					if( !empty( $all_users ) ) {
						foreach( $all_users as $all_user ) {
							$user_info = get_userdata($all_user->ID);
							$user_email = $user_info->user_email;

							$vendor_arr[$all_user->ID] = $user_email;
						}
					}
				}
			}
		}
		return $vendor_arr;
	}
// Add Custom Dashboard Widget
function add_wcfm_vendors_no_products_dashboard_widgets() {

	wp_add_dashboard_widget(
		'wcfm_vendors_no_products',
		'WCFM Vendors with no products',
		'wcfm_vendors_no_products_dashboard_widgets'
	);
}
add_action( 'wp_dashboard_setup', 'add_wcfm_vendors_no_products_dashboard_widgets' );


function wcfm_vendors_no_products_dashboard_widgets() {
	global $WCFM;
	$wcfm_vendors_array = custom_wcfm_get_vendor_list( true, $offset, $length, '', $search_vendor, false, $vendor_search_data );
	var_dump($wcfm_vendors_array);
	if(!empty($wcfm_vendors_array)) {
		echo '<table id="wcfm_vendors"><thead><tr><td>Name</td><td>Email</td></tr></thead><tbody>';
		$index = 0;
		$wcfm_vendors_json_arr = array();
		foreach($wcfm_vendors_array as $wcfm_vendors_id => $wcfm_vendors_name ) {
			if($index != 0 ){
			$total_products = wcfm_get_user_posts_count( $wcfm_vendors_id, 'product', apply_filters( 'wcfm_limit_check_status', 'any' ) );
			if($total_products == 0 ) {
			preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $wcfm_vendors_name, $email);

    		$name = array_shift(explode('-', $wcfm_vendors_name));

				echo '<tr><td>'.$name.'</td><td>'.$email[0][0].'</td></tr>';
			}
		}
		$index++;
			
		}
		echo '</tbody></table>';
	}
?>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.24/b-1.7.0/b-html5-1.7.0/r-2.2.7/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.24/b-1.7.0/b-html5-1.7.0/r-2.2.7/datatables.min.js"></script>


<script>
jQuery(document).ready(function() {
    jQuery('#wcfm_vendors').DataTable({
    	dom: 'Bfrtip',
        buttons: [
            'copy', 'csv'
        ]
    });
} );
</script>
<?php
}




