<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit;

use Apermo\ClassicToGutenberg\Permission;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WP_User;

/**
 * Tests for Permission::user_can_convert().
 */
class UserCanConvertTest extends TestCase {

	/**
	 * Set up Brain Monkey.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain Monkey.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Admin on single site can convert.
	 *
	 * @return void
	 */
	public function test_admin_can_convert_on_single_site(): void {
		$user = $this->mock_user( 1 );

		Functions\expect( 'is_multisite' )->andReturn( false );
		Functions\expect( 'user_can' )->with( $user, 'manage_options' )->andReturn( true );
		Functions\expect( 'apply_filters' )
			->with( 'classic_to_gutenberg_user_can_convert', true, $user )
			->andReturn( true );

		$this->assertTrue( Permission::user_can_convert( $user ) );
	}

	/**
	 * Non-admin on single site cannot convert.
	 *
	 * @return void
	 */
	public function test_non_admin_cannot_convert_on_single_site(): void {
		$user = $this->mock_user( 2 );

		Functions\expect( 'is_multisite' )->andReturn( false );
		Functions\expect( 'user_can' )->with( $user, 'manage_options' )->andReturn( false );
		Functions\expect( 'apply_filters' )
			->with( 'classic_to_gutenberg_user_can_convert', false, $user )
			->andReturn( false );

		$this->assertFalse( Permission::user_can_convert( $user ) );
	}

	/**
	 * Super admin on multisite can convert.
	 *
	 * @return void
	 */
	public function test_super_admin_can_convert_on_multisite(): void {
		$user = $this->mock_user( 1 );

		Functions\expect( 'is_multisite' )->andReturn( true );
		Functions\expect( 'is_super_admin' )->with( 1 )->andReturn( true );
		Functions\expect( 'apply_filters' )
			->with( 'classic_to_gutenberg_user_can_convert', true, $user )
			->andReturn( true );

		$this->assertTrue( Permission::user_can_convert( $user ) );
	}

	/**
	 * Non-super-admin on multisite cannot convert.
	 *
	 * @return void
	 */
	public function test_non_super_admin_cannot_convert_on_multisite(): void {
		$user = $this->mock_user( 5 );

		Functions\expect( 'is_multisite' )->andReturn( true );
		Functions\expect( 'is_super_admin' )->with( 5 )->andReturn( false );
		Functions\expect( 'apply_filters' )
			->with( 'classic_to_gutenberg_user_can_convert', false, $user )
			->andReturn( false );

		$this->assertFalse( Permission::user_can_convert( $user ) );
	}

	/**
	 * Filter can grant access to non-admin.
	 *
	 * @return void
	 */
	public function test_filter_can_grant_access(): void {
		$user = $this->mock_user( 3 );

		Functions\expect( 'is_multisite' )->andReturn( false );
		Functions\expect( 'user_can' )->with( $user, 'manage_options' )->andReturn( false );
		Functions\expect( 'apply_filters' )
			->with( 'classic_to_gutenberg_user_can_convert', false, $user )
			->andReturn( true );

		$this->assertTrue( Permission::user_can_convert( $user ) );
	}

	/**
	 * Filter can revoke access from admin.
	 *
	 * @return void
	 */
	public function test_filter_can_revoke_access(): void {
		$user = $this->mock_user( 1 );

		Functions\expect( 'is_multisite' )->andReturn( false );
		Functions\expect( 'user_can' )->with( $user, 'manage_options' )->andReturn( true );
		Functions\expect( 'apply_filters' )
			->with( 'classic_to_gutenberg_user_can_convert', true, $user )
			->andReturn( false );

		$this->assertFalse( Permission::user_can_convert( $user ) );
	}

	/**
	 * Defaults to current user when no user is passed.
	 *
	 * @return void
	 */
	public function test_defaults_to_current_user(): void {
		$user = $this->mock_user( 1 );

		Functions\expect( 'wp_get_current_user' )->once()->andReturn( $user );
		Functions\expect( 'is_multisite' )->andReturn( false );
		Functions\expect( 'user_can' )->with( $user, 'manage_options' )->andReturn( true );
		Functions\expect( 'apply_filters' )
			->with( 'classic_to_gutenberg_user_can_convert', true, $user )
			->andReturn( true );

		$this->assertTrue( Permission::user_can_convert() );
	}

	/**
	 * Create a mock WP_User with an ID.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return WP_User
	 */
	private function mock_user( int $user_id ): WP_User {
		$user     = Mockery::mock( WP_User::class );
		$user->ID = $user_id;
		return $user;
	}
}
