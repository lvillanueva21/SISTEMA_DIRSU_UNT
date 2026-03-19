<?php
// includes/page_header.php
$pageHeaderTitle  = $pageHeaderTitle ?? '';
$pageHeaderCrumbs = $pageHeaderCrumbs ?? []; // [['Inicio','index.php'], ['Áreas',null], ['Proyectos',null]]
?>
<div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
    <div class="container text-center py-5">
        <h1 class="display-3 text-white mb-4 animated slideInDown">
            <?= htmlspecialchars($pageHeaderTitle, ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <nav aria-label="breadcrumb animated slideInDown">
            <ol class="breadcrumb justify-content-center mb-0">
                <?php foreach ($pageHeaderCrumbs as $i => $c): ?>
                    <?php
                        $label = $c[0] ?? '';
                        $href  = $c[1] ?? null;
                        $isLast = ($i === count($pageHeaderCrumbs) - 1);
                    ?>
                    <?php if ($isLast || !$href): ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>
</div>
