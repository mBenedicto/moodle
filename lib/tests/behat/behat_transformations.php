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
 * Behat arguments transformations.
 *
 * This methods are used by Behat CLI command.
 *
 * @package    core
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;

/**
 * Transformations to apply to steps arguments.
 *
 * This methods are applied to the steps arguments that matches
 * the regular expressions specified in the @Transform tag.
 *
 * @package   core
 * @category  test
 * @copyright 2013 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_transformations extends behat_base {

    /**
     * Removes escaped argument delimiters.
     *
     * We use double quotes as arguments delimiters and
     * to add the " as part of an argument we escape it
     * with a backslash, this method removes this backslash.
     *
     * @Transform /^((.*)"(.*))$/
     * @param string $string
     * @return string The string with the arguments fixed.
     */
    public function arg_replace_slashes($string) {
        return $this->replace_slashes($string);
    }

    /**
     * Replaces $NASTYSTRING vars for a nasty string.
     *
     * @Transform /^((.*)\$NASTYSTRING(\d)(.*))$/
     * @param string $argument The whole argument value.
     * @return string
     */
    public function arg_replace_nasty_strings($argument) {
        return $this->replace_nasty_strings($argument);
    }

    /**
     * Transformations for TableNode arguments.
     *
     * All the transformations have to be applied to tables,
     * adding them in a different method for Behat API restrictions.
     *
     * @Transform /^table:(.*)/
     * @param TableNode $tablenode
     * @return TableNode The transformed table
     */
    public function tablenode_transformations(TableNode $tablenode) {

        // Walk through all values including the optional headers.
        $rows = $tablenode->getRows();
        foreach ($rows as $rowkey => $row) {
            foreach ($row as $colkey => $value) {

                // TableNodes values doesn't need to be escaped, but maybe somebody does it.
                $rows[$rowkey][$colkey] = $this->replace_slashes($value);

                // Transforms vars into nasty strings.
                if (preg_match('/\$NASTYSTRING(\d)/', $rows[$rowkey][$colkey])) {
                    $rows[$rowkey][$colkey] = $this->replace_nasty_strings($rows[$rowkey][$colkey]);
                }
            }
        }

        // Return the transformed TableNode.
        $tablenode->setRows($rows);
        return $tablenode;
    }

    /**
     * Removes the escaped double quotes.
     *
     * Method reused by TableNode transformation.
     *
     * @param string $string
     * @return string
     */
    public function replace_slashes($string) {
        return str_replace('\"', '"', $string);
    }

    /**
     * Replaces $NASTYSTRING vars for a nasty string.
     *
     * Method reused by TableNode tranformation.
     *
     * @param string $string
     * @return string
     */
    public function replace_nasty_strings($string) {
        return preg_replace_callback(
            '/\$NASTYSTRING(\d)/',
            function ($matches) {
                return nasty_strings::get($matches[0]);
            },
            $string
        );
    }

}
