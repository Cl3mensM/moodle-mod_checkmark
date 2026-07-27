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

namespace mod_checkmark;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/checkmark/locallib.php');

/**
 * Tests for the Checkmark grading summary.
 *
 * @package   mod_checkmark
 * @category  test
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(gradingsummary::class)]
final class gradingsummary_test extends \advanced_testcase {
    /**
     * Presentation rows show correct counts and filter links when enabled.
     */
    public function test_presentation_rows_show_counts_and_filter_links(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        [$course, $checkmark, $students] = $this->create_checkmark_with_students(true, 3);
        $this->create_feedback($checkmark, $students[0], CHECKMARK_PRESENTATION_STATUS_MARKED);
        $this->create_feedback($checkmark, $students[1], CHECKMARK_PRESENTATION_STATUS_MARKED);
        $this->create_feedback($checkmark, $students[2], CHECKMARK_PRESENTATION_STATUS_YES);

        $instance = new \checkmark($checkmark->cmid);
        $summary = $instance->create_grading_summary();

        $this->assertSame(2, $summary->presentationmarkedcount);
        $this->assertSame(1, $summary->presentationgradingcount);

        $html = $this->render_summary($instance, $summary, $course);
        $this->assert_summary_link(
            $html,
            get_string('presentationmarkedcount', 'checkmark'),
            2,
            \checkmark::FILTER_PRESENTATION_MARKED,
            (int) $checkmark->cmid
        );
        $this->assert_summary_link(
            $html,
            get_string('presentationgradingcount', 'checkmark'),
            1,
            \checkmark::FILTER_PRESENTATIONGRADING,
            (int) $checkmark->cmid
        );
    }

    /**
     * Presentation rows are present with zero counts when enabled.
     */
    public function test_presentation_rows_show_zero_counts_when_enabled(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        [$course, $checkmark] = $this->create_checkmark_with_students(true, 1);
        $instance = new \checkmark($checkmark->cmid);
        $summary = $instance->create_grading_summary();
        $html = $this->render_summary($instance, $summary, $course);

        $this->assertSame(0, $summary->presentationmarkedcount);
        $this->assertSame(0, $summary->presentationgradingcount);
        $this->assert_summary_link(
            $html,
            get_string('presentationmarkedcount', 'checkmark'),
            0,
            \checkmark::FILTER_PRESENTATION_MARKED,
            (int) $checkmark->cmid
        );
        $this->assert_summary_link(
            $html,
            get_string('presentationgradingcount', 'checkmark'),
            0,
            \checkmark::FILTER_PRESENTATIONGRADING,
            (int) $checkmark->cmid
        );
    }

    /**
     * Presentation rows are absent when presentation grading is disabled.
     */
    public function test_presentation_rows_are_hidden_when_disabled(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        [$course, $checkmark] = $this->create_checkmark_with_students(false, 1);
        $instance = new \checkmark($checkmark->cmid);
        $summary = $instance->create_grading_summary();
        $html = $this->render_summary($instance, $summary, $course);

        $this->assertSame(-1, $summary->presentationmarkedcount);
        $this->assertSame(-1, $summary->presentationgradingcount);
        $this->assertStringNotContainsString(get_string('presentationmarkedcount', 'checkmark'), $html);
        $this->assertStringNotContainsString(get_string('presentationgradingcount', 'checkmark'), $html);
    }

    /**
     * Presentation counts and links use the currently selected group.
     */
    public function test_presentation_rows_respect_current_group(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        [$course, $checkmark, $students] = $this->create_checkmark_with_students(true, 4, VISIBLEGROUPS);
        $generator = $this->getDataGenerator();
        $groupa = $generator->create_group(['courseid' => $course->id]);
        $groupb = $generator->create_group(['courseid' => $course->id]);
        $generator->create_group_member(['groupid' => $groupa->id, 'userid' => $students[0]->id]);
        $generator->create_group_member(['groupid' => $groupa->id, 'userid' => $students[1]->id]);
        $generator->create_group_member(['groupid' => $groupb->id, 'userid' => $students[2]->id]);
        $generator->create_group_member(['groupid' => $groupb->id, 'userid' => $students[3]->id]);

        $this->create_feedback($checkmark, $students[0], CHECKMARK_PRESENTATION_STATUS_MARKED);
        $this->create_feedback($checkmark, $students[1], CHECKMARK_PRESENTATION_STATUS_YES);
        $this->create_feedback($checkmark, $students[2], CHECKMARK_PRESENTATION_STATUS_MARKED);
        $this->create_feedback($checkmark, $students[3], CHECKMARK_PRESENTATION_STATUS_MARKED);

        $oldget = $_GET;
        $_GET['group'] = $groupa->id;
        try {
            $instance = new \checkmark($checkmark->cmid);
            $summary = $instance->create_grading_summary();
            $html = $this->render_summary($instance, $summary, $course);
        } finally {
            $_GET = $oldget;
        }

        $this->assertSame(1, $summary->presentationmarkedcount);
        $this->assertSame(1, $summary->presentationgradingcount);
        $this->assert_summary_link(
            $html,
            get_string('presentationmarkedcount', 'checkmark'),
            1,
            \checkmark::FILTER_PRESENTATION_MARKED,
            (int) $checkmark->cmid,
            (int) $groupa->id
        );
        $this->assert_summary_link(
            $html,
            get_string('presentationgradingcount', 'checkmark'),
            1,
            \checkmark::FILTER_PRESENTATIONGRADING,
            (int) $checkmark->cmid,
            (int) $groupa->id
        );
    }

    /**
     * Create a Checkmark activity and enrolled students.
     *
     * @param bool $presentationgrading Whether presentation grading is enabled.
     * @param int $studentcount Number of students to create.
     * @param int $groupmode Activity group mode.
     * @return array Course, activity and students.
     */
    private function create_checkmark_with_students(
        bool $presentationgrading,
        int $studentcount,
        int $groupmode = NOGROUPS
    ): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $students = [];
        for ($i = 0; $i < $studentcount; $i++) {
            $students[] = $generator->create_user();
            $generator->enrol_user($students[$i]->id, $course->id, 'student');
        }
        $checkmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => (int) $presentationgrading,
            'presentationgrade' => $presentationgrading ? 100 : 0,
            'groupmode' => $groupmode,
        ]);

        return [$course, $checkmark, $students];
    }

    /**
     * Create presentation feedback for a student.
     *
     * @param object $checkmark Checkmark activity.
     * @param object $student Student record.
     * @param int $status Presentation status.
     */
    private function create_feedback(object $checkmark, object $student, int $status): void {
        $this->getDataGenerator()->get_plugin_generator('mod_checkmark')->create_feedback([
            'checkmark' => $checkmark->id,
            'userid' => $student->id,
            'presentationstatus' => $status,
        ]);
    }

    /**
     * Render the grading summary.
     *
     * @param \checkmark $instance Checkmark instance.
     * @param gradingsummary $summary Grading summary.
     * @param object $course Course record.
     * @return string Rendered summary HTML.
     */
    private function render_summary(\checkmark $instance, gradingsummary $summary, object $course): string {
        global $PAGE;

        $PAGE->set_cm($instance->cm, $course);
        $renderer = $PAGE->get_renderer('mod_checkmark');

        return $renderer->render_checkmark_grading_summary($summary, $instance->cm);
    }

    /**
     * Assert a summary row contains the expected count and filter link.
     *
     * @param string $html Rendered summary HTML.
     * @param string $label Row label.
     * @param int $count Expected count.
     * @param int $filter Expected submissions filter.
     * @param int $coursemoduleid Expected course module id.
     * @param int|null $groupid Expected group id, if groups are active.
     */
    private function assert_summary_link(
        string $html,
        string $label,
        int $count,
        int $filter,
        int $coursemoduleid,
        ?int $groupid = null
    ): void {
        $params = [
            'id' => $coursemoduleid,
            'updatepref' => 1,
            'filter' => $filter,
        ];
        if ($groupid !== null) {
            $params['group'] = $groupid;
        }
        $url = new \moodle_url('/mod/checkmark/submissions.php', $params);
        $expectedlink = \html_writer::link($url, (string) $count, ['class' => 'link']);

        $this->assertStringContainsString($label, $html);
        $this->assertStringContainsString($expectedlink, $html);
    }
}
