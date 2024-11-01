<?php
/*
Plugin Name: User Switching Front
Description: Allows to use the wonderful plugin User switching directly from front-end by using admin bar.
Version:     0.9.0
Author:      Julien Maury
Text Domain: user-switching-front
Domain Path: /lang/
License:     GPL v3 or later
Network:     true

Copyright © 2017 Julien Maury

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

if ( ! function_exists( 'add_action' )  ) {
	die( 'No !' );
}

define( 'USF_DIR', plugin_dir_path( __FILE__ ) );
define( 'USF_URL', plugin_dir_url( __FILE__ ) );
define( 'USF_VERSION', '0.9.0' );

class user_switching_front {

	/**
	 * @var self
	 */
	protected static $instance;
	protected $parent_id;

	protected function __construct() {
	    $this->parent_id = 'user_switching_front';
    }

	/**
	 * @return self
	 */
	final public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static;
		}
		return self::$instance;
	}

	public function hooks() {
		// no use if parent plugin not there
		if ( ! class_exists( 'user_switching' )
		     || ! method_exists( 'user_switching', 'maybe_switch_url' )
		     || ! method_exists( 'user_switching', 'get_old_user' )
		     || ! method_exists( 'user_switching', 'current_url' )
		     || ! method_exists( 'user_switching', 'switch_back_url' ) ) {
			return false;
		}

		add_action( 'admin_bar_menu', [ $this, 'add_nodes' ], 2016 );
		add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ], 11 );
		add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 11 );
		add_action( 'wp_ajax_search_in_users', [ $this, 'search_in_users' ] );
	}

	public function search_in_users() {

	    // prevent forgery
        check_ajax_referer( 'search_users', 'security' );

        if ( ! isset( $_GET['s_users'] ) ) {
            wp_send_json_error();
        }

        $search = esc_attr( $_GET['s_users'] );

        // add each user as sub node
        $_ids = self::get_users( $search );

        if ( empty( $_ids ) ) {
	        wp_send_json_error( [ 'error' => __( 'No users found.' ) ] );
        }

        $data = [];

        foreach ( $_ids as $k => $_id ) {

            $_user = get_userdata( $_id );

            if ( empty( $_user ) ) {
                continue;
            }

            $name = get_user_meta( $_id, 'first_name', true ) . " " . get_user_meta( $_id, 'last_name', true );

            $data[ $k ]['title']      = esc_html( $name );
            $data[ $k ]['role']       = reset( $_user->roles );
            $data[ $k ]['switch_url'] = esc_url( user_switching::maybe_switch_url( $_user ) );

        }

        wp_send_json_success( [ 'to_complete' => $data ] );
    }

    /**
     * Add our css
     *
     * @author Julien Maury
     * @return bool
     */
    public function scripts() {

        if ( ! is_user_logged_in() || ! current_user_can( 'edit_users', get_current_user_id() ) ) {
            return false;
        }

		$prefix = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_style(
			'user-switching-front',
			USF_URL . 'css/user-switching-front' . $prefix .  '.css',
			[],
			USF_VERSION
		);

        wp_register_script(
            'jquery-auto-complete',
            USF_URL . 'js/lib/jquery.auto-complete.min.js',
            ['jquery'],
            USF_VERSION,
            true
        );

        wp_register_script(
            'user-switching-front',
            USF_URL . 'js/s_users' . $prefix .  '.js',
            ['jquery', 'jquery-auto-complete'],
            USF_VERSION,
            true
        );

		wp_enqueue_style( 'user-switching-front' );
		wp_enqueue_script( 'jquery-auto-complete' );
		wp_enqueue_script( 'user-switching-front' );
		wp_localize_script(
		    'user-switching-front',
            'ajax_data',
            [
		'url'       => esc_url( admin_url( 'admin-ajax.php' ) ),
                'nonce'     => wp_create_nonce( 'search_users' ),
            ]
        );
	}

	/**
	 * Add our node to admin bar
	 *
	 * @param $wp_admin_bar
	 *
	 * @author Julien Maury
	 * @return bool
	 */
	public function add_nodes( $wp_admin_bar ) {

		/**
		 * no use to trigger anything if admin bar not showing
		 * or user not allowed
		 */
		if ( ! is_admin_bar_showing() ) {
			return false;
		}

		// we use parent plugin that already checks for old user before switching
		$old_user  = user_switching::get_old_user();

		if ( ! empty( $old_user ) ) {

			$wp_admin_bar->add_menu( [
				'id'    => $this->parent_id,
				'title' => sprintf(
					esc_html__( 'Switch back to %s', 'user-switching-front' ),
					$old_user->display_name
				),
                'href'   => add_query_arg(
                    'redirect_to',
                    urlencode( user_switching::current_url() ),
                    user_switching::switch_back_url( $old_user )
                ),
			] );

		} else if ( current_user_can( 'edit_users', get_current_user_id() ) ) {
			$wp_admin_bar->add_menu( [
				'id'    => $this->parent_id,
				'title' => esc_html__( 'Switch user', 'user-switching-front' ),
				'href'  => false,
				'meta'   => array(
					'class'    => 'user-switching-front hide-if-no-js',
				),
			] );

            /**
             * add form search users
             */
		    ob_start();
		    require_once ( USF_DIR . 'views/search-users.php' );

            $wp_admin_bar->add_menu( [
                'id'     => 's_users',
                'parent' => $this->parent_id,
                'title'  => ob_get_clean(),
                'href'   => false,
            ] );

        }

		return true;
	}

	/**
	 * List users WP
	 *
	 * @param $search
	 * @author Julien Maury
	 * @return array
	 */
	protected static function get_users( $search ) {
		// explode spaces in search query
		$search_split = explode( ' ', $search );
		$search_split = join( ' ', $search_split );

		$users = new WP_User_Query( [
			'blog_id'    => get_current_blog_id(),
			'meta_query' => [
				'relation' => 'OR',
				[
					'key'     => 'first_name',
					'value'   => '^' . $search_split,
					'compare' => 'RLIKE',
				],
				[
					'key'     => 'last_name',
					'value'   => '^' . $search_split,
					'compare' => 'RLIKE',
				]
			]
		] );

		$users = $users->get_results();
		$_ids  = apply_filters( 'user_switching_front/user_ids', wp_list_pluck( $users, 'ID' ) );

		// if 0-1 user then no use
		if ( ! is_array( $_ids ) || count( $_ids ) < 1 ) {
			return [];
		}
		return $_ids;
	}
}

add_action( 'plugins_loaded', function(){

	load_plugin_textdomain(
		'user-switching-front',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/lang'
	);

	$i = user_switching_front::getInstance();
	$i->hooks();
});

