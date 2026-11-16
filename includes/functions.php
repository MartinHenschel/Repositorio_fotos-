<?php
require_once 'db.php';
require_once 'Logger.php';  

function getDB() {
    return Database::getInstance()->getPDO();
}

function getAllImages() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM images ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getImagesByAlbum($albumId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM images WHERE album_id = ? ORDER BY id DESC");
    $stmt->execute([$albumId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllAlbums() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM albums ORDER BY id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAlbum($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM albums WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function countImages() {
    $db = getDB();
    return $db->query("SELECT COUNT(*) FROM images")->fetchColumn();
}

function countAlbums() {
    $db = getDB();
    return $db->query("SELECT COUNT(*) FROM albums")->fetchColumn();
}

function countImagesInAlbum($albumId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM images WHERE album_id = ?");
    $stmt->execute([$albumId]);
    return $stmt->fetchColumn();
}

function deleteImage($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT url, nome FROM images WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($img) {
        if (strpos($img['url'], 'uploads/') === 0) {
            $filePath = __DIR__ . '/' . $img['url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM images WHERE id = ?");
        $stmt->execute([$id]);
        
        // ===== LOG =====
        $logger = new Logger();
        $logger->log('image_deleted', $_SESSION['user_id'] ?? 0, "Image: {$img['nome']} (ID: {$id})");
    }
}

function createAlbum($nome) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO albums (nome) VALUES (?)");
    $stmt->execute([$nome]);
    $id = $db->lastInsertId();
    
    // ===== LOG =====
    $logger = new Logger();
    $logger->log('album_created', $_SESSION['user_id'] ?? 0, "Album: {$nome} (ID: {$id})");
    
    return $id;
}

function uploadImage($nome, $albumId, $file) {
    $db = getDB();
    $maxUploadSize = 10 * 1024 * 1024;
    $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
        'image/webp' => 'webp',
    ];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || !isset($file['tmp_name'], $file['name'], $file['size'])
        || !is_uploaded_file($file['tmp_name'])
        || $file['size'] <= 0
        || $file['size'] > $maxUploadSize) {
        return false;
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $imageInfo = @getimagesize($file['tmp_name']);
    if (!isset($mimeToExtension[$mime]) || $imageInfo === false
        || $imageInfo[0] > MAX_IMAGE_WIDTH
        || $imageInfo[1] > MAX_IMAGE_HEIGHT
        || ($imageInfo[0] * $imageInfo[1]) > MAX_IMAGE_PIXELS) {
        return false;
    }

    $targetDir = UPLOAD_DIR;
    $ext = $mimeToExtension[$mime];
    $filename = uniqid() . '.' . $ext;
    $targetFile = $targetDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        $url = 'uploads/' . $filename;
        $tamanho = formatSize($file['size']);
        $data = date('Y-m-d');
        // Se não forneceu nome, usa o nome original
        if (empty($nome)) {
            $nome = $file['name'];
        }
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO images (nome, tamanho, data, url, album_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $tamanho, $data, $url, $albumId ?: null]);
            $imageId = (int)$db->lastInsertId();
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            @unlink($targetFile);
            return false;
        }
        
        // ===== LOG =====
        $logger = new Logger();
        $logger->log('image_uploaded', $_SESSION['user_id'] ?? 0, "{$filename} ({$tamanho})");
        
        return ['id' => $imageId, 'url' => $url];
    }
    return false;
}

function formatSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

// ==========================================================
// PERMISSÕES / CARGOS
// ==========================================================

/**
 * Catálogo de todas as permissões existentes no sistema, agrupadas por área.
 * Cada chave (ex: 'imagens_ver') é o identificador salvo no banco.
 */
function getPermissoesDisponiveis(): array
{
    return [
        'imagens' => [
            'label' => 'Imagens',
            'icon'  => 'fa-image',
            'itens' => [
                'imagens_ver'     => 'Visualizar imagens',
                'imagens_upload'  => 'Enviar imagens',
                'imagens_excluir' => 'Excluir imagens',
                'imagens_baixar'  => 'Baixar imagens',
            ],
        ],
        'albuns' => [
            'label' => 'Álbuns',
            'icon'  => 'fa-folder',
            'itens' => [
                'albuns_ver'     => 'Visualizar',
                'albuns_criar'   => 'Criar',
                'albuns_editar'  => 'Editar',
                'albuns_excluir' => 'Excluir',
            ],
        ],
        'usuarios' => [
            'label' => 'Usuários',
            'icon'  => 'fa-users',
            'itens' => [
                'usuarios_ver'     => 'Visualizar',
                'usuarios_criar'   => 'Criar',
                'usuarios_editar'  => 'Editar',
                'usuarios_excluir' => 'Excluir',
            ],
        ],
        'dashboard' => [
            'label' => 'Dashboard',
            'icon'  => 'fa-chart-bar',
            'itens' => [
                'dashboard_ver' => 'Visualizar',
            ],
        ],
        'logs' => [
            'label' => 'Logs',
            'icon'  => 'fa-history',
            'itens' => [
                'logs_ver' => 'Visualizar',
            ],
        ],
        'backups' => [
            'label' => 'Backups',
            'icon'  => 'fa-database',
            'itens' => [
                'backups_criar'     => 'Criar Backup',
                'backups_restaurar' => 'Restaurar Backup',
                'backups_baixar'    => 'Baixar Backup',
            ],
        ],
        'sistema' => [
            'label' => 'Sistema',
            'icon'  => 'fa-gear',
            'itens' => [
                'sistema_config' => 'Alterar configurações',
            ],
        ],
    ];
}

/**
 * Lista simples (sem agrupamento) com todas as chaves de permissão existentes.
 */
function getTodasPermissoesKeys(): array
{
    $todas = [];
    foreach (getPermissoesDisponiveis() as $grupo) {
        $todas = array_merge($todas, array_keys($grupo['itens']));
    }
    return $todas;
}

/**
 * Conjunto de permissões padrão sugerido para cada cargo.
 * Usado só para pré-marcar as caixinhas ao escolher um cargo — o
 * administrador ainda pode ajustar manualmente antes de salvar.
 */
function getCargoPresets(): array
{
    return [
        'administrador' => getTodasPermissoesKeys(),
        'supervisor' => [
            'imagens_ver', 'imagens_upload', 'imagens_excluir', 'imagens_baixar',
            'albuns_ver', 'albuns_criar', 'albuns_editar', 'albuns_excluir',
            'usuarios_ver',
            'dashboard_ver',
            'logs_ver',
        ],
        'operador' => [
            'imagens_ver', 'imagens_upload', 'imagens_baixar',
            'albuns_ver',
            'dashboard_ver',
        ],
        'personalizado' => [],
    ];
}

/**
 * Rótulos amigáveis para exibir o cargo na lista de usuários.
 */
function getCargoLabel(string $cargo): string
{
    $labels = [
        'administrador' => 'Administrador',
        'supervisor'    => 'Supervisor',
        'operador'      => 'Operador',
        'personalizado' => 'Personalizado',
    ];
    return $labels[$cargo] ?? ucfirst($cargo);
}

/**
 * Verifica se um conjunto de permissões (array de chaves) contém uma chave específica.
 */
function temPermissao(array $permissoesDoUsuario, string $chave): bool
{
    return in_array($chave, $permissoesDoUsuario, true);
}

// ==========================================================
// AUTENTICAÇÃO / USUÁRIOS (equipe de TI)
// Não existe cadastro público: contas só são criadas por
// alguém que já está logado, através de usuarios.php
// ==========================================================

function getUserByUsername($usuario) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function getUserById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

/**
 * Confere usuario/senha. Retorna os dados do usuário em caso de sucesso, ou false.
 */
function authenticateUser($usuario, $senha) {
    $user = getUserByUsername($usuario);
    if ($user && password_verify($senha, $user['senha'])) {
        return $user;
    }
    return false;
}

function userExists($usuario) {
    return getUserByUsername($usuario) !== null;
}

/**
 * Cria um novo usuário de TI. Só deve ser chamado a partir de uma página
 * já protegida por requireLogin() (ex: usuarios.php) — nunca a partir de
 * uma tela pública de cadastro.
 *
 * @param string $cargo      'administrador' | 'supervisor' | 'operador' | 'personalizado'
 * @param array  $permissoes Lista de chaves de permissão (ver getPermissoesDisponiveis())
 */
function createUser($usuario, $senha, $nome, string $cargo = 'operador', array $permissoes = []) {
    if (userExists($usuario)) {
        return false;
    }
    $db = getDB();
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    if (!array_key_exists($cargo, getCargoPresets())) {
        $cargo = 'operador';
    }
    // Só aceita chaves de permissão que realmente existem no catálogo
    $permissoesValidas = array_intersect($permissoes, getTodasPermissoesKeys());
    $permissoesJson = json_encode(array_values(array_unique($permissoesValidas)));

    $stmt = $db->prepare("INSERT INTO usuarios (usuario, senha, nome, cargo, permissoes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuario, $hash, $nome, $cargo, $permissoesJson]);
    $id = $db->lastInsertId();
    
    // ===== LOG =====
    $logger = new Logger();
    $logger->log('user_created', $_SESSION['user_id'] ?? 0, "User: {$usuario} ({$nome}) - Cargo: {$cargo} - ID: {$id}");
    
    return $id;
}

/**
 * Atualiza nome/login de um usuário existente. Se $novaSenha vier preenchida, troca a senha também.
 */
function updateUserInfo($id, $nome, $usuario, $novaSenha = '') {
    $db = getDB();
    if ($novaSenha !== '') {
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE usuarios SET nome = ?, usuario = ?, senha = ? WHERE id = ?");
        $stmt->execute([$nome, $usuario, $hash, $id]);
    } else {
        $stmt = $db->prepare("UPDATE usuarios SET nome = ?, usuario = ? WHERE id = ?");
        $stmt->execute([$nome, $usuario, $id]);
    }

    // ===== LOG =====
    $logger = new Logger();
    $logger->log('user_updated', $_SESSION['user_id'] ?? 0, "User ID: {$id} - Nome: {$nome}");
}

/**
 * Atualiza o cargo e as permissões de um usuário existente.
 */
function updateUserPermissions($id, string $cargo, array $permissoes) {
    $db = getDB();

    if (!array_key_exists($cargo, getCargoPresets())) {
        $cargo = 'operador';
    }
    $permissoesValidas = array_intersect($permissoes, getTodasPermissoesKeys());
    $permissoesJson = json_encode(array_values(array_unique($permissoesValidas)));

    $stmt = $db->prepare("UPDATE usuarios SET cargo = ?, permissoes = ? WHERE id = ?");
    $stmt->execute([$cargo, $permissoesJson, $id]);

    // ===== LOG =====
    $logger = new Logger();
    $logger->log('user_permissions_updated', $_SESSION['user_id'] ?? 0, "User ID: {$id} - Cargo: {$cargo}");
}

function getAllUsers() {
    $db = getDB();
    $stmt = $db->query("SELECT id, usuario, nome, cargo, permissoes, criado_em FROM usuarios ORDER BY id");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($usuarios as &$u) {
        $u['permissoes'] = json_decode($u['permissoes'] ?? '[]', true) ?: [];
    }
    unset($u);
    return $usuarios;
}

function deleteUser($id) {
    $db = getDB();
    
    // Pegar dados do usuário antes de deletar
    $stmt = $db->prepare("SELECT usuario, nome FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    
    // ===== LOG =====
    $logger = new Logger();
    $logger->log('user_deleted', $_SESSION['user_id'] ?? 0, "User: {$user['usuario']} ({$user['nome']}) - ID: {$id}");
}

function deleteAlbum($id) {
    $db = getDB();
    
    // Pegar nome do álbum antes de deletar (para o log)
    $stmt = $db->prepare("SELECT nome FROM albums WHERE id = ?");
    $stmt->execute([$id]);
    $album = $stmt->fetch(PDO::FETCH_ASSOC);

    // Remove o vínculo das imagens com o álbum
    $stmt = $db->prepare("UPDATE images SET album_id = NULL WHERE album_id = ?");
    $stmt->execute([$id]);

    // Exclui o álbum
    $stmt = $db->prepare("DELETE FROM albums WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    // ===== LOG =====
    $logger = new Logger();
    $logger->log('album_deleted', $_SESSION['user_id'] ?? 0, "Album: {$album['nome']} (ID: {$id})");
    
    return $result;
}

// ==========================================================
// PAGINAÇÃO
// ==========================================================

/**
 * Retorna um array de imagens paginado
 * @param int $page - número da página (começa em 1)
 * @param int $limit - imagens por página (padrão 15)
 * @return array ['images' => [...], 'total' => int, 'pages' => int, 'current_page' => int]
 */
function getImagesPaginated($page = 1, $limit = 15) {
    $page = max(1, (int)$page); // garante que é pelo menos 1
    $limit = max(1, (int)$limit); // garante que é pelo menos 1
    
    $db = getDB();
    
    // Total de imagens
    $total = $db->query("SELECT COUNT(*) FROM images")->fetchColumn();
    $pages = ceil($total / $limit);
    
    // Garante que a página não excede o máximo
    $page = min($page, max(1, $pages));
    
    // Calcula o offset
    $offset = ($page - 1) * $limit;
    
    // Busca as imagens da página
    $stmt = $db->prepare("SELECT * FROM images ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'images' => $images,
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page,
        'limit' => $limit
    ];
}

function getImagesWithoutAlbumPaginated($page = 1, $limit = 15) {
    $page = max(1, (int)$page);
    $limit = max(1, (int)$limit);
    $db = getDB();

    $total = (int)$db->query("SELECT COUNT(*) FROM images WHERE album_id IS NULL")->fetchColumn();
    $pages = (int)ceil($total / $limit);
    $page = min($page, max(1, $pages));
    $offset = ($page - 1) * $limit;

    $stmt = $db->prepare("SELECT * FROM images WHERE album_id IS NULL ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'images' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page,
        'limit' => $limit,
    ];
}

/**
 * Retorna imagens de um álbum paginado
 */
function getImagesByAlbumPaginated($albumId, $page = 1, $limit = 15) {
    $page = max(1, (int)$page);
    $limit = max(1, (int)$limit);
    
    $db = getDB();
    
    // Total de imagens no álbum
    $stmt = $db->prepare("SELECT COUNT(*) FROM images WHERE album_id = ?");
    $stmt->execute([$albumId]);
    $total = $stmt->fetchColumn();
    $pages = ceil($total / $limit);
    
    // Garante que a página não excede o máximo
    $page = min($page, max(1, $pages));
    
    // Calcula o offset
    $offset = ($page - 1) * $limit;
    
    // Busca as imagens da página
    $stmt = $db->prepare("SELECT * FROM images WHERE album_id = ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $albumId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'images' => $images,
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page,
        'limit' => $limit
    ];
}
?>