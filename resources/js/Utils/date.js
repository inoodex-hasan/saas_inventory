/**
 * Format any date input to clean YYYY-MM-DD (no time component).
 * Safely strips any timestamp, ISO T-part, or time string.
 *
 * @param {string|Date|number|null|undefined} val
 * @param {string} fallback
 * @returns {string}
 */
export function formatDate(val, fallback = '—') {
    if (!val) return fallback;

    if (typeof val === 'string') {
        const trimmed = val.trim();
        if (!trimmed) return fallback;
        // Strip out T... and time spaces: e.g. "2026-10-18T00:00:00.000000Z" -> "2026-10-18"
        const dateOnly = trimmed.split('T')[0].split(' ')[0];
        return dateOnly || fallback;
    }

    if (val instanceof Date && !isNaN(val.getTime())) {
        return val.toISOString().split('T')[0];
    }

    return String(val);
}
