<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin par défaut
        DB::table('utilisateurs')->insert([
            'nom'              => 'Administrateur',
            'prenom'           => 'POS',
            'telephone'        => '0000000000',
            'role'             => 'admin',
            'mot_de_passe_hash'=> Hash::make('admin1234'),
            'actif'            => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Catégories
        $categories = [
            ['nom' => 'Alimentation',  'icone' => 'restaurant',      'couleur' => '#FF6B35'],
            ['nom' => 'Boissons',      'icone' => 'local_drink',      'couleur' => '#4ECDC4'],
            ['nom' => 'Hygiene',       'icone' => 'soap',             'couleur' => '#45B7D1'],
            ['nom' => 'Pharmacie',     'icone' => 'medical_services', 'couleur' => '#96CEB4'],
            ['nom' => 'Electronique',  'icone' => 'devices',          'couleur' => '#FFEAA7'],
            ['nom' => 'Habillement',   'icone' => 'checkroom',        'couleur' => '#DDA0DD'],
            ['nom' => 'Autre',         'icone' => 'category',         'couleur' => '#B0B0B0'],
        ];
        foreach ($categories as $cat) {
            DB::table('categories')->insert(array_merge($cat, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        // Produits de démonstration
        $produits = [
            ['nom' => 'Riz 5kg',       'prix_vente' => 3500, 'prix_achat' => 2800, 'code_barre' => '6001001', 'unite' => 'sac',   'categorie_id' => 1],
            ['nom' => 'Huile 1L',       'prix_vente' => 1200, 'prix_achat' => 900,  'code_barre' => '6001002', 'unite' => 'L',     'categorie_id' => 1],
            ['nom' => 'Sucre 1kg',      'prix_vente' => 800,  'prix_achat' => 600,  'code_barre' => '6001003', 'unite' => 'kg',    'categorie_id' => 1],
            ['nom' => 'Savon Omo',      'prix_vente' => 500,  'prix_achat' => 350,  'code_barre' => '6001004', 'unite' => 'piece', 'categorie_id' => 3],
            ['nom' => 'Eau minerale',   'prix_vente' => 300,  'prix_achat' => 150,  'code_barre' => '6001005', 'unite' => 'piece', 'categorie_id' => 2],
            ['nom' => 'Coca Cola',      'prix_vente' => 400,  'prix_achat' => 250,  'code_barre' => '6001006', 'unite' => 'piece', 'categorie_id' => 2],
            ['nom' => 'Pain',           'prix_vente' => 150,  'prix_achat' => 80,   'code_barre' => '6001007', 'unite' => 'piece', 'categorie_id' => 1],
            ['nom' => 'Lait concentre', 'prix_vente' => 600,  'prix_achat' => 420,  'code_barre' => '6001008', 'unite' => 'boite', 'categorie_id' => 1],
            ['nom' => 'Sardine boite',  'prix_vente' => 750,  'prix_achat' => 500,  'code_barre' => '6001009', 'unite' => 'boite', 'categorie_id' => 1],
            ['nom' => 'Allumettes',     'prix_vente' => 100,  'prix_achat' => 50,   'code_barre' => '6001010', 'unite' => 'piece', 'categorie_id' => 7],
            ['nom' => 'Bougie',         'prix_vente' => 200,  'prix_achat' => 120,  'code_barre' => '6001011', 'unite' => 'piece', 'categorie_id' => 7],
            ['nom' => 'Farine 1kg',     'prix_vente' => 700,  'prix_achat' => 500,  'code_barre' => '6001012', 'unite' => 'kg',    'categorie_id' => 1],
        ];

        foreach ($produits as $produit) {
            $id = DB::table('produits')->insertGetId(array_merge($produit, [
                'actif' => true, 'created_at' => now(), 'updated_at' => now(),
            ]));
            DB::table('stock')->insert([
                'produit_id'        => $id,
                'quantite_actuelle' => rand(10, 100),
                'seuil_alerte'      => 5,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        $this->command->info('Données initiales créées avec succès !');
    }
}
