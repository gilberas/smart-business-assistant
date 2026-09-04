export function format(date: Date | string, fmt: string): string {
    const d = typeof date === 'string' ? new Date(date) : date;
    if (isNaN(d.getTime())) return 'Invalid date';
    const pad = (n: number) => String(n).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const map: Record<string, string> = {
        yyyy: String(d.getFullYear()),
        MM: pad(d.getMonth() + 1),
        dd: pad(d.getDate()),
        HH: pad(d.getHours()),
        mm: pad(d.getMinutes()),
        ss: pad(d.getSeconds()),
        MMM: months[d.getMonth()],
        d: String(d.getDate()),
    };
    let result = fmt;
    for (const [key, value] of Object.entries(map)) {
        result = result.replace(key, value);
    }
    return result;
}

export function formatDistanceToNow(date: Date | string, options?: { addSuffix?: boolean }): string {
    const d = typeof date === 'string' ? new Date(date) : date;
    const now = new Date();
    const diffMs = now.getTime() - d.getTime();
    const absDiff = Math.abs(diffMs);
    const seconds = Math.floor(absDiff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    const months = Math.floor(days / 30);
    const years = Math.floor(days / 365);

    let result: string;
    if (seconds < 60) result = 'less than a minute';
    else if (minutes === 1) result = '1 minute';
    else if (minutes < 60) result = `${minutes} minutes`;
    else if (hours === 1) result = '1 hour';
    else if (hours < 24) result = `${hours} hours`;
    else if (days === 1) result = '1 day';
    else if (days < 30) result = `${days} days`;
    else if (months === 1) result = '1 month';
    else if (months < 12) result = `${months} months`;
    else if (years === 1) result = '1 year';
    else result = `${years} years`;

    if (options?.addSuffix) {
        result = diffMs >= 0 ? `${result} ago` : `in ${result}`;
    }
    return result;
}
