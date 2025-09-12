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

declare(strict_types=1);

namespace mod_scheduler\local\systemreports;

use html_writer;
use lang_string;
use moodle_url;
use pix_icon;
use stdClass;
use core_reportbuilder\datasource;
use core_reportbuilder\manager;
use core_reportbuilder\system_report;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\filters\{boolean_select, date, tags, text, select};
use core_reportbuilder\local\helpers\{audience, custom_fields, format};
use core_reportbuilder\local\report\{action, column, filter};
use core_reportbuilder\output\report_name_editable;
use core_reportbuilder\local\models\report;
use core_reportbuilder\permission;
use core_tag\reportbuilder\local\entities\tag;
use core_tag_tag;

/**
 * Images list
 *
 * @package     mod_scheduler
 * @copyright   Daniel Neis Araujo <danielneis@gmail.com>
 * @copyright   Based on reports lists from 2021 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class images_list extends system_report {

    /**
     * The name of our internal report entity
     *
     * @return string
     */
    private function get_report_entity_name(): string {
        return 'report';
    }

    /**
     * Initialise the report
     */
    protected function initialise(): void {
        $this->set_main_table('files', 'f');
        $report = $this->get_report_persistent();
        $this->add_base_condition_simple('f.itemid', $report->get('itemid'));
        $this->add_base_condition_simple('f.filearea', $report->get('area'));
        $this->add_base_condition_simple('f.component', $report->get('component'));
        $this->add_base_condition_simple('f.contextid', $report->get('contextid'));
        $this->add_base_condition_sql('f.filename != "."');

        // Select fields required for actions, permission checks, and row class callbacks.
        $this->add_base_fields('f.id, f.contenthash, f.filename, f.filearea');

        // Join user entity for "Userid" column.
        $entityuser = new user();
        $entityuseralias = $entityuser->get_table_alias('user');
        $this->add_entity($entityuser
            ->add_join("JOIN {user} {$entityuseralias} ON {$entityuseralias}.id = f.userid")
        );

        // Define our internal entity for report elements.
        $this->annotate_entity($this->get_report_entity_name(),
            new lang_string('imageslist', 'mod_scheduler'));

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(false);
    }

    /**
     * Ensure we can view the report
     *
     * @return bool
     */
    protected function can_view(): bool {
        // TODO: testar capabilities do modulo
        return permission::can_view_reports_list();
    }

    /**
     * Dim the table row for invalid datasource
     *
     * @param stdClass $row
     * @return string
    public function get_row_class(stdClass $row): string {
        return $this->report_source_valid($row->source) ? '' : 'text-muted';
    }
     */

    protected function get_file_url($value) {
        global $CFG;
        $report = $this->get_report_persistent();
        return $CFG->wwwroot . '/pluginfile.php/' .
                $report->get('contextid') .  '/mod_scheduler/message/' . $report->get('itemid') . '/' .  $value;

    }

    /**
     * Add columns to report
     */
    protected function add_columns(): void {
        $tablealias = $this->get_main_table_alias();

        // Report name column.
        $this->add_column((new column(
            'filename',
            new lang_string('file'),
            $this->get_report_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            // We need enough fields to re-create the persistent and pass to the editable component.
            ->add_fields("{$tablealias}.filename")
            ->set_is_sortable(true, ["{$tablealias}.filename"])
            ->add_callback(function(string $value, stdClass $row, $report) {
                $url = $this->get_file_url($value);
                return html_writer::link($url, $value);
            })
        );

        // Time created column.
        $this->add_column((new column(
            'timecreated',
            new lang_string('timecreated', 'core_reportbuilder'),
            $this->get_report_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.timecreated")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'])
        );

        // The user who modified the report.
        $this->add_column_from_entity('user:fullnamewithlink')
            ->set_title(new lang_string('user'));

        // Initial sorting.
        $this->set_initial_sort_column('report:timecreated', SORT_DESC);
    }

    /**
     * Add filters to report
     */
    protected function add_filters(): void {
        $tablealias = $this->get_main_table_alias();

        // Name filter.
        $this->add_filter((new filter(
            text::class,
            'filename',
            new lang_string('file'),
            $this->get_report_entity_name(),
            "{$tablealias}.filename"
        )));

        // Time created filter.
        $this->add_filter((new filter(
            date::class,
            'timecreated',
            new lang_string('timecreated', 'core_reportbuilder'),
            $this->get_report_entity_name(),
            "{$tablealias}.timecreated"
        ))
            ->set_limited_operators([
                date::DATE_ANY,
                date::DATE_RANGE,
                date::DATE_BEFORE,
                date::DATE_LAST,
                date::DATE_CURRENT,
            ])
        );

        // User modified filter.
        $this->add_filter_from_entity('user:userselect')
            ->set_header(new lang_string('usermodified', 'reportbuilder'))
            ->set_is_available(has_capability('moodle/user:viewalldetails', $this->get_context()));
    }

    /**
     * Add actions to report
     */
    protected function add_actions(): void {
        // Delete action.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/delete', ''),
            [
                'data-action' => 'image-delete',
                'data-image-id' => ':id',
                'data-image-name' => ':name',
                'class' => 'text-danger',
            ],
            false,
            new lang_string('deleteimage', 'mod_scheduler')
        ))
            ->add_callback(function(stdClass $row): bool {

                // Ensure data name attribute is properly formatted.
                $report = new report(0, $row);
                $row->name = $report->get_formatted_name();

                // We don't check whether report is valid to ensure editor can always delete them.
                return permission::can_edit_report($report);
            })
        );
    }

    /**
     * Helper to determine whether given report source is valid (it both exists, and is available)
     *
     * @param string $source
     * @return bool
     */
    private function report_source_valid(string $source): bool {
        return manager::report_source_exists($source, datasource::class) && manager::report_source_available($source);
    }
}
