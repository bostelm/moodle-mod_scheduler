<?php

/**
 * Library for export functions
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/excellib.class.php');
require_once($CFG->dirroot.'/lib/odslib.class.php');
require_once($CFG->dirroot.'/lib/csvlib.class.php');
require_once($CFG->dirroot.'/lib/pdflib.php');


/**
 * A data field included in an export from Scheduler.
 */
abstract class scheduler_export_field {

    protected $renderer;

    public function set_renderer(mod_scheduler_renderer $renderer) {
        $this->renderer = $renderer;
    }

    /**
     * Is the field available in this scheduler?
     * @return boolean whether the field is available
     */
    public function is_available(scheduler_instance $scheduler) {
        return true;
    }

    /**
     * Retrieve the unique id (a string) for this field
     */
    public abstract function get_id();

    /**
     * Retrieve the group that this field belongs to -
     * either 'slot' or 'student' or 'appointment',
     *
     * @return string the group id as above
     */
    public abstract function get_group();

    /**
     * Retrieve the header (in the sense of table header in the output)
     * used for this field.
     *
     * @param $scheduler the scheduler instance in question
     * @return string the header for this field
     */
    public function get_header(scheduler_instance $scheduler) {
        return get_string('field-'.$this->get_id(), 'scheduler');
    }

    /**
     * Retrieve the label used in the configuration form to label this field.
     * By default, this equals the table header.
     *
     * @param $scheduler the scheduler instance in question
     * @return string the form label for this field
     */
    public function get_formlabel(scheduler_instance $scheduler) {
        return $this->get_header($scheduler);
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param $scheduler the scheduler instance in question
     * @return int the width of this field (number of characters)
     */
    public function get_typical_width(scheduler_instance $scheduler) {
        return strlen($this->get_formlabel($scheduler));
    }

    /**
     * Does this field use wrapped text?
     *
     * @return boolean whether wrapping is used for this field
     */
    public function is_wrapping() {
        return false;
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param $slot the scheduler slot to get data from
     * @param $appointment the appointment to evaluate (may be null for an empty slot)
     * @return string the value of this field for the given data
     */
    public abstract function get_value(scheduler_slot $slot, $appointment);

}


/**
 * Get a list of all export fields available.
 *
 * @return array the fields as an array of scheduler_export_field objects.
 */
function scheduler_get_export_fields() {
    $result = array();
    $result[] = new scheduler_slotdate_field();
    $result[] = new scheduler_starttime_field();
    $result[] = new scheduler_endtime_field();
    $result[] = new scheduler_location_field();
    $result[] = new scheduler_teachername_field();
    $result[] = new scheduler_maxstudents_field();
    $result[] = new scheduler_slotnotes_field();

    $result[] = new scheduler_student_field('studentfullname', 'fullname', 25);
    $result[] = new scheduler_student_field('studentfirstname', 'firstname');
    $result[] = new scheduler_student_field('studentlastname', 'lastname');
    $result[] = new scheduler_student_field('studentemail', 'email');
    $result[] = new scheduler_student_field('studentusername', 'username');
    $result[] = new scheduler_student_field('studentidnumber', 'idnumber');

    $result[] = new scheduler_attended_field();
    $result[] = new scheduler_grade_field();
    $result[] = new scheduler_appointmentnote_field();
    $result[] = new scheduler_teachernote_field();

    return $result;
}


/**
 * Export field: Date of the slot
 */
class scheduler_slotdate_field extends scheduler_export_field {

    public function get_id() {
        return 'date';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_typical_width(scheduler_instance $scheduler) {
        return strlen(mod_scheduler_renderer::userdate(1)) + 3;
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        return mod_scheduler_renderer::userdate($slot->starttime);
    }
}

/**
 * Export field: Start time of the slot
 */
class scheduler_starttime_field extends scheduler_export_field {

    public function get_id() {
        return 'starttime';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        return mod_scheduler_renderer::usertime($slot->starttime);
    }

}


/**
 * Export field: End time of the slot
 */
class scheduler_endtime_field extends scheduler_export_field {

    public function get_id() {
        return 'endtime';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        return mod_scheduler_renderer::usertime($slot->endtime);
    }

}

/**
 * Export field: Full name of the teacher
 */
class scheduler_teachername_field extends scheduler_export_field {

    public function get_id() {
        return 'teachername';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_header(scheduler_instance $scheduler) {
        return $scheduler->get_teacher_name();
    }

    public function get_typical_width(scheduler_instance $scheduler) {
        return 20;
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        return fullname($slot->teacher);
    }

}

/**
 * Export field: Appointment location
 */
class scheduler_location_field extends scheduler_export_field {

    public function get_id() {
        return 'location';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        return format_string($slot->appointmentlocation);
    }

}

/**
 * Export field: Maximum number of students / appointments in the slot
 */
class scheduler_maxstudents_field extends scheduler_export_field {

    public function get_id() {
        return 'maxstudents';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        if ($slot->exclusivity <= 0) {
            return get_string('unlimited', 'scheduler');
        } else {
            return $slot->exclusivity;
        }
    }

}

/**
 * Export field: A field in the student record (to be chosen via the constructor)
 */
class scheduler_student_field extends scheduler_export_field {

    protected $id;
    protected $studfield;
    protected $typicalwidth;

    public function __construct($id, $studfield, $typicalwidth=0) {
        $this->id = $id;
        $this->studfield = $studfield;
        $this->typicalwidth = $typicalwidth;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_group() {
        return 'student';
    }

    public function get_typical_width(scheduler_instance $scheduler) {
        if ($this->typicalwidth > 0) {
            return $this->typicalwidth;
        } else {
            return parent::get_typical_width($scheduler);
        }
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        if (! $appointment instanceof scheduler_appointment) {
            return '';
        }
        $student = $appointment->get_student();
        if (is_null($student)) {
            return '';
        }
        if ($this->studfield == 'fullname') {
            return fullname($student);
        } else {
            return $student->{$this->studfield};
        }
    }


}

/**
 * Export field: Whether the appointment has been attended
 */
class scheduler_attended_field extends scheduler_export_field {

    public function get_id() {
        return 'attended';
    }

    public function get_group() {
        return 'appointment';
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        if (! $appointment instanceof scheduler_appointment) {
            return '';
        }
        $str = $appointment->is_attended() ? get_string('yes') : get_string('no');
        return $str;
    }

}

/**
 * Export field: Slot notes
 */
class scheduler_slotnotes_field extends scheduler_export_field {

    public function get_id() {
        return 'slotnotes';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_typical_width(scheduler_instance $scheduler) {
        return 30;
    }

    public function is_wrapping() {
        return true;
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        return strip_tags($slot->notes);
    }

}

/**
 * Export field: Appointment notes
 */
class scheduler_appointmentnote_field extends scheduler_export_field {

    public function get_id() {
        return 'appointmentnote';
    }

    public function get_group() {
        return 'appointment';
    }

    public function get_typical_width(scheduler_instance $scheduler) {
        return 30;
    }

    public function is_wrapping() {
        return true;
    }

    public function is_available(scheduler_instance $scheduler) {
        return $scheduler->uses_appointmentnotes();
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        if (! $appointment instanceof scheduler_appointment) {
            return '';
        }
        return strip_tags($appointment->appointmentnote);
    }

}

/**
 * Export field: Teacher notes
 */
class scheduler_teachernote_field extends scheduler_export_field {

    public function get_id() {
        return 'teachernote';
    }

    public function get_group() {
        return 'appointment';
    }

    public function get_typical_width(scheduler_instance $scheduler) {
        return 30;
    }

    public function is_wrapping() {
        return true;
    }

    public function is_available(scheduler_instance $scheduler) {
        return $scheduler->uses_teachernotes();
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        if (! $appointment instanceof scheduler_appointment) {
            return '';
        }
        return strip_tags($appointment->teachernote);
    }

}

/**
 * Export field: Grade for the appointment
 */
class scheduler_grade_field extends scheduler_export_field {

    public function get_id() {
        return 'grade';
    }

    public function get_group() {
        return 'appointment';
    }

    public function is_available(scheduler_instance $scheduler) {
        return $scheduler->uses_grades();
    }

    public function get_value(scheduler_slot $slot, $appointment) {
        if (! $appointment instanceof scheduler_appointment) {
            return '';
        }
        return $this->renderer->format_grade($slot->get_scheduler(), $appointment->grade);
    }

}

/**
 * An "output device" for scheduler exports
 */
abstract class scheduler_canvas {


    public $formatheader;
    public $formatbold;
    public $formatboldit;
    public $formatwrap;

    /**
     * Start a new page (tab, etc.) with an optional title.
     *
     * @param $title the title of the page
     */
    public abstract function start_page($title);

    /**
     * Write a string into a certain position of the canvas.
     *
     * @param $row the row into which to write (starts with 0)
     * @param $column the column into which to write (starts with 0)
     * @param $str the string to write
     * @param $format the format to use (one of the $format... fields of this object), can be null
     */
    public abstract function write_string($row, $col, $str, $format);

    /**
     * Write a number into a certain position of the canvas.
     *
     * @param $row the row into which to write (starts with 0)
     * @param $column the column into which to write (starts with 0)
     * @param $num the number to write
     * @param $format the format to use (one of the $format... fields of this object), can be null
     */
    public abstract function write_number($row, $col, $num, $format);

    /**
     * Merge a range of cells in the same row.
     *
     * @param $row the row in which to merge
     * @param $fromcol the first column to merge
     * @param $tocol the last column to merge
     */
    public abstract function merge_cells($row, $fromcol, $tocol);

    /**
     * Set the width of a particular column. (This will make sense only for certain outout formats,
     * it can be ignored otherwise.)
     *
     * @param $col the affected column
     * @param $width the width of that column
     */
    public function set_column_width($col, $width) {
        // Ignore widths by default.
    }

    protected $title;

    /**
     * Set the title of the entire output file.
     *
     * This is stored in the field $title, and can be used as appropriate for the particular implementation.
     *
     * @param title the title to set
     */
    public function set_title($title) {
        $this->title = $title;
    }

    /**
     * Send the output file via HTTP, as a downloadable file.
     *
     * @param $filename the file name to send
     */
    public abstract function send($filename);

}



/**
 * Output device: Excel file
 */
class scheduler_excel_canvas extends scheduler_canvas {

    protected $workbook;
    protected $worksheet;


    public function __construct() {

        // Create a workbook.
        $this->workbook = new MoodleExcelWorkbook("-");

        // Set up formats.
        $this->formatheader = $this->workbook->add_format();
        $this->formatbold = $this->workbook->add_format();
        $this->formatbold = $this->workbook->add_format();
        $this->formatboldit = $this->workbook->add_format();
        $this->formatwrap = $this->workbook->add_format();
        $this->formatheader->set_bold();
        $this->formatbold->set_bold();
        $this->formatboldit->set_bold();
        $this->formatboldit->set_italic();
        $this->formatwrap->set_text_wrap();

    }


    public function start_page($title) {
        $this->worksheet = $this->workbook->add_worksheet($title);
    }

    private function ensure_open_page() {
        if (!$this->worksheet) {
            $this->start_page('');
        }
    }

    public function write_string($row, $col, $str, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_string($row, $col, $str, $format);
    }

    public function write_number($row, $col, $num, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_number($row, $col, $num, $format);
    }

    public function merge_cells($row, $fromcol, $tocol) {
        $this->ensure_open_page();
        $this->worksheet->merge_cells($row, $fromcol, $row, $tocol);
    }

    public function set_column_width($col, $width) {
        $this->worksheet->set_column($col, $col, $width);
    }

    public function send($filename) {
        $this->workbook->send($filename);
        $this->workbook->close();
    }

}

/**
 * Output device: ODS file
 */
class scheduler_ods_canvas extends scheduler_canvas {

    protected $workbook;
    protected $worksheet;


    public function __construct() {

        // Create a workbook.
        $this->workbook = new MoodleODSWorkbook("-");

        // Set up formats.
        $this->formatheader = $this->workbook->add_format();
        $this->formatbold = $this->workbook->add_format();
        $this->formatboldit = $this->workbook->add_format();
        $this->formatwrap = $this->workbook->add_format();
        $this->formatheader->set_bold();
        $this->formatbold->set_bold();
        $this->formatboldit->set_bold();
        $this->formatboldit->set_italic();
        $this->formatwrap->set_text_wrap();

    }


    public function start_page($title) {
        $this->worksheet = $this->workbook->add_worksheet($title);
    }

    private function ensure_open_page() {
        if (!$this->worksheet) {
            $this->start_page('');
        }
    }


    public function write_string($row, $col, $str, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_string($row, $col, $str, $format);
    }

    public function write_number($row, $col, $num, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_number($row, $col, $num, $format);
    }

    public function merge_cells($row, $fromcol, $tocol) {
        $this->ensure_open_page();
        $this->worksheet->merge_cells($row, $fromcol, $row, $tocol);
    }

    public function set_column_width($col, $width) {
        $this->worksheet->set_column($col, $col, $width);
    }

    public function send($filename) {
        $this->workbook->send($filename);
        $this->workbook->close();
    }

}


/**
 * An output device that is based on first collecting all text in an array.
 */
abstract class scheduler_cached_text_canvas extends scheduler_canvas {

    protected $pages;
    protected $curpage;

    public function __construct() {

        $this->formatheader = 'header';
        $this->formatbold = 'bold';
        $this->formatboldit = 'boldit';
        $this->formatwrap = 'wrap';

        $this->start_page('');

    }

    protected function get_col_count($page) {
        $maxcol = 0;
        foreach ($page->cells as $rownum => $row) {
            foreach ($row as $colnum => $col) {
                if ($colnum > $maxcol) {
                    $maxcol = $colnum;
                }
            }
        }
        return $maxcol + 1;
    }

    protected function get_row_count($page) {
        $maxrow = 0;
        foreach ($page->cells as $rownum => $row) {
            if ($rownum > $maxrow) {
                $maxrow = $rownum;
            }
        }
        return $maxrow + 1;
    }

    protected function compute_relative_widths($page) {
        $cols = $this->get_col_count($page);
        $sum = 0;
        foreach ($page->columnwidths as $width) {
            $sum += $width;
        }
        $relwidths = array();
        for ($col = 0; $col < $cols; $col++) {
            if ($sum > 0 && isset($page->columnwidths[$col])) {
                $relwidths[$col] = (int) ($page->columnwidths[$col] / $sum * 100);
            } else {
                $relwidths[$col] = 0;
            }
        }
        return $relwidths;
    }

    public function start_page($title) {
        $onemptypage = $this->curpage &&  !$this->curpage->cells && !$this->curpage->mergers && !$this->curpage->title;
        if ($onemptypage) {
            $this->curpage->title = $title;
        } else {
            $newpage = new stdClass;
            $newpage->title = $title;
            $newpage->cells = array();
            $newpage->formats = array();
            $newpage->mergers = array();
            $newpage->columnwidths = array();
            $this->pages[] = $newpage;
            $this->curpage = $newpage;
        }
    }


    public function write_string($row, $col, $str, $format=null) {
        $this->curpage->cells[$row][$col] = $str;
        $this->curpage->formats[$row][$col] = $format;
    }

    public function write_number($row, $col, $num, $format=null) {
        $this->write_string($row, $col, $num, $format);
    }

    public function merge_cells($row, $fromcol, $tocol) {
        $this->curpage->mergers[$row][$fromcol] = $tocol - $fromcol + 1;
    }

    public function set_column_width($col, $width) {
        $this->curpage->columnwidths[$col] = $width;
    }

}

/**
 * Output device: HTML file
 */
class scheduler_html_canvas extends scheduler_cached_text_canvas {

    public function as_html($rowcutoff, $usetitle = true) {
        global $OUTPUT;

        $o = '';

        if ($usetitle && $this->title) {
            $o .= html_writer::tag('h1', $this->title);
        }

        foreach ($this->pages as $page) {
            if ($page->title) {
                $o .= html_writer::tag('h2', $page->title);
            }

            // Find extent of the table.
            $rows = $this->get_row_count($page);
            $cols = $this->get_col_count($page);
            if ($rowcutoff && $rows > $rowcutoff) {
                $rows = $rowcutoff;
            }
            $relwidths = $this->compute_relative_widths($page);

            $table = new html_table();
            $table->cellpadding = 3;
            for ($row = 0; $row < $rows; $row++) {
                $hrow = new html_table_row();
                $col = 0;
                while ($col < $cols) {
                    $span = 1;
                    if (isset($page->mergers[$row][$col])) {
                        $mergewidth = (int) $page->mergers[$row][$col];
                        if ($mergewidth >= 1) {
                            $span = $mergewidth;
                        }
                    }
                    $cell = new html_table_cell('');
                    $text = '';
                    if (isset($page->cells[$row][$col])) {
                        $text = $page->cells[$row][$col];
                    }
                    if (isset($page->formats[$row][$col])) {
                        $cell->header = ($page->formats[$row][$col] == 'header');
                        if ($page->formats[$row][$col] == 'boldit') {
                            $text = html_writer::tag('i', $text);
                            $text = html_writer::tag('b', $text);
                        }
                        if ($page->formats[$row][$col] == 'bold') {
                            $text = html_writer::tag('b', $text);
                        }
                    }
                    if ($span > 1) {
                        $cell->colspan = $span;
                    }
                    if ($row == 0 & $relwidths[$col] > 0) {
                        $cell->width = $relwidths[$col].'%';
                    }
                    $cell->text = $text;
                    $hrow->cells[] = $cell;
                    $col = $col + $span;
                }
                $table->data[] = $hrow;
            }
            $o .= html_writer::table($table);
        }
        return $o;
    }

    public function send($filename) {
        global $OUTPUT, $PAGE;
        $PAGE->set_pagelayout('print');
        echo $OUTPUT->header();
        echo $this->as_html(0, true);
        echo $OUTPUT->footer();
    }

}

/**
 * Output device: CSV (text) file
 */
class scheduler_csv_canvas extends scheduler_cached_text_canvas {

    protected $delimiter;

    public function __construct($delimiter) {
        parent::__construct();
        $this->delimiter = $delimiter;
    }

    public function send($filename) {

        $writer = new csv_export_writer($this->delimiter);
        $writer->set_filename($filename);

        foreach ($this->pages as $page) {
            if ($page->title) {
                $writer->add_data(array('*** '.$page->title.' ***'));
            }

            // Find extent of the table.
            $rows = $this->get_row_count($page);
            $cols = $this->get_col_count($page);

            for ($row = 0; $row < $rows; $row++) {
                $data = array();
                $col = 0;
                while ($col < $cols) {
                    if (isset($page->cells[$row][$col])) {
                        $data[] = $page->cells[$row][$col];
                    } else {
                        $data[] = '';
                    }

                    $span = 1;
                    if (isset($page->mergers[$row][$col])) {
                        $mergewidth = (int) $page->mergers[$row][$col];
                        if ($mergewidth >= 1) {
                            $span = $mergewidth;
                        }
                    }
                    $col += $span;
                }
                $writer->add_data($data);
            }
        }

        $writer->download_file();
    }

}

/**
 * Output device: PDF file
 */
class scheduler_pdf_canvas extends scheduler_cached_text_canvas {

    protected $orientation;

    public function __construct($orientation) {
        parent::__construct();
        $this->orientation = $orientation;
    }

    public function send($filename) {

        $doc = new pdf($this->orientation);
        if ($this->title) {
            $doc->setHeaderData('', 0, $this->title);
            $doc->setPrintHeader(true);
        } else {
            $doc->setPrintHeader(false);
        }
        $doc->setPrintFooter(false);

        foreach ($this->pages as $page) {
            $doc->AddPage();
            if ($page->title) {
                $doc->writeHtml('<h2>'.$page->title.'</h2>');
            }

            // Find extent of the table.
            $rows = $this->get_row_count($page);
            $cols = $this->get_col_count($page);
            $relwidths = $this->compute_relative_widths($page);

            $o = html_writer::start_tag('table', array('border' => 1, 'cellpadding' => 1));
            for ($row = 0; $row < $rows; $row++) {
                $o .= html_writer::start_tag('tr');
                $col = 0;
                while ($col < $cols) {
                    $span = 1;
                    if (isset($page->mergers[$row][$col])) {
                        $mergewidth = (int) $page->mergers[$row][$col];
                        if ($mergewidth >= 1) {
                            $span = $mergewidth;
                        }
                    }
                    $opts = array();
                    if ($row == 0 && $relwidths[$col] > 0) {
                        $opts['width'] = $relwidths[$col].'%';
                    }
                    if ($span > 1) {
                        $opts['colspan'] = $span;
                    }
                    $o .= html_writer::start_tag('td', $opts);
                    $cell = '';
                    if (isset($page->cells[$row][$col])) {
                        $cell = s($page->cells[$row][$col]);
                        if (isset($page->formats[$row][$col])) {
                            $thisformat = $page->formats[$row][$col];
                            if ($thisformat == 'header') {
                                $cell = html_writer::tag('b', $cell);
                            } else if ($thisformat == 'boldit') {
                                $cell = html_writer::tag('i', $cell);
                            }
                        }
                    }
                    $o .= $cell;

                    $o .= html_writer::end_tag('td');

                    $col += $span;
                }
                $o .= html_writer::end_tag('tr');
            }
            $o .= html_Writer::end_tag('table');
            $doc->writeHtml($o);
        }

        $doc->Output($filename.'.pdf');
    }

}

/**
 * A class that generates the export file with given settings.
 */
class scheduler_export {

    protected $canvas;
    protected $studfilter = null;

    public function __construct(scheduler_canvas $canvas) {
        $this->canvas = $canvas;
    }


    public function build(scheduler_instance $scheduler, array $fields, $mode, $userid, $groupid, $includeempty, $pageperteacher) {
        if ($groupid) {
            $this->studfilter = array_keys(groups_get_members($groupid, 'u.id'));
        }
        $this->canvas->set_title(format_string($scheduler->name));
        if ($userid) {
            $slots = $scheduler->get_slots_for_teacher($userid, $groupid);
            $this->build_page($scheduler, $fields, $slots, $mode, $includeempty);
        } else if ($pageperteacher) {
            $teachers = $scheduler->get_teachers();
            foreach ($teachers as $teacher) {
                $slots = $scheduler->get_slots_for_teacher($teacher->id, $groupid);
                $title = fullname($teacher);
                $this->canvas->start_page($title);
                $this->build_page($scheduler, $fields, $slots, $mode, $includeempty);
            }
        } else {
            $slots = $scheduler->get_slots_for_group($groupid);
            $this->build_page($scheduler, $fields, $slots, $mode, $includeempty);
        }
    }

    protected function build_page(scheduler_instance $scheduler, array $fields, array $slots, $mode, $includeempty) {

        // Output the header.
        $row = 0;
        $col = 0;
        foreach ($fields as $field) {
            if ($field->get_group() != 'slot' || $mode != 'appointmentsgrouped') {
                $header = $field->get_header($scheduler);
                $this->canvas->write_string($row, $col, $header, $this->canvas->formatheader);
                $this->canvas->set_column_width($col, $field->get_typical_width($scheduler));
                $col++;
            }
        }
        $row++;

        // Output the data rows.
        foreach ($slots as $slot) {
            $appts = $slot->get_appointments($this->studfilter);
            if ($mode == 'appointmentsgrouped') {
                if ($appts || $includeempty) {
                    $this->write_row_summary($row, $slot, $fields);
                    $row++;
                }
                foreach ($appts as $appt) {
                    $this->write_row($row, $slot, $appt, $fields, false);
                    $row++;
                }
            } else {
                if ($appts) {
                    if ($mode == 'onelineperappointment') {
                        foreach ($appts as $appt) {
                            $this->write_row($row, $slot, $appt, $fields, true);
                            $row++;
                        }
                    } else {
                        $this->write_row($row, $slot, $appts[0], $fields, true, count($appts) > 1);
                        $row++;
                    }
                } else if ($includeempty) {
                    $this->write_row($row, $slot, null, $fields, true);
                    $row++;
                }
            }
        }

    }

    protected function write_row($row, scheduler_slot $slot, $appointment, $fields, $includeslotfields = true, $multiple = false) {

        $col = 0;
        foreach ($fields as $field) {
            if ($includeslotfields || $field->get_group() != 'slot') {
                if ($multiple && $field->get_group() != 'slot') {
                    $value = get_string('multiple', 'scheduler');
                } else {
                    $value = $field->get_value($slot, $appointment);
                }
                $format = $field->is_wrapping() ? $this->canvas->formatwrap : null;
                $this->canvas->write_string($row, $col, $value, $format);
                $col++;
            }
        }
    }

    protected function write_row_summary($row, scheduler_slot $slot, $fields) {

        $strs = array();
        $cols = 0;
        foreach ($fields as $field) {
            if ($field->get_group() == 'slot') {
                $strs[] = $field->get_value($slot, null);
            } else {
                $cols++;
            }
        }
        $str = implode(' - ', $strs);
        $this->canvas->write_string($row, 0, $str, $this->canvas->formatboldit);
        $this->canvas->merge_cells($row, 0, $cols - 1);
    }

}
