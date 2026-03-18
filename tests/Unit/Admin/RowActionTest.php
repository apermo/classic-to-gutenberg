<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Admin;

use Apermo\ClassicToGutenberg\Admin\RowAction;
use Apermo\ClassicToGutenberg\ContentConverter;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Post;
use WP_User;

/**
 * Tests for RowAction.
 */
class RowActionTest extends TestCase {

	/**
	 * Set up Brain Monkey.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\stubs(
			[
				'add_filter'         => null,
				'add_action'         => null,
				'get_post_types'     => static fn() => [ 'post', 'page' ],
				'get_current_screen' => static fn() => null,
			],
		);
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
	 * Row actions are hidden for posts with block markup.
	 *
	 * @return void
	 */
	public function test_hides_actions_for_block_posts(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$instance  = new RowAction( $converter );

		$post = Mockery::mock( WP_Post::class );
		$post->ID = 42;
		$post->post_content = '<!-- wp:paragraph --><p>Block content</p><!-- /wp:paragraph -->';

		$actions = $instance->add_row_actions( [ 'edit' => 'Edit' ], $post );

		$this->assertArrayNotHasKey( 'ctg_convert', $actions );
		$this->assertArrayNotHasKey( 'ctg_preview', $actions );
	}

	/**
	 * Row actions are hidden when user lacks permission.
	 *
	 * @return void
	 */
	public function test_hides_actions_when_no_permission(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$instance  = new RowAction( $converter );

		$post = Mockery::mock( WP_Post::class );
		$post->ID = 42;
		$post->post_content = 'Classic content without blocks.';

		Functions\expect( 'is_multisite' )->andReturn( false );
		Functions\expect( 'user_can' )->andReturn( false );
		Functions\expect( 'apply_filters' )
			->with( 'classic_to_gutenberg_user_can_convert', false, Mockery::any() )
			->andReturn( false );
		Functions\expect( 'wp_get_current_user' )->andReturn( $this->mock_user() );

		$actions = $instance->add_row_actions( [ 'edit' => 'Edit' ], $post );

		$this->assertArrayNotHasKey( 'ctg_convert', $actions );
		$this->assertArrayNotHasKey( 'ctg_preview', $actions );
	}

	/**
	 * Row actions are added for classic posts when user has permission.
	 *
	 * @return void
	 */
	public function test_adds_actions_for_classic_posts(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$instance  = new RowAction( $converter );

		$post = Mockery::mock( WP_Post::class );
		$post->ID = 42;
		$post->post_content = 'Classic content without blocks.';

		Functions\expect( 'is_multisite' )->andReturn( false );
		Functions\expect( 'user_can' )->andReturn( true );
		Functions\expect( 'apply_filters' )
			->with( 'classic_to_gutenberg_user_can_convert', true, Mockery::any() )
			->andReturn( true );
		Functions\expect( 'wp_get_current_user' )->andReturn( $this->mock_user() );
		Functions\stubs(
			[
				'wp_nonce_url' => static fn( string $url ): string => $url,
				'admin_url'    => static fn( string $path ): string => '/wp-admin/' . $path,
				'esc_url'      => static fn( string $url ): string => $url,
				'esc_html__'   => static fn( string $text ): string => $text,
			],
		);

		$actions = $instance->add_row_actions( [ 'edit' => 'Edit' ], $post );

		$this->assertArrayHasKey( 'ctg_convert', $actions );
		$this->assertArrayHasKey( 'ctg_preview', $actions );
		$this->assertStringContainsString( 'Convert to Blocks', $actions['ctg_convert'] );
		$this->assertStringContainsString( 'target="_blank"', $actions['ctg_preview'] );
	}

	/**
	 * Bulk action is added to the dropdown.
	 *
	 * @return void
	 */
	public function test_adds_bulk_action(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$instance  = new RowAction( $converter );

		Functions\stubs(
			[
				'__' => static fn( string $text ): string => $text,
			],
		);

		$actions = $instance->add_bulk_action( [] );

		$this->assertArrayHasKey( 'ctg_header', $actions );
		$this->assertArrayHasKey( 'ctg_bulk_convert', $actions );
		$this->assertStringContainsString( '↓', $actions['ctg_header'] );
	}

	/**
	 * Bulk convert ignores non-matching actions.
	 *
	 * @return void
	 */
	public function test_bulk_convert_ignores_other_actions(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$instance  = new RowAction( $converter );

		$result = $instance->handle_bulk_convert( '/redirect', 'other_action', [ 1, 2 ] );

		$this->assertSame( '/redirect', $result );
	}

	/**
	 * Create a mock WP_User.
	 *
	 * @return \WP_User
	 */
	private function mock_user(): WP_User {
		$user     = Mockery::mock( '\WP_User' );
		$user->ID = 1;
		return $user;
	}
}
