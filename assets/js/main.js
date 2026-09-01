/**
 * UK-Connect — JavaScript natif (interactions côté client)
 * Conformément à la fiche technique : validation légère, menus,
 * confirmations — la sécurité reste toujours côté serveur (PHP).
 */
document.addEventListener('DOMContentLoaded', function () {

  // Menu mobile (burger)
  var burger = document.getElementById('burger');
  var nav = document.getElementById('nav');
  if (burger && nav) {
    burger.addEventListener('click', function () {
      nav.classList.toggle('ouvert');
    });
  }

  // Inscription : afficher le bon bloc selon le rôle choisi
  var choixRole = document.querySelectorAll('input[name="role"]');
  var blocEtudiant  = document.getElementById('bloc-etudiant');
  var blocPartenaire = document.getElementById('bloc-partenaire');
  function majBlocs() {
    var role = document.querySelector('input[name="role"]:checked');
    if (!role) return;
    if (blocEtudiant)   blocEtudiant.style.display   = (role.value === 'etudiant')  ? '' : 'none';
    if (blocPartenaire) blocPartenaire.style.display = (role.value === 'partenaire') ? '' : 'none';
  }
  choixRole.forEach(function (r) { r.addEventListener('change', majBlocs); });
  majBlocs();

  // Confirmation avant les actions sensibles (valider, rejeter, désactiver…)
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (ev) {
      if (!window.confirm(el.getAttribute('data-confirm'))) {
        ev.preventDefault();
      }
    });
  });
});
