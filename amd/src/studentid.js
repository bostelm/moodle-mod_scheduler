// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Potential user selector module.
 *
 * @module     mod_scheduler/studentid
 * @copyright  2022 University of Glasgow
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/templates'], function($, Ajax, Templates) {

    return /** @alias module:mod_scheduler/studentid */ {

        processResults: function(selector, results) {
            var users = [];
            $.each(results, function(index, user) {
                users.push({
                    value: user.id,
                    label: user._label
                });
            });
            return users;
        },

        transport: function(selector, query, success, failure) {
            var promise;

            let scheduler = $(selector).attr('scheduler') || null;
            let groupids = $(selector).attr('groupids') || null;
            promise = Ajax.call([{
                methodname: 'mod_scheduler_studentid',
                args: {
                    query: query,
                    scheduler: scheduler,
                    groupids: groupids
                }
            }]);

            promise[0].then(function(results) {
                var promises = [],
                    i = 0;

                // Render the label.
                $.each(results, function(index, user) {
                    promises.push(Templates.render('mod_scheduler/studentid', user));
                });

                // Apply the label to the results.
                return $.when.apply($.when, promises).then(function() {
                    var args = arguments;
                    $.each(results, function(index, user) {
                        user._label = args[i];
                        i++;
                    });
                    success(results);
                    return;
                });

            }).fail(failure);
        }

    };

});