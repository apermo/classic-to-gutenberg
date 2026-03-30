<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\TableConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TableConverter.
 *
 * Full conversion tests live in integration tests (requires WP_HTML_Tag_Processor).
 */
class TableConverterTest extends TestCase {

	/**
	 * Supports the table tag.
	 *
	 * @return void
	 */
	public function test_supports_table_tag(): void {
		$converter = new TableConverter();

		$this->assertSame( [ 'table' ], $converter->get_supported_tags() );
	}

	/**
	 * Can convert a table with thead and tbody.
	 *
	 * @return void
	 */
	public function test_can_convert_with_thead_and_tbody(): void {
		$converter = new TableConverter();

		$html = '<table><thead><tr><th>H</th></tr></thead><tbody><tr><td>D</td></tr></tbody></table>';

		$this->assertTrue( $converter->can_convert( 'table', $html ) );
	}

	/**
	 * Cannot convert a table without thead.
	 *
	 * @return void
	 */
	public function test_cannot_convert_without_thead(): void {
		$converter = new TableConverter();

		$html = '<table><tbody><tr><td>D</td></tr></tbody></table>';

		$this->assertFalse( $converter->can_convert( 'table', $html ) );
	}

	/**
	 * Cannot convert a table without tbody.
	 *
	 * @return void
	 */
	public function test_cannot_convert_without_tbody(): void {
		$converter = new TableConverter();

		$html = '<table><thead><tr><th>H</th></tr></thead></table>';

		$this->assertFalse( $converter->can_convert( 'table', $html ) );
	}

	/**
	 * Cannot convert non-table tags.
	 *
	 * @return void
	 */
	public function test_cannot_convert_other_tags(): void {
		$converter = new TableConverter();

		$this->assertFalse( $converter->can_convert( 'div', '<div>Not a table</div>' ) );
	}
}
