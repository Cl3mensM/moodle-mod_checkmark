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

\checkmark_randomselect\access::require_can_use($context, $checkmark);

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

$renderer = $PAGE->get_renderer('core');
$defaultfilter = \checkmark_randomselect\filter::default_for_course((int) $course->id);
$defaultapplication = \checkmark_randomselect\application::default_for_checkmark($checkmark);
$formdata = \checkmark_randomselect\formdata::from_request($defaultfilter, $defaultapplication);
$filter = new \checkmark_randomselect\filter(
    (int) $course->id,
    $formdata->includeexistingpresentations,
    $formdata->checkmarkselection,
    $formdata->selectedcheckmarkids
);
$application = new \checkmark_randomselect\application(
    $defaultapplication->get_examples(),
    $formdata->exampleselection,
    $formdata->selectedexampleids,
    $formdata->examplesperstudent
);
$preview = null;

if ($formdata->action !== '') {
    require_sesskey();

    $selector = new \checkmark_randomselect\selector(
        $cm,
        $checkmark,
        $context,
        $formdata->selectedcheckmarkids,
        $formdata->includeexistingpresentations,
        $formdata->selectedexampleids,
        $formdata->examplesperstudent
    );

    if ($formdata->action === \checkmark_randomselect\formdata::ACTION_PREVIEW) {
        $preview = $selector->create_preview();
    } else if ($formdata->action === \checkmark_randomselect\formdata::ACTION_APPLY) {
        if (\checkmark_randomselect\preview::decode_fingerprint($formdata->previewdata) === $formdata->get_fingerprint()) {
            $preview = $selector->preview_from_assignments(
                \checkmark_randomselect\preview::decode_assignments($formdata->previewdata)
            );
        }

        if ($preview === null || !$preview->has_assignments()) {
            \core\notification::warning(get_string('invalidpreview', 'checkmark_randomselect'));
        } else {
            $checkmarkinstance = new \checkmark($cm->id, $checkmark, $cm, $course);
            $result = (new \checkmark_randomselect\applier($checkmarkinstance))->apply($preview);
            $submissionsurl = new moodle_url('/mod/checkmark/submissions.php', [
                'id' => $cm->id,
                'updatepref' => 1,
                'filter' => \checkmark::FILTER_PRESENTATION_MARKED,
                'quickgrade' => get_user_preferences('checkmark_quickgrade', 0),
                'perpage' => get_user_preferences('checkmark_perpage', 10),
            ]);
            redirect(
                $submissionsurl,
                get_string('assignmentsuccess', 'checkmark_randomselect', (object) $result),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }
    }
}

echo $OUTPUT->header();
echo html_writer::start_div('header-maxwidth');
echo $OUTPUT->heading(get_string('randomselectionforpresentation', 'checkmark_randomselect'));
echo html_writer::link($backurl, get_string('back'), [
    'class' => 'btn btn-secondary mb-3',
    'id' => 'randomselect-back',
]);
echo html_writer::end_div();
$page = new \checkmark_randomselect\output\page(
    $backurl,
    $PAGE->url,
    $filter,
    $application,
    $preview,
    (int) $course->id,
    $formdata->get_fingerprint()
);
echo $OUTPUT->render_from_template('checkmark_randomselect/page', $page->export_for_template($renderer));
echo $OUTPUT->footer();
