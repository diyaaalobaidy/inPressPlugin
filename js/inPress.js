/**
 * plugins/generic/inPressPlugin/js/inPress.js
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * Frontend interactive behavior: search/filter, abstract toggles, citation copy.
 */

(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		initAbstractToggles();
		initLiveFilter();
		initCitationCopy();
	});

	/**
	 * Toggle Expand / Collapse for long abstracts
	 */
	function initAbstractToggles() {
		var toggles = document.querySelectorAll('.pkp_inpress_abstract_toggle');
		toggles.forEach(function(btn) {
			btn.addEventListener('click', function() {
				var wrapper = btn.closest('.pkp_inpress_abstract_wrapper');
				if (!wrapper) return;

				var abstractEl = wrapper.querySelector('.pkp_inpress_abstract');
				if (!abstractEl) return;

				var isCollapsed = abstractEl.classList.contains('pkp_inpress_collapsed');
				var moreText = btn.getAttribute('data-more') || 'Read more';
				var lessText = btn.getAttribute('data-less') || 'Show less';

				if (isCollapsed) {
					abstractEl.classList.remove('pkp_inpress_collapsed');
					btn.innerHTML = lessText + ' &uarr;';
				} else {
					abstractEl.classList.add('pkp_inpress_collapsed');
					btn.innerHTML = moreText + ' &darr;';
				}
			});
		});
	}

	/**
	 * Real-time search and section dropdown filtering
	 */
	function initLiveFilter() {
		var searchInput = document.getElementById('pkpInPressSearch');
		var sectionSelect = document.getElementById('pkpInPressSectionFilter');
		var container = document.getElementById('pkpInPressArticlesContainer');
		var countEl = document.getElementById('pkpInPressVisibleCount');
		var noMatchesEl = document.getElementById('pkpInPressNoFilterResults');

		if (!container) return;

		var cards = container.querySelectorAll('.pkp_inpress_card');
		if (!cards.length) return;

		function applyFilter() {
			var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
			var selectedSection = sectionSelect ? sectionSelect.value.trim() : '';
			var visibleCount = 0;

			cards.forEach(function(card) {
				var cardTitle = card.getAttribute('data-title') || '';
				var cardAuthors = card.getAttribute('data-authors') || '';
				var cardSection = card.getAttribute('data-section') || '';

				var matchesSearch = !query || cardTitle.indexOf(query) !== -1 || cardAuthors.indexOf(query) !== -1;
				var matchesSection = !selectedSection || cardSection === selectedSection;

				if (matchesSearch && matchesSection) {
					card.style.display = '';
					visibleCount++;
				} else {
					card.style.display = 'none';
				}
			});

			if (countEl) {
				countEl.textContent = visibleCount;
			}

			if (noMatchesEl) {
				noMatchesEl.style.display = (visibleCount === 0) ? 'block' : 'none';
			}
		}

		if (searchInput) {
			searchInput.addEventListener('input', applyFilter);
		}
		if (sectionSelect) {
			sectionSelect.addEventListener('change', applyFilter);
		}
	}

	/**
	 * Copy citation to clipboard
	 */
	function initCitationCopy() {
		var copyBtn = document.getElementById('pkpInPressCopyCitation');
		var noticeEl = document.getElementById('pkpInPressCitationNotice');

		if (!copyBtn) return;

		copyBtn.addEventListener('click', function() {
			var textToCopy = copyBtn.getAttribute('data-text') || '';
			if (!textToCopy) {
				var box = document.getElementById('pkpInPressCitationText');
				if (box) textToCopy = box.innerText.trim();
			}

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(textToCopy).then(showNotice).catch(fallbackCopy);
			} else {
				fallbackCopy();
			}

			function fallbackCopy() {
				var textarea = document.createElement('textarea');
				textarea.value = textToCopy;
				textarea.style.position = 'fixed';
				textarea.style.opacity = '0';
				document.body.appendChild(textarea);
				textarea.select();
				try {
					document.execCommand('copy');
					showNotice();
				} catch (err) {
					console.error('Copy citation failed:', err);
				}
				document.body.removeChild(textarea);
			}

			function showNotice() {
				if (!noticeEl) return;
				noticeEl.style.display = 'block';
				setTimeout(function() {
					noticeEl.style.display = 'none';
				}, 2500);
			}
		});
	}
})();
