// app.js - para interações com o PHP

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-visualizar');
    const modalImg = document.getElementById('modal-img');
    const modalNome = document.getElementById('modal-nome');
    const modalTamanho = document.getElementById('modal-tamanho');
    const modalId = document.getElementById('modal-id');

    // Token CSRF (injetado no <head> da página via <meta name="csrf-token">)
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function buscarImagemDoCard(id) {
        const card = document.querySelector(`.card[data-id="${id}"]`);
        return card ? card.querySelector('.card-image img') : null;
    }

    function abrirModal(id) {
        const card = document.querySelector(`.card[data-id="${id}"]`);
        if (!card) return;
        const img = card.querySelector('.card-image img');
        const nome = card.querySelector('.card-footer .name');
        const tamanho = card.querySelector('.card-footer .size');
        if (img) modalImg.src = img.dataset.fullSrc || img.src;
        if (nome) modalNome.textContent = nome.textContent;
        if (tamanho) modalTamanho.textContent = tamanho.textContent;
        modalId.value = id;
        modal.classList.add('active');
    }

    function fecharModal() {
        modal.classList.remove('active');
    }

    // Copia o link da imagem (usado tanto pelo botão do card quanto pelo do modal)
    function copiarLinkDaImagem(id) {
        const img = buscarImagemDoCard(id);
        if (!img) return;
        const imageUrl = img.dataset.fullSrc || img.src;
        navigator.clipboard.writeText(imageUrl).then(() => {
            alert('Link copiado para a área de transferência!');
        }).catch(() => {
            alert('Não foi possível copiar. Link: ' + imageUrl);
        });
    }

    // Abre a imagem em nova aba para download (usado pelo card e pelo modal)
    function baixarImagem(id) {
        const img = buscarImagemDoCard(id);
        if (img) window.open(img.dataset.fullSrc || img.src, '_blank');
    }

    // Pede confirmação e envia a exclusão via POST + CSRF (excluir.php não aceita mais GET).
    // Mensagem de confirmação é parametrizada pois o card e o modal usam textos diferentes.
    function excluirImagem(id, mensagemDeConfirmacao) {
        if (!confirm(mensagemDeConfirmacao)) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'excluir.php';
        form.style.display = 'none';

        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id';
        inputId.value = id;
        form.appendChild(inputId);

        const inputToken = document.createElement('input');
        inputToken.type = 'hidden';
        inputToken.name = 'csrf_token';
        inputToken.value = CSRF_TOKEN;
        form.appendChild(inputToken);

        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('click', function(e) {
        const botao = e.target.closest('button');
        if (!botao) return;

        if (botao.classList.contains('btn-view')) {
            abrirModal(botao.dataset.id);
        }
        if (botao.classList.contains('btn-copy')) {
            copiarLinkDaImagem(botao.dataset.id);
        }
        if (botao.classList.contains('btn-download')) {
            baixarImagem(botao.dataset.id);
        }
        if (botao.classList.contains('btn-delete')) {
            excluirImagem(botao.dataset.id, 'Tem certeza que deseja excluir esta imagem?');
        }

        if (botao.id === 'modal-close-btn' || botao.closest('.modal-overlay')) {
            fecharModal();
        }
        if (botao.id === 'modal-copy') {
            copiarLinkDaImagem(modalId.value);
        }
        if (botao.id === 'modal-download') {
            baixarImagem(modalId.value);
        }
        if (botao.id === 'modal-delete') {
            excluirImagem(modalId.value, 'Excluir esta imagem?');
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') fecharModal();
    });

    // Pesquisa (client-side)
    const campoDeBusca = document.getElementById('search-input');
    if (campoDeBusca) {
        campoDeBusca.addEventListener('input', function() {
            const termo = this.value.toLowerCase().trim();
            document.querySelectorAll('.card').forEach(card => {
                const nome = card.querySelector('.card-footer .name');
                if (!nome) return;
                const correspondeAoTermo = termo === '' || nome.textContent.toLowerCase().includes(termo);
                card.style.display = correspondeAoTermo ? '' : 'none';
            });
        });
    }
});