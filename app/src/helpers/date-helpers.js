export function formatDate(date) {
    if (!date) return null

    // Handle non-string inputs (Date objects, moment objects, etc.)
    let dateString = date
    if (typeof date !== 'string') {
        // If it's a moment object
        if (date.format && typeof date.format === 'function') {
            dateString = date.format('YYYY-MM-DD')
        }
        // If it's a Date object
        else if (date instanceof Date) {
            const year = date.getFullYear()
            const month = String(date.getMonth() + 1).padStart(2, '0')
            const day = String(date.getDate()).padStart(2, '0')
            dateString = `${year}-${month}-${day}`
        }
        // Otherwise, try to convert to string and return as-is
        else {
            return null
        }
    }

    const [year, month, day] = dateString.split('-')
    return `${day}.${month}.${year}`
}

export function parseDate(date) {
    if (!date) return null

    const [day, month, year] = date.split('.')
    return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`
}

export function dayNameByNumber(dayNumber) {
    if (!dayNumber && dayNumber > 0) return null
    const dayNames = ["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"];
    return dayNames[dayNumber - 1];
}

export function dayArrayToString(dayArray) {
    let result = "";
    for (let i = 0; i < dayArray.length; i++) {
        result += dayNameByNumber(dayArray[i]);
        if (i + 1 < dayArray.length) {
            result += ", ";
        }
    }
    return result;
}
