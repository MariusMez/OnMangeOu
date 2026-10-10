import type { ExtractedMenu, Optimization } from "./schemas";

/** Carte fictive utilisée en mode démo (OPTICARTE_MOCK=1), avec quelques fautes volontaires. */
export const MOCK_MENU: ExtractedMenu = {
  restaurant_name: "Chez Lucien",
  cuisine_type: "bistrot",
  currency: "EUR",
  language: "fr",
  sections: [
    {
      name: "Entrées",
      items: [
        { name: "Soupe a l'oignon", description: "gratinée", price: 8, price_text: "8€", category: "entree", tags: [], is_highlighted: false },
        { name: "Oeufs mayonnaise", description: null, price: 6, price_text: "6€", category: "entree", tags: [], is_highlighted: false },
        { name: "Terrine de campagne", description: "maison, cornichons", price: 9, price_text: "9€", category: "entree", tags: ["fait maison"], is_highlighted: false },
        { name: "Salade de chèvre chaud", description: null, price: 11, price_text: "11€", category: "entree", tags: ["végétarien"], is_highlighted: false },
        { name: "Foie gras mi-cuit", description: "chutney de figue", price: 19, price_text: "19€", category: "entree", tags: [], is_highlighted: false },
      ],
    },
    {
      name: "Plats",
      items: [
        { name: "Steak frites", description: "sauce au poivre", price: 17, price_text: "17€", category: "plat", tags: [], is_highlighted: false },
        { name: "Confit de canard", description: "pommes sarladaises", price: 19, price_text: "19€", category: "plat", tags: [], is_highlighted: true },
        { name: "Poisson du jour", description: null, price: 21, price_text: "21€", category: "plat", tags: [], is_highlighted: false },
        { name: "Burger maison", description: "cheddar, bacon, frites", price: 16, price_text: "16€", category: "plat", tags: ["fait maison"], is_highlighted: false },
        { name: "Risotto aux champignon", description: null, price: 15, price_text: "15€", category: "plat", tags: ["végétarien"], is_highlighted: false },
        { name: "Côte de boeuf pour 2", description: "1 kg, gratin dauphinois", price: 58, price_text: "58€", category: "plat", tags: [], is_highlighted: true },
        { name: "Blanquette de veau", description: "riz pilaf", price: 18, price_text: "18€", category: "plat", tags: [], is_highlighted: false },
        { name: "Tartare de boeuf", description: "coupé au couteau", price: 18, price_text: "18€", category: "plat", tags: [], is_highlighted: false },
      ],
    },
    {
      name: "Desserts",
      items: [
        { name: "Crème brulée", description: null, price: 7, price_text: "7€", category: "dessert", tags: [], is_highlighted: false },
        { name: "Tarte tatin", description: "crème fraîche", price: 8, price_text: "8€", category: "dessert", tags: [], is_highlighted: false },
        { name: "Mousse au chocolat", description: null, price: 7, price_text: "7€", category: "dessert", tags: [], is_highlighted: false },
        { name: "Café gourmand", description: null, price: 9, price_text: "9€", category: "dessert", tags: [], is_highlighted: false },
      ],
    },
  ],
  spelling_issues: [
    { original: "Soupe a l'oignon", corrected: "Soupe à l'oignon", reason: "accent manquant sur « à »" },
    { original: "Risotto aux champignon", corrected: "Risotto aux champignons", reason: "accord du pluriel" },
    { original: "Crème brulée", corrected: "Crème brûlée", reason: "accent circonflexe" },
  ],
  reading_notes: null,
};

export const MOCK_OPTIMIZATION: Optimization = {
  summary:
    "Carte de bistrot lisible mais sous-exploitée : les descriptions sont rares, les prix sont alignés avec le symbole € ce qui pousse le client à comparer, et la côte de bœuf à 58 € écrase la gamme des plats. En recentrant l'offre, en enrichissant les descriptions et en mettant en avant les plats à forte marge (risotto, blanquette), le ticket moyen peut progresser sans changer la cuisine.",
  overall_score: 58,
  strengths: [
    "Rubriques claires et peu nombreuses",
    "Mentions « fait maison » déjà présentes",
    "Prix des entrées et desserts bien resserrés",
  ],
  corrections: MOCK_MENU.spelling_issues,
  description_rewrites: [
    {
      section: "Plats",
      original_name: "Risotto aux champignon",
      new_name: "Risotto crémeux aux champignons des bois",
      new_description: "Parmesan affiné 24 mois, huile de truffe, persillade",
      rationale: "Plat à forte marge : une description sensorielle justifie le prix et attire les végétariens.",
    },
    {
      section: "Plats",
      original_name: "Blanquette de veau",
      new_name: "Blanquette de veau de nos grands-mères",
      new_description: "Mijotée 4 heures, champignons de Paris, riz pilaf au beurre",
      rationale: "Le registre affectif renforce le plat signature du bistrot.",
    },
    {
      section: "Entrées",
      original_name: "Oeufs mayonnaise",
      new_name: "Œufs mayonnaise comme à Paris",
      new_description: "Mayonnaise montée minute, ciboulette",
      rationale: "Un classique bon marché qui gagne à être raconté.",
    },
  ],
  layout_recommendations: [
    {
      title: "Supprimer le symbole € et l'alignement des prix",
      detail: "Écrire les prix juste après la description, sans colonne alignée ni symbole monétaire, pour réduire la sensibilité au prix.",
      priority: "haute",
    },
    {
      title: "Isoler la côte de bœuf dans un encart « À partager »",
      detail: "Sortie de la liste des plats, elle ne fausse plus l'ouverture de gamme et devient un produit d'appel.",
      priority: "haute",
    },
    {
      title: "Placer le confit et le risotto en tête et en fin de rubrique",
      detail: "Les premières et dernières positions sont les plus lues : y placer les plats les plus rentables.",
      priority: "moyenne",
    },
    {
      title: "Ajouter une suggestion du chef encadrée",
      detail: "Un seul encadré par page, pour un plat à forte marge dans la zone de prix médiane.",
      priority: "moyenne",
    },
  ],
  pricing_recommendations: [
    { item: "Foie gras mi-cuit", current_price: 19, suggested_price: 16, rationale: "À 19 € l'entrée dépasse l'ouverture de gamme (×3,2). 16 € reste premium sans casser la cohérence." },
    { item: "Risotto aux champignon", current_price: 15, suggested_price: 16.5, rationale: "Plat à forte marge sous-tarifé par rapport au burger : +1,50 € reste dans la zone médiane." },
    { item: "Café gourmand", current_price: 9, suggested_price: 8.5, rationale: "Ramener le dessert le plus cher au niveau de la tatin pour resserrer la gamme." },
  ],
  menu_engineering: [
    { item: "Confit de canard", classification: "etoile", estimated_margin: "forte", estimated_popularity: "forte", action: "Garder en avant, position de tête de rubrique." },
    { item: "Steak frites", classification: "vache_a_lait", estimated_margin: "faible", estimated_popularity: "forte", action: "Conserver, mais ne plus le mettre en avant ; proposer un accompagnement payant." },
    { item: "Risotto aux champignon", classification: "enigme", estimated_margin: "forte", estimated_popularity: "moyenne", action: "Réécrire la description et le placer en fin de rubrique." },
    { item: "Blanquette de veau", classification: "enigme", estimated_margin: "forte", estimated_popularity: "moyenne", action: "En faire la suggestion du chef encadrée." },
    { item: "Côte de boeuf pour 2", classification: "poids_mort", estimated_margin: "faible", estimated_popularity: "faible", action: "Déplacer dans un encart « À partager » hors de la liste des plats." },
    { item: "Salade de chèvre chaud", classification: "vache_a_lait", estimated_margin: "moyenne", estimated_popularity: "forte", action: "Conserver telle quelle." },
  ],
  optimized_menu: {
    title: "Chez Lucien",
    tagline: "Cuisine de bistrot, faite maison, depuis 1987",
    sections: [
      {
        name: "Pour commencer",
        intro: null,
        items: [
          { name: "Soupe à l'oignon gratinée", description: "Oignons confits au beurre, croûtons, comté", price: 8, highlight: false },
          { name: "Œufs mayonnaise comme à Paris", description: "Mayonnaise montée minute, ciboulette", price: 6, highlight: false },
          { name: "Terrine de campagne maison", description: "Cornichons, pain de campagne grillé", price: 9, highlight: true },
          { name: "Salade de chèvre chaud", description: "Miel de l'Yonne, noix, jeunes pousses", price: 11, highlight: false },
          { name: "Foie gras mi-cuit", description: "Chutney de figue, brioche toastée", price: 16, highlight: false },
        ],
      },
      {
        name: "Nos plats",
        intro: null,
        items: [
          { name: "Confit de canard du Sud-Ouest", description: "Pommes sarladaises, salade d'herbes", price: 19, highlight: true },
          { name: "Steak frites", description: "Pièce du boucher, frites maison, sauce au poivre", price: 17, highlight: false },
          { name: "Poisson du jour", description: "Selon l'arrivage, beurre blanc, légumes de saison", price: 21, highlight: false },
          { name: "Burger maison", description: "Bœuf charolais, cheddar affiné, bacon fumé, frites", price: 16, highlight: false },
          { name: "Tartare de bœuf coupé au couteau", description: "Condiments maison, frites", price: 18, highlight: false },
          { name: "Blanquette de veau de nos grands-mères", description: "Mijotée 4 heures, champignons de Paris, riz pilaf au beurre", price: 18, highlight: true },
          { name: "Risotto crémeux aux champignons des bois", description: "Parmesan affiné 24 mois, huile de truffe, persillade", price: 16.5, highlight: false },
        ],
      },
      {
        name: "À partager",
        intro: "Pour deux gourmands",
        items: [
          { name: "Côte de bœuf maturée (1 kg)", description: "Gratin dauphinois, salade, sauce béarnaise", price: 58, highlight: false },
        ],
      },
      {
        name: "Douceurs",
        intro: null,
        items: [
          { name: "Crème brûlée à la vanille de Madagascar", description: null, price: 7, highlight: false },
          { name: "Tarte tatin, crème fraîche d'Isigny", description: null, price: 8, highlight: true },
          { name: "Mousse au chocolat noir 70 %", description: null, price: 7, highlight: false },
          { name: "Café gourmand", description: "Trois mignardises du jour", price: 8.5, highlight: false },
        ],
      },
    ],
    footer_note: "Prix nets, service compris. Liste des allergènes disponible sur demande.",
  },
};
