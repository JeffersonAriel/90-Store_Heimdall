<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CategoriaTipoProduto;
use App\Models\Produto;
use Illuminate\Support\Str;

class FullCategoryStructureSeeder extends Seeder
{
    public function run()
    {
        $tree = [
            'Futebol' => [
                'Chuteiras' => [
                    'Campo',
                    'Society',
                    'Futsal',
                    'Infantil',
                    'Profissional',
                ],
                'Camisas' => [
                    'Brasileirão' => [
                        'Corinthians', 'Flamengo', 'Palmeiras', 'São Paulo', 'Vasco da Gama', 'Grêmio', 
                        'Atlético Mineiro', 'Cruzeiro', 'Internacional', 'Fluminense', 'Botafogo', 'Santos', 
                        'Bahia', 'Vitória', 'Sport Recife', 'Fortaleza', 'Ceará', 'Athletico Paranaense', 
                        'Coritiba', 'Juventude', 'Red Bull Bragantino', 'Goiás', 'Cuiabá', 'América Mineiro', 
                        'Chapecoense', 'Santa Cruz', 'Paysandu', 'Remo', 'Criciúma', 'Avaí', 'Figueirense', 
                        'Ponte Preta', 'Guarani', 'Vila Nova', 'CRB', 'CSA', 'Náutico', 'Sampaio Corrêa', 
                        'Botafogo-SP', 'Novorizontino', 'Ituano', 'Operário-PR', 'Amazonas FC', 'Brusque', 
                        'Mirassol', 'Londrina', 'Paraná Clube', 'Joinville', 'Santo André', 'Bangu', 'Portuguesa'
                    ],
                    'Europeus' => [
                        // Inglaterra
                        'Manchester City', 'Manchester United', 'Arsenal', 'Liverpool', 'Chelsea', 'Tottenham Hotspur', 
                        'Newcastle United', 'Aston Villa', 'West Ham United', 'Brighton', 'Wolverhampton', 'Everton', 
                        'Fulham', 'Crystal Palace', 'Brentford', 'Bournemouth', 'Nottingham Forest', 'Leicester City', 
                        'Southampton', 'Ipswich Town', 'Leeds United', 'Sunderland',
                        // Espanha
                        'Real Madrid', 'Barcelona', 'Atlético de Madrid', 'Sevilla', 'Valencia', 'Real Betis', 
                        'Athletic Bilbao', 'Real Sociedad', 'Villarreal', 'Girona', 'Celta de Vigo', 'Osasuna', 
                        'Rayo Vallecano', 'Getafe', 'Mallorca', 'Espanyol', 'Valladolid', 'Deportivo La Coruña', 'Zaragoza',
                        // Itália
                        'Juventus', 'Milan', 'Inter de Milão', 'Napoli', 'Roma', 'Lazio', 'Fiorentina', 'Atalanta', 
                        'Torino', 'Bologna', 'Sampdoria', 'Genoa', 'Verona', 'Udinese', 'Parma', 'Cagliari', 'Monza', 'Como',
                        // Alemanha
                        'Bayern de Munique', 'Borussia Dortmund', 'Bayer Leverkusen', 'RB Leipzig', 'Eintracht Frankfurt', 
                        'VfB Stuttgart', 'Borussia Mönchengladbach', 'Wolfsburg', 'Werder Bremen', 'Schalke 04', 
                        'Hamburg SV', 'Hertha Berlin', 'Union Berlin', 'Freiburg', 'Mainz 05',
                        // França
                        'PSG', 'Olympique de Marseille', 'Lyon', 'Monaco', 'Lille', 'Rennes', 'Nice', 'Bordeaux', 'Saint-Étienne', 'Nantes',
                        // Portugal
                        'Benfica', 'Porto', 'Sporting CP', 'Braga', 'Vitória de Guimarães', 'Boavista',
                        // Holanda
                        'Ajax', 'PSV Eindhoven', 'Feyenoord', 'AZ Alkmaar',
                        // América do Sul / Outros Continentes
                        'Boca Juniors', 'River Plate', 'Racing Club', 'Independiente', 'San Lorenzo', 'Vélez Sarsfield', 
                        'Estudiantes', 'Peñarol', 'Nacional (Uruguai)', 'Colo-Colo', 'Universidad de Chile', 
                        'Liga de Quito', 'Barcelona de Guayaquil', 'Olimpia', 'Cerro Porteño', 'Atlético Nacional', 
                        'Millonarios', 'Al-Nassr', 'Al-Hilal', 'Al-Ittihad', 'Al-Ahli', 'Inter Miami', 'LA Galaxy'
                    ],
                    'Seleções' => [
                        // Américas
                        'Brasil', 'Argentina', 'Uruguai', 'Chile', 'Colômbia', 'México', 'EUA', 'Peru', 'Paraguai', 
                        'Equador', 'Venezuela', 'Bolívia', 'Costa Rica', 'Jamaica', 'Canadá', 'Panamá', 'Honduras', 
                        'El Salvador', 'Guatemala', 'Haiti',
                        // Europa
                        'França', 'Alemanha', 'Itália', 'Espanha', 'Inglaterra', 'Portugal', 'Holanda', 'Bélgica', 
                        'Croácia', 'Dinamarca', 'Suécia', 'Suíça', 'Áustria', 'Polônia', 'Sérvia', 'Turquia', 
                        'Grécia', 'Escócia', 'País de Gales', 'Irlanda', 'Noruega', 'Ucrânia', 'República Tcheca', 
                        'Eslováquia', 'Hungria', 'Romênia', 'Bulgária', 'Finlândia', 'Islândia', 'Eslovênia', 'Albânia',
                        // África
                        'Marrocos', 'Nigéria', 'Senegal', 'Egito', 'Gana', 'Camarões', 'Costa do Marfim', 'Argélia', 
                        'Tunísia', 'África do Sul', 'Mali', 'Burkina Faso', 'Guiné', 'RD Congo', 'Angola', 'Cabo Verde',
                        // Ásia / Oceania
                        'Japão', 'Coreia do Sul', 'Arábia Saudita', 'Austrália', 'Irã', 'Catar', 'EAU', 'Uzbequistão', 
                        'Iraque', 'China', 'Nova Zelândia'
                    ],
                    'Retrô' => [
                        'Anos 70',
                        'Anos 80',
                        'Anos 90',
                        'Anos 2000',
                        'Anos 2010',
                    ],
                    'Treino',
                ],
                'Calções',
                'Meiões',
                'Jaquetas',
                'Agasalhos',
                'Bolas',
                'Caneleiras',
                'Luvas de Goleiro',
                'Mochilas',
                'Bolsas',
                'Acessórios',
            ],
            'Basquete' => [
                'Camisas NBA' => [
                    // Conferência Leste
                    'Boston Celtics', 'Brooklyn Nets', 'New York Knicks', 'Philadelphia 76ers', 'Toronto Raptors', 
                    'Chicago Bulls', 'Cleveland Cavaliers', 'Detroit Pistons', 'Indiana Pacers', 'Milwaukee Bucks', 
                    'Atlanta Hawks', 'Charlotte Hornets', 'Miami Heat', 'Orlando Magic', 'Washington Wizards',
                    // Conferência Oeste
                    'Denver Nuggets', 'Minnesota Timberwolves', 'Oklahoma City Thunder', 'Portland Trail Blazers', 'Utah Jazz', 
                    'Golden State Warriors', 'LA Clippers', 'Los Angeles Lakers', 'Phoenix Suns', 'Sacramento Kings', 
                    'Dallas Mavericks', 'Houston Rockets', 'Memphis Grizzlies', 'New Orleans Pelicans', 'San Antonio Spurs'
                ],
                'Regatas',
                'Shorts',
                'Tênis',
                'Bolas',
                'Bonés',
                'Mochilas',
                'Acessórios',
            ],
            'NFL (Futebol Americano)' => [
                'Jerseys' => [
                    'Kansas City Chiefs', 'San Francisco 49ers', 'Dallas Cowboys', 'Philadelphia Eagles', 'Buffalo Bills', 
                    'Miami Dolphins', 'New England Patriots', 'Baltimore Ravens', 'Cincinnati Bengals', 'Pittsburgh Steelers', 
                    'Green Bay Packers', 'Detroit Lions', 'Chicago Bears', 'Minnesota Vikings', 'Tampa Bay Buccaneers', 
                    'Seattle Seahawks', 'Los Angeles Rams', 'Las Vegas Raiders', 'New York Jets', 'New York Giants', 
                    'Jacksonville Jaguars', 'Denver Broncos', 'Indianapolis Colts', 'Houston Texans', 'Tennessee Titans', 
                    'Cleveland Browns', 'Arizona Cardinals', 'Atlanta Falcons', 'Carolina Panthers', 'New Orleans Saints', 
                    'Los Angeles Chargers', 'Washington Commanders'
                ],
                'Camisetas',
                'Moletons',
                'Bonés',
                'Shorts',
                'Jaquetas',
                'Mochilas',
                'Acessórios',
            ],
            'MLB (Beisebol)' => [
                'Jerseys' => [
                    'New York Yankees', 'Los Angeles Dodgers', 'Boston Red Sox', 'Chicago Cubs', 'San Francisco Giants', 
                    'Atlanta Braves', 'Houston Astros', 'Philadelphia Phillies', 'San Diego Padres', 'Toronto Blue Jays'
                ],
                'Bonés',
                'Camisetas',
                'Jaquetas'
            ],
            'Corrida' => [
                'Tênis',
                'Camisetas',
                'Shorts',
                'Leggings',
                'Jaquetas',
                'Mochilas',
                'Relógios',
                'Acessórios',
            ],
            'Academia' => [
                'Camisetas',
                'Regatas',
                'Shorts',
                'Calças',
                'Tênis',
                'Mochilas',
                'Garrafas',
                'Luvas',
                'Acessórios',
            ],
            'Vôlei' => [],
            'Futsal' => [],
            'Casual' => [
                'Camisetas',
                'Moletons',
                'Jaquetas',
                'Calças',
                'Bermudas',
                'Bonés',
                'Mochilas',
                'Tênis',
            ],
            'Infantil' => [],
            'Feminino' => [],
            'Outlet' => [
                'Até 30%',
                'Até 50%',
                'Até 70%',
                'Últimas Unidades',
            ],
        ];

        $keptIds = [];
        $this->syncCategoryTree($tree, null, $keptIds);

        // Mapear produtos vinculados a categorias legadas antes de deletá-las
        $oldCategories = CategoriaTipoProduto::whereNotIn('id', $keptIds)->get();
        foreach ($oldCategories as $oldCat) {
            $prods = Produto::where('categoria_id', $oldCat->id)->get();
            if ($prods->count() > 0) {
                $newCat = CategoriaTipoProduto::whereIn('id', $keptIds)->where('nome', $oldCat->nome)->first()
                       ?? CategoriaTipoProduto::where('nome', 'Futebol')->first();
                foreach ($prods as $prod) {
                    $prod->update(['categoria_id' => $newCat->id]);
                }
            }
        }

        // Remove categorias antigas duplicadas/legadas
        CategoriaTipoProduto::whereNotIn('id', $keptIds)->delete();
    }

    private function syncCategoryTree($categories, $parentId, &$keptIds)
    {
        $ordem = 1;
        foreach ($categories as $key => $value) {
            $name = is_string($key) ? $key : $value;
            
            $category = CategoriaTipoProduto::where('nome', $name)->where('parent_id', $parentId)->first();
            
            if (!$category) {
                $baseSlug = Str::slug($name);
                $slug = $baseSlug;
                $counter = 1;
                while (CategoriaTipoProduto::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $category = CategoriaTipoProduto::create([
                    'nome' => $name,
                    'parent_id' => $parentId,
                    'slug' => $slug,
                    'ativo' => true,
                    'ordem' => $ordem
                ]);
            } else {
                $category->update(['ordem' => $ordem, 'ativo' => true]);
            }

            $keptIds[] = $category->id;
            $ordem++;

            if (is_array($value) && count($value) > 0) {
                $this->syncCategoryTree($value, $category->id, $keptIds);
            }
        }
    }
}
