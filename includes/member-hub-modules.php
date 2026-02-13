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
			'oras-card oras-member-hub__module oras-member-hub__module--%1$s oras-member-hub__module--%2$s',
			sanitize_html_class( $context ),
			sanitize_html_class( $module_id )
		);

		$module_anchor_id = 'oras-hub-module-' . sanitize_html_class( $module_id );

		$output  = '<section id="' . esc_attr( $module_anchor_id ) . '" class="' . esc_attr( $classes ) . '" data-module="' . esc_attr( $module_id ) . '">';
		$output .= '<h2 class="oras-card__title oras-member-hub__module-title">' . esc_html( $title ) . '</h2>';
		$output .= '<div class="oras-card__body oras-member-hub__module-content">' . $content_html . '</div>';
		$output .= '</section>';

		return (string) apply_filters( 'oras_member_hub_module_output', $output, $module_id, $context, $title, $content_html );
	}
}

if ( ! function_exists( 'oras_member_hub_module_conditions_summary' ) ) {
	/**
	 * Render Tonight's Observing Briefing.
	 *
	 * @return string
	 */
	function oras_member_hub_module_conditions_summary() {
		$payload = class_exists( 'ORAS_MH_Conditions_Service' )
			? ORAS_MH_Conditions_Service::get_payload()
			: array(
				'score'              => 0,
				'badge'              => __( 'Poor', 'oras-member-hub' ),
				'message'            => __( 'Conditions service unavailable.', 'oras-member-hub' ),
				'moon_phase'         => __( 'Unknown', 'oras-member-hub' ),
				'is_connected'       => false,
				'connection_message' => __( 'Live feed unavailable.', 'oras-member-hub' ),
				'cache_note'         => '',
				'site'               => array(
					'name'       => 'Oil Region Astronomical Society Observatory',
					'address'    => '4249 Camp Coffman Road, Cranberry, Pennsylvania 16319',
					'city_short' => 'Cranberry, PA',
					'lat'        => 41.321903,
					'lon'        => -79.585394,
					'elevation'  => 1420,
				),
				'tiles'              => array(),
			);

		$conditions = isset( $payload['conditions'] ) && is_array( $payload['conditions'] ) ? $payload['conditions'] : array();
		$astronomy  = isset( $payload['astronomy'] ) && is_array( $payload['astronomy'] ) ? $payload['astronomy'] : array();
		$moon       = isset( $payload['moon'] ) && is_array( $payload['moon'] ) ? $payload['moon'] : array();

		$score      = isset( $payload['score'] ) ? (int) $payload['score'] : 0;
		$badge      = isset( $payload['badge'] ) ? (string) $payload['badge'] : __( 'Poor', 'oras-member-hub' );
		$message    = isset( $payload['message'] ) ? (string) $payload['message'] : '';
		$moon_phase = isset( $payload['moon_phase'] ) ? (string) $payload['moon_phase'] : __( 'Unknown', 'oras-member-hub' );
		$site       = isset( $payload['site'] ) && is_array( $payload['site'] ) ? $payload['site'] : array();
		$site_name  = isset( $site['name'] ) ? (string) $site['name'] : 'Oil Region Astronomical Society Observatory';
		$address    = isset( $site['address'] ) ? (string) $site['address'] : '4249 Camp Coffman Road, Cranberry, Pennsylvania 16319';
		$city_short = isset( $site['city_short'] ) ? (string) $site['city_short'] : 'Cranberry, PA';
		$lat        = isset( $site['lat'] ) ? (string) $site['lat'] : '41.321903';
		$lon        = isset( $site['lon'] ) ? (string) $site['lon'] : '-79.585394';
		$elevation  = isset( $site['elevation'] ) ? (string) $site['elevation'] : '1420';
		$cache_note = isset( $payload['cache_note'] ) ? (string) $payload['cache_note'] : '';
		$generated  = isset( $payload['generated_at'] ) ? (string) $payload['generated_at'] : '';

		$cloud_cover = isset( $conditions['cloud_cover_pct'] ) && is_numeric( $conditions['cloud_cover_pct'] ) ? (float) $conditions['cloud_cover_pct'] : null;
		$precip_prob = isset( $conditions['precip_probability'] ) && is_numeric( $conditions['precip_probability'] ) ? (float) $conditions['precip_probability'] : null;
		$wind_mph    = isset( $conditions['wind_mph'] ) && is_numeric( $conditions['wind_mph'] ) ? (float) $conditions['wind_mph'] : null;
		$humidity    = isset( $conditions['humidity_pct'] ) && is_numeric( $conditions['humidity_pct'] ) ? (float) $conditions['humidity_pct'] : null;
		$temp_f      = isset( $conditions['temperature_f'] ) && is_numeric( $conditions['temperature_f'] ) ? (float) $conditions['temperature_f'] : null;
		$moon_illum  = isset( $moon['illumination'] ) && is_numeric( $moon['illumination'] ) ? (float) $moon['illumination'] : null;

		$time_format   = (string) get_option( 'time_format', 'g:i A' );
		$sunset_ts     = isset( $astronomy['sunset_ts'] ) ? (int) $astronomy['sunset_ts'] : 0;
		$astro_dark_ts = isset( $astronomy['astro_dark_ts'] ) ? (int) $astronomy['astro_dark_ts'] : 0;

		$last_updated_minutes = 0;
		if ( '' !== $generated ) {
			$generated_ts = strtotime( $generated );
			if ( false !== $generated_ts ) {
				$last_updated_minutes = (int) floor( max( 0, current_time( 'timestamp' ) - $generated_ts ) / MINUTE_IN_SECONDS );
			}
		}

		$last_updated_text = ( '' !== $generated )
			? sprintf(
				/* translators: %d: number of minutes. */
				__( '%d min ago', 'oras-member-hub' ),
				$last_updated_minutes
			)
			: __( 'Unknown', 'oras-member-hub' );

		$status_for_value = static function ( $value, $good_max, $warn_max ) {
			if ( null === $value ) {
				return 'oras-status-neutral';
			}

			if ( $value <= $good_max ) {
				return 'oras-status-good';
			}

			if ( $value <= $warn_max ) {
				return 'oras-status-warning';
			}

			return 'oras-status-critical';
		};

		$metrics = array(
			array(
				'key'    => 'cloud',
				'label'  => __( 'Cloud Cover', 'oras-member-hub' ),
				'value'  => null !== $cloud_cover ? (string) round( $cloud_cover ) : '—',
				'unit'   => null !== $cloud_cover ? '%' : '',
				'status' => $status_for_value( $cloud_cover, 20, 50 ),
			),
			array(
				'key'    => 'precip',
				'label'  => __( 'Precip Chance', 'oras-member-hub' ),
				'value'  => null !== $precip_prob ? (string) round( $precip_prob ) : '—',
				'unit'   => null !== $precip_prob ? '%' : '',
				'status' => $status_for_value( $precip_prob, 15, 40 ),
			),
			array(
				'key'    => 'wind',
				'label'  => __( 'Wind', 'oras-member-hub' ),
				'value'  => null !== $wind_mph ? (string) round( $wind_mph, 1 ) : '—',
				'unit'   => null !== $wind_mph ? __( 'mph', 'oras-member-hub' ) : '',
				'status' => $status_for_value( $wind_mph, 8, 15 ),
			),
			array(
				'key'    => 'humidity',
				'label'  => __( 'Humidity', 'oras-member-hub' ),
				'value'  => null !== $humidity ? (string) round( $humidity ) : '—',
				'unit'   => null !== $humidity ? '%' : '',
				'status' => 'oras-status-neutral',
			),
			array(
				'key'    => 'temp',
				'label'  => __( 'Temperature', 'oras-member-hub' ),
				'value'  => null !== $temp_f ? (string) round( $temp_f, 1 ) : '—',
				'unit'   => null !== $temp_f ? '°F' : '',
				'status' => 'oras-status-neutral',
			),
			array(
				'key'    => 'moon',
				'label'  => __( 'Moon Illumination', 'oras-member-hub' ),
				'value'  => null !== $moon_illum ? (string) round( $moon_illum ) : '—',
				'unit'   => null !== $moon_illum ? '%' : '',
				'sub'    => $moon_phase,
				'status' => $status_for_value( $moon_illum, 25, 60 ),
			),
			array(
				'key'    => 'sunset',
				'label'  => __( 'Sunset', 'oras-member-hub' ),
				'value'  => $sunset_ts > 0 ? wp_date( $time_format, $sunset_ts ) : '—',
				'unit'   => '',
				'status' => 'oras-status-neutral',
			),
			array(
				'key'    => 'astro-dark',
				'label'  => __( 'Astronomical Darkness', 'oras-member-hub' ),
				'value'  => $astro_dark_ts > 0 ? wp_date( $time_format, $astro_dark_ts ) : '—',
				'unit'   => '',
				'status' => 'oras-status-neutral',
			),
		);

		$metrics = apply_filters( 'oras_member_hub_conditions_items', $metrics, $payload );

		$badge_class = 'oras-status-neutral';
		if ( $score >= 75 ) {
			$badge_class = 'oras-status-good';
		} elseif ( $score >= 45 ) {
			$badge_class = 'oras-status-warning';
		} else {
			$badge_class = 'oras-status-critical';
		}

		$content  = '<div class="oras-conditions-briefing">';
		$content .= '<div class="oras-briefing-hero">';
		$content .= '<div class="oras-score">';
		$content .= '<p class="oras-score__label">' . esc_html__( 'Observing Score', 'oras-member-hub' ) . '</p>';
		$content .= '<p class="oras-score__value"><span class="oras-score__num">' . esc_html( $score ) . '</span><span class="oras-score__den">/100</span></p>';
		$content .= '<p class="oras-score__message">' . esc_html( $message ) . '</p>';
		$content .= '</div>';
		$content .= '<div class="oras-briefing-meta">';
		$content .= '<span class="oras-badge ' . esc_attr( $badge_class ) . '">' . esc_html( strtoupper( $badge ) ) . '</span>';
		$content .= '<p class="oras-briefing-meta__live">' . esc_html__( 'Live at ORAS Observatory • Cranberry, PA', 'oras-member-hub' ) . '</p>';
		$content .= '<p class="oras-briefing-meta__updated">' . esc_html__( 'Last updated:', 'oras-member-hub' ) . ' ' . esc_html( $last_updated_text ) . '</p>';
		$content .= '</div>';
		$content .= '</div>';

		if ( ! empty( $payload['is_connected'] ) ) {
			$content .= '<p class="oras-conditions-briefing__connection">' . esc_html__( 'Live Conditions – ORAS Observatory', 'oras-member-hub' ) . '</p>';
		} else {
			$connection_message = isset( $payload['connection_message'] ) ? (string) $payload['connection_message'] : (string) __( 'Live feed unavailable.', 'oras-member-hub' );
			$content           .= '<p class="oras-conditions-briefing__connection">' . esc_html( $connection_message ) . '</p>';
			if ( '' !== $cache_note ) {
				$content .= '<p class="oras-conditions-briefing__connection">' . esc_html( $cache_note ) . '</p>';
			}
		}

		$content .= '<div class="oras-metric-grid">';

		foreach ( $metrics as $item ) {
			$key         = isset( $item['key'] ) ? sanitize_html_class( (string) $item['key'] ) : 'condition';
			$label       = isset( $item['label'] ) ? (string) $item['label'] : '';
			$value       = isset( $item['value'] ) ? (string) $item['value'] : '—';
			$unit        = isset( $item['unit'] ) ? (string) $item['unit'] : '';
			$sub         = isset( $item['sub'] ) ? (string) $item['sub'] : '';
			$status      = isset( $item['status'] ) ? sanitize_html_class( (string) $item['status'] ) : 'oras-status-neutral';
			$tile_class  = 'oras-metric oras-status-neutral ' . $status . ' oras-metric--' . $key;

			$content .= '<div class="' . esc_attr( $tile_class ) . '">';
			$content .= '<strong class="oras-metric__label">' . esc_html( $label ) . '</strong>';
			$content .= '<p class="oras-metric__value-row"><span class="oras-metric__value">' . esc_html( $value ) . '</span>';
			if ( '' !== $unit ) {
				$content .= '<span class="oras-metric__unit">' . esc_html( $unit ) . '</span>';
			}
			$content .= '</p>';
			if ( '' !== $sub ) {
				$content .= '<p class="oras-metric__sub">' . esc_html( $sub ) . '</p>';
			}
			$content .= '</div>';
		}

		$content .= '</div>';
		$content .= '<div class="oras-csc-mini">';
		$content .= '<p class="oras-csc-mini__title">' . esc_html__( 'Clear Sky Chart (Forecast)', 'oras-member-hub' ) . '</p>';
		$content .= '<a href="https://www.cleardarksky.com/c/ORASOb2PAkey.html" target="_blank" rel="noopener noreferrer">';
		$content .= '<img src="https://www.cleardarksky.com/c/ORASOb2PAcsk.gif?c=277232" alt="' . esc_attr__( 'Clear Sky Chart — ORAS Observatory', 'oras-member-hub' ) . '" loading="lazy" />';
		$content .= '</a>';
		$content .= '<p class="oras-csc-mini__note">' . esc_html__( 'Tap chart to open full forecast', 'oras-member-hub' ) . '</p>';
		$content .= '</div>';
		$content .= '<details class="oras-station-details">';
		$content .= '<summary>' . esc_html__( 'Station details', 'oras-member-hub' ) . '</summary>';
		$content .= '<p>' . esc_html( $site_name ) . '</p>';
		$content .= '<p>' . esc_html( $address ) . '</p>';
		$content .= '<p>' . esc_html__( 'Lat:', 'oras-member-hub' ) . ' ' . esc_html( $lat ) . ' · ' . esc_html__( 'Lon:', 'oras-member-hub' ) . ' ' . esc_html( $lon ) . ' · ' . esc_html__( 'Elevation:', 'oras-member-hub' ) . ' ' . esc_html( $elevation ) . ' ft</p>';
		$content .= '<p>' . esc_html( $city_short ) . '</p>';
		$content .= '</details>';
		$content .= '</div>';

		$output = oras_member_hub_wrap_module(
			'conditions-summary',
			__( 'Tonight at ORAS Observatory', 'oras-member-hub' ),
			$content,
			'main'
		);

		$output = str_replace( 'oras-card ', 'oras-card oras-card--tonight ', $output );

		return (string) apply_filters( 'oras_member_hub_module_conditions_summary_output', $output, $metrics );
	}
}

if ( ! function_exists( 'oras_mh_extract_order_ids_from_group' ) ) {
	/**
	 * Extract unique order IDs from a ticket event-group payload.
	 *
	 * @param array<string, mixed> $group Event group payload.
	 * @return array<int, int>
	 */
	function oras_mh_extract_order_ids_from_group( array $group ) {
		$order_ids = array();

		if ( isset( $group['order_id'] ) && is_numeric( $group['order_id'] ) ) {
			$order_ids[] = (int) $group['order_id'];
		}

		if ( isset( $group['latest_order_id'] ) && is_numeric( $group['latest_order_id'] ) ) {
			$order_ids[] = (int) $group['latest_order_id'];
		}

		if ( isset( $group['order_ids'] ) && is_array( $group['order_ids'] ) ) {
			foreach ( $group['order_ids'] as $candidate ) {
				if ( is_numeric( $candidate ) ) {
					$order_ids[] = (int) $candidate;
				}
			}
		}

		if ( isset( $group['orders'] ) && is_array( $group['orders'] ) ) {
			foreach ( $group['orders'] as $order ) {
				if ( ! is_array( $order ) ) {
					continue;
				}

				if ( isset( $order['order_id'] ) && is_numeric( $order['order_id'] ) ) {
					$order_ids[] = (int) $order['order_id'];
				} elseif ( isset( $order['id'] ) && is_numeric( $order['id'] ) ) {
					$order_ids[] = (int) $order['id'];
				}
			}
		}

		$order_ids = array_values( array_unique( array_filter( $order_ids ) ) );

		return $order_ids;
	}
}

if ( ! function_exists( 'oras_mh_get_attending_events_map' ) ) {
	/**
	 * Build a per-event attendance map for current user from ORAS Tickets REST.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array<string, mixed>>
	 */
	function oras_mh_get_attending_events_map( $user_id ) {
		$user_id = (int) $user_id;

		if ( $user_id <= 0 || ! function_exists( 'rest_do_request' ) ) {
			return array();
		}

		if ( function_exists( 'oras_mh_has_oras_tickets_routes' ) && ! oras_mh_has_oras_tickets_routes() ) {
			return array();
		}

		$transient_key = 'oras_mh_attending_events_' . $user_id;
		$cached        = get_transient( $transient_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$event_map = array();
		$paths     = array(
			'/oras-tickets/v1/me/tickets/summary',
			'/oras-tickets/v1/me/tickets',
		);

		foreach ( $paths as $path ) {
			$request = new WP_REST_Request( 'GET', $path );

			if ( '/oras-tickets/v1/me/tickets' === $path ) {
				$request->set_param( 'scope', 'all' );
				$request->set_param( 'group_by', 'event' );
				$request->set_param( 'per_page', 50 );
			}

			$response = rest_do_request( $request );
			$status   = (int) $response->get_status();

			if ( 401 === $status || 403 === $status ) {
				continue;
			}

			if ( $status < 200 || $status >= 300 ) {
				continue;
			}

			$data = $response->get_data();

			if ( ! is_array( $data ) ) {
				continue;
			}

			if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
				$data = $data['data'];
			}

			$buckets = array();
			if ( isset( $data['upcoming'] ) && is_array( $data['upcoming'] ) ) {
				$buckets[] = $data['upcoming'];
			}
			if ( isset( $data['past'] ) && is_array( $data['past'] ) ) {
				$buckets[] = $data['past'];
			}
			if ( isset( $data['events'] ) && is_array( $data['events'] ) ) {
				$buckets[] = $data['events'];
			}

			foreach ( $buckets as $bucket ) {
				foreach ( $bucket as $group ) {
					if ( ! is_array( $group ) || ! isset( $group['event_id'] ) || ! is_numeric( $group['event_id'] ) ) {
						continue;
					}

					$event_id  = (int) $group['event_id'];
					$order_ids = oras_mh_extract_order_ids_from_group( $group );

					if ( ! isset( $event_map[ $event_id ] ) ) {
						$event_map[ $event_id ] = array(
							'attending' => true,
							'order_ids' => array(),
						);
					}

					$event_map[ $event_id ]['order_ids'] = array_values(
						array_unique(
							array_merge( $event_map[ $event_id ]['order_ids'], $order_ids )
						)
					);
				}
			}

			if ( ! empty( $event_map ) ) {
				break;
			}
		}

		set_transient( $transient_key, $event_map, 120 );

		return $event_map;
	}
}

if ( ! function_exists( 'oras_mh_format_event_countdown' ) ) {
	/**
	 * Format countdown text for an event row.
	 *
	 * @param int  $now_ts Current timestamp.
	 * @param int  $start_ts Event start timestamp.
	 * @param int  $end_ts Event end timestamp.
	 * @param bool $is_ongoing Whether event is currently in progress.
	 * @return string
	 */
	function oras_mh_format_event_countdown( $now_ts, $start_ts, $end_ts, $is_ongoing ) {
		$now_ts   = (int) $now_ts;
		$start_ts = (int) $start_ts;
		$end_ts   = (int) $end_ts;

		if ( $is_ongoing ) {
			if ( $end_ts > $now_ts ) {
				$diff_hours = (int) ceil( ( $end_ts - $now_ts ) / HOUR_IN_SECONDS );
				$diff_hours = max( 1, $diff_hours );

				return sprintf(
					/* translators: %d: hour count. */
					_n( 'Ends in %d hour', 'Ends in %d hours', $diff_hours, 'oras-member-hub' ),
					$diff_hours
				);
			}

			return (string) __( 'Ongoing', 'oras-member-hub' );
		}

		if ( $start_ts > $now_ts ) {
			$diff = $start_ts - $now_ts;

			if ( $diff >= DAY_IN_SECONDS ) {
				$days = (int) ceil( $diff / DAY_IN_SECONDS );

				return sprintf(
					/* translators: %d: day count. */
					_n( 'in %d day', 'in %d days', $days, 'oras-member-hub' ),
					$days
				);
			}

			$hours = (int) ceil( $diff / HOUR_IN_SECONDS );
			$hours = max( 1, $hours );

			return sprintf(
				/* translators: %d: hour count. */
				_n( 'in %d hour', 'in %d hours', $hours, 'oras-member-hub' ),
				$hours
			);
		}

		return '';
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
		$now_time    = (int) current_time( 'timestamp' );
		$epoch_start = '1970-01-01 00:00:00';
		$time_format = (string) get_option( 'date_format', 'M j, Y' ) . ' ' . (string) get_option( 'time_format', 'g:i A' );
		$attending   = is_user_logged_in() ? oras_mh_get_attending_events_map( get_current_user_id() ) : array();

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

					if ( false !== $start_time && false !== $end_time ) {
						$is_ongoing = ( $start_time <= $now_time && $end_time >= $now_time );
					}

					$items[] = array(
						'event_id'    => $event_id,
						'title'     => get_the_title( $event_id ),
						'url'       => get_permalink( $event_id ),
						'start_raw' => $start_raw,
						'start_ts'  => false !== $start_time ? (int) $start_time : 0,
						'end_ts'    => false !== $end_time ? (int) $end_time : 0,
						'start'     => tribe_get_start_date( $event_id, false, $time_format ),
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

					if ( false !== $start_time && false !== $end_time ) {
						$is_ongoing = ( $start_time <= $now_time && $end_time >= $now_time );
					}

					$items[] = array(
						'event_id'    => $event_id,
						'title'     => get_the_title(),
						'url'       => get_permalink(),
						'start_raw' => $start_raw,
						'start_ts'  => false !== $start_time ? (int) $start_time : 0,
						'end_ts'    => false !== $end_time ? (int) $end_time : 0,
						'start'     => $timestamp ? wp_date( $time_format, $timestamp ) : '',
						'is_ongoing' => $is_ongoing,
					);
				}
			}

			wp_reset_postdata();
		}

		if ( ! empty( $items ) ) {
			foreach ( $items as $index => $item ) {
				$event_id        = isset( $item['event_id'] ) ? (int) $item['event_id'] : 0;
				$is_attending    = $event_id > 0 && isset( $attending[ $event_id ] );
				$is_ongoing      = ! empty( $item['is_ongoing'] );
				$start_ts        = isset( $item['start_ts'] ) ? (int) $item['start_ts'] : 0;
				$end_ts          = isset( $item['end_ts'] ) ? (int) $item['end_ts'] : 0;
				$status_label    = $is_attending ? __( 'Attending', 'oras-member-hub' ) : ( $is_ongoing ? __( 'Ongoing', 'oras-member-hub' ) : __( 'Upcoming', 'oras-member-hub' ) );
				$status_class    = $is_attending ? 'oras-event-pill--attending' : ( $is_ongoing ? 'oras-event-pill--ongoing' : 'oras-event-pill--upcoming' );
				$status_priority = $is_attending ? 0 : ( $is_ongoing ? 1 : 2 );
				$countdown       = oras_mh_format_event_countdown( $now_time, $start_ts, $end_ts, $is_ongoing );
				$order_ids       = $is_attending && isset( $attending[ $event_id ]['order_ids'] ) && is_array( $attending[ $event_id ]['order_ids'] )
					? array_values( array_unique( array_map( 'intval', $attending[ $event_id ]['order_ids'] ) ) )
					: array();

				$items[ $index ]['is_attending']    = $is_attending;
				$items[ $index ]['status_label']    = $status_label;
				$items[ $index ]['status_class']    = $status_class;
				$items[ $index ]['status_priority'] = $status_priority;
				$items[ $index ]['countdown']       = $countdown;
				$items[ $index ]['order_ids']       = $order_ids;
			}

			usort(
				$items,
				static function ( $a, $b ) {
					$a_priority = isset( $a['status_priority'] ) ? (int) $a['status_priority'] : 3;
					$b_priority = isset( $b['status_priority'] ) ? (int) $b['status_priority'] : 3;

					if ( $a_priority !== $b_priority ) {
						return $a_priority <=> $b_priority;
					}

					$a_time = ! empty( $a['start_ts'] ) ? (int) $a['start_ts'] : 0;
					$b_time = ! empty( $b['start_ts'] ) ? (int) $b['start_ts'] : 0;

					if ( 1 === $a_priority ) {
						$a_time = ! empty( $a['end_ts'] ) ? (int) $a['end_ts'] : $a_time;
						$b_time = ! empty( $b['end_ts'] ) ? (int) $b['end_ts'] : $b_time;
					}

					return $a_time <=> $b_time;
				}
			);

			$items = array_slice( $items, 0, $limit );
		}

		$items = apply_filters( 'oras_mh_upcoming_events_items', $items, $limit );

		$content = '<div class="oras-events-hud">';

		if ( empty( $items ) ) {
			$content .= '<p>' . esc_html__( 'No upcoming events found yet.', 'oras-member-hub' ) . '</p>';
		} else {
			$content .= '<ul class="oras-events-hud__list">';

			foreach ( $items as $item ) {
				$title        = isset( $item['title'] ) ? (string) $item['title'] : '';
				$url          = isset( $item['url'] ) ? (string) $item['url'] : '';
				$start        = isset( $item['start'] ) ? (string) $item['start'] : '';
				$countdown    = isset( $item['countdown'] ) ? (string) $item['countdown'] : '';
				$status_label = isset( $item['status_label'] ) ? (string) $item['status_label'] : (string) __( 'Upcoming', 'oras-member-hub' );
				$status_class = isset( $item['status_class'] ) ? sanitize_html_class( (string) $item['status_class'] ) : 'oras-event-pill--upcoming';
				$event_id     = isset( $item['event_id'] ) ? (int) $item['event_id'] : 0;
				$order_ids    = isset( $item['order_ids'] ) && is_array( $item['order_ids'] ) ? array_values( array_filter( array_map( 'intval', $item['order_ids'] ) ) ) : array();

				$content .= '<li class="oras-events-hud__row">';
				$content .= '<div class="oras-events-hud__main">';
				$content .= '<p class="oras-events-hud__title"><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></p>';
				$content .= '<p class="oras-events-hud__meta">' . esc_html( $start ) . '</p>';
				$content .= '</div>';
				$content .= '<div class="oras-events-hud__side">';
				$content .= '<span class="oras-events-hud__pill ' . esc_attr( $status_class ) . '">' . esc_html( strtoupper( $status_label ) ) . '</span>';
				if ( '' !== $countdown ) {
					$content .= '<p class="oras-events-hud__countdown">' . esc_html( $countdown ) . '</p>';
				}
				$content .= '</div>';

				$content .= '<div class="oras-events-hud__actions">';
				$content .= '<a class="oras-events-hud__action-link" href="' . esc_url( $url ) . '">' . esc_html__( 'View Event', 'oras-member-hub' ) . '</a>';

				if ( ! empty( $item['is_attending'] ) ) {
					if ( 1 === count( $order_ids ) ) {
						$print_url = add_query_arg(
							array(
								'order_id' => (int) $order_ids[0],
								'event_id' => $event_id,
							),
							site_url( '/oras-ticket/print' )
						);

						$content .= '<a class="button oras-events-hud__print-button" href="' . esc_url( $print_url ) . '">' . esc_html__( 'Print Tickets', 'oras-member-hub' ) . '</a>';
					} elseif ( count( $order_ids ) > 1 ) {
						$content .= '<span class="oras-events-hud__print-note">' . esc_html__( 'Print Tickets', 'oras-member-hub' ) . '</span>';
						$content .= '<ul class="oras-events-hud__print-list">';

						foreach ( $order_ids as $order_id ) {
							$print_url = add_query_arg(
								array(
									'order_id' => (int) $order_id,
									'event_id' => $event_id,
								),
								site_url( '/oras-ticket/print' )
							);

							$content .= '<li><a class="oras-events-hud__print-link" href="' . esc_url( $print_url ) . '">' . sprintf(
								esc_html__( 'Order #%d — Print', 'oras-member-hub' ),
								(int) $order_id
							) . '</a></li>';
						}

						$content .= '</ul>';
					} else {
						$content .= '<span class="oras-events-hud__print-unavailable">' . esc_html__( 'Print unavailable', 'oras-member-hub' ) . '</span>';
					}
				}

				$content .= '</div>';
				$content .= '</li>';
			}

			$content .= '</ul>';
		}

		$content .= '</div>';

		$output = oras_member_hub_wrap_module(
			'upcoming-events',
			__( 'Upcoming Events', 'oras-member-hub' ),
			$content,
			'main'
		);

		return (string) apply_filters( 'oras_mh_upcoming_events_output', $output, $items );
	}
}

if ( ! function_exists( 'oras_mh_has_oras_tickets_routes' ) ) {
	/**
	 * Determine whether ORAS Tickets REST routes are registered.
	 *
	 * @return bool
	 */
	function oras_mh_has_oras_tickets_routes() {
		if ( ! function_exists( 'rest_get_server' ) ) {
			return false;
		}

		$server = rest_get_server();

		if ( ! is_object( $server ) || ! method_exists( $server, 'get_routes' ) ) {
			return false;
		}

		$routes = $server->get_routes();

		if ( ! is_array( $routes ) ) {
			return false;
		}

		return isset( $routes['/oras-tickets/v1/me/tickets'] ) || isset( $routes['/oras-tickets/v1/me/tickets/summary'] );
	}
}

if ( ! function_exists( 'oras_member_hub_module_my_tickets_reminders' ) ) {
	/**
	 * Render My Tickets / Reminders module.
	 *
	 * @return string
	 */
	function oras_member_hub_module_my_tickets_reminders() {
		$items          = array();
		$service_note   = '';

		if ( ! is_user_logged_in() ) {
			$content = '<p>' . esc_html__( 'Please log in to view tickets.', 'oras-member-hub' ) . '</p>';

			$output = oras_member_hub_wrap_module(
				'my-tickets-reminders',
				__( 'My Tickets / My Event Reminders', 'oras-member-hub' ),
				$content,
				'main'
			);

			return (string) apply_filters( 'oras_member_hub_module_my_tickets_output', $output, $items );
		}

		if ( ! oras_mh_has_oras_tickets_routes() ) {
			$service_note = (string) __( 'Ticket service unavailable.', 'oras-member-hub' );
		}

		if ( shortcode_exists( 'oras_my_tickets' ) ) {
			$content = do_shortcode( '[oras_my_tickets]' );

			if ( '' === trim( wp_strip_all_tags( (string) $content ) ) ) {
				$content = '<p>' . esc_html__( 'Tickets are unavailable right now.', 'oras-member-hub' ) . '</p>';
			}
		} else {
			$fallback = (string) __( 'Tickets are unavailable right now.', 'oras-member-hub' );
			$items    = apply_filters( 'oras_member_hub_my_tickets_items', array( $fallback ) );
			$content  = '<p>' . esc_html( $fallback ) . '</p>';
		}

		if ( '' !== $service_note ) {
			$content .= '<p class="oras-member-hub__tickets-note">' . esc_html( $service_note ) . '</p>';
		}

		$output = oras_member_hub_wrap_module(
			'my-tickets-reminders',
			__( 'My Tickets / My Event Reminders', 'oras-member-hub' ),
			$content,
			'main'
		);

		return (string) apply_filters( 'oras_member_hub_module_my_tickets_output', $output, $items );
	}
}

if ( ! function_exists( 'oras_mh_get_observing_toolkit_items' ) ) {
	/**
	 * Get observing toolkit content and quick links.
	 *
	 * @return array<string, mixed>
	 */
	function oras_mh_get_observing_toolkit_items() {
		$toolkit = array(
			'links' => array(
				array(
					'title'       => __( 'AstroViewer Sky Chart', 'oras-member-hub' ),
					'url'         => 'https://www.astroviewer.net/',
					'description' => __( 'Live sky view and object positions for planning tonight’s targets.', 'oras-member-hub' ),
					'category'    => __( 'Sky', 'oras-member-hub' ),
				),
				array(
					'title'       => __( 'NOAA Aviation Weather', 'oras-member-hub' ),
					'url'         => 'https://aviationweather.gov/',
					'description' => __( 'Forecasts, METARs, radar, and aviation-grade weather overlays.', 'oras-member-hub' ),
					'category'    => __( 'Weather', 'oras-member-hub' ),
				),
				array(
					'title'       => __( 'Windy', 'oras-member-hub' ),
					'url'         => 'https://www.windy.com/',
					'description' => __( 'Interactive wind, cloud, and pressure visualizations.', 'oras-member-hub' ),
					'category'    => __( 'Weather', 'oras-member-hub' ),
				),
				array(
					'title'       => __( 'LightningMaps', 'oras-member-hub' ),
					'url'         => 'http://www.lightningmaps.org/#m=oss;t=3;s=0;o=0;b=;ts=0;y=41.3309;x=-79.5115;z=10;d=8;dl=2;dc=0;',
					'description' => __( 'Real-time lightning monitor centered near the ORAS observing site.', 'oras-member-hub' ),
					'category'    => __( 'Weather', 'oras-member-hub' ),
				),
				array(
					'title'       => __( 'NOAA SWPC', 'oras-member-hub' ),
					'url'         => 'https://www.swpc.noaa.gov/',
					'description' => __( 'Aurora and space weather outlooks from NOAA.', 'oras-member-hub' ),
					'category'    => __( 'Weather', 'oras-member-hub' ),
				),
				array(
					'title'       => __( 'LightPollutionMap.info', 'oras-member-hub' ),
					'url'         => 'https://www.lightpollutionmap.info/',
					'description' => __( 'Find darker skies and compare light pollution layers.', 'oras-member-hub' ),
					'category'    => __( 'Light Pollution', 'oras-member-hub' ),
				),
				array(
					'title'       => __( 'Equipment Use Tutorials', 'oras-member-hub' ),
					'url'         => 'https://astronomy.com/observing/equipment-use',
					'description' => __( 'Astronomy.com walkthroughs for setup and observing gear.', 'oras-member-hub' ),
					'category'    => __( 'Equipment', 'oras-member-hub' ),
				),
				array(
					'title'       => __( 'Mars Trek', 'oras-member-hub' ),
					'url'         => 'https://trek.nasa.gov/mars/',
					'description' => __( 'Interactive Mars maps, terrain layers, and planning views.', 'oras-member-hub' ),
					'category'    => __( 'Planets', 'oras-member-hub' ),
				),
				array(
					'title'       => __( 'Jupiter Moon Map', 'oras-member-hub' ),
					'url'         => 'https://shallowsky.com/jupiter/',
					'description' => __( 'Track Galilean moon positions, shadows, and GRS timing.', 'oras-member-hub' ),
					'category'    => __( 'Planets', 'oras-member-hub' ),
				),
				array(
					'title'       => __( 'Saturn Moons Tool', 'oras-member-hub' ),
					'url'         => 'https://skyandtelescope.org/wp-content/plugins/observing-tools/saturn_moons/saturn.html',
					'description' => __( 'Interactive map for the brighter Saturnian moons.', 'oras-member-hub' ),
					'category'    => __( 'Planets', 'oras-member-hub' ),
				),
			),
		);

		return apply_filters( 'oras_mh_observing_toolkit_items', $toolkit );
	}
}

if ( ! function_exists( 'oras_member_hub_module_resources' ) ) {
	/**
	 * Render observing toolkit module.
	 *
	 * @return string
	 */
	function oras_member_hub_module_resources() {
		$toolkit      = oras_mh_get_observing_toolkit_items();
		$links        = isset( $toolkit['links'] ) && is_array( $toolkit['links'] ) ? $toolkit['links'] : array();
		$allowed_categories = apply_filters( 'oras_mh_observing_toolkit_allowed_categories', array() );
		$allowed_categories = is_array( $allowed_categories ) ? array_map( 'strtolower', array_map( 'trim', $allowed_categories ) ) : array();

		$content = '<div class="oras-toolkit">';

		if ( empty( $links ) ) {
			$content .= '<p>' . esc_html__( 'Toolkit resources coming soon.', 'oras-member-hub' ) . '</p>';
		} else {
			if ( ! empty( $links ) ) {
				$content .= '<h4 class="oras-toolkit__heading">' . esc_html__( 'Quick Links', 'oras-member-hub' ) . '</h4>';
				$content .= '<ul class="oras-toolkit__grid">';

				foreach ( $links as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}

					$title       = isset( $item['title'] ) ? trim( (string) $item['title'] ) : '';
					$url         = isset( $item['url'] ) ? (string) $item['url'] : '';
					$description = isset( $item['description'] ) ? trim( (string) $item['description'] ) : '';
					$category    = isset( $item['category'] ) ? trim( (string) $item['category'] ) : '';
					$category_key = strtolower( $category );

					if ( '' === $title || '' === $url ) {
						continue;
					}

					if ( ! empty( $allowed_categories ) && ! in_array( $category_key, $allowed_categories, true ) ) {
						continue;
					}

					$is_visible = (bool) apply_filters( 'oras_mh_observing_toolkit_link_visible', true, $item, $category );
					if ( ! $is_visible ) {
						continue;
					}

					$content .= '<li class="oras-toolkit__row oras-toolkit__row--hud">';
					$content .= '<p class="oras-toolkit__title"><a class="oras-toolkit__link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $title ) . '</a> <span class="oras-toolkit__ext" aria-hidden="true">↗</span></p>';

					if ( '' !== $description ) {
						$content .= '<p class="oras-toolkit__desc">' . esc_html( $description ) . '</p>';
					}

					if ( '' !== $category ) {
						$content .= '<p class="oras-toolkit__meta">' . esc_html( $category ) . '</p>';
					}

					$content .= '</li>';
				}

				$content .= '</ul>';
			}
		}

		$content .= '</div>';

		$output = oras_member_hub_wrap_module(
			'resources',
			__( 'Observing Toolkit', 'oras-member-hub' ),
			$content,
			'main'
		);

		return (string) apply_filters( 'oras_member_hub_module_resources_output', $output, $toolkit );
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
		$manage_url = function_exists( 'pmpro_url' ) ? (string) pmpro_url( 'account' ) : (string) home_url( '/membership-account/' );

		if ( ! is_user_logged_in() ) {
			$content = sprintf(
				'<p>%1$s <a href="%2$s">%3$s</a>.</p>',
				esc_html__( 'Please log in to view your membership status.', 'oras-member-hub' ),
				esc_url( wp_login_url( get_permalink() ) ),
				esc_html__( 'Log in', 'oras-member-hub' )
			);

			$output = oras_member_hub_wrap_module(
				'membership-status',
				__( 'Membership Status', 'oras-member-hub' ),
				$content,
				'sidebar'
			);

			return (string) apply_filters( 'oras_member_hub_sidebar_membership_status_output', $output, '', '' );
		}

		$current_user = wp_get_current_user();

		if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
			$status  = (string) apply_filters( 'oras_member_hub_membership_status_value', __( 'Membership system unavailable.', 'oras-member-hub' ) );
			$renewal = (string) apply_filters( 'oras_member_hub_membership_renewal_value', __( 'Unknown', 'oras-member-hub' ) );

			$content  = '<p><strong>' . esc_html__( 'Member:', 'oras-member-hub' ) . '</strong> ' . esc_html( $current_user->display_name ) . '</p>';
			$content .= '<p><strong>' . esc_html__( 'Status:', 'oras-member-hub' ) . '</strong> ' . esc_html( $status ) . '</p>';
			$content .= '<p><strong>' . esc_html__( 'Auto-renew:', 'oras-member-hub' ) . '</strong> ' . esc_html( $renewal ) . '</p>';

			$output = oras_member_hub_wrap_module(
				'membership-status',
				__( 'Membership Status', 'oras-member-hub' ),
				$content,
				'sidebar'
			);

			return (string) apply_filters( 'oras_member_hub_sidebar_membership_status_output', $output, $status, $renewal );
		}

		$user_id = (int) $current_user->ID;
		$level   = pmpro_getMembershipLevelForUser( $user_id );

		if ( empty( $level ) ) {
			$status  = (string) apply_filters( 'oras_member_hub_membership_status_value', __( 'No active membership found.', 'oras-member-hub' ) );
			$renewal = (string) apply_filters( 'oras_member_hub_membership_renewal_value', __( 'Unknown', 'oras-member-hub' ) );

			$content  = '<p><strong>' . esc_html__( 'Member:', 'oras-member-hub' ) . '</strong> ' . esc_html( $current_user->display_name ) . '</p>';
			$content .= '<p><strong>' . esc_html__( 'Status:', 'oras-member-hub' ) . '</strong> ' . esc_html( $status ) . '</p>';
			$content .= '<p><strong>' . esc_html__( 'Auto-renew:', 'oras-member-hub' ) . '</strong> ' . esc_html( $renewal ) . '</p>';
			$content .= '<p><a class="button" href="' . esc_url( $manage_url ) . '">' . esc_html__( 'Manage / Renew Membership', 'oras-member-hub' ) . '</a></p>';

			if ( ! class_exists( 'WooCommerce' ) ) {
				$content .= '<p>' . esc_html__( 'Commerce features are currently unavailable.', 'oras-member-hub' ) . '</p>';
			}

			$output = oras_member_hub_wrap_module(
				'membership-status',
				__( 'Membership Status', 'oras-member-hub' ),
				$content,
				'sidebar'
			);

			return (string) apply_filters( 'oras_member_hub_sidebar_membership_status_output', $output, $status, $renewal );
		}

		$level_name       = isset( $level->name ) ? (string) $level->name : (string) __( 'Unknown', 'oras-member-hub' );
		$level_id         = isset( $level->id ) ? absint( $level->id ) : 0;
		$expiration_label = (string) __( 'Never', 'oras-member-hub' );
		$days_remaining   = '';
		$urgency_message  = '';
		$is_expired       = false;
		$enddate_raw      = isset( $level->enddate ) ? (string) $level->enddate : '';
		$end_timestamp    = $enddate_raw ? strtotime( $enddate_raw ) : false;

		if ( false !== $end_timestamp ) {
			$expiration_label = wp_date( get_option( 'date_format' ), $end_timestamp );
			$days_diff        = (int) floor( ( $end_timestamp - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );

			if ( $days_diff < 0 ) {
				$is_expired      = true;
				$days_remaining  = (string) __( 'Expired', 'oras-member-hub' );
				$urgency_message = (string) __( 'Your membership has expired.', 'oras-member-hub' );
			} else {
				$days_remaining = sprintf(
					/* translators: %d: days remaining. */
					_n( '%d day', '%d days', $days_diff, 'oras-member-hub' ),
					$days_diff
				);

				if ( $days_diff <= 7 ) {
					$urgency_message = (string) __( 'Urgent: your membership expires within 7 days.', 'oras-member-hub' );
				} elseif ( $days_diff <= 30 ) {
					$urgency_message = (string) __( 'Warning: your membership expires within 30 days.', 'oras-member-hub' );
				}
			}
		}

		$auto_renew = (string) __( 'Unknown', 'oras-member-hub' );

		if ( false !== $end_timestamp && function_exists( 'pmpro_getMemberOrders' ) ) {
			$latest_successful_order = null;
			$latest_successful_ts    = 0;
			$orders                  = pmpro_getMemberOrders( $user_id );

			if ( is_array( $orders ) ) {
				foreach ( $orders as $order ) {
					if ( ! is_object( $order ) ) {
						continue;
					}

					$status_raw = isset( $order->status ) ? strtolower( (string) $order->status ) : '';

					if ( 'success' !== $status_raw ) {
						continue;
					}

					$order_level_id = isset( $order->membership_id ) ? absint( $order->membership_id ) : 0;

					if ( $level_id > 0 && $order_level_id > 0 && $order_level_id !== $level_id ) {
						continue;
					}

					$order_ts = 0;
					if ( isset( $order->timestamp ) && is_numeric( $order->timestamp ) ) {
						$order_ts = (int) $order->timestamp;
					} elseif ( ! empty( $order->timestamp ) ) {
						$parsed_ts = strtotime( (string) $order->timestamp );
						$order_ts  = false !== $parsed_ts ? $parsed_ts : 0;
					} elseif ( ! empty( $order->date ) ) {
						$parsed_ts = strtotime( (string) $order->date );
						$order_ts  = false !== $parsed_ts ? $parsed_ts : 0;
					}

					if ( $order_ts >= $latest_successful_ts ) {
						$latest_successful_ts    = $order_ts;
						$latest_successful_order = $order;
					}
				}
			}

			if ( is_object( $latest_successful_order ) && ! empty( $latest_successful_order->subscription_transaction_id ) ) {
				$auto_renew = (string) __( 'On', 'oras-member-hub' );
			}
		}

		$status  = $level_name;
		$renewal = $auto_renew;

		$status  = (string) apply_filters( 'oras_member_hub_membership_status_value', $status );
		$renewal = (string) apply_filters( 'oras_member_hub_membership_renewal_value', $renewal );

		$content  = '<p><strong>' . esc_html__( 'Member:', 'oras-member-hub' ) . '</strong> ' . esc_html( $current_user->display_name ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Level:', 'oras-member-hub' ) . '</strong> ' . esc_html( $status ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Expiration:', 'oras-member-hub' ) . '</strong> ' . esc_html( $expiration_label ) . '</p>';

		if ( '' !== $days_remaining ) {
			$content .= '<p><strong>' . esc_html__( 'Days remaining:', 'oras-member-hub' ) . '</strong> ' . esc_html( $days_remaining ) . '</p>';
		}

		$content .= '<p><strong>' . esc_html__( 'Auto-renew:', 'oras-member-hub' ) . '</strong> ' . esc_html( $renewal ) . '</p>';

		if ( '' !== $urgency_message ) {
			$content .= '<p>' . esc_html( $urgency_message ) . '</p>';
		}

		if ( $is_expired ) {
			$content .= '<p><a class="button" href="' . esc_url( $manage_url ) . '">' . esc_html__( 'Manage / Renew Membership', 'oras-member-hub' ) . '</a></p>';
		}

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
