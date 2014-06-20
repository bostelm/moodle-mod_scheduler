<?php

/**
 * Exports the scheduler data in spreadsheet format.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



/************************************ ODS (OpenOffice Sheet) download generator ******************************/
if ($action == 'downloadods'){
    require_once($CFG->libdir."/odslib.class.php");
    /// Calculate file name
    $schedname = format_string($scheduler->name);
    $downloadfilename = clean_filename("{$course->shortname}_{$schedname}.ods");
    /// Creating a workbook
    $workbook = new MoodleODSWorkbook("-");
}
/************************************ Excel download generator ***********************************************/
if ($action == 'downloadexcel'){
    require_once($CFG->libdir."/excellib.class.php");

    /// Calculate file name
    $schedname = format_string($scheduler->name);
    $downloadfilename = clean_filename("{$course->shortname}_{$schedname}");
    /// Creating a workbook
    $workbook = new MoodleExcelWorkbook("-");
}
if($action == 'downloadexcel' || $action == 'downloadods'){

    /// Sending HTTP headers
    $workbook->send($downloadfilename);

    /// Prepare data
    $sql = 'SELECT DISTINCT '.user_picture::fields('u',array('email','department'))
          .' FROM {scheduler_slots} s, {user} u'
          .' WHERE s.teacherid = u.id AND schedulerid = ?';
    $teachers = $DB->get_records_sql($sql, array($scheduler->id));
    $slots = $DB->get_records('scheduler_slots', array('schedulerid' => $scheduler->id), 'starttime', 'id, starttime, duration, exclusivity, teacherid, hideuntil');
    if ($subaction == 'singlesheet'){
        /// Adding the worksheet
        $myxls['singlesheet'] = $workbook->add_worksheet($COURSE->shortname.': '.format_string($scheduler->name));
        $myxls['singlesheet']->write_string(0,0,get_string('date', 'scheduler'));
        $myxls['singlesheet']->write_string(0,1,get_string('starttime', 'scheduler'));
        $myxls['singlesheet']->write_string(0,2,get_string('endtime', 'scheduler'));
        $myxls['singlesheet']->write_string(0,3,get_string('slottype', 'scheduler'));
        $myxls['singlesheet']->write_string(0,4,$scheduler->get_teacher_name());
        $myxls['singlesheet']->write_string(0,5,get_string('students', 'scheduler'));
        $myxls['singlesheet']->set_column(0,0,26);
        $myxls['singlesheet']->set_column(1,2,15);
        $myxls['singlesheet']->set_column(3,3,10);
        $myxls['singlesheet']->set_column(4,4,20);
        $myxls['singlesheet']->set_column(5,5,60);
        $f = $workbook->add_format(array('bold' => 1));
        $myxls['singlesheet']->set_row(0,13,$f);
    }
    elseif ($subaction == 'byteacher') {
        /// Adding the worksheets
        if ($teachers){
            foreach($teachers as $teacher){
                $myxls[$teacher->id] = $workbook->add_worksheet(fullname($teacher));
                /// Print names of all the fields
                $myxls[$teacher->id]->write_string(0,0,get_string('date', 'scheduler'));
                $myxls[$teacher->id]->write_string(0,1,get_string('starttime', 'scheduler'));
                $myxls[$teacher->id]->write_string(0,2,get_string('endtime', 'scheduler'));
                $myxls[$teacher->id]->write_string(0,3,get_string('slottype', 'scheduler'));
                $myxls[$teacher->id]->write_string(0,4,get_string('students', 'scheduler'));
                $myxls[$teacher->id]->set_column(0,0,26);
                $myxls[$teacher->id]->set_column(1,2,15);
                $myxls[$teacher->id]->set_column(3,3,10);
                $myxls[$teacher->id]->set_column(4,4,60);
                $f = $workbook->add_format(array('bold' => 1));
                $myxls[$teacher->id]->set_row(0,13,$f);
            }
        }
    }

    /// Print all the lines of data.
    $i = array();

    if (!empty($slots)) {
        foreach ($slots as $slot) {
            switch($subaction){
                case 'byteacher':
                    $sheetname = $slot->teacherid ;
                    break;
                default :
                    $sheetname = $subaction;
            }

            $appointments = $DB->get_records('scheduler_appointment', array('slotid' => $slot->id));

            /// fill slot data
            $datestart = $output->userdate($slot->starttime);
            $timestart = $output->usertime($slot->starttime);
            $timeend = $output->usertime($slot->starttime + $slot->duration * 60);
            $i[$sheetname] = @$i[$sheetname] + 1;
            $myxls[$sheetname]->write_string($i[$sheetname],0,$datestart);
            $myxls[$sheetname]->write_string($i[$sheetname],1,$timestart);
            $myxls[$sheetname]->write_string($i[$sheetname],2,$timeend);
            switch($slot->exclusivity){
                case 0 :
                    $myxls[$sheetname]->write_string($i[$sheetname], 3, get_string('unlimited', 'scheduler'));
                    break;
                case 1 :
                    $myxls[$sheetname]->write_string($i[$sheetname], 3, get_string('exclusive', 'scheduler'));
                    break;
                default :
                	$remaining = ($slot->exclusivity - count($appointments));
                    $myxls[$sheetname]->write_string($i[$sheetname], 3, get_string('limited', 'scheduler',$remaining));
            }
            $j = 4;
            if ($subaction == 'singlesheet'){
                $myxls[$sheetname]->write_string($i[$sheetname], $j, fullname($teachers[$slot->teacherid]));
                $j++;
            }
            if (!empty($appointments)) {
                $appointedlist = '';
                foreach($appointments as $appointment){
                    $user = $DB->get_record('user', array('id' => $appointment->studentid));
                    $user->lastname = strtoupper($user->lastname);
                    $strattended = ($appointment->attended) ? ' (A) ': '';
                    $appointedlist[] = fullname($user). " $strattended";
                }
                $myxls[$sheetname]->write_string($i[$sheetname], $j, implode(',', $appointedlist));
            }
        }
    }

    /// Close the workbook
    $workbook->close();
    exit;
}
/********************************************* csv generator : get parms ************************************/
if ($action == 'dodownloadcsv'){
    ?>
<center>
<?php
echo $OUTPUT->heading(get_string('csvparms', 'scheduler'));
echo $OUTPUT->box_start()
?>
<form name="csvparms" method="POST" action="view.php" target="_blank">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="what" value="downloadcsv" />
<input type="hidden" name="page" value="<?php p($page) ?>" />
<input type="hidden" name="subaction" value="<?php p($subaction) ?>" />
<table>
    <tr>
        <td align="right" valign="top"><strong><?php print_string('csvrecordseparator','scheduler') ?>:</strong></td>
        <td align="left" valign="top">
            <select name="csvrecordseparator">
                <option value="CR">[CR] (\r) - OLD MAC</option>
                <option value="CRLF" >[CR][LF] (\r\n) - DOS/WINDOWS</option>
                <option value="LF" selected="selected">[LF] (\n) - LINUX/UNIX</option>
            </select>
        </td>
    </tr>
    <tr>
        <td align="right" valign="top"><strong><?php print_string('csvfieldseparator','scheduler') ?>:</strong></td>
        <td align="left" valign="top">
            <select name="csvfieldseparator">
                <option value="TAB">[TAB]</option>
                <option value=";">;</option>
                <option value=",">,</option>
            </select>
        </td>
    </tr>
    <tr>
        <td align="right" valign="top"><strong><?php print_string('csvencoding','scheduler') ?>:</strong></td>
        <td align="left" valign="top">
            <select name="csvencoding">
                <option value="UTF-16">UTF-16</option>
                <option value="UTF-8" selected="selected" >UTF-8</option>
                <option value="UTF-7">UTF-7</option>
                <option value="ASCII">ASCII</option>
                <option value="EUC-JP">EUC-JP</option>
                <option value="SJIS">SJIS</option>
                <option value="eucJP-win">eucJP-win</option>
                <option value="SJIS-win">SJIS-win</option>
                <option value="ISO-2022-JP">ISO-2022-JP</option>
                <option value="JIS">JIS</option>
                <option value="ISO-8859-1">ISO-8859-1</option>
                <option value="ISO-8859-2">ISO-8859-2</option>
                <option value="ISO-8859-3">ISO-8859-3</option>
                <option value="ISO-8859-4">ISO-8859-4</option>
                <option value="ISO-8859-5">ISO-8859-5</option>
                <option value="ISO-8859-6">ISO-8859-6</option>
                <option value="ISO-8859-7">ISO-8859-7</option>
                <option value="ISO-8859-8">ISO-8859-8</option>
                <option value="ISO-8859-9">ISO-8859-9</option>
                <option value="ISO-8859-10">ISO-8859-10</option>
                <option value="ISO-8859-13">ISO-8859-13</option>
                <option value="ISO-8859-14">ISO-8859-14</option>
                <option value="ISO-8859-15">ISO-8859-15</option>
                <option value="BASE64">BASE64</option>
            </select>
        </td>
    </tr>
    <tr>
        <td align="center" valign="top" colspan="2">
            <input type="submit" name="go_btn" value="<?php print_string('continue') ?>" />
        </td>
    </tr>
</table>
</form>
<?php
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
exit;
}
/********************************************* csv generator : generate **********************************/
if ($action == 'downloadcsv'){

    $ENDLINES = array( 'LF' => "\n", 'CRLF' => "\r\n", 'CR' => "\r");
    $csvrecordseparator = $ENDLINES[required_param('csvrecordseparator', PARAM_TEXT)];
    $csvfieldseparator = required_param('csvfieldseparator', PARAM_TEXT);
    if ($csvfieldseparator == 'TAB'){
        $csvfieldseparator = "\t";
    }
    $csvencoding = required_param('csvencoding', PARAM_CLEAN);
    $downloadfilename = clean_filename(shorten_text("{$course->shortname}_{$scheduler->name}", 20).'.csv');
    /// sending headers
    header("Content-Type:application/download\n\n");
    header("Content-Disposition: attachment; filename=\"$downloadfilename\"");

    /// Prepare data
    $sql = "
        SELECT DISTINCT
        ".user_picture::fields('u',array('email','department'))."
        FROM
        {scheduler_slots} s,
        {user} u
        WHERE
        s.teacherid = u.id AND
        schedulerid = ?
        ";
    $teachers = $DB->get_records_sql($sql, array($scheduler->id));
    $stream = '';
    $slots = $DB->get_records('scheduler_slots', array('schedulerid' => $scheduler->id), 'starttime', 'id, starttime, duration, exclusivity, teacherid, hideuntil');
    if ($subaction == 'slots'){
        /// Making title line
        $stream .= get_string('date', 'scheduler') . $csvfieldseparator;
        $stream .= get_string('starttime', 'scheduler') . $csvfieldseparator;
        $stream .= get_string('endtime', 'scheduler') . $csvfieldseparator;
        $stream .= get_string('slottype', 'scheduler') . $csvfieldseparator;
        $stream .= get_string('students', 'scheduler') .$csvrecordseparator;

        /// Print all the lines of data.
        if (!empty($slots)) {
            foreach ($slots as $slot) {
                $appointments = $DB->get_records('scheduler_appointment', array('slotid'=>$slot->id));

                /// fill slot data
                $datestart = $output->userdate($slot->starttime);
                $timestart = $output->usertime($slot->starttime);
                $timeend = $output->usertime($slot->starttime + $slot->duration * 60);
                $stream .= $datestart . $csvfieldseparator;
                $stream .= $timestart . $csvfieldseparator;
                $stream .= $timeend . $csvfieldseparator;
                switch($slot->exclusivity){
                    case 0 :
                        $stream .= get_string('unlimited', 'scheduler') . $csvfieldseparator;
                        break;
                    case 1 :
                        $stream .= get_string('exclusive', 'scheduler') . $csvfieldseparator;
                        break;
                    default :
                        $stream .= get_string('limited', 'scheduler').' '.($slot->exclusivity - count($appointments)) . $csvfieldseparator;
                }
                if (!empty($appointments)) {
                    $appointedlist = '';
                    foreach($appointments as $appointment){
                        $user = $DB->get_record('user', array('id' => $appointment->studentid));
                        $user->lastname = strtoupper($user->lastname);
                        $strattended = ($appointment->attended) ? ' (A) ': '';
                        $appointedlist[] = fullname($user).$strattended;
                    }
                    $stream .= implode(',', $appointedlist);
                }
                $stream .= $csvrecordseparator;
            }
        }
    }
    else if ($subaction == 'grades'){
        $sql = 'SELECT a.id, a.studentid, a.grade, a.appointmentnote,' .user_picture::fields('u').
        		' FROM {user} u, {scheduler_slots} s, {scheduler_appointment} a' .
        		' WHERE u.id = a.studentid AND a.slotid = s.id AND s.schedulerid = ? AND a.attended = 1' .
        		' ORDER BY u.lastname, u.firstname, s.teacherid';
        $grades = $DB->get_records_sql($sql, array($scheduler->id));
        $finals = array();
        foreach($grades as $grade){
        	if (!array_key_exists($grade->studentid, $finals)) {
        	    $finals[$grade->studentid] = new stdClass();
        	}
            if ($scheduler->scale > 0){ // numeric scales
                $finals[$grade->studentid]->sum = @$finals[$grade->studentid]->sum + $grade->grade;
                $finals[$grade->studentid]->count = @$finals[$grade->studentid]->count + 1;
                $finals[$grade->studentid]->max = (@$finals[$grade->studentid]->max < $grade->grade) ? $grade->grade : @$finals[$studentid]->max ;
            }
            else if ($scheduler->scale < 0){ // non numeric scales
                $scaleid = - ($scheduler->scale);
                if ($scale = $DB->get_record('scale', 'id', $scaleid)) {
                    $scalegrades = make_menu_from_list($scale->scale);
                    foreach ($grades as $aGrade) {
                        $finals[$aGrade->studentid]->sum = @$finals[$aGrade->studentid]->sum + $scalegrades[$aGgrade->grade];
                        $finals[$aGrade->studentid]->count = @$finals[$aGrade->studentid]->count + 1;
                        $finals[$aGrade->studentid]->max = (@$finals[$aGrade->studentid]->max < $aGrade) ? $scalegrades[$aGgrade->grade] : @$finals[$aGrade->studentid]->max ;
                    }
                }
            }
            $finals[$grade->studentid]->lastname = $grade->lastname;
            $finals[$grade->studentid]->firstname = $grade->firstname;
            $finals[$grade->studentid]->middlename = $grade->middlename;
            $finals[$grade->studentid]->lastnamephonetic = $grade->lastnamephonetic;
            $finals[$grade->studentid]->firstnamephonetic = $grade->firstnamephonetic;
            $finals[$grade->studentid]->alternatename = $grade->alternatename;

            $separator = isset($finals[$grade->studentid]->appointmentnote) ? ' | ' : '';
            $finals[$grade->studentid]->appointmentnote = @$finals[$grade->studentid]->appointmentnote.$separator.$grade->appointmentnote;
        }
        /// Making title line
        $stream .= get_string('student', 'scheduler') . $csvfieldseparator;
        $stream .= get_string('grades') . $csvfieldseparator;
        $stream .= get_string('finalgrade', 'scheduler') . $csvfieldseparator;
        $stream .= get_string('notes', 'scheduler') . $csvrecordseparator;

        if ($finals){
            foreach($finals as $studentid => $final){
                $stream .= fullname($final) . $csvfieldseparator;
                $stream .= $final->count . $csvfieldseparator;
                if ($scheduler->gradingstrategy == MEAN_GRADE){
                    $stream .= $final->sum / $final->count . $csvfieldseparator;
                }
                else{
                    $stream .= $final->max . $csvfieldseparator;
                }
                $stream .= strtr($final->appointmentnote, "\r\n", "  ") . $csvrecordseparator;
            }
        }
    }

    echo mb_convert_encoding($stream, $csvencoding, 'UTF-8');
    exit;
}

/*********************************************** download selection **********************************/
else {


    // Print top tabs.

    $taburl = new moodle_url('/mod/scheduler/view.php', array('id' => $scheduler->cmid, 'what' => 'downloads'));
    echo $output->teacherview_tabs($scheduler, $taburl, 'downloads');

    $strdownloadexcelsingle = get_string('strdownloadexcelsingle', 'scheduler');
    $strdownloadexcelteachers = get_string('strdownloadexcelteachers', 'scheduler', format_string($scheduler->get_teacher_name()));
    $strdownloadodssingle = get_string('strdownloadodssingle', 'scheduler');
    $strdownloadodsteachers = get_string('strdownloadodsteachers', 'scheduler', format_string($scheduler->get_teacher_name()));
    $strdownloadcsvslots = get_string('strdownloadcsvslots', 'scheduler');
    $strdownloadcsvgrades = get_string('strdownloadcsvgrades', 'scheduler');



    ?>
<center>
<?php echo $OUTPUT->heading(get_string('downloads', 'scheduler')) ?>
<hr width="60%" class="separator"/>
<table>
    <tr>
        <td>
            <form action="view.php" method="post" name="deleteallform" target="_blank">
                <input type="hidden" name="what" value="downloadexcel" />
                <input type="hidden" name="subaction" value="singlesheet" />
                <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
                <input type="submit" name="go_btn" value="<?php echo $strdownloadexcelsingle ?>" style="width:240px"/>
            </form>
        </td>
    </tr>
    <tr>
        <td>
            <form action="view.php" method="post" name="deleteallform" target="_blank">
                <input type="hidden" name="what" value="downloadexcel" />
                <input type="hidden" name="subaction" value="byteacher" />
                <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
                <input type="submit" name="go_btn" value="<?php echo $strdownloadexcelteachers ?>" style="width:240px"/>
            </form>
        </td>
    </tr>
    <tr>
        <td>
            <form action="view.php" method="post" name="deleteallform" target="_blank">
                <input type="hidden" name="what" value="downloadods" />
                <input type="hidden" name="subaction" value="singlesheet" />
                <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
                <input type="submit" name="go_btn" value="<?php echo $strdownloadodssingle ?>" style="width:240px"/>
            </form>
        </td>
    </tr>
    <tr>
        <td>
            <form action="view.php" method="post" name="deleteallform" target="_blank">
                <input type="hidden" name="what" value="downloadods" />
                <input type="hidden" name="subaction" value="byteacher" />
                <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
                <input type="submit" name="go_btn" value="<?php echo $strdownloadodsteachers ?>" style="width:240px"/>
            </form>
        </td>
    </tr>
    <tr>
        <td>
            <form action="view.php" method="post" name="deleteallform">
                <input type="hidden" name="what" value="dodownloadcsv" />
                <input type="hidden" name="subaction" value="slots" />
                <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
                <input type="submit" name="go_btn" value="<?php echo $strdownloadcsvslots ?>" style="width:240px"/>
            </form>
        </td>
    </tr>
<?php
if ($scheduler->scale != 0){
    ?>
    <tr>
        <td>
            <form action="view.php" method="post" name="deleteallform">
                <input type="hidden" name="what" value="dodownloadcsv" />
                <input type="hidden" name="subaction" value="grades" />
                <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
                <input type="submit" name="go_btn" value="<?php echo $strdownloadcsvgrades ?>" style="width:240px"/>
            </form>
        </td>
    </tr>
    <tr>
        <td>
            <br/><?php print_string('exportinstructions','scheduler') ?>
        </td>
    </tr>
<?php
}
?>
</table>
</center>
<?php
}
?>
