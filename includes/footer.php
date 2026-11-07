</main>
<?php if (!empty($telephone)): ?>
<nav class="tabbar" aria-label="Navigation">
    <?php
    $connecte = est_connecte();
    $vers = function ($cible) use ($connecte) {
        return $connecte ? $cible : 'login.php';
    };
    $onglets = [
        ['Accueil', 'accueil', $connecte ? 'mon_stage.php' : 'index.php', $connecte ? 'mon_stage.php' : 'index.php', $connecte ? ['mes_candidatures.php'] : []],
        ['Offres', 'offres', $vers('index.php'), $connecte ? 'index.php' : '', $connecte ? ['offre_detail.php'] : []],
        ['Conventions', 'oeil', $vers('convention.php'), $connecte ? 'convention.php' : '', []],
        ['Suivi', 'suivi', $vers('suivi.php'), $connecte ? 'suivi.php' : '', $connecte ? ['rapport.php', 'journal.php', 'commentaires.php'] : []],
        ['Profil', 'profil', $vers('profil.php'), $connecte ? 'profil.php' : '', $connecte ? ['stages_precedents.php', 'notifications.php'] : []],
    ];
    foreach ($onglets as $o) {
        [$texte, $icone, $href, $pageCle, $liees] = $o;
        $actif = ($pageCle !== '' && ($page === $pageCle || in_array($page, $liees, true))) ? ' actif' : '';
        echo '<a class="onglet' . $actif . '" href="' . $href . '">' . icone($icone) . '<span>' . e($texte) . '</span></a>';
    }
    ?>
</nav>
<script>
document.querySelectorAll('.fichier input[type=file]').forEach(function (input) {
    input.addEventListener('change', function () {
        var nom = input.files && input.files[0] ? input.files[0].name : '';
        var cible = input.parentElement.querySelector('.nom-fichier');
        if (cible) cible.textContent = nom;
    });
});
</script>
<?php endif; ?>
</div>
</body>
</html>
