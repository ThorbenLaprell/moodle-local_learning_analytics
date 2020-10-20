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

/**
 * Version info for the Activities report
 *
 * @package     local_learning_analytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lareport_quiz_assign;

defined('MOODLE_INTERNAL') || die();

use local_learning_analytics\local\outputs\plot;
use local_learning_analytics\local\outputs\table;
use local_learning_analytics\report_base;
use context_course;

class query_helper {

    public static function query_quiz(int $courseid): array {
        global $DB;

        $query = <<<SQL
        SELECT
            q.id,
            AVG(qa.sumgrades)/q.sumgrades AS result,
            AVG(case when qa.attempt=1 then qa.sumgrades else NULL end)/q.sumgrades AS firsttryresult,
            COUNT(DISTINCT userid) AS users,
            COUNT(1) AS attempts
        FROM {grade_items} gi
        JOIN {quiz} q
            ON q.id = gi.iteminstance
        JOIN {quiz_attempts} qa
        ON qa.quiz = q.id
            AND qa.state = 'finished'
        WHERE gi.courseid = ?
            AND gi.itemtype = 'mod'
            AND gi.itemmodule = 'quiz'
        GROUP BY q.id
SQL;

        return \local_learning_analytics\demo::data('quiz_assign', 'quiz');
        return $DB->get_records_sql($query, [$courseid]);
    }

    public static function query_assignment(int $courseid): array {
        global $DB;

        $query = <<<SQL
        SELECT
            a.id,
            COUNT(1) handins,
            (AVG(gg.rawgrade)-gg.rawgrademin)/(gi.grademax-gg.rawgrademin) AS grade
        FROM {grade_items} gi
        JOIN {grade_grades} gg
            ON gg.itemid = gi.id
            AND gg.usermodified IS NOT NULL -- filter out entries created by Moodle automatically (INDEX)
            AND gg.rawgrade IS NOT NULL -- filter out non-graded assignments
        JOIN {assign} a
            ON a.id = gi.iteminstance
        WHERE gi.courseid = ?
            AND gi.itemtype = 'mod'
            AND gi.itemmodule = 'assign'
        GROUP BY
            a.id,
            gg.rawgrademin,
            gi.grademax
SQL;

        return \local_learning_analytics\demo::data('quiz_assign', 'assign');
        return $DB->get_records_sql($query, [$courseid]);
    }
}
