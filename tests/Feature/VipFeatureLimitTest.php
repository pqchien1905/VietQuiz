<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Question;
use App\Models\User;
use App\Models\VipSubscription;
use App\Support\VipFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VipFeatureLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_teacher_limits_classes_bank_questions_and_quiz_size(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        for ($i = 0; $i < VipFeature::FREE_CLASS_LIMIT; $i++) {
            ClassModel::create([
                'teacher_id' => $teacher->id,
                'name' => 'Class '.$i,
                'code' => 'CLS'.$i,
            ]);
        }

        for ($i = 0; $i < VipFeature::FREE_BANK_QUESTION_LIMIT; $i++) {
            Question::create([
                'teacher_id' => $teacher->id,
                'content' => 'Bank question '.$i,
                'type' => 'short_answer',
                'options' => [],
                'correct_answer' => 'ok',
                'points' => 1,
            ]);
        }

        $this->assertFalse(VipFeature::canCreateClass($teacher));
        $this->assertFalse(VipFeature::canAddBankQuestions($teacher));
        $this->assertFalse(VipFeature::canUseQuizQuestionCount($teacher, VipFeature::FREE_QUIZ_QUESTION_LIMIT + 1));
        $this->assertTrue(VipFeature::canUseQuizQuestionCount($teacher, VipFeature::FREE_QUIZ_QUESTION_LIMIT));
    }

    public function test_active_vip_subscription_bypasses_free_limits(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        VipSubscription::create([
            'user_id' => $teacher->id,
            'plan' => 'monthly',
            'status' => 'active',
            'started_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $teacher->load('vipSubscription');

        $this->assertTrue(VipFeature::canCreateClass($teacher));
        $this->assertTrue(VipFeature::canAddBankQuestions($teacher, VipFeature::FREE_BANK_QUESTION_LIMIT + 1));
        $this->assertTrue(VipFeature::canUseQuizQuestionCount($teacher, VipFeature::FREE_QUIZ_QUESTION_LIMIT + 1));
    }
}
