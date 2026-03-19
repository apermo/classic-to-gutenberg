<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Migration;

use Apermo\ClassicToGutenberg\Migration\ClassicPostFinder;
use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ClassicPostFinder.
 */
class ClassicPostFinderTest extends TestCase {

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
	 * Find returns post IDs as integers.
	 *
	 * @return void
	 */
	public function test_find_returns_integer_ids(): void {
		$wpdb = $this->mock_wpdb( [ '42', '43', '44' ] );
		$this->set_global_wpdb( $wpdb );

		Functions\stubs(
			[
				'wp_parse_args' => static fn( $args, $defaults ) => \array_merge( $defaults, $args ),
			],
		);
		Filters\expectApplied( 'classic_to_gutenberg_finder_args' )->once();

		$finder = new ClassicPostFinder();
		$result = $finder->find();

		$this->assertSame( [ 42, 43, 44 ], $result );
	}

	/**
	 * Find returns empty array when no posts match.
	 *
	 * @return void
	 */
	public function test_find_returns_empty_when_no_matches(): void {
		$wpdb = $this->mock_wpdb( [] );
		$this->set_global_wpdb( $wpdb );

		Functions\stubs(
			[
				'wp_parse_args' => static fn( $args, $defaults ) => \array_merge( $defaults, $args ),
			],
		);
		Filters\expectApplied( 'classic_to_gutenberg_finder_args' );

		$finder = new ClassicPostFinder();
		$result = $finder->find();

		$this->assertSame( [], $result );
	}

	/**
	 * Custom post types are passed to the query.
	 *
	 * @return void
	 */
	public function test_find_uses_custom_post_types(): void {
		$prepare_args = [];
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->posts  = 'wp_posts';
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing(
			static function () use ( &$prepare_args ): string {
				$prepare_args = \func_get_args();
				return $prepare_args[0];
			},
		);
		$wpdb->shouldReceive( 'get_col' )->andReturn( [ '1' ] );
		$this->set_global_wpdb( $wpdb );

		Functions\stubs(
			[
				'wp_parse_args' => static fn( $args, $defaults ) => \array_merge( $defaults, $args ),
			],
		);
		Filters\expectApplied( 'classic_to_gutenberg_finder_args' );

		$finder = new ClassicPostFinder();
		$finder->find( [ 'post_type' => [ 'product', 'event' ] ] );

		// Verify custom post types are passed as prepare arguments.
		$this->assertContains( 'product', $prepare_args );
		$this->assertContains( 'event', $prepare_args );
	}

	/**
	 * Count returns integer.
	 *
	 * @return void
	 */
	public function test_count_returns_integer(): void {
		$wpdb = $this->mock_wpdb_count( '15' );
		$this->set_global_wpdb( $wpdb );

		Functions\stubs(
			[
				'wp_parse_args' => static fn( $args, $defaults ) => \array_merge( $defaults, $args ),
			],
		);

		$finder = new ClassicPostFinder();
		$result = $finder->count();

		$this->assertSame( 15, $result );
	}

	/**
	 * Count returns zero when no matches.
	 *
	 * @return void
	 */
	public function test_count_returns_zero_when_empty(): void {
		$wpdb = $this->mock_wpdb_count( '0' );
		$this->set_global_wpdb( $wpdb );

		Functions\stubs(
			[
				'wp_parse_args' => static fn( $args, $defaults ) => \array_merge( $defaults, $args ),
			],
		);

		$finder = new ClassicPostFinder();
		$result = $finder->count();

		$this->assertSame( 0, $result );
	}

	/**
	 * Finder args filter is applied.
	 *
	 * @return void
	 */
	public function test_finder_args_filter_is_applied(): void {
		$wpdb = $this->mock_wpdb( [] );
		$this->set_global_wpdb( $wpdb );

		Functions\stubs(
			[
				'wp_parse_args' => static fn( $args, $defaults ) => \array_merge( $defaults, $args ),
			],
		);

		Filters\expectApplied( 'classic_to_gutenberg_finder_args' )
			->once()
			->with( Mockery::type( 'array' ), Mockery::type( 'array' ) );

		$finder = new ClassicPostFinder();
		$finder->find();
	}

	/**
	 * Create a mock wpdb for find() queries.
	 *
	 * @param string[] $col_result The column result to return.
	 *
	 * @return object
	 */
	private function mock_wpdb( array $col_result ): object {
		$wpdb        = Mockery::mock( 'wpdb' );
		$wpdb->posts = 'wp_posts';

		$wpdb->shouldReceive( 'prepare' )->andReturnUsing(
			static fn(): string => \func_get_args()[0],
		);
		$wpdb->shouldReceive( 'get_col' )->andReturn( $col_result );

		return $wpdb;
	}

	/**
	 * Create a mock wpdb for count() queries.
	 *
	 * @param string $count_result The count result to return.
	 *
	 * @return object
	 */
	private function mock_wpdb_count( string $count_result ): object {
		$wpdb        = Mockery::mock( 'wpdb' );
		$wpdb->posts = 'wp_posts';

		$wpdb->shouldReceive( 'prepare' )->andReturnUsing(
			static fn(): string => \func_get_args()[0],
		);
		$wpdb->shouldReceive( 'get_var' )->andReturn( $count_result );

		return $wpdb;
	}

	/**
	 * Set the global $wpdb instance.
	 *
	 * @param object $wpdb The mock wpdb.
	 *
	 * @return void
	 */
	private function set_global_wpdb( object $wpdb ): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- required for unit testing.
		$GLOBALS['wpdb'] = $wpdb;
	}
}
