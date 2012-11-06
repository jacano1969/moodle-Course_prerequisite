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
 * Keeps track of upgrades to the course prerequisites block
 *
 * @package    blocks
 * @subpackage course_prerequisites 
 * @copyright  2011 Enovation Solutions Ltd. 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_course_prerequisites extends block_base {

    private $courseid;

    function init() {
        global $COURSE;

        $this->title = get_string('course_prerequisites_title', 'block_course_prerequisites');
        $this->courseid = $COURSE->id;        
    }

    function applicable_formats() {
        return array('course' => true);
    }

    private function getPreRequisiteCourses() {
        global $DB;

        if (!$this->courseid || $this->courseid == SITEID) return array();
        if (!$prereqs = $DB->get_records("course_availability", array("courseid"=>$this->courseid)) ) return array();
        
        $courses = array();
        foreach ($prereqs as $ca) {
            $courses[$ca->sourcecourseid] = $DB->get_record("course", array("id"=>$ca->sourcecourseid));
        }       
        return $courses;
    }

    private function getPostRequisiteCourses() {
        global $DB;

        if (!$this->courseid || $this->courseid == SITEID) return array();
        if (!$prereqs = $DB->get_records("course_availability", array("sourcecourseid"=>$this->courseid)) ) return array();

        $courses = array();
        foreach ($prereqs as $ca) {
            $courses[$ca->courseid] = $DB->get_record("course", array("id"=>$ca->courseid));
        }      
        return $courses;
    }

    function get_content() {
        global $CFG, $OUTPUT, $COURSE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $courseid = $COURSE->id;
        if ($courseid <= 0) {
            $courseid = SITEID;
        }

        $prereqs = $this->getPreRequisiteCourses();
        $postreqs = $this->getPostRequisiteCourses();

        $this->content = new stdClass;
        $this->content->text = '';

        //show the courses which are pre-requisite to this one
        if (sizeof($prereqs) > 0) {
            $this->content->text .= "<h4>".get_string('course_prerequisites', 'block_course_prerequisites', $COURSE->shortname)."</h4>\n<ul>\n"; 
            foreach ( $prereqs as $courseid=>$course) {
                $courseurl = new moodle_url('/course/view.php?id='.$course->id);
                $this->content->text .= "<li><a href=\"{$courseurl}\">{$course->shortname}</a></li>\n";
            }
            $this->content->text .= "</ul>\n";
        }

        //show the courses this course is a pre-requisite for
        if (sizeof($postreqs) > 0) {
            $this->content->text .= "<h4>".get_string('course_is_prerequisite_for', 'block_course_prerequisites', $COURSE->shortname)."</h4><ul>";
            foreach ( $postreqs as $courseid=>$course) {
                $courseurl = new moodle_url('/course/view.php?id='.$course->id);
                $this->content->text .= "<li><a href=\"{$courseurl}\">{$course->shortname}</a></li>\n";
            }
            $this->content->text .= "</ul>\n";
        }

        $this->content->footer = '';


        if (empty($this->instance->pageid)) {
            $this->instance->pageid = SITEID;
        }


        return $this->content;
    }
}
