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
 * Submitted form state for the Checkmark random selection page.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace checkmark_randomselect;

/**
 * Submitted form state for the Checkmark random selection page.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class formdata {
    /** Form action for creating a preview. */
    public const ACTION_PREVIEW = 'preview';

    /** Form action for applying a preview. */
    public const ACTION_APPLY = 'apply';

    /** Hidden field containing the transient preview data. */
    public const PREVIEW_DATA = 'previewdata';

    /**
     * Constructor.
     *
     * @param string $action Submitted form action.
     * @param string $checkmarkselection Selected Checkmark activity mode.
     * @param int[] $selectedcheckmarkids Selected data-basis Checkmark ids.
     * @param bool $includeexistingpresentations Whether existing presentations should be included.
     * @param string $exampleselection Selected example mode.
     * @param int[] $selectedexampleids Selected current-activity example ids.
     * @param int $examplesperstudent Maximum number of examples per selected student.
     * @param string $previewdata Encoded transient preview data.
     */
    public function __construct(
        /** @var string Submitted form action. */
        public readonly string $action,
        /** @var string Selected Checkmark activity mode. */
        public readonly string $checkmarkselection,
        /** @var int[] Selected data-basis Checkmark ids. */
        public readonly array $selectedcheckmarkids,
        /** @var bool Whether existing presentations should be included. */
        public readonly bool $includeexistingpresentations,
        /** @var string Selected example mode. */
        public readonly string $exampleselection,
        /** @var int[] Selected current-activity example ids. */
        public readonly array $selectedexampleids,
        /** @var int Maximum number of examples per selected student. */
        public readonly int $examplesperstudent,
        /** @var string Encoded transient preview data. */
        public readonly string $previewdata,
    ) {
    }

    /**
     * Create form state from the current request.
     *
     * @param filter $filter Default filter criteria data.
     * @param application $application Default field of application data.
     * @return self
     */
    public static function from_request(filter $filter, application $application): self {
        $availablecheckmarkids = self::get_ids($filter->get_checkmark_options());
        $availableexampleids = self::get_ids($application->get_example_options());

        $checkmarkselection = optional_param(
            'checkmarkselection',
            filter::CHECKMARK_SELECTION_ALL,
            PARAM_ALPHA
        );
        if (!in_array($checkmarkselection, [filter::CHECKMARK_SELECTION_ALL, filter::CHECKMARK_SELECTION_SELECTED], true)) {
            $checkmarkselection = filter::CHECKMARK_SELECTION_ALL;
        }

        $exampleselection = optional_param(
            'exampleselection',
            application::EXAMPLE_SELECTION_ALL,
            PARAM_ALPHA
        );
        if (!in_array($exampleselection, [application::EXAMPLE_SELECTION_ALL, application::EXAMPLE_SELECTION_SELECTED], true)) {
            $exampleselection = application::EXAMPLE_SELECTION_ALL;
        }

        $selectedcheckmarkids = $availablecheckmarkids;
        if ($checkmarkselection === filter::CHECKMARK_SELECTION_SELECTED) {
            $selectedcheckmarkids = self::filter_posted_ids(
                optional_param_array('checkmarkids', [], PARAM_INT),
                $availablecheckmarkids
            );
        }

        $selectedexampleids = $availableexampleids;
        if ($exampleselection === application::EXAMPLE_SELECTION_SELECTED) {
            $selectedexampleids = self::filter_posted_ids(
                optional_param_array('exampleids', [], PARAM_INT),
                $availableexampleids
            );
        }

        $selectedexamplecount = count($selectedexampleids);
        $examplesperstudent = optional_param('examplesperstudent', 1, PARAM_INT);
        $examplesperstudent = min(max($examplesperstudent, 1), max($selectedexamplecount, 1));

        return new self(
            optional_param('action', '', PARAM_ALPHA),
            $checkmarkselection,
            $selectedcheckmarkids,
            (bool) optional_param(
                settings::INCLUDE_EXISTING_PRESENTATIONS,
                settings::include_existing_presentations_by_default(),
                PARAM_BOOL
            ),
            $exampleselection,
            $selectedexampleids,
            $examplesperstudent,
            optional_param(self::PREVIEW_DATA, '', PARAM_RAW)
        );
    }

    /**
     * Return a stable fingerprint for the current preview-affecting settings.
     *
     * @return string
     */
    public function get_fingerprint(): string {
        return sha1(json_encode([
            'checkmarkselection' => $this->checkmarkselection,
            'selectedcheckmarkids' => $this->selectedcheckmarkids,
            'includeexistingpresentations' => $this->includeexistingpresentations,
            'exampleselection' => $this->exampleselection,
            'selectedexampleids' => $this->selectedexampleids,
            'examplesperstudent' => $this->examplesperstudent,
        ]));
    }

    /**
     * Return ids from option arrays.
     *
     * @param array $options Option arrays containing an id key.
     * @return int[]
     */
    private static function get_ids(array $options): array {
        return array_values(array_map(static fn(array $option): int => (int) $option['id'], $options));
    }

    /**
     * Keep only posted ids that are valid for the current page.
     *
     * @param int[] $postedids Posted ids.
     * @param int[] $availableids Available ids.
     * @return int[]
     */
    private static function filter_posted_ids(array $postedids, array $availableids): array {
        $postedids = array_unique(array_map('intval', $postedids));

        return array_values(array_intersect($availableids, $postedids));
    }
}
