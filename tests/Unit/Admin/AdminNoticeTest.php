<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Admin;

use Apermo\ClassicToGutenberg\Admin\AdminNotice;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Post;

/**
 * Tests for AdminNotice.
 */
class AdminNoticeTest extends TestCase {

	/**
	 * Set up Brain Monkey.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\stubs( [ 'add_action' => null ] );
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
	 * No output when transient is missing.
	 *
	 * @return void
	 */
	public function test_no_output_when_transient_missing(): void {
		Functions\expect( 'get_current_user_id' )->andReturn( 1 );
		Functions\expect( 'get_transient' )->andReturn( false );

		$notice = new AdminNotice();

		\ob_start();
		$notice->display_notices();
		$output = \ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * No output when transient is not an array.
	 *
	 * @return void
	 */
	public function test_no_output_when_transient_not_array(): void {
		Functions\expect( 'get_current_user_id' )->andReturn( 1 );
		Functions\expect( 'get_transient' )->andReturn( 'invalid' );

		$notice = new AdminNotice();

		\ob_start();
		$notice->display_notices();
		$output = \ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Success notice with single post shows link.
	 *
	 * @return void
	 */
	public function test_success_notice_with_single_post(): void {
		$post = $this->mock_post( 42, 'Test Post' );
		$transient = [
			'type'     => 'converted',
			'post_ids' => [ 42 ],
			'failed'   => 0,
		];

		Functions\expect( 'get_current_user_id' )->andReturn( 1 );
		Functions\expect( 'get_transient' )->andReturn( $transient );
		Functions\expect( 'delete_transient' )->once();
		Functions\expect( 'get_post' )->with( 42 )->andReturn( $post );
		Functions\expect( 'get_the_title' )->with( $post )->andReturn( 'Test Post' );
		Functions\expect( 'get_edit_post_link' )->with( 42 )->andReturn( '/wp-admin/post.php?post=42&action=edit' );
		Functions\stubs(
			[
				'esc_html__' => static fn( string $text ): string => $text,
				'esc_html'   => static fn( string $text ): string => $text,
				'esc_url'    => static fn( string $url ): string => $url,
				'esc_attr'   => static fn( string $text ): string => $text,
			],
		);

		$notice = new AdminNotice();

		\ob_start();
		$notice->display_notices();
		$output = \ob_get_clean();

		$this->assertStringContainsString( 'notice-success', $output );
		$this->assertStringContainsString( 'Open "Test Post"', $output );
		$this->assertStringContainsString( 'post.php?post=42', $output );
	}

	/**
	 * Success notice with multiple posts shows list.
	 *
	 * @return void
	 */
	public function test_success_notice_with_multiple_posts(): void {
		$post1 = $this->mock_post( 1, 'First' );
		$post2 = $this->mock_post( 2, 'Second' );
		$transient = [
			'type'     => 'converted',
			'post_ids' => [ 1, 2 ],
			'failed'   => 0,
		];

		Functions\expect( 'get_current_user_id' )->andReturn( 1 );
		Functions\expect( 'get_transient' )->andReturn( $transient );
		Functions\expect( 'delete_transient' );
		$posts = [
			1 => $post1,
			2 => $post2,
		];
		Functions\expect( 'get_post' )->andReturnUsing(
			static fn( int $post_id ) => $posts[ $post_id ] ?? null,
		);
		Functions\expect( 'get_the_title' )->andReturnUsing(
			static fn( WP_Post $post ): string => $post->title,
		);
		Functions\expect( 'get_edit_post_link' )->andReturn( '/wp-admin/post.php' );
		Functions\stubs(
			[
				'esc_html__' => static fn( string $text ): string => $text,
				'esc_html'   => static fn( string $text ): string => $text,
				'esc_url'    => static fn( string $url ): string => $url,
				'esc_attr'   => static fn( string $text ): string => $text,
			],
		);

		$notice = new AdminNotice();

		\ob_start();
		$notice->display_notices();
		$output = \ob_get_clean();

		$this->assertStringContainsString( '<ul>', $output );
		$this->assertStringContainsString( 'First', $output );
		$this->assertStringContainsString( 'Second', $output );
	}

	/**
	 * Notice shows failure count alongside successes.
	 *
	 * @return void
	 */
	public function test_notice_shows_failure_count(): void {
		$transient = [
			'type'     => 'converted',
			'post_ids' => [],
			'failed'   => 3,
		];

		Functions\expect( 'get_current_user_id' )->andReturn( 1 );
		Functions\expect( 'get_transient' )->andReturn( $transient );
		Functions\expect( 'delete_transient' );
		Functions\stubs(
			[
				'esc_html__' => static fn( string $text ): string => $text,
				'esc_html'   => static fn( string $text ): string => $text,
				'esc_attr'   => static fn( string $text ): string => $text,
				'_n'         => static fn( string $single, string $plural, int $count ): string =>
					$count === 1 ? $single : $plural,
			],
		);

		$notice = new AdminNotice();

		\ob_start();
		$notice->display_notices();
		$output = \ob_get_clean();

		$this->assertStringContainsString( 'notice-error', $output );
		$this->assertStringContainsString( 'failed to convert', $output );
	}

	/**
	 * Error notice shows message.
	 *
	 * @return void
	 */
	public function test_error_notice_shows_message(): void {
		$transient = [
			'type'    => 'error',
			'message' => 'Something went wrong.',
		];

		Functions\expect( 'get_current_user_id' )->andReturn( 1 );
		Functions\expect( 'get_transient' )->andReturn( $transient );
		Functions\expect( 'delete_transient' );
		Functions\stubs(
			[
				'esc_html__' => static fn( string $text ): string => $text,
				'esc_html'   => static fn( string $text ): string => $text,
			],
		);

		$notice = new AdminNotice();

		\ob_start();
		$notice->display_notices();
		$output = \ob_get_clean();

		$this->assertStringContainsString( 'notice-error', $output );
		$this->assertStringContainsString( 'Something went wrong.', $output );
	}

	/**
	 * Transient is deleted after display.
	 *
	 * @return void
	 */
	public function test_transient_is_deleted(): void {
		$transient = [
			'type'    => 'error',
			'message' => 'Error.',
		];

		Functions\expect( 'get_current_user_id' )->andReturn( 1 );
		Functions\expect( 'get_transient' )->andReturn( $transient );
		Functions\expect( 'delete_transient' )->with( 'ctg_notice_1' )->once();
		Functions\stubs(
			[
				'esc_html__' => static fn( string $text ): string => $text,
				'esc_html'   => static fn( string $text ): string => $text,
			],
		);

		$notice = new AdminNotice();

		\ob_start();
		$notice->display_notices();
		\ob_get_clean();
	}

	/**
	 * Create a mock WP_Post.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $title   The post title.
	 *
	 * @return WP_Post
	 */
	private function mock_post( int $post_id, string $title ): WP_Post {
		$post        = Mockery::mock( WP_Post::class );
		$post->ID    = $post_id;
		$post->title = $title;
		return $post;
	}
}
