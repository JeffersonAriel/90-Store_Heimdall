/**
 * Gera dados determinísticos de avaliações baseados no ID do produto
 * para garantir consistência visual em toda a aplicação.
 */

const NOME_LIST = [
  "João S.", "Maria C.", "Pedro H.", "Lucas M.", "Carla F.", "Juliana R.",
  "Fernanda O.", "Rodrigo A.", "Thiago P.", "Rafael M.", "Beatriz G.", "Mariana L.",
  "Bruno T.", "Gabriel C.", "Diego V.", "Amanda S.", "Felipe N.", "Gustavo D."
];

const REVIEW_TEXTS = [
  "Produto excelente! Qualidade do material me surpreendeu e chegou antes do prazo.",
  "Muito bom, o tamanho ficou perfeito. Só achei que a cor era um pouco mais escura que na foto.",
  "Gostei bastante. Uso para treinar e é bem leve e confortável.",
  "Material muito resistente e acabamento impecável. Vale cada centavo!",
  "Chuteira muito leve, com excelente aderência. Comprei e recomendo com certeza.",
  "Amei! Superou minhas expectativas. A entrega foi super rápida.",
  "Bom custo benefício. Comprei meu tamanho padrão e serviu perfeitamente.",
  "Muito bonito e veste super bem. A loja está de parabéns pelo atendimento ágil.",
  "Ideal para o dia a dia. Confortável e de excelente qualidade de confecção.",
  "Comprei de presente e a pessoa adorou! Excelente material e costura reforçada.",
  "Excelente qualidade! As cores são exatamente como na foto do anúncio.",
  "Ótimo produto, caimento perfeito e muito confortável para caminhadas longas.",
  "Material de primeira linha. Ideal para treinos intensificados.",
  "Sensacional, design moderno e confortável demais. Recomendo muito!"
];

export function getRatingCount(productId) {
  if (!productId) return 12;
  // Retorna um número de avaliações determinístico entre 14 e 85
  return (productId * 17 + 5) % 72 + 14;
}

export function getRatingAverage(productId) {
  if (!productId) return 5;
  // Retorna uma média determinística de estrelas (de 4.0 a 5.0)
  const choices = [5, 4.5, 5, 4.5, 5, 4];
  const idx = (productId * 13 + 7) % choices.length;
  return choices[idx];
}

export function getStarsString(average) {
  if (average === 5) return "★★★★★";
  if (average === 4.5) return "★★★★☆"; // Pode ser aproximado ou usar estrela vazia
  if (average === 4) return "★★★★☆";
  return "★★★★★";
}

export function getProductReviews(productId) {
  if (!productId) return [];
  const count = (productId * 3 + 4) % 4 + 2; // entre 2 e 5 avaliações visíveis
  const reviews = [];
  
  for (let i = 0; i < count; i++) {
    const seed = productId * (i + 1) + i * 31;
    const authorIdx = seed % NOME_LIST.length;
    const textIdx = seed % REVIEW_TEXTS.length;
    
    // Gera uma data fictícia retroativa determinística
    const daysAgo = (seed % 28) + 1;
    const date = new Date();
    date.setDate(date.getDate() - daysAgo);
    const dateString = date.toLocaleDateString('pt-BR');
    
    // Determina a nota desta avaliação específica
    const starOptions = ["★★★★★", "★★★★★", "★★★★☆", "★★★★★", "★★★★☆"];
    const stars = starOptions[seed % starOptions.length];

    reviews.push({
      id: i,
      autor: NOME_LIST[authorIdx],
      texto: REVIEW_TEXTS[textIdx],
      data: dateString,
      estrelas: stars
    });
  }
  
  return reviews;
}
