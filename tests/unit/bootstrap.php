<?php
/**
 * Bootstrap metadata contract consumed by WordPress plugin discovery.
 *
 * Usage: php tests/unit/bootstrap.php
 */

declare(strict_types=1);

$source = (string) file_get_contents( __DIR__ . '/../../ts-charge-guide.php', false, null, 0, 8192 );

/**
 * Match WordPress' line-oriented plugin-header convention.
 *
 * @param string $text  Plugin file head.
 * @param string $field Header field.
 * @return string
 */
function plugin_header( string $text, string $field ): string {
	$pattern = '/^[ \t\/*#@]*' . preg_quote( $field, '/' ) . ':(.*)$/mi';
	return preg_match( $pattern, $text, $match ) ? trim( $match[1] ) : '';
}

$expected = [
	'Plugin Name'      => 'TehranSpeaker Charge Guide',
	'Version'          => '0.4.0',
	'Requires PHP'     => '8.0',
	'Requires Plugins' => 'woocommerce',
	'Text Domain'      => 'ts-charge-guide',
	'Author'           => 'Parsa Dana, Keyvan Havestin',
];

$pass = 0;
$fail = 0;
foreach ( $expected as $field => $value ) {
	if ( $value === plugin_header( $source, $field ) ) {
		$pass++;
		echo "ok  WordPress discovers {$field}\n";
	} else {
		$fail++;
		echo "FAIL WordPress discovers {$field}\n";
	}
}

// The version has one source of truth: the header.
$constant = preg_match( "/define\(\s*'TS_CHARGE_GUIDE_VERSION',\s*'([^']+)'/", $source, $match ) ? $match[1] : '';
check_internal( 'the version constant matches the header', plugin_header( $source, 'Version' ) === $constant );

/**
 * Assert helper.
 *
 * @param string $name Test name.
 * @param bool   $cond Condition.
 */
function check_internal( string $name, bool $cond ): void {
	global $pass, $fail;
	if ( $cond ) {
		$pass++;
		echo "ok  {$name}\n";
	} else {
		$fail++;
		echo "FAIL {$name}\n";
	}
}

echo "\n{$pass} passed, {$fail} failed\n";
exit( $fail ? 1 : 0 );
