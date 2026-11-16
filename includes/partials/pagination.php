<?php
/**
 * Partial: controle de paginação
 * Espera as variáveis:
 * - $paginacao (array com 'pages', 'current_page', 'total')
 * - $urlBase (ex: 'index.php' ou 'album.php?id=5')
 */

if (!isset($paginacao) || $paginacao['pages'] <= 1) {
    return; // Sem paginação se há apenas 1 página
}

$currentPage = $paginacao['current_page'];
$totalPages = $paginacao['pages'];

// Detecta se $urlBase já possui query string e escolhe o separador apropriado
$sep = (strpos($urlBase, '?') === false) ? '?' : '&';
?>

<div class="pagination">
    <!-- Botão Anterior -->
    <?php if ($currentPage > 1): ?>
        <a href="<?php echo $urlBase . $sep . 'page=' . ($currentPage - 1); ?>" class="pagination-btn pagination-prev">
            <i class="fas fa-chevron-left"></i> Anterior
        </a>
    <?php else: ?>
        <span class="pagination-btn pagination-prev disabled">
            <i class="fas fa-chevron-left"></i> Anterior
        </span>
    <?php endif; ?>

    <!-- Números de Página -->
    <div class="pagination-numbers">
        <?php
        // Mostrar páginas ao redor da atual
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        // Se não for desde a página 1, mostrar "1 ..."
        if ($start > 1): ?>
            <a href="<?php echo $urlBase . $sep . 'page=1'; ?>" class="pagination-number">1</a>
            <?php if ($start > 2): ?>
                <span class="pagination-dots">...</span>
            <?php endif;
        endif;
        
        // Mostrar as páginas próximas
        for ($i = $start; $i <= $end; $i++):
            $isActive = ($i === $currentPage);
            ?>
            <a href="<?php echo $urlBase . $sep . 'page=' . $i; ?>" 
               class="pagination-number <?php echo $isActive ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor;
        
        // Se não for até a última página, mostrar "... última"
        if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
                <span class="pagination-dots">...</span>
            <?php endif; ?>
            <a href="<?php echo $urlBase . $sep . 'page=' . $totalPages; ?>" 
               class="pagination-number">
                <?php echo $totalPages; ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Botão Próximo -->
    <?php if ($currentPage < $totalPages): ?>
        <a href="<?php echo $urlBase . $sep . 'page=' . ($currentPage + 1); ?>" class="pagination-btn pagination-next">
            Próximo <i class="fas fa-chevron-right"></i>
        </a>
    <?php else: ?>
        <span class="pagination-btn pagination-next disabled">
            Próximo <i class="fas fa-chevron-right"></i>
        </span>
    <?php endif; ?>
</div>

<div class="pagination-info">
    Página <strong><?php echo $currentPage; ?></strong> de <strong><?php echo $totalPages; ?></strong> 
    (<?php echo $paginacao['total']; ?> imagens)
</div>