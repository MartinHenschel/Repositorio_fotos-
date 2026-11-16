<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $this->pdo = new PDO('sqlite:' . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->initTables();
        } catch (PDOException $e) {
            die('Erro ao conectar ao banco: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPDO() {
        return $this->pdo;
    }

    private function initTables() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS albums (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                capa TEXT
            );
        ");
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS images (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                tamanho TEXT,
                data TEXT,
                url TEXT NOT NULL,
                album_id INTEGER,
                FOREIGN KEY(album_id) REFERENCES albums(id) ON DELETE SET NULL
            );
        ");
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario TEXT NOT NULL UNIQUE,
                senha TEXT NOT NULL,
                nome TEXT NOT NULL,
                criado_em TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // ===== MIGRAÇÃO: cargo + permissões =====
        // Adiciona as colunas se o banco já existia antes dessa versão
        // (SQLite não tem "ADD COLUMN IF NOT EXISTS", então checamos manualmente)
        $colunasUsuarios = $this->pdo->query("PRAGMA table_info(usuarios)")->fetchAll(PDO::FETCH_ASSOC);
        $nomesColunas = array_column($colunasUsuarios, 'name');
        if (!in_array('cargo', $nomesColunas)) {
            $this->pdo->exec("ALTER TABLE usuarios ADD COLUMN cargo TEXT NOT NULL DEFAULT 'operador'");
        }
        if (!in_array('permissoes', $nomesColunas)) {
            // Guardamos as permissões como um array JSON de chaves, ex: ["imagens_ver","albuns_criar"]
            $this->pdo->exec("ALTER TABLE usuarios ADD COLUMN permissoes TEXT NOT NULL DEFAULT '[]'");
        }

        // ===== CRIAR ÍNDICES =====
        // Isso deixa as buscas MUITO mais rápidas
        
        // Índices nas tabelas de imagens
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_images_album_id ON images(album_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_images_album_id_id ON images(album_id, id DESC)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_images_data ON images(data)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_images_url ON images(url)");
        
        // Índices na tabela de usuários
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_usuarios_usuario ON usuarios(usuario)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_usuarios_id ON usuarios(id)");
        
        // Índices na tabela de álbuns
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_albums_nome ON albums(nome)");

        // Cria um usuário admin padrão se ainda não existir nenhum usuário
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM usuarios");
        if ($stmt->fetchColumn() == 0) {
            $this->seedAdminUser();
        }
    }

    private function seedAdminUser() {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $todasPermissoes = json_encode([
            'imagens_ver', 'imagens_upload', 'imagens_excluir', 'imagens_baixar',
            'albuns_ver', 'albuns_criar', 'albuns_editar', 'albuns_excluir',
            'usuarios_ver', 'usuarios_criar', 'usuarios_editar', 'usuarios_excluir',
            'dashboard_ver', 'logs_ver', 'backups_criar', 'backups_restaurar', 'sistema_config',
        ]);
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (usuario, senha, nome, cargo, permissoes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $hash, 'Administrador', 'administrador', $todasPermissoes]);
    }

}
?>