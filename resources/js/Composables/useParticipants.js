import { ref } from "vue";

/**
 * Composable pour les fonctionnalités liées aux participants
 */
export function useParticipants() {
    /**
     * Détermine le type de participant à partir du nom de la classe
     */
    const participantType = (type) => {
        if (type === "App\\Models\\User") return "Utilisateur";
        if (type === "App\\Models\\Attendee") return "Invité";
        return type;
    };

    /**
     * Construit un nom complet à partir des propriétés first_name et last_name
     */
    const fullName = (user) => {
        if (!user) return "N/A";
        return `${user.first_name} ${user.last_name}`;
    };

    return {
        participantType,
        fullName,
    };
}
