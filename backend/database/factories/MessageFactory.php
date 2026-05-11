<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    /**
     * Tag-keyed message templates.
     * Seeder picks a tag first, then pulls a matching title + content pair.
     * Tag always matches content — no lorem ipsum in messages.
     */
    public static function templates(): array
    {
        return [
            'item_requirement' => [
                ['title' => 'Low stock: Chicken Biryani',     'content' => 'Chicken Biryani stock has dropped below the threshold. We need a restock of at least 50 portions before the lunch rush tomorrow.'],
                ['title' => 'Restock request: Beverages',     'content' => 'Mango Lassi and Cold Coffee are nearly out. Requesting 60 units each to cover the rest of the week.'],
                ['title' => 'Low stock: Spring Rolls',        'content' => 'Vegetable Spring Rolls are almost finished. Current count is 4. Please approve a restock at your earliest convenience.'],
                ['title' => 'Urgent: Rice stock critical',    'content' => 'We are down to the last 10 kg of basmati rice. This will not last beyond today\'s service. Immediate restock required.'],
                ['title' => 'Restock needed: Combo Meals',    'content' => 'Staff Lunch Box and Student Special ingredients are running low. Please approve restocking before Monday.'],
            ],
            'customer_inquiry' => [
                ['title' => 'Customer complaint — order delayed',   'content' => 'A customer reported their order took over 30 minutes during peak hours. Please review queue handling and let me know if we need to adjust staffing.'],
                ['title' => 'Customer feedback — portion size',     'content' => 'We received feedback that the Combo Meal B portions have gotten smaller. Can you confirm whether the recipe or quantity was changed recently?'],
                ['title' => 'Inquiry about dietary options',        'content' => 'A customer asked whether we offer any gluten-free or vegan options. Should I direct them to specific items or is there a standard response?'],
                ['title' => 'Complaint — cold food served',         'content' => 'A customer reported that their Grilled Chicken Plate was served cold. I\'ve apologised on behalf of the team. Please review holding times for grill items.'],
            ],
            'staff_duty' => [
                ['title' => 'Shift handover notes',             'content' => 'Counter 2 handled 41 orders today. All cleared by 3pm. Fryer cleaned and signed off. Leftover soup disposed of per policy.'],
                ['title' => 'Schedule update — Friday shift',   'content' => 'Friday\'s shift has been adjusted due to a public holiday. Please confirm your availability by Thursday evening so we can arrange cover.'],
                ['title' => 'Reminder: closing checklist',      'content' => 'Please ensure the full closing checklist is completed before locking up. Last night, the chiller temperature log was not signed off.'],
                ['title' => 'Task: update menu board',          'content' => 'The physical menu board needs to be updated to reflect the new Combo Meal C. Please action this before tomorrow morning.'],
                ['title' => 'Handover: peak hour observations', 'content' => 'Peak hour ran from 12:15 to 1:30 today. Queue backed up at the till. Suggest opening a second payment point from 12pm tomorrow.'],
            ],
            'incident' => [
                ['title' => 'Equipment issue: Fryer down',      'content' => 'The main fryer tripped at 1pm and could not be reset. Snack orders are on hold. Maintenance has been called and is expected within 2 hours.'],
                ['title' => 'Spill on service counter',         'content' => 'Minor oil spill at counter 1 at approximately 11:45am. Area cleaned and sanitised immediately. No injuries reported. Logged for safety record.'],
                ['title' => 'Fridge temperature alert',         'content' => 'The beverage fridge temperature rose above safe threshold this morning. Stock has been checked. Please arrange for a technician inspection today.'],
                ['title' => 'Power outage — partial service',   'content' => 'We experienced a 20-minute partial power outage at 10am. Hot food service was paused. All equipment resumed normally. No stock loss.'],
            ],
            'other' => [
                ['title' => 'General update — staff meeting',   'content' => 'Reminder: there is a staff meeting tomorrow at 9am in the break room. Attendance is mandatory for all kitchen and counter staff.'],
                ['title' => 'Feedback on lunch service',        'content' => 'Overall lunch service ran smoothly today. Special commendation for the team handling the biryani station — zero complaints and fast turnaround.'],
                ['title' => 'Notice: inspection next week',     'content' => 'A health and safety inspection is scheduled for next Wednesday. Please ensure all stations, chillers and waste logs are in order by Tuesday.'],
                ['title' => 'Menu planning — next month',       'content' => 'We are reviewing the menu for next month. If you have suggestions for items to add or retire based on sales, please share them by end of week.'],
            ],
        ];
    }

    public function definition(): array
    {
        $templates = self::templates();
        $tag       = fake()->randomElement(array_keys($templates));
        $template  = fake()->randomElement($templates[$tag]);

        return [
            'tenant_id'   => null,
            'sender_id'   => null,
            'receiver_id' => null,
            'title'       => $template['title'],
            'content'     => $template['content'],
            'tag'         => $tag,
            'priority'    => fake()->randomElement(['high', 'medium', 'medium', 'low', 'low']),
            'is_read'     => fake()->boolean(60),
            'created_at'  => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at'  => fn(array $a) => $a['created_at'],
        ];
    }
}