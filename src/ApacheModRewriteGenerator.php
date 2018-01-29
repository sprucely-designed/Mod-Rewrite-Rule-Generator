<?php

namespace donatj\RewriteGenerator;

use donatj\RewriteGenerator\Exceptions\AmbiguousRelativeHostException;

class ApacheModRewriteGenerator implements GeneratorInterface {

	/**
	 * @inheritdoc
	 */
	public function generateRewrite( string $from, string $to, int $type ) : string {
		$parsedFrom = parse_url($from);
		$parsedTo   = parse_url($to);

		$parsedFrom['host'] = $parsedFrom['host'] ?? '';
		$parsedTo['host']   = $parsedTo['host'] ?? '';
		$parsedFrom['query'] = $parsedFrom['query'] ?? '';
		$parsedTo['query']   = $parsedTo['query'] ?? '';

		$output = "";

		if( !$parsedFrom['host'] && $parsedTo['host'] ) {
			throw new AmbiguousRelativeHostException('Unclear relative host. When the "FROM" URI specifies a HOST the "TO" MUST specify a HOST as well.');
		} elseif( $parsedFrom['host'] != $parsedTo['host'] && $parsedTo['host'] ) {
			$output .= 'RewriteCond %{HTTP_HOST} ^' . preg_quote($parsedFrom['host']) . '$';
			$output .= "\n";
			$prefix = $parsedTo['scheme'] . '://' . $parsedTo['host'] . '/';
		} else {
			$prefix = '/';
		}

		$explodedQuery = explode('&', $parsedFrom['query']);
		foreach( $explodedQuery as $qs ) {
			if( strlen($qs) > 0 ) {
				$output .= 'RewriteCond %{QUERY_STRING} (^|&)' . preg_quote($qs) . '($|&)';
				$output .= "\n";
			}
		}

		$output .= 'RewriteRule ^' . preg_quote(ltrim($parsedFrom['path'], '/')) . '$ ' . $prefix . ltrim($parsedTo['path'], '/') . '?' . $parsedTo['query'];

		switch( $type ) {
			case RewriteTypes::SERVER_REWRITE:
				$output .= '&%{QUERY_STRING}';
				break;
			case RewriteTypes::PERMANENT_REDIRECT:
				$output .= ' [L,R=301]';
				break;
			default:
				throw new \InvalidArgumentException('Unhandled RewriteType: ' . $type, $type);
		}

		return $output;
	}

	/**
	 * @inheritdoc
	 */
	public function lineComment( string $from, string $to, int $type ) : string {
		return sprintf('# %s --- %s => %s', RewriteTypes::name($type), $from, $to);
	}

	/**
	 * Generate a comment as a string
	 *
	 * @param string $text
	 * @return string
	 */
	public function comment( string $text ) : string {
		return "# {$text}";
	}

}
