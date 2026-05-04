<?php

namespace Tests\Feature\Student;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_settings_page_displays_real_account_data(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Trần Gia Hân',
            'email' => 'giahan@example.com',
            'phone' => '0912345678',
        ]);

        $this->actingAs($student)
            ->get(route('student.settings'))
            ->assertOk()
            ->assertSee('Trần Gia Hân')
            ->assertSee('giahan@example.com')
            ->assertSee('0912345678')
            ->assertDontSee('student@demo.com')
            ->assertDontSee('Học sinh Demo');
    }

    public function test_student_can_update_profile_from_settings(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post(route('student.settings.profile'), [
                'name' => 'Tên mới',
                'email' => 'new-student@example.com',
                'phone' => '0900000000',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Tên mới',
            'email' => 'new-student@example.com',
            'phone' => '0900000000',
        ]);
    }

    public function test_student_can_update_password_and_export_data(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($student)
            ->post(route('student.settings.password'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password', $student->fresh()->password));

        $this->actingAs($student)
            ->get(route('student.settings.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/json; charset=UTF-8');
    }
}
