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
 * Random selection preview.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace checkmark_randomselect;

use moodle_url;

/**
 * Random selection preview.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class preview {
    /**
     * Constructor.
     *
     * @param array $assignments Assigned examples with exampleid, examplename, userid and userfullname.
     * @param array $unassigned Unassigned examples with exampleid and examplename.
     */
    public function __construct(
        /** @var array Assigned examples. */
        private readonly array $assignments,
        /** @var array Unassigned examples. */
        private readonly array $unassigned,
    ) {
    }

    /**
     * Return assigned examples.
     *
     * @return array
     */
    public function get_assignments(): array {
        return $this->assignments;
    }

    /**
     * Return whether the preview contains assignments.
     *
     * @return bool
     */
    public function has_assignments(): bool {
        return !empty($this->assignments);
    }

    /**
     * Return transient assignment data for hidden form state.
     *
     * @param string $fingerprint Preview settings fingerprint.
     * @return string
     */
    public function encode(string $fingerprint = ''): string {
        $assignments = array_map(static fn(array $assignment): array => [
            'exampleid' => (int) $assignment['exampleid'],
            'userid' => (int) $assignment['userid'],
        ], $this->assignments);

        return base64_encode(json_encode([
            'fingerprint' => $fingerprint,
            'assignments' => $assignments,
        ]));
    }

    /**
     * Decode transient assignment data from hidden form state.
     *
     * @param string $encoded Encoded preview data.
     * @return array Decoded assignments.
     */
    public static function decode_assignments(string $encoded): array {
        if ($encoded === '') {
            return [];
        }

        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            return [];
        }

        $assignments = json_decode($decoded, true);
        if (!is_array($assignments)) {
            return [];
        }
        if (isset($assignments['assignments']) && is_array($assignments['assignments'])) {
            $assignments = $assignments['assignments'];
        }

        $clean = [];
        foreach ($assignments as $assignment) {
            if (!is_array($assignment) || !isset($assignment['exampleid'], $assignment['userid'])) {
                continue;
            }

            $clean[] = [
                'exampleid' => (int) $assignment['exampleid'],
                'userid' => (int) $assignment['userid'],
            ];
        }

        return $clean;
    }

    /**
     * Decode the preview settings fingerprint.
     *
     * @param string $encoded Encoded preview data.
     * @return string
     */
    public static function decode_fingerprint(string $encoded): string {
        if ($encoded === '') {
            return '';
        }

        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            return '';
        }

        $data = json_decode($decoded, true);
        if (!is_array($data) || !isset($data['fingerprint']) || !is_string($data['fingerprint'])) {
            return '';
        }

        return $data['fingerprint'];
    }

    /**
     * Export template data.
     *
     * @param int $courseid Course id for user profile links.
     * @param string $fingerprint Preview settings fingerprint.
     * @return array Template data.
     */
    public function export_for_template(int $courseid, string $fingerprint = ''): array {
        $assignedcount = count($this->assignments);
        $assigneduserids = array_unique(array_column($this->assignments, 'userid'));
        $unassignedcount = count($this->unassigned);

        return [
            'haspreview' => true,
            'hasassignments' => $this->has_assignments(),
            'hasunassigned' => !empty($this->unassigned),
            'previewdata' => $this->encode($fingerprint),
            'loadinglabel' => get_string('previewloading', 'checkmark_randomselect'),
            'unassignedintro' => get_string('previewunassignedintro', 'checkmark_randomselect', (object) [
                'count' => $unassignedcount,
                'reason' => get_string('notenougheligiblestudents', 'checkmark_randomselect'),
            ]),
            'unassignedexamples' => $this->export_unassigned_examples(),
            'assignmentintro' => get_string('previewassignmentintro', 'checkmark_randomselect', (object) [
                'examplecount' => $assignedcount,
                'studentcount' => count($assigneduserids),
            ]),
            'assignments' => $this->export_assignments($courseid),
            'nextsteps' => get_string('previewnextsteps', 'checkmark_randomselect'),
        ];
    }

    /**
     * Export assigned examples.
     *
     * @param int $courseid Course id for user profile links.
     * @return array
     */
    private function export_assignments(int $courseid): array {
        return array_map(static fn(array $assignment): array => [
            'exampleid' => (int) $assignment['exampleid'],
            'examplename' => $assignment['examplename'],
            'userid' => (int) $assignment['userid'],
            'userfullname' => $assignment['userfullname'],
            'userurl' => (new moodle_url('/user/view.php', [
                'id' => (int) $assignment['userid'],
                'course' => $courseid,
            ]))->out(false),
        ], $this->assignments);
    }

    /**
     * Export unassigned examples.
     *
     * @return array
     */
    private function export_unassigned_examples(): array {
        return array_map(static fn(array $example): array => [
            'exampleid' => (int) $example['exampleid'],
            'examplename' => $example['examplename'],
        ], $this->unassigned);
    }
}
