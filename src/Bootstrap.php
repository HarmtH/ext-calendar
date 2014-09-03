<?php
namespace Fisharebest\ExtCalendar;

/**
 * class Bootstrap - create global functions to emulate the calendar extension in PHP.
 *
 * Some PHP installations do not include the ext-calendar extension, which provides
 * functions for working with, and converting between, various calendars such as
 * Gregorian, Julian and Jewish.
 *
 * If you are writing applications that may be installed on such a system,
 *
 * @author    Greg Roach <fisharebest@gmail.com>
 * @copyright (c) 2014 Greg Roach
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
class Bootstrap {
	public static function init() {
		if (!function_exists('cal_info')) {
			require __DIR__ . '/shims.php';
		}
	}
}
