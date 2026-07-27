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
 * Field of application data for the Checkmark random selection page.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace checkmark_randomselect;

use mod_checkmark\example;
use renderer_base;

/**
 * Field of application data for the Checkmark random selection page.
 *
 * @package   checkmark_randomselect
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class application {
    /** Form value selecting all examples from the current Checkmark activity. */
    public const EXAMPLE_SELECTION_ALL = 'all';

    /** Form value selecting specific examples from the current Checkmark activity. */
    public const EXAMPLE_SELECTION_SELECTED = 'selected';

    /**
     * Constructor.
     *
     * @param example[] $examples Examples from the current Checkmark activity.
     * @param string $exampleselection Selected example mode.
     * @param int[] $selectedexampleids Selected example ids.
     * @param int $examplesperstudent Selected number of examples per student.
     */
    public function __construct(
        /** @var example[] Examples from the current Checkmark activity. */
        private readonly array $examples,
        /** @var string Selected example mode. */
        private readonly string $exampleselection = self::EXAMPLE_SELECTION_ALL,
        /** @var int[] Selected example ids. */
        private readonly array $selectedexampleids = [],
        /** @var int Selected number of examples per student. */
        private readonly int $examplesperstudent = 1,
    ) {
    }

    /**
     * Create default field of application data for a Checkmark activity.
     *
     * @param object $checkmark Checkmark activity record.
     * @return self
     */
    public static function default_for_checkmark(object $checkmark): self {
        $exampleprefix = $checkmark->exampleprefix ?? false;
        if ($exampleprefix === null) {
            $exampleprefix = false;
        }

        return new self(\checkmark::get_examples_static($checkmark->id, $exampleprefix));
    }

    /**
     * Return examples from the current Checkmark activity.
     *
     * @return example[]
     */
    public function get_examples(): array {
        return $this->examples;
    }

    /**
     * Return example options in the activity order.
     *
     * @return array
     */
    public function get_example_options(): array {
        $options = [];

        foreach ($this->examples as $example) {
            $exampleid = (int) $example->get_id();
            $options[] = [
                'id' => $exampleid,
                'name' => $example->get_name(),
                'label' => $this->get_example_label($example),
                'inputid' => 'checkmark-randomselect-example-' . $exampleid,
                'checked' => $this->exampleselection === self::EXAMPLE_SELECTION_ALL
                    || in_array($exampleid, $this->selectedexampleids, true),
            ];
        }

        return $options;
    }

    /**
     * Export template data.
     *
     * @param renderer_base $output Renderer.
     * @return array Template data.
     */
    public function export_for_template(renderer_base $output): array {
        $exampleoptions = $this->get_example_options();
        $selectedexamplecount = $this->count_selected_examples($exampleoptions);

        return [
            'exampleslabel' => get_string('examplesfromcurrentcheckmark', 'checkmark_randomselect'),
            'exampleshelpicon' => $output->help_icon(
                'examplesfromcurrentcheckmark',
                'checkmark_randomselect'
            ),
            'exampleselectionname' => 'exampleselection',
            'exampleselectionall' => self::EXAMPLE_SELECTION_ALL,
            'exampleselectionallid' => 'checkmark-randomselect-example-selection-all',
            'exampleselectionallchecked' => $this->exampleselection === self::EXAMPLE_SELECTION_ALL,
            'exampleselectionselected' => self::EXAMPLE_SELECTION_SELECTED,
            'exampleselectionselectedid' => 'checkmark-randomselect-example-selection-selected',
            'exampleselectionselectedchecked' => $this->exampleselection === self::EXAMPLE_SELECTION_SELECTED,
            'alllabel' => get_string('all'),
            'selectedlabel' => get_string('selected', 'form'),
            'nonelabel' => get_string('none'),
            'exampleoptions' => $exampleoptions,
            'hasexampleoptions' => !empty($exampleoptions),
            'hasselectedexamples' => $selectedexamplecount > 0,
            'nooptionsmessage' => get_string('noexamplesavailable', 'checkmark_randomselect'),
            'examplesperstudentlabel' => get_string('examplesperstudent', 'checkmark_randomselect'),
            'examplesperstudenthelpicon' => $output->help_icon('examplesperstudent', 'checkmark_randomselect'),
            'examplesperstudentname' => 'examplesperstudent',
            'examplesperstudentoptions' => $this->get_examples_per_student_options($selectedexamplecount),
        ];
    }

    /**
     * Return options for the number of examples per student selector.
     *
     * @param int $examplecount Number of selectable examples.
     * @return array
     */
    private function get_examples_per_student_options(int $examplecount): array {
        $options = [];
        $selectedvalue = min(max($this->examplesperstudent, 1), max($examplecount, 1));

        for ($i = 1; $i <= $examplecount; $i++) {
            $options[] = [
                'value' => $i,
                'label' => $i,
                'selected' => $i === $selectedvalue,
            ];
        }

        return $options;
    }

    /**
     * Count currently selected examples.
     *
     * @param array $exampleoptions Exported example options.
     * @return int
     */
    private function count_selected_examples(array $exampleoptions): int {
        if ($this->exampleselection === self::EXAMPLE_SELECTION_ALL) {
            return count($exampleoptions);
        }

        return count(array_filter($exampleoptions, static fn(array $option): bool => !empty($option['checked'])));
    }

    /**
     * Return the example label including points.
     *
     * @param example $example Example object.
     * @return string
     */
    private function get_example_label(example $example): string {
        $grade = (float) $example->get_grade();
        $a = (object) [
            'name' => $example->get_name(),
            'points' => format_float($grade, 2, true, true),
            'pointsstring' => example::get_static_pointstring($grade),
        ];

        return get_string('examplewithpoints', 'checkmark_randomselect', $a);
    }
}
