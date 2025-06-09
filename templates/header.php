<?php
// templates/header.php

// Base URL - useful for absolute paths for CSS, JS, images if they are moved to separate files/folders
// For now, assuming CSS is still embedded or paths are relative from root files.
// $base_url = '/'; // Adjust if site is in a subdirectory, e.g., /futebol/
?>
<header class="site-header">
    <div class="header-container">
        <div class="logo-area">
            <a href="index.php" class="logo-text">Fut<span class="logo-accent">Online</span></a>
            <!-- Or an image: <a href="index.php"><img src="<?php //echo $base_url; ?>images/logo.png" alt="Logo"></a> -->
        </div>
        <nav class="main-navigation">
            <ul>
                <li><a href="index.php">Início</a></li>
                <!-- League links will be populated here later -->
                <li><a href="#">Brasileirão</a></li>
                <li><a href="#">Champions League</a></li>
                <li><a href="#">NBA</a></li>
                <!-- Add more example links or leave empty for dynamic population -->
            </ul>
        </nav>
        <div class="search-area">
            <form action="search.php" method="GET" class="search-form">
                <input type="search" name="query" placeholder="Buscar jogos, times..." aria-label="Buscar">
                <button type="submit">Buscar</button>
            </form>
        </div>
    </div>
</header>
