<?php
/**
 * Partial: grade de imagens.
 * Espera a variável $imagens (array) já definida por quem faz o include.
 * Extraído de index.php e album.php, onde a mesma marcação estava duplicada.
 */
?>
<div class="grid" id="grade-imagens">
    <?php foreach ($imagens as $img): ?>
    <div class="card" data-id="<?php echo $img['id']; ?>">
        <div class="card-image">
            <img src="thumbnail.php?id=<?php echo (int)$img['id']; ?>"
                data-full-src="<?php echo htmlspecialchars($img['url'], ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars($img['nome']); ?>" loading="lazy">
            <div class="card-actions">
                <button class="btn-view" data-id="<?php echo $img['id']; ?>"><i class="fas fa-eye"></i> Visualizar</button>
                <button class="btn-copy" data-id="<?php echo $img['id']; ?>"><i class="fas fa-copy"></i> Copiar</button>
                <button class="btn-download" data-id="<?php echo $img['id']; ?>"><i class="fas fa-download"></i> Baixar</button>
                <button class="btn-delete btn-danger" data-id="<?php echo $img['id']; ?>"><i class="fas fa-trash"></i> Excluir</button>
            </div>
        </div>
        <div class="card-footer">
            <div class="name"><?php echo htmlspecialchars($img['nome']); ?></div>
            <div class="size"><?php echo $img['tamanho']; ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
