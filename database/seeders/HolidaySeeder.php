<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 2025年の日本の祝日
        $holidays = [
            ['date' => '2025-01-01', 'name' => '元日'],
            ['date' => '2025-01-13', 'name' => '成人の日'],
            ['date' => '2025-02-11', 'name' => '建国記念の日'],
            ['date' => '2025-02-23', 'name' => '天皇誕生日'],
            ['date' => '2025-03-21', 'name' => '春分の日'],
            ['date' => '2025-04-29', 'name' => '昭和の日'],
            ['date' => '2025-05-03', 'name' => '憲法記念日'],
            ['date' => '2025-05-04', 'name' => 'みどりの日'],
            ['date' => '2025-05-05', 'name' => 'こどもの日'],
            ['date' => '2025-07-21', 'name' => '海の日'],
            ['date' => '2025-08-11', 'name' => '山の日'],
            ['date' => '2025-09-15', 'name' => '敬老の日'],
            ['date' => '2025-09-23', 'name' => '秋分の日'],
            ['date' => '2025-10-13', 'name' => 'スポーツの日'],
            ['date' => '2025-11-03', 'name' => '文化の日'],
            ['date' => '2025-11-23', 'name' => '勤労感謝の日'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create([
                'date' => Carbon::parse($holiday['date']),
                'name' => $holiday['name'],
            ]);
        }
    }
}
