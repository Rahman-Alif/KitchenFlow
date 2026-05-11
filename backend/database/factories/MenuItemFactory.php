<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemFactory extends Factory
{
    // ── Popularity tier constants ─────────────────────────────────────────────
    // HOT     → ~5x pick weight  (everyday staples, always in demand)
    // REGULAR → 1x baseline
    // SLOW    → ~0.3x           (premium / niche / seasonal)
    public const TIER_HOT     = 'hot';
    public const TIER_REGULAR = 'regular';
    public const TIER_SLOW    = 'slow';

    // ── Basket affinity map ───────────────────────────────────────────────────
    // anchor item name → companion names frequently ordered together.
    // Seeder uses this when building order_items: after placing an anchor,
    // each companion has a high probability of being added too.
    public static function affinityGroups(): array
    {
        return [
            'Chicken Biryani'       => ['Mango Lassi',     'Raita Cup',      'Mineral Water (500ml)'],
            'Beef Kacchi'           => ['Borhani',          'Raita Cup'],
            'Mutton Tehari'         => ['Mango Lassi',      'Vegetable Spring Roll'],
            'Egg Fried Rice'        => ['Sweet Corn Chicken Soup', 'Cold Coffee'],
            'Paratha & Egg'         => ['Masala Chai',      'Boiled Egg Plate'],
            'Toast & Butter'        => ['Masala Chai',      'Cold Coffee'],
            'Pancake Stack'         => ['Fresh Orange Juice', 'Cold Coffee'],
            'French Fries'          => ['Cold Coffee',      'Lemon Iced Tea', 'Mineral Water (500ml)'],
            'Chicken Nuggets'       => ['French Fries',     'Cold Coffee'],
            'Samosa (2 pcs)'        => ['Masala Chai',      'Mineral Water (500ml)'],
            'Grilled Chicken Plate' => ['French Fries',     'Mushroom Cream Soup'],
            'BBQ Beef Ribs'         => ['Cold Coffee',      'Tomato Bisque'],
            'Chicken Seekh Kebab'   => ['Mango Lassi',      'Vegetable Spring Roll'],
            'Mixed Grill Platter'   => ['Mango Lassi',      'French Fries'],
            'Chicken Club Sandwich' => ['Tomato Bisque',    'Lemon Iced Tea'],
            'Chicken Shawarma Roll' => ['French Fries',     'Lemon Iced Tea'],
            'Chocolate Fudge Cake'  => ['Cold Coffee',      'Vanilla Ice Cream'],
            'Rasgulla (3 pcs)'      => ['Masala Chai'],
            'Combo Meal A'          => ['Mango Lassi',      'Mineral Water (500ml)'],
            'Combo Meal B'          => ['Cold Coffee',      'Lemon Iced Tea'],
        ];
    }

    // ── Category → allowed order hours ───────────────────────────────────────
    // [start_hour, end_hour] inclusive, 24-hour clock.
    // Seeder only places orders for items in a category during these hours.
    public static function categoryHours(): array
    {
        return [
            'Breakfast'    => [7,  11],
            'Rice & Curry' => [11, 22],
            'Noodles'      => [11, 22],
            'Grills'       => [12, 22],
            'Soups'        => [11, 21],
            'Snacks'       => [10, 22],
            'Sandwiches'   => [9,  21],
            'Beverages'    => [7,  22],
            'Desserts'     => [12, 22],
            'Combo Meals'  => [11, 21],
        ];
    }

    // ── Structured catalog ────────────────────────────────────────────────────
    // Each item: name, description, tier, price_min, price_max (BDT, 2025 Dhaka).
    public static function catalog(): array
    {
        return [
            'Rice & Curry' => [
                ['name' => 'Chicken Biryani',       'description' => 'Fragrant basmati rice slow-cooked with tender chicken and whole spices.',        'tier' => self::TIER_HOT,     'price_min' => 180, 'price_max' => 260],
                ['name' => 'Beef Kacchi',            'description' => 'Layered raw-meat biryani marinated overnight with yoghurt and saffron.',         'tier' => self::TIER_HOT,     'price_min' => 220, 'price_max' => 320],
                ['name' => 'Vegetable Khichuri',     'description' => 'Comfort rice and lentil porridge tempered with ghee and cumin.',                 'tier' => self::TIER_REGULAR, 'price_min' => 80,  'price_max' => 130],
                ['name' => 'Mutton Tehari',          'description' => 'Slow-cooked mutton with aromatic rice, fried onions and bay leaves.',            'tier' => self::TIER_SLOW,    'price_min' => 260, 'price_max' => 380],
                ['name' => 'Egg Fried Rice',         'description' => 'Wok-tossed day-old rice with scrambled egg, soy sauce and spring onion.',        'tier' => self::TIER_HOT,     'price_min' => 90,  'price_max' => 150],
            ],
            'Noodles' => [
                ['name' => 'Chicken Noodle Soup',    'description' => 'Clear broth with soft egg noodles, shredded chicken and bok choy.',              'tier' => self::TIER_REGULAR, 'price_min' => 120, 'price_max' => 180],
                ['name' => 'Beef Noodles',           'description' => 'Thick wheat noodles in a rich beef bone broth topped with braised beef slices.', 'tier' => self::TIER_REGULAR, 'price_min' => 150, 'price_max' => 220],
                ['name' => 'Prawn Fried Noodles',    'description' => 'Wok-charred egg noodles tossed with tiger prawns, bean sprouts and chilli oil.', 'tier' => self::TIER_SLOW,    'price_min' => 180, 'price_max' => 280],
                ['name' => 'Vegetable Hakka Noodles','description' => 'Indo-Chinese style noodles stir-fried with mixed vegetables and soy sauce.',     'tier' => self::TIER_HOT,     'price_min' => 100, 'price_max' => 160],
            ],
            'Snacks' => [
                ['name' => 'Vegetable Spring Roll',  'description' => 'Crispy fried rolls stuffed with seasoned mixed vegetables.',                    'tier' => self::TIER_HOT,     'price_min' => 60,  'price_max' => 100],
                ['name' => 'Chicken Nuggets',        'description' => 'Golden-fried bite-sized chicken pieces served with dipping sauce.',             'tier' => self::TIER_HOT,     'price_min' => 100, 'price_max' => 160],
                ['name' => 'Onion Rings',            'description' => 'Beer-battered onion rings fried until golden and crunchy.',                     'tier' => self::TIER_REGULAR, 'price_min' => 80,  'price_max' => 130],
                ['name' => 'French Fries',           'description' => 'Thick-cut potato fries seasoned with sea salt and served with ketchup.',        'tier' => self::TIER_HOT,     'price_min' => 70,  'price_max' => 120],
                ['name' => 'Samosa (2 pcs)',          'description' => 'Flaky pastry cones filled with spiced potato and green peas.',                  'tier' => self::TIER_HOT,     'price_min' => 40,  'price_max' => 80],
            ],
            'Beverages' => [
                ['name' => 'Mango Lassi',            'description' => 'Chilled yoghurt drink blended with ripe Alphonso mango pulp.',                  'tier' => self::TIER_HOT,     'price_min' => 60,  'price_max' => 100],
                ['name' => 'Lemon Iced Tea',         'description' => 'Freshly brewed black tea chilled and served over ice with lemon slices.',       'tier' => self::TIER_HOT,     'price_min' => 50,  'price_max' => 90],
                ['name' => 'Cold Coffee',            'description' => 'Blended espresso with chilled milk and a hint of vanilla syrup.',               'tier' => self::TIER_HOT,     'price_min' => 80,  'price_max' => 140],
                ['name' => 'Mineral Water (500ml)',  'description' => 'Chilled sealed mineral water bottle.',                                          'tier' => self::TIER_HOT,     'price_min' => 20,  'price_max' => 40],
                ['name' => 'Fresh Orange Juice',     'description' => 'Cold-pressed juice from freshly squeezed oranges, served without added sugar.', 'tier' => self::TIER_REGULAR, 'price_min' => 80,  'price_max' => 130],
                ['name' => 'Masala Chai',            'description' => 'Spiced milk tea brewed with ginger, cardamom, cinnamon and cloves.',            'tier' => self::TIER_HOT,     'price_min' => 30,  'price_max' => 60],
                ['name' => 'Borhani',                'description' => 'Traditional spiced yoghurt drink with mint and mustard, served chilled.',       'tier' => self::TIER_REGULAR, 'price_min' => 50,  'price_max' => 80],
                ['name' => 'Raita Cup',              'description' => 'Cooling yoghurt with cucumber, roasted cumin and fresh coriander.',             'tier' => self::TIER_REGULAR, 'price_min' => 40,  'price_max' => 70],
            ],
            'Breakfast' => [
                ['name' => 'Paratha & Egg',          'description' => 'Flaky whole-wheat flatbread served with a fried egg and a side of pickle.',     'tier' => self::TIER_HOT,     'price_min' => 60,  'price_max' => 100],
                ['name' => 'Toast & Butter',         'description' => 'Thick-sliced white bread toasted golden, served with butter and jam.',          'tier' => self::TIER_HOT,     'price_min' => 40,  'price_max' => 70],
                ['name' => 'Pancake Stack',          'description' => 'Three fluffy buttermilk pancakes served with maple syrup and fresh berries.',   'tier' => self::TIER_REGULAR, 'price_min' => 120, 'price_max' => 180],
                ['name' => 'Cereal Bowl',            'description' => 'Mixed grain cereal served with chilled full-cream milk.',                       'tier' => self::TIER_SLOW,    'price_min' => 80,  'price_max' => 120],
                ['name' => 'Boiled Egg Plate',       'description' => 'Two soft-boiled eggs with buttered toast soldiers and a sprinkle of sea salt.', 'tier' => self::TIER_REGULAR, 'price_min' => 50,  'price_max' => 90],
            ],
            'Grills' => [
                ['name' => 'Grilled Chicken Plate',  'description' => 'Marinated half chicken breast grilled over charcoal, served with rice and salad.','tier' => self::TIER_HOT,   'price_min' => 200, 'price_max' => 300],
                ['name' => 'BBQ Beef Ribs',          'description' => 'Slow-smoked beef ribs glazed with tangy BBQ sauce, served with coleslaw.',      'tier' => self::TIER_SLOW,    'price_min' => 380, 'price_max' => 550],
                ['name' => 'Mixed Grill Platter',    'description' => 'A sharing platter of chicken tikka, seekh kebab and lamb chops with mint chutney.','tier'=> self::TIER_SLOW,  'price_min' => 450, 'price_max' => 650],
                ['name' => 'Chicken Seekh Kebab',    'description' => 'Spiced minced chicken skewered and grilled on open flame, served with naan.',   'tier' => self::TIER_REGULAR, 'price_min' => 160, 'price_max' => 250],
            ],
            'Soups' => [
                ['name' => 'Tom Yum Soup',           'description' => 'Thai hot and sour broth with lemongrass, kaffir lime, mushrooms and prawns.',   'tier' => self::TIER_REGULAR, 'price_min' => 130, 'price_max' => 200],
                ['name' => 'Mushroom Cream Soup',    'description' => 'Velvety blended soup of button mushrooms, cream and thyme, served with bread.',  'tier' => self::TIER_REGULAR, 'price_min' => 120, 'price_max' => 180],
                ['name' => 'Tomato Bisque',          'description' => 'Slow-roasted tomato soup finished with cream and fresh basil oil.',              'tier' => self::TIER_SLOW,    'price_min' => 120, 'price_max' => 180],
                ['name' => 'Sweet Corn Chicken Soup','description' => 'Chinese-style thick soup with creamed sweet corn and shredded chicken.',         'tier' => self::TIER_HOT,     'price_min' => 100, 'price_max' => 160],
            ],
            'Sandwiches' => [
                ['name' => 'Chicken Club Sandwich',  'description' => 'Triple-decker sandwich with grilled chicken, bacon, lettuce, tomato and mayo.', 'tier' => self::TIER_HOT,     'price_min' => 150, 'price_max' => 220],
                ['name' => 'Tuna Melt',              'description' => 'Toasted sandwich filled with tuna salad and melted cheddar cheese.',            'tier' => self::TIER_REGULAR, 'price_min' => 140, 'price_max' => 200],
                ['name' => 'Egg Salad Sandwich',     'description' => 'Soft white bread filled with creamy egg salad and crisp lettuce.',              'tier' => self::TIER_REGULAR, 'price_min' => 90,  'price_max' => 140],
                ['name' => 'Chicken Shawarma Roll',  'description' => 'Grilled spiced chicken wrapped in flatbread with garlic sauce and pickles.',    'tier' => self::TIER_HOT,     'price_min' => 130, 'price_max' => 200],
            ],
            'Desserts' => [
                ['name' => 'Chocolate Fudge Cake',   'description' => 'Dense dark chocolate cake layered with ganache and finished with cocoa dust.',  'tier' => self::TIER_HOT,     'price_min' => 120, 'price_max' => 200],
                ['name' => 'Vanilla Ice Cream',      'description' => 'Two scoops of Madagascar vanilla bean ice cream served in a chilled bowl.',     'tier' => self::TIER_HOT,     'price_min' => 80,  'price_max' => 140],
                ['name' => 'Fruit Salad Cup',        'description' => 'Seasonal fresh fruit tossed with honey and a squeeze of lime.',                 'tier' => self::TIER_REGULAR, 'price_min' => 80,  'price_max' => 130],
                ['name' => 'Bread Pudding',          'description' => 'Warm baked bread pudding soaked in custard, served with vanilla sauce.',        'tier' => self::TIER_SLOW,    'price_min' => 100, 'price_max' => 160],
                ['name' => 'Rasgulla (3 pcs)',        'description' => 'Soft spongy cottage cheese balls soaked in light rose-flavoured sugar syrup.',  'tier' => self::TIER_HOT,     'price_min' => 60,  'price_max' => 100],
            ],
            'Combo Meals' => [
                ['name' => 'Combo Meal A',           'description' => 'Chicken biryani, one side dish and a choice of beverage at a bundled price.',   'tier' => self::TIER_HOT,     'price_min' => 250, 'price_max' => 350],
                ['name' => 'Combo Meal B',           'description' => 'Grilled chicken plate with french fries, coleslaw and a soft drink.',           'tier' => self::TIER_HOT,     'price_min' => 280, 'price_max' => 400],
                ['name' => 'Student Special',        'description' => 'Rice, dal, one vegetable side and water — a filling budget-friendly meal.',     'tier' => self::TIER_HOT,     'price_min' => 80,  'price_max' => 130],
                ['name' => 'Staff Lunch Box',        'description' => 'Rotating daily menu of two curries, rice, salad and a dessert cup.',            'tier' => self::TIER_REGULAR, 'price_min' => 120, 'price_max' => 180],
            ],
        ];
    }

    // Fallback — seeder always provides fields directly
    public function definition(): array
    {
        return [
            'category_id'                => null,
            'name'                       => 'Item',
            'description'                => null,
            'image_path'                 => null,
            'price'                      => fake()->randomFloat(2, 60, 350),
            'is_available'               => fake()->boolean(85),
            'needs_restock'              => false,
            'requested_restock_quantity' => null,
            'created_at'                 => fake()->dateTimeBetween('-6 months', '-4 months'),
            'updated_at'                 => fn(array $a) => $a['created_at'],
        ];
    }

    public function unavailable(): static
    {
        return $this->state(['is_available' => false, 'needs_restock' => true]);
    }
}