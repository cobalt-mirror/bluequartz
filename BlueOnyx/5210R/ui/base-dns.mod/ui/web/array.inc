<?php
	function array_merge_alt($a, $b) {
		$new = array();
		$new = $a;
		foreach ( $b as $line ) {
			$key = array_search($line, $a);
			if ( $key === FALSE ) {
				if ( $line ) {
					$new[] = $line;
				}
			}
		}
		return $new;
	}
?>
