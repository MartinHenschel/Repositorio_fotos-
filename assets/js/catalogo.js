// assets/js/catalogo.js

const URL_ALBUNS = 'api_albums.php';
const URL_UPLOAD = 'api_upload.php';
const SEM_ALBUM = 'Nenhum álbum';

// Token CSRF (injetado no <head> da página via <meta name="csrf-token">)
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

// DOM
const fileInput = document.getElementById('fileInput');
const btnEscolher = document.getElementById('btnEscolher');
const btnLimpar = document.getElementById('btnLimpar');
const btnEnviar = document.getElementById('btnEnviar');
const listaFotos = document.getElementById('listaFotos');
const contador = document.getElementById('contador');
const comboAlbum = document.getElementById('comboAlbum');
const btnAtualizarAlbuns = document.getElementById('btnAtualizarAlbuns');
const btnCriarAlbum = document.getElementById('btnCriarAlbum');
const formCriarAlbum = document.getElementById('formCriarAlbum');
const nomeNovoAlbum = document.getElementById('nomeNovoAlbum');
const btnCancelarCriarAlbum = document.getElementById('btnCancelarCriarAlbum');
const chkSubstituir = document.getElementById('chkSubstituir');
const statusDiv = document.getElementById('status');

let fotosSelecionadas = [];
let albunsDisponiveis = [];

// --- Lista ---
function atualizarLista() {
    listaFotos.innerHTML = '';
    fotosSelecionadas.forEach((file, index) => {
        const li = document.createElement('li');
        li.textContent = file.name;
        li.dataset.index = index;
        li.addEventListener('dblclick', () => removerItem(index));
        listaFotos.appendChild(li);
    });
    const qtd = fotosSelecionadas.length;
    contador.textContent = qtd ? `${qtd} foto(s) na fila` : 'Nenhuma foto selecionada';
    btnEnviar.disabled = (qtd === 0);
}

function removerItem(index) {
    fotosSelecionadas.splice(index, 1);
    atualizarLista();
}

function limparSelecao() {
    fotosSelecionadas = [];
    fileInput.value = '';
    atualizarLista();
}

// --- Álbuns ---
async function carregarAlbuns() {
    try {
        const resp = await fetch(URL_ALBUNS);
        if (!resp.ok) throw new Error('Erro ao buscar álbuns');
        const data = await resp.json();
        if (!data.sucesso) throw new Error(data.erro || 'Erro desconhecido');

        albunsDisponiveis = data.albuns || [];
        comboAlbum.innerHTML = '';
        const optNone = document.createElement('option');
        optNone.value = '';
        optNone.textContent = SEM_ALBUM;
        comboAlbum.appendChild(optNone);

        albunsDisponiveis.forEach(album => {
            const opt = document.createElement('option');
            opt.value = album.id;
            opt.textContent = album.nome;
            comboAlbum.appendChild(opt);
        });
        comboAlbum.value = '';
    } catch (err) {
        console.warn('Falha ao carregar álbuns:', err);
        comboAlbum.innerHTML = `<option value="">${SEM_ALBUM}</option>`;
        comboAlbum.value = '';
        statusDiv.textContent = 'Não foi possível carregar álbuns.';
    }
}

function abrirCriacaoDeAlbum() {
    formCriarAlbum.hidden = false;
    btnCriarAlbum.hidden = true;
    nomeNovoAlbum.value = '';
    nomeNovoAlbum.focus();
}

function fecharCriacaoDeAlbum() {
    formCriarAlbum.hidden = true;
    btnCriarAlbum.hidden = false;
}

async function criarAlbum(event) {
    event.preventDefault();
    const nome = nomeNovoAlbum.value.trim();
    if (!nome) return;

    btnCancelarCriarAlbum.disabled = true;
    formCriarAlbum.querySelector('button[type="submit"]').disabled = true;
    statusDiv.textContent = 'Criando álbum...';

    try {
        const formData = new FormData();
        formData.append('nome', nome);
        formData.append('csrf_token', CSRF_TOKEN);
        const response = await fetch('api_create_album.php', {
            method: 'POST',
            body: formData,
        });
        const data = await response.json();
        if (!response.ok || !data.sucesso) {
            throw new Error(data.erro || 'Não foi possível criar o álbum.');
        }

        await carregarAlbuns();
        comboAlbum.value = String(data.album.id);
        statusDiv.textContent = `Álbum "${data.album.nome}" criado e selecionado.`;
        fecharCriacaoDeAlbum();
    } catch (error) {
        statusDiv.textContent = error.message;
    } finally {
        btnCancelarCriarAlbum.disabled = false;
        formCriarAlbum.querySelector('button[type="submit"]').disabled = false;
    }
}

// --- Envio ---
async function enviarFotos() {
    if (fotosSelecionadas.length === 0) return;

    const substituir = chkSubstituir.checked;
    const albumId = comboAlbum.value || null;

    setControlsEnabled(false);
    statusDiv.textContent = 'Preparando envio...';

    let sucesso = 0, pulados = 0, erros = [];

    for (let i = 0; i < fotosSelecionadas.length; i++) {
        const file = fotosSelecionadas[i];
        statusDiv.textContent = `Enviando ${i+1}/${fotosSelecionadas.length}...`;

        const formData = new FormData();
        if (albumId) formData.append('album_id', albumId);
        formData.append('imagem', file);
        formData.append('csrf_token', CSRF_TOKEN);

        try {
            const resp = await fetch(URL_UPLOAD, {
                method: 'POST',
                body: formData,
            });
            const data = await resp.json();
            if (!data.sucesso) {
                // Se o erro indicar que já existe e não substituir, pulamos
                if (data.erro && data.erro.includes('já existe') && !substituir) {
                    pulados++;
                    continue;
                }
                throw new Error(data.erro || 'Erro no upload');
            }
            sucesso++;
        } catch (err) {
            erros.push(`${file.name}: ${err.message}`);
        }
    }

    let msg = `${sucesso} enviadas, ${pulados} já existiam (puladas), de ${fotosSelecionadas.length} no total.`;
    if (erros.length > 0) {
        msg += `\n\nFalhas:\n${erros.slice(0, 15).join('\n')}`;
        if (erros.length > 15) msg += `\n... e mais ${erros.length - 15} erro(s).`;
        alert('Concluído com avisos:\n' + msg);
    } else {
        alert('Sucesso!\n' + msg);
    }

    limparSelecao();
    setControlsEnabled(true);
    statusDiv.textContent = '';
}

function setControlsEnabled(enabled) {
    btnEscolher.disabled = !enabled;
    btnLimpar.disabled = !enabled;
    comboAlbum.disabled = !enabled;
    chkSubstituir.disabled = !enabled;
    btnEnviar.disabled = true;
    if (!enabled) {
        btnEnviar.textContent = 'Enviando...';
    } else {
        btnEnviar.textContent = 'Enviar';
        if (fotosSelecionadas.length > 0) btnEnviar.disabled = false;
    }
}

// --- Eventos ---
btnEscolher.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', (e) => {
    const files = Array.from(e.target.files);
    files.forEach(file => {
        if (!fotosSelecionadas.some(f => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified)) {
            fotosSelecionadas.push(file);
        }
    });
    atualizarLista();
    fileInput.value = '';
});

btnLimpar.addEventListener('click', limparSelecao);
btnEnviar.addEventListener('click', enviarFotos);
btnAtualizarAlbuns.addEventListener('click', carregarAlbuns);
btnCriarAlbum.addEventListener('click', abrirCriacaoDeAlbum);
btnCancelarCriarAlbum.addEventListener('click', fecharCriacaoDeAlbum);
formCriarAlbum.addEventListener('submit', criarAlbum);

// Inicialização
carregarAlbuns();
atualizarLista();