<?php

declare(strict_types=1);

namespace Superclasses;

use DateTime;
use DateTimeZone;

/**
 * This class is part of the Superclasses library, and provides some useful methods for working with
 * date-times in PHP.
 */
class DateTimeUtilities
{
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Constants

    // These values are exact, based on average Gregorian calendar month and year lengths.
    public const SECONDS_PER_MINUTE  = 60;
    public const SECONDS_PER_HOUR    = 3600;
    public const SECONDS_PER_DAY     = 86400;
    public const SECONDS_PER_WEEK    = 604800;
    public const SECONDS_PER_MONTH   = 2629746;
    public const SECONDS_PER_YEAR    = 31556952;

    public const MINUTES_PER_HOUR    = 60;
    public const MINUTES_PER_DAY     = 1440;
    public const MINUTES_PER_WEEK    = 10080;
    public const MINUTES_PER_MONTH   = 43829.1;
    public const MINUTES_PER_YEAR    = 525949.2;

    public const HOURS_PER_DAY       = 24;
    public const HOURS_PER_WEEK      = 168;
    public const HOURS_PER_MONTH     = 730.485;
    public const HOURS_PER_YEAR      = 8765.82;

    public const DAYS_PER_WEEK       = 7;
    public const DAYS_PER_MONTH      = 30.436875;
    public const DAYS_PER_YEAR       = 365.2425;

    public const WEEKS_PER_MONTH     = 4.348125;
    public const WEEKS_PER_YEAR      = 52.1775;

    public const MONTHS_PER_YEAR     = 12;

    // Formats
    public const MYSQL = 'Y-m-d H:i:s';

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Static methods

    /**
     * The current datetime as a DateTime object.
     *
     * @return DateTime
     */
    public static function now()
    {
        return new DateTime();
    }

    /**
     * The current datetime in the UTC timezone, as an DateTime object.
     *
     * @return DateTime
     */
    public static function nowUTC()
    {
        // This will call the parent constructor, which defaults to 'now'.
        return new self(null, 'UTC');
    }

    /**
     * Today's date as an DateTime object.
     *
     * @return DateTime
     */
    public static function today()
    {
        $now = self::now();
        return $now->date();
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Constructor

    /**
     * Constructor for making dates and datetimes.
     *
     * Time zones may be provided as DateTimeZone objects, or as timezone strings.
     * The $timezone parameter and the current timezone are ignored when the $time parameter either is a UNIX
     * timestamp (e.g. @946684800 or 946684800) or specifies a timezone (e.g. 2010-01-28T15:00:00+02:00).
     * @see http://php.net/manual/en/datetime.construct.php
     *
     * All arguments are optional.
     *
     * Usage examples:
     *    $dt = new DateTime();
     *    $dt = new DateTime($unix_timestamp);
     *    $dt = new DateTime($datetime_string);
     *    $dt = new DateTime($datetime_string, $timezone);
     *    $dt = new DateTime($year, $month, $day);
     *    $dt = new DateTime($year, $month, $day, $timezone);
     *    $dt = new DateTime($year, $month, $day, $hour, $minute, $second);
     *    $dt = new DateTime($year, $month, $day, $hour, $minute, $second, $timezone);
     *
     * @param string|int                    $year, $unix_timestamp or $datetime_string
     * @param null|DateTimeZone|string|int  $month or $timezone
     * @param int                           $day
     * @param null|DateTimeZone|string|int  $hour or $timezone
     * @param int                           $minute
     * @param int                           $second
     * @param null|DateTimeZone|string      $timezone
     */
    public function __construct()
    {
        // All arguments are optional and several serve multiple roles, so it's simpler not to include parameters in the
        // function signature, and instead just grab them as follows:
        $n_args = func_num_args();
        $args = func_get_args();

        // Default timezone:
        $timezone = null;

        if ($n_args == 0) {
            // Now:
            $datetime = 'now';
        } elseif ($n_args == 1 && is_numeric($args[0])) {
            // Unix timestamp:
            $datetime = '@' . $args[0];
        } elseif ($n_args <= 2) {
            // Args are assumed to be: $datetime, [$timezone], as for the DateTime constructor.
            $datetime = $args[0];
            $timezone = isset($args[1]) ? $args[1] : null;
        } elseif ($n_args <= 4) {
            // Args are assumed to be: $year, $month, $day, [$timezone].
            $date = self::zeroPad($args[0], 4) . '-' . self::zeroPad($args[1]) . '-' . self::zeroPad($args[2]);
            $time = '00:00:00';
            $datetime = "$date $time";
            $timezone = isset($args[3]) ? $args[3] : null;
        } elseif ($n_args >= 6 && $n_args <= 7) {
            // Args are assumed to be: $year, $month, $day, [$timezone].
            $date = self::zeroPad($args[0], 4) . '-' . self::zeroPad($args[1]) . '-' . self::zeroPad($args[2]);
            $time = self::zeroPad($args[3]) . ':' . self::zeroPad($args[4]) . ':' . self::zeroPad($args[5]);
            $datetime = "$date $time";
            $timezone = isset($args[6]) ? $args[6] : null;
        } else {
            trigger_error("DateTime::__construct() - Invalid number of paremeters.", E_USER_WARNING);
        }

        // Support string timezones:
        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }

        // Check we have a valid timezone:
        if ($timezone !== null && !($timezone instanceof DateTimeZone)) {
            trigger_error("DateTime::__construct() - Invalid timezone.", E_USER_WARNING);
        }

        // Call parent constructor:
        parent::__construct($datetime, $timezone);
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Formatting

    /**
     * Pads a number with '0' characters up to a specified width.
     *
     * @param int $n
     * @param int $w
     * @return string
     */
    protected static function zeroPad($n, $w = 2)
    {
        return str_pad((string)$n, $w, '0', STR_PAD_LEFT);
    }

    /**
     * Convert the datetime to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format('Y-m-d H:i:s P e');
    }

    /**
     * Format the datetime in ISO format, without timezone.
     * Suitable for MySQL DATETIME columns.
     *
     * @return string
     */
    public function iso()
    {
        return $this->format(self::ATOM);
    }

    /**
     * Format the datetime suitable for MySQL DATETIME columns.
     * No timezone information is included in the format string, so you may want to set the timezone beforehand.
     *
     * @return string
     */
    public function mysql()
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * Sets the timezone to UTC and formats the datetime suitable for MySQL DATETIME columns.
     *
     * @return string
     */
    public function mysqlUTC()
    {
        return $this->timezone('UTC')->mysql();
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Date and time parts

    /**
     * Gets or sets the date.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return DateTime
     */
    public function date($year = 1, $month = 1, $day = 1)
    {
        if (func_num_args() == 0) {
            // Get the date:
            return new self($this->format('Y-m-d'));
        } else {
            // Set the date:
            return $this->setDate($year, $month, $day);
        }
    }

    /**
     * Gets or sets the time.
     *
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @return Time|DateTime
     */
    public function time($hour = 0, $minute = 0, $second = 0)
    {
        if (func_num_args() == 0) {
            // Get the time:
            return new Time($this->hour(), $this->minute(), $this->second());
        } else {
            // Set the time:
            return $this->setTime($hour, $minute, $second);
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Timestamp

    /**
     * Get/set the timestamp.
     *
     * @param null|int
     * @return int
     */
    public function timestamp($ts = null)
    {
        if ($ts === null) {
            return $this->getTimestamp();
        } else {
            return $this->setTimestamp($ts);
        }
    }

    /**
     * Convert a value into a Unix timestamp.
     *
     * @param int|string|DateTime $datetime
     * @return int
     */
    public static function toTimestamp($datetime)
    {
        if (is_int($datetime)) {
            // Assume parameter is a Unix timestamp:
            return $datetime;
        } elseif (is_string($datetime)) {
            // Assume parameter is a valid datetime timestamp:
            $ts = strtotime($datetime);
            if ($ts !== false) {
                return $ts;
            }
        } elseif ($datetime instanceof \DateTime) {
            // Parameter is a \DateTime object:
            return $datetime->getTimestamp();
        }
        trigger_error("DateTime::toTimestamp() - Invalid parameter.", E_USER_WARNING);
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Timezone

    /**
     * Get/set the timezone.
     *
     * @param null|string|DateTimeZone $tz
     * @return DateTimeZone
     */
    public function timezone($tz = null)
    {
        if ($tz === null) {
            // Get the timezone:
            return $this->getTimezone();
        } else {
            // Set the timezone:
            if (is_string($tz)) {
                $tz = new DateTimeZone($tz);
            }
            return $this->setTimezone($tz);
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Standard datetime parts

    /**
     * Get or set the year.
     *
     * @param int $year
     * @return int|DateTime
     */
    public function year($year = 1)
    {
        if (func_num_args() == 0) {
            // Get the year:
            return (int) $this->format('Y');
        } else {
            // Set the year:
            return $this->date($year, $this->month(), $this->day());
        }
    }

    /**
     * Get or set the month.
     *
     * @param int $month
     * @return int|DateTime
     */
    public function month($month = 1)
    {
        if (func_num_args() == 0) {
            // Get the month:
            return (int) $this->format('n');
        } else {
            // Set the month:
            return $this->date($this->year(), $month, $this->day());
        }
    }

    /**
     * Get or set the day of the month.
     *
     * @param int $day
     * @return int|DateTime
     */
    public function day($day = 1)
    {
        if (func_num_args() == 0) {
            // Get the day of the month:
            return (int) $this->format('j');
        } else {
            // Set the day of the month:
            return $this->date($this->year(), $this->month(), $day);
        }
    }

    /**
     * Get or set the hour.
     *
     * @param int $hour
     * @return int|DateTime
     */
    public function hour($hour = 0)
    {
        if (func_num_args() == 0) {
            // Get the hour:
            return (int) $this->format('G');
        } else {
            // Set the hour:
            return $this->time($hour, $this->minute(), $this->second());
        }
    }

    /**
     * Get or set the minute.
     *
     * @param int $minute
     * @return int|DateTime
     */
    public function minute($minute = 0)
    {
        if (func_num_args() == 0) {
            // Get the minute:
            return (int) $this->format('i');
        } else {
            // Set the minute:
            return $this->time($this->hour(), $minute, $this->second());
        }
    }

    /**
     * Get or set the second.
     *
     * @param int $second
     * @return int|DateTime
     */
    public function second($second = 0)
    {
        if (func_num_args() == 0) {
            // Get the second:
            return (int) $this->format('s');
        } else {
            // Set the second:
            return $this->time($this->hour(), $this->minute(), $second);
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Additional handy getters.

    /**
     * Get the week of the year as an integer (1.. 52).
     *
     * @return int
     */
    public function week()
    {
        return (int) $this->format('W');
    }

    /**
     * Get the day of the year as an integer (1..366).
     *
     * @return int
     */
    public function dayOfYear()
    {
        return ((int) $this->format('z')) + 1;
    }

    /**
     * Get the day of the week as an integer (1..7).
     * 1 = Monday .. 7 = Sunday
     *
     * @return int
     */
    public function dayOfWeek()
    {
        return (int) $this->format('N');
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Add periods. These methods return a new DateTime object; they don't modify the calling object.

    /**
     * Add years.
     *
     * @param int $years
     * @return DateTime
     */
    public function addYears($years)
    {
        $dt = clone $this;
        return $dt->year($dt->year() + $years);
    }

    /**
     * Add months.
     *
     * @param int $months
     * @return DateTime
     */
    public function addMonths($months)
    {
        $dt = clone $this;
        return $dt->month($dt->month() + $months);
    }

    /**
     * Add weeks.
     *
     * @param int $weeks
     * @return DateTime
     */
    public function addWeeks($weeks)
    {
        return $this->addDays($weeks * 7);
    }

    /**
     * Add days.
     *
     * @param int $days
     * @return DateTime
     */
    public function addDays($days)
    {
        $dt = clone $this;
        return $dt->day($dt->day() + $days);
    }

    /**
     * Add hours.
     *
     * @param int $hours
     * @return DateTime
     */
    public function addHours($hours)
    {
        $dt = clone $this;
        return $dt->hour($dt->hour() + $hours);
    }

    /**
     * Add minutes.
     *
     * @param int $minutes
     * @return DateTime
     */
    public function addMinutes($minutes)
    {
        $dt = clone $this;
        return $dt->minute($dt->minute() + $minutes);
    }

    /**
     * Add seconds.
     *
     * @param int $seconds
     * @return DateTime
     */
    public function addSeconds($seconds)
    {
        $dt = clone $this;
        return $dt->second($dt->second() + $seconds);
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Subtract periods. These methods return a new DateTime object; they don't modify the calling object.

    /**
     * Subtract years.
     *
     * @param int $years
     * @return DateTime
     */
    public function subYears($years)
    {
        return $this->addYears(-$years);
    }

    /**
     * Subtract months.
     *
     * @param int $months
     * @return DateTime
     */
    public function subMonths($months)
    {
        return $this->addMonths(-$months);
    }

    /**
     * Subtract weeks.
     *
     * @param int $weeks
     * @return DateTime
     */
    public function subWeeks($weeks)
    {
        return $this->addWeeks(-$weeks);
    }

    /**
     * Subtract days.
     *
     * @param int $days
     * @return DateTime
     */
    public function subDays($days)
    {
        return $this->addDays(-$days);
    }

    /**
     * Subtract hours.
     *
     * @param int $hours
     * @return DateTime
     */
    public function subHours($hours)
    {
        return $this->addHours(-$hours);
    }

    /**
     * Subtract minutes.
     *
     * @param int $minutes
     * @return DateTime
     */
    public function subMinutes($minutes)
    {
        return $this->addMinutes(-$minutes);
    }

    /**
     * Subtract seconds.
     *
     * @param int $seconds
     * @return DateTime
     */
    public function subSeconds($seconds)
    {
        return $this->addSeconds(-$seconds);
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Day counts

    /**
     * Calculate the Julian Day for the datetime.
     *
     * @return float
     */
    public function jd()
    {
        $d = $this->day();
        $m = $this->month();
        $y = $this->year();
        $s = $this->time()->days();
        return (367 * $y) - floor(7 * ($y + floor(($m + 9) / 12)) / 4) -
          floor(3 * (floor(($y + ($m - 9) / 7) / 100) + 1) / 4) +
          floor(275 * $m / 9) + $d + 1721028.5 + $s;
    }

    /**
     * Calculate the Modified Julian Day for the date part of the datetime.
     *
     * @return int
     */
    public function mjd()
    {
        return $this->jd() - 2400000.5;
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Miscellaneous

    /**
     * Clamp the year to a specified range.
     * Either min or max or neither or both can be specified.
     *
     * @param int|null $min_year
     * @param int|null $max_year
     */
    public function clampYear($min_year = null, $max_year = null)
    {
        // Clamp to min year, if specified:
        if ($min_year !== null && $this->year() < $min_year) {
            $this->year($min_year);
        }
        // Clamp to max year, if specified:
        if ($max_year !== null && $this->year() > $max_year) {
            $this->year($max_year);
        }
    }

    /**
     * Generates a string describing about how long ago a datetime was.
     *
     * @return string
     */
    public function aboutHowLongAgo()
    {
        $ts = $this->timestamp();
        $now = time();

        // Get the time difference in seconds:
        $seconds = $now - $ts;

        // Check time is in the past:
        if ($seconds < 0) {
            trigger_error("DateTime::aboutHowLongAgo() - Datetimes must be in the past.", E_USER_WARNING);
            return false;
        }

        // Now:
        if ($seconds == 0) {
            return 'now';
        }

        // Seconds:
        if ($seconds <= 20) {
            return $seconds == 1 ? 'a second' : "$seconds seconds";
        }

        // 5 seconds:
        if ($seconds < 58) {
            return (round($seconds / 5) * 5) . ' seconds';
        }

        // Minutes:
        $minutes = round($seconds / self::SECONDS_PER_MINUTE);
        if ($minutes <= 20) {
            return $minutes == 1 ? 'a minute' : "$minutes minutes";
        }

        // 5 minutes:
        if ($minutes < 58) {
            return (round($minutes / 5) * 5) . ' minutes';
        }

        // Hours:
        $hours = round($seconds / self::SECONDS_PER_HOUR);
        if ($hours < 48 && $hours % self::HOURS_PER_DAY != 0) {
            return $hours == 1 ? 'an hour' : "$hours hours";
        }

        // Days:
        $days = round($seconds / self::SECONDS_PER_DAY);
        if ($days < 28 && $days % self::DAYS_PER_WEEK != 0) {
            return $days == 1 ? 'a day' : "$days days";
        }

        // Weeks:
        $weeks = round($seconds / self::SECONDS_PER_WEEK);
        if ($weeks <= 12) {
            return $weeks == 1 ? 'a week' : "$weeks weeks";
        }

        // Months:
        $months = round($seconds / self::SECONDS_PER_MONTH);
        if ($months < 24 && $months % self::MONTHS_PER_YEAR != 0) {
            return $months == 1 ? 'a month' : "$months months";
        }

        // Years:
        $years = round($seconds / self::SECONDS_PER_YEAR);
        return $years == 1 ? 'a year' : "$years years";
    }

    /**
     * Calculate the difference in seconds between two datetimes.
     *
     * The signature is identical to
     * @see DateTime::diff(), which returns a DateInterval.
     *
     * @param DateTime $dt2
     * @param bool $absolute
     *   If TRUE then the absolute value of the difference is returned.
     */
    public function diffSeconds(self $datetime2, $absolute = false)
    {
        $diff = $this->timestamp() - $datetime2->timestamp();
        if ($absolute) {
            $diff = abs($diff);
        }
        return $diff;
    }

    /**
     * Calculate the difference in days between two dates.
     *
     * The signature is identical to
     * @see DateTime::diff(), which returns a DateInterval.
     *
     * The result is not necessarily the same as $this->diffSeconds() / self::SECONDS_PER_DAY,
     * or even the floor() or round() of that, because the time parts of the datetimes are discarded.
     *
     * @param DateTime $datetime
     * @param bool $absolute
     *   If TRUE then the absolute value of the difference is returned.
     */
    public function diffDays(self $datetime, $absolute = false)
    {
        $diff = $this->mjd() - $datetime->mjd();
        if ($absolute) {
            $diff = abs($diff);
        }
        return $diff;
    }
}
