<?php

namespace Tests\Unit;

use App\TrainingTrainer;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase;

/**
 * Pure model logic: boots the framework (casts need it) but never touches the database.
 */
class ModelsTest extends TestCase
{
    public function test_accounting_minutes_and_hours_are_derived_from_start_and_end(): void
    {
        $tt = new TrainingTrainer;
        $tt->accounting_time_start = Carbon::parse('2099-01-01 18:00:00');
        $tt->accounting_time_end = Carbon::parse('2099-01-01 19:30:00');

        $this->assertSame(90, $tt->accounting_minutes);
        $this->assertEquals(2.0, $tt->accounting_hours, 'an accounting hour has 45 minutes');
    }

    public function test_accounting_values_are_null_without_both_times(): void
    {
        $tt = new TrainingTrainer;
        $tt->accounting_time_start = Carbon::parse('2099-01-01 18:00:00');

        $this->assertNull($tt->accounting_minutes);
        $this->assertNull($tt->accounting_hours);
    }

    public function test_accounting_minutes_across_midnight(): void
    {
        $tt = new TrainingTrainer;
        $tt->accounting_time_start = Carbon::parse('2099-01-01 23:30:00');
        $tt->accounting_time_end = Carbon::parse('2099-01-02 00:15:00');

        $this->assertSame(45, $tt->accounting_minutes);
    }

    public function test_user_name_attribute_concatenates_first_and_family_name(): void
    {
        $user = new User(['firstName' => 'Max', 'familyName' => 'Muster']);

        $this->assertSame('Max Muster', $user->name);
    }

    public function test_user_hides_sensitive_attributes_when_serialized(): void
    {
        $user = new User(['firstName' => 'A', 'familyName' => 'B', 'email' => 'a@b.de']);
        $user->setRelation('roles', collect());
        $user->setRelation('groups', collect());
        $user->setRelation('trainerBranches', collect());
        $user->email_verification_token = 'secret';
        $user->remember_token = 'secret';
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertArrayNotHasKey('email_verification_token', $array);
    }
}
