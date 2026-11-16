# Relatório do Sistema de Hospedagem de Fotos

## 1. Objetivo

Sistema web interno para hospedagem, organização e gerenciamento de imagens da empresa.

## 2. Principais funções

- Login de usuários.
- Controle de acesso por permissões.
- Cargos e permissões personalizadas.
- Upload de imagens.
- Validação de tipo e tamanho dos arquivos.
- Organização por álbuns.
- Visualização das imagens.
- Busca na galeria.
- Paginação de imagens.
- Filtro de imagens sem álbum.
- Download individual de imagens.
- Exportação de URLs dos álbuns em Excel.
- Geração de miniaturas para acelerar a galeria.
- Exclusão de imagens e álbuns.
- Dashboard com estatísticas.
- Logs de atividades.
- Rate limit no login.
- Proteção CSRF.
- Backups do banco de dados.
- Verificação de arquivos físicos e registros do banco.
- Testes automatizados básicos.

## 3. Tecnologias usadas

- PHP.
- SQLite com PDO.
- HTML, CSS e JavaScript.
- Apache/XAMPP.
- PhpSpreadsheet para geração de arquivos Excel.
- GD para criação de miniaturas.
- Composer para dependências.

## 4. Estrutura principal

- `index.php`: galeria geral.
- `album.php`: imagens de um álbum.
- `albums.php`: gerenciamento de álbuns.
- `upload.php`: tela de upload.
- `api_upload.php`: API de envio de imagens.
- `api_albums.php`: API de consulta de álbuns.
- `usuarios.php`: gerenciamento de usuários e permissões.
- `dashboard.php`: estatísticas do sistema.
- `download.php`: download individual.
- `download_album.php`: exportação de URLs em Excel.
- `thumbnail.php`: geração de miniaturas.
- `backups.php`: criação, listagem e restauração de backups.
- `includes/`: banco, autenticação, permissões, CSRF, logs e backups.
- `uploads/`: arquivos originais das imagens.
- `storage/thumbnails/`: miniaturas geradas.
- `tests/smoke.php`: testes básicos do sistema.

## 5. Segurança implementada

- Senhas armazenadas com `password_hash`.
- Regeneração de sessão após login.
- Cookies `HttpOnly`, `SameSite` e `Secure` condicional.
- Proteção CSRF nas ações POST.
- Rate limit contra tentativas repetidas de login.
- Validação MIME dos uploads.
- Limite de 10 MB por imagem.
- Bloqueio de scripts dentro de `uploads`.
- Proteção Apache para banco, logs, configurações e diretórios internos.
- Controle de permissões por ação.
- Validação de caminhos no download e nas miniaturas.
- Confirmação digitada para restauração de backup.
- Registro de ações em log.

## 6. Backups

Os backups do banco ficam fora da pasta pública do Apache:

```text
C:\xampp\hospedagem_foto_backups
```

O backup atual contém o SQLite. Ele ainda não inclui automaticamente a pasta `uploads`, portanto as fotos precisam de uma rotina de backup própria para que a restauração seja completa.

## 7. Melhorias ainda recomendadas

1. Fazer backup conjunto do banco e da pasta `uploads`.
2. Servir imagens originais por endpoint autenticado ou mover `uploads` para fora do `htdocs`.
3. Remover a senha fixa `admin123` da criação automática do administrador.
4. Usar `BASE_URL` validada na exportação do Excel.



## Conclusão

O sistema atende ao uso interno de hospedagem e organização de imagens, com autenticação, permissões, álbuns, uploads, miniaturas, exportação, logs e backups do banco. Para maior segurança e recuperação, a principal melhoria pendente é incluir as imagens da pasta `uploads` no backup junto com o banco.
