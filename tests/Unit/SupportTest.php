<?php

use LibreBlocks\Core\Context\ObjectStack;
use LibreBlocks\Core\Support\Cache;
use LibreBlocks\Core\Support\Json;
use PHPUnit\Framework\TestCase;

final class SupportTest extends TestCase {

	protected function setUp(): void {
		WPStub::reset();
	}

	public function test_stack_is_restored_after_exceptions(): void {
		$stack = new ObjectStack( fn() => 'root' );

		try {
			$stack->with( 'item', function () {
				throw new RuntimeException( 'boom' );
			} );
		} catch ( RuntimeException $e ) {
			// expected
		}

		$this->assertSame( 0, $stack->depth() );
		$this->assertSame( 'root', $stack->current() );
	}

	public function test_describe_objects(): void {
		$this->assertSame( [ 'type' => 'term', 'id' => 5 ], ObjectStack::describe( new WP_Term( [ 'term_id' => '5' ] ) ) );
		$this->assertSame( [ 'type' => 'row', 'id' => 7 ], ObjectStack::describe( (object) [ '_id' => '7' ] ) );
		$this->assertSame( [ 'type' => 'none', 'id' => null ], ObjectStack::describe( null ) );
	}

	public function test_cache_remember_and_flush(): void {
		$cache = new Cache( 'test' );
		$calls = 0;
		$fn    = function () use ( &$calls ) {
			return ++$calls;
		};

		$this->assertSame( 1, $cache->remember( 'k', 'queries', $fn ) );
		$this->assertSame( 1, $cache->remember( 'k', 'queries', $fn ) );

		usleep( 10 );
		$cache->flush_group( 'queries' );

		$this->assertSame( 2, $cache->remember( 'k', 'queries', $fn ) );
		$this->assertSame( Cache::hash( [ 'a' => 1 ] ), Cache::hash( [ 'a' => 1 ] ) );
	}

	public function test_json_helpers(): void {
		$this->assertSame( '{"name":"São Paulo","url":"a/b"}', Json::encode( [ 'name' => 'São Paulo', 'url' => 'a/b' ] ) );
		$this->assertSame( [], Json::decode( 'not json' ) );
		$this->assertSame( [], Json::decode( null ) );
		$this->assertSame( [ 'a' => 1 ], Json::decode( '{"a":1}' ) );
	}
}
