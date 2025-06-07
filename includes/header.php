<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link href="<?php echo asset('css/styles.css'); ?>" rel="stylesheet">
    
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1030;
        }
    </style>
</head>
<body>
<?php if (isset($_SESSION['usuario_id'])): ?>    
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#"><?php echo APP_NAME; ?></a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap d-flex align-items-center">
                <span class="px-3 text-white"><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></span>
                <a class="nav-link px-3" href="<?php echo url('logout.php'); ?>">Cerrar sesión</a>
            </div>
        </div>
    </header>
<?php endif; ?>

