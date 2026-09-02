import { ref } from "vue";

/**
 * Composable pour les fonctionnalités de formatage
 */
export function useFormatting() {
    /**
     * Formate une date en format belge
     */
    const formatDate = (dateString) => {
        if (!dateString) return "N/A";
        return new Date(dateString).toLocaleDateString("fr-BE");
    };

    /**
     * Formate un prix au format monétaire belge
     */
    const formatPrice = (price) => {
        return new Intl.NumberFormat("fr-BE", {
            style: "currency",
            currency: "EUR",
        }).format(price);
    };

    return {
        formatDate,
        formatPrice,
    };
}
