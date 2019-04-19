export function formatDate(date) {
    if (!date) return null

    const [year, month, day] = date.split('-')
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
