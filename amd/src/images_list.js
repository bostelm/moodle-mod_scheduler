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
 * Report builder images list management
 *
 * @module      core_imagebuilder/images_list
 * @copyright   2021 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

"use strict";

import {dispatchEvent} from 'core/event_dispatcher';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {prefetchStrings} from 'core/prefetch';
import {getString} from 'core/str';
import {add as addToast} from 'core/toast';
import * as reportEvents from 'core_reportbuilder/local/events';
import Ajax from 'core/ajax';

/**
 * Delete given image
 *
 * @param {Number} imageId
 * @return {Promise}
 */
export const deleteImage = imageId => {
    const request = {
        methodname: 'mod_scheduler_delete_image',
        args: {imageid: imageId}
    };

    return Ajax.call([request])[0];
};

/**
 * Initialise module
 */
export const init = () => {
    prefetchStrings('mod_scheduler', [
        'deleteimage',
        'deleteimageconfirm',
        'imagedeleted',
    ]);

    prefetchStrings('core', [
        'delete',
    ]);

    document.addEventListener('click', event => {
        const imageDelete = event.target.closest('[data-action="image-delete"]');
        if (imageDelete) {
            event.preventDefault();

            // Use triggerElement to return focus to the action menu toggle.
            const triggerElement = imageDelete.closest('.dropdown').querySelector('.dropdown-toggle');
            Notification.saveCancelPromise(
                getString('deleteimage', 'mod_scheduler'),
                getString('deleteimageconfirm', 'mod_scheduler', imageDelete.dataset.imageName),
                getString('delete', 'core'),
                {triggerElement}
            ).then(() => {
                const pendingPromise = new Pending('mod_scheduler/image:delete');
                const reportElement = event.target.closest('[data-region="core_reportbuilder/report"]');

                return deleteImage(imageDelete.dataset.imageId)
                    .then(() => addToast(getString('imagedeleted', 'mod_scheduler')))
                    .then(() => {
                        dispatchEvent(reportEvents.tableReload, {preservePagination: true}, reportElement);
                        return pendingPromise.resolve();
                    })
                    .catch(Notification.exception);
            }).catch(() => {
                return;
            });
        }
    });
};
