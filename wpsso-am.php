<?php
/*
 * Plugin Name: WPSSO App Meta (WPSSO AM)
 * Plugin URI: http://surniaulula.com/extend/plugins/wpsso-am/
 * Author: Jean-Sebastien Morisset
 * Author URI: http://surniaulula.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Description: WPSSO extension to provide Apple Store / iTunes and Google Play App meta tags for Apple's mobile Safari and Twitter's App Card.
 * Requires At Least: 3.0
 * Tested Up To: 4.0
 * Version: 1.1.1
 * 
 * Copyright 2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoAm' ) ) {

	class WpssoAm {

		private $opt_version = 'am7';
		private $min_version = '2.7.2';
		private $has_min_ver = true;

		public $p;				// class object variables

		public function __construct() {
			require_once ( dirname( __FILE__ ).'/lib/config.php' );
			WpssoAmConfig::set_constants( __FILE__ );
			WpssoAmConfig::require_libs( __FILE__ );

			add_filter( 'wpssoam_installed_version', array( &$this, 'filter_installed_version' ), 10, 1 );
			add_filter( 'wpsso_get_config', array( &$this, 'filter_get_config' ), 10, 1 );

			if ( is_admin() )
				add_action( 'admin_init', array( &$this, 'check_for_wpsso' ) );

			add_action( 'wpsso_init_options', array( &$this, 'init_options' ), 10 );
			add_action( 'wpsso_init_addon', array( &$this, 'init_addon' ), 10 );
		}

		// this filter is executed at init priority -1
		public function filter_get_config( $cf ) {
			if ( version_compare( $cf['plugin']['wpsso']['version'], $this->min_version, '<' ) ) {
				$this->has_min_ver = false;
				return $cf;
			}
			$cf['opt']['version'] .= $this->opt_version;
			$cf = SucomUtil::array_merge_recursive_distinct( $cf, WpssoAmConfig::$cf );
			return $cf;
		}

		public function check_for_wpsso() {
			$active_plugins = get_option( 'active_plugins', array() );
			if ( ! class_exists( 'Wpsso' ) || ! in_array( 'wpsso/wpsso.php', $active_plugins ) ) {
				require_once( ABSPATH.'wp-admin/includes/plugin.php' );
				deactivate_plugins( WPSSOAM_PLUGINBASE );
				wp_die( '<p>'. sprintf( __( 'WPSSO AM requires the use of WPSSO &mdash; 
					Please install and activate the WPSSO plugin before re-activating this extension.', WPSSOAM_TEXTDOM ) ).'</p>' );
			}
		}

		// this action is executed when WpssoOptions::__construct() is executed (class object is created)
		public function init_options() {
			global $wpsso;
			$this->p =& $wpsso;

			if ( $this->has_min_ver === false )
				return;

			$this->p->is_avail['am'] = true;
			$this->p->is_avail['admin']['appmeta'] = true;
		}

		// this action is executed once all class objects and addons have been created
		public function init_addon() {
			$shortname = WpssoAmConfig::$cf['plugin']['wpssoam']['short'];

			if ( $this->has_min_ver === false ) {
				$wpsso_version = $this->p->cf['plugin']['wpsso']['version'];
				$this->p->debug->log( $shortname.' requires WPSSO version '.$this->min_version.' or newer ('.$wpsso_version.' installed)' );
				if ( is_admin() )
					$this->p->notice->err( $shortname.' v'.WpssoAmConfig::$cf['plugin']['wpssoam']['version'].
					' requires WPSSO v'.$this->min_version.' or newer ('.$wpsso_version.' is currently installed).', true );
				return;
			}

			if ( is_admin() && 
				! empty( $this->p->options['plugin_wpssoam_tid'] ) && 
				! $this->p->check->aop( 'wpssoam', false ) ) {
				$this->p->notice->inf( 'An Authentication ID was entered for '.$shortname.', 
				but the Pro version is not installed yet &ndash; 
				don\'t forget to update the '.$shortname.' plugin to install the Pro version.', true );
			}

			WpssoAmConfig::load_lib( false, 'appmeta' );
			$this->p->appmeta = new WpssoAmAppmeta( $this->p, __FILE__ );
		}

		public function filter_installed_version( $version ) {
			if ( ! $this->p->check->aop( 'wpssoam', false ) )
				$version = '0.'.$version;
			return $version;
		}
	}

        global $wpssoam;
	$wpssoam = new WpssoAm();
}

?>
