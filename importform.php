<?php

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/uploadlib.php');
require_once($CFG->libdir.'/csvlib.class.php');

defined('MOODLE_INTERNAL') || die;

class scheduler_import_form extends moodleform {

    function definition() {
        global $CFG;

        $mform    =& $this->_form;

        $mform->addElement('header', 'csvheader', 'CSV data');
        $mform->addElement('filepicker', 'attachment', "CSV data file");
        $mform->addRule('attachment', null, 'required');

		if (isset($this->_customdata['columns'])) {
			$collist = implode(', ', $this->_customdata['columns']);
			$msg = "$collist";
        	$mform->addElement('static', 'columns', 'Expected columns in file', $msg);
		}

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');


        //--------------------------------------------------------------------------------

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_INT);
        $this->add_action_buttons(true, "Upload");

    }

    /// perform some extra moodle validation
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

    function get_csv_reader($expected_cols = null, $returnurl, $required=true) {
        global $csv_expected_cols;

        $data = $this->get_data();

        $cols = false;
        if ($expected_cols) {
        	$cols = $expected_cols;
        }
        else if (array_key_exists('columns', $this->_customdata)) {
        	$cols = $this->_customdata['columns'];
        }

        $content = $this->get_file_content('attachment');

        $iid = csv_import_reader::get_new_iid('uploadform');
        $cir = new csv_import_reader($iid, 'uploadform');

        $readcount = $cir->load_csv_content($content, $data->encoding, $data->delimiter_name);

        if ($readcount === false) {
            if ($required) {
                print_error($cir->get_error(), 'error', $returnurl);
            } else {
                return false;
            }
        }

        if ($cols) {
        	$this->validate_upload_columns($cir, $cols, $returnurl);
        }

        return $cir;

    }


    protected function validate_upload_columns(csv_import_reader $cir, $expectedfields, $returnurl) {
        $columns = $cir->get_columns();

        if (empty($columns)) {
            $cir->close();
            $cir->cleanup();
            print_error('cannotreadtmpfile', 'error', $returnurl);
        }
        if (count($columns) < count($expectedfields)) {
            $cir->close();
            $cir->cleanup();
            print_error('csvfewcolumns', 'error', $returnurl);
        }

        // test columns
        $cnt = 0;
        $processed = array();
        foreach ($columns as $key=>$unused) {
            $field = $columns[$key];
            $field = str_replace('"', '', $field);
            $field = core_text::strtolower($field);
            $expected = $expectedfields[$cnt];
            if ($field == $expected) {
                $newfield = $field;
            } else {
                $cir->close();
                $cir->cleanup();
                print_error('invalidfieldname', 'error', $returnurl, $field);
            }
            if (in_array($newfield, $processed)) {
                $cir->close();
                $cir->cleanup();
                print_error('duplicatefieldname', 'error', $returnurl, $newfield);
            }
            $processed[$key] = $newfield;
            $cnt++;
        }
        return $processed;
    }
}


function clean_csv_data($str) {

    $result = str_replace('"','',$str);
    $result = trim($result);
    return $result;

}
