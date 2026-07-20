<?php

namespace Database\Seeders;

use Botble\Base\Facades\MetaBox;
use Botble\Base\Supports\BaseSeeder;
use Botble\RealEstate\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountSeeder extends BaseSeeder
{
    public function run(): void
    {
        Account::query()->truncate();

        $files = $this->uploadFiles('avatars');

        $faker = $this->fake();
        $now = $this->now();

        $socialLinks = [
            [
                [
                    'key' => 'name',
                    'value' => 'Facebook',
                ],
                [
                    'key' => 'icon',
                    'value' => 'ti ti-brand-facebook',
                ],
                [
                    'key' => 'url',
                    'value' => 'https://www.facebook.com/',
                ],
            ],
            [
                [
                    'key' => 'name',
                    'value' => 'Instagram',
                ],
                [
                    'key' => 'icon',
                    'value' => 'ti ti-brand-instagram',
                ],
                [
                    'key' => 'url',
                    'value' => 'https://www.instagram.com/',
                ],
            ],
            [
                [
                    'key' => 'name',
                    'value' => 'Twitter',
                ],
                [
                    'key' => 'icon',
                    'value' => 'ti ti-brand-x',
                ],
                [
                    'key' => 'url',
                    'value' => 'https://www.twitter.com/',
                ],
            ],
            [
                [
                    'key' => 'name',
                    'value' => 'YouTube',
                ],
                [
                    'key' => 'icon',
                    'value' => 'ti ti-brand-youtube',
                ],
                [
                    'key' => 'url',
                    'value' => 'https://www.youtube.com/',
                ],
            ],
        ];

        $emails = ['john.smith@botble.com', 'agent@botble.com'];

        foreach ($emails as $index => $email) {
            // Verify the first agent
            $isVerified = $index === 0;

            $data = [
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => $email,
                'username' => Str::slug($faker->unique()->userName()),
                'password' => Hash::make('12345678'),
                'dob' => $faker->dateTime(),
                'phone' => $phone = $faker->e164PhoneNumber(),
                'whatsapp' => $phone,
                'description' => $faker->realText(30),
                'credits' => 10,
                'confirmed_at' => $now,
                'approved_at' => $now,
                'avatar_id' => $faker->randomElements($files)[0]['data']->id,
                'is_public_profile' => true,
                'is_verified' => $isVerified,
            ];

            if ($isVerified) {
                $data['verified_at'] = Carbon::now()->subDays($faker->numberBetween(1, 365));
                $data['verified_by'] = 1; // Admin user
                $data['verification_note'] = 'Verified trusted agent';
            }

            $account = Account::query()->create($data);

            MetaBox::saveMetaBoxData($account, 'social_links', $socialLinks);
        }

        foreach (range(1, 10) as $index) {
            // Randomly verify about 40% of agents
            $isVerified = $faker->boolean(40);

            $data = [
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => $faker->email(),
                'username' => Str::slug($faker->unique()->userName()),
                'password' => Hash::make($faker->password()),
                'dob' => $faker->dateTime(),
                'phone' => $phone = $faker->e164PhoneNumber(),
                'whatsapp' => $phone,
                'description' => $faker->realText(30),
                'credits' => $faker->numberBetween(1, 10),
                'confirmed_at' => $now,
                'approved_at' => $now,
                'avatar_id' => $faker->randomElements($files)[0]['data']->id,
                'is_public_profile' => true,
                'is_featured' => $faker->boolean(),
                'is_verified' => $isVerified,
            ];

            if ($isVerified) {
                $data['verified_at'] = Carbon::now()->subDays($faker->numberBetween(1, 365));
                $data['verified_by'] = 1; // Admin user
                $data['verification_note'] = $faker->randomElement([
                    'Verified after background check',
                    'Documents verified successfully',
                    'Agent credentials confirmed',
                    'Verified trusted partner',
                    'Premium agent - verified',
                    null, // Some may not have notes
                ]);
            }

            $account = Account::query()->create($data);

            MetaBox::saveMetaBoxData($account, 'social_links', $socialLinks);
        }
    }
}
