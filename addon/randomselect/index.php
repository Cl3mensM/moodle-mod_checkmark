<?php
// This file is part of mod_checkmark for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Random selection page for Checkmark presentation selection.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../config.php');

// We check the course module context in detail after resolving the checkmark instance.
require_login();

require_once($CFG->dirroot . '/mod/checkmark/locallib.php');

$id = optional_param('id', 0, PARAM_INT);
$c = optional_param('c', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$url = new moodle_url('/mod/checkmark/addon/randomselect/index.php');
if ($returnurl !== '') {
    $url->param('returnurl', $returnurl);
}
[$cm, $checkmark, $course] = \checkmark::init_checks($id, $c, $url);
$context = context_module::instance($cm->id);

\checkmark_randomselect\access::require_can_use($context);

$PAGE->set_title(format_string($checkmark->name, true));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('randomselectionforpresentation', 'checkmark_randomselect'));
$PAGE->activityheader->disable();
$PAGE->add_body_class('limitedwidth');
$PAGE->requires->js_call_amd('checkmark_randomselect/randomselect_layout', 'init', ['#checkmark-randomselect-page']);

if ($returnurl !== '') {
    $backurl = new moodle_url($returnurl);
} else {
    $backurl = new moodle_url('/mod/checkmark/submissions.php', ['id' => $cm->id]);
}

echo $OUTPUT->header();
echo html_writer::start_div('header-maxwidth');
echo $OUTPUT->heading(get_string('randomselectionforpresentation', 'checkmark_randomselect'));
echo html_writer::link($backurl, get_string('back'), [
    'class' => 'btn btn-secondary mb-3',
    'id' => 'randomselect-back',
]);
echo html_writer::end_div();
$renderer = $PAGE->get_renderer('core');
$page = new \checkmark_randomselect\output\page($backurl);
echo $OUTPUT->render_from_template('checkmark_randomselect/page', $page->export_for_template($renderer));
echo $OUTPUT->footer();
