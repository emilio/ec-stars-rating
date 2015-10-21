/**
 * @author Emilio Cobos (http://emiliocobos.net)
 */
;(function (window, document) {
	// The wordpress passed script
	var globaldata = null,
		ec_stars_loaded = false,
		voting = false;
	// No me importa dejar a IE7 sin soporte
	if( ! document.querySelectorAll ) {
		return;
	}


	/**
	 * Loop through an array of elements
	 * @param {Array|Nodelist} col
	 * @param {Function} callback
	 * @return void
	 */
	function forEach(col, callback) {
		var i = 0,
			len = col.length;

		for( ; i < len; i++ ) {
			callback.call(col[i], i);
		}
	}

	/**
	 * Add an event to a node
	 * @param {Node} el
	 * @param {String} type
	 * @param {Function} callback
	 * @return void
	 */
	function addEvent(el, type, callback) {
		if( el.addEventListener ) {
			el.addEventListener(type, callback, false);
		} else if( el.attachEvent ) {
			el.attachEvent('on' + type, function(e) {
				if( ! e ) {
					e = window.event;
				}
				if( ! e.preventDefault ) {
					e.preventDefault = function() {
						e.returnValue = false;
					}
					e.stopPropagation = function() {
						e.cancelBubble = true;
					}
					e.isDefaultPrevented = function() {
						return e.returnValue === false;
					}
				}
				if( ! e.target ) {
					e.target = e.srcElement;
				}

				callback.call(el, e);
			});
		}
	}

	/**
	 * Make a post AJAX request with a desired URL, passed data, and callback
	 * 
	 * @param {String} url
	 * @param {String|FormData} data
	 * @param {Function} callback
	 * @return void
	 */
	function postRequest(url, data, callback) {
		var req = new window.XMLHttpRequest();

		req.open("POST", url, true);
		req.onreadystatechange = function() {
			if( req.readyState === 4 ) {
				callback(req.responseText);
			}
		}

		req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

		req.send(data);
	}

	/**
	 * Generate a function that we'll use as AJAX callback
	 * @see vote
	 * @return {Function}
	 */
	function ajaxCallback(root, star) {
		var
			show_message = function(message) {
				root.setAttribute('data-tooltip', message);
			},
			update_value = function(result, count) {
				var parent = root.parentNode
				root.className += " is-voted";

				root.querySelector('.ec-stars-overlay').style.width = (100 - result * 100 / 5) + '%';
				parent.querySelector('.ec-stars-rating-count').innerHTML = count;
				parent.querySelector('.ec-stars-rating-value').innerHTML = parseInt(result * 100, 10) / 100;
			}
		return function(response) {
			response = window.JSON.parse(response);
			switch(response.status) {
				case globaldata.codes.PREVIOUSLY_VOTED:
					show_message(globaldata.messages.previously_voted);
					break;
				case globaldata.codes.REQUEST_ERROR:
					show_message(globaldata.messages.request_error);
					break;
				case globaldata.codes.SUCCESS:
					update_value(response.result, response.votes);
					show_message(globaldata.messages.success);
					break;
				default: 
					show_message(globaldata.messages.unknown);
			}
			voting = false;
		}
	}
	/**
	 * Vote (if it's not voted)
	 * @param {Node} root the wrapper element
	 * @param {Node} star the clicked star
	 * @param {Number} post_id the id of the voted post
	 */
	function vote(root, star, post_id) {
		var rating = parseInt(star.getAttribute('data-value') , 10);

		// If the reference to the global object doesn't exists, create it
		if( globaldata === null ) {
			globaldata = window.ec_ajax_data || null;
		}

		window.console && console.log(root, star, post_id, rating);
		// If we previously voted, or there is something bad in the rating ,stop
		if( /\bis-voted\b/.test(root.className) || ! rating || rating > 5 || rating < 1) {
			return;
		}

		postRequest(
			globaldata.ajax_url,
			'action=ec_stars_rating&rating=' + rating + '&post_id=' + post_id,
			ajaxCallback(root, star)
		);
	}



	/**
	 * Main function executed onDOMReady or onload
	 */
	function init() {
		var elements;

		if( ec_stars_loaded ) {
			return;
		}

		ec_stars_loaded = true;
		
		elements = document.querySelectorAll('.ec-stars-wrapper');

		forEach(elements, function() {
			var el = this,
				post_id = parseInt(el.getAttribute('data-post-id'), 10);

			addEvent(el, 'click', function(e) {
				// Cuz event delegation rules
				if( e.target.nodeName === "A" ) {
					e.preventDefault();
					vote(el, e.target, post_id);
				}
			})
		})
	}

	if( document.addEventListener ) {
		document.addEventListener('DOMContentLoaded', init, false);
	}

	addEvent(window, 'load', init);
}(window, window.document))