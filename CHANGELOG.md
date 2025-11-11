# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2025-11-11

### Adicionado
- Estrutura inicial do plugin
- Sistema de kits configurável por produto
- Engine de pricing com descontos progressivos
- Painel administrativo completo
- Meta box na edição de produtos
- Página de configurações com dashboard
- Display frontend responsivo do seletor de kits
- Integração completa com carrinho WooCommerce
- Cálculo automático de descontos e preço unitário
- Sistema de badges personalizáveis
- Compatibilidade HPOS (High-Performance Order Storage)
- Assets CSS e JavaScript
- Internacionalização (i18n) pt_BR
- Hooks e filtros para extensibilidade
- Documentação completa

### Características
- ✅ Configurável para qualquer produto
- ✅ Múltiplos kits por produto
- ✅ Badges com cores personalizáveis
- ✅ Cálculo automático de economia
- ✅ Interface admin intuitiva
- ✅ Design responsivo
- ✅ Compatível com HPOS
- ✅ Sem controle de estoque (conforme especificado)

### Estrutura do Projeto
```
wc-upsell/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
├── includes/
│   ├── admin/
│   │   ├── class-wc-upsell-admin.php
│   │   └── views/
│   │       ├── product-meta-box.php
│   │       └── settings-page.php
│   ├── core/
│   │   ├── class-wc-upsell-cart-handler.php
│   │   ├── class-wc-upsell-pricing-engine.php
│   │   └── class-wc-upsell-product-kit.php
│   ├── frontend/
│   │   ├── class-wc-upsell-frontend.php
│   │   └── templates/
│   │       └── kit-selector.php
│   └── class-wc-upsell.php
├── languages/
│   └── wc-upsell-pt_BR.po
├── .gitignore
├── CHANGELOG.md
├── README.md
└── wc-upsell.php
```

### Tecnologias
- PHP 7.4+
- WordPress 5.8+
- WooCommerce 6.0+
- jQuery
- CSS3
- Git

### Próximas Melhorias Planejadas
- [ ] Suporte a produtos variáveis
- [ ] Relatórios de conversão de kits
- [ ] Testes A/B de kits
- [ ] Exportação/importação de configurações
- [ ] API REST para integração externa
- [ ] Widget para sidebar
- [ ] Shortcode para exibir kits

---

[1.0.0]: https://github.com/yourusername/wc-upsell/releases/tag/v1.0.0
