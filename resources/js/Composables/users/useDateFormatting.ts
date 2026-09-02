import { type DateValue, getLocalTimeZone } from "@internationalized/date";

export function useDateFormatting() {
    /**
     * Ajoute des zéros devant un nombre pour obtenir une chaîne de longueur 2
     * (Alternative à padStart pour la compatibilité TypeScript)
     */
    function padWithZeros(num: number): string {
        return num < 10 ? `0${num}` : `${num}`;
    }

    /**
     * Formate une date pour le backend (format YYYY-MM-DD)
     */
    function formatDateForBackend(dateValue: DateValue): string {
        // Convertir en Date JavaScript standard
        const jsDate = dateValue.toDate(getLocalTimeZone());
        const month = jsDate.getMonth() + 1;
        const day = jsDate.getDate();
        // Format YYYY-MM-DD que Laravel peut facilement traiter
        return `${jsDate.getFullYear()}-${padWithZeros(month)}-${padWithZeros(
            day
        )}`;
    }

    /**
     * Crée une chaîne de date pour le parsing
     */
    function createDateString(
        year: number,
        month: number,
        day: number = 1
    ): string {
        return `${year}-${padWithZeros(month)}-${padWithZeros(day)}`;
    }

    return {
        padWithZeros,
        formatDateForBackend,
        createDateString,
    };
}
