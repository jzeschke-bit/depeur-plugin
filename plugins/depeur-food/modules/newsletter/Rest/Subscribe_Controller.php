<?php
/**
 * Subscribe_Controller — eigener REST-Endpoint für die Newsletter-Anmeldung (Eigenes-Design-Modi).
 *
 * Das eigene Formular (Spotlight/Minimal/Popup) sendet hierher statt an Flodesks Widget —
 * dadurch entfällt das „I am not a robot"-Captcha. Eigener Spam-Schutz: Nonce + Honeypot +
 * IP-Rate-Limit + E-Mail-Validierung. Der eigentliche Eintrag läuft serverseitig über
 * Support\Flodesk_Api (API-Key bleibt auf dem Server).
 *
 * @package Depeur\Food\Modules\Newsletter\Rest
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Newsletter\Rest;

use Depeur\Food\Modules\Newsletter\Support\Config;
use Depeur\Food\Modules\Newsletter\Support\Flodesk_Api;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und bedient die Subscribe-Route.
 *
 * @since 0.3.0
 */
final class Subscribe_Controller {

	/**
	 * REST-Namespace.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const REST_NAMESPACE = 'depeur-food/v1';

	/**
	 * Routen-Pfad.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const ROUTE = '/newsletter/subscribe';

	/**
	 * Nonce-Action (JS erhält den Nonce via wp_localize_script).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const NONCE_ACTION = 'depeur_food_newsletter';

	/**
	 * Rate-Limit: max. Anmeldungen je IP im Zeitfenster.
	 *
	 * @since 0.3.0
	 * @var int
	 */
	private const RATE_LIMIT_MAX = 10;

	/**
	 * Rate-Limit-Zeitfenster in Sekunden (1 Stunde).
	 *
	 * @since 0.3.0
	 * @var int
	 */
	private const RATE_LIMIT_WINDOW = 3600;

	/**
	 * Verdrahtet die Routen-Registrierung.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	/**
	 * Registriert die POST-Route (öffentlich; Schutz erfolgt im Callback).
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function register_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'email' => array(
						'required' => true,
						'type'     => 'string',
					),
					'nonce' => array(
						'required' => true,
						'type'     => 'string',
					),
					'hp'    => array(
						'required' => false,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * Verarbeitet die Anmeldung: Nonce → Honeypot → Rate-Limit → Validierung → Flodesk-API.
	 *
	 * @since 0.3.0
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		// 1) Nonce (CSRF-Schutz; für Logged-out-Besucher uid-0-basiert, WP-Standard).
		$nonce = (string) $request->get_param( 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return $this->error( __( 'Sicherheitsprüfung fehlgeschlagen. Bitte die Seite neu laden.', 'depeur-food' ), 403 );
		}

		// 2) Honeypot: gefülltes df_hp = Bot → nach außen „Erfolg", ohne einzutragen.
		if ( '' !== trim( (string) $request->get_param( 'hp' ) ) ) {
			return $this->success( $this->success_message() );
		}

		// 3) Rate-Limit je IP.
		if ( $this->is_rate_limited() ) {
			return $this->error( __( 'Zu viele Versuche. Bitte später erneut versuchen.', 'depeur-food' ), 429 );
		}

		// 4) E-Mail validieren.
		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		if ( '' === $email || ! is_email( $email ) ) {
			return $this->error( __( 'Bitte eine gültige E-Mail-Adresse eingeben.', 'depeur-food' ), 400 );
		}

		// 5) Konfiguration.
		$api_key = Config::text( 'flodesk_api_key' );
		if ( '' === $api_key ) {
			return $this->error( __( 'Newsletter ist nicht vollständig konfiguriert (API-Key fehlt).', 'depeur-food' ), 500 );
		}

		// 6) Eintragen.
		$result = ( new Flodesk_Api() )->subscribe( $email, $api_key, Config::segment_ids() );
		if ( is_wp_error( $result ) ) {
			// Interne Details nur ins Log; dem Besucher eine neutrale Meldung.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'depeur-food newsletter: ' . $result->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- nur bei WP_DEBUG.
			}
			return $this->error( __( 'Anmeldung momentan nicht möglich. Bitte später erneut versuchen.', 'depeur-food' ), 502 );
		}

		return $this->success( $this->success_message() );
	}

	/**
	 * Prüft + erhöht den IP-Zähler (Transient). true, wenn das Limit überschritten ist.
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	private function is_rate_limited(): bool {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( '' === $ip ) {
			return false;
		}

		$key   = 'df_nl_rl_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= self::RATE_LIMIT_MAX ) {
			return true;
		}

		set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );

		return false;
	}

	/**
	 * Standard-Erfolgsmeldung (Double-Opt-In-Hinweis).
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	private function success_message(): string {
		return __( 'Fast geschafft! Bitte bestätige deine Anmeldung über die E-Mail, die wir dir gerade geschickt haben.', 'depeur-food' );
	}

	/**
	 * Baut eine Erfolgs-Antwort.
	 *
	 * @since 0.3.0
	 *
	 * @param string $message Nutzer-Meldung.
	 * @return \WP_REST_Response
	 */
	private function success( string $message ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => $message,
			),
			200
		);
	}

	/**
	 * Baut eine Fehler-Antwort.
	 *
	 * @since 0.3.0
	 *
	 * @param string $message Nutzer-Meldung.
	 * @param int    $status  HTTP-Status.
	 * @return \WP_REST_Response
	 */
	private function error( string $message, int $status ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => $message,
			),
			$status
		);
	}
}
