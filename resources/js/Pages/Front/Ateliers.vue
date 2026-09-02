<script setup>
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";
import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";
import ImageLightbox from "@/Components/custom/ImageLightbox.vue";
import { Clock, Euro } from "lucide-vue-next";

// --- État de la Lightbox pour les flyers ---
const isLightboxOpen = ref(false);
const lightboxIndex = ref(0);

const flyerImages = [
    { src: "/images/assets/libre-flyer-1.jpg", alt: "Atelier libre flyer 1" },
    { src: "/images/assets/libre-flyer-2.jpg", alt: "Atelier libre flyer 2" },
];

const openLightbox = (index) => {
    lightboxIndex.value = index;
    isLightboxOpen.value = true;
};

// --- Données tarifaires Atelier Libre ---
const librePlans = [
    {
        duration: "1 mois",
        commitment: "Sans engagement",
        access: "Accès illimité",
        eleves: { total: "80 €", monthly: null },
        nonMembres: { total: "104 €", monthly: null },
    },
    {
        duration: "3 mois",
        commitment: "Engagement de 3 mois",
        access: "Accès illimité",
        eleves: { total: "200 €", monthly: "ou 69 €/m" },
        nonMembres: { total: "260 €", monthly: "ou 89 €/m" },
    },
    {
        duration: "6 mois",
        commitment: "Engagement de 6 mois",
        access: "Accès illimité",
        eleves: { total: "380 €", monthly: "ou 65 €/m" },
        nonMembres: { total: "495 €", monthly: "ou 85 €/m" },
    },
    {
        duration: "1 an",
        commitment: "Engagement de 12 mois",
        access: "Accès illimité",
        eleves: { total: "700 €", monthly: "ou 60 €/m" },
        nonMembres: { total: "910 €", monthly: "ou 77 €/m" },
    },
];
</script>

<template>
    <Head title="Ateliers" />
    <Nav />

    <section id="ateliers" class="py-12 font-brand">
        <div class="bg-white pt-2 pb-20 px-4 sm:px-6 lg:pt-0 lg:pb-28 lg:px-8">
            <div class="relative max-w-lg mx-auto lg:max-w-6xl">
                <h1
                    class="text-5xl leading-9 text-gray-900 sm:text-6xl sm:leading-10 mb-12"
                >
                    Ateliers
                </h1>

                <!-- ========================================== -->
                <!-- 1. Cours collectifs                        -->
                <!-- ========================================== -->
                <div>
                    <h2
                        class="text-3xl leading-9 text-gray-900 sm:text-4xl sm:leading-10"
                        id="collectifs"
                    >
                        Cours collectifs
                    </h2>
                    <div
                        class="mt-5 sm:mt-8 flex flex-col lg:flex-row text-justify border-t-2 border-gray-100 pt-10"
                    >
                        <div class="lg:w-1/2 lg:pr-8">
                            <p class="text-xl leading-7 text-gray-500 mb-12">
                                Les cours se présentent par module de 10 séances
                                hebdomadaires, que ce soit pour le modelage ou
                                pour le tournage. Nous accueillons les débutants
                                comme les plus aguerris désirant découvrir
                                d’autres techniques, ou perfectionner certains
                                gestes. Les groupes sont constitués de 8
                                personnes maximum afin de favoriser un suivi
                                individuel et personnalisé. Á travers ce module,
                                nous parcourons le façonnage, les cuissons et
                                l’émaillage des pièces réalisées.
                            </p>
                            <img
                                class="rounded-lg shadow-md object-cover object-center mt-4 lg:mt-0 w-full mb-12 flex-grow xl:flex-grow-0 xl:h-80"
                                src="/images/assets/collectifs.jpg"
                                loading="lazy"
                                alt="cours collectifs"
                            />
                            <p class="text-xl leading-7 text-gray-500 mb-12">
                                <span class="italic">Le modelage</span> permet
                                une grande liberté d’expression. Les pièces
                                peuvent être créées grâce à une multitude de
                                techniques comme le pinçage, le pastillage, le
                                colombin, le coulage ou le travail à la plaque.
                                Celles-ci peuvent être passées en revue lors du
                                cours, mais il est toujours bon de se renseigner
                                au préalable, et de venir avec un projet concret
                                en tête ou sur papier. La réussite de la pièce
                                dépendra de l'implication et de l'intérêt qu'y
                                portera le/la participant(e).
                            </p>
                            <p class="text-xl leading-7 text-gray-500 mb-12">
                                <span class="italic">Le tour de potier</span>
                                est utilisé pour façonner des pièces régulières
                                et symétriques. Il demande une certaine pratique
                                et un ancrage caractérisé par une dimension
                                spirituelle et méditative, nécessaire au
                                centrage de la terre pour pouvoir ensuite la
                                “tirer” vers le haut et affiner la paroi. Dans
                                tout les cas, une priorité sera mise sur la
                                technique au delà de l'esthétique, qui elle,
                                viendra dans un second plan.
                            </p>
                            <p class="text-xl leading-7 text-gray-500 mb-12">
                                La compréhension avant tout; ressentir et
                                observer. Le tour de potier existe depuis
                                plusieurs siècles et s'est développé
                                différemment dans chaque civilisation. Les
                                techniques sont infinies, et ne dépendent à
                                priori que du confort du potier. Certaines
                                qualités seront indispensables pour amener une
                                compréhension nécessaire à une progression
                                “autonome”, celles-ci feront grandir le potier
                                dans sa vie quotidienne, et ce bien au delà de
                                la pratique elle-même.
                            </p>
                            <p class="text-xl leading-7 text-gray-500 mb-12">
                                Profiter de chaque opportunité; faire l'éloge de
                                la lenteur. Bien des choses se produisent quand
                                on va lentement. Notre regard change, notre
                                sensibilité s'accroit, le monde s'arrête et un
                                univers tout entier se révèle entre nos mains.
                                La lenteur est indissociable de la
                                compréhension. Le ressenti est indissociable de
                                la lenteur.
                            </p>
                            <p class="text-xl leading-7 text-gray-500 mb-12">
                                Faire de l'ergonomie une priorité; nous sommes
                                tous différents. Certains sont petits, d'autres
                                sont grands. Notre corps nous est propre, et
                                celui-ci est notre outil principal. Chacun
                                dispose donc d'outils différents, qui devront
                                être utilisés à bon escient. Il n'y a donc aucun
                                doute, cela ne porte pas de sens d'imposer une
                                technique, une manière de faire. Le potier,
                                grâce à sa compréhension, pourra adapter les
                                gestes pour rendre ceux-ci confortables. Si un
                                geste est inconfortable, il faudra le corriger.
                                De même si celui-ci demande de l'effort. Le
                                potier sera invité à modifier sa position et
                                corriger la hauteur de son tabouret afin de
                                rendre sa pratique confortable pour plusieurs
                                heures.
                            </p>
                        </div>
                        <div class="flex flex-col lg:w-1/2 lg:pl-8">
                            <p class="text-xl leading-7 text-gray-500 mb-12">
                                La loi du moindre effort; le potier est
                                fainéant. L’intérêt premier du tour de potier
                                est de rechercher homogénéité de la pièce, au
                                niveau de sa forme et de son épaisseur. Afin
                                d'avoir cette constance, il est important de
                                comprendre que le tour travaillera à sa place !
                                Les doigts ne pourront se déplacer que lorsque
                                la pièce aura fait au minimum une fois le tour
                                sur elle même, et ceux-ci ne devront se déplacer
                                que sur l'axe d'un rayon car c'est l’entièreté
                                de la pièce qui en sera impacté. La clé sera
                                d'éviter tout les gestes parasites. Chaque
                                mouvement, chaque pression devront être
                                conscientisés afin de comprendre notre
                                interaction avec l'argile qui tourne entre nos
                                mains. Ce qui fait du tour une pratique
                                compliquée, voir ingrate à acquérir, c'est que
                                nous aurons beaucoup de chose à penser et à
                                ressentir. Dans un premier temps, il sera donc
                                impératif de limiter nos sollicitations, ne
                                faire qu'un geste à la fois, une étape à la fois
                                et ce jusqu'à avoir installé une certaine
                                aisance, jusqu'à ce qu'une zone de confort soit
                                bien déterminée.
                            </p>
                            <p class="text-xl leading-7 text-gray-500 mb-12">
                                Contrôler la matière; c'est moi qui décide. Il
                                est impératif de comprendre que votre pièce
                                prendra la forme que vous lui donnez. Rien
                                n'arrive par hasard. Le tout sera d'amener une
                                déformation « controlée » et non aléatoire. Afin
                                d’acquérir un maximum de liberté pour la suite,
                                il est très important d'être discipliné et de
                                réitérer certains exercices jusqu'à ce que
                                ceux-ci soient totalement acquis. L'exercice du
                                cylindre reste le plus intéressant et reviendra
                                tout au long de l'apprentissage du potier. Le
                                façonnage d'un cylindre parfait permettra au
                                potier en devenir de comprendre comment diriger
                                la matière et la déplacer en mettant les
                                objectifs techniques sur le premier plan. Le
                                cylindre permettra également d'entrainer son œil
                                à voir les irrégularités, et à comparer
                                visuellement la forme intérieure et extérieure
                                de la pièce, critère déterminant qui garantira
                                une réussite technique.
                            </p>
                            <img
                                class="rounded-lg shadow-md object-cover object-bottom mt-4 lg:mt-0 w-full mb-12 flex-grow xl:flex-grow-0 xl:h-96"
                                src="/images/assets/collectifs2.jpg"
                                loading="lazy"
                                alt="cours collectifs"
                            />
                            <p class="text-xl leading-7 text-gray-500 mb-12">
                                Je travaille la terre; je respecte la terre.
                                Contrairement aux idées reçues, la céramique est
                                loin d'être une pratique écologique. Deux
                                cuissons seront nécessaires à un produit fini.
                                Une première cuisson (le dégourdi, à 980°), et
                                le grésage (pour ma part à 1270°). Ces cuissons
                                demandent énormément d'énergie et produiront une
                                quantité importante de gaz toxiques qui seront
                                renvoyés dans l’atmosphère. L'impact écologique
                                des pièces est donc considérable et une
                                conscientisation de cet aspect n'est
                                certainement pas à négliger. Un accent
                                particulier sera donc mis sur le recyclage des
                                terres utilisées sur le tour car toute argile
                                peut être réutilisée autant de fois qu'on le
                                souhaite, tant qu'elle n'est pas cuite. Le
                                participant sera incité à s'installer sur le
                                tour en ayant une réelle intention et à se poser
                                certaines question avant de poser les mains sur
                                le tour. Nous ne conserverons et ne cuirons que
                                les pièces ayant un réel intérêt, ce qui,
                                premièrement leur donnera d'autant plus de
                                valeur, et qui, deuxièmement favorisera
                                considérablement l'apprentissage du potier.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                            <h3
                                class="text-lg leading-6 font-medium text-gray-900 flex items-center"
                            >
                                <Clock class="h-5 w-5 inline mr-2 text-earth" />
                                Horaire de nos cours collectifs
                            </h3>
                            <p
                                class="mt-1 max-w-2xl text-sm leading-5 text-gray-500"
                            >
                                Les sessions sont organisées chaque semaine.
                            </p>
                        </div>
                    </div>

                    <div
                        class="bg-white shadow overflow-hidden sm:rounded-lg my-12"
                    >
                        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                            <h3
                                class="text-lg leading-6 font-medium text-gray-900 flex items-center"
                            >
                                <Euro class="h-5 w-5 inline mr-2 text-earth" />
                                Tarifs
                            </h3>
                        </div>
                        <div>
                            <dl>
                                <div
                                    class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                                >
                                    <dt
                                        class="leading-8 font-medium text-gray-500"
                                    >
                                        Tour (2h30)
                                    </dt>
                                    <dd
                                        class="mt-1 leading-8 text-gray-900 sm:mt-0 sm:col-span-2"
                                    >
                                        Module 10 séances: 300€
                                    </dd>
                                </div>
                                <div
                                    class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                                >
                                    <dt
                                        class="leading-8 font-medium text-gray-500"
                                    >
                                        Modelage (2h30)
                                    </dt>
                                    <dd
                                        class="mt-1 leading-8 text-gray-900 sm:mt-0 sm:col-span-2"
                                    >
                                        Module 10 séances: 300€
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 2. Cours d'initiation                      -->
                <!-- ========================================== -->
                <div class="pt-20">
                    <h2
                        class="text-3xl leading-9 text-gray-900 sm:text-4xl sm:leading-10"
                        id="initiation"
                    >
                        Cours d’initiation
                    </h2>
                    <div
                        class="mt-5 sm:mt-8 flex flex-col lg:flex-row text-justify border-t-2 border-gray-100 pt-10"
                    >
                        <div class="lg:w-1/2 lg:pr-8">
                            <p class="text-xl leading-7 text-gray-500 mb-12">
                                Une journée ou une demi-journée d’initiation
                                peut être fixée sur demande par groupe de 6,
                                entre amis ou en famille. Nous parcourrons les
                                bases du centrage ainsi que les premiers gestes
                                à acquérir pour façonner un cylindre. (Aucune
                                pièce ne sera conservée)
                            </p>

                            <div
                                class="bg-white shadow overflow-hidden sm:rounded-lg my-12 lg:mb-0"
                            >
                                <div
                                    class="px-4 py-5 border-b border-gray-200 sm:px-6"
                                >
                                    <h3
                                        class="text-lg leading-6 font-medium text-gray-900 flex items-center"
                                    >
                                        <Euro
                                            class="h-5 w-5 inline mr-2 text-earth"
                                        />
                                        Tarifs
                                    </h3>
                                </div>
                                <div>
                                    <dl>
                                        <div
                                            class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                                        >
                                            <dt
                                                class="leading-8 font-medium text-gray-500"
                                            >
                                                Atelier de 3h
                                            </dt>
                                            <dd
                                                class="mt-1 leading-8 text-gray-900 sm:mt-0 sm:col-span-2"
                                            >
                                                50€ par personne
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="lg:w-1/2 lg:pl-8">
                            <img
                                class="rounded-lg shadow-md object-cover object-center mt-4 lg:mt-0 w-full h-96 xl:h-80"
                                src="/images/assets/initiation.jpg"
                                loading="lazy"
                                alt="cours d'initiation"
                            />
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 3. Cours privé                             -->
                <!-- ========================================== -->
                <div class="pt-20">
                    <h2
                        class="text-3xl leading-9 text-gray-900 sm:text-4xl sm:leading-10"
                        id="prive"
                    >
                        Cours privé
                    </h2>
                    <div
                        class="mt-5 sm:mt-8 flex flex-col lg:flex-row text-justify border-t-2 border-gray-100 pt-10"
                    >
                        <div class="lg:w-1/2 lg:pr-8">
                            <p class="text-xl leading-7 text-gray-500 mb-12">
                                Pour une première expérience du tournage de la
                                terre, il est bon que le professeur puisse
                                offrir toute son attention à l’élève, afin de
                                lui permettre d’assimiler les notions
                                nécessaires pour continuer au sein d’un groupe.
                            </p>

                            <div
                                class="bg-white shadow overflow-hidden sm:rounded-lg my-12 lg:mb-0"
                            >
                                <div
                                    class="px-4 py-5 border-b border-gray-200 sm:px-6"
                                >
                                    <h3
                                        class="text-lg leading-6 font-medium text-gray-900 flex items-center"
                                    >
                                        <Euro
                                            class="h-5 w-5 inline mr-2 text-earth"
                                        />
                                        Tarifs
                                    </h3>
                                </div>
                                <div>
                                    <dl>
                                        <div
                                            class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                                        >
                                            <dt
                                                class="leading-8 font-medium text-gray-500 text-left"
                                            >
                                                Séance unique de minimum 2h
                                            </dt>
                                            <dd
                                                class="mt-1 leading-8 text-gray-900 sm:mt-0 sm:col-span-2"
                                            >
                                                50€/h
                                            </dd>
                                        </div>
                                        <div
                                            class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                                        >
                                            <dt
                                                class="leading-8 font-medium text-gray-500 text-left"
                                            >
                                                Module de 5x2h
                                            </dt>
                                            <dd
                                                class="mt-1 leading-8 text-gray-900 sm:mt-0 sm:col-span-2"
                                            >
                                                450€ (45€/h)
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="lg:w-1/2 lg:pl-8">
                            <img
                                class="rounded-lg shadow-md object-cover object-bottom mt-4 lg:mt-0 h-96 w-full mb-12"
                                src="/images/assets/prive.jpg"
                                loading="lazy"
                                alt="cours privé"
                            />
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 4. Atelier libre                           -->
                <!-- ========================================== -->
                <div class="pt-20">
                    <h2
                        class="text-3xl leading-9 text-gray-900 sm:text-4xl sm:leading-10 flex items-center"
                        id="libre"
                    >
                        Atelier libre
                        <span
                            class="ml-3 px-3 py-1 rounded-full bg-blue-500 text-white text-xs font-semibold translate-y-[2px]"
                        >
                            Nouveau
                        </span>
                    </h2>

                    <!-- Descriptif Block 1 -->
                    <div
                        class="mt-5 sm:mt-8 flex flex-col lg:flex-row lg:items-center text-justify border-t-2 border-gray-100 pt-10 mb-12"
                    >
                        <div class="lg:w-1/2 lg:pr-8">
                            <p class="text-xl leading-7 text-gray-500 mb-6">
                                Un espace pensé pour les passionnés sans place
                                ni budget pour un tour chez eux.
                            </p>
                            <p class="text-xl leading-7 text-gray-500 mb-6">
                                Cet atelier a été conçu pour offrir aux
                                céramistes un lieu fonctionnel et chaleureux, où
                                chacun peut pratiquer le tournage en toute
                                autonomie.
                            </p>
                            <p class="text-xl leading-7 text-gray-500 mb-6">
                                L’inscription se fait par abonnement, selon
                                quatre formules offrant une pratique libre et
                                illimitée.
                            </p>
                        </div>
                        <div class="lg:w-1/2 lg:pl-8">
                            <img
                                class="rounded-lg shadow-md object-cover mt-4 lg:mt-0 h-96 w-full mb-6 lg:mb-0"
                                src="/images/assets/libre-1.jpg"
                                loading="lazy"
                                alt="étagère personnelle pour les cours libres"
                            />
                        </div>
                    </div>

                    <!-- Descriptif Block 2 -->
                    <div
                        class="flex flex-col-reverse lg:flex-row lg:items-center text-justify mb-16"
                    >
                        <div class="lg:w-1/2 lg:pr-8">
                            <img
                                class="rounded-lg shadow-md object-cover mt-4 lg:mt-0 h-96 w-full mb-6 lg:mb-0"
                                src="/images/assets/libre-2.jpg"
                                loading="lazy"
                                alt="tour de potier"
                            />
                        </div>
                        <div class="lg:w-1/2 lg:pl-8">
                            <p class="text-xl leading-7 text-gray-500 mb-6">
                                Accessible 7 jours sur 7, de 7h à 22h, l'atelier
                                met à disposition de chaque participant une
                                étagère personnelle pour entreposer pièces en
                                cours, terre et outils.
                            </p>
                            <p class="text-xl leading-7 text-gray-500 mb-6">
                                Un pain de terre est offert lors de la première
                                inscription. Les cuissons sont réalisées sur
                                demande et facturées en supplément.
                            </p>
                        </div>
                    </div>

                    <!-- Tableaux des Tarifs -->
                    <div class="mt-5 sm:mt-8 mb-16">
                        <!-- Vue Desktop (Grid) -->
                        <div
                            class="hidden lg:grid lg:grid-cols-[0.5fr_1.5fr_0.75fr_0.75fr] gap-y-4"
                        >
                            <div></div>
                            <div></div>
                            <div
                                class="h-[4.5rem] bg-[#a4764e] text-white text-2xl font-semibold flex items-center justify-center ml-4"
                            >
                                Élèves
                            </div>
                            <div
                                class="h-[4.5rem] bg-[#a4764e] text-white text-2xl font-semibold flex items-center justify-center ml-4"
                            >
                                Non‑Membres
                            </div>

                            <!-- Colonne 1: Durées -->
                            <div class="space-y-3">
                                <div
                                    v-for="plan in librePlans"
                                    :key="plan.duration"
                                    class="h-24 bg-[#7faba7] text-white text-2xl font-semibold flex items-center justify-center"
                                >
                                    {{ plan.duration }}
                                </div>
                            </div>

                            <!-- Colonne 2: Engagements -->
                            <div class="space-y-3">
                                <div
                                    v-for="plan in librePlans"
                                    :key="plan.duration"
                                    class="h-24 bg-[#e6e6e6] px-6 flex items-center"
                                >
                                    <ul
                                        class="text-lg list-disc list-inside space-y-0.5"
                                    >
                                        <li class="font-bold">
                                            {{ plan.commitment }}
                                        </li>
                                        <li>{{ plan.access }}</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Colonne 3: Prix Élèves -->
                            <div class="space-y-3 ml-4">
                                <div
                                    v-for="plan in librePlans"
                                    :key="plan.duration"
                                    class="h-24 bg-[#e6e6e6] px-6 flex items-center justify-center"
                                >
                                    <div class="text-center">
                                        <div class="text-4xl font-extrabold">
                                            {{ plan.eleves.total }}
                                        </div>
                                        <div
                                            v-if="plan.eleves.monthly"
                                            class="text-[15px] font-extrabold text-gray-700 mt-0.5"
                                        >
                                            {{ plan.eleves.monthly }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Colonne 4: Prix Non-Membres -->
                            <div class="space-y-3 ml-4">
                                <div
                                    v-for="plan in librePlans"
                                    :key="plan.duration"
                                    class="h-24 bg-[#e6e6e6] px-6 flex items-center justify-center"
                                >
                                    <div class="text-center">
                                        <div class="text-4xl font-extrabold">
                                            {{ plan.nonMembres.total }}
                                        </div>
                                        <div
                                            v-if="plan.nonMembres.monthly"
                                            class="text-[15px] font-extrabold text-gray-700 mt-0.5"
                                        >
                                            {{ plan.nonMembres.monthly }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vue Mobile (Cards) -->
                        <div class="lg:hidden flex flex-col space-y-8">
                            <div
                                v-for="plan in librePlans"
                                :key="plan.duration"
                            >
                                <h3
                                    class="bg-[#7faba7] text-white text-3xl font-semibold text-center py-3"
                                >
                                    {{ plan.duration }}
                                </h3>
                                <div class="bg-gray-100 shadow-md p-4">
                                    <div class="p-4 mb-3">
                                        <ul
                                            class="text-base list-disc list-inside mb-4 inline-block text-left"
                                        >
                                            <li class="font-bold">
                                                {{ plan.commitment }}
                                            </li>
                                            <li>{{ plan.access }}</li>
                                        </ul>
                                        <div
                                            class="grid grid-cols-2 max-[375px]:grid-cols-1 gap-3"
                                        >
                                            <div
                                                class="bg-[#e6e6e6] overflow-hidden text-center"
                                            >
                                                <h3
                                                    class="bg-[#a4764e] text-white text-lg font-bold py-2"
                                                >
                                                    Élèves
                                                </h3>
                                                <div class="p-3">
                                                    <div
                                                        class="text-2xl font-extrabold"
                                                    >
                                                        {{ plan.eleves.total }}
                                                    </div>
                                                    <div
                                                        v-if="
                                                            plan.eleves.monthly
                                                        "
                                                        class="text-sm font-bold text-gray-700"
                                                    >
                                                        {{
                                                            plan.eleves.monthly
                                                        }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                class="bg-[#e6e6e6] overflow-hidden text-center"
                                            >
                                                <h3
                                                    class="bg-[#a4764e] text-white text-lg font-bold py-2"
                                                >
                                                    Non‑Membres
                                                </h3>
                                                <div class="p-3">
                                                    <div
                                                        class="text-2xl font-extrabold"
                                                    >
                                                        {{
                                                            plan.nonMembres
                                                                .total
                                                        }}
                                                    </div>
                                                    <div
                                                        v-if="
                                                            plan.nonMembres
                                                                .monthly
                                                        "
                                                        class="text-sm font-bold text-gray-700"
                                                    >
                                                        {{
                                                            plan.nonMembres
                                                                .monthly
                                                        }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flyers avec Lightbox -->
                    <div class="mt-12 mb-8">
                        <div
                            class="mt-3 sm:mt-4 lg:grid lg:grid-cols-2 lg:gap-8 text-justify"
                        >
                            <button
                                type="button"
                                class="rounded-lg shadow-md overflow-hidden block w-full text-left group cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7faba7]"
                                @click="openLightbox(0)"
                            >
                                <img
                                    class="object-cover mt-4 lg:mt-0 w-full transition-transform duration-300 group-hover:scale-105"
                                    :src="flyerImages[0].src"
                                    loading="lazy"
                                    :alt="flyerImages[0].alt"
                                />
                            </button>
                            <button
                                type="button"
                                class="rounded-lg shadow-md overflow-hidden block w-full text-left mt-4 lg:mt-0 group cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#7faba7]"
                                @click="openLightbox(1)"
                            >
                                <img
                                    class="object-cover mt-4 lg:mt-0 w-full transition-transform duration-300 group-hover:scale-105"
                                    :src="flyerImages[1].src"
                                    loading="lazy"
                                    :alt="flyerImages[1].alt"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Lightbox -->
    <ImageLightbox
        v-model:open="isLightboxOpen"
        v-model:index="lightboxIndex"
        :images="flyerImages"
    />

    <Footer />
</template>
