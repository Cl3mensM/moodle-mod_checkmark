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
 * Tests for the Checkmark submissions table.
 *
 * @package   mod_checkmark
 * @category  test
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(submissionstable::class)]
final class submissionstable_test extends \advanced_testcase {
    /**
     * Example markers are scaled to remain visible in narrow PDF columns.
     */
    public function test_export_example_columns_use_scaling(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $checkmark = $this->getDataGenerator()->create_module('checkmark', [
            'course' => $course->id,
            'examplecount' => 3,
            'grade' => 30,
        ]);

        $table = submissionstable::create_export_table(
            $checkmark->cmid,
            \checkmark::FILTER_ALL,
            [$student->id]
        );
        [, , , $columnformats] = $table->get_data();
        $exampleformats = array_filter(
            $columnformats,
            static fn($key): bool => str_starts_with($key, 'example'),
            ARRAY_FILTER_USE_KEY
        );

        $this->assertCount(3, $exampleformats);
        foreach ($exampleformats as $format) {
            $this->assertSame('C', $format['align']);
            $this->assertSame(MTablePDF::STRETCH_SCALING, $format['stretch']);
        }
    }

    /**
     * Presentation feedback keeps HTML normally and uses readable plain text in quick grading.
     */
    public function test_presentation_feedback_output_with_and_without_quick_grading(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $checkmark = $this->getDataGenerator()->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'presentationgradebook' => 0,
        ]);

        $feedback = '<div class="checkmark-randomselect-feedback">'
            . '<p>[Random selected]:</p><ul>'
            . '<li>Example 1 &amp; introduction</li>'
            . '<li>Example 2 &lt;/textarea&gt;&lt;script&gt;alert(1)&lt;/script&gt;</li>'
            . '</ul></div>';
        $values = (object) [
            'id' => (int) $student->id,
            'feedbackid' => 1,
            'presentationfeedback' => $feedback,
        ];

        $standardtable = new submissionstable('checkmark-presentation-feedback-standard', $checkmark->cmid);
        $standardoutput = $standardtable->col_presentationfeedback($values);

        $this->assertStringContainsString($feedback, $standardoutput);
        $this->assertStringNotContainsString('<textarea', $standardoutput);

        $quickgradetable = new submissionstable('checkmark-presentation-feedback-quickgrade', $checkmark->cmid);
        $quickgradeproperty = new \ReflectionProperty(submissionstable::class, 'quickgrade');
        $quickgradeproperty->setValue($quickgradetable, true);
        $quickgradeoutput = $quickgradetable->col_presentationfeedback($values);

        $this->assertStringContainsString('<textarea', $quickgradeoutput);
        $this->assertStringContainsString("* Example 1 &amp; introduction\n", $quickgradeoutput);
        $this->assertStringContainsString('* Example 2 &lt;/textarea&gt;&lt;script&gt;alert(1)&lt;/script&gt;', $quickgradeoutput);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $quickgradeoutput);
    }
}
