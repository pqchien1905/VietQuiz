<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Demo Teacher 1
        User::create([
            'name' => 'Nguyễn Văn An',
            'email' => 'teacher@demo.com',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
            'phone' => '0901234567',
            'subject' => 'Công nghệ Thông tin',
        ]);

        // Demo Teacher 2
        User::create([
            'name' => 'Trần Thị Mai',
            'email' => 'teacher2@demo.com',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
            'phone' => '0902345678',
            'subject' => 'Toán học',
        ]);

        // Demo Student
        User::create([
            'name' => 'Lê Minh Tuấn',
            'email' => 'student@demo.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'phone' => '0911234567',
        ]);

        // 29 more students
        $studentNames = [
            'Phạm Thị Hương', 'Hoàng Văn Bình', 'Ngô Thị Ngọc', 'Lê Hoàng Cường',
            'Cao Thị Vân', 'Lý Thị Quỳnh', 'Đỗ Minh Đức', 'Vũ Thị Lan',
            'Bùi Văn Khoa', 'Trịnh Thị Thu', 'Đinh Văn Nam', 'Dương Thị Hà',
            'Lương Văn Phúc', 'Tạ Thị Mai', 'Hồ Văn Long', 'Phan Thị Yến',
            'Châu Minh Trí', 'Nguyễn Thị Oanh', 'Trương Văn Hải', 'Lâm Thị Diệu',
            'Võ Văn Tùng', 'Mai Thị Hoa', 'Đặng Văn Kiên', 'Tô Thị Phương',
            'Huỳnh Văn Sơn', 'Thái Thị Linh', 'Kiều Văn Đạt', 'Quách Thị Nhi',
            'La Văn Thắng',
        ];

        foreach ($studentNames as $i => $name) {
            User::create([
                'name' => $name,
                'email' => 'student' . ($i + 2) . '@demo.com',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '091' . str_pad($i + 2, 7, '0', STR_PAD_LEFT),
            ]);
        }
    }
}
