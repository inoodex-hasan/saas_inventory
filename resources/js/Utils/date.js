import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';

// Extend dayjs with standard plugins
dayjs.extend(relativeTime);
dayjs.extend(customParseFormat);
dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);

/**
 * Check if a string looks like a dayjs format pattern.
 */
function isFormatPattern(str) {
    if (!str || typeof str !== 'string') return false;
    return /YYYY|YY|MMMM|MMM|MM|DD|D|HH|hh|mm|ss|A|a/.test(str);
}

/**
 * Format any date input with dayjs.
 * Backward compatible with (val, fallback = '—') and supports (val, format, fallback).
 *
 * @param {string|Date|number|null|undefined} val
 * @param {string} formatOrFallback (e.g., 'YYYY-MM-DD' or fallback like 'N/A')
 * @param {string} fallback
 * @returns {string}
 */
export function formatDate(val, formatOrFallback = 'YYYY-MM-DD', fallback = '—') {
    if (!val) {
        return (typeof formatOrFallback === 'string' && !isFormatPattern(formatOrFallback))
            ? formatOrFallback
            : fallback;
    }

    let format = 'YYYY-MM-DD';
    let emptyFallback = fallback;

    if (typeof formatOrFallback === 'string') {
        if (isFormatPattern(formatOrFallback)) {
            format = formatOrFallback;
        } else {
            emptyFallback = formatOrFallback;
        }
    }

    const d = dayjs(val);
    if (!d.isValid()) return emptyFallback;

    return d.format(format);
}

/**
 * Format date & time (e.g. "19 Oct 2026, 05:20 PM" or "2026-10-19 17:20").
 *
 * @param {string|Date|number|null|undefined} val
 * @param {string} format
 * @param {string} fallback
 * @returns {string}
 */
export function formatDateTime(val, format = 'DD MMM YYYY, hh:mm A', fallback = '—') {
    if (!val) return fallback;
    const d = dayjs(val);
    if (!d.isValid()) return fallback;
    return d.format(format);
}

/**
 * Format date to human-friendly text (e.g., "19 Oct 2026").
 *
 * @param {string|Date|number|null|undefined} val
 * @param {string} fallback
 * @returns {string}
 */
export function formatHumanDate(val, fallback = '—') {
    return formatDate(val, 'DD MMM YYYY', fallback);
}

/**
 * Format relative time (e.g., "2 hours ago", "in 3 days", "just now").
 *
 * @param {string|Date|number|null|undefined} val
 * @param {string} fallback
 * @returns {string}
 */
export function formatRelative(val, fallback = '—') {
    if (!val) return fallback;
    const d = dayjs(val);
    if (!d.isValid()) return fallback;
    return d.fromNow();
}

/**
 * Calculate integer days remaining until a target date.
 * Returns negative number if date is in the past.
 *
 * @param {string|Date|number|null|undefined} targetDate
 * @returns {number|null}
 */
export function daysRemaining(targetDate) {
    if (!targetDate) return null;
    const d = dayjs(targetDate);
    if (!d.isValid()) return null;
    const today = dayjs().startOf('day');
    return d.startOf('day').diff(today, 'day');
}

/**
 * Check if a date is in the past.
 *
 * @param {string|Date|number|null|undefined} targetDate
 * @returns {boolean}
 */
export function isExpired(targetDate) {
    if (!targetDate) return false;
    const d = dayjs(targetDate);
    if (!d.isValid()) return false;
    return d.isBefore(dayjs());
}

export { dayjs };
export default dayjs;
