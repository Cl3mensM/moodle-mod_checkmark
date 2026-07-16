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
 * Random presentation selector.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace checkmark_randomselect;

use context_module;
use core_user\fields;
use mod_checkmark\example;
use mod_checkmark\submissionstable;

/**
 * Random presentation selector.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class selector {
    /** @var \Closure|null Optional shuffle callback for tests. */
    private ?\Closure $shuffle;

    /**
     * Constructor.
     *
     * @param object $cm Current Checkmark course module.
     * @param object $checkmark Current Checkmark activity record.
     * @param context_module $context Current module context.
     * @param int[] $selectedcheckmarkids Checkmark activity ids used for presentation history.
     * @param bool $includeexistingpresentations Whether existing presentations should be included.
     * @param int[] $selectedexampleids Selected current-activity example ids.
     * @param int $examplesperstudent Maximum examples per selected student.
     * @param \Closure|null $shuffle Optional shuffle callback for tests.
     */
    public function __construct(
        /** @var object Current Checkmark course module. */
        private readonly object $cm,
        /** @var object Current Checkmark activity record. */
        private readonly object $checkmark,
        /** @var context_module Current module context. */
        private readonly context_module $context,
        /** @var int[] Checkmark activity ids used for presentation history. */
        private readonly array $selectedcheckmarkids,
        /** @var bool Whether existing presentations should be included. */
        private readonly bool $includeexistingpresentations,
        /** @var int[] Selected current-activity example ids. */
        private readonly array $selectedexampleids,
        /** @var int Maximum examples per selected student. */
        private readonly int $examplesperstudent,
        ?\Closure $shuffle = null,
    ) {
        $this->shuffle = $shuffle;
    }

    /**
     * Create a new random preview.
     *
     * @return preview
     */
    public function create_preview(): preview {
        $examples = $this->get_selected_examples();
        $eligibleusersbyexample = $this->get_eligible_userids_by_example($examples);
        $exampletouser = $this->match_examples_to_users($examples, $eligibleusersbyexample);

        return $this->create_preview_from_example_user_map(
            $examples,
            $exampletouser,
            $eligibleusersbyexample
        );
    }

    /**
     * Recreate and validate a preview from posted assignment ids.
     *
     * @param array $assignments Posted assignments.
     * @return preview|null Valid preview or null if invalid.
     */
    public function preview_from_assignments(array $assignments): ?preview {
        $examples = $this->get_selected_examples();
        $eligibleusersbyexample = $this->get_eligible_userids_by_example($examples);
        $exampletouser = [];
        $usercounts = [];

        foreach ($assignments as $assignment) {
            $exampleid = (int) ($assignment['exampleid'] ?? 0);
            $userid = (int) ($assignment['userid'] ?? 0);

            if (!isset($examples[$exampleid]) || isset($exampletouser[$exampleid])) {
                return null;
            }
            if (!in_array($userid, $eligibleusersbyexample[$exampleid] ?? [], true)) {
                return null;
            }

            $usercounts[$userid] = ($usercounts[$userid] ?? 0) + 1;
            if ($usercounts[$userid] > $this->examplesperstudent) {
                return null;
            }

            $exampletouser[$exampleid] = $userid;
        }

        return $this->create_preview_from_example_user_map(
            $examples,
            $exampletouser,
            $eligibleusersbyexample
        );
    }

    /**
     * Return selected current-activity examples.
     *
     * @return example[]
     */
    private function get_selected_examples(): array {
        $exampleprefix = $this->checkmark->exampleprefix ?? false;
        if ($exampleprefix === null) {
            $exampleprefix = false;
        }

        $examples = \checkmark::get_examples_static($this->checkmark->id, $exampleprefix);
        $selected = [];
        foreach ($examples as $example) {
            $exampleid = (int) $example->get_id();
            if (in_array($exampleid, $this->selectedexampleids, true)) {
                $selected[$exampleid] = $example;
            }
        }

        return $selected;
    }

    /**
     * Return eligible user ids for each selected example.
     *
     * @param example[] $examples Selected examples.
     * @return array<int,int[]>
     */
    private function get_eligible_userids_by_example(array $examples): array {
        $candidateuserids = $this->get_candidate_userids();
        if (empty($examples) || empty($candidateuserids)) {
            return array_fill_keys(array_keys($examples), []);
        }

        $checkedusersbyexample = $this->get_checked_userids_by_example_id(array_keys($examples), $candidateuserids);
        $eligible = [];

        foreach (array_keys($examples) as $exampleid) {
            $eligible[$exampleid] = array_values(array_intersect(
                $candidateuserids,
                $checkedusersbyexample[$exampleid] ?? []
            ));
        }

        return $eligible;
    }

    /**
     * Return base candidate user ids.
     *
     * @return int[]
     */
    private function get_candidate_userids(): array {
        $currentgroup = groups_get_activity_group($this->cm, true);
        $userids = submissionstable::get_userids_static(
            $this->context,
            $this->checkmark->id,
            $currentgroup,
            \checkmark::FILTER_ALL
        );
        $userids = $this->normalize_userids($userids);

        if (!$this->includeexistingpresentations) {
            $userids = array_values(array_diff($userids, $this->get_userids_with_existing_presentations($userids)));
        }

        sort($userids, SORT_NUMERIC);

        return $userids;
    }

    /**
     * Return users with completed presentation in the selected presentation-history activities.
     *
     * @param int[] $candidateuserids Candidate user ids.
     * @return int[]
     */
    private function get_userids_with_existing_presentations(array $candidateuserids): array {
        global $DB;

        if (empty($candidateuserids) || empty($this->selectedcheckmarkids)) {
            return [];
        }

        [$checkmarksql, $checkmarkparams] = $DB->get_in_or_equal(
            $this->selectedcheckmarkids,
            SQL_PARAMS_NAMED,
            'existingcm'
        );
        [$usersql, $userparams] = $DB->get_in_or_equal($candidateuserids, SQL_PARAMS_NAMED, 'existinguser');
        $params = $checkmarkparams + $userparams + [
            'presentationstatus' => CHECKMARK_PRESENTATION_STATUS_YES,
        ];

        $sql = "SELECT DISTINCT userid
                  FROM {checkmark_feedbacks}
                 WHERE checkmarkid {$checkmarksql}
                   AND userid {$usersql}
                   AND presentationstatus = :presentationstatus";

        return array_map('intval', $DB->get_fieldset_sql($sql, $params));
    }

    /**
     * Return users who checked each selected example in the current Checkmark activity.
     *
     * @param int[] $exampleids Selected current-activity example ids.
     * @param int[] $candidateuserids Candidate user ids.
     * @return array<int,int[]>
     */
    private function get_checked_userids_by_example_id(array $exampleids, array $candidateuserids): array {
        global $DB;

        [$examplesql, $exampleparams] = $DB->get_in_or_equal(
            $exampleids,
            SQL_PARAMS_NAMED,
            'currentexample'
        );
        [$usersql, $userparams] = $DB->get_in_or_equal($candidateuserids, SQL_PARAMS_NAMED, 'basisuser');
        $params = $exampleparams + $userparams + [
            'currentcheckmark' => $this->checkmark->id,
            'checked' => example::CHECKED,
            'teacherchecked' => example::UNCHECKED_OVERWRITTEN,
        ];

        $sql = "SELECT DISTINCT cc.exampleid, s.userid
                  FROM {checkmark_submissions} s
                  JOIN {checkmark_checks} cc ON cc.submissionid = s.id
                  JOIN {checkmark_examples} ex ON ex.id = cc.exampleid AND ex.checkmarkid = s.checkmarkid
                 WHERE s.checkmarkid = :currentcheckmark
                   AND cc.exampleid {$examplesql}
                   AND s.userid {$usersql}
                   AND cc.state IN (:checked, :teacherchecked)";

        $checked = [];
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $record) {
            $checked[(int) $record->exampleid][] = (int) $record->userid;
        }
        $records->close();

        foreach ($checked as $exampleid => $userids) {
            $checked[$exampleid] = array_values(array_unique($userids));
        }

        return $checked;
    }

    /**
     * Match examples to users while respecting per-user capacity.
     *
     * @param array $examples Selected examples.
     * @param array $eligibleusersbyexample Eligible users per example.
     * @return array Example id to user id map.
     */
    private function match_examples_to_users(array $examples, array $eligibleusersbyexample): array {
        $userids = [];
        foreach ($eligibleusersbyexample as $eligibleuserids) {
            $userids = array_merge($userids, $eligibleuserids);
        }
        $userids = array_values(array_unique($userids));

        $slots = [];
        foreach ($userids as $userid) {
            for ($i = 0; $i < $this->examplesperstudent; $i++) {
                $slots[$userid . ':' . $i] = $userid;
            }
        }

        $slotidsbyexample = [];
        foreach ($examples as $exampleid => $example) {
            $slotids = [];
            foreach ($slots as $slotid => $userid) {
                if (in_array($userid, $eligibleusersbyexample[$exampleid] ?? [], true)) {
                    $slotids[] = $slotid;
                }
            }
            $this->shuffle($slotids);
            $slotidsbyexample[$exampleid] = $slotids;
        }

        $exampleids = array_keys($examples);
        $this->shuffle($exampleids);
        usort($exampleids, static function (int $left, int $right) use ($slotidsbyexample): int {
            return count($slotidsbyexample[$left]) <=> count($slotidsbyexample[$right]);
        });

        $slottoexample = [];
        foreach ($exampleids as $exampleid) {
            $seen = [];
            $this->assign_example_to_slot($exampleid, $slotidsbyexample, $slottoexample, $seen);
        }

        $exampletouser = [];
        foreach ($slottoexample as $slotid => $exampleid) {
            $exampletouser[$exampleid] = $slots[$slotid];
        }

        return $exampletouser;
    }

    /**
     * Assign an example to a matching slot with rematching.
     *
     * @param int $exampleid Example id.
     * @param array $slotidsbyexample Slot ids by example.
     * @param array $slottoexample Current slot to example map.
     * @param array $seen Seen slot ids.
     * @return bool
     */
    private function assign_example_to_slot(
        int $exampleid,
        array $slotidsbyexample,
        array &$slottoexample,
        array &$seen
    ): bool {
        foreach ($slotidsbyexample[$exampleid] as $slotid) {
            if (isset($seen[$slotid])) {
                continue;
            }
            $seen[$slotid] = true;

            if (
                !isset($slottoexample[$slotid])
                || $this->assign_example_to_slot($slottoexample[$slotid], $slotidsbyexample, $slottoexample, $seen)
            ) {
                $slottoexample[$slotid] = $exampleid;
                return true;
            }
        }

        return false;
    }

    /**
     * Create preview object from example-to-user map.
     *
     * @param array $examples Selected examples.
     * @param array $exampletouser Example id to user id map.
     * @param array $eligibleusersbyexample Eligible users per example.
     * @return preview
     */
    private function create_preview_from_example_user_map(
        array $examples,
        array $exampletouser,
        array $eligibleusersbyexample
    ): preview {
        $users = $this->get_users(array_values($exampletouser));
        $assignments = [];
        $unassigned = [];

        foreach ($examples as $exampleid => $example) {
            if (!isset($exampletouser[$exampleid]) || !isset($users[$exampletouser[$exampleid]])) {
                $unassigned[] = [
                    'exampleid' => $exampleid,
                    'examplename' => $example->get_name(),
                ];
                continue;
            }

            $user = $users[$exampletouser[$exampleid]];
            $assignments[] = [
                'exampleid' => $exampleid,
                'examplename' => $example->get_name(),
                'userid' => (int) $user->id,
                'userfullname' => fullname($user),
            ];
        }

        $unmetexamplesperstudent = $this->has_unmet_per_student_count(
            $exampletouser,
            $eligibleusersbyexample
        ) ? $this->examplesperstudent : null;

        return new preview($assignments, $unassigned, $unmetexamplesperstudent);
    }

    /**
     * Return whether an eligible user received fewer examples than configured.
     *
     * @param array $exampletouser Example id to user id map.
     * @param array $eligibleusersbyexample Eligible users per example.
     * @return bool
     */
    private function has_unmet_per_student_count(
        array $exampletouser,
        array $eligibleusersbyexample
    ): bool {
        $eligibleuserids = [];
        foreach ($eligibleusersbyexample as $userids) {
            $eligibleuserids = array_merge($eligibleuserids, $userids);
        }
        $eligibleuserids = array_unique($eligibleuserids);

        if (empty($eligibleuserids)) {
            return false;
        }

        $assignmentcounts = array_count_values($exampletouser);
        foreach ($eligibleuserids as $userid) {
            if (($assignmentcounts[$userid] ?? 0) < $this->examplesperstudent) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return user records for display.
     *
     * @param int[] $userids User ids.
     * @return array<int,object>
     */
    private function get_users(array $userids): array {
        global $DB;

        $userids = array_values(array_unique(array_map('intval', $userids)));
        if (empty($userids)) {
            return [];
        }

        [$usersql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'displayuser');
        $namefields = fields::for_name()->get_sql('u')->selects;

        return $DB->get_records_sql("SELECT u.id {$namefields} FROM {user} u WHERE u.id {$usersql}", $params);
    }

    /**
     * Normalize Checkmark's empty-user sentinel.
     *
     * @param int[] $userids User ids.
     * @return int[]
     */
    private function normalize_userids(array $userids): array {
        $userids = array_values(array_filter(
            array_map('intval', $userids),
            static fn(int $userid): bool => $userid > 0
        ));

        return array_values(array_unique($userids));
    }

    /**
     * Shuffle an array.
     *
     * @param array $items Items to shuffle.
     */
    private function shuffle(array &$items): void {
        if ($this->shuffle !== null) {
            ($this->shuffle)($items);
            return;
        }

        shuffle($items);
    }
}
