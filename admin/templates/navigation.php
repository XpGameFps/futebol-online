<nav>
            <div>
                <a href="index.php">Painel Principal (Jogos)</a>
                <a href="manage_leagues.php">Gerenciar Ligas</a>
                <a href="manage_channels.php">Gerenciar Canais TV</a>
                <a href="manage_teams.php">Gerenciar Times</a>
                <a href="manage_saved_streams.php">Biblioteca de Streams</a>
                <a href="manage_banners.php">Gerenciar Banners</a>
                <a href="manage_item_reports.php">Reportes de Itens</a>
                <a href="manage_admins.php">Gerenciar Admins</a>
                <a href="manage_settings.php">Configurações</a>
            </div>
            <div class="nav-user-info">
                <span id="server-clock" style="margin-right: 15px; color: #007bff; font-weight:bold;"></span>
                Usuário: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?> |
                <a href="logout.php" class="logout-link">Logout</a>
    <div id="admin-stats-display-container" class="admin-user-stats" style="margin-top: 5px; clear:both; text-align: left;"></div>
            </div>
        </nav>

<script>
function updateServerTime() {
  const now = new Date();
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');
  const timeString = `${hours}:${minutes}:${seconds}`;
  const clockElement = document.getElementById('server-clock');
  if (clockElement) {
    clockElement.textContent = timeString;
  }
}

setInterval(updateServerTime, 1000);
updateServerTime(); // Initial call to display time immediately
</script>
