# 🚀 Guia Rápido de Uso - WC Upsell

## 📦 Instalação

1. O plugin já está na pasta `wp-content/plugins/wc-upsell`
2. Vá em **Plugins** no WordPress
3. Encontre **WC Upsell** e clique em **Ativar**

## ⚙️ Configuração Básica

### Passo 1: Acessar Produto

1. Vá em **Produtos** > **Todos os Produtos**
2. Clique para editar qualquer produto

### Passo 2: Configurar Kits

1. Role até a meta box **"Upsell Kits"**
2. Clique em **"Adicionar Kit"**
3. Configure:
   - **Quantidade**: Ex: 2, 3, 4 unidades
   - **Preço do Kit**: Ex: R$ 239,90
   - **Badge**: Ex: "Mais Vendido", "Oferta Especial"
   - **Cor**: Escolha a cor do badge
   - **Ativo**: Marque para ativar o kit

4. Clique em **"Atualizar"** para salvar

### Passo 3: Visualizar

1. Vá até a página do produto no site
2. Você verá o seletor de kits acima do botão "Adicionar ao Carrinho"

## 💡 Exemplo Prático

**Produto**: Copo
**Preço Regular**: R$ 159,90

**Kits Configurados**:

| Quantidade | Preço Total | Preço/Uni | Desconto | Badge |
|------------|-------------|-----------|----------|-------|
| 1 | R$ 159,90 | R$ 159,90 | 0% | - |
| 2 | R$ 239,90 | R$ 119,95 | 25% | Ótimo Negócio |
| 3 | R$ 329,90 | R$ 109,97 | 31% | Mais Vendido |
| 4 | R$ 399,90 | R$ 99,98 | 37% | Maior Desconto |

## 📊 Painel de Controle

Acesse **WooCommerce** > **Upsell Kits** para:

- Ver estatísticas gerais
- Lista de produtos com kits
- Visualizar rapidamente configurações
- Acessar edição de produtos

## 🎨 Personalização

### Cores dos Badges

Cada kit pode ter sua própria cor de badge:
- Verde: #2e7d32 (Economize)
- Dourado: #ffd700 (Mais Vendido)
- Vermelho: #d32f2f (Maior Desconto)
- Azul: #2271b1 (Oferta Especial)

### Textos Sugeridos para Badges

- "Mais Vendido"
- "Maior Desconto"
- "Oferta Especial"
- "Melhor Custo-Benefício"
- "Recomendado"
- "Aproveite"

## 🛒 Como Funciona no Carrinho

1. Cliente seleciona um kit (ex: 3 unidades)
2. Clica em "Adicionar ao Carrinho"
3. O sistema adiciona 3 unidades com o preço do kit
4. No carrinho aparece:
   - Quantidade: 3
   - Preço unitário: R$ 109,97
   - Total: R$ 329,90
   - Indicação "Kit - 3 Unidades"

## 🔧 Recursos Avançados

### Ativar/Desativar Kits

- Desmarque "Ativo" para desativar um kit temporariamente
- O kit permanece salvo mas não aparece no frontend

### Reordenar Kits

- Arraste os kits pela alça (☰)
- A ordem é mantida na exibição

### Remover Kits

- Clique no ícone de lixeira (🗑️)
- Confirme a remoção

## 📱 Responsivo

O seletor de kits se adapta automaticamente:
- **Desktop**: Grid com múltiplas colunas
- **Tablet**: 2 colunas
- **Mobile**: 1 coluna

## ✅ Boas Práticas

1. **Descontos Progressivos**: Quanto maior o kit, maior o desconto
2. **Badges Estratégicos**: Use no kit que você quer promover
3. **Preços Psicológicos**: Use .90 ou .99 nos preços
4. **Limite de Kits**: 3-4 opções é ideal (não sobrecarregue)
5. **Teste**: Sempre visualize no frontend após configurar

## 🆘 Solução de Problemas

### Kit não aparece no produto

- ✓ Verifique se o kit está marcado como "Ativo"
- ✓ Salve o produto após configurar
- ✓ Limpe o cache do site/navegador

### Preço não está correto

- ✓ Verifique o preço regular do produto
- ✓ Recalcule salvando o kit novamente
- ✓ Verifique se há outros plugins de preço ativos

### Badge não aparece

- ✓ Certifique-se de preencher o campo "Badge"
- ✓ Escolha uma cor diferente do fundo

## 🔄 Compatibilidade

- ✅ WordPress 5.8+
- ✅ WooCommerce 6.0+ (incluindo 9.0)
- ✅ HPOS (High-Performance Order Storage)
- ✅ PHP 7.4+
- ✅ Temas padrão WooCommerce

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique este guia
2. Consulte o README.md
3. Abra uma issue no GitHub

---

**Desenvolvido com ❤️ para WooCommerce**
