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
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_randomselect\filter::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_randomselect\output\page::class)]
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
        $this->assertFileExists($plugins['randomselect'] . '/classes/filter.php');
        $this->assertFileExists($plugins['randomselect'] . '/templates/page.mustache');
        $this->assertFileExists($plugins['randomselect'] . '/templates/filtercriteria.mustache');
        $this->assertFileExists($plugins['randomselect'] . '/amd/src/randomselect_layout.js');
        $this->assertFileExists($plugins['randomselect'] . '/amd/build/randomselect_layout.min.js');
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
        ]);
        $alpha = $generator->create_module('checkmark', [
            'course' => $course->id,
            'name' => 'Alpha Checkmark',
        ]);
        $generator->create_module('checkmark', [
            'course' => $course->id,
            'name' => 'Hidden Checkmark',
            'visible' => 0,
        ]);
        $generator->create_module('checkmark', [
            'course' => $othercourse->id,
            'name' => 'Other Course Checkmark',
        ]);

        set_config(
            \checkmark_randomselect\settings::INCLUDE_EXISTING_PRESENTATIONS,
            1,
            'checkmark_randomselect'
        );
        $this->setUser($teacher);

        $filter = \checkmark_randomselect\filter::default_for_course((int) $course->id);
        $options = $filter->get_checkmark_options();

        $this->assertSame(['Alpha Checkmark', 'Zulu Checkmark'], array_column($options, 'name'));
        $this->assertSame([(int) $alpha->id, (int) $zulu->id], array_column($options, 'id'));
        $this->assertTrue($options[0]['checked']);
        $this->assertTrue($options[1]['checked']);

        $data = $filter->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame(get_string('coursecheckmarks', 'checkmark_randomselect'), $data['coursecheckmarkslabel']);
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
    }

    /**
     * Random selection page exports the ticket #8752 layout data.
     */
    public function test_randomselect_page_layout_is_exported(): void {
        global $PAGE;

        $page = new \checkmark_randomselect\output\page(
            new \moodle_url('/mod/checkmark/view.php', ['id' => 23]),
            new \checkmark_randomselect\filter(SITEID, false)
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
        $this->assertSame(get_string('preview', 'checkmark_randomselect'), $data['previewtitle']);
        $this->assertNotEmpty($data['previewhelpicon']);
        $this->assertSame(
            get_string('applyrandomselection', 'checkmark_randomselect'),
            $data['applybuttonlabel']
        );
        $this->assertSame(
            get_string('createnewpreview', 'checkmark_randomselect'),
            $data['createpreviewbuttonlabel']
        );
        $this->assertSame(get_string('cancel'), $data['cancelbuttonlabel']);
        $this->assertStringContainsString('/mod/checkmark/view.php?id=23', $data['cancelurl']);
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
}
