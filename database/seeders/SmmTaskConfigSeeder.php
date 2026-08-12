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
                'task_type' => 'facebook_cookies',
                'name' => 'Facebook Cookies',
                'rate' => 15.00,
                'status' => 'active',
                'notice' => 'ফ্রি ফেসবুকে কুকিজ সহ আইডি সাবমিট করুন (User, Password, Cookies)।',
                'video_url' => '',
                'daily_password' => 'fbcook12',
                'required_fields' => ['username', 'password', 'cookies']
            ],
            [
                'task_type' => 'facebook_zero_friend',
                'name' => 'Facebook 0 Friend ID',
                'rate' => 15.00,
                'status' => 'active',
                'notice' => '২ফা কোড সহ ০ ফ্রেন্ড ফেসবুক আইডি সাবমিট করুন (User, Password, 2FA)।',
                'video_url' => '',
                'daily_password' => 'fbzero34',
                'required_fields' => ['username', 'password', 'two_factor']
            ],
            [
                'task_type' => 'facebook_number_id',
                'name' => 'Facebook Number ID',
                'rate' => 15.00,
                'status' => 'active',
                'notice' => 'নাম্বার সহ ফেসবুক আইডি সাবমিট করুন (User, Password, 2FA, Number)।',
                'video_url' => '',
                'daily_password' => 'fbnum56',
                'required_fields' => ['username', 'password', 'two_factor', 'phone_number']
            ],
            [
                'task_type' => 'instagram_2fa',
                'name' => 'Instagram 2FA',
                'rate' => 12.00,
                'status' => 'active',
                'notice' => '২ফা কোড সহ ইনস্টাগ্রাম আইডি সাবমিট করুন (User, Password, 2FA)।',
                'video_url' => '',
                'daily_password' => 'insta2fa',
                'required_fields' => ['username', 'password', 'two_factor']
            ],
            [
                'task_type' => 'instagram_cookies',
                'name' => 'Instagram Cookies',
                'rate' => 12.00,
                'status' => 'active',
                'notice' => 'ইনস্টাগ্রাম কুকিজ সহ আইডি সাবমিট করুন (User, Password, Cookies)।',
                'video_url' => '',
                'daily_password' => 'instacook',
                'required_fields' => ['username', 'password', 'cookies']
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
            ],
            [
                'task_type' => 'global_notice',
                'name' => 'Global Notice',
                'rate' => 0.00,
                'status' => 'active',
                'notice' => 'রুটবা SMM পোর্টাল থেকে সরাসরি সাবমিট করে ইনকাম করুন ঝামেলা মুক্তভাবে!',
                'video_url' => '',
                'daily_password' => '',
                'required_fields' => []
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
