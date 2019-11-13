<?php

/*
 * This file is part of the CSU Course Reserves App
 *
 * (c) California State University <library@calstate.edu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Reserves;

use Alma\Courses\Citation;
use Alma\Courses\Course;
use Alma\Courses\Metadata;
use Twig_Extension;
use Twig_SimpleFilter;

/**
 * Formatting filters for Twig
 *
 * @author dwalker
 */
class Formatter extends Twig_Extension
{
    /**
     * {@inheritDoc}
     * @see Twig_Extension::getFunctions()
     */
    public function getFilters()
    {
        return array(
            new Twig_SimpleFilter('course_name', array($this, 'getCourseName')),
            new Twig_SimpleFilter('instructors', array($this, 'getInstructors')),
            new Twig_SimpleFilter('title', array($this, 'getTitle')),
            new Twig_SimpleFilter('edition', array($this, 'getEdition')),
            new Twig_SimpleFilter('publication_info', array($this, 'getPublicationInfo')),
            new Twig_SimpleFilter('mmsid', array($this, 'getMmsId')),
            new Twig_SimpleFilter('openurl', array($this, 'getOpenUrl')),
        );
    }

    /**
     * Course code and name for display
     *
     * @param Course $course
     * @return string
     */
    public function getCourseName(Course $course)
    {
        $code = $course->getCode();
        $name = $course->getName();
        $section = $course->getSection();

        // if there is a code, suffix it with colon

        if ( $code != "" ) {
            $code .= ': ';
        }

        if ( $section != "" ) {
            $section .= ': ';
        }

        return trim("<span class='course-code'>$code</span>" .
                    "<span class='course-section'>$section</span>" .
                    "<span class='course-name'>$name</span>");
    }

    /**
     * Instructors in collapsed list
     *
     * @param Course $course
     * @return string
     */
    public function getInstructors(Course $course)
    {
        // instructor assigned to course

    	$inst = array();

    	foreach ($course->getInstructors() as $instructor) {
    		$inst[] = $instructor->getLastName();
    	}

    	if (count($inst) > 0) {
    	    return trim(implode("; ", $inst));
    	}

    	// instructor brought over as note, during migration

    	foreach ($course->getNotes() as $note) {
    	    $content = $note->getContent();

    	    if (strstr($content, 'Instructor:')) {
    	        return trim(str_replace('Instructor:', '', $content));
    	    } elseif (strstr($content, 'PROF_TA:')) {
    	        return trim(str_replace('PROF_TA:', '', $content));
    	    }
    	}

    	// nada
    	return null;
    }

    /**
     * Title formatted for display
     *
     * Show title, remove statement of responsibility
     *
     * @param Metadata $metadata
     * @return string
     */
    public function getTitle(Metadata $metadata)
    {
        $title = $metadata->getTitle();
        $jtitle = $metadata->getJournalTitle();

        if ($title == ""  && $jtitle != "") {
            $title = $jtitle;
        }

        if (strstr($title, '/')) {
            $parts = explode('/', $title);
            array_pop($parts);
            $title = implode('/', $parts);
        }

        return $title;
    }

    /**
     * Edition formatted for display
     *
     * Add 'ed.' where needed
     *
     * @param Metadata $metadata
     * @return string
     */
    public function getEdition(Metadata $metadata)
    {
        $edition = $metadata->getEdition();
        $edition = trim($edition);

        // just a number, so add suffix
        if ( is_numeric($edition)) {
            if ($edition == '1') {
                $edition .= 'st';
            } elseif ($edition == '2') {
                $edition .= 'nd';
            } elseif ($edition == '3') {
                $edition .= 'rd';
            } else {
                $edition .= 'th';
            }
        }

        // check for edition number but not the word edition
        if (!stristr($edition, 'ed') && (stristr($edition, 'st') || stristr($edition, 'nd') ||
            stristr($edition, 'rd') || stristr($edition, 'th'))) {
            $edition .= " ed.";
        }

        return $edition;
    }

    /**
     * Publication information
     *
     * Put together place, publisher, and date
     *
     * @param Metadata $metadata
     * @return string
     */
    public function getPublicationInfo(Metadata $metadata)
    {
        $final = "";
        $place = $metadata->getPlaceOfPublication();
        $publisher = $metadata->getPublisher();
        $date = $metadata->getPublicationDate();

        if ($publisher != "") {
            $final = $place . ' ' . $publisher;

            if ($date != "") {
                $final .= ', ' . $date;
            }
        } elseif ($date != "") {
            return $date;
        }

        return $final;
    }

    /**
     * Workaround to get MMS_ID
     *
     * @param Metadata $metadata
     * @return string
     */
    public function getMmsId(Metadata $metadata)
    {
        return (string) $metadata->json()->mms_id;
    }

    /**
     * Workaround to get https OpenURL
     *
     * @param Citation $citation
     * @return string
     */
    public function getOpenUrl(Citation $citation)
    {
        $url = $citation->getOpenUrl();
        $url = str_replace('http://', 'https://', $url);

        return $url;
    }
}
