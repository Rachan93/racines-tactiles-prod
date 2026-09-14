<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Paiement requis — {{ config('racines.name') }}</title>

    @vite(['resources/js/app.js'])
</head>

<body class="bg-white font-brand text-gray-900">
    <main class="min-h-screen px-4 py-12 sm:px-6 lg:px-8">
        <div
            class="mx-auto flex min-h-[75vh] max-w-3xl flex-col items-center justify-center text-center"
        >
            <a
                href="{{ route('home.index') }}"
                class="mb-10 inline-block"
            >
                <img
                    src="{{ asset(config('racines.logo')) }}"
                    alt="{{ config('racines.name') }}"
                    class="mx-auto h-20 w-auto"
                >
            </a>

            <p
                class="text-sm font-semibold uppercase tracking-[0.2em] text-earth"
            >
                Erreur 402
            </p>

            <h1
                class="mt-4 text-5xl leading-tight text-gray-900 sm:text-6xl"
            >
                Paiement requis
            </h1>

            <p
                class="mt-6 max-w-xl text-lg leading-7 text-gray-500"
            >
                Cette action nécessite un paiement avant de pouvoir continuer.
                Vérifiez votre situation ou revenez à l’accueil.
            </p>

            <div
                class="mt-10 flex flex-col items-center gap-3 sm:flex-row"
            >
                <a
                    href="{{ route('home.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-md bg-blue-600 px-5 text-sm font-medium text-white shadow transition-colors hover:bg-blue-500"
                >
                    Retour à l’accueil
                </a>

                <button
                    type="button"
                    onclick="history.back()"
                    class="inline-flex h-10 items-center justify-center rounded-md border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-900"
                >
                    Page précédente
                </button>
            </div>
        </div>
    </main>
</body>
</html>

