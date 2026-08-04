<?php
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

namespace mod_scheduler\courseformat;

use mod_scheduler\model\scheduler;
use core\output\action_link;
use core\output\local\properties\button;
use core\output\local\properties\text_align;
use core\url;
use core_courseformat\local\overview\overviewitem;

/**
 * Scheduler overview integration (for Moodle 5.1+)
 *
 * @package   mod_scheduler
 * @copyright 2025 Luca Bösch <luca.boesch@bfh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_courseformat\activityoverviewbase {
    /** @var scheduler $scheduler the scheduler instance. */
    private scheduler $scheduler;

    /**
     * Constructor.
     *
     * @param \cm_info $cm the course module instance.
     * @param \core\output\renderer_helper $rendererhelper the renderer helper.
     */
    public function __construct(
        \cm_info $cm,
        /** @var \core\output\renderer_helper $rendererhelper the renderer helper */
        protected readonly \core\output\renderer_helper $rendererhelper,
    ) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/scheduler/locallib.php');
        parent::__construct($cm);
        $this->scheduler = \mod_scheduler\model\scheduler::load_by_coursemodule_id($cm->id);
    }

    #[\Override]
    public function get_actions_overview(): ?overviewitem {
        $url = new url(
            '/mod/scheduler/view.php',
            ['id' => $this->cm->id],
        );

        $text = get_string('view');

        if (
            class_exists(button::class) &&
            (new \ReflectionClass(button::class))->hasConstant('BODY_OUTLINE')
        ) {
            $bodyoutline = button::BODY_OUTLINE;
            $buttonclass = $bodyoutline->classes();
        } else {
            $buttonclass = "btn btn-outline-secondary";
        }

        $content = new action_link($url, $text, null, ['class' => $buttonclass]);
        return new overviewitem(get_string('actions'), $text, $content, text_align::CENTER);
    }

    #[\Override]
    public function get_extra_overview_items(): array {
        return [
            'openappointments' => $this->get_extra_open_appointments_overview(),
            'appointmentstatus' => $this->get_extra_appointment_status_overview(),
        ];
    }

    /**
     * Retrieves an overview of submissions for the assignment.
     *
     * @return overviewitem|null An overview item c, or null if the user lacks the required capability.
     */
    private function get_extra_open_appointments_overview(): ?overviewitem {
        global $USER;

        if (!has_capability('mod/scheduler:manageallappointments', $this->cm->context)) {
            return null;
        }

        if (
            class_exists(button::class) &&
            (new \ReflectionClass(button::class))->hasConstant('BODY_OUTLINE')
        ) {
            $bodyoutline = button::BODY_OUTLINE;
            $buttonclass = $bodyoutline->classes();
        } else {
            $buttonclass = "btn btn-outline-secondary";
        }

        $groupscheduling = $this->scheduler->is_group_scheduling_enabled();
        if (!$groupscheduling) {
            $openappointments = $this->scheduler->get_students_for_scheduling();
            if (is_array($openappointments)) {
                $openappointments = count($openappointments);
            }
            $total = count($this->scheduler->get_available_students());

            $content = new action_link(
                url: new url(
                    '/mod/scheduler/view.php',
                    [
                        'id' => $this->cm->id,
                        'what' => 'view',
                        'scope' => 'activity',
                        'subpage' => 'allappointments',
                    ]
                ),
                text: get_string(
                    'count_of_total',
                    'core',
                    ['count' => $openappointments, 'total' => $total]
                ),
                attributes: ['class' => $buttonclass],
            );
        } else {
            $openappointments = count($this->scheduler->get_groups_for_scheduling());
            $total = count($this->scheduler->get_available_groups());

            $content = new action_link(
                url: new url(
                    '/mod/scheduler/view.php',
                    [
                        'id' => $this->cm->id,
                        'what' => 'view',
                        'scope' => 'activity',
                        'subpage' => 'allappointments',
                    ]
                ),
                text: get_string(
                    'count_of_total_groups',
                    'scheduler',
                    ['count' => $openappointments, 'total' => $total]
                ),
                attributes: ['class' => $buttonclass],
            );
        }

        return new overviewitem(
            name: get_string('needmakeappointment', 'scheduler'),
            value: $openappointments,
            content: $content,
            textalign: text_align::CENTER,
        );
    }

    /**
     * Retrieves the appointment status overview for the current user.
     *
     * @return overviewitem|null The overview item, or null if the user does not have the required capabilities.
     */
    private function get_extra_appointment_status_overview(): ?overviewitem {
        global $USER;

        if (
            !has_capability('mod/scheduler:appoint', $this->context, $USER, false) ||
            has_capability('mod/scheduler:manage', $this->context, $USER, false)
        ) {
            return null;
        }

        $userappointment = $this->scheduler->get_appointments_for_student($USER->id);

        if (!empty($userappointment)) {
            $appointmentstatus = get_string('appointmentstatus_appointed', 'scheduler');
        } else {
            $appointmentstatus = get_string('appointmentstatus_unappointed', 'scheduler');
        }

        return new overviewitem(
            name: get_string('appointmentstatus', 'scheduler'),
            value: $appointmentstatus,
            content: $appointmentstatus,
        );
    }
}
