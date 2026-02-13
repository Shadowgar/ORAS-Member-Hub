<?php
/**
 * Module rendering functions for ORAS Member Hub.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'oras_member_hub_wrap_module' ) ) {
	/**
	 * Wrap module content in a shared section shell.
	 *
	 * @param string $module_id Module identifier.
	 * @param string $title Module heading.
	 * @param string $content_html Module body HTML.
	 * @param string $context Main or sidebar context.
	 * @return string
	 */
	function oras_member_hub_wrap_module( $module_id, $title, $content_html, $context = 'main' ) {
		$classes = sprintf(
			'oras-member-hub__module oras-member-hub__module--%1$s oras-member-hub__module--%2$s',
			sanitize_html_class( $context ),
			sanitize_html_class( $module_id )
		);

		$output  = '<section class="' . esc_attr( $classes ) . '" data-module="' . esc_attr( $module_id ) . '">';
		$output .= '<h2 class="oras-member-hub__module-title">' . esc_html( $title ) . '</h2>';
		$output .= '<div class="oras-member-hub__module-content">' . $content_html . '</div>';
		$output .= '</section>';

		return (string) apply_filters( 'oras_member_hub_module_output', $output, $module_id, $context, $title, $content_html );
	}
}

if ( ! function_exists( 'oras_member_hub_module_conditions_summary' ) ) {
	/**
	 * Render astronomy conditions summary.
	 *
	 * @return string
	 */
	function oras_member_hub_module_conditions_summary() {
		$items = apply_filters(
			'oras_member_hub_conditions_items',
			array(
				array(
					'key'   => 'weather',
					'label' => __( 'Weather', 'oras-member-hub' ),
					'value' => __( 'Not connected', 'oras-member-hub' ),
					'url'   => '#',
				),
				array(
					'key'   => 'cloud_cover',
					'label' => __( 'Cloud Cover', 'oras-member-hub' ),
					'value' => __( 'Not connected', 'oras-member-hub' ),
					'url'   => '#',
				),
				array(
					'key'   => 'moon_phase',
					'label' => __( 'Moon Phase', 'oras-member-hub' ),
					'value' => __( 'Not connected', 'oras-member-hub' ),
					'url'   => '#',
				),
				array(
					'key'   => 'seeing',
					'label' => __( 'Seeing', 'oras-member-hub' ),
					'value' => __( 'Not connected', 'oras-member-hub' ),
					'url'   => '#',
				),
				array(
					'key'   => 'darkness',
					'label' => __( 'Darkness', 'oras-member-hub' ),
					'value' => __( 'Not connected', 'oras-member-hub' ),
					'url'   => '#',
				),
			)
		);

		$content = '<div class="oras-member-hub__conditions-grid">';

		foreach ( $items as $item ) {
			$key   = isset( $item['key'] ) ? sanitize_html_class( (string) $item['key'] ) : 'condition';
			$label = isset( $item['label'] ) ? (string) $item['label'] : '';
			$value = isset( $item['value'] ) ? (string) $item['value'] : '';
			$url   = ! empty( $item['url'] ) ? (string) $item['url'] : '#';

			$content .= '<a class="oras-member-hub__condition oras-member-hub__condition--' . esc_attr( $key ) . '" href="' . esc_url( $url ) . '">';
			$content .= '<strong class="oras-member-hub__condition-label">' . esc_html( $label ) . '</strong>';
			$content .= '<span class="oras-member-hub__condition-value">' . esc_html( $value ) . '</span>';
			$content .= '</a>';
		}

		$content .= '</div>';

		$output = oras_member_hub_wrap_module(
			'conditions-summary',
			__( 'Astronomy Conditions Summary', 'oras-member-hub' ),
			$content,
			'main'
		);

		return (string) apply_filters( 'oras_member_hub_module_conditions_summary_output', $output, $items );
	}
}

if ( ! function_exists( 'oras_mh_module_upcoming_events' ) ) {
	/**
	 * Render upcoming events from The Events Calendar when available.
	 *
	 * @return string
	 */
	function oras_mh_module_upcoming_events() {
		$limit = absint( apply_filters( 'oras_mh_upcoming_events_limit', 5 ) );
		$limit = $limit > 0 ? $limit : 5;

		$items       = array();
		$now_mysql   = current_time( 'mysql' );
		$now_time    = strtotime( $now_mysql );
		$epoch_start = '1970-01-01 00:00:00';

		if ( function_exists( 'tribe_get_events' ) && function_exists( 'tribe_get_start_date' ) ) {
			$tec_args = array(
				'posts_per_page' => $limit,
				'post_status'    => 'publish',
				'eventDisplay'   => 'custom',
				'start_date'     => $epoch_start,
				'ends_after'     => $now_mysql,
				'orderby'        => 'event_date',
				'order'          => 'ASC',
			);

			$events = tribe_get_events( $tec_args );

			if ( is_array( $events ) ) {
				foreach ( $events as $event ) {
					$event_id  = is_object( $event ) ? (int) $event->ID : (int) $event;
					$start_raw = tribe_get_start_date( $event_id, false, 'Y-m-d H:i:s' );
					$end_raw   = (string) get_post_meta( $event_id, '_EventEndDate', true );

					if ( '' === $end_raw || $end_raw < $now_mysql ) {
						continue;
					}

					$start_time = strtotime( $start_raw );
					$end_time   = strtotime( $end_raw );
					$is_ongoing = false;

					if ( false !== $start_time && false !== $end_time && false !== $now_time ) {
						$is_ongoing = ( $start_time <= $now_time && $end_time >= $now_time );
					}

					$items[] = array(
						'title'     => get_the_title( $event_id ),
						'url'       => get_permalink( $event_id ),
						'start_raw' => $start_raw,
						'start'     => tribe_get_start_date( $event_id, false, 'M j, Y g:i A' ),
						'is_ongoing' => $is_ongoing,
					);
				}
			}
		} else {
			$fallback_query = new WP_Query(
				array(
					'post_type'      => 'tribe_events',
					'post_status'    => 'publish',
					'posts_per_page' => $limit,
					'orderby'        => array(
						'event_start_clause' => 'ASC',
					),
					'meta_query'     => array(
						'relation'           => 'AND',
						'event_start_clause' => array(
							'key'     => '_EventStartDate',
							'compare' => 'EXISTS',
							'type'    => 'DATETIME',
						),
						'event_end_clause'   => array(
							'key'     => '_EventEndDate',
							'value'   => $now_mysql,
							'compare' => '>=',
							'type'    => 'DATETIME',
						),
					),
				)
			);

			if ( $fallback_query->have_posts() ) {
				while ( $fallback_query->have_posts() ) {
					$fallback_query->the_post();

					$event_id   = get_the_ID();
					$start_raw  = (string) get_post_meta( $event_id, '_EventStartDate', true );
					$end_raw    = (string) get_post_meta( $event_id, '_EventEndDate', true );
					$timestamp  = strtotime( $start_raw );
					$start_time = strtotime( $start_raw );
					$end_time   = strtotime( $end_raw );
					$is_ongoing = false;

					if ( false !== $start_time && false !== $end_time && false !== $now_time ) {
						$is_ongoing = ( $start_time <= $now_time && $end_time >= $now_time );
					}

					$items[] = array(
						'title'     => get_the_title(),
						'url'       => get_permalink(),
						'start_raw' => $start_raw,
						'start'     => $timestamp ? wp_date( 'M j, Y g:i A', $timestamp ) : '',
						'is_ongoing' => $is_ongoing,
					);
				}
			}

			wp_reset_postdata();
		}

		if ( ! empty( $items ) ) {
			usort(
				$items,
				static function ( $a, $b ) {
					$a_time = ! empty( $a['start_raw'] ) ? strtotime( (string) $a['start_raw'] ) : 0;
					$b_time = ! empty( $b['start_raw'] ) ? strtotime( (string) $b['start_raw'] ) : 0;

					return $a_time <=> $b_time;
				}
			);

			$items = array_slice( $items, 0, $limit );
		}

		$items = apply_filters( 'oras_mh_upcoming_events_items', $items, $limit );

		ob_start();
		?>
		<section class="oras-member-hub__module oras-member-hub__module--main oras-member-hub__module--upcoming-events" data-module="upcoming-events">
			<h2 class="oras-member-hub__module-title"><?php echo esc_html__( 'Upcoming Events', 'oras-member-hub' ); ?></h2>
			<div class="oras-member-hub__module-content">
				<?php if ( empty( $items ) ) : ?>
					<p><?php echo esc_html__( 'No upcoming events found yet.', 'oras-member-hub' ); ?></p>
				<?php else : ?>
					<ul class="oras-member-hub__events-list">
						<?php foreach ( $items as $item ) : ?>
							<?php
								$title      = isset( $item['title'] ) ? (string) $item['title'] : '';
								$url        = isset( $item['url'] ) ? (string) $item['url'] : '';
								$start      = isset( $item['start'] ) ? (string) $item['start'] : '';
								$is_ongoing = ! empty( $item['is_ongoing'] );
								?>
								<li class="oras-member-hub__events-item">
									<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
									<?php if ( '' !== $start ) : ?>
										<?php echo esc_html( ' — ' . $start ); ?>
										<?php if ( $is_ongoing ) : ?>
											<?php echo esc_html( ' (Ongoing)' ); ?>
										<?php endif; ?>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'oras_member_hub_module_my_tickets_reminders' ) ) {
	/**
	 * Render My Tickets / Reminders placeholder.
	 *
	 * @return string
	 */
	function oras_member_hub_module_my_tickets_reminders() {
		$items = apply_filters(
			'oras_member_hub_my_tickets_items',
			array(
				__( 'Ticket integrations are not connected yet.', 'oras-member-hub' ),
				__( 'Event reminders will appear here in a future phase.', 'oras-member-hub' ),
			)
		);

		$content = '<ul class="oras-member-hub__tickets-list">';

		foreach ( $items as $item ) {
			$content .= '<li>' . esc_html( (string) $item ) . '</li>';
		}

		$content .= '</ul>';

		$output = oras_member_hub_wrap_module(
			'my-tickets-reminders',
			__( 'My Tickets / My Event Reminders', 'oras-member-hub' ),
			$content,
			'main'
		);

		return (string) apply_filters( 'oras_member_hub_module_my_tickets_output', $output, $items );
	}
}

if ( ! function_exists( 'oras_member_hub_module_resources' ) ) {
	/**
	 * Render member resources list.
	 *
	 * @return string
	 */
	function oras_member_hub_module_resources() {
		$resources = apply_filters(
			'oras_member_hub_resources_items',
			array(
				array(
					'label' => __( 'Beginner Skywatching Guide (placeholder)', 'oras-member-hub' ),
					'url'   => '#',
				),
				array(
					'label' => __( 'Observing Checklist (placeholder)', 'oras-member-hub' ),
					'url'   => '#',
				),
				array(
					'label' => __( 'Telescope Setup Notes (placeholder)', 'oras-member-hub' ),
					'url'   => '#',
				),
			)
		);

		$content = '<ul class="oras-member-hub__resources-list">';

		foreach ( $resources as $resource ) {
			$label = isset( $resource['label'] ) ? (string) $resource['label'] : '';
			$url   = ! empty( $resource['url'] ) ? (string) $resource['url'] : '#';
			$content .= '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
		}

		$content .= '</ul>';

		$output = oras_member_hub_wrap_module(
			'resources',
			__( 'Resources', 'oras-member-hub' ),
			$content,
			'main'
		);

		return (string) apply_filters( 'oras_member_hub_module_resources_output', $output, $resources );
	}
}

if ( ! function_exists( 'oras_member_hub_module_community_updates' ) ) {
	/**
	 * Render community updates placeholder/feed.
	 *
	 * @return string
	 */
	function oras_member_hub_module_community_updates() {
		$query_args = apply_filters(
			'oras_member_hub_community_updates_query_args',
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
			)
		);

		$updates_query = new WP_Query( $query_args );
		$content       = '';

		if ( $updates_query->have_posts() ) {
			$content .= '<ul class="oras-member-hub__updates-list">';

			while ( $updates_query->have_posts() ) {
				$updates_query->the_post();
				$content .= '<li class="oras-member-hub__updates-item">';
				$content .= '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
				$content .= ' <span class="oras-member-hub__updates-date">(' . esc_html( get_the_date() ) . ')</span>';
				$content .= '</li>';
			}

			$content .= '</ul>';
		} else {
			$content .= '<p>' . esc_html__( 'Member updates feed will appear here.', 'oras-member-hub' ) . '</p>';
		}

		wp_reset_postdata();

		$output = oras_member_hub_wrap_module(
			'community-updates',
			__( 'Community / Updates', 'oras-member-hub' ),
			$content,
			'main'
		);

		return (string) apply_filters( 'oras_member_hub_module_community_updates_output', $output, $query_args );
	}
}

if ( ! function_exists( 'oras_member_hub_account_url' ) ) {
	/**
	 * Resolve WooCommerce account endpoint URLs when available.
	 *
	 * @param string $endpoint Optional endpoint slug.
	 * @return string
	 */
	function oras_member_hub_account_url( $endpoint = '' ) {
		if ( function_exists( 'wc_get_account_endpoint_url' ) && ! empty( $endpoint ) ) {
			return (string) wc_get_account_endpoint_url( $endpoint );
		}

		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$my_account = wc_get_page_permalink( 'myaccount' );

			if ( ! empty( $my_account ) ) {
				return (string) $my_account;
			}
		}

		return '#';
	}
}

if ( ! function_exists( 'oras_member_hub_sidebar_module_membership_status' ) ) {
	/**
	 * Render sidebar membership status module.
	 *
	 * @return string
	 */
	function oras_member_hub_sidebar_module_membership_status() {
		$current_user = wp_get_current_user();
		$status       = (string) apply_filters( 'oras_member_hub_membership_status_value', __( 'Active (placeholder)', 'oras-member-hub' ) );
		$renewal      = (string) apply_filters( 'oras_member_hub_membership_renewal_value', __( 'Not connected', 'oras-member-hub' ) );

		$content  = '<p><strong>' . esc_html__( 'Member:', 'oras-member-hub' ) . '</strong> ' . esc_html( $current_user->display_name ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Status:', 'oras-member-hub' ) . '</strong> ' . esc_html( $status ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Renewal:', 'oras-member-hub' ) . '</strong> ' . esc_html( $renewal ) . '</p>';

		$output = oras_member_hub_wrap_module(
			'membership-status',
			__( 'Membership Status', 'oras-member-hub' ),
			$content,
			'sidebar'
		);

		return (string) apply_filters( 'oras_member_hub_sidebar_membership_status_output', $output, $status, $renewal );
	}
}

if ( ! function_exists( 'oras_member_hub_sidebar_module_account_links' ) ) {
	/**
	 * Render sidebar account links module.
	 *
	 * @return string
	 */
	function oras_member_hub_sidebar_module_account_links() {
		$links = apply_filters(
			'oras_member_hub_account_links_items',
			array(
				array(
					'label' => __( 'My Account Dashboard', 'oras-member-hub' ),
					'url'   => oras_member_hub_account_url(),
				),
				array(
					'label' => __( 'Billing / Shipping Addresses', 'oras-member-hub' ),
					'url'   => oras_member_hub_account_url( 'edit-address' ),
				),
			)
		);

		$content = '<ul class="oras-member-hub__account-links">';

		foreach ( $links as $link ) {
			$label = isset( $link['label'] ) ? (string) $link['label'] : '';
			$url   = ! empty( $link['url'] ) ? (string) $link['url'] : '#';
			$content .= '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
		}

		$content .= '</ul>';

		$output = oras_member_hub_wrap_module(
			'account-links',
			__( 'Account Links', 'oras-member-hub' ),
			$content,
			'sidebar'
		);

		return (string) apply_filters( 'oras_member_hub_sidebar_account_links_output', $output, $links );
	}
}

if ( ! function_exists( 'oras_member_hub_sidebar_module_order_history' ) ) {
	/**
	 * Render sidebar order history link module.
	 *
	 * @return string
	 */
	function oras_member_hub_sidebar_module_order_history() {
		$order_url = (string) apply_filters( 'oras_member_hub_order_history_url', oras_member_hub_account_url( 'orders' ) );
		$content   = '<p><a href="' . esc_url( $order_url ) . '">' . esc_html__( 'View Order History', 'oras-member-hub' ) . '</a></p>';

		$output = oras_member_hub_wrap_module(
			'order-history',
			__( 'Order History', 'oras-member-hub' ),
			$content,
			'sidebar'
		);

		return (string) apply_filters( 'oras_member_hub_sidebar_order_history_output', $output, $order_url );
	}
}

if ( ! function_exists( 'oras_member_hub_sidebar_module_profile_settings' ) ) {
	/**
	 * Render sidebar profile settings link module.
	 *
	 * @return string
	 */
	function oras_member_hub_sidebar_module_profile_settings() {
		$profile_url = (string) apply_filters( 'oras_member_hub_profile_settings_url', oras_member_hub_account_url( 'edit-account' ) );
		$content     = '<p><a href="' . esc_url( $profile_url ) . '">' . esc_html__( 'Manage Profile / Settings', 'oras-member-hub' ) . '</a></p>';

		$output = oras_member_hub_wrap_module(
			'profile-settings',
			__( 'Profile / Settings', 'oras-member-hub' ),
			$content,
			'sidebar'
		);

		return (string) apply_filters( 'oras_member_hub_sidebar_profile_settings_output', $output, $profile_url );
	}
}
