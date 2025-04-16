/**
 * Convert time from Mikrotik format (e.g., "2h30m") to HH:MM:SS format
 * @param {string} mikrotikTime - Time in Mikrotik format (e.g., "2h30m")
 * @returns {string} Time in HH:MM:SS format
 */
export function convertFromMikrotikTime(mikrotikTime) {
    if (!mikrotikTime) return '00:00:00';

    let totalSeconds = 0;

    // Extract days, hours, minutes, seconds
    if (mikrotikTime.includes('d')) {
        const days = parseInt(mikrotikTime.match(/(\d+)d/)?.[1] || '0');
        totalSeconds += days * 24 * 60 * 60;
    }
    if (mikrotikTime.includes('h')) {
        const hours = parseInt(mikrotikTime.match(/(\d+)h/)?.[1] || '0');
        totalSeconds += hours * 60 * 60;
    }
    if (mikrotikTime.includes('m')) {
        const minutes = parseInt(mikrotikTime.match(/(\d+)m/)?.[1] || '0');
        totalSeconds += minutes * 60;
    }
    if (mikrotikTime.includes('s')) {
        const seconds = parseInt(mikrotikTime.match(/(\d+)s/)?.[1] || '0');
        totalSeconds += seconds;
    }

    // Convert to HH:MM:SS format
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

/**
 * Convert time from HH:MM:SS format or direct Mikrotik format to Mikrotik format
 * @param {string} time - Time in HH:MM:SS format or direct Mikrotik format (e.g., '1h30m')
 * @returns {string} Time in Mikrotik format
 */
export function convertToMikrotikTime(time) {
    if (!time) return '';
    
    // If the time is already in Mikrotik format (contains h, m, s, or d), return it as is
    if (/[hmsd]/.test(time)) {
        return time;
    }

    // Otherwise, assume it's in HH:MM:SS format
    const [hours, minutes, seconds] = time.split(':').map(Number);
    const totalSeconds = hours * 3600 + minutes * 60 + seconds;

    let result = '';
    if (hours > 0) {
        result += `${hours}h`;
    }
    if (minutes > 0) {
        result += `${minutes}m`;
    }
    if (seconds > 0) {
        result += `${seconds}s`;
    }

    return result || '0s';
} 