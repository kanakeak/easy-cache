(function($) {
	var $enableInMemoryCache = $( document.getElementById( 'we_enable_in_memory_object_caching' ) );
	var inMemoryCacheFields = document.querySelectorAll( '.in-memory-cache' );

	$enableInMemoryCache.on(
		'change', function(event) {
			if ('1' === event.target.value) {
				inMemoryCacheFields[0].className = inMemoryCacheFields[0].className.replace( /show/i, '' ) + ' show';
				inMemoryCacheFields[1].className = inMemoryCacheFields[1].className.replace( /show/i, '' ) + ' show';
			} else {
				inMemoryCacheFields[0].className = inMemoryCacheFields[0].className.replace( /show/i, '' );
				inMemoryCacheFields[1].className = inMemoryCacheFields[1].className.replace( /show/i, '' );
			}
		}
	);

	var $advancedModeToggle = $( document.getElementById( 'we_advanced_mode' ) );
	var advancedTable = document.querySelectorAll( '.we-advanced-mode-table' )[0];
	var simpleTable = document.querySelectorAll( '.we-simple-mode-table' )[0];
	var pageCachingSimple = document.getElementById( 'we_enable_page_caching_simple' );
	var pageCachingAdvanced = document.getElementById( 'we_enable_page_caching_advanced' );
	var pageCacheLengthSimple = document.getElementById( 'we_page_cache_length_simple' );
	var pageCacheLengthAdvanced = document.getElementById( 'we_page_cache_length_advanced' );
	var pageCacheLengthUnitSimple = document.getElementById( 'we_page_cache_length_unit_simple' );
	var pageCacheLengthUnitAdvanced = document.getElementById( 'we_page_cache_length_unit_advanced' );
	var gzipCompressionSimple = document.getElementById( 'we_enable_gzip_compression_simple' );
	var gzipCompressionAdvanced = document.getElementById( 'we_enable_gzip_compression_advanced' );

	$advancedModeToggle.on(
		'change', function(event) {
			if ('1' === event.target.value) {
				advancedTable.className = advancedTable.className.replace( /show/i, '' ) + ' show';
				simpleTable.className = simpleTable.className.replace( /show/i, '' );

				pageCachingSimple.disabled = true;
				pageCachingAdvanced.disabled = false;

				pageCacheLengthSimple.disabled = true;
				pageCacheLengthAdvanced.disabled = false;

				pageCacheLengthUnitSimple.disabled = true;
				pageCacheLengthUnitAdvanced.disabled = false;

				if ( gzipCompressionSimple ) {
					gzipCompressionSimple.disabled = true;
					gzipCompressionAdvanced.disabled = false;
				}
			} else {
				simpleTable.className = simpleTable.className.replace( /show/i, '' ) + ' show';
				advancedTable.className = advancedTable.className.replace( /show/i, '' );

				pageCachingSimple.disabled = false;
				pageCachingAdvanced.disabled = true;

				pageCacheLengthSimple.disabled = false;
				pageCacheLengthAdvanced.disabled = true;

				pageCacheLengthUnitSimple.disabled = false;
				pageCacheLengthUnitAdvanced.disabled = true;

				if (gzipCompressionSimple) {
					gzipCompressionSimple.disabled = false;
					gzipCompressionAdvanced.disabled = true;
				}
			}
		}
	);
})(jQuery);
