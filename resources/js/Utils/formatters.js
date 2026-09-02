/**
 * Utilitaires de formatage purs et sans état (stateless).
 */

/**
 * Gère l'accord en nombre selon les règles de la langue française (pluriel à partir de 2).
 *
 * @param {number} count - La quantité
 * @param {string} singular - Le libellé au singulier (ex: "participant", "place disponible")
 * @param {string|null} plural - Le libellé au pluriel si irrégulier (ou null pour ajout automatique d'un 's')
 * @param {boolean} includeCount - Si true, inclut le nombre devant (ex: "2 participants")
 * @returns {string}
 */
export function pluralize(count, singular, plural = null, includeCount = true) {
    const num = Number(count) || 0;
    // Règle du français : singulier uniquement pour 1
    const isPlural = Math.abs(num) !== 1;
    const word = isPlural ? (plural || `${singular}s`) : singular;

    return includeCount ? `${num} ${word}` : word;
}

/**
 * Formate un montant au format monétaire (ex: 42,50 €).
 *
 * @param {number|string} price
 * @param {string} currency
 * @param {string} locale
 * @returns {string}
 */
export function formatPrice(price, currency = 'EUR', locale = 'fr-BE') {
    const num = Number(price) || 0;
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
}

/**
 * Formate une date en chaîne lisible (ex: "15 août 2026").
 *
 * @param {string|Date} date
 * @param {Intl.DateTimeFormatOptions} options
 * @param {string} locale
 * @returns {string}
 */
export function formatDate(date, options = {}, locale = 'fr-BE') {
    if (!date) return 'N/A';
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'N/A';

    const defaultOptions = {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        ...options,
    };

    return new Intl.DateTimeFormat(locale, defaultOptions).format(d);
}

/**
 * Formate une plage de dates (ex: "du 15 août 2026 au 24 octobre 2026").
 *
 * @param {string|Date} startDate
 * @param {string|Date} endDate
 * @param {string} locale
 * @returns {string}
 */
export function formatDateRange(startDate, endDate, locale = 'fr-BE') {
    if (!startDate || !endDate) return '';
    return `du ${formatDate(startDate, {}, locale)} au ${formatDate(endDate, {}, locale)}`;
}
