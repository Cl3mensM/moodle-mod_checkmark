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
 * Applies a random presentation selection preview.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace checkmark_randomselect;

/**
 * Applies a random presentation selection preview.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class applier {
    /** @var string CSS class for generated feedback blocks. */
    private const FEEDBACK_BLOCK_CLASS = 'checkmark-randomselect-feedback';

    /**
     * Constructor.
     *
     * @param \checkmark $checkmark Checkmark instance.
     */
    public function __construct(
        /** @var \checkmark Checkmark instance. */
        private readonly \checkmark $checkmark,
    ) {
    }

    /**
     * Apply a preview to Checkmark presentation feedback.
     *
     * @param preview $preview Preview to apply.
     * @return array Counts with examplecount and studentcount.
     */
    public function apply(preview $preview): array {
        $assignmentsbyuser = $this->group_assignments_by_user($preview->get_assignments());

        foreach ($assignmentsbyuser as $userid => $assignments) {
            $this->apply_user_assignments((int) $userid, $assignments);
        }

        return [
            'examplecount' => count($preview->get_assignments()),
            'studentcount' => count($assignmentsbyuser),
        ];
    }

    /**
     * Group assignments by user id.
     *
     * @param array $assignments Assignments.
     * @return array<int,array>
     */
    private function group_assignments_by_user(array $assignments): array {
        $grouped = [];
        foreach ($assignments as $assignment) {
            $grouped[(int) $assignment['userid']][] = $assignment;
        }

        return $grouped;
    }

    /**
     * Apply assignments for a single user.
     *
     * @param int $userid User id.
     * @param array $assignments Assignments for the user.
     */
    private function apply_user_assignments(int $userid, array $assignments): void {
        global $DB, $USER;

        $feedback = $this->checkmark->prepare_new_feedback($userid);
        $feedback->presentationstatus = CHECKMARK_PRESENTATION_STATUS_MARKED;
        $feedback->presentationfeedback = $this->append_feedback_block(
            (string) ($feedback->presentationfeedback ?? ''),
            array_column($assignments, 'examplename')
        );
        $feedback->presentationformat = FORMAT_HTML;
        $feedback->graderid = $USER->id;
        $feedback->mailed = 1;
        $feedback->timemodified = time();
        $feedback->presentationtimemodified = $feedback->timemodified;

        $DB->update_record('checkmark_feedbacks', $feedback);
        $this->checkmark->update_grade($feedback);

        \mod_checkmark\event\grade_updated::manual($this->checkmark->cm, [
            'userid' => $feedback->userid,
            'feedbackid' => $feedback->id,
        ])->trigger();
    }

    /**
     * Replace the generated random selection feedback block.
     *
     * @param string $existingfeedback Existing presentation feedback.
     * @param string[] $examplenames Assigned example names.
     * @return string
     */
    private function append_feedback_block(string $existingfeedback, array $examplenames): string {
        $existingfeedback = $this->remove_existing_feedback_block($existingfeedback);

        $items = [];
        foreach ($examplenames as $examplename) {
            $items[] = \html_writer::tag('li', s($examplename));
        }

        $blockcontent = \html_writer::tag('p', s(get_string('feedbackblockheading', 'checkmark_randomselect')))
            . \html_writer::tag('ul', implode('', $items));
        $block = \html_writer::tag('div', $blockcontent, [
            'class' => self::FEEDBACK_BLOCK_CLASS,
        ]);

        if (trim($existingfeedback) === '') {
            return $block;
        }

        return rtrim($existingfeedback) . "\n" . $block;
    }

    /**
     * Remove previous generated random selection feedback blocks.
     *
     * @param string $feedback Existing presentation feedback.
     * @return string
     */
    private function remove_existing_feedback_block(string $feedback): string {
        $class = preg_quote(self::FEEDBACK_BLOCK_CLASS, '~');
        $updatedfeedback = preg_replace(
            '~\s*<div\b(?=[^>]*\bclass=(["\'])(?:(?!\1).)*\b' . $class . '\b(?:(?!\1).)*\1)[^>]*>.*?</div>\s*~is',
            "\n",
            $feedback
        );
        if ($updatedfeedback !== null) {
            $feedback = $updatedfeedback;
        }

        $heading = preg_quote(s(get_string('feedbackblockheading', 'checkmark_randomselect')), '~');
        $updatedfeedback = preg_replace(
            '~\s*<p\b[^>]*>\s*' . $heading . '\s*</p>\s*<ul\b[^>]*>.*?</ul>\s*~is',
            "\n",
            $feedback
        );

        return $updatedfeedback ?? $feedback;
    }
}
