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

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/classes/performance_measure.php');

/**
 * Behat step definitions to measure performance.
 */
class behat_performance extends behat_base {

    /**
     * @var array
     */
    private $measures = [];

    /**
     * Start measuring performance.
     *
     * @When /^I start measuring "([^"]+)"$/
     */
    public function i_start_measuring(string $name) {
        $this->measures[$name] = new performance_measure($name, $this->getSession()->getDriver());
        $this->measures[$name]->start();
    }

    /**
     * Stop measuring performance.
     *
     * @When /^I stop measuring "([^"]+)"$/
     */
    public function i_stop_measuring(string $name) {
        $this->get_performance_measure($name)->end();
    }

    /**
     * Assert how long a performance measure took.
     *
     * @Then /^"([^"]+)" should have taken (less than|more than|exactly) (\d+(?:\.\d+)? (?:seconds|milliseconds))$/
     */
    public function timing_should_have_taken(string $measure, Closure $comparison, float $expectedtime) {
        $measuretiming = $this->get_performance_measure($measure);

        if (!call_user_func($comparison, $measuretiming->duration, $expectedtime)) {
            throw new ExpectationException(
                "Expected duration for '$measure' failed! (took {$measuretiming->duration}ms)",
                $this->getSession()->getDriver()
            );
        }

        $measuretiming->store();
    }

    /**
     * Parse time.
     *
     * @Transform /^\d+(?:\.\d+)? (?:seconds|milliseconds)$/
     * @param string $text Time string.
     * @return float
     */
    public function parse_time(string $text): float {
        $spaceindex = strpos($text, ' ');
        $value = floatval(substr($text, 0, $spaceindex));

        switch (substr($text, $spaceindex + 1)) {
            case 'seconds':
                $value *= 1000;
                break;
        }

        return $value;
    }

    /**
     * Parse a comparison function.
     *
     * @Transform /^less than|more than|exactly$/
     * @param string $text Comparison string.
     * @return Closure
     */
    public function parse_comparison(string $text): Closure {
        switch ($text) {
            case 'less than':
                return function ($a, $b) {
                    return $a < $b;
                };
            case 'more than':
                return function ($a, $b) {
                    return $a > $b;
                };
            case 'exactly':
                return function ($a, $b) {
                    return $a === $b;
                };
        }
    }

    /**
     * Get performance measure by name.
     *
     * @param string $name Performance measure name.
     * @return performance_measure Performance measure.
     */
    private function get_performance_measure(string $name): performance_measure {
        if (!isset($this->measures[$name])) {
            throw new DriverException("'$name' performance measure does not exist.");
        }

        return $this->measures[$name];
    }

}
