<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\WikiSEO;

use Parser;
use PPFrame;

/**
 * Parses tags and expands wikitext
 *
 * @package MediaWiki\Extension\WikiSEO
 */
class TagParser {
	/**
	 * Parses key value pairs of format 'key=value'
	 *
	 * @param array $args Key value pairs to parse
	 *
	 * @return array
	 */
	public function parseArgs( array $args ) {
		$results = [];

		foreach ( $args as $arg ) {
			$pair = explode( '=', $arg, 2 );
			$pair = array_map( 'trim', $pair );

			if ( count( $pair ) === 2 ) {
				list( $name, $value ) = $pair;
				$results[$name] = $value;
			}
		}

		return array_filter( $results, function ( $value, $key ) {
			return mb_strlen( $value ) > 0 && mb_strlen( $key ) > 0;
		}, ARRAY_FILTER_USE_BOTH );
	}

	/**
	 * Parses <seo> tag contents
	 *
	 * @param string $text Tag content
	 *
	 * @return array
	 */
	public function parseText( $text ) {
		$lines = explode( '|', $text );

		return $this->parseArgs( $lines );
	}

	/**
	 * Expands <seo> tag wiki text
	 *
	 * @param array $tags
	 * @param Parser $parser
	 * @param PPFrame $frame
	 *
	 * @return array Parsed wiki texts
	 */
	public function expandWikiTextTagArray( array $tags, Parser $parser, PPFrame $frame ) {
		foreach ( $tags as $key => $tag ) {
			$tags[$key] = $parser->recursiveTagParseFully( $tag, $frame );
		}

		$tags = array_map( 'strip_tags', $tags );

		return array_map( 'trim', $tags );
	}

	/**
	 * Convert the attributed cached as HTML comments back into an attribute array
	 *
	 * @param string $html
	 *
	 * @return array
	 */
	public static function extractSeoDataFromHtml( $html ) {
		$params = [];

		if ( !preg_match_all( '/^WikiSEO:([:a-zA-Z_-]+);([0-9a-zA-Z\+\/=]+)[\n|\r]?$/m', $html, $matches, PREG_SET_ORDER ) ) {
			return $params;
		}

		foreach ( $matches as $match ) {
			$params[$match[1]] = base64_decode( $match[2] );
		}

		return $params;
	}
}