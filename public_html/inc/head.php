<?php
$page_title = $page_title ?? 'DIRSU';
$page_description = $page_description ?? '';
$page_keywords = $page_keywords ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($page_title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?php if ($page_description): ?><meta name="description" content="<?= htmlspecialchars($page_description) ?>"><?php endif; ?>
  <?php if ($page_keywords): ?><meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>"><?php endif; ?>

  <link rel="icon" href="<?= asset('img/rsu_icono.ico') ?>">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500;600;700&family=Open+Sans:wght@400;500&display=swap" rel="stylesheet">

  <!-- Iconos -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Librerías CSS -->
  <link href="<?= asset('lib/animate/animate.min.css') ?>" rel="stylesheet">
  <link href="<?= asset('lib/owlcarousel/assets/owl.carousel.min.css') ?>" rel="stylesheet">
  <link href="<?= asset('lib/lightbox/css/lightbox.min.css') ?>" rel="stylesheet">

  <!-- Bootstrap + Tema -->
  <link href="<?= asset('css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
  <link href="<?= asset('css/custom.css') ?>" rel="stylesheet"><!-- ← AQUÍ va tu <style> -->
</head>
<body>
  <!-- Spinner -->
  <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
    <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;"></div>
  </div>
