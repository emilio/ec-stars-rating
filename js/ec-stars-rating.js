/**
 * EC-stars-rating
 * Copyright (C) 2015 Emilio Cobos Álvarez (http://emiliocobos.me)
 *
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
 * with this program; if not, see http://github.com/ecoal95/ec-stars-rating/blob/master/LICENSE
 */
;(function ($) {

	var globaldata = window.ec_ajax_data || null;

	$.fn.wpAjaxRating = function() {
		return this.each(function() {
			var el = this,
				post_id = parseInt(this.getAttribute('data-post-id'), 10);
			$(this).on('click', 'a', function(e) {
				var rating = parseInt(this.getAttribute('data-value'), 10),
					data = {
						"action": "ec_stars_rating",
						"rating": rating,
						"post_id": post_id
					},
					update_value = function(result, count) {
						$(el).addClass("is-voted")
							.find('.ec-stars-overlay').css({
								"width": (100 - result * 100 / 5) + '%'
							})
								.end().parent()
							.find('.ec-stars-rating-count').html(count)
								.end().parent()
							.find('.ec-stars-rating-value').html(parseInt(result * 100) / 100);

					},
					show_message = function(message) {
						el.setAttribute('data-tooltip', message);
					};

				if( globaldata === null ) {
					globaldata = window.ec_ajax_data || null;
				}
				e.preventDefault();
				if( (/\bis-voted\b/).test(el.className) === null || rating > 5 || rating < 1) {
					return;
				}

				$.post(globaldata.ajax_url, data, function(response) {
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
							// break;
					}


				}, "json")
			});
		});
	};

	$(function() {
		$('.ec-stars-wrapper').wpAjaxRating();
	})
}(window.jQuery))
