# LibreBlocks Core

Base compartilhada da família LibreBlocks: campos, tokens, contexto, REST e painel.

- WordPress 6.6+ e PHP 8.0+
- Gutenberg e Elementor
- Licença: GPL-2.0-or-later (veja [LICENSE](LICENSE))
- Versão atual: 0.3.2

*Shared foundation for the LibreBlocks plugin family: fields, tokens, context, REST and admin.*

## Instalação

Baixe o zip pronto em [libreblocks.com.br](https://libreblocks.com.br) (ou no diretório do WordPress.org) e envie em **Plugins → Adicionar novo → Enviar plugin**.

## Desenvolvimento

Os plugins LibreBlocks são desenvolvidos lado a lado. Clone os repositórios na mesma pasta, com estes nomes:

```bash
git clone https://github.com/USUARIO/libreblocks-core.git
```

Depois, em `libreblocks-core/`, rode `npm install`. Então:

- `npm run build` gera os arquivos em `build/`
- `phpunit` roda os testes de `tests/Unit` (sem banco de dados)
