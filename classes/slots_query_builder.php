<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/
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
 * Slots query builder.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler;

use coding_exception;
use core\dml\sql_join;
use mod_scheduler\model\scheduler;

/**
 * Slots query builder.
 *
 * Simple builder for preparing a query of slots.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slots_query_builder {

    /** All slots. */
    const TIMERANGE_ALL = 0;
    /** Future slots. */
    const TIMERANGE_FUTURE = 1;
    /** Past slots. */
    const TIMERANGE_PAST = 2;

    /** On a specific date, discarding the time. */
    const OPERATOR_ON = 0;
    /** Before the date and time. */
    const OPERATOR_BEFORE = 1;
    /** After the date and time. */
    const OPERATOR_AFTER = 2;
    /** At the exact date and time. */
    const OPERATOR_AT = 3;
    /** Between two times. */
    const OPERATOR_BETWEEN = 4;

    /** @var int|null The group ID. */
    protected $groupid = 0;
    /** @var sql_join An array of joins. */
    protected $joins = [];
    /** @var int Limit offset. */
    protected $limitfrom = 0;
    /** @var int Limit quantity. */
    protected $limitnum = 0;
    /** @var array[] Array of column names and direction. */
    protected $orderby = [];
    /** @var mixed[] A list of parameters. */
    protected $params = [];
    /** @var string The slot table prefix. */
    protected $prefix = '';
    /** @var int|null The teacher ID. */
    protected $teacherid = 0;
    /** @var int Timerange constant. */
    protected $timerange = self::TIMERANGE_ALL;
    /** @var string[] A list where conditions. */
    protected $wheres = [];

    /**
     * Constructor.
     *
     * @param string $prefix The slot table prefix.
     */
    public function __construct($prefix = 's.') {
        $this->prefix = $prefix;
    }

    /**
     * Add order.
     *
     * The last call to this method sets the most significant column.
     *
     * @param string $field The order field.
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     */
    public function add_order_by($field, $dir = SORT_ASC) {
        $entry = [$this->prefix . $field, $dir];
        $this->orderby = array_merge([$entry], $this->orderby);
    }

    /**
     * Add order by teacher.
     *
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     */
    public function add_order_by_teacher($dir = SORT_ASC) {
        $this->joins['sortbyteacher'] = new sql_join("JOIN {user} t ON t.id = {$this->prefix}teacherid");
        $entry = ['t.lastname', $dir];
        $this->orderby = array_merge([$entry], $this->orderby);
    }

    /**
     * Return a clone of the builder.
     *
     * @return self
     */
    public function clone() {
        $clone = new self($this->prefix);
        $clone->groupid = $this->groupid;
        $clone->joins = $this->joins;
        $clone->limitfrom = $this->limitfrom;
        $clone->limitnum = $this->limitnum;
        $clone->orderby = $this->orderby;
        $clone->teacherid = $this->teacherid;
        $clone->timerange = $this->timerange;
        $clone->params = $this->params;
        $clone->wheres = $this->wheres;
        return $clone;
    }

    /**
     * Filter by location.
     *
     * @param string $query The string to match.
     * @return void
     */
    public function filter_location($query) {
        global $DB;
        if (empty($query)) {
            unset($this->wheres['filterlocation']);
            unset($this->params['filterlocation']);
            return;
        }
        $this->wheres['filterlocation'] = $DB->sql_like($this->prefix . 'appointmentlocation', ':filterlocation', false);
        $this->params['filterlocation'] = '%' . $DB->sql_like_escape($query) . '%';
    }

    /**
     * Filter by starttime.
     *
     * @param string $timestamp The timestamp.
     * @param int $operator The operator constant.
     * @param int $timestampend The second timestamp when OPERATOR_BETWEEN.
     * @return void
     */
    public function filter_starttime($timestamp, $operator = self::OPERATOR_ON, $timestampend = 0) {
        $timestamp = (int) $timestamp;
        $timestampend = (int) $timestampend;

        if (empty($timestamp)) {
            unset($this->wheres['filterstarttime']);
            unset($this->params['filterstarttime']);
            unset($this->params['filterstarttimeend']);
            return;
        }

        $sql = '1=1';
        $params = ['filterstarttime' => $timestamp];

        // Convert the operator ON to BETWEEN.
        if ($operator === self::OPERATOR_ON) {
            $operator = self::OPERATOR_BETWEEN;
            $timestamp = usergetmidnight($timestamp);
            $timestampend = $timestamp + DAYSECS;
        }

        switch ($operator) {
            case self::OPERATOR_AT:
                $sql = "{$this->prefix}starttime = :filterstarttime";
                break;
            case self::OPERATOR_AFTER:
                $sql = "{$this->prefix}starttime > :filterstarttime";
                break;
            case self::OPERATOR_BEFORE:
                $sql = "{$this->prefix}starttime < :filterstarttime";
                break;
            case self::OPERATOR_BETWEEN:
                $sql = "{$this->prefix}starttime > :filterstarttime AND {$this->prefix}starttime < :filterstarttimeend";
                $params = [
                    'filterstarttime' => $timestamp,
                    'filterstarttimeend' => $timestampend
                ];
                break;
            default:
                throw new coding_exception('Unexpected operator');
        }

        $this->wheres['filterstarttime'] = $sql;
        $this->params = array_merge($this->params, $params);
    }

    /**
     * Get the joins.
     *
     * @return string
     */
    public function get_joins() {
        return implode(' ', array_map(function($join) {
            return $join->joins;
        }, $this->joins));
    }

    /**
     * Get the limit.
     *
     * @return array With amount and offset.
     */
    public function get_limit() {
        return [$this->limitnum, $this->limitfrom];
    }

    /**
     * Get order by.
     *
     * @return string
     */
    public function get_order_by() {
        if (empty($this->orderby)) {
            return '';
        }
        return implode(', ', array_map(function($order) {
            return $order[0] . ' ' . ($order[1] === SORT_ASC ? 'ASC' : 'DESC');
        }, $this->orderby));
    }

    /**
     * Get where fragment.
     *
     * @return string
     */
    public function get_where() {
        $wheres = $this->wheres;
        $params = $this->params;

        if ($this->teacherid) {
            $wheres[] = "{$this->prefix}teacherid = :paramtid";
            $params['paramtid'] = $this->teacherid;
        }

        if ($this->groupid) {
            $wheres[] = "EXISTS (SELECT 1
                                   FROM {groups_members} gm
                                  WHERE gm.groupid = :paramgid
                                    AND gm.userid = {$this->prefix}teacherid)";
            $params['paramgid'] = $this->groupid;
        }

        if ($this->timerange === static::TIMERANGE_PAST) {
            $wheres[] = "s.starttime < :paramtimerange";
            $params['paramtimerange'] = time();

        } else if ($this->timerange === static::TIMERANGE_FUTURE) {
            $wheres[] = "s.starttime >= :paramtimerange";
            $params['paramtimerange'] = time();
        }

        foreach ($this->joins as $join) {
            $where[] = '(' . $join->wheres . ')';
            $params = array_merge($params, $join->params);
        }

        $where = implode(' AND ', $wheres);
        return [$where, $params];
    }

    /**
     * Reset the current order by.
     *
     * @return void
     */
    public function reset_order_by() {
        $this->orderby = [];
        unset($this->joins['sortbyteacher']);
    }

    /**
     * Set the group ID.
     *
     * @param int $groupid The group ID.
     */
    public function set_groupid($groupid) {
        $this->groupid = empty($groupid) ? 0 : (int) $groupid;
    }

    /**
     * Set the teacher ID.
     *
     * @param int $teacherid The teacher ID.
     */
    public function set_teacherid($teacherid) {
        $this->teacherid = empty($teacherid) ? 0 : (int) $teacherid;
    }

    /**
     * Set the desired time range.
     *
     * @param int $rangetype Constant TIMERANGE_.
     */
    public function set_timerange($rangetype) {
        $this->timerange = $rangetype;
    }

    /**
     * Set the limit.
     *
     * @param int $limit The quantity.
     * @param int $offset The offset.
     */
    public function set_limit($limit, $offset = 0) {
        $this->limitnum = max(0, (int) $limit);
        $this->limitfrom = max(0, (int) $offset);
    }

}
