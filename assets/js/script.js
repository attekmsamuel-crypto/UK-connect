document.addEventListener('DOMContentLoaded', function () {

    var entete = document.querySelector('.entete');
    if (entete) {
        var ombrer = function () {
            entete.classList.toggle('detache', window.scrollY > 4);
        };
        ombrer();
        window.addEventListener('scroll', ombrer, { passive: true });
    }

    var animationsReduites = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!animationsReduites && !document.hidden) {
        document.querySelectorAll('.presentation .chiffre b').forEach(function (element) {
            var cible = parseInt(element.textContent, 10);
            if (isNaN(cible) || cible === 0) {
                return;
            }

            var duree = 900;
            var depart = null;
            element.textContent = '0';

            var avancer = function (temps) {
                if (depart === null) {
                    depart = temps;
                }
                var part = Math.min((temps - depart) / duree, 1);
                element.textContent = Math.round(cible * (1 - Math.pow(1 - part, 3)));
                if (part < 1) {
                    window.requestAnimationFrame(avancer);
                }
            };

            window.requestAnimationFrame(avancer);
        });
    }

    var bouton = document.getElementById('bouton-menu');
    var menu = document.getElementById('navigation');

    if (bouton && menu) {
        bouton.addEventListener('click', function () {
            menu.classList.toggle('ouvert');
        });
    }

    document.querySelectorAll('[data-confirmer]').forEach(function (element) {
        element.addEventListener('click', function (evenement) {
            if (!window.confirm(element.getAttribute('data-confirmer'))) {
                evenement.preventDefault();
            }
        });
    });

    var roles = document.querySelectorAll('input[name="role"]');
    var blocEtudiant = document.getElementById('bloc-etudiant');
    var blocPartenaire = document.getElementById('bloc-partenaire');

    function afficherBlocs() {
        var choisi = document.querySelector('input[name="role"]:checked');
        if (!choisi || !blocEtudiant || !blocPartenaire) {
            return;
        }
        var etudiant = choisi.value === 'etudiant';
        blocEtudiant.hidden = !etudiant;
        blocPartenaire.hidden = etudiant;

        document.querySelectorAll('.choix-role label').forEach(function (label) {
            var champ = label.querySelector('input');
            label.classList.toggle('retenu', champ && champ.checked);
        });
    }

    roles.forEach(function (champ) {
        champ.addEventListener('change', afficherBlocs);
    });
    afficherBlocs();
});
