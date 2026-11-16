<?php
/**
 * Partial: modal de visualização de imagem.
 * Extraído de index.php e album.php, onde a mesma marcação estava duplicada.
 */
?>
<div class="modal-overlay" id="modal-visualizar">
    <div class="modal">
        <button class="modal-close" id="modal-close-btn">&times;</button>
        <img id="modal-img" src="" alt="Visualização">
        <div class="info">
            <div class="name" id="modal-nome">Nome da imagem</div>
            <div class="size" id="modal-tamanho">Tamanho</div>
            <input type="hidden" id="modal-id" value="">
        </div>
        <div class="modal-actions">
            <button id="modal-copy"><i class="fas fa-copy"></i> Copiar link</button>
            <button id="modal-download"><i class="fas fa-download"></i> Baixar</button>
            <button id="modal-delete" class="btn-danger"><i class="fas fa-trash"></i> Excluir</button>
        </div>
    </div>
</div>
