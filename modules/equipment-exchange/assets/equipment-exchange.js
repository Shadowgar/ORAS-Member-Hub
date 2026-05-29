(function () {
	'use strict';

	document.addEventListener('change', function (event) {
		if (event.target && event.target.name === 'listing_type') {
			var form = event.target.closest('form');
			if (!form) {
				return;
			}
			var tradeRow = form.querySelector('textarea[name="trade_details"]');
			if (tradeRow) {
				tradeRow.closest('p').style.display = event.target.value === 'trade' ? '' : 'none';
			}
		}
	});
})();
