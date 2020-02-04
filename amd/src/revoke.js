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
 * Revoke an appointment.
 *
 * This module allows for revoking an appointment from a student list.
 *
 * @module     mod_scheduler/revoke
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    var actionSelector = '.otherstudent a.revoke-student';
    var studentWrapperSelector = '.otherstudent';

    /**
     * Revoker.
     *
     * @param {jQuery} root The root wrapper as jQuery element.
     * @param {Number} cmid The cmid.
     */
    function Revoker(root, cmid) {
        this.cmid = cmid;

        root.on('click', actionSelector, function(e) {
            e.preventDefault();
            var wrapper = $(e.target).closest(studentWrapperSelector);
            this.triggerRevoke(wrapper);
        }.bind(this));
    }

    /**
     * Trigger the revoke from a node.
     *
     * @param {jQuery} studentWrapper The student wrapper node.
     */
    Revoker.prototype.triggerRevoke = function(studentWrapper) {
        var appId = parseInt(studentWrapper.data('appointmentid'), 10);
        if (!appId) {
            return;
        }

        Str.get_strings([
            {key: 'confirmation', component: 'core_admin'},
            {key: 'confirmsinglerevoke', component: 'mod_scheduler'},
            {key: 'yes', component: 'core'},
            {key: 'no', component: 'core'}
        ]).then(function(str) {
            Notification.confirm(str[0], str[1], str[2], str[3], function() {
                // The user confirmed.
                studentWrapper.hide();
                this.revokeAppointment(appId).then(function() {
                    studentWrapper.remove();
                    return;
                }).fail(function(e) {
                    studentWrapper.show();
                    Notification.exception(e);
                });
            }.bind(this), function() {
                // The user cancelled.
            });
        }.bind(this)).fail(Notification.exception);
    };

    /**
     * Revoke an appointment.
     *
     * @param {Number} appId The appointment ID.
     * @return {Deferred}
     */
    Revoker.prototype.revokeAppointment = function(appId) {
        return Ajax.call([{
            methodname: 'mod_scheduler_revoke_appointment',
            args: {
                cmid: this.cmid,
                appointmentid: appId
            }
        }])[0];
    };

    return {
        init: function(wrapperSelector, cmid) {
            new Revoker($(wrapperSelector), cmid);
        }
    };

});
