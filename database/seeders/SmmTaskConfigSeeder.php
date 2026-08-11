<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmmTaskConfig;

class SmmTaskConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            [
                'task_type' => 'gmail',
                'name' => 'Gmail Sell',
                'rate' => 18.00,
                'status' => 'active',
                'notice' => 'Used/Double Sell/Duplicate strict rules apply. Fresh Gmails only.',
                'video_url' => 'https://youtu.be/xaOT545aOT4',
                'daily_password' => 'aass1122',
                'required_fields' => ['gmail_address', 'password']
            ],
            [
                'task_type' => 'facebook',
                'name' => 'Facebook Sell',
                'rate' => 15.00,
                'status' => 'active',
                'notice' => 'Old profile required, minimum 50 friends.',
                'video_url' => '',
                'daily_password' => 'pass1234',
                'required_fields' => ['profile_url', 'password']
            ],
            [
                'task_type' => 'instagram',
                'name' => 'Instagram Sell',
                'rate' => 12.00,
                'status' => 'active',
                'notice' => 'Profile picture and 5+ posts required.',
                'video_url' => '',
                'daily_password' => 'insta99',
                'required_fields' => ['username', 'password', 'email', 'two_factor']
            ],
            [
                'task_type' => 'whatsapp',
                'name' => 'WhatsApp Sell',
                'rate' => 0.00,
                'status' => 'inactive',
                'notice' => 'Market Down থাকার কারণে WhatsApp Task অস্থায়ীভাবে Off রাখা হয়েছে 📉 পরিস্থিতি ভালো হলে পুনরায় চালু হয়ে যাবে ইনশাল্লাহ ✨',
                'video_url' => '',
                'daily_password' => '',
                'required_fields' => ['phone_number']
            ],
            [
                'task_type' => 'telegram',
                'name' => 'Telegram Sell',
                'rate' => 10.00,
                'status' => 'active',
                'notice' => 'Must verify with active code.',
                'video_url' => '',
                'daily_password' => 'tele44',
                'required_fields' => ['phone_number', 'verification_code']
            ]
        ];

        foreach ($configs as $config) {
            SmmTaskConfig::updateOrCreate(
                ['task_type' => $config['task_type']],
                $config
            );
        }
    }
}
