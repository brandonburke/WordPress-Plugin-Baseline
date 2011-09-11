<?php
class TimThumbCheck implements themecheck {
	protected $error = array();

	function check( $php_files, $css_files, $other_files ) {
		$ret = true;

		foreach ( $php_files as $name => $content ) {
		checkcount();
			if ( strpos( $content, 'cleanSource($src);' ) !== false ) {
			preg_match( "/define\s\('VERSION',\s'([0-9]\.[0-9]+)'\)/", $content, $matches );
			$version = $matches[1];
			$filename = tc_filename( $name );

			if ( $version < 1.28 ) { //set to latest timthumb http://code.google.com/p/timthumb/source/browse/trunk/timthumb.php
				$this->error[] = "<span class='tc-lead tc-warning'>WARNING</span>: TimThumb detected in file <strong>{$filename}</strong>. Version <strong>{$version}</strong> is out of date!";
				$ret = false;
			} else {
				$this->error[] = "<span class='tc-lead tc-info'>INFO</span>: TimThumb detected in file <strong>{$filename}</strong>. Version detected was <strong>{$version}</strong>";
				}
			}
		}
		// return the pass/fail
		return $ret;
	}
	function getError() { return $this->error; }
}
$themechecks[] = new TimThumbCheck;