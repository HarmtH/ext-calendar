<?php

/**
 * class Shim - PHP implementations of functions from the PHP calendar extension.
 *
 * @link      http://php.net/manual/en/book.calendar.php
 *
 * @author    Greg Roach <greg@subaqua.co.uk>
 * @copyright (c) 2014-2021 Greg Roach
 * @license   This program is free software: you can redistribute it and/or modify
 *            it under the terms of the GNU General Public License as published by
 *            the Free Software Foundation, either version 3 of the License, or
 *            (at your option) any later version.
 *
 *            This program is distributed in the hope that it will be useful,
 *            but WITHOUT ANY WARRANTY; without even the implied warranty of
 *            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *            GNU General Public License for more details.
 *
 *            You should have received a copy of the GNU General Public License
 *            along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fisharebest\ExtCalendar;

use InvalidArgumentException;
use ValueError;

class Shim
{
    /** @var FrenchCalendar */
    private static $french_calendar;

    /** @var GregorianCalendar */
    private static $gregorian_calendar;

    /** @var JewishCalendar */
    private static $jewish_calendar;

    /** @var JulianCalendar */
    private static $julian_calendar;

    /**
     * English names for the days of the week.
     *
     * @var string[]
     */
    private static $DAY_NAMES = array(
        'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',
    );

    /**
     * Abbreviated English names for the days of the week.
     *
     * @var string[]
     */
    private static $DAY_NAMES_SHORT = array(
        'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',
    );

    /** @var string[] Names of the months of the Gregorian/Julian calendars */
    private static $MONTH_NAMES = array(
        '', 'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    );

    /** @var string[] Abbreviated names of the months of the Gregorian/Julian calendars */
    private static $MONTH_NAMES_SHORT = array(
        '', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    );

    /** @var string[] Name of the months of the French calendar */
    private static $MONTH_NAMES_FRENCH = array(
        '', 'Vendemiaire', 'Brumaire', 'Frimaire', 'Nivose', 'Pluviose', 'Ventose',
        'Germinal', 'Floreal', 'Prairial', 'Messidor', 'Thermidor', 'Fructidor', 'Extra'
    );

    /** @var string[] Names of the months of the Jewish calendar in a non-leap year */
    private static $MONTH_NAMES_JEWISH = array(
        '', 'Tishri', 'Heshvan', 'Kislev', 'Tevet', 'Shevat', 'Adar',
        'Adar', 'Nisan', 'Iyyar', 'Sivan', 'Tammuz', 'Av', 'Elul',
    );

    /** @var string[] Names of the months of the Jewish calendar in a leap year */
    private static $MONTH_NAMES_JEWISH_LEAP_YEAR = array(
        '', 'Tishri', 'Heshvan', 'Kislev', 'Tevet', 'Shevat', 'Adar I',
        'Adar II', 'Nisan', 'Iyyar', 'Sivan', 'Tammuz', 'Av', 'Elul',
    );

    /** @var string[] Names of the months of the Jewish calendar (before PHP bug 54254 was fixed) */
    private static $MONTH_NAMES_JEWISH_54254 = array(
        '', 'Tishri', 'Heshvan', 'Kislev', 'Tevet', 'Shevat', 'AdarI',
        'AdarII', 'Nisan', 'Iyyar', 'Sivan', 'Tammuz', 'Av', 'Elul',
    );

    /**
     * Create the necessary shims to emulate the ext/calendar package.
     *
     * @return void
     */
    public static function create()
    {
        self::$french_calendar    = new FrenchCalendar();
        self::$gregorian_calendar = new GregorianCalendar();
        self::$jewish_calendar    = new JewishCalendar(array(
            JewishCalendar::EMULATE_BUG_54254 => self::shouldEmulateBug54254(),
        ));
        self::$julian_calendar    = new JulianCalendar();
    }

    /**
     * Do we need to emulate PHP bug #54254?
     *
     * This bug relates to the names used for months 6 and 7 in the Jewish calendar.
     *
     * It was fixed in PHP 5.5.0
     *
     * @link https://bugs.php.net/bug.php?id=54254
     *
     * @return bool
     */
    public static function shouldEmulateBug54254()
    {
        return PHP_VERSION_ID < 50500;
    }

    /**
     * Do we need to emulate PHP bug #67960?
     *
     * This bug relates to the constants CAL_DOW_SHORT and CAL_DOW_LONG.
     *
     * It was fixed in PHP 5.6.5 and 5.5.21
     *
     * @link https://bugs.php.net/bug.php?id=67960
     * @link https://github.com/php/php-src/pull/806
     *
     * @return bool
     */
    public static function shouldEmulateBug67960()
    {
        return PHP_VERSION_ID < 50521 || PHP_VERSION_ID >= 50600 && PHP_VERSION_ID < 50605;
    }

    /**
     * Do we need to emulate PHP bug #67976?
     *
     * This bug relates to the number of days in the month 13 of year 14 in
     * the French calendar.
     *
     * It was fixed in PHP 5.6.25 and 7.0.10
     *
     * @link https://bugs.php.net/bug.php?id=67976
     *
     * @return bool
     */
    public static function shouldEmulateBug67976()
    {
        return PHP_VERSION_ID < 50625 || PHP_VERSION_ID >= 70000 && PHP_VERSION_ID < 70010;
    }

    /**
     * Return the number of days in a month for a given year and calendar.
     *
     * Shim implementation of cal_days_in_month()
     *
     * @link https://php.net/cal_days_in_month
     * @link https://bugs.php.net/bug.php?id=67976
     *
     * @param int $calendar_id
     * @param int $month
     * @param int $year
     *
     * @return int|bool The number of days in the specified month, or false on error
     */
    public static function calDaysInMonth($calendar_id, $month, $year)
    {
        $month = (int) $month;
        $year  = (int) $year;

        switch ($calendar_id) {
            case CAL_FRENCH:
                return self::calDaysInMonthFrench($year, $month);

            case CAL_GREGORIAN:
                return self::calDaysInMonthCalendar(self::$gregorian_calendar, $year, $month);

            case CAL_JEWISH:
                return self::calDaysInMonthCalendar(self::$jewish_calendar, $year, $month);

            case CAL_JULIAN:
                return self::calDaysInMonthCalendar(self::$julian_calendar, $year, $month);

            default:
                if (PHP_VERSION_ID < 80000) {
                    return trigger_error('invalid calendar ID ' . $calendar_id, E_USER_WARNING);
                }

                throw new ValueError('cal_days_in_month(): Argument #1 ($calendar) must be a valid calendar ID');
        }
    }

    /**
     * Calculate the number of days in a month in a specified (Gregorian or Julian) calendar.
     *
     * @param CalendarInterface $calendar
     * @param int               $year
     * @param int               $month
     *
     * @return int|bool
     */
    private static function calDaysInMonthCalendar(CalendarInterface $calendar, $year, $month)
    {
        try {
            return $calendar->daysInMonth($year, $month);
        } catch (InvalidArgumentException $ex) {
            if (PHP_VERSION_ID < 70200) {
                return trigger_error('invalid date.', E_USER_WARNING);
            }
            if (PHP_VERSION_ID < 80000) {
                return trigger_error('invalid date', E_USER_WARNING);
            }

            throw new ValueError('Invalid date');
        }
    }

    /**
     * Calculate the number of days in a month in the French calendar.
     *
     * Mimic PHP’s validation of the parameters
     *
     * @param int $year
     * @param int $month
     *
     * @return int|bool
     */
    private static function calDaysInMonthFrench($year, $month)
    {
        if ($month == 13 && $year == 14 && self::shouldEmulateBug67976()) {
            return -2380948;
        }

        if ($year > 14) {
            if (PHP_VERSION_ID < 70200) {
                return trigger_error('invalid date.', E_USER_WARNING);
            }
            if (PHP_VERSION_ID < 80000) {
                return trigger_error('invalid date', E_USER_WARNING);
            }

            throw new ValueError('Invalid date');
        }

        return self::calDaysInMonthCalendar(self::$french_calendar, $year, $month);
    }

    /**
     * Converts from Julian Day Count to a supported calendar.
     *
     * Shim implementation of cal_from_jd()
     *
     * @link https://php.net/cal_from_jd
     *
     * @param int $julian_day  Julian Day number
     * @param int $calendar_id Calendar constant
     *
     * @return array|bool
     */
    public static function calFromJd($julian_day, $calendar_id)
    {
        $julian_day = (int) $julian_day;

        switch ($calendar_id) {
            case CAL_FRENCH:
                return self::calFromJdCalendar($julian_day, self::jdToFrench($julian_day), self::$MONTH_NAMES_FRENCH, self::$MONTH_NAMES_FRENCH);

            case CAL_GREGORIAN:
                return self::calFromJdCalendar($julian_day, self::jdToGregorian($julian_day), self::$MONTH_NAMES, self::$MONTH_NAMES_SHORT);

            case CAL_JEWISH:
                $months = self::jdMonthNameJewishMonths($julian_day);

                $cal = self::calFromJdCalendar($julian_day, self::jdToCalendar(self::$jewish_calendar, $julian_day, 347998, 324542846), $months, $months);

                if (($julian_day < 347998 || $julian_day > 324542846) && !self::shouldEmulateBug67976()) {
                    $cal['dow'] = null;
                    $cal['dayname'] = '';
                    $cal['abbrevdayname'] = '';
                }

                return $cal;

            case CAL_JULIAN:
                return self::calFromJdCalendar($julian_day, self::jdToJulian($julian_day), self::$MONTH_NAMES, self::$MONTH_NAMES_SHORT);

            default:
                if (PHP_VERSION_ID < 80000) {
                    return trigger_error('invalid calendar ID ' . $calendar_id, E_USER_WARNING);
                }

                throw new ValueError('cal_from_jd(): Argument #2 ($calendar) must be a valid calendar ID');
        }
    }

    /**
     * Convert a Julian day number to a calendar and provide details.
     *
     * @param int      $julian_day
     * @param string   $mdy
     * @param string[] $months
     * @param string[] $months_short
     *
     * @return array
     */
    private static function calFromJdCalendar($julian_day, $mdy, $months, $months_short)
    {
        list($month, $day, $year) = explode('/', $mdy);

        return array(
            'date'          => $month . '/' . $day . '/' . $year,
            'month'         => (int) $month,
            'day'           => (int) $day,
            'year'          => (int) $year,
            'dow'           => self::jdDayOfWeek($julian_day, 0),
            'abbrevdayname' => self::jdDayOfWeek($julian_day, 2),
            'dayname'       => self::jdDayOfWeek($julian_day, 1),
            'abbrevmonth'   => $months_short[$month],
            'monthname'     => $months[$month],
        );
    }

    /**
     * Returns information about a particular calendar.
     *
     * Shim implementation of cal_info()
     *
     * @link https://php.net/cal_info
     *
     * @param int $calendar_id
     *
     * @return array|bool
     */
    public static function calInfo($calendar_id)
    {
        switch ($calendar_id) {
            case CAL_FRENCH:
                return self::calInfoCalendar(self::$MONTH_NAMES_FRENCH, self::$MONTH_NAMES_FRENCH, 30, 'French', 'CAL_FRENCH');

            case CAL_GREGORIAN:
                return self::calInfoCalendar(self::$MONTH_NAMES, self::$MONTH_NAMES_SHORT, 31, 'Gregorian', 'CAL_GREGORIAN');

            case CAL_JEWISH:
                $months = self::shouldEmulateBug54254() ? self::$MONTH_NAMES_JEWISH_54254 : self::$MONTH_NAMES_JEWISH_LEAP_YEAR;

                return self::calInfoCalendar($months, $months, 30, 'Jewish', 'CAL_JEWISH');

            case CAL_JULIAN:
                return self::calInfoCalendar(self::$MONTH_NAMES, self::$MONTH_NAMES_SHORT, 31, 'Julian', 'CAL_JULIAN');

            case -1:
                return array(
                    CAL_GREGORIAN => self::calInfo(CAL_GREGORIAN),
                    CAL_JULIAN    => self::calInfo(CAL_JULIAN),
                    CAL_JEWISH    => self::calInfo(CAL_JEWISH),
                    CAL_FRENCH    => self::calInfo(CAL_FRENCH),
                );

            default:
                if (PHP_VERSION_ID < 80000) {
                    return trigger_error('invalid calendar ID ' . $calendar_id, E_USER_WARNING);
                }

                throw new ValueError('cal_info(): Argument #1 ($calendar) must be a valid calendar ID');
        }
    }

    /**
     * Returns information about the French calendar.
     *
     * @param string[] $month_names
     * @param string[] $month_names_short
     * @param int      $max_days_in_month
     * @param string   $calendar_name
     * @param string   $calendar_symbol
     *
     * @return array
     */
    private static function calInfoCalendar($month_names, $month_names_short, $max_days_in_month, $calendar_name, $calendar_symbol)
    {
        return array(
            'months'         => array_slice($month_names, 1, null, true),
            'abbrevmonths'   => array_slice($month_names_short, 1, null, true),
            'maxdaysinmonth' => $max_days_in_month,
            'calname'        => $calendar_name,
            'calsymbol'      => $calendar_symbol,
        );
    }

    /**
     *  Converts from a supported calendar to Julian Day Count
     *
     * Shim implementation of cal_to_jd()
     *
     * @link https://php.net/cal_to_jd
     *
     * @param int $calendar_id
     * @param int $month
     * @param int $day
     * @param int $year
     *
     * @return int|bool
     */
    public static function calToJd($calendar_id, $month, $day, $year)
    {
        $day   = (int) $day;
        $month = (int) $month;
        $year  = (int) $year;

        switch ($calendar_id) {
            case CAL_FRENCH:
                return self::frenchToJd($month, $day, $year);

            case CAL_GREGORIAN:
                return self::gregorianToJd($month, $day, $year);

            case CAL_JEWISH:
                return self::jewishToJd($month, $day, $year);

            case CAL_JULIAN:
                return self::julianToJd($month, $day, $year);

            default:
                if (PHP_VERSION_ID < 80000) {
                    return trigger_error('invalid calendar ID ' . $calendar_id, E_USER_WARNING);
                }

                throw new ValueError('cal_to_jd(): Argument #1 ($calendar) must be a valid calendar ID');
        }
    }

    /**
     * Get Unix timestamp for midnight on Easter of a given year.
     *
     * Shim implementation of easter_date()
     *
     * @link https://php.net/easter_date
     *
     * @param int $year
     *
     * @return int|bool
     */
    public static function easterDate($year)
    {
        $year = (int) $year;

        if ($year < 1970 || $year > 2037) {
            if (PHP_VERSION_ID >= 80000) {
                throw new ValueError('easter_date(): Argument #1 ($year) must be between 1970 and 2037 (inclusive)');
            }
            return trigger_error('This function is only valid for years between 1970 and 2037 inclusive', E_USER_WARNING);
        }

        $days = self::$gregorian_calendar->easterDays($year);

        if ($days < 11) {
            $easter_date_str = $year . "-3-" . ($days + 21);
        } else {
            $easter_date_str = $year . "-4-" . ($days - 10);
        }

        $easter_date = new \DateTime($easter_date_str, new \DateTimeZone(date_default_timezone_get()));

        return $easter_date->getTimestamp();
    }

    /**
     * Get number of days after March 21 on which Easter falls for a given year.
     *
     * Shim implementation of easter_days()
     *
     * @link https://php.net/easter_days
     *
     * @param int $year
     * @param int $method Use the Julian or Gregorian calendar
     *
     * @return int
     */
    public static function easterDays($year, $method)
    {
        $year   = (int) $year;
        $method = (int) $method;

        if (
            $method === CAL_EASTER_ALWAYS_JULIAN ||
            $method === CAL_EASTER_ROMAN && $year <= 1582 ||
            $year <= 1752 && $method !== CAL_EASTER_ROMAN && $method !== CAL_EASTER_ALWAYS_GREGORIAN
        ) {
            return self::$julian_calendar->easterDays($year);
        }

        return self::$gregorian_calendar->easterDays($year);
    }

    /**
     * Converts a date from the French Republican Calendar to a Julian Day Count.
     *
     * Shim implementation of FrenchToJD()
     *
     * @link https://php.net/FrenchToJD
     *
     * @param int $month
     * @param int $day
     * @param int $year
     *
     * @return int
     */
    public static function frenchToJd($month, $day, $year)
    {
        $day   = (int) $day;
        $month = (int) $month;
        $year  = (int) $year;

        if ($year <= 0) {
            return 0;
        }

        return self::$french_calendar->ymdToJd($year, $month, $day);
    }

    /**
     * Converts a Gregorian date to Julian Day Count.
     *
     * Shim implementation of GregorianToJD()
     *
     * @link https://php.net/GregorianToJD
     *
     * @param int $month
     * @param int $day
     * @param int $year
     *
     * @return int
     */
    public static function gregorianToJd($month, $day, $year)
    {
        $day   = (int) $day;
        $month = (int) $month;
        $year  = (int) $year;

        if ($year === 0) {
            return 0;
        }

        return self::$gregorian_calendar->ymdToJd($year, $month, $day);
    }

    /**
     * Returns the day of the week.
     *
     * Shim implementation of JDDayOfWeek()
     *
     * @link https://php.net/JDDayOfWeek
     * @link https://bugs.php.net/bug.php?id=67960
     *
     * @param int $julian_day
     * @param int $mode
     *
     * @return int|string
     */
    public static function jdDayOfWeek($julian_day, $mode)
    {
        $julian_day = (int) $julian_day;

        $day_of_week = ($julian_day + 1) % 7;
        if ($day_of_week < 0) {
            $day_of_week += 7;
        }

        switch ($mode) {
            case 1: // 1, not CAL_DOW_LONG - see bug 67960
                return self::$DAY_NAMES[$day_of_week];

            case 2: // 2, not CAL_DOW_SHORT - see bug 67960
                return self::$DAY_NAMES_SHORT[$day_of_week];

            default: // CAL_DOW_DAYNO or anything else
                return $day_of_week;
        }
    }

    /**
     * Returns a month name.
     *
     * Shim implementation of JDMonthName()
     *
     * @link https://php.net/JDMonthName
     *
     * @param int $julian_day
     * @param int $mode
     *
     * @return string
     */
    public static function jdMonthName($julian_day, $mode)
    {
        $julian_day = (int) $julian_day;

        switch ($mode) {
            case CAL_MONTH_GREGORIAN_LONG:
                return self::jdMonthNameCalendar(self::$gregorian_calendar, $julian_day, self::$MONTH_NAMES);

            case CAL_MONTH_JULIAN_LONG:
                return self::jdMonthNameCalendar(self::$julian_calendar, $julian_day, self::$MONTH_NAMES);

            case CAL_MONTH_JULIAN_SHORT:
                return self::jdMonthNameCalendar(self::$julian_calendar, $julian_day, self::$MONTH_NAMES_SHORT);

            case CAL_MONTH_JEWISH:
                return self::jdMonthNameCalendar(self::$jewish_calendar, $julian_day, self::jdMonthNameJewishMonths($julian_day));

            case CAL_MONTH_FRENCH:
                return self::jdMonthNameCalendar(self::$french_calendar, $julian_day, self::$MONTH_NAMES_FRENCH);

            case CAL_MONTH_GREGORIAN_SHORT:
            default:
                return self::jdMonthNameCalendar(self::$gregorian_calendar, $julian_day, self::$MONTH_NAMES_SHORT);
        }
    }

    /**
     * Calculate the month-name for a given julian day, in a given calendar,
     * with given set of month names.
     *
     * @param CalendarInterface $calendar
     * @param int               $julian_day
     * @param string[]          $months
     *
     * @return string
     */
    private static function jdMonthNameCalendar(CalendarInterface $calendar, $julian_day, $months)
    {
        list(, $month) = $calendar->jdToYmd($julian_day);

        return $months[$month];
    }

    /**
     * Determine which month names to use for the Jewish calendar.
     *
     * @param int $julian_day
     *
     * @return string[]
     */
    private static function jdMonthNameJewishMonths($julian_day)
    {
        list(, , $year) = explode('/', self::jdToCalendar(self::$jewish_calendar, $julian_day, 347998, 324542846));

        if (self::$jewish_calendar->isLeapYear($year)) {
            return self::shouldEmulateBug54254() ? self::$MONTH_NAMES_JEWISH_54254 : self::$MONTH_NAMES_JEWISH_LEAP_YEAR;
        }

        return self::shouldEmulateBug54254() ? self::$MONTH_NAMES_JEWISH_54254 : self::$MONTH_NAMES_JEWISH;
    }

    /**
     * Convert a Julian day in a specific calendar to a day/month/year.
     *
     * Julian days outside the specified range are returned as “0/0/0”.
     *
     * @param CalendarInterface $calendar
     * @param int               $julian_day
     * @param int               $min_jd
     * @param int               $max_jd
     *
     * @return string
     */
    private static function jdToCalendar(CalendarInterface $calendar, $julian_day, $min_jd, $max_jd)
    {
        if ($julian_day >= $min_jd && $julian_day <= $max_jd) {
            list($year, $month, $day) = $calendar->jdToYmd($julian_day);

            return $month . '/' . $day . '/' . $year;
        }

        return '0/0/0';
    }

    /**
     * Converts a Julian Day Count to the French Republican Calendar.
     *
     * Shim implementation of JDToFrench()
     *
     * @link https://php.net/JDToFrench
     *
     * @param int $julian_day A Julian Day number
     *
     * @return string A string of the form "month/day/year"
     */
    public static function jdToFrench($julian_day)
    {
        $julian_day = (int) $julian_day;

        // JDToFrench() converts years 1 to 14 inclusive, even though the calendar
        // officially ended on 10 Nivôse 14 (JD 2380687, 31st December 1805 Gregorian).
        return self::jdToCalendar(self::$french_calendar, $julian_day, 2375840, 2380952);
    }

    /**
     * Converts Julian Day Count to Gregorian date.
     *
     * Shim implementation of JDToGregorian()
     *
     * @link https://php.net/JDToGregorian
     *
     * @param int $julian_day A Julian Day number
     *
     * @return string A string of the form "month/day/year"
     */
    public static function jdToGregorian($julian_day)
    {
        $julian_day = (int) $julian_day;

        // PHP has different limits on 32 and 64 bit systems.
        $MAX_JD = PHP_INT_SIZE === 4 ? 536838866 : 2305843009213661906;

        return self::jdToCalendar(self::$gregorian_calendar, $julian_day, 1, $MAX_JD);
    }

    /**
     * Converts a Julian day count to a Jewish calendar date.
     *
     * Shim implementation of JdtoJjewish()
     *
     * @link https://php.net/JdtoJewish
     *
     * @param int  $julian_day A Julian Day number
     * @param bool $hebrew     If true, the date is returned in Hebrew text
     * @param int  $fl         If $hebrew is true, then add alafim and gereshayim to the text
     *
     * @return string|bool A string of the form "month/day/year", or false on error
     */
    public static function jdToJewish($julian_day, $hebrew, $fl)
    {
        if ($hebrew) {
            if ($julian_day < 347998 || $julian_day > 4000075) {
                $error_msg = PHP_VERSION_ID < 70200 ? 'Year out of range (0-9999).' : 'Year out of range (0-9999)';

                return trigger_error($error_msg, E_USER_WARNING);
            }

            return self::$jewish_calendar->jdToHebrew(
                $julian_day,
                (bool)($fl & CAL_JEWISH_ADD_ALAFIM_GERESH),
                (bool)($fl & CAL_JEWISH_ADD_ALAFIM),
                (bool)($fl & CAL_JEWISH_ADD_GERESHAYIM)
            );
        }

        // The upper limit is hard-coded into PHP to prevent numeric overflow on 32 bit systems.
        return self::jdToCalendar(self::$jewish_calendar, $julian_day, 347998, 324542846);
    }

    /**
     * Converts a Julian Day Count to a Julian Calendar Date.
     *
     * Shim implementation of JDToJulian()
     *
     * @link https://php.net/JDToJulian
     *
     * @param int $julian_day A Julian Day number
     *
     * @return string A string of the form "month/day/year"
     */
    public static function jdToJulian($julian_day)
    {
        $julian_day = (int) $julian_day;

        // PHP has different limits on 32 and 64 bit systems.
        $MAX_JD = PHP_INT_SIZE === 4 ? 536838829 : 784368370349;

        return self::jdToCalendar(self::$julian_calendar, $julian_day, 1, $MAX_JD);
    }

    /**
     * Convert Julian Day to Unix timestamp.
     *
     * Shim implementation of jdtounix()
     *
     * @link https://php.net/jdtounix
     *
     * @param int $julian_day
     *
     * @return int|false
     * @throws ValueError
     */
    public static function jdToUnix($julian_day)
    {
        $julian_day = (int) $julian_day;

        $upper_limit = self::jdToUnixUpperLimit();

        if ($julian_day >= 2440588 && $julian_day <= $upper_limit) {
            return ($julian_day - 2440588) * 86400;
        }

        if (PHP_VERSION_ID < 80000) {
            return false;
        }

        throw new ValueError('jday must be between 2440588 and ' . $upper_limit);
    }

    /**
     * What is the maximum value acceptable to jdtounix()
     *
     * @internal
     *
     * @return int
     */
    public static function jdToUnixUpperLimit()
    {
        if (PHP_INT_SIZE === 2 || PHP_VERSION_ID < 70324 || PHP_VERSION_ID >= 70400 && PHP_VERSION_ID < 70412) {
            return 2465343;
        }

        return 106751993607888;
    }

    /**
     * Converts a date in the Jewish Calendar to Julian Day Count.
     *
     * Shim implementation of JewishToJD()
     *
     * @link https://php.net/JewishToJD
     *
     * @param int $month
     * @param int $day
     * @param int $year
     *
     * @return int
     */
    public static function jewishToJd($month, $day, $year)
    {
        $day   = (int) $day;
        $month = (int) $month;
        $year  = (int) $year;

        if ($year <= 0) {
            return 0;
        }

        return self::$jewish_calendar->ymdToJd($year, $month, $day);
    }

    /**
     * Converts a Julian Calendar date to Julian Day Count.
     *
     * Shim implementation of JdToJulian()
     *
     * @link https://php.net/JdToJulian
     *
     * @param int $month
     * @param int $day
     * @param int $year
     *
     * @return int
     */
    public static function julianToJd($month, $day, $year)
    {
        $day   = (int) $day;
        $month = (int) $month;
        $year  = (int) $year;

        if ($year === 0) {
            return 0;
        }

        return self::$julian_calendar->ymdToJd($year, $month, $day);
    }

    /**
     * Convert Unix timestamp to Julian Day.
     *
     * Shim implementation of unixtojd()
     *
     * @link https://php.net/unixtojd
     *
     * @param int $timestamp
     *
     * @return false|int
     */
    public static function unixToJd($timestamp)
    {
        $timestamp = (int) $timestamp;

        if ($timestamp < 0) {
            if (PHP_VERSION_ID < 80000) {
                return false;
            }

            throw new ValueError('unixtojd(): Argument #1 ($timestamp) must be greater than or equal to 0');
        }

        // Convert timestamp based on local timezone
        return self::GregorianToJd(gmdate('n', $timestamp), gmdate('j', $timestamp), gmdate('Y', $timestamp));
    }
}
