<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemFactory extends Factory
{
    /**
     * Structured catalog: category name → array of {name, description} pairs.
     * Seeder iterates this map — category is always the parent, items are its children.
     * Descriptions are contextual, never lorem ipsum.
     */
    public static function catalog(): array
    {
        return [
            'Rice & Curry' => [
                ['name' => 'Chicken Biryani',       'description' => 'Fragrant basmati rice slow-cooked with tender chicken and whole spices.'],
                ['name' => 'Beef Kacchi',            'description' => 'Layered raw-meat biryani marinated overnight with yoghurt and saffron.'],
                ['name' => 'Vegetable Khichuri',     'description' => 'Comfort rice and lentil porridge tempered with ghee and cumin.'],
                ['name' => 'Mutton Tehari',          'description' => 'Slow-cooked mutton with aromatic rice, fried onions and bay leaves.'],
                ['name' => 'Egg Fried Rice',         'description' => 'Wok-tossed day-old rice with scrambled egg, soy sauce and spring onion.'],
            ],
            'Noodles' => [
                ['name' => 'Chicken Noodle Soup',   'description' => 'Clear broth with soft egg noodles, shredded chicken and bok choy.'],
                ['name' => 'Beef Noodles',           'description' => 'Thick wheat noodles in a rich beef bone broth topped with braised beef slices.'],
                ['name' => 'Prawn Fried Noodles',   'description' => 'Wok-charred egg noodles tossed with tiger prawns, bean sprouts and chilli oil.'],
                ['name' => 'Vegetable Hakka Noodles', 'description' => 'Indo-Chinese style noodles stir-fried with mixed vegetables and soy sauce.'],
            ],
            'Snacks' => [
                ['name' => 'Vegetable Spring Roll',  'description' => 'Crispy fried rolls stuffed with seasoned mixed vegetables.'],
                ['name' => 'Chicken Nuggets',        'description' => 'Golden-fried bite-sized chicken pieces served with dipping sauce.'],
                ['name' => 'Onion Rings',            'description' => 'Beer-battered onion rings fried until golden and crunchy.'],
                ['name' => 'French Fries',           'description' => 'Thick-cut potato fries seasoned with sea salt and served with ketchup.'],
                ['name' => 'Samosa (2 pcs)',         'description' => 'Flaky pastry cones filled with spiced potato and green peas.'],
            ],
            'Beverages' => [
                ['name' => 'Mango Lassi',            'description' => 'Chilled yoghurt drink blended with ripe Alphonso mango pulp.'],
                ['name' => 'Lemon Iced Tea',         'description' => 'Freshly brewed black tea chilled and served over ice with lemon slices.'],
                ['name' => 'Cold Coffee',            'description' => 'Blended espresso with chilled milk and a hint of vanilla syrup.'],
                ['name' => 'Mineral Water (500ml)',  'description' => 'Chilled sealed mineral water bottle.'],
                ['name' => 'Fresh Orange Juice',     'description' => 'Cold-pressed juice from freshly squeezed oranges, served without added sugar.'],
                ['name' => 'Masala Chai',            'description' => 'Spiced milk tea brewed with ginger, cardamom, cinnamon and cloves.'],
            ],
            'Breakfast' => [
                ['name' => 'Paratha & Egg',          'description' => 'Flaky whole-wheat flatbread served with a fried egg and a side of pickle.'],
                ['name' => 'Toast & Butter',         'description' => 'Thick-sliced white bread toasted golden, served with butter and jam.'],
                ['name' => 'Pancake Stack',          'description' => 'Three fluffy buttermilk pancakes served with maple syrup and fresh berries.'],
                ['name' => 'Cereal Bowl',            'description' => 'Mixed grain cereal served with chilled full-cream milk.'],
                ['name' => 'Boiled Egg Plate',       'description' => 'Two soft-boiled eggs with buttered toast soldiers and a sprinkle of sea salt.'],
            ],
            'Grills' => [
                ['name' => 'Grilled Chicken Plate',  'description' => 'Marinated half chicken breast grilled over charcoal, served with rice and salad.'],
                ['name' => 'BBQ Beef Ribs',          'description' => 'Slow-smoked beef ribs glazed with tangy BBQ sauce, served with coleslaw.'],
                ['name' => 'Mixed Grill Platter',    'description' => 'A sharing platter of chicken tikka, seekh kebab and lamb chops with mint chutney.'],
                ['name' => 'Chicken Seekh Kebab',    'description' => 'Spiced minced chicken skewered and grilled on open flame, served with naan.'],
            ],
            'Soups' => [
                ['name' => 'Tom Yum Soup',           'description' => 'Thai hot and sour broth with lemongrass, kaffir lime, mushrooms and prawns.'],
                ['name' => 'Mushroom Cream Soup',    'description' => 'Velvety blended soup of button mushrooms, cream and thyme, served with bread.'],
                ['name' => 'Tomato Bisque',          'description' => 'Slow-roasted tomato soup finished with cream and fresh basil oil.'],
                ['name' => 'Sweet Corn Chicken Soup', 'description' => 'Chinese-style thick soup with creamed sweet corn and shredded chicken.'],
            ],
            'Sandwiches' => [
                ['name' => 'Chicken Club Sandwich',  'description' => 'Triple-decker sandwich with grilled chicken, bacon, lettuce, tomato and mayo.'],
                ['name' => 'Tuna Melt',              'description' => 'Toasted sandwich filled with tuna salad and melted cheddar cheese.'],
                ['name' => 'Egg Salad Sandwich',     'description' => 'Soft white bread filled with creamy egg salad and crisp lettuce.'],
                ['name' => 'Chicken Shawarma Roll',  'description' => 'Grilled spiced chicken wrapped in flatbread with garlic sauce and pickles.'],
            ],
            'Desserts' => [
                ['name' => 'Chocolate Fudge Cake',   'description' => 'Dense dark chocolate cake layered with ganache and finished with cocoa dust.'],
                ['name' => 'Vanilla Ice Cream',      'description' => 'Two scoops of Madagascar vanilla bean ice cream served in a chilled bowl.'],
                ['name' => 'Fruit Salad Cup',        'description' => 'Seasonal fresh fruit tossed with honey and a squeeze of lime.'],
                ['name' => 'Bread Pudding',          'description' => 'Warm baked bread pudding soaked in custard, served with vanilla sauce.'],
                ['name' => 'Rasgulla (3 pcs)',       'description' => 'Soft spongy cottage cheese balls soaked in light rose-flavoured sugar syrup.'],
            ],
            'Combo Meals' => [
                ['name' => 'Combo Meal A',           'description' => 'Chicken biryani, one side dish and a choice of beverage at a bundled price.'],
                ['name' => 'Combo Meal B',           'description' => 'Grilled chicken plate with french fries, coleslaw and a soft drink.'],
                ['name' => 'Student Special',        'description' => 'Rice, dal, one vegetable side and water — a filling budget-friendly meal.'],
                ['name' => 'Staff Lunch Box',        'description' => 'Rotating daily menu of two curries, rice, salad and a dessert cup.'],
            ],
        ];
    }

    public function definition(): array
    {
        // Fallback only — seeder always passes name, description, category_id directly
        return [
            'category_id'                => null,
            'name'                       => 'Item',
            'description'                => null,
            'image_path'                 => null,
            'price'                      => fake()->randomFloat(2, 30, 450),
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