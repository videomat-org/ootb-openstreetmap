<?php
/**
 * Plugin Options
 *
 * @package ootb-openstreetmap
 * @since 1.2
 */

namespace OOTB;

class Options {

	/**
	 * The OOTB/Options Class constructor.
	 */
	public function __construct() {
		add_filter( 'plugin_action_links_' . OOTB_PLUGIN_BASENAME, [ $this, 'settings_links' ], 10, 1 );
		add_action( 'admin_menu', [ $this, 'options_page' ], 10, 0 );
		add_action( 'admin_init', [ $this, 'settings_fields' ], 10, 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueues' ], 10, 1 );
	}

	/**
	 * Add settings link to the plugin's page.
	 *
	 * @param string[] $links An array of plugin action links. By default this can include 'activate', 'deactivate', and 'delete'. With Multisite active this can also include 'network_active' and 'network_only' items.
	 *
	 * @return string[]
	 */
	function settings_links( array $links ): array {
		array_unshift( $links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( 'options-general.php?page=ootb-openstreetmap' ) ),
				esc_html__( 'Settings', 'ootb-openstreetmap' )
			)
		);

		return $links;
	}

	/**
	 * The plugin settings fields.
	 *
	 * @return void
	 */
	function settings_fields() {
		register_setting( 'ootb',
			'ootb_options',
			[
				'sanitize_callback' => [ $this, 'options_validate' ],
			] );

		add_settings_section(
			'ootb_section_settings',
			__( 'Map Provider API key', 'ootb-openstreetmap' ),
			[ $this, 'section_settings_callback' ],
			'ootb'
		);
		add_settings_field(
			'api_mapbox',
			__( 'MapBox', 'ootb-openstreetmap' ),
			[ $this, 'field_api_key_mapbox' ],
			'ootb',
			'ootb_section_settings',
			[
				'label_for'        => 'api_mapbox',
				'class'            => 'ootb_row',
				'ootb_custom_data' => 'custom',
			]
		);

		add_settings_section(
			'ootb_section_defaults',
			__( 'Default location', 'ootb-openstreetmap' ),
			[ $this, 'section_defaults_callback' ],
			'ootb'
		);
		add_settings_field(
			'default_lat',
			__( 'Latitude', 'ootb-openstreetmap' ),
			[ $this, 'field_coordinates' ],
			'ootb',
			'ootb_section_defaults',
			[
				'label_for'        => 'default_lat',
				'class'            => 'ootb_row',
				'ootb_custom_data' => 'custom',
			]
		);
		add_settings_field(
			'default_lng',
			__( 'Longitude', 'ootb-openstreetmap' ),
			[ $this, 'field_coordinates' ],
			'ootb',
			'ootb_section_defaults',
			[
				'label_for'        => 'default_lng',
				'class'            => 'ootb_row',
				'ootb_custom_data' => 'custom',
			]
		);
	}

	/**
	 * The callback method for the default coordinates.
	 *
	 * @param array $args THe settings args.
	 *
	 * @return void
	 */
	function section_defaults_callback( array $args ) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>">
			<?php
			echo sprintf(
				wp_kses(
					__( 'Set the default coordinates when you add a new block and no marker is yet set. The plugin will try to guess the default location based on the <a href="%1$s">site\'s timezone</a>, but because there is no easy way to match against a specific database of coordinates, it can get it wrong. You can override these values here.', 'ootb-openstreetmap' ),
					[ 'a' => [ 'href' => [] ] ]
				),
				esc_url( admin_url( 'options-general.php' ) )
			);
			?>
		</p>
		<?php
	}

	/**
	 * The Section Settings Callback method.
	 *
	 * @param array $args THe settings args.
	 *
	 * @return void
	 */
	function section_settings_callback( array $args ) {
		?>
		<div class="ootb_info">
			<h3><?php _e( 'About OpenStreetMap usage limits', 'ootb-openstreetmap' ); ?></h3>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo sprintf(
					wp_kses(
						__( 'As stated on the <a href="%1$s" target="_blank">OpenStreetMap Tile Usage Policy</a>, OSM’s own servers are run entirely on donated resources and they have strictly limited capacity. Using them on a site with low traffic will probably be fine. Nevertheless, you are advised to create an account to <a href="%2$s" target="_blank">MapBox</a> and get a free API Key.',
							'ootb-openstreetmap'
						),
						[ 'a' => [ 'href' => [], 'target' => [] ] ]
					),
					esc_url( 'https://operations.osmfoundation.org/policies/tiles/' ),
					esc_url( 'https://www.mapbox.com/' )
				);
				?>
			</p>
			<p class="ootb-colophon"><a href="https://wordpress.org/support/plugin/ootb-openstreetmap/"
										target="_blank"><?php _e( 'Support forum',
						'ootb-openstreetmap' ); ?></a>
				| <?php echo sprintf( wp_kses( __( 'Plugin created by <a href="%s" target="_blank">Giorgos Sarigiannidis</a>',
					'ootb-openstreetmap' ),
					[ 'a' => [ 'href' => [], 'target' => [] ] ] ),
					esc_url( 'https://www.gsarigiannidis.gr/' ) ); ?></p>
		</div>

		<?php
	}

	/**
	 * The plugin options page.
	 *
	 * @return void
	 */
	function options_page() {
		add_submenu_page(
			'options-general.php',
			'Out of the Block: OpenStreetMap',
			'OOTB OpenStreetMap',
			'manage_options',
			'ootb-openstreetmap',
			[ $this, 'options_page_html' ]
		);
	}

	/**
	 * The Mapbox API key field.
	 *
	 * @param array $args THe settings args.
	 *
	 * @return void
	 */
	function field_api_key_mapbox( array $args ) {
		$option = get_option( 'ootb_options' );
		?>
		<input type="text" name="ootb_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			   id="<?php echo esc_attr( $args['label_for'] ); ?>"
			   value="<?php echo isset( $option[ $args['label_for'] ] ) ? esc_attr( $option[ $args['label_for'] ] ) : ''; ?>"/>
		<?php
	}

	/**
	 * The default coordinates.
	 *
	 * @param array $args The settings args.
	 *
	 * @return void
	 */
	function field_coordinates( array $args ) {
		$option   = get_option( 'ootb_options' );
		$defaults = Helper::default_location();
		$default  = '';
		if ( 'default_lat' === $args['label_for'] ) {
			$default = $defaults[0] ?? '';
		}
		if ( 'default_lng' === $args['label_for'] ) {
			$default = $defaults[1] ?? '';
		}
		?>
		<input type="text" name="ootb_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			   id="<?php echo esc_attr( $args['label_for'] ); ?>" placeholder="<?php echo esc_html( $default ); ?>"
			   value="<?php echo isset( $option[ $args['label_for'] ] ) ? esc_attr( $option[ $args['label_for'] ] ) : $default; ?>"/>
		<?php
	}

	/**
	 * The options page HTML.
	 *
	 * @return void
	 */
	function options_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = get_option( 'ootb_options' );
		$current = isset( $options['ootb_field_mode'] ) && $options['ootb_field_mode'] ? $options['ootb_field_mode'] : '';
		?>
		<div id="ootb_form" class="wrap" data-current="<?php echo esc_attr( $current ); ?>">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'ootb' );
				do_settings_sections( 'ootb' );
				submit_button( __( 'Save Settings', 'ootb-openstreetmap' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Validate Options.
	 *
	 * @param array $input The Options to validate.
	 *
	 * @return array
	 */
	function options_validate( array $input ): array {
		// Validate api_mapbox.
		if ( ! empty( $input['api_mapbox'] ) ) {
			$input['api_mapbox'] = preg_replace( '/\s+/',
				' ',
				esc_attr( $input['api_mapbox'] ) );
		}
		// Validate coordinates.
		$fallback = Helper::fallback_location();
		if ( ! empty( $input['default_lat'] ) ) {
			$is_valid = preg_match( '/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/', $input['default_lat'] );
			if ( ! $is_valid ) {
				$input['default_lat'] = $fallback[0] ?? '';
			}
		}
		if ( ! empty( $input['default_lng'] ) ) {
			$is_valid = preg_match( '/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/', $input['default_lng'] );
			if ( ! $is_valid ) {
				$input['default_lng'] = $fallback[1] ?? '';
			}
		}

		return $input;
	}

	/**
	 * Enqueue the admin styles.
	 *
	 * @param string $hook The current admin page.
	 *
	 * @return void
	 */
	function enqueues( string $hook ) {
		if ( 'settings_page_ootb-openstreetmap' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ootb-admin-styles',
			OOTB_PLUGIN_URL . 'assets/css/admin/styles.css',
			[],
			OOTB_VERSION
		);
	}
}
