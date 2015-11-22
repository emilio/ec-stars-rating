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
(function (window, document) {
  "use strict";

  var globaldata = null,
    ec_stars_loaded = false,
    voting = false;

  if (!document.querySelectorAll) {
    return;
  }

  /**
   * Loop through an array of elements
   * @param {Array|Nodelist} col
   * @param {Function} callback
   * @return void
   */
  function forEach(col, callback) {
    var i,
      len = col.length;

    for (i = 0; i < len; i++) {
      callback.call(col[i], col[i], i);
    }
  }

  function map(ary, callback) {
    var ret = [];

    forEach(ary, function (el) {
      ret.push(callback.call(el, el));
    });

    return ret;
  }

  /**
   * Add an event to a node
   * @param {Node} el
   * @param {String} type
   * @param {Function} callback
   * @return void
   */
  function addEvent(el, type, callback) {
    if (el.addEventListener) {
      el.addEventListener(type, callback, false);
    } else if (el.attachEvent) {
      el.attachEvent('on' + type, function (e) {
        if (!e) {
          e = window.event;
        }
        if (!e.preventDefault) {
          e.preventDefault = function () {
            e.returnValue = false;
          };
          e.stopPropagation = function () {
            e.cancelBubble = true;
          };
          e.isDefaultPrevented = function () {
            return e.returnValue === false;
          };
        }
        if (!e.target) {
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
    req.onreadystatechange = function () {
      if (req.readyState === 4) {
        callback(req.responseText);
      }
    };

    req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

    req.send(data);
  }


  function updateValues(root, result, count) {
    var parent = root.parentNode,
      overlay_el = root.querySelector('.ec-stars-overlay'),
      count_el = parent.querySelector('.ec-stars-rating-count'),
      value_el = parent.querySelector('.ec-stars-rating-value');


    if (overlay_el)
      overlay_el.style.width = (100 - result * 100 / 5) + '%';

    if (count_el)
      count_el.innerHTML = count;

    if (value_el)
      value_el.innerHTML = parseInt(result * 100, 10) / 100;
  }


  /**
   * Generate a function that we'll use as AJAX callback
   * @see vote
   * @return {Function}
   */
  function ajaxCallback(root, star) {
    return function (response) {
      var message;
      response = JSON.parse(response);
      message = globaldata.messages.unknown;

      switch(response.status) {
        case globaldata.codes.PREVIOUSLY_VOTED:
          message = globaldata.messages.previously_voted;
          break;
        case globaldata.codes.REQUEST_ERROR:
          message = globaldata.messages.request_error;
          break;
        case globaldata.codes.SUCCESS:
          root.className += ' is-voted';
          updateValues(root, response.result, response.votes);
          message = globaldata.messages.success;
          break;
      }

      root.setAttribute('data-tooltip', message);
      voting = false;
    };
  }

  /**
   * Vote (if it's not voted)
   * @param {Node} root the wrapper element
   * @param {Node} star the clicked star
   * @param {Number} post_id the id of the voted post
   */
  function vote(root, star, post_id) {
    var rating = parseInt(star.getAttribute('data-value'), 10);

    // Don't allow two votes at the same time
    //
    // The server handles this too, but this way we avoid the
    // roundtrip.
    if (voting)
      return;

    if (window.console)
      console.log(root, star, post_id, rating);

    // If we previously voted, or there is something bad in the rating ,stop
    if (/\bis-voted\b/.test(root.className) || ! rating || rating > 5 || rating < 1)
      return;

    postRequest(
      globaldata.ajax_url,
      'action=ec_stars_rating&rating=' + rating + '&post_id=' + post_id,
      ajaxCallback(root, star)
    );
  }



  /**
   * Main function executed on DOMContentLoaded or onload
   */
  function init() {
    globaldata = window.ec_ajax_data;

    if (ec_stars_loaded || !globaldata)
      return;

    ec_stars_loaded = true;

    var elements = document.querySelectorAll('.ec-stars-wrapper');

    forEach(elements, function (root) {
      var post_id = parseInt(root.getAttribute('data-post-id'), 10);

      forEach(root.querySelectorAll('a'), function (star) {
        addEvent(star, 'click', function (e) {
          e.preventDefault();
          vote(root, this, post_id);
        });
      });
    });

    if (globaldata.workaround_cache) {
        var ids = map(elements, function (root) {
          return parseInt(root.getAttribute('data-post-id'), 10);
        });

        postRequest(globaldata.ajax_url,
                    'action=ec_stars_rating_workaround_cache&post_ids=' + ids.join(','),
                    function (response) {
                      response = JSON.parse(response);
                      forEach(response, function (response) {
                        updateValues(document.getElementById('ec-stars-wrapper-' + response.id), response.result, response.votes);
                      });
                    });
    }
  }

  if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', init, false);
  }

  addEvent(window, 'load', init);
} (window, window.document));
