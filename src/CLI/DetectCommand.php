<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\CLI;

use Apermo\ClassicToGutenberg\ContentConverter;
use Apermo\ClassicToGutenberg\Migration\ClassicPostFinder;
use WP_CLI;
use WP_CLI\Utils;

/**
 * WP-CLI command: classic-to-gutenberg detect.
 *
 * Scans classic posts and reports content that falls through
 * to the core/html fallback block (unparseable content).
 */
class DetectCommand {

	/**
	 * The content converter.
	 *
	 * @var ContentConverter
	 */
	private ContentConverter $converter;

	/**
	 * Create a new detect command.
	 *
	 * @param ContentConverter $converter The content converter.
	 */
	public function __construct( ContentConverter $converter ) {
		$this->converter = $converter;
	}

	/**
	 * Register the command with WP-CLI.
	 *
	 * @param ContentConverter $converter The content converter.
	 *
	 * @return void
	 */
	public static function register( ContentConverter $converter ): void {
		WP_CLI::add_command( 'classic-to-gutenberg detect', [ new self( $converter ), 'execute' ] );
	}

	/**
	 * Detect unparseable content in classic posts.
	 *
	 * Runs a dry-run conversion and reports posts that contain
	 * core/html fallback blocks (content no converter could handle).
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more post IDs to scan.
	 *
	 * [--post-type=<types>]
	 * : Comma-separated post types. Default: post,page.
	 *
	 * [--format=<format>]
	 * : Output format. Default: table. Options: table, csv, json.
	 *
	 * ## EXAMPLES
	 *
	 *     wp classic-to-gutenberg detect
	 *     wp classic-to-gutenberg detect --post-type=post
	 *     wp classic-to-gutenberg detect --format=csv
	 *     wp classic-to-gutenberg detect 42 43
	 *
	 * @param string[]             $args       Positional arguments (post IDs).
	 * @param array<string,string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function execute( array $args, array $assoc_args ): void {
		// Disable output buffering for responsive CLI output.
		while ( \ob_get_level() > 0 ) {
			\ob_end_flush();
		}

		$post_ids = $this->parse_post_ids( $args );

		if ( $post_ids === [] ) {
			$post_ids = $this->find_classic_posts( $assoc_args );
		}

		if ( $post_ids === [] ) {
			WP_CLI::success( 'No posts without block markup found.' );
			return;
		}

		$format  = $assoc_args['format'] ?? 'table';
		$results = $this->scan_posts( $post_ids );

		if ( $results === [] ) {
			WP_CLI::success(
				\sprintf( 'Scanned %d post(s). No unparseable content found.', \count( $post_ids ) ),
			);
			return;
		}

		WP_CLI\Utils\format_items(
			$format,
			$results,
			[ 'post_id', 'title', 'html_blocks', 'snippets' ],
		);

		WP_CLI::warning(
			\sprintf(
				'%d of %d post(s) contain unparseable content.',
				\count( $results ),
				\count( $post_ids ),
			),
		);
	}

	/**
	 * Parse post IDs from positional arguments.
	 *
	 * @param string[] $args Positional arguments.
	 *
	 * @return int[]
	 */
	private function parse_post_ids( array $args ): array {
		if ( $args === [] ) {
			return [];
		}

		$post_ids = [];
		foreach ( $args as $argument ) {
			foreach ( \explode( ',', $argument ) as $part ) {
				$part = \trim( $part );
				if ( $part !== '' && \ctype_digit( $part ) ) {
					$post_ids[] = (int) $part;
				}
			}
		}

		return $post_ids;
	}

	/**
	 * Find classic posts via query.
	 *
	 * @param array<string,string> $assoc_args Associative arguments.
	 *
	 * @return int[]
	 */
	private function find_classic_posts( array $assoc_args ): array {
		$finder     = new ClassicPostFinder();
		$query_args = [];

		if ( isset( $assoc_args['post-type'] ) ) {
			$query_args['post_type'] = \explode( ',', $assoc_args['post-type'] );
		}

		return $finder->find( $query_args );
	}

	/**
	 * Scan posts for unparseable content.
	 *
	 * @param int[] $post_ids Post IDs to scan.
	 *
	 * @return array<int, array{post_id: int, title: string, html_blocks: int, snippets: string}>
	 */
	private function scan_posts( array $post_ids ): array {
		$results  = [];
		$progress = Utils\make_progress_bar( 'Scanning', \count( $post_ids ) );

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( $post === null ) {
				$progress->tick();
				continue;
			}

			$converted   = $this->converter->convert( $post->post_content );
			$html_blocks = $this->extract_html_blocks( $converted );

			if ( $html_blocks !== [] ) {
				$results[] = [
					'post_id'     => $post_id,
					'title'       => get_the_title( $post ),
					'html_blocks' => \count( $html_blocks ),
					'snippets'    => \implode( ' | ', \array_map( [ $this, 'truncate_snippet' ], $html_blocks ) ),
				];
			}

			$progress->tick();
		}

		$progress->finish();

		return $results;
	}

	/**
	 * Extract content from core/html fallback blocks.
	 *
	 * @param string $converted The converted block markup.
	 *
	 * @return string[] The inner HTML of each core/html block.
	 */
	private function extract_html_blocks( string $converted ): array {
		\preg_match_all(
			'/<!-- wp:html -->\n(.*?)\n<!-- \/wp:html -->/s',
			$converted,
			$matches,
		);

		return $matches[1];
	}

	/**
	 * Truncate a snippet for display.
	 *
	 * @param string $snippet The HTML snippet.
	 *
	 * @return string
	 */
	private function truncate_snippet( string $snippet ): string {
		$snippet = \trim( $snippet );
		$snippet = \preg_replace( '/\s+/', ' ', $snippet ) ?? $snippet;

		if ( \strlen( $snippet ) > 80 ) {
			return \substr( $snippet, 0, 77 ) . '...';
		}

		return $snippet;
	}
}
