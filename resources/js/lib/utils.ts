import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

const CURRENCY_LOCALE_MAP: Record<string, string> = {
    USD: 'en-US',
    EUR: 'de-DE',
    GBP: 'en-GB',
    BRL: 'pt-BR',
    JPY: 'ja-JP',
    TZS: 'sw-TZ',
};

export function formatCurrency(value: number, currency: string = 'USD'): string {
    const locale = CURRENCY_LOCALE_MAP[currency] ?? 'en-US';

    try {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }).format(value);
    } catch {
        return `${currency} ${Number(value).toLocaleString()}`;
    }
}
