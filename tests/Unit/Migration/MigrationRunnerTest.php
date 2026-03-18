<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Migration;

use Apermo\ClassicToGutenberg\ContentConverter;
use Apermo\ClassicToGutenberg\Migration\MigrationResult;
use Apermo\ClassicToGutenberg\Migration\MigrationRunner;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Post;

/**
 * Tests for MigrationRunner.
 */
class MigrationRunnerTest extends TestCase {

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
	 * Returns failure when post is not found.
	 *
	 * @return void
	 */
	public function test_returns_failure_when_post_not_found(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$runner    = new MigrationRunner( $converter );

		Functions\expect( 'get_post' )->with( 999 )->andReturn( null );

		$result = $runner->convert_post( 999 );

		$this->assertFalse( $result->success );
		$this->assertSame( MigrationResult::ERROR_NOT_FOUND, $result->error_code );
	}

	/**
	 * Returns failure when post is locked.
	 *
	 * @return void
	 */
	public function test_returns_failure_when_post_locked(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$runner    = new MigrationRunner( $converter );
		$post      = $this->mock_post( 42, 'classic content' );

		Functions\expect( 'get_post' )->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_check_post_lock' )->with( 42 )->andReturn( 1 );

		$result = $runner->convert_post( 42 );

		$this->assertFalse( $result->success );
		$this->assertSame( MigrationResult::ERROR_LOCKED, $result->error_code );
	}

	/**
	 * Dry run does not save or lock.
	 *
	 * @return void
	 */
	public function test_dry_run_does_not_save(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$converter->shouldReceive( 'convert' )
			->with( 'classic content' )
			->andReturn( '<!-- wp:paragraph --><p>classic content</p><!-- /wp:paragraph -->' );

		$runner = new MigrationRunner( $converter );
		$post   = $this->mock_post( 42, 'classic content' );

		Functions\expect( 'get_post' )->with( 42 )->andReturn( $post );

		$result = $runner->convert_post( 42, true );

		$this->assertTrue( $result->success );
		$this->assertSame( 'classic content', $result->original );
		$this->assertStringContainsString( '<!-- wp:paragraph -->', $result->converted );
	}

	/**
	 * Dry run skips lock check.
	 *
	 * @return void
	 */
	public function test_dry_run_skips_lock_check(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$converter->shouldReceive( 'convert' )->andReturn( 'converted' );

		$runner = new MigrationRunner( $converter );
		$post   = $this->mock_post( 42, 'content' );

		Functions\expect( 'get_post' )->with( 42 )->andReturn( $post );
		// wp_check_post_lock should NOT be called during dry run.

		$result = $runner->convert_post( 42, true );

		$this->assertTrue( $result->success );
	}

	/**
	 * Successful conversion locks, saves revision, updates, and unlocks.
	 *
	 * @return void
	 */
	public function test_successful_conversion_flow(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$converter->shouldReceive( 'convert' )
			->with( 'original' )
			->andReturn( 'converted' );

		$runner = new MigrationRunner( $converter );
		$post   = $this->mock_post( 42, 'original' );

		Functions\expect( 'get_post' )->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_check_post_lock' )->with( 42 )->andReturn( false );
		Functions\expect( 'wp_set_post_lock' )->with( 42 )->once();
		Functions\expect( 'wp_save_post_revision' )->with( 42 )->andReturn( 100 );
		Functions\expect( 'wp_update_post' )->once()->andReturn( 42 );
		Functions\expect( 'delete_post_meta' )->with( 42, '_edit_lock' )->once();
		Functions\expect( 'update_post_meta' )->with( 42, '_ctg_revision_id', 100 )->once();
		Functions\expect( 'do_action' )->with( 'classic_to_gutenberg_post_converted', 42, Mockery::type( MigrationResult::class ) );

		$result = $runner->convert_post( 42 );

		$this->assertTrue( $result->success );
		$this->assertSame( 'original', $result->original );
		$this->assertSame( 'converted', $result->converted );
	}

	/**
	 * Returns failure with error code when update fails.
	 *
	 * @return void
	 */
	public function test_returns_failure_when_update_fails(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$converter->shouldReceive( 'convert' )->andReturn( 'converted' );

		$runner   = new MigrationRunner( $converter );
		$post     = $this->mock_post( 42, 'original' );
		$wp_error = Mockery::mock( 'WP_Error' );
		$wp_error->shouldReceive( 'get_error_message' )->andReturn( 'Update error occurred.' );

		Functions\expect( 'get_post' )->with( 42 )->andReturn( $post );
		Functions\expect( 'wp_check_post_lock' )->andReturn( false );
		Functions\expect( 'wp_set_post_lock' );
		Functions\expect( 'wp_save_post_revision' )->andReturn( 100 );
		Functions\expect( 'wp_update_post' )->andReturn( $wp_error );
		Functions\expect( 'is_wp_error' )->with( $wp_error )->andReturn( true );
		Functions\expect( 'delete_post_meta' );

		$result = $runner->convert_post( 42 );

		$this->assertFalse( $result->success );
		$this->assertSame( MigrationResult::ERROR_UPDATE_FAILED, $result->error_code );
		$this->assertSame( 'Update error occurred.', $result->error );
	}

	/**
	 * Batch fires lifecycle hooks and converts all posts.
	 *
	 * @return void
	 */
	public function test_batch_converts_all_posts(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$converter->shouldReceive( 'convert' )->andReturn( 'converted' );

		$runner = new MigrationRunner( $converter );

		Functions\expect( 'get_post' )->andReturnUsing(
			fn( int $post_id ) => $this->mock_post( $post_id, 'content' ),
		);
		Functions\expect( 'wp_check_post_lock' )->andReturn( false );
		Functions\expect( 'wp_set_post_lock' );
		Functions\expect( 'wp_save_post_revision' )->andReturn( 100 );
		Functions\expect( 'wp_update_post' )->andReturnUsing( static fn( array $args ) => $args['ID'] );
		Functions\expect( 'delete_post_meta' );
		Functions\expect( 'update_post_meta' );
		Functions\stubs( [ 'do_action' => null ] );

		$results = $runner->convert_batch( [ 1, 2, 3 ] );

		$this->assertCount( 3, $results );
		$this->assertTrue( $results[0]->success );
		$this->assertTrue( $results[1]->success );
		$this->assertTrue( $results[2]->success );
	}

	/**
	 * Revision ID is not stored when wp_save_post_revision returns non-int.
	 *
	 * @return void
	 */
	public function test_skips_revision_meta_when_no_revision_created(): void {
		$converter = Mockery::mock( ContentConverter::class );
		$converter->shouldReceive( 'convert' )->andReturn( 'converted' );

		$runner = new MigrationRunner( $converter );
		$post   = $this->mock_post( 42, 'original' );

		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'wp_check_post_lock' )->andReturn( false );
		Functions\expect( 'wp_set_post_lock' );
		Functions\expect( 'wp_save_post_revision' )->andReturn( null );
		Functions\expect( 'wp_update_post' )->andReturn( 42 );
		Functions\expect( 'delete_post_meta' );
		// update_post_meta for _ctg_revision_id should NOT be called.
		Functions\expect( 'do_action' );

		$result = $runner->convert_post( 42 );

		$this->assertTrue( $result->success );
	}

	/**
	 * Create a mock WP_Post with content.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $content The post content.
	 *
	 * @return WP_Post
	 */
	private function mock_post( int $post_id, string $content ): WP_Post {
		$post               = Mockery::mock( WP_Post::class );
		$post->ID           = $post_id;
		$post->post_content = $content;
		return $post;
	}
}
