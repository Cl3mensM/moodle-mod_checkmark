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
 * Tests for Checkmark add-on subplugin support.
 *
 * @package   mod_checkmark
 * @category  test
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_checkmark;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/checkmark/adminlib.php');
require_once($CFG->dirroot . '/mod/checkmark/locallib.php');

/**
 * Tests for Checkmark add-on subplugin support.
 *
 * @package   mod_checkmark
 * @category  test
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_checkmark\plugininfo\checkmark::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_plugin_manager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_randomselect\access::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_randomselect\application::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_randomselect\applier::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_randomselect\filter::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_randomselect\formdata::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_randomselect\output\page::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_randomselect\preview::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_randomselect\selector::class)]
final class checkmark_subplugin_test extends \advanced_testcase {
    /**
     * Checkmark declares the add-on subplugin type.
     */
    public function test_checkmark_type_is_registered(): void {
        $plugintypes = \core_component::get_plugin_types();
        $subplugins = \core_component::get_subplugins('mod_checkmark');

        $this->assertArrayHasKey('checkmark', $plugintypes);
        $this->assertStringEndsWith('/mod/checkmark/addon', $plugintypes['checkmark']);
        $this->assertArrayHasKey('checkmark', $subplugins);
        $this->assertIsArray($subplugins['checkmark']);
    }

    /**
     * Checkmark includes the random selection add-on scaffold.
     */
    public function test_randomselect_addon_scaffold_is_discoverable(): void {
        $plugins = \core_component::get_plugin_list('checkmark');

        $this->assertArrayHasKey('randomselect', $plugins);
        $this->assertFileExists($plugins['randomselect'] . '/version.php');
        $this->assertFileExists($plugins['randomselect'] . '/lang/en/checkmark_randomselect.php');
        $this->assertFileExists($plugins['randomselect'] . '/settings.php');
        $this->assertFileExists($plugins['randomselect'] . '/db/access.php');
        $this->assertFileExists($plugins['randomselect'] . '/index.php');
        $this->assertFileExists($plugins['randomselect'] . '/classes/application.php');
        $this->assertFileExists($plugins['randomselect'] . '/classes/applier.php');
        $this->assertFileExists($plugins['randomselect'] . '/classes/filter.php');
        $this->assertFileExists($plugins['randomselect'] . '/classes/formdata.php');
        $this->assertFileExists($plugins['randomselect'] . '/classes/preview.php');
        $this->assertFileExists($plugins['randomselect'] . '/classes/selector.php');
        $this->assertFileExists($plugins['randomselect'] . '/templates/page.mustache');
        $this->assertFileExists($plugins['randomselect'] . '/templates/filtercriteria.mustache');
        $this->assertFileExists($plugins['randomselect'] . '/templates/fieldofapplication.mustache');
        $this->assertFileExists($plugins['randomselect'] . '/templates/preview.mustache');
        $this->assertFileExists($plugins['randomselect'] . '/amd/src/randomselect_layout.js');
        $this->assertFileExists($plugins['randomselect'] . '/amd/build/randomselect_layout.min.js');
        $this->assertFileExists($plugins['randomselect'] . '/amd/build/randomselect_layout.min.js.map');
    }

    /**
     * Random selection registers a default setting for existing presentations.
     */
    public function test_randomselect_include_existing_presentations_setting_is_registered(): void {
        global $CFG;

        $this->resetAfterTest(true);

        require_once($CFG->libdir . '/adminlib.php');

        $plugins = \core_component::get_plugin_list('checkmark');
        $settings = new \admin_settingpage('checkmark_randomselect', 'Random selection');
        include($plugins['randomselect'] . '/settings.php');

        $registeredsettings = get_object_vars($settings->settings);
        $this->assertCount(1, $registeredsettings);

        $setting = reset($registeredsettings);
        $this->assertInstanceOf(\admin_setting_configselect::class, $setting);
        $this->assertSame('checkmark_randomselect', $setting->plugin);
        $this->assertSame(\checkmark_randomselect\settings::INCLUDE_EXISTING_PRESENTATIONS, $setting->name);
        $this->assertSame(0, $setting->get_defaultsetting());
        $this->assertSame([0 => get_string('no'), 1 => get_string('yes')], $setting->choices);
    }

    /**
     * Random selection reads the default setting live from plugin config.
     */
    public function test_randomselect_include_existing_presentations_default_is_read_live(): void {
        $this->resetAfterTest(true);

        unset_config(
            \checkmark_randomselect\settings::INCLUDE_EXISTING_PRESENTATIONS,
            'checkmark_randomselect'
        );
        $this->assertFalse(\checkmark_randomselect\settings::include_existing_presentations_by_default());

        set_config(\checkmark_randomselect\settings::INCLUDE_EXISTING_PRESENTATIONS, 1, 'checkmark_randomselect');
        $this->assertTrue(\checkmark_randomselect\settings::include_existing_presentations_by_default());

        set_config(\checkmark_randomselect\settings::INCLUDE_EXISTING_PRESENTATIONS, 0, 'checkmark_randomselect');
        $this->assertFalse(\checkmark_randomselect\settings::include_existing_presentations_by_default());
    }

    /**
     * Random selection declares the use capability for teachers.
     */
    public function test_randomselect_use_capability_is_declared(): void {
        $plugins = \core_component::get_plugin_list('checkmark');
        $capabilities = [];

        include($plugins['randomselect'] . '/db/access.php');

        $this->assertArrayHasKey(\checkmark_randomselect\access::CAPABILITY, $capabilities);
        $capability = $capabilities[\checkmark_randomselect\access::CAPABILITY];
        $this->assertSame(RISK_XSS, $capability['riskbitmask']);
        $this->assertSame('write', $capability['captype']);
        $this->assertSame(CONTEXT_MODULE, $capability['contextlevel']);
        $this->assertSame(CAP_ALLOW, $capability['archetypes']['teacher']);
        $this->assertSame(CAP_ALLOW, $capability['archetypes']['editingteacher']);
        $this->assertSame(CAP_ALLOW, $capability['archetypes']['manager']);
    }

    /**
     * Random selection access depends on add-on state, presentation grading, and the use capability.
     */
    public function test_randomselect_access_checks_enabled_state_and_capability(): void {
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $student = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'teacher');
        $generator->enrol_user($student->id, $course->id, 'student');
        $checkmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
        ]);
        $context = \context_module::instance($checkmark->cmid);

        set_config('disabled', 0, 'checkmark_randomselect');

        $this->setUser($teacher);
        $this->assertTrue(\checkmark_randomselect\access::can_use($context, $checkmark));

        $checkmarkwithoutpresentation = clone $checkmark;
        $checkmarkwithoutpresentation->presentationgrading = 0;
        $this->assertFalse(\checkmark_randomselect\access::can_use($context, $checkmarkwithoutpresentation));

        $this->setUser($student);
        $this->assertFalse(\checkmark_randomselect\access::can_use($context, $checkmark));

        set_config('disabled', 1, 'checkmark_randomselect');

        $this->setUser($teacher);
        $this->assertFalse(\checkmark_randomselect\access::can_use($context, $checkmark));
    }

    /**
     * Random selection start button links to the add-on page and preserves the return URL.
     */
    public function test_randomselect_start_button_links_to_addon_page(): void {
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'teacher');
        $checkmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
        ]);
        $context = \context_module::instance($checkmark->cmid);

        set_config('disabled', 0, 'checkmark_randomselect');
        $this->setUser($teacher);

        $button = \checkmark_randomselect\access::render_start_button(
            $context,
            $checkmark,
            $checkmark->cmid,
            new \moodle_url('/mod/checkmark/view.php', ['id' => $checkmark->cmid])
        );

        $this->assertStringContainsString(
            get_string('startpresentationrandomselection', 'checkmark_randomselect'),
            $button
        );
        $this->assertStringContainsString('/mod/checkmark/addon/randomselect/index.php', $button);
        $this->assertStringContainsString('returnurl=', $button);

        set_config('disabled', 1, 'checkmark_randomselect');
        $this->assertSame(
            '',
            \checkmark_randomselect\access::render_start_button(
                $context,
                $checkmark,
                $checkmark->cmid,
                new \moodle_url('/mod/checkmark/view.php', ['id' => $checkmark->cmid])
            )
        );

        set_config('disabled', 0, 'checkmark_randomselect');
        $checkmark->presentationgrading = 0;
        $this->assertSame(
            '',
            \checkmark_randomselect\access::render_start_button(
                $context,
                $checkmark,
                $checkmark->cmid,
                new \moodle_url('/mod/checkmark/view.php', ['id' => $checkmark->cmid])
            )
        );
    }

    /**
     * Random selection is linked from the Checkmark activity start page for users with access.
     */
    public function test_randomselect_start_button_is_added_to_checkmark_button_group(): void {
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'teacher');
        $checkmarkrecord = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
        ]);
        $checkmark = new \checkmark($checkmarkrecord->cmid);

        set_config('disabled', 0, 'checkmark_randomselect');
        $this->setUser($teacher);

        $buttons = $checkmark->buttongroup((object)['submissionssubmittedcount' => 0]);

        $this->assertStringContainsString(
            get_string('startpresentationrandomselection', 'checkmark_randomselect'),
            $buttons
        );
        $this->assertStringContainsString('/mod/checkmark/addon/randomselect/index.php', $buttons);
        $this->assertLessThan(
            strpos($buttons, get_string('gradebutton', 'checkmark')),
            strpos($buttons, get_string('startpresentationrandomselection', 'checkmark_randomselect'))
        );

        set_config('disabled', 1, 'checkmark_randomselect');
        $this->assertStringNotContainsString(
            get_string('startpresentationrandomselection', 'checkmark_randomselect'),
            $checkmark->buttongroup((object)['submissionssubmittedcount' => 0])
        );

        $checkmarkwithoutpresentation = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 0,
        ]);
        $checkmark = new \checkmark($checkmarkwithoutpresentation->cmid);

        set_config('disabled', 0, 'checkmark_randomselect');
        $this->assertStringNotContainsString(
            get_string('startpresentationrandomselection', 'checkmark_randomselect'),
            $checkmark->buttongroup((object)['submissionssubmittedcount' => 0])
        );
    }

    /**
     * Random selection filter criteria export visible course Checkmark activities.
     */
    public function test_randomselect_filter_criteria_exports_visible_course_checkmarks(): void {
        global $PAGE;

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $othercourse = $generator->create_course();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');

        $zulu = $generator->create_module('checkmark', [
            'course' => $course->id,
            'name' => 'Zulu Checkmark',
            'presentationgrading' => 1,
            'presentationgrade' => 100,
        ]);
        $alpha = $generator->create_module('checkmark', [
            'course' => $course->id,
            'name' => 'Alpha Checkmark',
            'presentationgrading' => 1,
            'presentationgrade' => 100,
        ]);
        $generator->create_module('checkmark', [
            'course' => $course->id,
            'name' => 'No Presentation Checkmark',
            'presentationgrading' => 0,
        ]);
        $generator->create_module('checkmark', [
            'course' => $course->id,
            'name' => 'Hidden Checkmark',
            'visible' => 0,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
        ]);
        $generator->create_module('checkmark', [
            'course' => $othercourse->id,
            'name' => 'Other Course Checkmark',
            'presentationgrading' => 1,
            'presentationgrade' => 100,
        ]);

        set_config(
            \checkmark_randomselect\settings::INCLUDE_EXISTING_PRESENTATIONS,
            1,
            'checkmark_randomselect'
        );
        $this->setUser($teacher);

        $filter = \checkmark_randomselect\filter::default_for_course((int) $course->id, (int) $zulu->id);
        $options = $filter->get_checkmark_options();

        $this->assertSame(['Alpha Checkmark', 'Zulu Checkmark'], array_column($options, 'name'));
        $this->assertSame([(int) $alpha->id, (int) $zulu->id], array_column($options, 'id'));
        $this->assertNotContains('No Presentation Checkmark', array_column($options, 'name'));
        $this->assertSame([false, true], array_column($options, 'iscurrent'));
        $this->assertTrue($options[0]['checked']);
        $this->assertTrue($options[1]['checked']);

        $renderer = $PAGE->get_renderer('core');
        $data = $filter->export_for_template($renderer);

        $this->assertSame(get_string('coursecheckmarks', 'checkmark_randomselect'), $data['coursecheckmarkslabel']);
        $this->assertSame(get_string('currentactivity', 'checkmark_randomselect'), $data['currentactivitylabel']);
        $this->assertNotEmpty($data['coursecheckmarkshelpicon']);
        $this->assertSame(\checkmark_randomselect\filter::CHECKMARK_SELECTION_ALL, $data['checkmarkselectionall']);
        $this->assertSame(
            \checkmark_randomselect\filter::CHECKMARK_SELECTION_SELECTED,
            $data['checkmarkselectionselected']
        );
        $this->assertSame(
            get_string('includeexistingpresentations', 'checkmark_randomselect'),
            $data['includeexistingpresentationslabel']
        );
        $this->assertNotEmpty($data['includeexistingpresentationshelpicon']);
        $this->assertSame(1, $data['includeexistingpresentationsoptions'][0]['value']);
        $this->assertTrue($data['includeexistingpresentationsoptions'][0]['selected']);
        $this->assertSame(0, $data['includeexistingpresentationsoptions'][1]['value']);
        $this->assertFalse($data['includeexistingpresentationsoptions'][1]['selected']);

        $html = $renderer->render_from_template('checkmark_randomselect/filtercriteria', $data);
        $this->assertSame(1, substr_count($html, get_string('currentactivity', 'checkmark_randomselect')));
        $this->assertStringContainsString('badge rounded-pill text-bg-secondary', $html);
        $this->assertStringContainsString('<strong>Zulu Checkmark</strong>', $html);
        $this->assertStringNotContainsString('<strong>Alpha Checkmark</strong>', $html);
    }

    /**
     * Random selection field of application exports current Checkmark examples.
     */
    public function test_randomselect_field_of_application_exports_current_checkmark_examples(): void {
        global $PAGE;

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $checkmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'examplecount' => 4,
            'examplestart' => 6,
            'grade' => 80,
        ]);

        $application = \checkmark_randomselect\application::default_for_checkmark($checkmark);
        $options = $application->get_example_options();

        $this->assertSame(
            [
                'Example 6 (20 Points)',
                'Example 7 (20 Points)',
                'Example 8 (20 Points)',
                'Example 9 (20 Points)',
            ],
            array_column($options, 'label')
        );
        $this->assertSame(
            ['Example 6', 'Example 7', 'Example 8', 'Example 9'],
            array_column($options, 'name')
        );
        $this->assertSame([true, true, true, true], array_column($options, 'checked'));

        $data = $application->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame(
            get_string('examplesfromcurrentcheckmark', 'checkmark_randomselect'),
            $data['exampleslabel']
        );
        $this->assertNotEmpty($data['exampleshelpicon']);
        $this->assertSame(\checkmark_randomselect\application::EXAMPLE_SELECTION_ALL, $data['exampleselectionall']);
        $this->assertSame(
            \checkmark_randomselect\application::EXAMPLE_SELECTION_SELECTED,
            $data['exampleselectionselected']
        );
        $this->assertSame(
            get_string('examplesperstudent', 'checkmark_randomselect'),
            $data['examplesperstudentlabel']
        );
        $this->assertNotEmpty($data['examplesperstudenthelpicon']);
        $this->assertSame([1, 2, 3, 4], array_column($data['examplesperstudentoptions'], 'value'));
        $this->assertTrue($data['examplesperstudentoptions'][0]['selected']);
        $this->assertFalse($data['examplesperstudentoptions'][1]['selected']);
    }

    /**
     * Random selection page exports the ticket #8752 layout data.
     */
    public function test_randomselect_page_layout_is_exported(): void {
        global $PAGE;

        $page = new \checkmark_randomselect\output\page(
            new \moodle_url('/mod/checkmark/view.php', ['id' => 23]),
            new \moodle_url('/mod/checkmark/addon/randomselect/index.php', ['id' => 23]),
            new \checkmark_randomselect\filter(SITEID, false),
            new \checkmark_randomselect\application([]),
            null,
            SITEID,
            ''
        );

        $data = $page->export_for_template($PAGE->get_renderer('core'));

        $this->assertStringContainsString(
            'Here, you can randomly select participants for a presentation.',
            $data['pageintro']
        );
        $this->assertCount(2, $data['sections']);
        $this->assertSame('checkmark-randomselect-filtercriteria', $data['sections'][0]['id']);
        $this->assertSame(get_string('filtercriteria', 'checkmark_randomselect'), $data['sections'][0]['title']);
        $this->assertNotEmpty($data['sections'][0]['helpicon']);
        $this->assertSame(
            get_string('coursecheckmarks', 'checkmark_randomselect'),
            $data['sections'][0]['filtercriteria']['coursecheckmarkslabel']
        );
        $this->assertSame('checkmark-randomselect-fieldofapplication', $data['sections'][1]['id']);
        $this->assertSame(get_string('fieldofapplication', 'checkmark_randomselect'), $data['sections'][1]['title']);
        $this->assertNotEmpty($data['sections'][1]['helpicon']);
        $this->assertSame(
            get_string('examplesfromcurrentcheckmark', 'checkmark_randomselect'),
            $data['sections'][1]['fieldofapplication']['exampleslabel']
        );
        $this->assertSame(get_string('preview', 'checkmark_randomselect'), $data['previewtitle']);
        $this->assertNotEmpty($data['previewhelpicon']);
        $this->assertFalse($data['preview']['haspreview']);
        $this->assertSame(get_string('nopreviewtitle', 'checkmark_randomselect'), $data['preview']['nopreviewtitle']);
        $this->assertSame(
            get_string('applyrandomselection', 'checkmark_randomselect'),
            $data['applybuttonlabel']
        );
        $this->assertTrue($data['applydisabled']);
        $this->assertSame(
            get_string('createnewpreview', 'checkmark_randomselect'),
            $data['createpreviewbuttonlabel']
        );
        $this->assertSame(get_string('cancel'), $data['cancelbuttonlabel']);
        $this->assertStringContainsString('/mod/checkmark/view.php?id=23', $data['cancelurl']);
    }

    /**
     * Random selection only assigns examples to students who checked them.
     */
    public function test_randomselect_preview_uses_checked_examples_and_excludes_existing_presentations(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $plugingenerator = $generator->get_plugin_generator('mod_checkmark');
        $course = $generator->create_course();
        $students = [
            'existing' => $generator->create_user(),
            'eligible' => $generator->create_user(),
            'teacherforced' => $generator->create_user(),
            'teacherunchecked' => $generator->create_user(),
            'unchecked' => $generator->create_user(),
        ];

        foreach ($students as $student) {
            $generator->enrol_user($student->id, $course->id, 'student');
        }

        $checkmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'examplecount' => 2,
            'grade' => 20,
        ]);
        $exampleids = $this->get_example_ids($checkmark);

        $plugingenerator->create_submission([
            'checkmark' => $checkmark->id,
            'userid' => $students['existing']->id,
            'example1' => 1,
        ]);
        $plugingenerator->create_submission([
            'checkmark' => $checkmark->id,
            'userid' => $students['eligible']->id,
            'example2' => 1,
        ]);
        $plugingenerator->create_submission([
            'checkmark' => $checkmark->id,
            'userid' => $students['teacherforced']->id,
        ]);
        $this->set_example_state(
            (int) $checkmark->id,
            (int) $students['teacherforced']->id,
            $exampleids[0],
            \mod_checkmark\example::UNCHECKED_OVERWRITTEN
        );
        $plugingenerator->create_submission([
            'checkmark' => $checkmark->id,
            'userid' => $students['teacherunchecked']->id,
            'example1' => 1,
        ]);
        $this->set_example_state(
            (int) $checkmark->id,
            (int) $students['teacherunchecked']->id,
            $exampleids[0],
            \mod_checkmark\example::CHECKED_OVERWRITTEN
        );
        $plugingenerator->create_submission([
            'checkmark' => $checkmark->id,
            'userid' => $students['unchecked']->id,
        ]);
        $plugingenerator->create_feedback([
            'checkmark' => $checkmark->id,
            'userid' => $students['existing']->id,
            'presentationstatus' => CHECKMARK_PRESENTATION_STATUS_YES,
        ]);

        $selector = new \checkmark_randomselect\selector(
            get_coursemodule_from_id('checkmark', $checkmark->cmid),
            $checkmark,
            \context_module::instance($checkmark->cmid),
            [$checkmark->id],
            false,
            $exampleids,
            1,
            static function (array &$items): void {
                // Keep test output deterministic.
            }
        );

        $preview = $selector->create_preview();
        $assignments = $preview->get_assignments();
        $assignedusersbyexample = [];
        foreach ($assignments as $assignment) {
            $assignedusersbyexample[$assignment['exampleid']] = $assignment['userid'];
        }

        $this->assertCount(2, $assignments);
        $this->assertSame((int) $students['teacherforced']->id, $assignedusersbyexample[$exampleids[0]]);
        $this->assertSame((int) $students['eligible']->id, $assignedusersbyexample[$exampleids[1]]);
        $this->assertNotContains((int) $students['existing']->id, $assignedusersbyexample);
        $this->assertNotContains((int) $students['teacherunchecked']->id, $assignedusersbyexample);
        $this->assertNotContains((int) $students['unchecked']->id, $assignedusersbyexample);

        $data = $preview->export_for_template((int) $course->id);
        $this->assertFalse($data['hasunassigned']);
        $this->assertFalse($data['hascapacitywarning']);
    }

    /**
     * Random selection does not correlate same-named examples between activities.
     */
    public function test_randomselect_preview_uses_only_current_activity_checks(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $plugingenerator = $generator->get_plugin_generator('mod_checkmark');
        $course = $generator->create_course();
        $currentchecker = $generator->create_user();
        $otherchecker = $generator->create_user();
        $generator->enrol_user($currentchecker->id, $course->id, 'student');
        $generator->enrol_user($otherchecker->id, $course->id, 'student');

        $currentcheckmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'examplecount' => 2,
            'grade' => 20,
        ]);
        $othercheckmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'examplecount' => 2,
            'grade' => 20,
        ]);
        $exampleids = $this->get_example_ids($currentcheckmark);

        $plugingenerator->create_submission([
            'checkmark' => $currentcheckmark->id,
            'userid' => $currentchecker->id,
            'example1' => 1,
        ]);
        $plugingenerator->create_submission([
            'checkmark' => $othercheckmark->id,
            'userid' => $otherchecker->id,
            'example2' => 1,
        ]);

        $selector = new \checkmark_randomselect\selector(
            get_coursemodule_from_id('checkmark', $currentcheckmark->cmid),
            $currentcheckmark,
            \context_module::instance($currentcheckmark->cmid),
            [$currentcheckmark->id, $othercheckmark->id],
            true,
            $exampleids,
            1,
            static function (array &$items): void {
                // Keep test output deterministic.
            }
        );

        $preview = $selector->create_preview();
        $assignments = $preview->get_assignments();
        $data = $preview->export_for_template((int) $course->id);

        $this->assertCount(1, $assignments);
        $this->assertSame($exampleids[0], $assignments[0]['exampleid']);
        $this->assertSame((int) $currentchecker->id, $assignments[0]['userid']);
        $this->assertTrue($data['hasunassigned']);
        $this->assertSame($exampleids[1], $data['unassignedexamples'][0]['exampleid']);

        $this->assertNull($selector->preview_from_assignments([[
            'exampleid' => $exampleids[1],
            'userid' => $otherchecker->id,
        ]]));
    }

    /**
     * Presentation history only uses the activities selected for that purpose.
     */
    public function test_randomselect_preview_scopes_presentation_history_to_selected_activities(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $plugingenerator = $generator->get_plugin_generator('mod_checkmark');
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');

        $currentcheckmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'examplecount' => 1,
            'grade' => 10,
        ]);
        $historycheckmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'examplecount' => 1,
            'grade' => 10,
        ]);
        $exampleids = $this->get_example_ids($currentcheckmark);

        $plugingenerator->create_submission([
            'checkmark' => $currentcheckmark->id,
            'userid' => $student->id,
            'example1' => 1,
        ]);
        $plugingenerator->create_feedback([
            'checkmark' => $historycheckmark->id,
            'userid' => $student->id,
            'presentationstatus' => CHECKMARK_PRESENTATION_STATUS_YES,
        ]);

        $selector = new \checkmark_randomselect\selector(
            get_coursemodule_from_id('checkmark', $currentcheckmark->cmid),
            $currentcheckmark,
            \context_module::instance($currentcheckmark->cmid),
            [$historycheckmark->id],
            false,
            $exampleids,
            1,
            static function (array &$items): void {
                // Keep test output deterministic.
            }
        );
        $this->assertEmpty($selector->create_preview()->get_assignments());

        $selector = new \checkmark_randomselect\selector(
            get_coursemodule_from_id('checkmark', $currentcheckmark->cmid),
            $currentcheckmark,
            \context_module::instance($currentcheckmark->cmid),
            [$currentcheckmark->id],
            false,
            $exampleids,
            1,
            static function (array &$items): void {
                // Keep test output deterministic.
            }
        );
        $this->assertCount(1, $selector->create_preview()->get_assignments());

        $selector = new \checkmark_randomselect\selector(
            get_coursemodule_from_id('checkmark', $currentcheckmark->cmid),
            $currentcheckmark,
            \context_module::instance($currentcheckmark->cmid),
            [],
            false,
            $exampleids,
            1,
            static function (array &$items): void {
                // Keep test output deterministic.
            }
        );
        $this->assertCount(1, $selector->create_preview()->get_assignments());
    }

    /**
     * One student can receive all checked current examples up to the configured maximum.
     */
    public function test_randomselect_preview_assigns_two_current_examples_without_warning(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $plugingenerator = $generator->get_plugin_generator('mod_checkmark');
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');

        $checkmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'examplecount' => 2,
            'grade' => 20,
        ]);
        $exampleids = $this->get_example_ids($checkmark);
        $plugingenerator->create_submission([
            'checkmark' => $checkmark->id,
            'userid' => $student->id,
            'example1' => 1,
            'example2' => 1,
        ]);

        $selector = new \checkmark_randomselect\selector(
            get_coursemodule_from_id('checkmark', $checkmark->cmid),
            $checkmark,
            \context_module::instance($checkmark->cmid),
            [],
            false,
            $exampleids,
            2,
            static function (array &$items): void {
                // Keep test output deterministic.
            }
        );

        $preview = $selector->create_preview();
        $assignments = $preview->get_assignments();
        $data = $preview->export_for_template((int) $course->id);

        $this->assertCount(2, $assignments);
        $this->assertEqualsCanonicalizing($exampleids, array_column($assignments, 'exampleid'));
        $this->assertSame(
            [(int) $student->id],
            array_values(array_unique(array_column($assignments, 'userid')))
        );
        $this->assertFalse($data['hasunassigned']);
        $this->assertFalse($data['hascapacitywarning']);
    }

    /**
     * Random selection warns when the configured count cannot be reached for every eligible student.
     */
    public function test_randomselect_preview_warns_about_unmet_examples_per_student_count(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $plugingenerator = $generator->get_plugin_generator('mod_checkmark');
        $course = $generator->create_course();
        $students = [
            $generator->create_user(),
            $generator->create_user(),
            $generator->create_user(),
        ];

        foreach ($students as $student) {
            $generator->enrol_user($student->id, $course->id, 'student');
        }

        $checkmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'examplecount' => 10,
            'grade' => 100,
        ]);
        $exampleids = $this->get_example_ids($checkmark);

        foreach ($students as $student) {
            $submission = [
                'checkmark' => $checkmark->id,
                'userid' => $student->id,
            ];
            for ($i = 1; $i <= 10; $i++) {
                $submission['example' . $i] = 1;
            }
            $plugingenerator->create_submission($submission);
        }

        $selector = new \checkmark_randomselect\selector(
            get_coursemodule_from_id('checkmark', $checkmark->cmid),
            $checkmark,
            \context_module::instance($checkmark->cmid),
            [$checkmark->id],
            true,
            $exampleids,
            5,
            static function (array &$items): void {
                // Keep test output deterministic.
            }
        );

        $preview = $selector->create_preview();
        $data = $preview->export_for_template((int) $course->id);

        $this->assertCount(10, $preview->get_assignments());
        $this->assertFalse($data['hasunassigned']);
        $this->assertTrue($data['hascapacitywarning']);
        $this->assertSame(
            get_string('previewcapacitywarning', 'checkmark_randomselect', 5),
            $data['capacitywarning']
        );
    }

    /**
     * Applying a random selection marks presentation status and appends presentation feedback.
     */
    public function test_randomselect_apply_marks_and_appends_presentation_feedback(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $plugingenerator = $generator->get_plugin_generator('mod_checkmark');
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $checkmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'examplecount' => 2,
            'grade' => 20,
        ]);
        $exampleids = $this->get_example_ids($checkmark);

        $plugingenerator->create_submission([
            'checkmark' => $checkmark->id,
            'userid' => $student->id,
            'example1' => 1,
            'example2' => 1,
        ]);
        $plugingenerator->create_feedback([
            'checkmark' => $checkmark->id,
            'userid' => $student->id,
            'presentationstatus' => CHECKMARK_PRESENTATION_STATUS_NO,
            'presentationfeedback' => '<p>Existing presentation note.</p>',
        ]);

        $selector = new \checkmark_randomselect\selector(
            get_coursemodule_from_id('checkmark', $checkmark->cmid),
            $checkmark,
            \context_module::instance($checkmark->cmid),
            [$checkmark->id],
            true,
            $exampleids,
            2,
            static function (array &$items): void {
                // Keep test output deterministic.
            }
        );
        $preview = $selector->create_preview();

        $result = (new \checkmark_randomselect\applier(new \checkmark($checkmark->cmid)))->apply($preview);

        $this->assertSame(['examplecount' => 2, 'studentcount' => 1], $result);

        $feedback = $DB->get_record('checkmark_feedbacks', [
            'checkmarkid' => $checkmark->id,
            'userid' => $student->id,
        ], '*', MUST_EXIST);

        $this->assertSame(CHECKMARK_PRESENTATION_STATUS_MARKED, (int) $feedback->presentationstatus);
        $this->assertStringContainsString('Existing presentation note.', $feedback->presentationfeedback);
        $this->assertStringContainsString(
            get_string('feedbackblockheading', 'checkmark_randomselect'),
            $feedback->presentationfeedback
        );
        $this->assertStringContainsString('checkmark-randomselect-feedback', $feedback->presentationfeedback);
        $this->assertStringContainsString('Example 1', $feedback->presentationfeedback);
        $this->assertStringContainsString('Example 2', $feedback->presentationfeedback);
        $this->assertSame((int) FORMAT_HTML, (int) $feedback->presentationformat);
    }

    /**
     * Applying a random selection replaces only a previous generated feedback block.
     */
    public function test_randomselect_apply_replaces_previous_generated_feedback_block(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $plugingenerator = $generator->get_plugin_generator('mod_checkmark');
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $checkmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'examplecount' => 2,
            'grade' => 20,
        ]);

        $plugingenerator->create_feedback([
            'checkmark' => $checkmark->id,
            'userid' => $student->id,
            'presentationstatus' => CHECKMARK_PRESENTATION_STATUS_MARKED,
            'presentationfeedback' => '<p>Teacher note before.</p>'
                . '<div class="checkmark-randomselect-feedback"><p>'
                . s(get_string('feedbackblockheading', 'checkmark_randomselect'))
                . '</p><ul><li>Old example</li></ul></div>'
                . '<p>Teacher note after.</p>',
        ]);

        $preview = new \checkmark_randomselect\preview([
            [
                'exampleid' => 2,
                'examplename' => 'New example',
                'userid' => (int) $student->id,
                'userfullname' => fullname($student),
            ],
        ], []);
        (new \checkmark_randomselect\applier(new \checkmark($checkmark->cmid)))->apply($preview);

        $feedback = $DB->get_record('checkmark_feedbacks', [
            'checkmarkid' => $checkmark->id,
            'userid' => $student->id,
        ], '*', MUST_EXIST);

        $this->assertStringContainsString('Teacher note before.', $feedback->presentationfeedback);
        $this->assertStringContainsString('Teacher note after.', $feedback->presentationfeedback);
        $this->assertStringContainsString('New example', $feedback->presentationfeedback);
        $this->assertStringNotContainsString('Old example', $feedback->presentationfeedback);
        $this->assertSame(1, substr_count($feedback->presentationfeedback, 'checkmark-randomselect-feedback'));
        $this->assertSame(
            1,
            substr_count($feedback->presentationfeedback, get_string('feedbackblockheading', 'checkmark_randomselect'))
        );
    }

    /**
     * Applying a random selection replaces legacy generated feedback blocks without wrappers.
     */
    public function test_randomselect_apply_replaces_legacy_generated_feedback_block(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $plugingenerator = $generator->get_plugin_generator('mod_checkmark');
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $checkmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'examplecount' => 2,
            'grade' => 20,
        ]);

        $plugingenerator->create_feedback([
            'checkmark' => $checkmark->id,
            'userid' => $student->id,
            'presentationstatus' => CHECKMARK_PRESENTATION_STATUS_MARKED,
            'presentationfeedback' => '<p>Teacher note.</p><p>'
                . s(get_string('feedbackblockheading', 'checkmark_randomselect'))
                . '</p><ul><li>Old legacy example</li></ul>',
        ]);

        $preview = new \checkmark_randomselect\preview([
            [
                'exampleid' => 2,
                'examplename' => 'New example',
                'userid' => (int) $student->id,
                'userfullname' => fullname($student),
            ],
        ], []);
        (new \checkmark_randomselect\applier(new \checkmark($checkmark->cmid)))->apply($preview);

        $feedback = $DB->get_record('checkmark_feedbacks', [
            'checkmarkid' => $checkmark->id,
            'userid' => $student->id,
        ], '*', MUST_EXIST);

        $this->assertStringContainsString('Teacher note.', $feedback->presentationfeedback);
        $this->assertStringContainsString('New example', $feedback->presentationfeedback);
        $this->assertStringNotContainsString('Old legacy example', $feedback->presentationfeedback);
        $this->assertSame(1, substr_count($feedback->presentationfeedback, 'checkmark-randomselect-feedback'));
        $this->assertSame(
            1,
            substr_count($feedback->presentationfeedback, get_string('feedbackblockheading', 'checkmark_randomselect'))
        );
    }

    /**
     * Checkmark can discover and manage a test fixture add-on.
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_fixture_addon_can_be_discovered_and_managed(): void {
        $this->resetAfterTest(true);

        $this->add_simple_fixture_addon();

        $plugins = \core_component::get_plugin_list('checkmark');
        $this->assertArrayHasKey('simple', $plugins);
        $subplugins = \core_component::get_subplugins('mod_checkmark');
        $this->assertContains('simple', $subplugins['checkmark']);

        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('checkmark_simple');
        $this->assertInstanceOf(\mod_checkmark\plugininfo\checkmark::class, $plugininfo);
        $this->assertSame('checkmark_simple', $plugininfo->get_settings_section_name());
        $this->assertSame('Simple Checkmark add-on', $plugininfo->displayname);
        $this->assertTrue($plugininfo->is_installed_and_upgraded());

        $manager = new \checkmark_plugin_manager('checkmark');
        $this->assertContains('simple', array_values($manager->get_sorted_plugins_list()));

        $manager->hide_plugin('simple');
        $this->assertSame(1, (int) get_config('checkmark_simple', 'disabled'));

        $manager->show_plugin('simple');
        $this->assertSame(0, (int) get_config('checkmark_simple', 'disabled'));
    }

    /**
     * Add the simple test fixture as an installed checkmark add-on.
     */
    private function add_simple_fixture_addon(): void {
        global $CFG;

        $plugindir = $CFG->dirroot . '/mod/checkmark/tests/fixtures/addon/simple';
        $this->add_mocked_plugin('checkmark', 'simple', $plugindir);

        $mockedcomponent = new \ReflectionClass(\core_component::class);
        $subplugins = $mockedcomponent->getStaticPropertyValue('subplugins');
        $subplugins['mod_checkmark']['checkmark'][] = 'simple';
        $mockedcomponent->setStaticPropertyValue('subplugins', $subplugins);

        $plugin = new \stdClass();
        require($plugindir . '/version.php');
        set_config('version', $plugin->version, $plugin->component);

        \core_plugin_manager::reset_caches(true);
    }

    /**
     * Return example ids for a Checkmark activity.
     *
     * @param object $checkmark Checkmark activity record.
     * @return int[]
     */
    private function get_example_ids(object $checkmark): array {
        return array_keys(\checkmark::get_examples_static($checkmark->id, $checkmark->exampleprefix));
    }

    /**
     * Set one stored example state for a user submission.
     *
     * @param int $checkmarkid Checkmark activity id.
     * @param int $userid User id.
     * @param int $exampleid Example id.
     * @param int $state Example state.
     */
    private function set_example_state(int $checkmarkid, int $userid, int $exampleid, int $state): void {
        global $DB;

        $submissionid = $DB->get_field('checkmark_submissions', 'id', [
            'checkmarkid' => $checkmarkid,
            'userid' => $userid,
        ], MUST_EXIST);

        $DB->set_field('checkmark_checks', 'state', $state, [
            'submissionid' => $submissionid,
            'exampleid' => $exampleid,
        ]);
    }
}
