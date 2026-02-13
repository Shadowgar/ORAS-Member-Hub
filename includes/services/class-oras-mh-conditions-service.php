<?php
/**
 * Conditions service for the ORAS Member Hub observing briefing module.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORAS_MH_Conditions_Service' ) ) {
	/**
	 * Fetches weather data, computes astronomy values, and formats module payload.
	 */
	class ORAS_MH_Conditions_Service {
		/**
		 * Observatory coordinates.
		 */
		private const LATITUDE = 41.321903;
		private const LONGITUDE = -79.585394;
		private const ELEVATION_FEET = 1420;
		private const TRANSIENT_KEY = 'oras_mh_conditions_oras_observatory';

		/**
		 * Transient TTL in seconds (10 minutes).
		 */
		private const CACHE_TTL = 600;

		/**
		 * Build full payload from cache or live data.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_payload() {
			$cached = get_transient( self::TRANSIENT_KEY );

			if ( is_array( $cached ) ) {
				$cached['source'] = 'transient';

				return $cached;
			}

			$conditions = self::fetch_weather();

			if ( is_wp_error( $conditions ) ) {
				$last_success = get_option( 'oras_mh_conditions_last_payload', array() );

				if ( is_array( $last_success ) && ! empty( $last_success ) ) {
					$last_success['is_connected']       = false;
					$last_success['connection_message'] = __( 'Live feed unavailable.', 'oras-member-hub' );
					$last_success['cache_note']         = self::build_last_updated_note( isset( $last_success['generated_at'] ) ? (string) $last_success['generated_at'] : '' );
					$last_success['source']             = 'fallback-cache';

					return $last_success;
				}

				return self::format_payload_for_ui(
					array(
						'is_connected'       => false,
						'connection_message' => __( 'Live feed unavailable.', 'oras-member-hub' ),
						'cache_note'         => '',
						'conditions'         => array(),
						'astronomy'          => self::compute_astronomy(),
						'moon'               => self::compute_moon_phase(),
						'observing'          => array(
							'score'   => 0,
							'badge'   => __( 'Poor', 'oras-member-hub' ),
							'message' => __( 'Weather feed unavailable right now.', 'oras-member-hub' ),
						),
					)
				);
			}

			$astronomy = self::compute_astronomy();
			$moon      = self::compute_moon_phase();
			$observing = self::compute_observing_score( $conditions, $moon );

			$payload = self::format_payload_for_ui(
				array(
					'is_connected'       => true,
					'connection_message' => '',
					'cache_note'         => '',
					'conditions'         => $conditions,
					'astronomy'          => $astronomy,
					'moon'               => $moon,
					'observing'          => $observing,
				)
			);

			set_transient( self::TRANSIENT_KEY, $payload, self::CACHE_TTL );
			update_option( 'oras_mh_conditions_last_payload', $payload, false );

			return $payload;
		}

		/**
		 * Fetch current weather data from Open-Meteo.
		 *
		 * @return array<string, mixed>|\WP_Error
		 */
		public static function fetch_weather() {
			$query = array(
				'latitude'         => self::LATITUDE,
				'longitude'        => self::LONGITUDE,
				'elevation'        => round( self::ELEVATION_FEET * 0.3048, 2 ),
				'temperature_unit' => 'fahrenheit',
				'wind_speed_unit'  => 'mph',
				'timezone'         => 'auto',
				'current'          => implode(
					',',
					array(
						'temperature_2m',
						'relative_humidity_2m',
						'cloud_cover',
						'wind_speed_10m',
						'surface_pressure',
						'visibility',
					)
				),
				'hourly'           => 'precipitation_probability',
				'daily'            => 'sunset',
				'forecast_days'    => 1,
			);

			$url             = add_query_arg( $query, 'https://api.open-meteo.com/v1/forecast' );
			$remote_response = wp_remote_get(
				$url,
				array(
					'timeout' => 6,
				)
			);

			if ( is_wp_error( $remote_response ) ) {
				return $remote_response;
			}

			$status_code = (int) wp_remote_retrieve_response_code( $remote_response );

			if ( 200 !== $status_code ) {
				return new \WP_Error( 'oras_mh_conditions_http', __( 'Conditions endpoint returned a non-200 response.', 'oras-member-hub' ) );
			}

			$decoded = json_decode( (string) wp_remote_retrieve_body( $remote_response ), true );

			if ( ! is_array( $decoded ) ) {
				return new \WP_Error( 'oras_mh_conditions_json', __( 'Conditions endpoint returned invalid JSON.', 'oras-member-hub' ) );
			}

			$current       = isset( $decoded['current'] ) && is_array( $decoded['current'] ) ? $decoded['current'] : array();
			$hourly        = isset( $decoded['hourly'] ) && is_array( $decoded['hourly'] ) ? $decoded['hourly'] : array();
			$daily         = isset( $decoded['daily'] ) && is_array( $decoded['daily'] ) ? $decoded['daily'] : array();
			$precip_prob   = null;
			$current_time  = isset( $current['time'] ) ? (string) $current['time'] : '';
			$hourly_times  = isset( $hourly['time'] ) && is_array( $hourly['time'] ) ? array_values( $hourly['time'] ) : array();
			$hourly_precip = isset( $hourly['precipitation_probability'] ) && is_array( $hourly['precipitation_probability'] ) ? array_values( $hourly['precipitation_probability'] ) : array();

			if ( '' !== $current_time && ! empty( $hourly_times ) && ! empty( $hourly_precip ) ) {
				$index = array_search( $current_time, $hourly_times, true );

				if ( false === $index ) {
					$current_hour = substr( $current_time, 0, 13 );
					foreach ( $hourly_times as $hourly_index => $hourly_time ) {
						if ( 0 === strpos( (string) $hourly_time, $current_hour ) ) {
							$index = $hourly_index;
							break;
						}
					}
				}

				if ( false !== $index && isset( $hourly_precip[ $index ] ) ) {
					$precip_prob = self::to_float_or_null( $hourly_precip[ $index ] );
				}
			}

			$sunset_iso = null;
			if ( isset( $daily['sunset'] ) && is_array( $daily['sunset'] ) && isset( $daily['sunset'][0] ) ) {
				$sunset_iso = (string) $daily['sunset'][0];
			}

			return array(
				'temperature_f'        => self::to_float_or_null( $current['temperature_2m'] ?? null ),
				'wind_mph'             => self::to_float_or_null( $current['wind_speed_10m'] ?? null ),
				'humidity_pct'         => self::to_float_or_null( $current['relative_humidity_2m'] ?? null ),
				'cloud_cover_pct'      => self::to_float_or_null( $current['cloud_cover'] ?? null ),
				'precip_probability'   => $precip_prob,
				'pressure_hpa'         => self::to_float_or_null( $current['surface_pressure'] ?? null ),
				'visibility_m'         => self::to_float_or_null( $current['visibility'] ?? null ),
				'current_time'         => $current_time,
				'sunset_iso'           => $sunset_iso,
				'api_timezone'         => isset( $decoded['timezone'] ) ? (string) $decoded['timezone'] : '',
				'latitude'             => self::LATITUDE,
				'longitude'            => self::LONGITUDE,
				'elevation_ft'         => self::ELEVATION_FEET,
			);
		}

		/**
		 * Compute local astronomy times for today.
		 *
		 * @return array<string, int|null>
		 */
		public static function compute_astronomy() {
			$timezone   = wp_timezone();
			$local_noon = new \DateTimeImmutable( 'now', $timezone );
			$local_noon = $local_noon->setTime( 12, 0, 0 );
			$sun_info   = date_sun_info( $local_noon->getTimestamp(), self::LATITUDE, self::LONGITUDE );

			if ( ! is_array( $sun_info ) ) {
				return array(
					'sunset_ts'      => null,
					'astro_dark_ts'  => null,
				);
			}

			$sunset_ts     = isset( $sun_info['sunset'] ) ? (int) $sun_info['sunset'] : null;
			$astro_dark_ts = isset( $sun_info['astronomical_twilight_end'] ) ? (int) $sun_info['astronomical_twilight_end'] : null;

			return array(
				'sunset_ts'     => $sunset_ts,
				'astro_dark_ts' => $astro_dark_ts,
			);
		}

		/**
		 * Compute moon phase and illumination using a simple synodic approximation.
		 *
		 * Formula: phase fraction = ((now - known_new_moon) / synodic_month) mod 1
		 * Illumination: (1 - cos(2π * phase_fraction)) / 2
		 *
		 * @return array<string, mixed>
		 */
		public static function compute_moon_phase() {
			$known_new_moon_ts = 947182440;
			$synodic_days      = 29.53058867;
			$days_since        = ( time() - $known_new_moon_ts ) / DAY_IN_SECONDS;
			$cycle_day         = fmod( $days_since, $synodic_days );

			if ( $cycle_day < 0 ) {
				$cycle_day += $synodic_days;
			}

			$phase_fraction = $cycle_day / $synodic_days;
			$illumination   = ( 1 - cos( 2 * M_PI * $phase_fraction ) ) / 2;
			$illumination   = (float) round( $illumination * 100, 1 );

			$phase_name = __( 'New Moon', 'oras-member-hub' );

			if ( $phase_fraction >= 0.03 && $phase_fraction < 0.22 ) {
				$phase_name = __( 'Waxing Crescent', 'oras-member-hub' );
			} elseif ( $phase_fraction >= 0.22 && $phase_fraction < 0.28 ) {
				$phase_name = __( 'First Quarter', 'oras-member-hub' );
			} elseif ( $phase_fraction >= 0.28 && $phase_fraction < 0.47 ) {
				$phase_name = __( 'Waxing Gibbous', 'oras-member-hub' );
			} elseif ( $phase_fraction >= 0.47 && $phase_fraction < 0.53 ) {
				$phase_name = __( 'Full Moon', 'oras-member-hub' );
			} elseif ( $phase_fraction >= 0.53 && $phase_fraction < 0.72 ) {
				$phase_name = __( 'Waning Gibbous', 'oras-member-hub' );
			} elseif ( $phase_fraction >= 0.72 && $phase_fraction < 0.78 ) {
				$phase_name = __( 'Last Quarter', 'oras-member-hub' );
			} elseif ( $phase_fraction >= 0.78 && $phase_fraction < 0.97 ) {
				$phase_name = __( 'Waning Crescent', 'oras-member-hub' );
			}

			return array(
				'phase_name'    => $phase_name,
				'illumination'  => $illumination,
				'phase_fraction' => $phase_fraction,
			);
		}

		/**
		 * Compute an observing score (0-100) using weighted normalized penalties.
		 *
		 * Score formula (v1):
		 * 1) Normalize each factor to 0..1 penalty.
		 * 2) weighted_penalty = Σ(weight_i * penalty_i).
		 * 3) score = 100 - ((weighted_penalty / Σ(active_weights)) * 100).
		 *
		 * @param array<string, mixed> $conditions Weather conditions payload.
		 * @param array<string, mixed> $moon Moon payload.
		 * @return array<string, mixed>
		 */
		public static function compute_observing_score( array $conditions, array $moon ) {
			$weights = apply_filters(
				'oras_member_hub_observing_score_weights',
				array(
					'cloud_cover' => 0.45,
					'precip_prob' => 0.20,
					'wind'        => 0.20,
					'moon_illum'  => 0.15,
				)
			);

			$cloud = self::to_float_or_null( $conditions['cloud_cover_pct'] ?? null );
			$precip = self::to_float_or_null( $conditions['precip_probability'] ?? null );
			$wind = self::to_float_or_null( $conditions['wind_mph'] ?? null );
			$moon_illum = self::to_float_or_null( $moon['illumination'] ?? null );

			$penalties = array(
				'cloud_cover' => null !== $cloud ? self::clamp( $cloud / 100, 0, 1 ) : null,
				'precip_prob' => null !== $precip ? self::clamp( $precip / 100, 0, 1 ) : null,
				'wind'        => null !== $wind ? self::clamp( $wind / 20, 0, 1 ) : null,
				'moon_illum'  => null !== $moon_illum ? self::clamp( $moon_illum / 100, 0, 1 ) : null,
			);

			$weighted_penalty = 0.0;
			$active_weight    = 0.0;

			foreach ( $weights as $metric => $weight ) {
				if ( ! isset( $penalties[ $metric ] ) || null === $penalties[ $metric ] ) {
					continue;
				}

				$weight = (float) $weight;
				if ( $weight <= 0 ) {
					continue;
				}

				$weighted_penalty += $weight * (float) $penalties[ $metric ];
				$active_weight    += $weight;
			}

			$score = 50;
			if ( $active_weight > 0 ) {
				$score = 100 - (int) round( ( $weighted_penalty / $active_weight ) * 100 );
			}

			$score = (int) self::clamp( $score, 0, 100 );
			$badge = __( 'Poor', 'oras-member-hub' );

			if ( $score >= 75 ) {
				$badge = __( 'Clear', 'oras-member-hub' );
			} elseif ( $score >= 45 ) {
				$badge = __( 'Marginal', 'oras-member-hub' );
			}

			$message = __( 'Conditions are mixed tonight.', 'oras-member-hub' );

			if ( null !== $precip && $precip > 40 ) {
				$message = __( 'Showers possible; keep gear protected.', 'oras-member-hub' );
			} elseif ( null !== $wind && $wind > 15 ) {
				$message = __( 'Windy seeing likely poor.', 'oras-member-hub' );
			} elseif ( null !== $moon_illum && $moon_illum > 60 ) {
				$message = __( 'Moon will wash out faint objects.', 'oras-member-hub' );
			} elseif ( $score >= 75 ) {
				$message = __( 'Great for deep sky.', 'oras-member-hub' );
			} elseif ( $score >= 45 ) {
				$message = __( 'Decent session with selective targets.', 'oras-member-hub' );
			}

			return array(
				'score'   => $score,
				'badge'   => $badge,
				'message' => $message,
			);
		}

		/**
		 * Format computed payload for UI rendering.
		 *
		 * @param array<string, mixed> $payload Raw payload.
		 * @return array<string, mixed>
		 */
		public static function format_payload_for_ui( array $payload ) {
			$conditions = isset( $payload['conditions'] ) && is_array( $payload['conditions'] ) ? $payload['conditions'] : array();
			$astronomy  = isset( $payload['astronomy'] ) && is_array( $payload['astronomy'] ) ? $payload['astronomy'] : array();
			$moon       = isset( $payload['moon'] ) && is_array( $payload['moon'] ) ? $payload['moon'] : array();
			$observing  = isset( $payload['observing'] ) && is_array( $payload['observing'] ) ? $payload['observing'] : array();

			$time_format    = (string) get_option( 'time_format', 'g:i A' );
			$sunset_ts      = isset( $astronomy['sunset_ts'] ) ? (int) $astronomy['sunset_ts'] : 0;
			$astro_dark_ts  = isset( $astronomy['astro_dark_ts'] ) ? (int) $astronomy['astro_dark_ts'] : 0;
			$cloud_cover    = self::to_float_or_null( $conditions['cloud_cover_pct'] ?? null );
			$precip_prob    = self::to_float_or_null( $conditions['precip_probability'] ?? null );
			$wind_mph       = self::to_float_or_null( $conditions['wind_mph'] ?? null );
			$humidity       = self::to_float_or_null( $conditions['humidity_pct'] ?? null );
			$temp_f         = self::to_float_or_null( $conditions['temperature_f'] ?? null );
			$moon_illum     = self::to_float_or_null( $moon['illumination'] ?? null );

			$tiles = array(
				array(
					'key'    => 'cloud-cover',
					'label'  => __( 'Cloud Cover', 'oras-member-hub' ),
					'value'  => null !== $cloud_cover ? round( $cloud_cover ) . '%' : '—',
					'status' => self::status_class( $cloud_cover, 20, 50 ),
				),
				array(
					'key'    => 'precip-prob',
					'label'  => __( 'Precip Chance', 'oras-member-hub' ),
					'value'  => null !== $precip_prob ? round( $precip_prob ) . '%' : '—',
					'status' => self::status_class( $precip_prob, 15, 40 ),
				),
				array(
					'key'    => 'wind',
					'label'  => __( 'Wind', 'oras-member-hub' ),
					'value'  => null !== $wind_mph ? round( $wind_mph, 1 ) . ' mph' : '—',
					'status' => self::status_class( $wind_mph, 8, 15 ),
				),
				array(
					'key'    => 'humidity',
					'label'  => __( 'Humidity', 'oras-member-hub' ),
					'value'  => null !== $humidity ? round( $humidity ) . '%' : '—',
					'status' => 'oras-status-neutral',
				),
				array(
					'key'    => 'temp',
					'label'  => __( 'Temp', 'oras-member-hub' ),
					'value'  => null !== $temp_f ? round( $temp_f, 1 ) . '°F' : '—',
					'status' => 'oras-status-neutral',
				),
				array(
					'key'    => 'moon-illum',
					'label'  => __( 'Moon Illumination', 'oras-member-hub' ),
					'value'  => null !== $moon_illum ? round( $moon_illum ) . '%' : '—',
					'status' => self::status_class( $moon_illum, 25, 60 ),
				),
				array(
					'key'    => 'sunset',
					'label'  => __( 'Sunset', 'oras-member-hub' ),
					'value'  => $sunset_ts > 0 ? wp_date( $time_format, $sunset_ts ) : '—',
					'status' => 'oras-status-neutral',
				),
				array(
					'key'    => 'astro-dark',
					'label'  => __( 'Astro Dark Begins', 'oras-member-hub' ),
					'value'  => $astro_dark_ts > 0 ? wp_date( $time_format, $astro_dark_ts ) : '—',
					'status' => 'oras-status-neutral',
				),
			);

			$payload['tiles']          = $tiles;
			$payload['score']          = isset( $observing['score'] ) ? (int) $observing['score'] : 0;
			$payload['badge']          = isset( $observing['badge'] ) ? (string) $observing['badge'] : __( 'Poor', 'oras-member-hub' );
			$payload['message']        = isset( $observing['message'] ) ? (string) $observing['message'] : '';
			$payload['moon_phase']     = isset( $moon['phase_name'] ) ? (string) $moon['phase_name'] : __( 'Unknown', 'oras-member-hub' );
			$payload['site']           = array(
				'name'       => 'Oil Region Astronomical Society Observatory',
				'address'    => '4249 Camp Coffman Road, Cranberry, Pennsylvania 16319',
				'city_short' => 'Cranberry, PA',
				'lat'        => self::LATITUDE,
				'lon'        => self::LONGITUDE,
				'elevation'  => self::ELEVATION_FEET,
			);
			$payload['generated_at']   = current_time( 'mysql' );
			$payload['is_connected']   = ! empty( $payload['is_connected'] );
			$payload['connection_message'] = isset( $payload['connection_message'] ) ? (string) $payload['connection_message'] : '';
			$payload['cache_note']     = isset( $payload['cache_note'] ) ? (string) $payload['cache_note'] : '';

			return $payload;
		}

		/**
		 * Build a human cache age note from payload timestamp.
		 *
		 * @param string $generated_at MySQL datetime from payload.
		 * @return string
		 */
		private static function build_last_updated_note( $generated_at ) {
			if ( '' === $generated_at ) {
				return '';
			}

			$ts = strtotime( $generated_at );
			if ( false === $ts ) {
				return '';
			}

			$minutes = (int) floor( max( 0, current_time( 'timestamp' ) - $ts ) / MINUTE_IN_SECONDS );

			return sprintf(
				/* translators: %d: number of minutes. */
				__( 'Last updated %d minutes ago.', 'oras-member-hub' ),
				$minutes
			);
		}

		/**
		 * Determine status class from threshold ranges.
		 *
		 * @param float|null $value Metric value.
		 * @param float $good_max Good threshold max.
		 * @param float $warn_max Warning threshold max.
		 * @return string
		 */
		private static function status_class( $value, $good_max, $warn_max ) {
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
		}

		/**
		 * Cast value to float or null.
		 *
		 * @param mixed $value Value.
		 * @return float|null
		 */
		private static function to_float_or_null( $value ) {
			if ( null === $value || '' === $value || ! is_numeric( $value ) ) {
				return null;
			}

			return (float) $value;
		}

		/**
		 * Clamp number between min and max.
		 *
		 * @param float|int $value Value.
		 * @param float|int $min Min.
		 * @param float|int $max Max.
		 * @return float
		 */
		private static function clamp( $value, $min, $max ) {
			return (float) max( $min, min( $max, $value ) );
		}
	}
}
