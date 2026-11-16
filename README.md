
Sistema web interno para upload, organização e gerenciamento de imagens em álbuns.

## Funcionalidades

- Autenticação de usuários
- Controle de acesso por cargos e permissões
- Upload e validação de imagens
- Organização por álbuns
- Galeria com busca e paginação
- Filtro de imagens sem álbum
- Download de imagens
- Exportação de URLs para Excel
- Geração de miniaturas
- Dashboard com estatísticas
- Logs de atividades
- Rate limit no login
- Proteção CSRF
- Backups automáticos do banco SQLite
- Restauração de backups
- Verificação de arquivos físicos e registros do banco

## Tecnologias

- PHP 8.2+
- SQLite com PDO
- HTML, CSS e JavaScript
- Apache/XAMPP
- Composer
- PhpSpreadsheet
- Extensão GD

## Requisitos

- XAMPP com Apache e PHP
- PHP 8.2 ou superior
- Extensões PHP:
  - `pdo_sqlite`
  - `sqlite3`
  - `fileinfo`
  - `gd`
  - `zip`
  - `mbstring`
- Composer

## Instalação

Clone ou copie o projeto para a pasta pública do Apache:

```text
C:\xampp\htdocs\hospedagem_foto
