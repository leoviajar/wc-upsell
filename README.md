# WC Upsell

Plugin profissional de upsell para WooCommerce com sistema de kits e descontos progressivos.

## Características

- ✅ Sistema de kits configurável por produto
- ✅ Descontos progressivos por quantidade
- ✅ Badges personalizáveis
- ✅ Compatível com HPOS (High-Performance Order Storage)
- ✅ Interface admin intuitiva
- ✅ Totalmente responsivo
- ✅ Internacionalizado e pronto para tradução

## Requisitos

- WordPress 5.8 ou superior
- WooCommerce 6.0 ou superior
- PHP 7.4 ou superior

## Instalação

1. Faça upload da pasta `wc-upsell` para o diretório `/wp-content/plugins/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Acesse WooCommerce > Upsell Kits para configurar

## Configuração

### Criando um Kit

1. Vá em WooCommerce > Upsell Kits
2. Clique em "Adicionar Novo Kit"
3. Selecione o produto
4. Configure as quantidades e preços
5. Adicione badges (opcional)
6. Salve as configurações

### Opções de Kit

- **Quantidade**: Número de unidades no kit
- **Preço**: Preço total do kit
- **Badge**: Texto exibido no badge (ex: "Mais Vendido", "Maior Desconto")
- **Cor do Badge**: Cor de fundo do badge

## Estrutura do Plugin

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
│   │   ├── class-wc-upsell-settings.php
│   │   └── views/
│   ├── core/
│   │   ├── class-wc-upsell-cart-handler.php
│   │   ├── class-wc-upsell-pricing-engine.php
│   │   └── class-wc-upsell-product-kit.php
│   ├── frontend/
│   │   ├── class-wc-upsell-frontend.php
│   │   └── templates/
│   └── class-wc-upsell.php
├── languages/
├── wc-upsell.php
└── README.md
```

## Hooks e Filtros

### Filtros

```php
// Modificar configuração do kit antes de salvar
add_filter( 'wc_upsell_before_save_kit', 'my_custom_kit_data', 10, 2 );

// Modificar HTML do seletor de kits
add_filter( 'wc_upsell_kit_selector_html', 'my_custom_selector_html', 10, 3 );

// Modificar preço calculado do kit
add_filter( 'wc_upsell_calculated_kit_price', 'my_custom_kit_price', 10, 3 );
```

### Actions

```php
// Executar ação após salvar kit
add_action( 'wc_upsell_after_save_kit', 'my_custom_action', 10, 2 );

// Executar ação quando kit é adicionado ao carrinho
add_action( 'wc_upsell_kit_added_to_cart', 'my_custom_cart_action', 10, 3 );
```

## Desenvolvimento

### Ambiente de desenvolvimento

```bash
# Clone o repositório
git clone https://github.com/yourusername/wc-upsell.git

# Entre na pasta
cd wc-upsell

# Instale dependências (se houver)
composer install
npm install

# Build assets
npm run build
```

## Compatibilidade HPOS

Este plugin é totalmente compatível com o novo sistema HPOS (High-Performance Order Storage) do WooCommerce 8.0+.

A compatibilidade é declarada através de:
```php
\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
```

## Changelog

### 1.0.0
- Lançamento inicial
- Sistema de kits por produto
- Painel admin completo
- Compatibilidade HPOS
- Display frontend responsivo

## Suporte

Para suporte, abra uma issue no [GitHub](https://github.com/yourusername/wc-upsell/issues).

## Licença

GPL v2 ou posterior - https://www.gnu.org/licenses/gpl-2.0.html

## Créditos

Desenvolvido com ❤️ para WooCommerce.
