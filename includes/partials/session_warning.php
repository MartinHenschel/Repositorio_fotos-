<?php
/**
 * Mostra um aviso quando a sessão está perto de expirar
 * Coloque isto no rodapé (antes de </body>)
 */
$timeRemaining = getSessionTimeRemaining();
?>

<div id="session-warning" style="display:none; position:fixed; bottom:20px; right:20px; background:#fff3cd; border:1px solid #ffc107; border-radius:8px; padding:15px; max-width:300px; box-shadow:0 4px 6px rgba(0,0,0,0.1); z-index:9999;">
    <div style="display:flex; gap:10px; align-items:flex-start;">
        <i class="fas fa-exclamation-triangle" style="color:#ffc107; margin-top:3px;"></i>
        <div>
            <strong style="color:#333;">Sessão expirando em breve!</strong>
            <p style="margin:8px 0 0; color:#666; font-size:13px;">Você será desconectado em <span id="minutes-remaining">5</span> minuto(s) por inatividade.</p>
            <button onclick="document.location.reload();" style="background:#ffc107; color:#000; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-weight:bold; margin-top:8px;">Continuar Ativo</button>
        </div>
    </div>
</div>

<script>
    // Mostrar aviso quando faltar 5 minutos
    function checkSessionTimeout() {
        <?php
        // Passar tempo restante para JavaScript
        echo "var timeRemaining = " . $timeRemaining . ";";
        ?>
        
        var warningDiv = document.getElementById('session-warning');
        var minutesSpan = document.getElementById('minutes-remaining');
        
        if (timeRemaining <= 5 && timeRemaining > 0) {
            warningDiv.style.display = 'block';
            minutesSpan.textContent = timeRemaining;
        } else if (timeRemaining <= 0) {
            warningDiv.innerHTML = '<div style="text-align:center;"><i class="fas fa-clock" style="color:#dc3545; font-size:24px; margin-bottom:10px;"></i><strong style="color:#dc3545;">Sessão Expirada!</strong><p style="color:#666; margin:10px 0;">Você foi desconectado por inatividade.</p><a href="login.php" style="background:#dc3545; color:white; padding:8px 16px; border-radius:4px; text-decoration:none; display:inline-block;">Fazer Login Novamente</a></div>';
        }
    }
    
    // Verificar a cada 10 segundos
    setInterval(checkSessionTimeout, 10000);
    checkSessionTimeout(); // Verificar na primeira vez
    
    // Atualizar última atividade ao clicar ou digitar
    document.addEventListener('click', function() {
        // Isso faz uma requisição silenciosa para atualizar a sessão
        fetch('includes/update_session.php', {method: 'POST'});
    });
</script>